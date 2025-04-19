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
                v-if="!isHide"
                :class="isHide ? 'opacity-50' : ''"
                class="text-indigo-600 bg-orange-400 shadow rounded-full p-2 font-bold text-md text-nowrap hover:text-indigo-900"
            >
                <span class="text-end">Simulate All Season</span>
            </button>
        </div>
        <div v-else>
            <p class="text-end"></p>
        </div>
    </div>
    <div v-if="!isHide" class="mb-4">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button
                    @click.prevent="showTransactions = !showTransactions"
                    class="flex items-center px-1 py-2 text-sm font-medium"
                    :class="[
                        showTransactions 
                            ? 'border-b-2 border-orange-500 text-orange-600'
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Recent Transactions
                </button>
            </nav>
        </div>

        <!-- Transactions Panel -->
        <transition name="fade" mode="out-in">
            <div v-if="showTransactions" class="mt-4">
                <RecentTransactions :key="showTransactions" :showTitle="!showTransactions" />
            </div>
        </transition>
    </div>
    <div class="block px-2" v-if="isHide">
        <transition name="fade" mode="out-in">
            <div v-if="showGameResults && activeGameId != 0" :key="'game-' + activeGameId">
                <GameResults :game_id="activeGameId" :showBoxScore="false" />
            </div>
            <div v-else-if="!showGameResults && activeGameId != 0" :key="'transactions-' + activeGameId">
                <RecentTransactions :key="activeGameId"/>
            </div>
        </transition>
        <div
            v-if="activeGameId != 0"
            class="w-full flex min-w-full overflow-x-auto border-b-2 text-md"
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
    <div class="block" v-if="!isHide && !showTransactions">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-semibold text-gray-800">
                Schedule and Results ({{ season_schedules?.total_count }})
            </h2>
            <select 
                id="teamFilter" 
                v-model="search_schedule.team_id" 
                @change.prevent="fetchConferenceSchedules()" 
                class="ml-4 py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm"
            >
                <option value="0">All Teams</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
            </select>
        </div>
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
                :style="{
                background: `
                        linear-gradient(45deg, 
                            #${game.home_secondary_color} 0%, 
                            #${game.home_secondary_color} 50%, 
                            #${game.home_primary_color} 50%, 
                            #${game.home_primary_color} 100%
                        ),
                        linear-gradient(-45deg, 
                            #${game.away_primary_color} 0%, 
                            #${game.away_primary_color} 50%, 
                            #${game.away_secondary_color} 50%, 
                            #${game.away_secondary_color} 100%
                        )`,
                    backgroundSize: '50% 100%',
                    backgroundPosition: 'left, right',
                    backgroundRepeat: 'no-repeat'
                }"
                class="rounded"
            >
                <div
                    class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center"
                >
                    <div>
                        <h3>
                            {{ game.home_score }}
                        </h3>
                    </div>
                    <div>
                        <h3>
                            {{ game.away_score }}
                        </h3>
                    </div>
                </div>
                <div
                    class="px-1 py-2 flex justify-between items-center"
                >
                    <h3>
                        <TeamDetails
                            :team_id="game.home_team_id" 
                            :key="game.home_team_id"
                            class="text-white text-xl uppercase text-wrap text-left"
                            :showButton="0"
                            :text="`${game.home_team_name}`" />
                    </h3>
                    <h3>
                        <TeamDetails
                            :team_id="game.away_team_id" 
                            :key="game.away_team_id"
                            class="text-white text-xl uppercase text-wrap text-right" 
                            :showButton="0" 
                            :text="`${game.away_team_name}`" />
                    </h3>
                    </div>
                    <div
                        class="px-4 text-nowrap text-xs py-0"
                    >
                        <span class="px-2 text-lg text-white py-1 rounded  flex justify-center">
                            {{ "Round #" + (parseFloat(game.round) + 1) }}
                        </span>
                        <small class="flex justify-center text-white"> #R{{ game.round }}-{{ game.game_id }}</small>
                    </div>
                    <div class="p-0 mt-2 flex justify-center items-center">
                        <div
                            v-if="!isHide"
                        >
                            <a
                                href="#"
                                class="text-xs text-blue-500 underline font-bold rounded-t bg-white p-2"
                                @click.prevent="
                                    isGameResultModalOpen =
                                        game.game_id
                                "
                                >View Result
                            </a>
                        </div>
                        <div
                            v-else
                            class="text-center"
                        >
                            <p
                               class="text-sm text-blue-500 underline font-bold rounded bg-white p-2"
                            >
                                Getting results
                            </p>
                        </div>
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
    <Modal :show="isGameResultModalOpen" :maxWidth="'4xl'" title="Game Results" @close="isGameResultModalOpen = false">
        <div class="mt-4">
            <GameResults :game_id="isGameResultModalOpen" />
        </div>
    </Modal>
    <Modal :show="isTradeModalOpen" :maxWidth="'fullscreen'" title="In Season Trade" @close="isTradeModalOpen = false">
        <div class="p-3 block">
            <Trade
                :key="props.conference_id"
                :isOffSeason="false"
            />
        </div>
    </Modal>
