<template>
  <div v-if="match" class="w-full max-w-md mx-auto">
    <!-- GAME CARD -->
    <div
      class="group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
    >
      <!-- Team Color Header -->
      <div
        class="relative overflow-hidden"
        :style="gameHeaderStyle"
      >
        <!-- Dark overlay -->
        <div class="absolute inset-0 bg-black/20"></div>

        <!-- Score Area -->
        <div
          class="relative grid grid-cols-[1fr_auto_1fr] items-center px-4 py-5 sm:px-5"
        >
          <!-- Home Score -->
          <div class="text-center">
            <div
              class="mb-1 text-[10px] font-bold uppercase tracking-[0.2em] text-white/70"
            >
              Home
            </div>

            <div
              class="text-5xl font-black leading-none tracking-tight text-white drop-shadow-lg sm:text-6xl"
            >
              {{ match.home_team?.home_score ?? 0 }}
            </div>
          </div>

          <!-- VS -->
          <div class="px-3 text-center">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-black/30 text-[10px] font-black text-white/80 backdrop-blur-sm"
            >
              VS
            </div>
          </div>

          <!-- Away Score -->
          <div class="text-center">
            <div
              class="mb-1 text-[10px] font-bold uppercase tracking-[0.2em] text-white/70"
            >
              Away
            </div>

            <div
              class="text-5xl font-black leading-none tracking-tight text-white drop-shadow-lg sm:text-6xl"
            >
              {{ match.away_team?.away_score ?? 0 }}
            </div>
          </div>
        </div>

        <!-- Team Names -->
        <div
          class="relative grid grid-cols-2 gap-2 border-t border-white/10 bg-black/20 px-3 py-3 backdrop-blur-sm"
        >
          <!-- Home Team -->
          <div class="min-w-0 text-left">
            <TeamDetails
              :team_id="match.home_team?.id"
              :key="`home-${match.home_team?.id}`"
              :showButton="0"
              :showInfo="false"
              class="!text-white"
              :current_conference_rank="match.home_team?.conference_rank"
              :text="`#${match.home_team?.overall_rank ?? 'TBD'} ${
                match.home_team?.name ?? 'TBD'
              }`"
            />
          </div>

          <!-- Away Team -->
          <div class="min-w-0 text-right">
            <TeamDetails
              :team_id="match.away_team?.id"
              :key="`away-${match.away_team?.id}`"
              :showButton="0"
              :showInfo="false"
              class="!text-white"
              :current_conference_rank="match.away_team?.conference_rank"
              :text="`#${match.away_team?.overall_rank ?? 'TBD'} ${
                match.away_team?.name ?? 'TBD'
              }`"
            />
          </div>
        </div>
      </div>

      <!-- GAME META -->
      <div class="bg-white px-3 py-3 dark:bg-slate-950">
        <div class="flex items-center justify-between gap-2">
          <!-- Conference -->
          <span
            :class="getConferenceClass(
              match.home_team?.conference,
              match.away_team?.conference
            )"
            class="inline-flex min-w-0 items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide shadow-sm"
          >
            <span class="truncate">
              {{ match.home_team?.conference ?? "N/A" }}
            </span>

            <span class="opacity-50">#</span>

            <span>
              {{ match.home_team?.conference_rank ?? "TBD" }}
            </span>

            <span class="mx-0.5 opacity-40">vs</span>

            <span class="truncate">
              {{ match.away_team?.conference ?? "N/A" }}
            </span>

            <span class="opacity-50">#</span>

            <span>
              {{ match.away_team?.conference_rank ?? "TBD" }}
            </span>
          </span>

          <!-- Actions -->
          <div class="flex shrink-0 items-center gap-1.5">
            <!-- Overtime -->
            <span
              v-if="Number(match.is_overtime) > 0"
              title="Overtime Game"
              class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-600"
            >
              <i class="fa fa-stopwatch text-xs"></i>
            </span>

            <!-- Compare -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-full bg-orange-500 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-orange-600 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-white"
              @click="compareTeams(match.home_team?.id, match.away_team?.id)"
            >
              <span>Compare</span>
              <i class="fa fa-exchange-alt text-[10px]"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- VIEW RESULT -->
      <div
        class="border-t border-slate-200 bg-slate-950 px-3 py-2.5 dark:border-white/5"
      >
        <button
          type="button"
          class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-blue-400 transition hover:bg-white/5 hover:text-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
          :title="`Game ID: ${match.game_id}`"
          @click="openGameResult"
        >
          <i class="fa fa-chart-bar text-[10px]"></i>

          <span>View Result</span>

          <i
            class="fa fa-arrow-right text-[9px] transition-transform duration-200 group-hover:translate-x-1"
          ></i>
        </button>
      </div>
    </div>
  </div>

  <!-- GAME RESULT MODAL -->
  <Modal
    :show="isGameResultModalOpen"
    maxWidth="fullscreen"
    title="Game Results"
    @close="closeGameResult"
  >
    <div class="mt-2 sm:mt-4">
      <GameResults
        v-if="selectedGame"
        :key="`${selectedGame.game_id}-${selectedGame.season_id}`"
        :game_id="selectedGame.game_id"
        :season_id="selectedGame.season_id"
      />
    </div>
  </Modal>

  <!-- TEAM COMPARISON MODAL -->
  <Modal
    :show="isTeamComparisonModalOpen"
    maxWidth="6xl"
    title="Team Comparison"
    @close="isTeamComparisonModalOpen = false"
  >
    <div class="mt-2 sm:mt-4">
      <TeamComparison
        :home_id="comparison.home_id"
        :away_id="comparison.away_id"
        :season_id="comparison.season_id"
      />
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

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

const selectedGame = ref(null);

const comparison = useForm({
  season_id: 0,
  home_id: 0,
  away_id: 0,
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const normalizeColor = (color, fallback = "334155") => {
  if (!color) {
    return fallback;
  }

  return String(color).replace("#", "");
};

const homePrimary = computed(() =>
  normalizeColor(props.match?.home_team?.primary_color, "1e293b")
);

const homeSecondary = computed(() =>
  normalizeColor(props.match?.home_team?.secondary_color, "475569")
);

const awayPrimary = computed(() =>
  normalizeColor(props.match?.away_team?.primary_color, "1e293b")
);

const awaySecondary = computed(() =>
  normalizeColor(props.match?.away_team?.secondary_color, "475569")
);

/*
|--------------------------------------------------------------------------
| Winner / Loser
|--------------------------------------------------------------------------
*/

const homeWon = computed(() => {
  if (props.match?.winner == null) {
    return false;
  }

  return Number(props.match.winner) === Number(props.match?.home_team?.id);
});

const awayWon = computed(() => {
  if (props.match?.winner == null) {
    return false;
  }

  return Number(props.match.winner) === Number(props.match?.away_team?.id);
});

const isCompleted = computed(() => props.match?.winner != null);

/*
|--------------------------------------------------------------------------
| Header Background
|--------------------------------------------------------------------------
*/

const gameHeaderStyle = computed(() => {
  const homeGradient = homeWon.value || !isCompleted.value
    ? `#${homeSecondary.value} 0%, #${homeSecondary.value} 48%, #${homePrimary.value} 52%, #${homePrimary.value} 100%`
    : "#52525b 0%, #3f3f46 48%, #27272a 52%, #18181b 100%";

  const awayGradient = awayWon.value || !isCompleted.value
    ? `#${awayPrimary.value} 0%, #${awayPrimary.value} 48%, #${awaySecondary.value} 52%, #${awaySecondary.value} 100%`
    : "#52525b 0%, #3f3f46 48%, #27272a 52%, #18181b 100%";

  return {
    background: `
      linear-gradient(
        45deg,
        ${homeGradient}
      ),
      linear-gradient(
        -45deg,
        ${awayGradient}
      )
    `,
    backgroundSize: "50% 100%",
    backgroundPosition: "left, right",
    backgroundRepeat: "no-repeat",
  };
});

