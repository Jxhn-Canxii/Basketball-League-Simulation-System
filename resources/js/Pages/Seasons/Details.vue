<template>
    <div class="min-w-0">
        <Head :title="'Season ' + season_id" />

        <AuthenticatedLayout>
            <template #header>
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-800 bg-gray-900 text-rose-400"
                    >
                        <i class="fa fa-calendar"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-white">
                            Season {{ season_id }}
                        </div>
                        <div class="text-[10px] uppercase tracking-wider text-gray-500">
                            Season Management
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex min-w-0 flex-1 flex-col bg-gray-950 p-2 text-white sm:p-3">
                <!-- =========================================================
                     TOP NAVIGATION
                ========================================================== -->
                <div
                    class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-xl"
                >
                    <!-- Header -->
                    <div
                        class="flex min-w-0 flex-col gap-3 border-b border-gray-800 p-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-950 text-rose-400 ring-1 ring-gray-800"
                            >
                                <i class="fa fa-layer-group"></i>
                            </div>

                            <div class="min-w-0">
                                <h1 class="truncate text-sm font-bold text-white sm:text-base">
                                    Season {{ season_id }}
                                </h1>
                                <p class="truncate text-[10px] uppercase tracking-wider text-gray-500">
                                    League overview and management
                                </p>
                            </div>
                        </div>

                        <!-- Season selector -->
                        <div class="w-full sm:w-auto sm:min-w-[190px]">
                            <label
                                for="season-selector"
                                class="mb-1 block text-[9px] font-bold uppercase tracking-widest text-gray-600"
                            >
                                Season
                            </label>

                            <div class="relative">
                                <select
                                    id="season-selector"
                                    v-model="season_id"
                                    class="w-full appearance-none rounded-lg border border-gray-800 bg-gray-950 px-3 py-2 pr-9 text-xs font-semibold text-gray-200 outline-none transition focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                >
                                    <option :value="0">
                                        Select Season
                                    </option>

                                    <option
                                        v-for="season in seasons"
                                        :key="season.season_id"
                                        :value="season.season_id"
                                    >
                                        {{ season.name }}
                                    </option>
                                </select>

                                <i
                                    class="fa fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-600"
                                ></i>
                            </div>
                        </div>
                    </div>

                    <!-- =====================================================
                         TAB BAR
                    ====================================================== -->
                    <div
                        class="min-w-0 overflow-x-auto border-b border-gray-800 bg-gray-950 scrollbar-thin"
                    >
                        <nav
                            class="flex w-max min-w-full items-center gap-1 p-2"
                            aria-label="Season navigation"
                        >
                            <!-- Season List -->
                            <a
                                :href="route('seasons.index')"
                                class="group flex shrink-0 items-center gap-2 rounded-lg border border-transparent px-3 py-2 text-xs font-semibold text-gray-400 transition hover:border-gray-800 hover:bg-gray-900 hover:text-white"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-md bg-gray-900 text-gray-500 transition group-hover:text-white"
                                >
                                    <i class="fa fa-list text-[10px]"></i>
                                </span>

                                <span>Season List</span>
                            </a>

                            <!-- Regular -->
                            <button
                                type="button"
                                @click="changeTab('Regular')"
                                :class="tabClass('Regular')"
                            >
                                <span :class="tabIconClass('Regular')">
                                    <i class="fa fa-trophy text-[10px]"></i>
                                </span>

                                <span>Regular Season</span>
                            </button>

                            <!-- Playoffs -->
                            <button
                                type="button"
                                @click="changeTab('Playoffs')"
                                :class="tabClass('Playoffs')"
                            >
                                <span :class="tabIconClass('Playoffs')">
                                    <i class="fa fa-diagram-project text-[10px]"></i>
                                </span>

                                <span>Playoffs</span>
                            </button>

                            <!-- Draft -->
                            <button
                                v-if="season_id > 1"
                                type="button"
                                @click="changeTab('Draft')"
                                :class="tabClass('Draft')"
                            >
                                <span :class="tabIconClass('Draft')">
                                    <i class="fa fa-star text-[10px]"></i>
                                </span>

                                <span>Draft Results</span>
                            </button>

                            <!-- Leaders -->
                            <!-- <button
                                type="button"
                                @click="changeTab('Leaders')"
                                :class="tabClass('Leaders')"
                            >
                                <span :class="tabIconClass('Leaders')">
                                    <i class="fa fa-chart-line text-[10px]"></i>
                                </span>

                                <span>Statistical Leaders</span>
                            </button> -->

                            <!-- Awards -->
                            <button
                                type="button"
                                @click="changeTab('Awards')"
                                :class="tabClass('Awards')"
                            >
                                <span :class="tabIconClass('Awards')">
                                    <i class="fa fa-medal text-[10px]"></i>
                                </span>

                                <span>Awards</span>
                            </button>

                            <!-- News -->
                            <button
                                type="button"
                                @click="changeTab('News')"
                                :class="tabClass('News')"
                            >
                                <span :class="tabIconClass('News')">
                                    <i class="fa fa-newspaper text-[10px]"></i>
                                </span>

                                <span>News</span>
                            </button>

                            <!-- Trade -->
                            <button
                                type="button"
                                @click="changeTab('Trade')"
                                :class="tabClass('Trade')"
                            >
                                <span :class="tabIconClass('Trade')">
                                    <i class="fa fa-exchange text-[10px]"></i>
                                </span>

                                <span>Trade</span>
                            </button>

                            <!-- Transactions -->
                            <button
                                type="button"
                                @click="changeTab('Transactions')"
                                :class="tabClass('Transactions')"
                            >
                                <span :class="tabIconClass('Transactions')">
                                    <i class="fa fa-list text-[10px]"></i>
                                </span>

                                <span>Transactions</span>
                            </button>

                            <!-- Reset -->
                            <button
                                type="button"
                                @click="changeTab('Reset')"
                                :class="tabClass('Reset')"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-md bg-red-500/10 text-red-500 transition"
                                >
                                    <i class="fa fa-rotate-left text-[10px]"></i>
                                </span>

                                <span>Reset</span>
                            </button>
                        </nav>
                    </div>

                    <!-- =====================================================
                         ACTIVE TAB INDICATOR
                    ====================================================== -->
                    <div
                        class="flex min-w-0 items-center justify-between gap-3 border-b border-gray-800 bg-gray-900 px-3 py-2"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.7)]"
                            ></span>

                            <span
                                class="truncate text-[10px] font-bold uppercase tracking-widest text-gray-500"
                            >
                                {{ currentTab }}
                            </span>
                        </div>

                        <span
                            v-if="season_id != 0"
                            class="shrink-0 rounded-md border border-gray-800 bg-gray-950 px-2 py-1 text-[9px] font-semibold text-gray-500"
                        >
                            Season {{ season_id }}
                        </span>
                    </div>
                </div>

                <!-- =========================================================
                     CONTENT
                ========================================================== -->
                <div class="mt-3 min-w-0">
                    <!-- Regular Season -->
                    <section
                        v-if="currentTab === 'Regular' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto">
                            <Seasons
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- Playoffs -->
                    <section
                        v-if="currentTab === 'Playoffs' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto">
                            <Playoffs
                                v-if="playoff_type == 1"
                                :key="season_id"
                                :season_id="season_id"
                            />

                            <PlayoffsSeries
                                v-else
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- Leaders -->
                    <section
                        v-if="currentTab === 'Leaders' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto">
                            <SeasonLeaders
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- Awards -->
                    <section
                        v-if="currentTab === 'Awards' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto">
                            <SeasonAwards
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- Draft -->
                    <section
                        v-if="currentTab === 'Draft' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto">
                            <DraftBoard
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- Transactions -->
                    <section
                        v-if="currentTab === 'Transactions' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto p-2 sm:p-4">
                            <Transactions
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- News -->
                    <section
                        v-if="currentTab === 'News' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto p-2 sm:p-4">
                            <AllNews
                                :key="season_id"
                                :season_id="season_id"
                            />
                        </div>
                    </section>

                    <!-- Trade -->
                    <section
                        v-if="currentTab === 'Trade' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto p-2 sm:p-4">
                            <ProposedTrade
                                :isOffSeason="false"
                                :seasonId="season_id"
                                :key="season_id"
                            />
                        </div>
                    </section>

                    <!-- Reset -->
                    <section
                        v-if="currentTab === 'Reset' && season_id != 0"
                        class="min-w-0 overflow-hidden rounded-xl border border-red-950/60 bg-gray-900 shadow-lg"
                    >
                        <div class="min-w-0 overflow-x-auto p-2 sm:p-4">
                            <ResetControls
                                :seasonId="season_id"
                                :key="season_id"
                            />
                        </div>
                    </section>

                    <!-- No Season -->
                    <section
                        v-if="season_id == 0"
                        class="flex min-h-[300px] min-w-0 items-center justify-center rounded-xl border border-gray-800 bg-gray-900"
                    >
                        <div class="flex flex-col items-center px-6 text-center">
                            <div
                                class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-gray-800 bg-gray-950 text-gray-600"
                            >
                                <i class="fa fa-calendar-days text-xl"></i>
                            </div>

                            <h2 class="text-sm font-bold text-gray-300">
                                Select a Season
                            </h2>

                            <p class="mt-1 max-w-sm text-xs text-gray-600">
                                Choose a season from the selector above to view
                                regular season statistics, playoffs, awards,
                                transactions, news, and other league information.
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios";

