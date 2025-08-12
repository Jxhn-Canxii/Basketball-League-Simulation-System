<template>
  <div
    class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6 border-b-2 border-dashed"
    v-if="season_info.seasons && season_info.seasons[0].status > 1 && !loading"
  >
    <div class="md:col-span-4 overflow-y-auto">
      <div class="flex justify-between" v-if="!isHide">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Playoffs Series</h2>
        <button
          :disabled="season_info.seasons && season_info.seasons[0].status > 10"
          :class="
            season_info.seasons && season_info.seasons[0].status > 10
              ? 'bg-gray-500 cursor-not-allowed'
              : 'bg-red-500 hover:bg-red-600 hover:text-red-900'
          "
          @click="simulateFullPlayoffs"
          type="button"
          class="text-white bg-gradient-to-br p-3 shadow rounded-full font-bold text-md text-nowrap"
        >
          Simulate Full Playoffs
        </button>
      </div>
      <!-- Show SeriesResult inline when simulating -->
      <div v-if="showSeriesResult && active_series_id !== 0" class="pt-4">
        <SeriesResult
          :key="active_series_id"
          :series_id="active_series_id"
          :season_id="props.season_id"
          @finish="onSeriesResultFinish"
        />
      </div>

      <!-- Show playoff series list when NOT simulating -->
      <div v-if="season_playoffs.playoffs">
        <div
          v-for="(seriesList, roundName) in season_playoffs.playoffs"
          :key="roundName"
          class="block"
        >
          <div v-if="seriesList.length > 0">
            <h3 class="text-lg font-semibold mt-4 text-orange-500">
              {{ roundNameFormatter(roundName) }}
            </h3>

            <div
              class="grid gap-4"
              :class="{
                'grid-cols-4': seriesList.length >= 4,
                'grid-cols-2 justify-center': seriesList.length === 2,
                'grid-cols-1 justify-center max-w-md mx-auto': seriesList.length === 1,
              }"
            >
              <!-- Single series centered -->
              <div v-if="seriesList.length === 1" class="flex justify-center">
                <SeriesCard
                  :series="seriesList[0]"
                  :roundName="roundName"
                  :key="seriesList[0].game_id"
                  :class="
                    active_index === 0
                      ? 'transform scale-105 shadow-lg border-4 border-blue-500 transition-all duration-500'
                      : ''
                  "
                />
              </div>

              <!-- Two series side by side -->
              <div
                v-if="seriesList.length === 2"
                class="col-span-2 flex justify-center gap-4"
              >
                <SeriesCard
                  v-for="(series, index) in seriesList"
                  :key="series.game_id"
                  :series="series"
                  :series_id="series.series_id"
                  :roundName="roundName"
                  :index="index"
                  class="w-full max-w-md"
                  :class="
                    index === active_index
                      ? 'transform scale-105 shadow-lg border-4 border-blue-500 transition-all duration-500'
                      : ''
                  "
                />
              </div>

              <!-- Four or more series grid -->
              <SeriesCard
                v-if="seriesList.length >= 4"
                v-for="(series, index) in seriesList"
                :key="series.id"
                :series="series"
                :roundName="roundName"
                :index="index"
                class="col-span-1"
                :class="
                  index === active_index
                    ? 'transform scale-105 shadow-lg border-4 border-blue-500 transition-all duration-500'
                    : ''
                "
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Regular season not finished message -->
  <div
    class="flex justify-center min-h-screen items-center border-b-2 border-dashed p-4 bg-white"
    v-if="season_info.seasons && season_info.seasons[0].status == 1 && !loading"
  >
    <div class="text-center bg-white p-8 rounded-lg shadow-lg border-2 border-red-500">
      <p class="text-red-500 font-bold text-3xl md:text-4xl leading-relaxed mb-4">
        Not playoffs season yet! Finish the Regular season first.
      </p>
      <p class="text-gray-700 text-lg md:text-xl">
        Please make sure all regular season games are completed before proceeding to
        playoffs.
      </p>
      <div class="mt-6">
        <a
          :href="route('seasons.details', { season_id: props.season_id })"
          class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-red-300"
        >
          Go to Regular Season
        </a>
      </div>
    </div>
  </div>

  <!-- Loading indicator -->
  <div class="flex justify-center items-center p-4" v-if="loading">
    <p class="text-red-500 font-bold text-2xl">Loading...</p>
  </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch, onUnmounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import SeriesCard from "@/Pages/Seasons/Module/SeriesCard.vue";
import SeriesResult from "@/Pages/Seasons/Module/SeriesResult.vue";
import { roundNameFormatter, roundStatusFormatter } from "@/Utility/Formatter.js";

