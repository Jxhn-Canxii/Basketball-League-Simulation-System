<template>
    <div
        class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6 border-b-2 border-dashed"
        v-if="season_info.seasons && season_info.seasons[0].status > 1 && !loading"
    >
        <div class="md:col-span-4 overflow-y-auto">
            <div class="flex justify-between">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Playoffs Series</h2>
                <button
                    :disabled="season_info.seasons && season_info.seasons[0].status > 10"
                    :class="season_info.seasons && season_info.seasons[0].status > 10 ? 'bg-gray-500 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600 hover:text-red-900'"
                    @click="simulateFullPlayoffs"
                    type="button"
                    class="text-white bg-gradient-to-br p-3 shadow rounded-full font-bold text-md text-nowrap"
                >
                    Simulate Full Playoffs
                </button>
            </div>
            <div
                class="flex justify-center text-red-500 pt-4"
                v-if="season_info.seasons && season_info.seasons[0].status == 3 && !loading"
            >
                <small>Please click to start play-offs simulation!</small>
            </div>
            
            <!-- Display playoff series -->
            <div class="grid grid-cols-1 gap-6" v-if="season_playoffs.playoffs">
                <div
                    v-for="(seriesList, roundName) in season_playoffs.playoffs"
                    :key="roundName"
                    class="block"
                >
                    <div v-if="seriesList.length > 0">
                        <h3 class="text-lg font-semibold mt-4 text-orange-500">
                            {{ roundNameFormatter(roundName) }}
                        </h3>
                        
                        <div class="grid gap-4" :class="{
                            'grid-cols-4': seriesList.length >= 4,
                            'grid-cols-2 justify-center': seriesList.length === 2,
                            'grid-cols-1 justify-center max-w-md mx-auto': seriesList.length === 1
                        }">
                            <!-- Single series centered -->
                            <div 
                                v-if="seriesList.length === 1"
                                class="flex justify-center"
                            >
                                <SeriesCard 
                                    :series="seriesList[0]"
                                    :roundName="roundName"
                                />
                            </div>
                            
                            <!-- Two series side by side -->
                            <div 
                                v-if="seriesList.length === 2"
                                class="col-span-2 flex justify-center gap-4"
                            >
                                <SeriesCard 
                                    v-for="(series, index) in seriesList" 
                                    :key="series.id"
                                    :series="series"
                                    :series_id="series.series_id"
                                    :roundName="roundName"
                                    :index="index"
                                    class="w-full max-w-md"
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
                            />
                        </div>
                        
                        <div
                            class="flex justify-end"
                            v-if="!isHide && seriesList.length > 0"
                        >
                            <button
                                v-if="
                                    season_info.seasons[0].status ==
                                        roundGridFormatter(
                                            roundName,
                                            season_info.seasons[0].start_playoffs
                                        ) &&
                                    seriesList.length > 0 &&
                                    roundName != 'finals'
                                "
                                @click="createPlayOffSchedule(roundName)"
                                class="text-indigo-600 font-bold text-md flex bg-orange-200 shadow p-1 rounded-full hover:text-indigo-900 mt-4"
                            >
                                End
                                {{ roundNameFormatter(roundName) }}
                            </button>
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
                Please make sure all regular season games are completed before
                proceeding to playoffs.
            </p>
            <div class="mt-6">
                <a :href="route('seasons.details', { season_id: props.season_id })"
                    class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-red-300">
                        Go to Regular Season
                </a>
            </div>
        </div>
    </div>
    
    <!-- Loading indicator -->
    <div class="flex justify-center items-center p-4" v-if="loading">
        <p class="text-red-500 font-bold text-2xl">Loading...</p>
    </div>
    
    <!-- Modals -->
    <Modal :show="isTeamComparisonModalOpen" :maxWidth="'6xl'" title="Team Comparison" @close="isTeamComparisonModalOpen = false">
        <div class="mt-4">
            <TeamComparison
                :home_id="comparison.home_id"
                :away_id="comparison.away_id"
                :season_id="comparison.season_id"
            />
        </div>
    </Modal>
    
    <Modal :show="isGameResultModalOpen" :maxWidth="'fullscreen'" title="Game Results" @close="isGameResultModalOpen = false">
        <div class="mt-4">
            <GameResults :key="isGameResultModalOpen" :game_id="isGameResultModalOpen" />
        </div>
    </Modal>
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

import TeamComparison from "@/Pages/Teams/Module/TeamComparison.vue";
import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import SeriesCard from "@/Pages/Seasons/Module/SeriesCard.vue";

const isAddModalOpen = ref(false);
const isTeamModalOpen = ref(false);
const isTeamComparisonModalOpen = ref(false);
const isGameResultModalOpen = ref(false);
const loading = ref(false);
const change_key = ref(localStorage.getItem("season-key"));
const isHide = ref(false);
const activeIndex = ref(0);
const season_info = ref(false);
const season_playoffs = ref(false);
const is_play_ins = ref(false);
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

const compareTeams = (home_id, away_id) => {
    comparison.season_id = props.season_id;
    comparison.home_id = home_id;
    comparison.away_id = away_id;
    isTeamComparisonModalOpen.value = true;
};

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
       
       console.log('haos');

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

        season_playoffs.value.playoffs[round][index] = response.data.series;

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
            timer: 2000, // Auto-close after 2 seconds (2000ms)
            showConfirmButton: false,
            timerProgressBar: true
        });


        isGameResultModalOpen.value = game_id;
        isHide.value = false;
    } catch (error) {
        console.error("Error simulating the game:", error);
        Swal.close();
        isHide.value = false;
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to simulate game.",
        });
        throw error; // Rethrow to allow caller to handle
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

    let currentRoundIndex = 0;
    let initialRound = "start";

    // Initialize playoffs if not started
    if (season_info.value.seasons[0].status == 2) {
      await createPlayOffScheduleAuto(initialRound);
    }

    while (currentRoundIndex < roundOrder.length) {
      const roundName = roundOrder[currentRoundIndex];
      console.log("Processing round:", roundName);

      // Refresh latest season info and playoffs data
      await fetchSeasonInfo(form.seasons_id);
      await fetchSeasonPlayoffs(is_play_ins.value);

      const matches = season_playoffs.value.games[roundName] || [];

      if (matches.length === 0) {
        console.log(`No matches found for round ${roundName}, skipping...`);
        currentRoundIndex++;
        continue;
      }

      // Simulate ALL games in this round first
      for (let i = 0; i < matches.length; i++) {
        const match = matches[i];
        if (match.winner === 0) {
          console.log(`Simulating game ${match.game_id}...`);
          await simulateGame(match.id, match.game_id, 2, i, roundName);
          await fetchSeasonPlayoffs(is_play_ins.value); // Refresh after each game
        } else {
          console.log(`Game ${match.game_id} already finished with winner: ${match.winner}`);
        }
      }

      // After simulating all games, create schedule for next round if not finals
      if (roundName !== "finals") {
        try {
          console.log(`Creating schedule for next round after ${roundName}...`);
          const nextRoundResponse = await createPlayOffScheduleAuto(roundName);
          if (
            typeof nextRoundResponse === "string" &&
            nextRoundResponse.toLowerCase().includes("already created")
          ) {
            console.log(`Schedule after ${roundName} already exists.`);
          }
        } catch (scheduleError) {
          console.warn(`Error creating schedule after ${roundName}:`, scheduleError);
        }
      }

      currentRoundIndex++;
    }

    // Final data refresh
    await fetchSeasonInfo(form.seasons_id);
    await fetchSeasonPlayoffs(is_play_ins.value);

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
```