// Season modules
import Seasons from "@/Pages/Seasons/Module/Season.vue";
import Playoffs from "@/Pages/Seasons/Module/Playoffs.vue";
import PlayoffsSeries from "@/Pages/Seasons/Module/PlayoffsSeries.vue";
import SeasonAwards from "./Module/SeasonAwards.vue";
import SeasonLeaders from "../Analytics/Module/SeasonLeaders.vue";
import DraftBoard from "./Module/DraftBoard.vue";
import Transactions from "./Module/Transactions.vue";
import AllNews from "./Module/AllNews.vue";
import ProposedTrade from "./Module/ProposedTrade.vue";
import ResetControls from "./Module/ResetControls.vue";

const props = defineProps({
    season_id: {
        type: [Number, String],
        default: 0,
        required: true,
    },

    playoff_type: {
        type: [Number, String],
        default: 0,
        required: true,
    },
});

const season_id = ref(0);
const season_info = ref([]);
const seasons = ref([]);
const currentTab = ref("Regular");

/*
|--------------------------------------------------------------------------
| Tab configuration
|--------------------------------------------------------------------------
*/

const tabDefinitions = {
    Regular: {
        active: "text-rose-400",
        icon: "text-rose-400",
        iconBg: "bg-rose-500/10",
    },

    Playoffs: {
        active: "text-rose-400",
        icon: "text-rose-400",
        iconBg: "bg-rose-500/10",
    },

    Draft: {
        active: "text-amber-400",
        icon: "text-amber-400",
        iconBg: "bg-amber-500/10",
    },

    Leaders: {
        active: "text-cyan-400",
        icon: "text-cyan-400",
        iconBg: "bg-cyan-500/10",
    },

    Awards: {
        active: "text-yellow-400",
        icon: "text-yellow-400",
        iconBg: "bg-yellow-500/10",
    },

    News: {
        active: "text-sky-400",
        icon: "text-sky-400",
        iconBg: "bg-sky-500/10",
    },

    Trade: {
        active: "text-emerald-400",
        icon: "text-emerald-400",
        iconBg: "bg-emerald-500/10",
    },

    Transactions: {
        active: "text-violet-400",
        icon: "text-violet-400",
        iconBg: "bg-violet-500/10",
    },

    Reset: {
        active: "text-red-400",
        icon: "text-red-400",
        iconBg: "bg-red-500/10",
    },
};

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const changeTab = (tab) => {
    currentTab.value = tab;

    localStorage.setItem("active_tab", tab);
};