/*
|--------------------------------------------------------------------------
| Game Result
|--------------------------------------------------------------------------
*/

const openGameResult = () => {
  selectedGame.value = {
    game_id: props.match?.game_id,
    season_id: props.match?.season_id,
  };

  isGameResultModalOpen.value = true;
};

const closeGameResult = () => {
  isGameResultModalOpen.value = false;
  selectedGame.value = null;
};

/*
|--------------------------------------------------------------------------
| Team Comparison
|--------------------------------------------------------------------------
*/

const compareTeams = (homeId, awayId) => {
  comparison.season_id = props.match?.season_id ?? 0;
  comparison.home_id = homeId ?? 0;
  comparison.away_id = awayId ?? 0;

  isTeamComparisonModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Conference Styling
|--------------------------------------------------------------------------
*/

const getConferenceClass = (homeConference, awayConference) => {
  const conferenceClasses = {
    NCR: "bg-blue-100 text-blue-600",
    Luzon: "bg-green-100 text-green-600",
    Visayas: "bg-yellow-100 text-yellow-700",
    Mindanao: "bg-red-100 text-red-600",
  };

  if (
    homeConference &&
    awayConference &&
    homeConference !== awayConference
  ) {
    return "bg-orange-100 text-orange-600";
  }

  return (
    conferenceClasses[homeConference] ||
    "bg-gray-100 text-gray-600"
  );
};
</script>