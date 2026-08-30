<template>
  <div
    class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6 border-b-2 border-dashed"
    v-if="season_info.seasons && season_info.seasons[0].status >= 2 && !loading"
  >
    <div class="md:col-span-4 overflow-y-auto">
      <div class="flex justify-between" v-if="!isHide">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Playoffs Series</h2>
        <button
          :disabled="season_info.seasons && season_info.seasons[0].status > 11"
          :class="
            season_info.seasons && season_info.seasons[0].status > 11
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
        <div v-for="(r,rr) in roundOrder" :key="rr">
          <h3 :class="season_playoffs.playoffs[r].series?.length >= 4 ? '' : 'text-center'" class="text-3xl mb-3 font-semibold mt-4 text-orange-500" v-if="season_playoffs.playoffs[r].series?.length > 0">
            {{ roundNameFormatter(r) }}
          </h3>
          <div
            :class="season_playoffs.playoffs[r].series?.length >= 4 ? 'grid gap-4 grid-cols-4' : 'flex justify-center space-x-2' "
          > 
            <div
              v-if=" season_playoffs.playoffs[r].series?.length > 0"
              v-for="(ser, serr) in season_playoffs.playoffs[r].series"
              :key="serr"
              class="block"
            >
                <div v-if="season_playoffs.playoffs[r].series?.length === 1" class="flex justify-center">
                  <SeriesCard
                    :key="ser.id"
                    :series="ser"
                    />
                </div>
                <div v-if="season_playoffs.playoffs[r].series?.length === 2" class="flex justify-center gap-4">
                  <SeriesCard
                    :key="ser.id"
                    :series="ser"
                    />
                </div>
                <div v-if="season_playoffs.playoffs[r].series?.length >= 4">
                  <SeriesCard
                    :key="ser.id"
                    :series="ser"
                    />
                </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Regular season not finished message -->
  <div
    class="flex justify-center min-h-screen items-center border-b-2 border-dashed p-4 bg-dark"
    v-if="season_info.seasons && season_info.seasons[0].status < 3 && !loading"
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
          :href="route('seasons.details', { season_id: props.season_id, playoff_type: 2 })"
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

    <!-- Game News Modal -->
    <div v-if="isGameNewsModalOpen && game_news" class="fixed bg-transparent bottom-0 right-0 bg-white p-2">
        <div class="flex justify-center" @click.prevent="isGameNewsModalOpen = false">
            <!-- <h3 class="text-lg font-semibold mb-2 text-white">Game News</h3> -->
            <GameNews :key="game_news.id" :data="game_news" :showNews="true" />
        </div>
    </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import axios from "axios";
import {
    roundNameFormatter,
    roundGridFormatter,
    roundStatusFormatter,
} from "@/Utility/Formatter.js";

import GameNews from "@/Pages/Seasons/Module/GameNews.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";

import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";
import SeriesCard from "@/Pages/Seasons/Module/SeriesCard.vue";

const isAddModalOpen = ref(false);
const isTeamModalOpen = ref(false);
const isTeamComparisonModalOpen = ref(false);
const isGameNewsModalOpen = ref(false);
const showSeriesResult = ref(false);
const loading = ref(false);
const change_key = ref(localStorage.getItem("season-key"));
const isHide = ref(false);
const activeIndex = ref(0);
const season_info = ref(false);
const season_playoffs = ref(false);
const is_play_ins = ref(false);
const active_series_id = ref(false);
const game_news = ref(false);

const form = useForm({
    seasons_id: 0,
});
const comparison = useForm({
    season_id: 0,
    home_id: 0,
    away_id: 0,
});
const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
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

const createPlayOffSchedule = async (round) => {
    try {
        let prev_round = round;
        let start_playoffs = season_info.value.seasons[0].start_playoffs;
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
        });
        isHide.value = true;
        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);
        isHide.value = false;
        isAddModalOpen.value = false;
        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });
    } catch (error) {
        console.error("Error creating playoff schedule:", error);
        Swal.close();
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to create playoff schedule.",
        });
        throw error; // Rethrow to allow caller to handle
    }
};

