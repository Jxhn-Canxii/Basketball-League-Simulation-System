<template>
    <div>
        <div
            class="flex justify-end mb-2 space-x-2"
            v-if="season_schedules && !season_schedules.is_simulated && !loadingSchedules"
        >
            <button
                @click.prevent="simulateConference(props.season_id,props.conference_id)"
                :disabled="isHide"
                :class="isHide ? 'opacity-50' : ''"
                class="text-indigo-600 bg-orange-300 shadow rounded-full p-2 font-bold text-md text-nowrap hover:text-indigo-900"
            >
                Simulate Conference
            </button>
            <button
                @click.prevent="simulateAll()"
                :disabled="isHide"
                :class="isHide ? 'opacity-50' : ''"
                class="text-indigo-600 bg-orange-400 shadow rounded-full p-2 font-bold text-md text-nowrap hover:text-indigo-900"
            >
                Simulate All Season
            </button>
        </div>
        <div v-else>
            <p class="text-end"></p>
        </div>
    </div>
    <div class="block" v-if="isHide">
        <GameResults v-if="activeGameId != 0" :key="activeGameId" :game_id="activeGameId" :showBoxScore="false" />
        <div v-else class="p-0 bg-gray-900 shadow-md min-h-screen flex justify-center items-center rounded-lg max-w-7xl mx-auto">
            <!-- Skeleton Loader -->
            <div class="flex justify-center items-center h-full">
                <!-- Centered Loader -->
                <div class="flex flex-col items-center space-y-6">
                    <!-- Placeholder for Home Team Name -->
                    <div class="w-32 h-6 bg-gray-700 rounded-md animate-pulse"></div>

                    <!-- Placeholder for Home Team Score -->
                    <div class="w-24 h-8 bg-gray-700 rounded-md animate-pulse"></div>

                    <!-- Placeholder for "VS" Text -->
                    <div class="text-white text-xl font-semibold block">
                        <span class="animate-pulse">VS</span>
                    </div>

                    <!-- Placeholder for Away Team Score -->
                    <div class="w-24 h-8 bg-gray-700 rounded-md animate-pulse"></div>

                    <!-- Placeholder for Away Team Name -->
                    <div class="w-32 h-6 bg-gray-700 rounded-md animate-pulse"></div>

                    <!-- Placeholder for Round or Game Status -->
                    <div class="w-48 h-6 bg-gray-700 rounded-md animate-pulse mt-4"></div>

                    <!-- Placeholder for Matchup Record -->
                    <div class="w-32 h-6 bg-gray-700 rounded-md animate-pulse mt-4"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="block" v-else>
        <div class="flex justify-end mb-2"></div>
        <h2 class="text-lg font-semibold text-gray-800 mb-2">
            Schedule and Results ({{
                season_schedules?.schedules?.length
            }})
        </h2>
        <div
            v-if="
                season_schedules &&
                season_schedules.schedules?.length > 0 && !loadingSchedules
            "
            class="grid md:grid-cols-2 sm:col-span-1 gap-6"
        >
            <div
                v-for="(game, index) in season_schedules.schedules"
                :key="index"
                class="bg-white shadow overflow-hidden sm:rounded-lg"
            >
                <div class="px-4 py-5 sm:px-6">
                    <h3
                        class="text-xs flex font-extrabold text-nowrap leading-6 space-x-2 uppercase text-gray-900"
                    >
                        <TeamDetails
                            :team_id="game.home_team_id" 
                            :key="game.home_team_id" 
                            :showButton="0" 
                            :text="`${game.home_team_name}`" />
                        <br />
                        <small class="text-red-500">vs</small>
                        <br />
                        <TeamDetails
                            :team_id="game.away_team_id" 
                            :key="game.away_team_id" 
                            :showButton="0" 
                            :text="`${game.away_team_name}`" />
                    </h3>
                    <p
                        class="mt-1 max-w-2xl text-xs uppercase text-gray-500"
                    >
                        {{ "Round #" + (parseFloat(game.round) + 1) }}
                    </p>
                    <code class="mt-1 max-w-2xl text-xs text-gray-300">
                        #R{{ game.round }}-{{ game.game_id }}
                    </code>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <template
                            v-if="
                                game.home_score === 0 &&
                                game.away_score === 0
                            "
                        >
                            <div
                                v-if="!isHide"
                                class="bg-white px-4 py-5 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-6"
                            >
                                <a
                                    href="#"
                                    class="text-sm text-blue-500 underline font-bold"
                                    @click.prevent="
                                        isGameResultModalOpen =
                                            game.game_id
                                    "
                                    >View Result</a
                                >
                            </div>
                            <div
                                v-else
                                class="bg-white px-4 py-3 text-center sm:grid sm:grid-cols-1 sm:gap-4 sm:px-6"
                            >
                                <p
                                    class="text-red-500 animate-pulse text-xs text-nowrap"
                                >
                                    Getting results
                                </p>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                            >
                                <dt
                                    class="text-sm font-medium text-gray-500"
                                >
                                    Home
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 sm:col-span-2"
                                >
                                    {{ game.home_score }}
                                </dd>
                            </div>
                            <div
                                class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                            >
                                <dt
                                    class="text-sm font-medium text-gray-500"
                                >
                                    Away
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 sm:col-span-2"
                                >
                                    {{ game.away_score }}
                                </dd>
                            </div>
                            <div
                                class="bg-gray-200 px-4 py-1 flex justify-end sm:gap-4 sm:px-6"
                            >
                                <a
                                    href="#"
                                    class="text-sm text-blue-500 underline font-bold"
                                    @click.prevent="
                                        isGameResultModalOpen =
                                            game.game_id
                                    "
                                    >View Result</a
                                >
                            </div>
                        </template>
                    </dl>
                </div>
            </div>
        </div>
        <div v-if="loadingSchedules">
            <p class="text-gray-500">Loading Schedules.</p>
        </div>
        <div v-if="
                season_schedules &&
                season_schedules.schedules?.length == 0 && !loadingSchedules
            ">
            <p class="text-gray-500">No schedule available.</p>
        </div>
        <div class="flex w-full overflow-auto"
        v-if="
            season_schedules &&
            season_schedules.schedules?.length > 0 && !loadingSchedules
        ">
            <Paginator
                v-if="season_schedules.total_count"
                :page_number="search_schedule.page_num"
                :total_rows="season_schedules.total_count ?? 0"
                :itemsperpage="season_schedules.itemsperpage"
                @page_num="handlePagination"
            />
        </div>
    </div>
    <Modal :show="isGameResultModalOpen" :maxWidth="'4xl'">
        <button
            class="flex float-end bg-gray-100 p-3"
            @click.prevent="isGameResultModalOpen = false"
        >
            <i class="fa fa-times text-black-600"></i>
        </button>
        <div class="mt-4">
            <GameResults :game_id="isGameResultModalOpen" />
        </div>
    </Modal>
    </template>
   <script setup>
   import { ref, onMounted } from "vue";
   import Swal from "sweetalert2";
   import axios from "axios";
   import Modal from "@/Components/Modal.vue";
   import Paginator from "@/Components/Paginator.vue";
   import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
   import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
   
   const season_schedules = ref(false);
   const isGameResultModalOpen = ref(false);
   const isHide = ref(false);
   const currentRound = ref(0);
   const loadingSchedules = ref(false);
   const activeGameId = ref(0);
   const emit = defineEmits(["transaction_id", "simulate_next_conference"]);
   const props = defineProps({
       season_id: { type: [Number, String], required: true },
       conference_id: { type: [Number, String], required: true },
       season_data: Object,
       simulate_next: { type: Boolean, default: false },
   });
   
   const search_schedule = ref({
       current_page: 1,
       total_pages: 0,
       total: 0,
       search: "",
       conference_id: 0,
       season_id: 0,
       itemsperpage: 6,
   });
   
   const fetchConferenceSchedules = async (page = 1) => {
       try {
           season_schedules.value = [];
           loadingSchedules.value = true;
           search_schedule.value.current_page = page;
           search_schedule.value.season_id = props.season_id;
           search_schedule.value.conference_id = props.conference_id;
   
           const response = await axios.post(route("conferences.schedules"), search_schedule.value);
           season_schedules.value = response.data;
           loadingSchedules.value = false;
       } catch (error) {
           console.error("Error fetching season standings:", error);
       }
   };
   
   const handlePagination = (page_num) => {
       search_schedule.value.page_num = page_num ?? 1;
       fetchConferenceSchedules();
   };
   
   /**
    * Simulates all conferences in the season sequentially.
    */
   const simulateAll = async () => {
       isHide.value = true;
   
       for (const conference of props.season_data.conferences) {
           console.log(`Simulating conference: ${conference.name}`);
           await simulateConference(props.season_id, conference.id);
       }
   
       isHide.value = false;
       Swal.fire({
           icon: "success",
           title: "All conferences simulated!",
           text: "All games in all conferences have been completed.",
       });
   };
   
   /**
    * Simulates an entire conference round by round.
    */
   const simulateConference = async (season_id, conference_id) => {
       try {
           const response = await axios.post(route("upcoming.rounds.season"), {
               season_id: season_id,
               conference_id: conference_id,
           });
   
           const rounds = response.data.rounds;
           if (rounds.length === 0) {
               console.log(`No rounds left for conference ${conference_id}`);
               return;
           }
   
           for (const round of rounds) {
               console.log(`Simulating Round: ${round}`);
               await simulateConferenceRoundGames(round, conference_id);
           }
   
           console.log(`Finished simulating conference ${conference_id}`);
           await fetchConferenceSchedules();
   
       } catch (error) {
           console.error("Error simulating conference:", error);
           Swal.fire({
               icon: "warning",
               title: "Warning!",
               text: error.response?.data?.error || "An error occurred.",
           });
       }
   };
   
   /**
    * Simulates all games in a round within a conference.
    */
   const simulateConferenceRoundGames = async (round, conference_id) => {
       try {
           currentRound.value = round;
           Swal.fire({
               icon: "success",
               title: `Simulating Round ${round}`,
               timer: 2000,
               showConfirmButton: false,
               toast: true,
               position: "top-end",
           });
   
           const response = await axios.post(route("game.per.round"), {
               season_id: props.season_id,
               round: round,
               conference_id: conference_id,
           });
   
           const gameIds = response.data.schedule_ids;
           if (gameIds.length === 0) {
               console.log(`No games found for round ${round}`);
               return;
           }
   
           isHide.value = true;
   
           for (const gameId of gameIds) {
               console.log(`Processing Game ID: ${gameId}`);
               await simulateGame(gameId);
           }
   
           isHide.value = false;
           await fetchConferenceSchedules(); // Refresh schedules after simulating all games in the round
   
       } catch (error) {
           console.error("Error simulating round:", error);
           isHide.value = false;
       }
   };
   
   /**
    * Simulates a single game.
    */
   const simulateGame = async (schedule_id) => {
       try {
           const response = await axios.post(route("game.simulate.regular"), {
               schedule_id: schedule_id,
           });
   
           activeGameId.value = response.data.game_id ?? 0;
   
           // Fetch updated schedule to reflect game results
           await fetchConferenceSchedules();
   
           Swal.fire({
               icon: "success",
               title: "Game Simulated!",
               text: `Game ID ${schedule_id} has been completed.`,
               timer: 1500,
               showConfirmButton: false,
               toast: true,
               position: "top-end",
           });
   
       } catch (error) {
           console.error("Error simulating game:", error);
           Swal.fire({
               icon: "error",
               title: "Error!",
               text: error.response?.data?.message || "An error occurred.",
               timer: 3000,
               showConfirmButton: false,
           });
       }
   };
   
   onMounted(async () => {
       await fetchConferenceSchedules();
   });
   </script>
   