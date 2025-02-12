<template>
   <div>
    <div class="md:col-span-3 sm:col-span-1 overflow-y-auto">
        <h2 class="text-lg font-semibold text-gray-800 mb-2" >
            <!-- {{ season_info.conferences[0].name }}  {{ season_info.seasons[0].name }} Standings -->
              Standings
        </h2>
        <table
            class="min-w-full divide-y divide-gray-200"
            v-if="
                season_standings &&
                season_standings.standings?.length > 0 && !loadingStandings
            "
        >
            <thead class="bg-gray-50">
                <tr>
                    <th
                        class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-nowrap"
                    >
                        #
                    </th>
                    <th
                        class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-nowrap"
                    >
                        Team
                    </th>
                    <th
                        class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-nowrap"
                    >
                        Wins
                    </th>
                    <th
                        class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-nowrap"
                    >
                        Loss
                    </th>
                    <th
                        class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-nowrap"
                    >
                        Rank
                    </th>
                    <th
                        class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-nowrap"
                    >
                        Status
                    </th>
                </tr>
            </thead>
            <tbody
                class="bg-rose-200 divide-y divide-gray-200 text-bold"
            >
                <tr
                    v-for="(team, index) in season_standings.standings"
                    :key="index"
                    :class="index <= 5
                        ? 'bg-orange-300 text-black text-bold' :
                    index >= 6 && index <=9
                        ? 'bg-blue-300 text-black text-bold' : ''"
                >
                    <td
                        class="px-2 py-2 whitespace-nowrap text-nowrap text-sm"
                    >
                        {{ team.conference_rank }}
                    </td>
                    <td
                        class="px-2 py-2 whitespace-nowrap uppercase text-sm"
                    >
                        <TeamDetails
                        :title="' Playoff Appearance:' + team.playoff_appearances"
                        :team_id="team.team_id" 
                        :showInfo="props.showLegend"
                        :current_conference_rank="team.conference_rank"
                        :season_id="team.season_id"
                        :showButton="0" 
                        :text="`${team.team_name}`" />
                    </td>
                    <td
                        class="px-2 py-2 whitespace-nowrap text-nowrap text-sm"
                    >
                        {{ team.wins }}
                    </td>
                    <td
                        class="px-2 py-2 whitespace-nowrap text-nowrap text-sm"
                    >
                        {{ team.losses }}
                    </td>
                    <td
                        class="px-2 py-2 whitespace-nowrap text-nowrap text-sm"
                    >
                        {{ team.overall_rank }}
                    </td>
                    <td class="px-2 py-2 whitespace-nowrap text-sm">
                        <div class="flex space-x-1">
                            <!-- Championships - Most important -->
                            <span
                                v-if="team.championships > 0"
                                class="flex items-center justify-center w-5 h-5 bg-yellow-600 text-black text-xs rounded-full"
                                title="National Championships"
                            >
                                {{ team.championships }}
                            </span>
                            <!-- Finals Appearances -->
                            <span
                            v-if="team.finals_appearances > 0"
                            class="flex items-center justify-center w-5 h-5 bg-green-300 text-black text-xs rounded-full"
                            title="National Finals Appearance"
                            >
                            {{ team.finals_appearances }}
                            </span>

                            <!-- Conference Championships -->
                            <span
                                v-if="team.conference_championships > 0"
                                class="flex items-center justify-center w-5 h-5 bg-gray-400 text-black text-xs rounded-full"
                                title="Conference Championships"
                            >
                                {{ team.conference_championships }}
                            </span>

                            <!-- Conference Finals Appearances (Runner-up) -->
                            <span
                            v-if="team.conference_finals_appearances > 0"
                            class="flex items-center justify-center w-5 h-5 bg-orange-500 text-black text-xs rounded-full"
                            title="Conference Finals Appearance"
                            >
                            {{ team.conference_finals_appearances }}
                            </span>

                            <!-- Overall Rank #1 -->
                            <!-- <span
                                v-if="team.overall_1_rank > 0"
                                class="flex items-center justify-center w-5 h-5 bg-blue-500 text-black text-sm rounded-full"
                                title="#1 Overall Rank"
                            >
                                {{ team.overall_1_rank }}
                            </span> -->

                            <!-- Conference Rank #1 -->
                            <!-- <span
                                v-if="team.conference_1_rank > 0"
                                class="flex items-center justify-center w-5 h-5 bg-green-500 text-black text-sm rounded-full"
                                title="#1 Conference Rank"
                            >
                                {{ team.conference_1_rank }}
                            </span> -->
                        </div>

                    </td>
                </tr>
            </tbody>
        </table>
        <div  v-if="loadingStandings" class="text-center font-bold text-red-500">
            Loading Standings...
        </div>
        <div  v-if="
                season_standings &&
                season_standings.standings?.length == 0 && !loadingStandings
            " class="text-center font-bold text-red-500">
            No Standings available
        </div>
        <!-- Stats List -->
        <ul class="mt-4 uppercase" v-if="season_info.seasons">
            <li class="flex space-x-3">
                <i class="fas fa-trophy"></i>Finals Champion:
                <TeamDetails
                            :team_id="season_info.seasons[0].finals_winner_id" 
                            :key="season_info.seasons[0].finals_winner_id" 
                            :showButton="0" 
                            :text="`${season_info.seasons[0].finals_winner_name}`" />
            </li>
            <li class="flex space-x-3">
                <i class="fas fa-medal"></i>Finals Runner Up:
                <TeamDetails
                            :team_id="season_info.seasons[0].finals_loser_id" 
                            :key="season_info.seasons[0].finals_loser_id" 
                            :showButton="0" 
                            :text="`${season_info.seasons[0].finals_loser_name}`" />
            </li>
            <li class="flex space-x-3">
                <i class="fas fa-trophy"></i> Regular Season Champion:
                <TeamDetails
                            :team_id="season_info.seasons[0].champion_id" 
                            :key="season_info.seasons[0].champion_id" 
                            :showButton="0" 
                            :text="`${season_info.seasons[0].champion_name}`" />
            </li>
            <li class="flex space-x-3">
                <i class="fas fa-bomb"></i> Weakest:
                <TeamDetails
                            :team_id="season_info.seasons[0].weakest_id" 
                            :key="season_info.seasons[0].weakest_id" 
                            :showButton="0" 
                            :text="`${season_info.seasons[0].weakest_name}`" />
            </li>
            <li class="flex space-x-3">
                <i class="fas fa-calendar-alt"></i> Season Name:
                <b>{{ season_info.seasons[0].name }}</b>
            </li>
        </ul>

    </div>
   </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";


const season_standings = ref(false);
const loadingStandings = ref(false);
const season_info = ref([]);
const props = defineProps({
    showLegend: {
        type: Boolean,
        default: false,
    },
    season_id: {
        type: [Number,String],
        required: true,
    },
    conference_id: {
        type: [Number,String],
        required: true,
    },
    season_data: Object,
});
const fetchConferenceStandings = async () => {
    try {
        season_standings.value = [];
        loadingStandings.value = true;
        const response = await axios.post(route("conferences.standings"), {
            season_id: props.season_id,
            conference_id: props.conference_id,
        });
        season_standings.value = response.data;
        loadingStandings.value = false;
    } catch (error) {
        console.error("Error fetching season standings:", error);
    }
};
const  getStandingsInfo = () => {
    season_info.value = props.season_data;
    fetchConferenceStandings();
}
onMounted(() => {
    getStandingsInfo();
});
</script>