const createPlayOffScheduleAuto = async (round) => {
    try {
        let prev_round = round;
        let start_playoffs = season_info.value.seasons[0].start_playoffs;
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
        });

        isHide.value = true;
        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);
        isHide.value = false;
        isAddModalOpen.value = false;

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });

        // ✅ Return message so it can be checked by caller
        return response.data.message;

    } catch (error) {
        console.error("Error creating playoff schedule:", error);
        Swal.close();
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to create playoff schedule.",
        });
        throw error; // Still rethrow to let the simulation function decide
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
        let status = season_info.value.seasons[0].status;
        let start_playoffs = season_info.value.seasons[0].start_playoffs;
        const response = await axios.post(route("seasons.playoffs.series"), {
            season_id: form.seasons_id,
            type: type,
            status: status,
            start: start_playoffs,
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
        } else {
            loading.value = true;
            season_playoffs.value = response.data;
            loading.value = false;
        }
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

const simulateGame = async (id, game_id, type, index, round) => {
    try {
        isHide.value = true;
        isGameNewsModalOpen.value = true;
        activeIndex.value = index;

        Swal.fire({
            title: 'Simulating...',
            text: 'Please wait while the game is being simulated.',
            icon: 'info',
            toast: true,
            position: 'top',
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            timerProgressBar: true,
            didOpen: (toast) => {
                Swal.showLoading();
            }
        });


        const response = await axios.post(route("game.simulate.playoff.series"), {
            schedule_id: id,
        });

        game_news.value = response.data.news;
        // season_playoffs.value.playoffs[round][index] = response.data.schedule;

        // active_series_id.value = season_playoffs.value.playoffs[round][index]?.series_id ?? 0;

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
            timer: 2000, // Auto-close after 2 seconds (2000ms)
            showConfirmButton: false,
            timerProgressBar: true
        });

        isGameNewsModalOpen.value = false;
        isHide.value = false;
    } catch (error) {
        console.error("Error simulating the game:", error);

        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to simulate game.",
        });

        throw error; // Rethrow to allow caller to handle
    } finally{
        
        isHide.value = false;
        isGameNewsModalOpen.value = false;

        Swal.close();
    }
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

        let currentRoundIndex = 0;
        let initialRound = 'start';

        // Initialize playoffs if not started
        if (season_info.value.seasons[0].status == 2) {
            await createPlayOffScheduleAuto(initialRound);
        }
        console.log(roundOrder);
        while (currentRoundIndex < roundOrder.length) {
            let roundName = roundOrder[currentRoundIndex];
            let start_playoffs = season_info.value.seasons[0].start_playoffs;
            let prevRound = roundStatusFormatter(roundOrder[currentRoundIndex], start_playoffs, is_play_ins.value);

            const isPrevRoundCompleted = season_playoffs.value.playoffs[prevRound]?.completed;
            const isCompleted = season_playoffs.value.playoffs[roundName]?.completed;
            if(isPrevRoundCompleted){
                console.log(`${prevRound}  Prev Round Completed:${isCompleted} skipping...`);
                if(isCompleted){
                    console.log(`${prevRound}  Prev Round Completed:${isCompleted} skipping...`);
                    currentRoundIndex++; // Move to the next round
                    continue;
                }
            }
            console.log(`Current ${roundName}`);
            // Refresh latest data
            await fetchSeasonInfo(form.seasons_id);
            await fetchSeasonPlayoffs(is_play_ins.value);

            // Check if round has matches
            if (
                !season_playoffs.value.playoffs[roundName] ||
                season_playoffs.value.playoffs[roundName]?.series?.length === 0
            ) {
                try {
                    const scheduleResponse = await createPlayOffScheduleAuto(roundName);
                    
                    // If schedule already created, log and continue
                    if (typeof scheduleResponse === 'string' &&
                        scheduleResponse.toLowerCase().includes("already created")) {
                        if(!isCompleted){
                            console.log(isCompleted);
                            await fetchSeasonPlayoffs(is_play_ins.value);
                        }
                        console.log(`Schedule for ${roundName} already exists. Proceeding... status:${isCompleted}`);
                    } else {
                        await fetchSeasonPlayoffs(is_play_ins.value);
                    }
                } catch (scheduleError) {
                    console.warn(`Failed to create schedule for ${roundName}:`, scheduleError);
                }
            }

            // Get updated matches
            const matches = season_playoffs.value.pending || [];
            if (matches.length === 0) {
                const nextRoundResponse = await createPlayOffScheduleAuto(roundName);
                if (typeof nextRoundResponse === 'string' &&
                    nextRoundResponse.toLowerCase().includes("already created")) {
                    console.log(`Next round after ${roundName} already scheduled.`);
                }else{
                  currentRoundIndex++;
                  continue;
                }
            }

            // Simulate each game
            for (let index = 0; index < matches.length; index++) {
                const match = matches[index];
                try {
                    await simulateGame(match.id, match.game_id, 2, index, roundName);
                    await fetchSeasonPlayoffs(is_play_ins.value); // Refresh after each game
                } catch (error) {
                    console.error(`Error simulating game ${match.id} in ${roundName}:`, error);

                    // Reload pending games so we can retry safely
                    await fetchSeasonPlayoffs(is_play_ins.value);

                    // Show error but do NOT exit entire playoffs loop
                    Swal.fire({
                        icon: "error",
                        title: "Game Simulation Error",
                        text: error.response?.data?.message || `Failed to simulate game ${match.id}. Retrying...`,
                    });

                    // Important: break the inner loop so we stay on this round,
                    // but do not increment currentRoundIndex (retry same round next cycle)
                    index = matches.length; // break out of inner loop
                    currentRoundIndex--;    // force stay in same round
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

watch(
    () => props.season_id,
    async (n, o) => {
        if (n !== o) {
            await fetchSeasonInfo(n); // Use new season_id
        }
    }
);

onMounted(() => {
    fetchSeasonInfo(props.season_id);
});
</script>
