<template>
  <div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <!-- Loading State -->
    <div v-if="loadingStandings" class="p-8 text-center">
      <div class="animate-pulse flex flex-col items-center">
        <div class="w-12 h-12 rounded-full bg-gray-200 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!season_standings?.standings?.length" class="p-8 text-center text-gray-500">
      <i class="fas fa-chart-bar text-3xl mb-2"></i>
      <p>No standings available</p>
    </div>

    <!-- Standings Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-nowrap">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
            <th scope="col" class="px-4 py-2 text-left text-xs text-yellow-500 font-medium text-gray-500 uppercase tracking-wider">W</th>
            <th scope="col" class="px-4 py-2 text-left text-xs text-red-500 font-medium text-gray-500 uppercase tracking-wider">L</th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PPG</th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Legacy</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(team, index) in season_standings.standings" 
              :key="index"
              :class="getTeamRowClass(index)">
            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
              {{ team.conference_rank }}
            </td>
            <td class="px-4 py-2 whitespace-nowrap">
              <TeamDetails
                :title="'Playoff Appearances: ' + team.playoff_appearances"
                :team_id="team.team_id"
                :showInfo="props.showLegend"
                :current_conference_rank="team.conference_rank"
                :season_id="team.season_id"
                class="text-sm text-black shadow-lg"
                :showButton="0"
                :text="`${team.team_city} ${team.team_name}`"
              />
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ team.wins }}</td>
            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ team.losses }}</td>
            <td class="px-4 py-2 whitespace-nowrap text-xs">{{ ((parseFloat(team.home_ppg ?? 0) + parseFloat(team.away_ppg ?? 0)) / 2).toFixed(2) }}</td>
            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ team.overall_rank }}</td>
            <td class="px-4 py-2 whitespace-nowrap">
              <div class="flex space-x-1.5">
                <Achievement v-if="team.championships > 0" 
                           type="championship" 
                           :count="team.championships" />
                <Achievement v-if="team.conference_championships > 0" 
                           type="conference" 
                           :count="team.conference_championships" />
                <Achievement v-if="team.overall_1_rank > 0" 
                           type="overall" 
                           :count="team.overall_1_rank" />
                <Achievement v-if="team.conference_1_rank > 0" 
                           type="conference_best" 
                           :count="team.conference_1_rank" />
                <Achievement v-if="team.is_grandslam > 0" 
                           type="grandslam" 
                           :count="team.is_grandslam" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Season Summary -->
    <div v-if="season_info.seasons" class="bg-gray-50 p-4 space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Season Highlights</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <SummaryItem icon="trophy" label="Finals Champion" 
                      :teamId="season_info.seasons[0].finals_winner_id"
                      :teamName="season_info.seasons[0].finals_winner_name" />
          <SummaryItem icon="medal" label="Finals Runner Up"
                      :teamId="season_info.seasons[0].finals_loser_id"
                      :teamName="season_info.seasons[0].finals_loser_name" />
        </div>
        <div class="space-y-2">
          <SummaryItem icon="crown" label="Regular Season Champion"
                      :teamId="season_info.seasons[0].champion_id"
                      :teamName="season_info.seasons[0].champion_name" />
          <SummaryItem icon="exclamation-circle" label="Lowest Ranked"
                      :teamId="season_info.seasons[0].weakest_id"
                      :teamName="season_info.seasons[0].weakest_name" />
        </div>
      </div>
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
import Achievement from "@/Pages/Seasons/Module/Achievement.vue";
import SummaryItem from "@/Pages/Seasons/Module/SummaryItem.vue";

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

const getTeamRowClass = (index) => {
  if (index <= 5) return 'bg-gradient-to-l from-orange-500 via-orange-200 via-orange-400 to-orange-500';
  if (index >= 6 && index <= 9) return 'bg-gradient-to-l from-blue-500 via-orange-200 via-blue-400 to-blue-500';
  return 'bg-gradient-to-l from-red-500 via-orange-200 via-red-400 to-red-500';
};
</script>
