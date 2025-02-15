<template>
    <!-- Top 10 players module -->
    <div
        class=" inline-block min-w-full overflow-hidden rounded p-4"
    >
        <h2 class="text-xl font-semibold text-gray-800" v-if="team_info.teams">
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
        <h3 class="text-md font-semibold text-gray-800 mb-6">
           Star Players Per Season
        </h3>

        <table class="w-full text-xs">
            <thead>
                <tr
                    class="border-b bg-gray-50 text-left text-nowrap text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-left font-semibold uppercase text-gray-600"
                    >
                        Rank
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-left font-semibold uppercase text-gray-600"
                    >
                        Player
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-left font-semibold uppercase text-gray-600"
                    >
                        Current Role
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-left font-semibold uppercase text-gray-600"
                    >
                        Current Team
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                       FG %
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                        Avg. Points
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                        Avg. Assist
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                        Avg. Rebound
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                        Avg. Blocks
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                        Avg. Steals
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
                    >
                        PER
                    </th>
                    <th
                        class="border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-right font-semibold uppercase text-gray-600"
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
                        class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                    >
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.player_name }}
                        </p>
                    </td>
                    <td
                        class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                    >
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.current_role }}
                        </p>
                    </td>
                    <td
                        class="border-b border-gray-200  px-2 py-2 text-ellipsis overflow-hidden"
                    >
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.current_team }}
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
                            {{ player.per }}
                        </p>
                    </td>
                    <td
                        class="border-b border-gray-200  px-2 py-2 text-right text-ellipsis overflow-hidden"
                    >
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.eff }}
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
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Paginator from "@/Components/Paginator.vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    team_id: Number,
});
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
th {
    background-color: #f4f4f4;
}
</style>
