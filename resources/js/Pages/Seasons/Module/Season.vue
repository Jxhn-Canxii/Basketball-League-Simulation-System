<template>
    <div class="min-w-0 w-full">
        <!-- ================================================================
             CONFERENCE NAVIGATION
        ================================================================= -->
        <div
            class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-lg"
        >
            <div
                class="flex min-w-0 flex-col gap-2 p-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <!-- Conference tabs -->
                <div class="min-w-0 flex-1 overflow-x-auto scrollbar-thin">
                    <nav
                        class="flex w-max min-w-full items-center gap-1"
                        aria-label="Conference navigation"
                    >
                        <button
                            v-for="conference in season_info?.conferences ?? []"
                            :key="conference.id"
                            type="button"
                            @click.prevent="fetchConferenceData(conference.id)"
                            :class="conferenceTabClass(conference.id)"
                        >
                            <span
                                :class="conferenceIconClass(conference.id)"
                            >
                                <i class="fa fa-shield text-[10px]"></i>
                            </span>

                            <span class="whitespace-nowrap">
                                {{ conference.name }}
                            </span>

                            <span
                                v-if="conference.champions_count > 0"
                                class="rounded-full border border-gray-700 bg-gray-900 px-1.5 py-0.5 text-[9px] font-bold text-gray-400"
                            >
                                {{ conference.champions_count }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Boost -->
                <button
                    type="button"
                    @click.prevent="boostMode = !boostMode"
                    :class="
                        boostMode
                            ? 'border-red-500/40 bg-red-500/10 text-red-400 shadow-[0_0_15px_rgba(239,68,68,0.08)]'
                            : 'border-gray-700 bg-gray-900 text-gray-400 hover:border-gray-600 hover:text-white'
                    "
                    class="group flex shrink-0 items-center justify-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold transition-all duration-200"
                >
                    <span
                        :class="
                            boostMode
                                ? 'bg-red-500/15 text-red-400'
                                : 'bg-gray-800 text-gray-500 group-hover:text-gray-300'
                        "
                        class="flex h-6 w-6 items-center justify-center rounded-md"
                    >
                        <i
                            class="fa fa-rocket text-[10px]"
                            :class="{ 'animate-pulse': boostMode }"
                        ></i>
                    </span>

                    <span>
                        {{ boostMode ? "Boost Active" : "Boost" }}
                    </span>

                    <span
                        :class="
                            boostMode
                                ? 'bg-red-500'
                                : 'bg-gray-700'
                        "
                        class="h-1.5 w-1.5 rounded-full transition-colors"
                    ></span>
                </button>
            </div>
        </div>

        <!-- ================================================================
             SEASON CONTENT
        ================================================================= -->
        <div
            v-if="
                season_info?.seasons &&
                season_info.seasons.length > 0 &&
                season_info.seasons[0].type != 1
            "
            class="mt-3 grid min-w-0 grid-cols-1 gap-3 lg:grid-cols-12"
        >
            <!-- ============================================================
                 LEFT PANEL
            ============================================================= -->
            <section
                class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg lg:col-span-5"
            >
                <!-- Panel header -->
                <div
                    class="flex items-center justify-between gap-3 border-b border-gray-800 bg-gray-950 px-3 py-2.5"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-rose-500/10 text-rose-400"
                        >
                            <i
                                class="fa fa-ranking-star text-[11px]"
                            ></i>
                        </span>

                        <div class="min-w-0">
                            <h2
                                class="truncate text-xs font-bold text-gray-200"
                            >
                                {{
                                    selectedConference != 0
                                        ? "Conference Standings"
                                        : "MVP Candidates"
                                }}
                            </h2>

                            <p
                                class="truncate text-[9px] uppercase tracking-wider text-gray-600"
                            >
                                {{
                                    selectedConference != 0
                                        ? "Current conference"
                                        : "Season leaders"
                                }}
                            </p>
                        </div>
                    </div>

                    <span
                        v-if="selectedConference != 0"
                        class="shrink-0 rounded-md border border-gray-800 bg-gray-900 px-2 py-1 text-[9px] font-semibold text-gray-500"
                    >
                        {{ activeConferenceName }}
                    </span>
                </div>

                <!-- Standings / MVP -->
                <div class="min-w-0 overflow-x-auto p-2">
                    <Standings
                        v-if="selectedConference != 0"
                        :key="updateKey"
                        :showLegend="false"
                        :season_id="props.season_id"
                        :conference_id="activeConferenceTab"
                        :season_data="season_info.seasons"
                    />

                    <Top15MVPCandidate
                        v-else
                        :key="updateKey"
                        :current_round="currentRound"
                    />
                </div>

                <!-- Debug / season state -->
                <div
                    class="border-t border-gray-800 bg-gray-950 px-3 py-2"
                >
                    <div
                        class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[9px] font-mono text-gray-600"
                    >
                        <span>
                            Transaction:
                            <strong class="text-gray-500">
                                {{ updateKey }}
                            </strong>
                        </span>

                        <span class="text-gray-800">•</span>

                        <span>
                            Conference:
                            <strong class="text-gray-500">
                                {{ currentConference }}
                            </strong>
                        </span>

                        <span class="text-gray-800">•</span>

                        <span>
                            Round:
                            <strong class="text-gray-500">
                                {{ currentRound ?? 0 }}
                            </strong>
                        </span>

                        <span class="text-gray-800">•</span>

                        <span>
                            Status:
                            <strong
                                :class="seasonStatusClass"
                            >
                                {{ seasonStatus ?? 0 }}
                            </strong>
                        </span>
                    </div>
                </div>

                <!-- Recent trade -->
                <div
                    v-if="seasonStatus == 1"
                    class="border-t border-gray-800 p-2"
                >
                    <div
                        class="mb-2 flex items-center gap-2 px-1"
                    >
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-500/10 text-emerald-400"
                        >
                            <i
                                class="fa fa-arrow-right-arrow-left text-[9px]"
                            ></i>
                        </span>

                        <span
                            class="text-[10px] font-bold uppercase tracking-wider text-gray-500"
                        >
                            Recent Trade
                        </span>
                    </div>

                    <div class="min-w-0 overflow-x-auto">
                        <RecentTradeProposal
                            :key="updateKey"
                            :isOffSeason="false"
                            :seasonId="props.season_id"
                        />
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 RIGHT PANEL
            ============================================================= -->
            <section
                class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg lg:col-span-7"
            >
                <!-- Panel header -->
                <div
                    class="flex items-center justify-between gap-3 border-b border-gray-800 bg-gray-950 px-3 py-2.5"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-cyan-500/10 text-cyan-400"
                        >
                            <i
                                class="fa fa-calendar-days text-[11px]"
                            ></i>
                        </span>

                        <div class="min-w-0">
                            <h2
                                class="truncate text-xs font-bold text-gray-200"
                            >
                                Schedule & Results
                            </h2>

                            <p
                                class="truncate text-[9px] uppercase tracking-wider text-gray-600"
                            >
                                Games and simulation
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            v-if="currentRound"
                            class="rounded-md border border-gray-800 bg-gray-900 px-2 py-1 text-[9px] font-semibold text-gray-500"
                        >
                            Round {{ currentRound }}
                        </span>

                        <span
                            :class="
                                seasonStatus == 1
                                    ? 'bg-emerald-500'
                                    : 'bg-gray-700'
                            "
                            class="h-1.5 w-1.5 rounded-full"
                        ></span>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="min-w-0 overflow-x-auto p-2">
                    <SeasonSchedule
                        v-if="season_info"
                        @transaction_id="handleTransaction"
                        @season_status="handleSeasonStatus"
                        @transaction_update="handleTransactionUpdate"
                        @round="handleCurrentRound"
                        @conference="handleCurrentConference"
                        :season_id="props.season_id"
                        :key="selectedConference"
                        :conference_id="activeConferenceTab"
                        :simulate_next="isAutoSimulate"
                        :season_data="season_info"
                    />
                </div>
            </section>
        </div>

        <!-- ================================================================
             LOADING / EMPTY STATE
        ================================================================= -->
        <div
            v-else
            class="mt-3 flex min-h-[300px] items-center justify-center rounded-xl border border-gray-800 bg-gray-900"
        >
            <div class="flex flex-col items-center text-center">
                <div
                    class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-gray-800 bg-gray-950 text-gray-600"
                >
                    <i class="fa fa-spinner fa-spin text-xl"></i>
                </div>

                <h2 class="text-sm font-bold text-gray-300">
                    Loading Season
                </h2>

                <p class="mt-1 text-xs text-gray-600">
                    Loading standings and season schedule...
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";

import Standings from "./Standings.vue";
import SeasonSchedule from "./SeasonSchedule.vue";
import Top15MVPCandidate from "@/Pages/Seasons/Module/Top15MVPCandidate.vue";
import RecentTradeProposal from "@/Pages/Seasons/Module/RecentTradeProposal.vue";

const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const season_info = ref(null);
const seasonStatus = ref(false);

const activeConferenceTab = ref(false);
const selectedConference = ref(1);

const currentRound = ref(0);
const currentConference = ref(1);

const isAutoSimulate = ref(false);
const updateKey = ref(0);
const transactionUpdate = ref(false);

const boostMode = ref(false);

/*
|--------------------------------------------------------------------------
| Conference helpers
|--------------------------------------------------------------------------
*/

const activeConferenceName = computed(() => {
    const conferences = season_info.value?.conferences ?? [];

    const conference = conferences.find(
        (item) => Number(item.id) === Number(activeConferenceTab.value)
    );

    return conference?.name ?? "Conference";
});

const conferenceTabClass = (id) => {
    const active =
        Number(activeConferenceTab.value) === Number(id);

    return [
        "group relative flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-200",
        active
            ? "border border-orange-500/20 bg-orange-500/10 text-orange-400"
            : "border border-transparent text-gray-500 hover:border-gray-800 hover:bg-gray-900 hover:text-gray-200",
    ];
};

const conferenceIconClass = (id) => {
    const active =
        Number(activeConferenceTab.value) === Number(id);

    return [
        "flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition-all duration-200",
        active
            ? "bg-orange-500/15 text-orange-400"
            : "bg-gray-900 text-gray-600 group-hover:bg-gray-800 group-hover:text-gray-300",
    ];
};

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

const seasonStatusClass = computed(() => {
    switch (Number(seasonStatus.value)) {
        case 1:
            return "text-emerald-400";

        case 2:
            return "text-amber-400";

        case 3:
            return "text-cyan-400";

        default:
            return "text-gray-500";
    }
});

/*
|--------------------------------------------------------------------------
| Conference selection
|--------------------------------------------------------------------------
*/

const fetchConferenceData = (id) => {
    activeConferenceTab.value = id;
    updateKey.value = id;
    selectedConference.value = id;
};

/*
|--------------------------------------------------------------------------
| Season data
|--------------------------------------------------------------------------
*/

const fetchSeasonInfo = async () => {
    try {
        const response = await axios.post(
            route("seasons.info"),
            {
                season_id: props.season_id,
            }
        );

        season_info.value = response.data;

        const conferences =
            season_info.value?.conferences ?? [];

        if (conferences.length > 0) {
            const firstConference = conferences[0];

            activeConferenceTab.value =
                firstConference.id;

            updateKey.value =
                firstConference.id;

            selectedConference.value =
                firstConference.id;

            currentConference.value =
                firstConference.id;
        }
    } catch (error) {
        console.error(
            "Error fetching season information:",
            error
        );

        season_info.value = null;
    }
};

/*
|--------------------------------------------------------------------------
| Child events
|--------------------------------------------------------------------------
*/

const handleTransaction = (id) => {
    console.log("Conference ID: " + id);

    updateKey.value =
        id + ":" + Math.random();

    activeConferenceTab.value = id;
};

const handleSeasonStatus = (status) => {
    console.log("Season Status: " + status);

    seasonStatus.value = status;
};

const handleCurrentRound = (round) => {
    console.log("Active Round: " + round);

    currentRound.value = round;
};

const handleCurrentConference = (conference) => {
    console.log("Active Conference: " + conference);

    currentConference.value = conference;
};

const handleTransactionUpdate = (transaction_change) => {
    const lastCount =
        localStorage.getItem("transaction_change");

    const lastValue =
        lastCount !== null
            ? Number(lastCount)
            : null;

    const currentValue =
        Number(transaction_change);

    const hasChanged =
        lastValue !== null &&
        lastValue > currentValue;

    console.log(
        "Transaction Updated: " +
            transaction_change +
            " Last Change: " +
            lastCount
    );

    localStorage.setItem(
        "transaction_change",
        transaction_change
    );

    transactionUpdate.value =
        hasChanged;

    console.log(
        "Transaction Update: " +
            hasChanged
    );
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchSeasonInfo();
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
</style>