<template>
    <!-- Top 10 players module -->
    <div
        class="inline-block min-w-full min-h-screen overflow-hidden rounded p-4"
        v-if="team_info.teams" :style="{ backgroundColor: '#'+team_info.teams.secondary_color, }"
    >
        <h2 class="text-xl font-semibold text-white" v-if="team_info.teams">
            {{ team_info.teams.team_name ?? "-" }} ({{ team_info.teams.acronym ?? "-" }})
        </h2>
        <span
            v-if="team_info.teams"
            class="inline-flex items-center px-2.5 py-0.5 bg-green-300 text-green-600 rounded text-xs font-medium"
        >
            {{ team_info.teams.conference_name ?? "-" }}
        </span>
        <!-- Divider -->
        <hr class="my-4 border-t border-gray-200" />
        
        <div class="p-4 bg-white shadow-lg rounded min-h-screen">
            <h3 class="text-md font-semibold text-gray-800 mb-4">
            Star Players Per Season
            </h3>

            <table class="w-full text-xs">
                <thead  :style="{ backgroundColor: '#'+team_info.teams.primary_color, color: 'white' }">
                    <tr
                        class="border-b text-left text-nowrap text-xs font-semibold uppercase tracking-wide"
                    >
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-left font-semibold uppercase"
                        >
                            Rank
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-left font-semibold uppercase"
                        >
                            Player
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-left font-semibold uppercase"
                        >
                            Current Role
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-left font-semibold uppercase"
                        >
                            Current Team
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                        FG %
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            Avg. Points
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            Avg. Assist
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            Avg. Rebound
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            Avg. Blocks
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            Avg. Steals
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            PER
                        </th>
                        <th
                            class="border-b-2 border-gray-200 py-2 px-2 text-right font-semibold uppercase"
                        >
                            EFF
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(player,ii) in players"
                        v-if="players.length > 0 && !loading"
                        :key="ii"
                        class="text-gray-700"
                        @click.enter.prevent="showPlayerProfileModal = player.player_id"
                        :class="player.season_team != player.current_team ? 'bg-red-50' : 'bg-green-50'"
                    >
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.season_name }}
                            </p>
                        </td>
                        <td
                            :title="player.draft_status == 'Special Draft' || player.draft_status == 'Undrafted' ? 'S'+player.draft_id+' '+player.draft_status : player.draft_status"
                            class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate uppercase font-bold">
                                {{ player.player_name }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate uppercase">
                                {{ player.current_role }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate uppercase">
                                {{ player.current_team ?? 'FREE AGENT' }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.field_goal_percentage }} %
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.avg_points_per_game }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.avg_assists_per_game }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.avg_rebounds_per_game }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.avg_blocks_per_game }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ player.avg_steals_per_game }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ parseFloat(player.per ?? 0).toFixed(2) }}
                            </p>
                        </td>
                        <td
                            class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                        >
                            <p class="text-gray-900 whitespace-nowrap truncate">
                                {{ parseFloat(player.eff ?? 0).toFixed(2) }}
                            </p>
                        </td>
                    </tr>
                    <tr  v-if="players.length == 0 && !loading">
                        <td colspan="13" class="border-b text-center font-bold text-lg border-gray-200  px-3 py-3">
                            <p class="text-red-500 whitespace-no-wrap">No Data Found!</p>
                        </td>
                    </tr>
                    <tr  v-if="loading">
                        <td colspan="13" class="border-b text-center font-bold text-lg border-gray-200  px-3 py-3">
                            <p class="text-red-500 whitespace-no-wrap">Loading Data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- <div class="flex w-full overflow-auto mt-2">
                <Paginator
                    v-if="players.total"
                    :page_number="search_filters.page_num"
                    :total_rows="players.total ?? 0"
                    :itemsperpage="search_filters.itemsperpage"
                    @page_num="handlePagination"
                />
            </div> -->
        </div>
    </div>
   <Modal :show="showPlayerProfileModal" :maxWidth="'6xl'" title="Player Profile" @close="showPlayerProfileModal = false">
        <div class="p-6 block">
            <!-- Image Section -->
            <PlayerPerformance :key="showPlayerProfileModal" :player_id="showPlayerProfileModal" />
        </div>
    </Modal>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Paginator from "@/Components/Paginator.vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    team_id: Number,
});

const showPlayerProfileModal = ref(false);
const players = ref([]);
const team_info = ref([]);
const loading = ref(false);
const fetchTeamInfo = async () => {
    try {
        const response = await axios.post(route("teams.info"), {
            team_id: props.team_id,
        });
        team_info.value = response.data;
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};
const fetchTopPlayers = async () => {
    try {
        loading.value = true;
        const response = await axios.post(
            route("best.team.star.players"),
            {team_id: props.team_id,}
        );
        players.value = response.data;
        loading.value = false;
        console.log('loaded stars list');
    } catch (error) {
        console.error("Error fetching filtered players:", error);
    }
};

const handlePagination = (page_num) => {
    search_filters.page_num = page_num;
    fetchTopPlayers();
};

onMounted(() => {
    fetchTeamInfo();
    fetchTopPlayers();
});
</script>

<style scoped>
/* Additional custom styles */
table {
    border-collapse: collapse;
}
th,
td {
    border: 1px solid #ddd;
    padding: 0.5rem;
}
</style>
