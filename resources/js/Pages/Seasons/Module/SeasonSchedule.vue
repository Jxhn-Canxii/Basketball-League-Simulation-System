<template>
    <div class="w-full min-w-0 space-y-3">
        <!-- ================================================================
             SIMULATION ACTION
        ================================================================= -->
        <div
            v-if="data && !data.is_simulated && !loadingSchedules && !isHide"
            class="flex justify-end"
        >
            <button
                type="button"
                @click.prevent="simulateAll"
                :disabled="isHide"
                class="group flex items-center gap-2 rounded-lg border border-orange-500/30 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-400 shadow-sm transition-all duration-200 hover:border-orange-500/50 hover:bg-orange-500/15 hover:text-orange-300 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span
                    class="flex h-6 w-6 items-center justify-center rounded-md bg-orange-500/15"
                >
                    <i class="fa fa-forward text-[10px]"></i>
                </span>

                <span>Simulate All Season</span>
            </button>
        </div>

        <!-- ================================================================
             SEASON SECONDARY NAVIGATION
        ================================================================= -->
        <section
            v-if="!isHide"
            class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
        >
            <div
                class="flex min-w-0 overflow-x-auto border-b border-gray-800 bg-gray-950 scrollbar-thin"
            >
                <nav
                    class="flex w-max min-w-full items-center gap-1 p-2"
                    aria-label="Schedule navigation"
                >
                    <!-- Transactions -->
                    <button
                        type="button"
                        @click.prevent="showSchedule"
                        :class="secondaryTabClass(showTransactions)"
                    >
                        <span
                            :class="
                                showTransactions
                                    ? 'bg-red-500/10 text-red-400'
                                    : 'bg-gray-900 text-gray-600 group-hover:text-gray-300'
                            "
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition"
                        >
                            <i class="fas fa-exchange-alt text-[10px]"></i>
                        </span>

                        <span>Recent Transactions</span>
                    </button>

                    <!-- MVP -->
                    <button
                        type="button"
                        @click.prevent="showMVP"
                        :class="secondaryTabClass(showMVPLeaders)"
                    >
                        <span
                            :class="
                                showMVPLeaders
                                    ? 'bg-yellow-500/10 text-yellow-400'
                                    : 'bg-gray-900 text-gray-600 group-hover:text-gray-300'
                            "
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition"
                        >
                            <i class="fas fa-medal text-[10px]"></i>
                        </span>

                        <span>MVP Leaders</span>
                    </button>

                    <!-- Schedule -->
                    <button
                        v-if="showTransactions || showMVPLeaders"
                        type="button"
                        @click.prevent="showSchedule"
                        :class="secondaryTabClass(false)"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-gray-900 text-gray-600 transition group-hover:text-gray-300"
                        >
                            <i class="fas fa-calendar-alt text-[10px]"></i>
                        </span>

                        <span>Show Schedule</span>
                    </button>
                </nav>
            </div>

            <!-- ============================================================
                 TRANSACTIONS
            ============================================================= -->
            <transition name="fade" mode="out-in">
                <div
                    v-if="showTransactions"
                    class="min-w-0 p-2 sm:p-3"
                >
                    <div
                        class="mb-2 flex items-center gap-2 px-1"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-md bg-red-500/10 text-red-400"
                        >
                            <i class="fa fa-exchange-alt text-[10px]"></i>
                        </span>

                        <div>
                            <h3 class="text-xs font-bold text-gray-200">
                                Recent Transactions
                            </h3>

                            <p class="text-[9px] uppercase tracking-wider text-gray-600">
                                Latest league activity
                            </p>
                        </div>
                    </div>

                    <div class="min-w-0 overflow-x-auto">
                        <RecentTransactions
                            :key="'transactions-' + currentRound"
                            :showTitle="false"
                        />
                    </div>
                </div>
            </transition>

            <!-- ============================================================
                 MVP LEADERS
            ============================================================= -->
            <transition name="fade" mode="out-in">
                <div
                    v-if="showMVPLeaders"
                    class="min-w-0 p-2 sm:p-3"
                >
                    <div
                        class="mb-2 flex items-center gap-2 px-1"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-md bg-yellow-500/10 text-yellow-400"
                        >
                            <i class="fa fa-medal text-[10px]"></i>
                        </span>

                        <div>
                            <h3 class="text-xs font-bold text-gray-200">
                                MVP Leaders
                            </h3>

                            <p class="text-[9px] uppercase tracking-wider text-gray-600">
                                Top candidates this season
                            </p>
                        </div>
                    </div>

                    <div class="min-w-0 overflow-x-auto">
                        <Top15MVPCandidate
                            :key="'mvp-' + currentRound"
                            :current_round="currentRound"
                        />
                    </div>
                </div>
            </transition>
        </section>

        <!-- ================================================================
             GAME SIMULATION / RESULTS MODE
        ================================================================= -->
        <section
            v-if="isHide"
            class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-xl"
        >
            <!-- Game result -->
            <transition name="fade" mode="out-in">
                <div
                    v-if="activeGameId != 0"
                    :key="'game-' + activeGameId"
                    class="min-w-0 overflow-x-auto p-2 sm:p-3"
                >
                    <GameResults
                        :game_id="activeGameId"
                        :season_id="props.season_id"
                        :showBoxScore="false"
                        :showGameNews="false"
                    />
                </div>
            </transition>

            <!-- Active conference -->
            <div
                v-if="activeGameId != 0"
                class="min-w-0 border-t border-gray-800 bg-gray-900"
            >
                <div
                    class="flex min-w-0 overflow-x-auto scrollbar-thin"
                >
                    <nav
                        class="flex w-max min-w-full items-center gap-1 p-2"
                        aria-label="Simulation conference"
                    >
                        <div
                            v-for="conference in season_data?.conferences ?? []"
                            :key="conference.id"
                            :class="
                                Number(activeConferenceTab) ===
                                Number(conference.id)
                                    ? 'border-orange-500/20 bg-orange-500/10 text-orange-400'
                                    : 'border-transparent text-gray-600'
                            "
                            class="flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                        >
                            <span
                                :class="
                                    Number(activeConferenceTab) ===
                                    Number(conference.id)
                                        ? 'bg-orange-500/15 text-orange-400'
                                        : 'bg-gray-950 text-gray-600'
                                "
                                class="flex h-6 w-6 items-center justify-center rounded-md"
                            >
                                <i class="fa fa-shield text-[10px]"></i>
                            </span>

                            <span class="whitespace-nowrap">
                                {{ conference.name }}
                            </span>

                            <span
                                v-if="conference.champions_count > 0"
                                class="rounded-full bg-gray-950 px-1.5 py-0.5 text-[9px] text-gray-500"
                            >
                                {{ conference.champions_count }}
                            </span>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Simulation loading state -->
            <div
                v-if="activeGameId == 0"
                class="flex min-h-[420px] items-center justify-center border-t border-gray-800 bg-gray-950 p-6"
            >
                <div
                    class="flex w-full max-w-xs flex-col items-center text-center"
                >
                    <!-- League -->
                    <div class="mb-6">
                        <div
                            class="mb-2 text-[10px] font-bold uppercase tracking-[0.25em] text-orange-400"
                        >
                            Liga Pilipinas
                        </div>

                        <div class="font-mono text-xs text-gray-600">
                            GAME #{{ activeGameId ?? "-" }}
                        </div>
                    </div>

                    <!-- Teams / score skeleton -->
                    <div class="w-full space-y-3">
                        <div
                            class="mx-auto h-7 w-40 animate-pulse rounded-md bg-gray-800"
                        ></div>

                        <div
                            class="mx-auto h-5 w-28 animate-pulse rounded-md bg-gray-900"
                        ></div>

                        <div
                            class="mx-auto h-12 w-24 animate-pulse rounded-lg bg-gray-800"
                        ></div>

                        <div
                            class="flex items-center justify-center py-2"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-orange-500/20 bg-orange-500/10 text-orange-500"
                            >
                                <i
                                    class="fa fa-basketball text-sm animate-bounce"
                                ></i>
                            </div>
                        </div>

                        <div
                            class="mx-auto h-12 w-24 animate-pulse rounded-lg bg-gray-800"
                        ></div>

                        <div
                            class="mx-auto h-5 w-28 animate-pulse rounded-md bg-gray-900"
                        ></div>

                        <div
                            class="mx-auto h-7 w-40 animate-pulse rounded-md bg-gray-800"
                        ></div>
                    </div>

                    <div class="mt-8 flex items-center gap-2 text-[10px] uppercase tracking-widest text-gray-600">
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-orange-500"
                        ></span>

                        Simulating game...
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             SCHEDULE
        ================================================================= -->
        <section
            v-if="!isHide && !showTransactions && !showMVPLeaders"
            class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
        >
            <!-- Schedule header -->
            <div
                class="flex min-w-0 flex-col gap-3 border-b border-gray-800 bg-gray-950 p-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-400"
                    >
                        <i class="fa fa-calendar-days text-[11px]"></i>
                    </span>

                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-bold text-gray-200">
                            Schedule & Results
                        </h2>

                        <p class="text-[9px] uppercase tracking-wider text-gray-600">
                            {{ data?.total_count ?? 0 }} scheduled games
                        </p>
                    </div>
                </div>

                <!-- Team filter -->
                <div class="w-full sm:w-auto sm:min-w-[190px]">
                    <div class="relative">
                        <select
                            id="teamFilter"
                            v-model="search_schedule.team_id"
                            @change.prevent="searchInput"
                            class="w-full appearance-none rounded-lg border border-gray-800 bg-gray-900 px-3 py-2 pr-9 text-xs font-medium text-gray-300 outline-none transition focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500"
                        >
                            <option :value="0">
                                All Teams
                            </option>

                            <option
                                v-for="team in teams"
                                :key="team.id"
                                :value="team.id"
                            >
                                {{ team.name }}
                            </option>
                        </select>

                        <i
                            class="fa fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-gray-600"
                        ></i>
                    </div>
                </div>
            </div>

            <!-- Schedule body -->
            <div class="min-w-0 p-2 sm:p-3">
                <!-- Loading -->
                <div
                    v-if="loadingSchedules"
                    class="flex min-h-[220px] items-center justify-center"
                >
                    <div class="flex flex-col items-center">
                        <div
                            class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gray-950 text-cyan-400"
                        >
                            <i class="fa fa-spinner fa-spin"></i>
                        </div>

                        <span class="text-xs font-medium text-gray-500">
                            Loading schedules...
                        </span>
                    </div>
                </div>

                <!-- Schedule cards -->
                <div
                    v-else-if="
                        data &&
                        data.schedules?.length > 0
                    "
                    class="grid min-w-0 grid-cols-1 gap-3 xl:grid-cols-2"
                >
                    <ScoreCard
                        v-for="match in data.schedules"
                        :key="match.id ?? match.schedule_id"
                        :match="match"
                    />
                </div>

                <!-- Empty -->
                <div
                    v-else
                    class="flex min-h-[220px] items-center justify-center rounded-lg border border-dashed border-gray-800 bg-gray-950/50"
                >
                    <div class="text-center">
                        <div
                            class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gray-900 text-gray-700"
                        >
                            <i class="fa fa-calendar-xmark"></i>
                        </div>

                        <p class="text-xs font-semibold text-gray-500">
                            No schedule available
                        </p>

                        <p class="mt-1 text-[10px] text-gray-700">
                            There are no games matching the current filter.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="
                        data &&
                        data.schedules?.length > 0 &&
                        !loadingSchedules &&
                        data.total_count
                    "
                    class="mt-3 border-t border-gray-800 pt-3"
                >
                    <Paginator
                        :page_number="search_schedule.page_num"
                        :total_rows="data.total_count ?? 0"
                        :itemsperpage="search_schedule.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
        </section>

        <!-- ================================================================
             TRADE MODAL
        ================================================================= -->
        <Modal
            :show="isTradeModalOpen"
            :maxWidth="'fullscreen'"
            title="In Season Trade"
            @close="isTradeModalOpen = false"
        >
            <div class="min-w-0 bg-gray-950 p-2 sm:p-3">
                <Trade
                    :key="props.conference_id"
                    :isOffSeason="false"
                />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";

