<template>
    <div class="group w-full max-w-md mx-auto">
        <!-- SERIES CARD -->
        <div
            class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
        >
            <!-- =========================================================
                 SERIES HEADER
            ========================================================== -->
            <div
                class="relative overflow-hidden"
                :style="seriesHeaderStyle"
            >
                <!-- Dark overlay -->
                <div class="absolute inset-0 bg-black/20"></div>

                <!-- Series Info -->
                <div
                    class="relative flex items-center justify-between border-b border-white/10 bg-black/25 px-4 py-2.5 backdrop-blur-sm"
                >
                    <!-- Round -->
                    <div class="min-w-0">
                        <span
                            class="inline-flex max-w-full items-center rounded-full border border-white/10 bg-black/30 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white/90"
                        >
                            <span class="truncate">
                                {{ roundNameFormatter(series.round) }}
                            </span>
                        </span>
                    </div>

                    <!-- Best Of -->
                    <div class="shrink-0 text-right">
                        <span
                            class="text-[10px] font-bold uppercase tracking-wider text-white/60"
                        >
                            {{ series.best_of == 1 ? "Win or Go Home!" : "Best of" }}
                        </span>

                        <div
                            v-if="series.best_of != 1"
                            class="text-sm font-black text-white"
                        >
                            {{ series.best_of }}
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     SERIES SCORE
                ====================================================== -->
                <div
                    class="relative grid grid-cols-[1fr_auto_1fr] items-center px-4 py-5"
                >
                    <!-- Home -->
                    <div class="text-center">
                        <div
                            class="mb-1 text-[9px] font-bold uppercase tracking-[0.2em] text-white/60"
                        >
                            Home
                        </div>

                        <div
                            class="text-5xl font-black leading-none tracking-tight text-white drop-shadow-lg sm:text-6xl"
                        >
                            {{ series.home_team?.wins ?? 0 }}
                        </div>

                        <div
                            v-if="homeWon"
                            class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-400/90 px-2 py-0.5 text-[9px] font-black uppercase text-black"
                        >
                            <i class="fa fa-trophy text-[8px]"></i>
                            Winner
                        </div>
                    </div>

                    <!-- SERIES LEAD -->
                    <div class="px-3 text-center">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-black/30 text-[9px] font-black uppercase text-white/80 shadow-lg backdrop-blur-sm"
                        >
                            {{ series.best_of == 1 ? "VS" : "LEAD" }}
                        </div>

                        <div
                            v-if="series.best_of != 1"
                            class="mt-2 whitespace-nowrap text-[10px] font-bold uppercase tracking-wide text-white/80"
                        >
                            {{ series.series_lead }}
                        </div>
                    </div>

                    <!-- Away -->
                    <div class="text-center">
                        <div
                            class="mb-1 text-[9px] font-bold uppercase tracking-[0.2em] text-white/60"
                        >
                            Away
                        </div>

                        <div
                            class="text-5xl font-black leading-none tracking-tight text-white drop-shadow-lg sm:text-6xl"
                        >
                            {{ series.away_team?.wins ?? 0 }}
                        </div>

                        <div
                            v-if="awayWon"
                            class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-400/90 px-2 py-0.5 text-[9px] font-black uppercase text-black"
                        >
                            <i class="fa fa-trophy text-[8px]"></i>
                            Winner
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     TEAM NAMES
                ====================================================== -->
                <div
                    class="relative grid grid-cols-2 gap-3 border-t border-white/10 bg-black/20 px-3 py-3 backdrop-blur-sm"
                >
                    <!-- Home Team -->
                    <div
                        class="min-w-0 rounded-lg px-2 py-1 transition"
                        :class="homeWon ? 'bg-white/10' : ''"
                    >
                        <div class="flex items-center gap-1.5">
                            <i
                                v-if="homeWon"
                                class="fa fa-trophy shrink-0 text-[10px] text-amber-300"
                            ></i>

                            <TeamDetails
                                :team_id="series.home_team?.id"
                                :key="`home-${series.home_team?.id}`"
                                :showButton="0"
                                :showInfo="false"
                                class="!text-white"
                                :current_conference_rank="
                                    series.home_team?.conference_rank
                                "
                                :text="`#${series.home_team?.overall_rank ?? 'TBD'} ${
                                    series.home_team?.name ?? 'TBD'
                                }`"
                            />
                        </div>
                    </div>

                    <!-- Away Team -->
                    <div
                        class="min-w-0 rounded-lg px-2 py-1 text-right transition"
                        :class="awayWon ? 'bg-white/10' : ''"
                    >
                        <div class="flex items-center justify-end gap-1.5">
                            <TeamDetails
                                :team_id="series.away_team?.id"
                                :key="`away-${series.away_team?.id}`"
                                :showButton="0"
                                :showInfo="false"
                                class="!text-white"
                                :current_conference_rank="
                                    series.away_team?.conference_rank
                                "
                                :text="`#${series.away_team?.overall_rank ?? 'TBD'} ${
                                    series.away_team?.name ?? 'TBD'
                                }`"
                            />

                            <i
                                v-if="awayWon"
                                class="fa fa-trophy shrink-0 text-[10px] text-amber-300"
                            ></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =========================================================
                 SERIES META
            ========================================================== -->
            <div class="bg-white px-3 py-3 dark:bg-slate-950">
                <div class="flex items-center justify-between gap-2">
                    <!-- Conference -->
                    <div class="min-w-0">
                        <span
                            :class="
                                getConferenceClass(
                                    series.home_team?.conference,
                                    series.away_team?.conference
                                )
                            "
                            class="inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide shadow-sm"
                        >
                            <span class="truncate">
                                {{ series.home_team?.conference ?? "N/A" }}
                            </span>

                            <span class="opacity-40">#</span>

                            <span>
                                {{ series.home_team?.conference_rank ?? "TBD" }}
                            </span>

                            <span class="mx-0.5 opacity-40">vs</span>

                            <span class="truncate">
                                {{ series.away_team?.conference ?? "N/A" }}
                            </span>

                            <span class="opacity-40">#</span>

                            <span>
                                {{ series.away_team?.conference_rank ?? "TBD" }}
                            </span>
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex shrink-0 items-center gap-1.5">
                        <!-- Rivalry -->
                        <span
                            v-if="series.is_rivals"
                            title="Rivalry"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-500"
                        >
                            <i class="fa fa-fire text-xs"></i>
                        </span>

                        <!-- Compare -->
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-orange-500 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-orange-600 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-white"
                            @click="
                                compareTeams(
                                    series.home_team?.id,
                                    series.away_team?.id
                                )
                            "
                        >
                            <span>Compare</span>
                            <i class="fa fa-exchange-alt text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- =========================================================
                 VIEW RESULTS
            ========================================================== -->
            <div
                v-if="hasSeriesResults"
                class="border-t border-slate-200 bg-slate-950 px-3 py-2.5 dark:border-white/5"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-wider text-blue-400 transition hover:bg-white/5 hover:text-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                    @click="isSeriesResultModalOpen = true"
                >
                    <i class="fa fa-list-ol text-[10px]"></i>

                    <span>View Series Results</span>

                    <i
                        class="fa fa-arrow-right text-[9px] transition-transform duration-200 group-hover:translate-x-1"
                    ></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ===============================================================
         SERIES RESULT MODAL
    ================================================================ -->
    <Modal
        :show="isSeriesResultModalOpen"
        maxWidth="fullscreen"
        :title="series.series_lead"
        @close="isSeriesResultModalOpen = false"
    >
        <div class="mt-2 sm:mt-4">
            <SeriesResult
                v-if="series.series_id"
                :key="series.series_id"
                :series_id="series.series_id"
                :season_id="series.season_id"
            />
        </div>
    </Modal>

    <!-- ===============================================================
         TEAM COMPARISON MODAL
    ================================================================ -->
    <Modal
        :show="isTeamComparisonModalOpen"
        maxWidth="6xl"
        title="Team Comparison"
        @close="isTeamComparisonModalOpen = false"
    >
        <div class="mt-2 sm:mt-4">
            <TeamComparison
                :key="comparison.season_id"
                :season_id="comparison.season_id"
                :home_id="comparison.home_id"
                :away_id="comparison.away_id"
            />
        </div>
    </Modal>
</template>

<script setup>
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import Modal from "@/Components/Modal.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import SeriesResult from "@/Pages/Seasons/Module/SeriesResult.vue";
import TeamComparison from "@/Pages/Teams/Module/TeamComparison.vue";

import {
    roundNameFormatter,
} from "@/Utility/Formatter.js";

const props = defineProps({
    series: {
        type: Object,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| Modal State
|--------------------------------------------------------------------------
*/

const isSeriesResultModalOpen = ref(false);
const isTeamComparisonModalOpen = ref(false);

/*
|--------------------------------------------------------------------------
| Comparison
|--------------------------------------------------------------------------
*/

const comparison = useForm({
    season_id: 0,
    home_id: 0,
    away_id: 0,
});

const compareTeams = (homeId, awayId) => {
    comparison.season_id = props.series?.season_id ?? 0;
    comparison.home_id = homeId ?? 0;
    comparison.away_id = awayId ?? 0;

    isTeamComparisonModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Series State
|--------------------------------------------------------------------------
*/

const homeWon = computed(() => {
    if (!props.series?.winner_id) {
        return false;
    }

    return (
        Number(props.series.winner_id) ===
        Number(props.series?.home_team?.id)
    );
});

const awayWon = computed(() => {
    if (!props.series?.winner_id) {
        return false;
    }

    return (
        Number(props.series.winner_id) ===
        Number(props.series?.away_team?.id)
    );
});

const hasSeriesResults = computed(() => {
    return (
        Number(props.series?.home_team?.wins ?? 0) > 0 ||
        Number(props.series?.away_team?.wins ?? 0) > 0
    );
});

/*
|--------------------------------------------------------------------------
| Colors
|--------------------------------------------------------------------------
*/

const normalizeColor = (color, fallback = "334155") => {
    if (!color) {
        return fallback;
    }

    return String(color).replace("#", "");
};

const homePrimary = computed(() =>
    normalizeColor(
        props.series?.home_team?.primary_color,
        "1e293b"
    )
);

const homeSecondary = computed(() =>
    normalizeColor(
        props.series?.home_team?.secondary_color,
        "475569"
    )
);

const awayPrimary = computed(() =>
    normalizeColor(
        props.series?.away_team?.primary_color,
        "1e293b"
    )
);

const awaySecondary = computed(() =>
    normalizeColor(
        props.series?.away_team?.secondary_color,
        "475569"
    )
);

/*
|--------------------------------------------------------------------------
| Series Header Background
|--------------------------------------------------------------------------
*/

const seriesHeaderStyle = computed(() => {
    const homeGradient = homeWon.value || !props.series?.winner_id
        ? `#${homeSecondary.value} 0%, #${homeSecondary.value} 48%, #${homePrimary.value} 52%, #${homePrimary.value} 100%`
        : "#52525b 0%, #3f3f46 48%, #27272a 52%, #18181b 100%";

    const awayGradient = awayWon.value || !props.series?.winner_id
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