const isHide = ref(false);
const showGameResults = ref(true);
const showSeriesResult = ref(true);
const seriesResultFinished = ref(false);
const loading = ref(false);
const active_index = ref(-1);
const active_series_id = ref(0);
const season_info = ref(false);
const season_playoffs = ref(false);
const is_play_ins = ref(false);
const isSimulating = ref(false);
const flipTimer = ref(null);
const form = useForm({
  seasons_id: 0,
});

const props = defineProps({
  season_id: {
    type: [Number, String],
    required: true,
  },
});

const roundOrder = [
  "play_ins_elims_round_1",
  "play_ins_elims_round_2",
  "play_ins_finals",
  "round_of_16",
  "quarter_finals",
  "semi_finals",
  "interconference_semi_finals",
  "finals",
];

// Called when SeriesResult emits 'finish' event
const onSeriesResultFinish = (id) => {
  if (id === active_series_id.value) {
    seriesResultFinished.value = true;
  }
};

// Simulate a single playoff game
const simulateGame = async (id, game_id, type, index, round) => {
  try {
    isHide.value = true;
    active_index.value = index;

    Swal.fire({
      title: "Simulating...",
      text: "Please wait while the game is being simulated.",
      icon: "info",
      toast: true,
      position: "top",
      showConfirmButton: false,
      allowOutsideClick: true,
      allowEscapeKey: true,
      timerProgressBar: true,
      didOpen: (toast) => {
        Swal.showLoading();
      },
    });

    const response = await axios.post(route("game.simulate.playoff.series"), {
      schedule_id: id,
    });

    season_playoffs.value.playoffs[round][index] = response.data.series;

    active_series_id.value = season_playoffs.value.playoffs[round][index]?.series_id;
    Swal.close();


    if (flipTimer.value) clearInterval(flipTimer.value);

    let flipCount = 0;

    flipTimer.value = setInterval(() => {
      showSeriesResult.value = !showSeriesResult.value;
      flipCount++;

      if (flipCount >= 2) {  // Stop after 2 flips
        clearInterval(flipTimer.value);
        flipTimer.value = null;
      }
    }, 10000);

    // Wait 8 seconds for 2 flips (optional if you need to wait here)
    await new Promise((resolve) => setTimeout(resolve, 8000));


    Swal.fire({
      icon: "success",
      title: "Success!",
      text: response.data.message,
      timer: 2000,
      showConfirmButton: false,
      timerProgressBar: true,
    });

    isHide.value = false;

    // Return series_id so caller knows what finished
    return active_series_id.value;
  } catch (error) {
    console.error("Error simulating the game:", error);
    Swal.close();
    isHide.value = false;
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: error.response?.data?.message || "Failed to simulate game.",
    });
    throw error;
  }
};

const simulateFullPlayoffs = async () => {
    try {
        isHide.value = true;
        loading.value = true;

        Swal.fire({
            title: "Simulating Playoffs...",
            text: "Please wait while the entire playoff is being simulated.",
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const roundOrder = [
            'play_ins_elims_round_1',
            'play_ins_elims_round_2',
            'play_ins_finals',
            'round_of_16',
            'quarter_finals',
            'semi_finals',
            'interconference_semi_finals',
            'finals'
        ];

        let currentRoundIndex = 0;
        let initialRound = 'start';

        // Initialize playoffs if not started
        if (season_info.value.seasons[0].status == 2) {
            await createPlayOffScheduleAuto(initialRound);
        }

        while (currentRoundIndex < roundOrder.length) {
            const roundName = roundOrder[currentRoundIndex];

            // Refresh latest data
            await fetchSeasonInfo(form.seasons_id);
            await fetchSeasonPlayoffs(is_play_ins.value);

            // Check if round has matches
            if (
                !season_playoffs.value.playoffs[roundName] ||
                season_playoffs.value.playoffs[roundName].length === 0
            ) {
                try {
                    const scheduleResponse = await createPlayOffScheduleAuto(roundName);

                    // If schedule already created, log and continue
                    if (typeof scheduleResponse === 'string' &&
                        scheduleResponse.toLowerCase().includes("already created")) {
                        console.log(`Schedule for ${roundName} already exists. Proceeding.`);
                    } else {
                        await fetchSeasonPlayoffs(is_play_ins.value);
                    }
                } catch (scheduleError) {
                    console.warn(`Failed to create schedule for ${roundName}:`, scheduleError);
                }
            }

            // Get updated matches
            const matches = season_playoffs.value.games[roundName] || [];
            if (matches.length === 0) {
                currentRoundIndex++;
                continue;
            }

            // Simulate each game
            for (let index = 0; index < matches.length; index++) {
                const match = matches[index];
                if (match.winner == 0) {
                    await simulateGame(match.id, match.game_id, 2, index, roundName);
                    await fetchSeasonPlayoffs(is_play_ins.value); // Refresh after each game
                }
            }

            // Try to advance to next round if not finals
            if (roundName !== 'finals') {
                try {
                    const nextRoundResponse = await createPlayOffScheduleAuto(roundName);
                    if (typeof nextRoundResponse === 'string' &&
                        nextRoundResponse.toLowerCase().includes("already created")) {
                        console.log(`Next round after ${roundName} already scheduled.`);
                    }
                } catch (advanceError) {
                    console.warn(`Could not advance from ${roundName}:`, advanceError);
                }
            }

            currentRoundIndex++;
        }

        // Final refresh
        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Playoffs Completed!",
            text: "The entire playoff simulation has finished successfully.",
        });

    } catch (error) {
        console.error("Error simulating full playoffs:", error);
        Swal.close();
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to simulate playoffs.",
        });
    } finally {
        isHide.value = false;
        loading.value = false;
    }
};