import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
import Trade from "@/Pages/Seasons/Module/Trade.vue";
import RecentTransactions from "@/Pages/Seasons/Module/RecentTransactions.vue";
import Top15MVPCandidate from "@/Pages/Seasons/Module/Top15MVPCandidate.vue";
import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";

const emit = defineEmits([
    "conference",
    "round",
    "season_status",
    "transaction_update",
    "transaction_id",
    "simulate_next_conference",
]);

const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },

    conference_id: {
        type: [Number, String],
        required: true,
    },

    season_data: {
        type: Object,
        default: () => ({}),
    },

    simulate_next: {
        type: Boolean,
        default: false,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const isTradeModalOpen = ref(false);
const currentRound = ref(0);

const showTransactions = ref(false);
const showMVPLeaders = ref(false);

const isHide = ref(false);
const activeConferenceTab = ref(0);

const loadingSchedules = ref(false);

const teams = ref([]);
const data = ref(null);

const activeGameId = ref(0);
const seasonStatus = ref(0);
const transactionUpdate = ref(0);

const showGameResults = ref(true);
const currentConference = ref(1);

const flipTimer = ref(null);

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

const search_schedule = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    conference_id: 0,
    team_id: 0,
    season_id: 0,
    itemsperpage: 6,
});

/*
|--------------------------------------------------------------------------
| Secondary tabs
|--------------------------------------------------------------------------
*/

