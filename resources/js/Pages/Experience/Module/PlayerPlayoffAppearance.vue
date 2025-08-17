<template>
    <div class="bg-white inline-block min-w-full overflow-hidden rounded shadow p-2">
        <h3 class="text-md font-semibold text-gray-800">
            Player Playoff Filters
        </h3>

        <!-- Filters -->
        <div class="flex gap-2">
            <input
                type="text"
                v-model="search_filters.search"
                @input.prevent="fetchFilteredPlayers()"
                placeholder="Enter player name"
                class="mt-1 mb-2 p-2 border rounded w-full"
            />
            <select
                v-model="search_filters.sort_by"
                @change="fetchFilteredPlayers()"
                class="mt-1 mb-2 p-2 border rounded w-full"
            >
                <option value="playoff_appearances">Most Playoff Appearances</option>
                <option value="finals_appearances">Most Finals Appearances</option>
                <option value="big_four">Most Big 4 Appearances</option>
                <option value="seasons_played">Seasons Played</option>
                <option value="championships_won">Championships</option>
            </select>
        </div>
        <div v-if="isLoading" class="flex justify-center items-center py-4">
            <span class="loader mr-2"></span> Loading...
        </div>
        <table v-else class="w-full text-xs">
            <thead>
                <tr class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th :class="getThClass('player_name')">Player</th>
                    <th>Status</th>
                    <th>Current Team</th>
                    <th>Team Played (Playoffs)</th>
                    <th :class="getThClass('playoff_appearances')" class="text-right">Playoffs</th>
                    <th class="text-right">Play-ins (10th vs 9th)</th>
                    <th class="text-right">Play-ins (8th vs 7th)</th>
                    <th class="text-right">Play-ins Finals</th>
                    <th class="text-right">Conf. Quarters</th>
                    <th class="text-right">Conf. Semis</th>
                    <th class="text-right">Conf. Finals</th>
                    <th :class="getThClass('big_four')" class="text-nowrap">Big 4</th>
                    <th :class="getThClass('finals_appearances')" class="text-right">Nat'l Finals</th>
                    <th :class="getThClass('championships_won')" class="text-right">Championships</th>
                    <th :class="getThClass('seasons_played')" class="text-right">Seasons Played</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="player in players.data" :key="player.id" class="text-gray-700">
                    <td>
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.player_name }}
                        </p>
                    </td>
                    <td>
                        <span
                            v-if="!player.active_status"
                            class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                        >Retired</span>
                        <span
                            v-else
                            class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                        >Active</span>
                    </td>
                    <td>
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.current_team_name ?? "-" }}
                        </p>
                    </td>
                    <td>
                        <p class="text-gray-900 whitespace-nowrap truncate">
                            {{ player.teams_played_for_in_playoffs ?? "-" }}
                        </p>
                    </td>
                    <td class="text-right">{{ player.total_playoff_appearances }}</td>
                    <td class="text-right">{{ player.play_ins_elims_round_1_appearances }}</td>
                    <td class="text-right">{{ player.play_ins_elims_round_2_appearances }}</td>
                    <td class="text-right">{{ player.play_ins_finals_appearances }}</td>
                    <td class="text-right">{{ player.round_of_16_appearances }}</td>
                    <td class="text-right">{{ player.quarter_finals_appearances }}</td>
                    <td class="text-right">{{ player.semi_finals_appearances }}</td>
                    <td class="text-right">{{ player.interconference_semi_finals_appearances }}</td>
                    <td class="text-right">{{ player.finals_appearances }}</td>
                    <td class="text-right">{{ player.championships_won }}</td>
                    <td class="text-right">{{ player.experience ?? 0 }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex w-full overflow-auto mt-2">
            <Paginator
                v-if="players.total"
                :page_number="search_filters.page_num"
                :total_rows="players.total ?? 0"
                :itemsperpage="search_filters.itemsperpage"
                @page_num="handlePagination"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import Paginator from "@/Components/Paginator.vue";

const players = ref([]);
const isLoading = ref(false);
const search_filters = useForm({
    page_num: 1,
    itemsperpage: 10,
    search: "",
    sort_by: "playoff_appearances",
    sort_order: "desc",
});

const fetchFilteredPlayers = async () => {
    isLoading.value = true;
    try {
        const response = await axios.post(route("filter.playoffs.player"), search_filters);
        players.value = response.data;
    } catch (error) {
        console.error("Error fetching filtered players:", error);
    } finally {
        isLoading.value = false;
    }
};

const handlePagination = (page_num) => {
    search_filters.page_num = page_num;
    fetchFilteredPlayers();
};

const getThClass = (column) => {
    return [
        "border-b-2 border-gray-200 bg-gray-100 py-2 px-2 text-left font-semibold uppercase",
        {
            "bg-yellow-100 text-yellow-800": search_filters.sort_by === column,
        },
    ];
};

onMounted(() => {
    fetchFilteredPlayers();
});
</script>

<style scoped>
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
.loader {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3498db;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    animation: spin 1s linear infinite;
    display: inline-block;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