const createPlayOffScheduleAuto = async (round) => {
  try {
    let prev_round = round;
    let start_playoffs = season_info.value.seasons[0].start_playoffs;
    let playoff_type = season_info.value.seasons[0].playoff_type;
    round = roundStatusFormatter(round, start_playoffs, is_play_ins.value);

    Swal.fire({
      title: "Simulating...",
      text: "Please wait while creating the schedule for " + roundNameFormatter(round),
      icon: "info",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const response = await axios.post(route("create.schedule.playoff"), {
      season_id: form.seasons_id,
      round: round,
      prev_round: prev_round,
      start: start_playoffs,
      playoff_type: playoff_type,
    });

    Swal.close();
    Swal.fire({
      icon: "success",
      title: "Success!",
      text: response.data.message,
    });

    return { success: true, message: response.data.message };
  } catch (error) {
    Swal.close();

    const errorMsg = error.response?.data?.message || "Failed to create playoff schedule.";

    if (errorMsg.toLowerCase().includes("already created")) {
      console.log("Schedule already created for round:", round);
      return { success: true, message: errorMsg };
    }

    if (errorMsg.toLowerCase().includes("current round series schedule is ongoing")) {
      console.log("Current round series ongoing. Can't create next round schedule yet:", round);
      return { success: false, message: errorMsg };
    }

    Swal.fire({
      icon: "error",
      title: "Error!",
      text: errorMsg,
    });

    throw error;
  }
};


const fetchSeasonInfo = async (id) => {
  try {
    form.seasons_id = id;
    const response = await axios.post(route("seasons.info"), {
      season_id: form.seasons_id,
    });

    season_info.value = response.data;
    is_play_ins.value = response.data.is_play_ins ? 1 : 2;
    await fetchSeasonPlayoffs(is_play_ins.value);
  } catch (error) {
    console.error("Error fetching season information:", error);
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: error.response?.data?.message || "Failed to fetch season information.",
    });
  }
};

const fetchSeasonPlayoffs = async (type) => {
  try {
    loading.value = true;

    let status = season_info.value.seasons[0].status;
    let start_playoffs = season_info.value.seasons[0].start_playoffs;
    let playoff_type = season_info.value.seasons[0].playoff_type;
    const response = await axios.post(route("seasons.playoffs.series"), {
      season_id: form.seasons_id,
      type: type,
      status: status,
      start: start_playoffs,
      playoff_type: playoff_type,
    });

    if (type === 2) {
      if (
        typeof season_playoffs.value.playoffs !== "object" ||
        season_playoffs.value.playoffs === null
      ) {
        season_playoffs.value.playoffs = {};
      }
      season_playoffs.value.playoffs = {
        ...season_playoffs.value.playoffs,
        ...response.data.playoffs,
      };

      if (
        typeof season_playoffs.value.games !== "object" ||
        season_playoffs.value.games === null
      ) {
        season_playoffs.value.games = {};
      }
      season_playoffs.value.games = {
        ...season_playoffs.value.games,
        ...response.data.games,
      };
    } else {
      season_playoffs.value = response.data;
    }
    loading.value = false;
  } catch (error) {
    loading.value = false;
    console.error("Error fetching season playoffs:", error);
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: error.response?.data?.message || "Failed to fetch playoff data.",
    });
  }
};

watch(
  () => props.season_id,
  async (newVal, oldVal) => {
    if (newVal !== oldVal) {
      await fetchSeasonInfo(newVal);
    }
  }
);

onMounted(async () => {
  await fetchSeasonInfo(props.season_id);
  await fetchSeasonPlayoffs(2);
});

onUnmounted(() => {
      if (flipTimer.value) {
          clearInterval(flipTimer.value);
          flipTimer.value = null;
      }
  });
</script>