</template>
<script setup>
    import { ref, onMounted, onUnmounted } from "vue";
    import Swal from "sweetalert2";
    import axios from "axios";
    import Modal from "@/Components/Modal.vue";
    import Paginator from "@/Components/Paginator.vue";
    import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
    import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
    import Trade from "@/Pages/Seasons/Module/Trade.vue";
    import RecentTransactions from "@/Pages/Seasons/Module/RecentTransactions.vue";

    const season_schedules = ref(false);
    const isTradeModalOpen = ref(false);
    const isGameResultModalOpen = ref(false);
    const showTransactions = ref(false);
    const isHide = ref(false);
    const activeConferenceTab = ref(0);
    const loadingSchedules = ref(false);
    const teams = ref([]);
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
        team_id: 0,
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

                let isTradeDeadline = roundResponse.data.is_trade_deadline;
                isTradeModalOpen.value = false;
                if(isTradeDeadline){
                    isTradeModalOpen.value = true;
                    break;
                }
                while (gameIds.length > 0) {
                    for (const gameId of gameIds) {
                        console.log(`Simulating Game ID: ${gameId.id}`);

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
            
            activeGameId.value = response.data.game_id ?? 0;
            showGameResults.value = true; // Show game results first

            // Wait for the user to view results before moving to the next game
            activeConferenceTab.value = conference_id;
            emit('transaction_id',conference_id);

            // Start flipping between views
            if (flipTimer.value) clearInterval(flipTimer.value);
            flipTimer.value = setInterval(() => {
                showGameResults.value = !showGameResults.value;
            }, 4000);
    
            await new Promise((resolve) => setTimeout(resolve, 8000)); // Allow 3 flips

            // Clear the interval when moving to next game
            if (flipTimer.value) {
                clearInterval(flipTimer.value);
                flipTimer.value = null;
            }

           

        } catch (error) {
            console.error("Error simulating game:", error);
            if (flipTimer.value) clearInterval(flipTimer.value);
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: error.response?.data?.message || "An error occurred.",
                timer: 3000,
                showConfirmButton: false,
            });
        }
    };

    const fetchConferenceTeams = async () => {
        try {
            const response = await axios.post(route("conference.team.dropdown", { conference_id: props.conference_id}));
            teams.value = response.data; // Store fetched standings data
        } catch (error) {
            console.error("Error fetching standings:", error);
        }
    };

    const showGameResults = ref(true);
    const flipTimer = ref(null);

    onMounted(async () => {
        await fetchConferenceTeams();
        await fetchConferenceSchedules();
    });

    // Clean up on component unmount
    onUnmounted(() => {
        if (flipTimer.value) {
            clearInterval(flipTimer.value);
            flipTimer.value = null;
        }
    });
</script>
<style>
.flip-enter-active,
.flip-leave-active {
    transition: transform 0.5s;
    transform-style: preserve-3d;
}

.flip-enter-from,
.flip-leave-to {
    transform: rotateX(180deg);
}

.flip-enter-to,
.flip-leave-from {
    transform: rotateX(0deg);
}

/* Add to your existing styles */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
