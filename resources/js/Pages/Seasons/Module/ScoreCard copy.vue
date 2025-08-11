<template>
  <div v-if="match" class="w-full max-w-md">
    <!-- Existing card content -->
    <div
      :style="{
        background: `
        linear-gradient(45deg, 
            #${match?.home_team?.secondary_color} 0%, 
            #${match?.home_team?.secondary_color} 50%, 
            #${match?.home_team?.primary_color} 50%, 
            #${match?.home_team?.primary_color} 100%
        ),
        linear-gradient(-45deg, 
            #${match?.away_team?.primary_color} 0%, 
            #${match?.away_team?.primary_color} 50%, 
            #${match?.away_team?.secondary_color} 50%, 
            #${match?.away_team?.secondary_color} 100%
        )`,
        backgroundSize: '50% 100%',
        backgroundPosition: 'left, right',
        backgroundRepeat: 'no-repeat',
      }"
      class="shadow-md rounded-md overflow-hidden"
    >
      <div
        class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center"
      >
        <div>
          <h3>
            {{ match.home_team.home_score }}
          </h3>
        </div>
        <div>
          <h3>
            {{ match.away_team.away_score }}
          </h3>
        </div>
      </div>
      <div class="px-1 py-1 flex justify-between items-center">
        <h3>
          <TeamDetails
            :team_id="match.home_team.id"
            :key="match.home_team.id"
            :showButton="0"
            :showInfo="false"
            class="text-white text-md uppercase text-wrap text-left"
            :current_conference_rank="match.home_team.conference_rank"
            :text="`#${match.home_team.overall_rank ?? 'TBD'} ${
              match.home_team.name ?? 'TBD'
            }`"
          />
        </h3>
        <h3>
          <TeamDetails
            :team_id="match.away_team.id"
            :key="match.away_team.id"
            :showButton="0"
            :showInfo="false"
            class="text-white text-md uppercase text-wrap text-right"
            :current_conference_rank="match.away_team.conference_rank"
            :text="`#${match.away_team.overall_rank ?? 'TBD'} ${
              match.away_team.name ?? 'TBD'
            }`"
          />
        </h3>
      </div>
      <div class="px-4 text-nowrap text-xs py-0 flex justify-center">
        <span class="px-2 text-xs text-white py-1 rounded"> </span>
      </div>
      <div class="border-gray-200 flex justify-between mt-4 mb-4 bg-white">
        <div class="px-2 text-nowrap text-xs py-3">
          <span
            :class="
              getConferenceClass(match.home_team.conference, match.away_team.conference)
            "
            class="px-2 shadow py-1 rounded"
          >
            {{ match.home_team.conference }} #{{ match.home_team.conference_rank }} vs
            {{ match.away_team.conference }} #{{ match.away_team.conference_rank }}
          </span>
        </div>
        <div class="px-2 text-nowrap text-red-600 text-xs py-2 flex items-center">
          <button
            class="text-white bg-orange-500 rounded-full px-2 py-1"
            @click.prevent="compareTeams(match.home_team.id, match.away_team.id)"
          >
            Compare
            <i class="fa fa-exchange-alt ml-1"></i>
          </button>
        </div>
      </div>
      <div class="border-gray-200 flex justify-center">
        <a
          href="#"
          class="bg-slate-900 rounded-t text-blue-500 underlined px-2 hover:bg-slate-300 text-sm font-bold"
          @click.prevent="isGameResultModalOpen = match.game_id"
        >
          View Result
        </a>
      </div>
    </div>
  </div>
  <!-- Game Result Modal -->
  <Modal
    :show="isGameResultModalOpen"
    :maxWidth="'fullscreen'"
    title="Game Results"
    @close="isGameResultModalOpen = false"
  >
    <div class="mt-4">
      <GameResults :key="isGameResultModalOpen" :game_id="isGameResultModalOpen" />
    </div>
  </Modal>
  <Modal
    :show="isTeamComparisonModalOpen"
    :maxWidth="'6xl'"
    title="Team Comparison"
    @close="isTeamComparisonModalOpen = false"
  >
    <div class="mt-4">
      <TeamComparison
        :home_id="comparison.home_id"
        :away_id="comparison.away_id"
        :season_id="comparison.season_id"
      />
    </div>
  </Modal>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import axios from "axios";
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";

import TeamComparison from "@/Pages/Teams/Module/TeamComparison.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
const props = defineProps({
  match: {
    type: Object,
    required: true,
  },
  isSimulating: {
    type: Boolean,
    default: false,
  },
});
const isGameResultModalOpen = ref(false);
const isTeamComparisonModalOpen = ref(false);
const comparison = useForm({
  season_id: 0,
  home_id: 0,
  away_id: 0,
});
const compareTeams = (home_id, away_id) => {
  comparison.season_id = props.match?.season_id;
  comparison.home_id = home_id;
  comparison.away_id = away_id;
  isTeamComparisonModalOpen.value = true;
};
const getConferenceClass = (home_conference, away_conference) => {
  const conferenceClasses = {
    NCR: "bg-blue-100 text-blue-500",
    Luzon: "bg-green-100 text-green-500",
    Visayas: "bg-yellow-100 text-yellow-500",
    Mindanao: "bg-red-100 text-red-500",
  };

  if (home_conference !== away_conference) {
    return "bg-orange-100 text-orange-500";
  }

  return conferenceClasses[home_conference] || "bg-gray-100 text-gray-500";
};
</script>