const secondaryTabClass = (active) => {
    return [
        "group relative flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition-all duration-200",
        active
            ? "border-gray-800 bg-gray-900 text-gray-200"
            : "border-transparent text-gray-600 hover:border-gray-800 hover:bg-gray-900 hover:text-gray-300",
    ];
};

const showSchedule = () => {
    showTransactions.value = false;
    showMVPLeaders.value = false;
};

const showTransactionsPanel = () => {
    showTransactions.value = true;
    showMVPLeaders.value = false;
};

const showMVP = () => {
    showTransactions.value = false;
    showMVPLeaders.value = true;
};

/*
|--------------------------------------------------------------------------
| Schedule
|--------------------------------------------------------------------------
*/

const searchInput = () => {
    search_schedule.value.page_num = 1;

    fetchConferenceSchedules();
};

const fetchConferenceSchedules = async () => {
    loadingSchedules.value = true;

    try {
        search_schedule.value.season_id =
            props.season_id;

        search_schedule.value.conference_id =
            props.conference_id;

        const response = await axios.post(
            route("conferences.schedules"),
            search_schedule.value
        );

        data.value = response.data;
    } catch (error) {
        console.error(
            "Error fetching conference schedules:",
            error
        );

        data.value = {
            schedules: [],
            total_count: 0,
        };
    } finally {
        loadingSchedules.value = false;
    }
};