const tabClass = (tab) => {
    const config = tabDefinitions[tab] ?? tabDefinitions.Regular;
    const active = currentTab.value === tab;

    return [
        "group relative flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition-all duration-200",
        active
            ? [
                  "border border-gray-800",
                  "bg-gray-900",
                  config.active,
                  "shadow-sm",
              ]
            : [
                  "border border-transparent",
                  "text-gray-500",
                  "hover:border-gray-800",
                  "hover:bg-gray-900",
                  "hover:text-gray-200",
              ],
    ];
};

const tabIconClass = (tab) => {
    const config = tabDefinitions[tab] ?? tabDefinitions.Regular;
    const active = currentTab.value === tab;

    return [
        "flex h-6 w-6 shrink-0 items-center justify-center rounded-md transition-all duration-200",
        active
            ? [config.iconBg, config.icon]
            : [
                  "bg-gray-900",
                  "text-gray-600",
                  "group-hover:bg-gray-800",
                  "group-hover:text-gray-300",
              ],
    ];
};

/*
|--------------------------------------------------------------------------
| Season loading
|--------------------------------------------------------------------------
*/

const loadSeason = () => {
    season_id.value = props.season_id;

    const storedTab = localStorage.getItem("active_tab");

    /*
     * Keep the existing saved-tab behavior, but make sure Draft
     * cannot be selected for Season 1.
     */
    if (storedTab === "Draft" && Number(season_id.value) <= 1) {
        currentTab.value = "Regular";
    } else {
        currentTab.value = storedTab ?? "Regular";
    }

    seasonsDropdown();
    fetchSeasonInfo();
};

const seasonsDropdown = async () => {
    try {
        const response = await axios.post(
            route("seasons.dropdown"),
            {
                season_id: 0,
            }
        );

        seasons.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error("Error fetching seasons:", error);
        seasons.value = [];
    }
};

const fetchSeasonInfo = async () => {
    try {
        const response = await axios.post(
            route("seasons.info"),
            {
                season_id: props.season_id,
            }
        );

        season_info.value = response.data;
    } catch (error) {
        console.error("Error fetching season information:", error);

        season_info.value = [];
    }
};

onMounted(() => {
    loadSeason();
});
</script>

<style scoped>
/*
 * Keeps horizontal navigation usable on smaller screens
 * without forcing the entire page to become wider.
 */
.scrollbar-thin {
    scrollbar-width: thin;
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
</style>