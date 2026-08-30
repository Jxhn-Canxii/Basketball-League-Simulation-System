<template>
  <!-- Player Profile and Playoff Performance in One Row -->
  <div
    class="grid grid-cols-1 gap-6 p-2 rounded"
    :style="{
      background: main_performance.coach_details.primary_color
        ? `linear-gradient(to bottom, #${main_performance.coach_details.primary_color}, #${main_performance.coach_details.secondary_color}cc)`
        : '#f9fafb',
    }"
    v-if="!isLoading && main_performance.coach_details"
  >
    <!-- Player Details Section -->
    <div
      class="player-details mb-2 p-2 rounded flex-1 grid gap-6 grid-cols-3 xs:grid-cols-1"
      v-if="main_performance.coach_details"
    >
      <div class="bg-white rounded p-2">
        <h3 class="text-md font-semibold text-yellow-500 mb-2 flex items-center">
          <i class="fa fa-user text-blue-500 mr-2"></i>
          Coach Details
        </h3>
        <div class="ml-4">
          <p class="first-letter:uppercase">
            <strong>Coach ID:</strong> #{{ main_performance.coach_details.id ?? "-" }}
          </p>
          <p class="text-nowrap">
            <strong>Name:</strong>
            <span>
              {{ main_performance.coach_details.name ?? "-" }} ,{{
                main_performance.coach_details.age ?? "N/A"
              }}
              <sup
                :class="
                  main_performance.coach_details.is_active == 0
                    ? 'p-1 rounded-full text-red-500'
                    : 'p-1 rounded-full text-yellow-500'
                "
              >
                {{ main_performance.coach_details.is_active == 0 ? "R" : "A" }}
              </sup>
            </span>
          </p>
          <p>
            <strong>Team:</strong>
            {{ main_performance.coach_details.team_name ?? "-" }}
          </p>
          <p class="text-wrap capitalize">
            <strong>Coaching Style:</strong>
            {{ main_performance.coach_details.coaching_style ?? "-" }}
          </p>
        </div>
        <h3 class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center">
            <i class="fa fa-file-contract text-green-500 mr-2"></i>
            Contracts
        </h3>
        <div class="ml-4">
          <p>
            <strong>Contract Left:</strong>
            {{
              main_performance.coach_details.contract_years > 0
                ? main_performance.coach_details.contract_years + " years left"
                : "unsigned"
            }}
          </p>
        </div>
      </div>

      <div class="bg-white rounded p-2">
        <h3 class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center">
          <i class="fa fa-users text-green-500 mr-2"></i>
          Career
        </h3>
        <div class="ml-4">
          <p>
            <strong>Wins:</strong>
            {{ main_performance.coach_details.career_wins ?? 0 }}
          </p>
          <p>
            <strong>Loss:</strong>
            {{ main_performance.coach_details.career_losses ?? 0 }}
          </p>
          <p>
            <strong>Winrate:</strong>
            {{ main_performance.coach_details.winning_percentage ?? 0 }}%
          </p>
        </div>
      </div>

      <div class="bg-white rounded p-2">
        <h3 class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center">
          <i class="fa fa-list text-red-500 mr-2"></i>
          League Experience
        </h3>
        <div class="ml-4">
          <p>
            <strong>Season Exp:</strong>
            <span
              class="text-xs"
              :class="playerExpStatusClass(main_performance.season_count)"
            >
              {{
                playerExpStatusText(
                  main_performance.season_count ?? main_performance.season_count
                )
              }}
              ({{ main_performance.season_count ?? 0 }})
            </span>
          </p>
          <p>
            <strong>Playoff Exp:</strong>
            <span
              class="text-xs"
              :class="playerExpStatusClass(main_performance.playoff_count)"
            >
              {{
                playerExpStatusText(
                  main_performance.playoff_count ?? main_performance.playoff_count
                )
              }}
              ({{ main_performance.playoff_count ?? 0 }})
            </span>
          </p>
        </div>
        <!-- Playoff Performance Section -->
        <div class="playoff-performance bg-white mb-2 p-2 rounded flex-1">
          <h3 class="text-md font-semibold text-yellow-500 mb-2 flex items-center">
            <i class="fa fa-network-wired text-purple-500 mr-2"></i>
            Playoff
          </h3>
          <div
            v-if="main_performance.playoff_performance"
            class="ml-4 text-sm text-nowrap"
          >
            <p>
              <strong>Champions:</strong>
              {{ main_performance.playoff_performance.champion_count ?? 0 }}
            </p>
            <p>
              <strong>Playoff:</strong>
              {{ main_performance.playoff_performance.playoff_count ?? 0 }}
            </p>
          </div>
          <div v-else class="ml-4">
            <p>No playoff performance data available.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 xs:grid-cols-1 gap-6 p-2">
      <div class="awards bg-white mb-6 p-2 rounded flex-1 text-nowrap">
        <h3 class="text-md font-semibold text-yellow-500 mb-2 flex items-center">
          <i class="fa fa-trophy text-yellow-500 mr-2"></i>
          Championships ({{
            main_performance.national_championships?.length +
            main_performance.conference_championships?.length +
            main_performance.national_overall_champions?.length +
            main_performance.conference_overall_champions?.length
          }})
        </h3>
        <div v-if="main_performance.national_championships?.length > 0" class="ml-4">
          <h4 class="text-sm font-semibold text-gray-600 mb-2">
            National Championships
            {{
              main_performance.national_championships?.length > 0
                ? "(" + main_performance.national_championships?.length + ")"
                : ""
            }}
          </h4>
          <div
            v-for="(season, index) in main_performance.national_championships"
            :key="index"
            class="flex items-center mb-2"
          >
            <i class="fa fa-trophy text-yellow-500 mr-2"></i>
            <p class="text-xs">
              {{ season.season_name }} ({{ season.championship_team }})
            </p>
          </div>
        </div>
        <div v-if="main_performance.conference_championships?.length > 0" class="ml-4">
          <h4 class="text-sm font-semibold text-gray-600 mb-2">
            Conf. Championships
            {{
              main_performance.conference_championships?.length > 0
                ? "(" + main_performance.conference_championships?.length + ")"
                : ""
            }}
          </h4>
          <div
            v-for="(season, index) in main_performance.conference_championships"
            :key="index"
            class="flex items-center mb-2"
          >
            <i class="fa fa-trophy text-yellow-500 mr-2"></i>
            <p class="text-xs">
              {{ season.season_name }} ({{ season.championship_team }})
            </p>
          </div>
        </div>
        <div v-if="main_performance.national_overall_champions?.length > 0" class="ml-4">
          <h4 class="text-sm font-semibold text-gray-600 mb-2">
            Nationals Rank #1
            {{
              main_performance.national_overall_champions?.length > 0
                ? "(" + main_performance.national_overall_champions?.length + ")"
                : ""
            }}
          </h4>
          <div
            v-for="(season, index) in main_performance.national_overall_champions"
            :key="index"
            class="flex items-center mb-2"
          >
            <i class="fa fa-trophy text-yellow-500 mr-2"></i>
            <p class="text-xs">{{ season.season_name }} ({{ season.team_name }})</p>
          </div>
        </div>
        <div
          v-if="main_performance.conference_overall_champions?.length > 0"
          class="ml-4"
        >
          <h4 class="text-sm font-semibold text-gray-600 mb-2">
            Conference Rank #1
            {{
              main_performance.conference_overall_champions?.length > 0
                ? "(" + main_performance.conference_overall_champions?.length + ")"
                : ""
            }}
          </h4>
          <div
            v-for="(season, index) in main_performance.conference_overall_champions"
            :key="index"
            class="flex items-center mb-2"
          >
            <i class="fa fa-trophy text-yellow-500 mr-2"></i>
            <p class="text-xs">{{ season.season_name }} ({{ season.team_name }})</p>
          </div>
        </div>
        <div v-else class="text-sm text-red-500 ml-4">
          <p>No championship available.</p>
        </div>
      </div>
      <div class="awards bg-white mb-6 p-2 flex-1 rounded text-nowrap">
        <div class="flex max-w-full">
          <div class="w-full max-w-[400px]">
            <CoachRadarChart
              v-if="main_performance.coach_details"
              :key="main_performance.coach_details.id"
              :coachDetails="main_performance.coach_details"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="flex" v-else>
    <div class="flex items-center justify-center w-full h-32">
      <div class="block text-center">
        <i class="fa fa-spinner fa-spin text-blue-500 text-4xl"></i>
        <p>Loading coach data...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import Modal from "@/Components/Modal.vue";

import axios from "axios";
import {
  roleBadgeClass,
  playerExpStatusClass,
  playerExpStatusText,
} from "@/Utility/Formatter";
import CoachRadarChart from "./CoachRadarChart.vue";
const props = defineProps({
  coach_id: {
    type: Number,
    required: true,
  },
});
const main_performance = ref([]);
const isLoading = ref(false);

// Watch for changes in player_id
// Fetch data on component mount
onMounted(() => {
  fetchPlayerMainPerformance();
});

const fetchPlayerMainPerformance = async () => {
  try {
    isLoading.value = true;
    const response = await axios.post(route("coach.information"), {
      coach_id: props.coach_id,
    });
    main_performance.value = response.data;
    isLoading.value = false;
  } catch (error) {
    isLoading.value = false;
    console.error("Error fetching coach performance:", error);
  }
};
</script>

<style scoped>
.table {
  font-size: 0.75rem; /* Smaller text size */
}

.table th,
.table td {
  padding: 0.5rem; /* Smaller padding */
}
</style>
