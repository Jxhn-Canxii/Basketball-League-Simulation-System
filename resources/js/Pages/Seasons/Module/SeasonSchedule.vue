<template>
    <div>
        <div
            class="flex justify-end mb-2 space-x-2"
            v-if="season_schedules && !season_schedules.is_simulated && !loadingSchedules"
        >
            <!-- <button
                @click.prevent="simulateConference(props.season_id,props.conference_id)"
                :disabled="isHide"
                :class="isHide ? 'opacity-50' : ''"
                class="text-indigo-600 bg-orange-300 shadow rounded-full p-2 font-bold text-md text-nowrap hover:text-indigo-900"
            >
                Simulate Conference
            </button> -->
            <button
                @click.prevent="simulateAll()"
                :disabled="isHide"
                :class="isHide ? 'opacity-50' : ''"
                class="text-indigo-600 bg-orange-400 shadow rounded-full p-2 font-bold text-md text-nowrap hover:text-indigo-900"
            >
                <span class="text-end">{{ isHide ? 'Getting results...' : 'Simulate All Season' }}</span>
            </button>
        </div>
        <div v-else>
            <p class="text-end"></p>
        </div>
    </div>
    <div class="block" v-if="isHide">
        <GameResults v-if="activeGameId != 0" :key="activeGameId" :game_id="activeGameId" :showBoxScore="false" />
        <div
            v-if="activeGameId != 0"
            class="w-full flex min-w-full overflow-x-auto border-b-2"
        >
            <ul class="flex flex-wrap">
                <li
                    v-for="conference in season_data.conferences"
                    :key="conference.id"
                    :class="
                        activeConferenceTab == conference.id
                            ? 'animate-pulse font-bold text-orange-500'
                            : ''
                    "
                    class="whitespace-nowrap group flex items-center px-3 py-2 cursor-pointer relative flex-shrink-0 max-w-xs"
                >
                    <i
                        :class="
                            activeConferenceTab == conference.id
                                ? 'text-orange-500'
                                : 'text-gray-500'
                        "
                        class="fa fa-shield mr-2"
                        :title="conference.name + ' Conference'"
                    ></i>
                    <span
                        hidden
                        class="text-truncate hidden sm:inline md:inline"
                        >{{ conference.name }}
                        {{
                            conference.champions_count > 0
                                ? "( " + conference.champions_count + " )"
                                : ""
                        }}</span
                    >
                    <!-- Warning Badge Notification Counter -->
                </li>
            </ul>
        </div>
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
    <Modal :show="isTradeModalOpen" :maxWidth="'fullscreen'">
                <button
                    class="flex float-end bg-gray-100 p-3"
                    @click.prevent="
                        isTradeModalOpen = false
                    "
                >
                    <i class="fa fa-times text-black-600"></i>
                </button>
                <div class="mt-4 p-3 block">
                    <Trade
                        @newSeason="handleTradeSeason"
                        :isOffSeason="false"
                    />
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
    import Trade from "./Trade.vue";
    
    const season_schedules = ref(false);
    const isTradeModalOpen = ref(false);
    const isGameResultModalOpen = ref(false);
    const isHide = ref(false);
    const activeConferenceTab = ref(0);
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
   
    const simulateAll = async () => {
        isHide.value = true;
        isTradeModalOpen.value = false;
        let failedGames = new Set(); // Store failed games

        console.log("Checking pending games for the season...");

        try {
            const response = await axios.post(route("upcoming.rounds.season"), {
                season_id: props.season_id,
            });

            let rounds = response.data.rounds;
            let isFinished = response.data.is_finished;
            let isTradeDeadline = response.data.is_trade_deadline;
            isTradeModalOpen.value = false;
            if(isTradeDeadline){
                isTradeModalOpen.value = true;
            }
            if (isFinished) {
                Swal.fire({
                    icon: "success",
                    title: "All games are simulated!",
                    text: "The entire season has been completed.",
                });
                isHide.value = false;
                return;
            }

            for (const roundData of rounds) {
                console.log(`Simulating Round: ${roundData}`);

                let roundResponse = await axios.post(route("game.per.round"), {
                    season_id: props.season_id,
                    round: roundData,
                });

                let gameIds = roundResponse.data.schedule_ids;

                while (gameIds.length > 0) {
                    for (const gameId of gameIds) {
                        console.log(`Simulating Game ID: ${gameId}`);

                        try {
                            await simulateGameWithResults(gameId.id,gameId.conference_id);
                            await new Promise((resolve) => setTimeout(resolve, 2000));

                            // Remove successfully simulated game from failedGames if it exists
                            failedGames.delete(gameId);
                        } catch (error) {
                            console.error(`Error simulating Game ID: ${gameId.id}`, error);
                            failedGames.add(gameId);
                        }
                    }

                    // Re-fetch remaining unsimulated games
                    roundResponse = await axios.post(route("game.per.round"), {
                        season_id: props.season_id,
                        round: roundData,
                    });

                    gameIds = roundResponse.data.schedule_ids;

                    if (gameIds.length > 0) {
                        console.warn(`Retrying ${gameIds.length} failed simulations`);
                    }
                }
            }
        } catch (error) {
            console.error("Error fetching or simulating games:", error);
        }

        // Retry failed games
        if (failedGames.size > 0) {
            console.warn(`Retrying ${failedGames.size} failed games...`);

            for (const gameId of [...failedGames]) {
                try {
                    console.log(`Retrying Game ID: ${gameId}`);
                    await simulateGameWithResults(gameId);
                    await new Promise((resolve) => setTimeout(resolve, 2000));
                    failedGames.delete(gameId); // Remove from failed list on success
                } catch (error) {
                    console.error(`Retry failed for Game ID: ${gameId}`, error);
                }
            }
        }

        isHide.value = false;
        await fetchConferenceSchedules();

        Swal.fire({
            icon: "success",
            title: "All games simulated!",
            text: "The entire season has been completed.",
        });
    };


    const simulateGameWithResults = async (schedule_id,conference_id) => {
        try {

            isHide.value = true;
            const response = await axios.post(route("game.simulate.regular"), {
                schedule_id: schedule_id,
            });

            activeGameId.value = response.data.game_id ?? 0;
            // isGameResultModalOpen.value = response.data.game_id; // Open game results modal

            // Show a toast notification
            Swal.fire({
                icon: "success",
                title: "Game Simulated!",
                text: `Game ID ${schedule_id} has been completed.`,
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: "top-end",
            });

            // Wait for the user to view results before moving to the next game
            activeConferenceTab.value = conference_id;
            emit('transaction_id',conference_id);
            await new Promise((resolve) => setTimeout(resolve, 3000)); // Allow time for UI to update

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
   