const handlePagination = (page_num) => {
    search_schedule.value.page_num =
        Number(page_num) || 1;

    fetchConferenceSchedules();
};

/*
|--------------------------------------------------------------------------
| Simulate entire season
|--------------------------------------------------------------------------
*/

const simulateAll = async () => {
    isHide.value = true;
    isTradeModalOpen.value = false;

    const failedGames = new Map();

    console.log(
        "Checking pending games for the season..."
    );

    try {
        const response = await axios.post(
            route("upcoming.rounds.season"),
            {
                season_id: props.season_id,
            }
        );

        currentRound.value =
            response.data.current_round ?? 0;

        const rounds =
            response.data.rounds ?? [];

        const isFinished =
            response.data.is_finished;

        if (isFinished) {
            Swal.fire({
                icon: "success",
                title: "All games are simulated!",
                text: "The entire season has been completed.",
                background: "#111827",
                color: "#fff",
            });

            isHide.value = false;

            return;
        }

        for (const roundData of rounds) {
            console.log(
                `Simulating Round: ${roundData}`
            );

            let roundResponse =
                await axios.post(
                    route("game.per.round"),
                    {
                        season_id:
                            props.season_id,
                        round: roundData,
                    }
                );

            let gameIds =
                roundResponse.data.schedule_ids ??
                [];

            const isTradeDeadline =
                roundResponse.data
                    .is_trade_deadline;

            isTradeModalOpen.value =
                false;

            if (isTradeDeadline) {
                isTradeModalOpen.value =
                    true;

                break;
            }

            while (gameIds.length > 0) {
                for (const game of gameIds) {
                    const gameId =
                        game.id;

                    console.log(
                        `Simulating Game ID: ${gameId}`
                    );

                    try {
                        await simulateGameWithResults(
                            gameId,
                            game.conference_id
                        );

                        await new Promise(
                            (resolve) =>
                                setTimeout(
                                    resolve,
                                    500
                                )
                        );

                        failedGames.delete(
                            gameId
                        );
                    } catch (error) {
                        console.error(
                            `Error simulating Game ID: ${gameId}`,
                            error
                        );

                        failedGames.set(
                            gameId,
                            game
                        );
                    }
                }

                roundResponse =
                    await axios.post(
                        route("game.per.round"),
                        {
                            season_id:
                                props.season_id,
                            round: roundData,
                        }
                    );

                gameIds =
                    roundResponse.data
                        .schedule_ids ?? [];

                if (gameIds.length > 0) {
                    console.warn(
                        `Retrying ${gameIds.length} failed simulations`
                    );
                }
            }
        }
    } catch (error) {
        console.error(
            "Error fetching or simulating games:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Simulation Error",
            text:
                error.response?.data?.message ||
                "An error occurred while simulating the season.",
            background: "#111827",
            color: "#fff",
        });
    }

    /*
     * Retry games that failed during the main simulation.
     */
    if (failedGames.size > 0) {
        console.warn(
            `Retrying ${failedGames.size} failed games...`
        );

        for (const [
            gameId,
            game,
        ] of failedGames) {
            try {
                console.log(
                    `Retrying Game ID: ${gameId}`
                );

                await simulateGameWithResults(
                    gameId,
                    game.conference_id
                );

                await new Promise(
                    (resolve) =>
                        setTimeout(
                            resolve,
                            1000
                        )
                );

                failedGames.delete(gameId);
            } catch (error) {
                console.error(
                    `Retry failed for Game ID: ${gameId}`,
                    error
                );
            }
        }
    }

    isHide.value = false;

    await fetchConferenceSchedules();

    /*
     * Only show completion message if there is no
     * trade-deadline modal currently waiting.
     */
    if (!isTradeModalOpen.value) {
        Swal.fire({
            icon: "success",
            title: "All games simulated!",
            text: "The entire season has been completed.",
            background: "#111827",
            color: "#fff",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Simulate individual game
|--------------------------------------------------------------------------
*/

const simulateGameWithResults = async (
    schedule_id,
    conference_id
) => {
    try {
        isHide.value = true;

        const simulateGameUrl =
            Number(conference_id) > 0
                ? "game.simulate.regular"
                : "game.simulate.allstar";

        const response = await axios.post(
            route(simulateGameUrl),
            {
                schedule_id,
                conference_id,
            }
        );

        Swal.fire({
            icon: "success",
            title: "Game Simulated!",
            text: `Game ID ${schedule_id} has been completed.`,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: "top-end",
            background: "#111827",
            color: "#fff",
        });

        activeGameId.value =
            response.data.game_id ?? 0;

        seasonStatus.value =
            response.data.season_status ?? 0;

        currentRound.value =
            response.data.round ?? 0;

        currentConference.value =
            response.data.conference_id ?? 0;

        transactionUpdate.value =
            response.data.transaction_count ?? 0;

        showGameResults.value = true;

        activeConferenceTab.value =
            conference_id;

        emit(
            "season_status",
            seasonStatus.value
        );

        emit(
            "round",
            currentRound.value
        );

        emit(
            "conference",
            currentConference.value
        );

        emit(
            "transaction_update",
            transactionUpdate.value
        );

        emit(
            "transaction_id",
            conference_id
        );

        await new Promise(
            (resolve) =>
                setTimeout(resolve, 500)
        );

        if (flipTimer.value) {
            clearInterval(
                flipTimer.value
            );

            flipTimer.value = null;
        }
    } catch (error) {
        console.error(
            "Error simulating game:",
            error
        );

        if (flipTimer.value) {
            clearInterval(
                flipTimer.value
            );

            flipTimer.value = null;
        }

        Swal.fire({
            icon: "error",
            title: "Simulation Error",
            text:
                error.response?.data?.message ||
                "An error occurred.",
            timer: 3000,
            showConfirmButton: false,
            background: "#111827",
            color: "#fff",
        });

        throw error;
    }
};

/*
|--------------------------------------------------------------------------
| Teams
|--------------------------------------------------------------------------
*/

const fetchConferenceTeams = async () => {
    try {
        const response = await axios.post(
            route("conference.team.dropdown", {
                conference_id:
                    props.conference_id,
            })
        );

        teams.value =
            Array.isArray(response.data)
                ? response.data
                : [];
    } catch (error) {
        console.error(
            "Error fetching conference teams:",
            error
        );

        teams.value = [];
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await fetchConferenceTeams();
    await fetchConferenceSchedules();
});

onUnmounted(() => {
    if (flipTimer.value) {
        clearInterval(
            flipTimer.value
        );

        flipTimer.value = null;
    }
});
</script>

<style scoped>
.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: rgb(55 65 81) transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    height: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgb(55 65 81);
    border-radius: 9999px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgb(75 85 99);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>