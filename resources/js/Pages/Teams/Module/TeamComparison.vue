<template>
    <div class="w-full rounded-2xl bg-slate-950 border border-slate-800 shadow-2xl overflow-hidden">
        <!-- ========================================================= -->
        <!-- PAGE HEADER -->
        <!-- ========================================================= -->
        <div
            class="relative px-4 sm:px-5 py-4 border-b border-slate-800"
        >
            <div
                class="absolute inset-x-0 top-0 h-1"
                :style="comparisonGradient"
            ></div>

            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/10"
                        >
                            <i
                                class="fas fa-scale-balanced text-xs text-indigo-400"
                            ></i>
                        </span>

                        <div>
                            <h2
                                class="text-base sm:text-lg font-black text-white"
                            >
                                Team Comparison
                            </h2>

                            <p
                                class="text-[10px] sm:text-xs text-slate-600 mt-0.5"
                            >
                                Franchise history, playoff success and current
                                season performance
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="self-start sm:self-auto inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-900 border border-slate-800"
                >
                    <i class="fas fa-chart-simple text-[10px] text-slate-500"></i>

                    <span
                        class="text-[9px] uppercase tracking-wider font-bold text-slate-500"
                    >
                        Head to Head
                    </span>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TEAM COMPARISON -->
        <!-- ========================================================= -->
        <div class="p-3 sm:p-4">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 sm:gap-4">
                <!-- ===================================================== -->
                <!-- HOME TEAM -->
                <!-- ===================================================== -->
                <div
                    class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900/70"
                >
                    <!-- Team accent -->
                    <div
                        class="absolute inset-x-0 top-0 h-1"
                        :style="{
                            background: homeGradient,
                        }"
                    ></div>

                    <!-- Loading -->
                    <div
                        v-if="homeLoading"
                        class="flex flex-col items-center justify-center min-h-[420px]"
                    >
                        <div
                            class="flex items-center justify-center w-11 h-11 rounded-xl bg-slate-950 border border-slate-800 mb-3"
                        >
                            <i
                                class="fas fa-spinner fa-spin text-indigo-400"
                            ></i>
                        </div>

                        <p class="text-sm font-semibold text-slate-400">
                            Loading Home Team
                        </p>

                        <p class="mt-1 text-xs text-slate-600">
                            Retrieving team statistics...
                        </p>
                    </div>

                    <!-- Content -->
                    <template v-else>
                        <!-- Team header -->
                        <div
                            class="relative p-4 border-b border-slate-800"
                            :style="{
                                background: `linear-gradient(135deg, ${hexColor(
                                    home?.teams?.primary_color,
                                    '#334155'
                                )}20, transparent 70%)`,
                            }"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-xl border border-white/10 shadow-lg"
                                        :style="{
                                            background: homeGradient,
                                        }"
                                    >
                                        <span
                                            class="text-xs font-black text-white"
                                        >
                                            {{
                                                home?.teams?.acronym ?? "HOME"
                                            }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <h3
                                            class="text-sm sm:text-base font-black text-white truncate"
                                        >
                                            {{
                                                home?.teams?.team_name ??
                                                "Home Team"
                                            }}
                                        </h3>

                                        <div
                                            class="flex flex-wrap items-center gap-2 mt-1.5"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white/5 border border-white/10 text-[9px] font-semibold text-slate-300"
                                            >
                                                <i
                                                    class="fas fa-building-columns text-slate-500"
                                                ></i>

                                                {{
                                                    home?.teams
                                                        ?.conference_name ??
                                                    "-"
                                                }}
                                            </span>

                                            <span
                                                v-if="home?.latestSeason?.[0]"
                                                class="text-[10px] font-bold text-slate-500"
                                            >
                                                {{
                                                    home.latestSeason[0].wins ??
                                                    0
                                                }}
                                                -
                                                {{
                                                    home.latestSeason[0].losses ??
                                                    0
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Streak -->
                                <div
                                    v-if="home?.latestSeason?.[0]"
                                    class="flex-shrink-0 text-right"
                                >
                                    <p
                                        class="text-[8px] uppercase tracking-wider text-slate-600"
                                    >
                                        Streak
                                    </p>

                                    <p
                                        class="text-sm font-black"
                                        :class="
                                            streakClass(
                                                home.latestSeason[0]
                                                    .streak_status
                                            )
                                        "
                                    >
                                        {{
                                            home.latestSeason[0]
                                                .streak_status ?? "-"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="p-3 sm:p-4 space-y-4">
                            <!-- All-Time -->
                            <StatSection
                                title="All-Time Record"
                                icon="fas fa-clock-rotate-left"
                                icon-class="text-cyan-400"
                                v-if="home.allTimeStats"
                            >
                                <StatRow
                                    label="Wins"
                                    :value="
                                        home.allTimeStats.all_time_wins ?? 0
                                    "
                                />

                                <StatRow
                                    label="Losses"
                                    :value="
                                        home.allTimeStats.all_time_losses ?? 0
                                    "
                                />

                                <StatRow
                                    label="Win Rate"
                                    :value="
                                        calculateWinRate(home.allTimeStats) +
                                        '%'
                                    "
                                    value-class="text-cyan-300"
                                />
                            </StatSection>

                            <!-- Playoffs -->
                            <StatSection
                                title="Playoff History"
                                icon="fas fa-trophy"
                                icon-class="text-amber-400"
                            >
                                <StatRow
                                    label="Playoff Appearances"
                                    :value="
                                        home.playoffStats
                                            ?.playoff_appearances ?? 0
                                    "
                                />

                                <StatRow
                                    label="Playoff Wins"
                                    :value="
                                        home.playoffStats?.playoff_wins ?? 0
                                    "
                                />

                                <StatRow
                                    label="Playoff Losses"
                                    :value="
                                        home.playoffStats?.playoff_losses ?? 0
                                    "
                                />

                                <StatRow
                                    label="Semi-Final Appearances"
                                    :value="
                                        home.roundStats
                                            ?.semi_final_appearances ?? 0
                                    "
                                />

                                <StatRow
                                    label="Quarter-Final Appearances"
                                    :value="
                                        home.roundStats
                                            ?.quarter_final_appearances ?? 0
                                    "
                                />
                            </StatSection>

                            <!-- Championships -->
                            <StatSection
                                title="Championship History"
                                icon="fas fa-crown"
                                icon-class="text-yellow-400"
                            >
                                <StatRow
                                    label="Finals Appearances"
                                    :value="
                                        home.finalsStats?.finals_appearances ??
                                        0
                                    "
                                />

                                <div
                                    v-if="home.finalsStats"
                                    class="flex items-center justify-between gap-3 py-2 border-b border-slate-800/70"
                                >
                                    <span class="stat-label">
                                        National Championships
                                    </span>

                                    <div
                                        class="flex items-center gap-2 min-w-0"
                                    >
                                        <span
                                            class="text-xs font-black text-white"
                                        >
                                            {{
                                                home.finalsStats.finals_wins ??
                                                0
                                            }}
                                        </span>

                                        <TrophyIcons
                                            :wins="
                                                home.finalsStats.finals_wins
                                            "
                                            :losses="
                                                home.finalsStats.finals_losses
                                            "
                                        />
                                    </div>
                                </div>

                                <StatRow
                                    label="Conference Championships"
                                    :value="
                                        home.seasonStats
                                            ?.conferenceChampions ?? 0
                                    "
                                    value-class="text-amber-300"
                                />
                            </StatSection>

                            <!-- Rankings -->
                            <StatSection
                                title="Season Rankings"
                                icon="fas fa-ranking-star"
                                icon-class="text-purple-400"
                            >
                                <StatRow
                                    label="National Rank #1"
                                    :value="
                                        home.seasonStats?.overallRank1Count ??
                                        0
                                    "
                                    value-class="text-purple-300"
                                />

                                <StatRow
                                    label="Conference Rank #1"
                                    :value="
                                        home.seasonStats
                                            ?.conferenceRank1Count ?? 0
                                    "
                                    value-class="text-purple-300"
                                />

                                <StatRow
                                    label="Worst Rankings"
                                    :value="
                                        home.seasonStats
                                            ?.lastOverallRankCount ?? 0
                                    "
                                    value-class="text-red-400"
                                />
                            </StatSection>
                        </div>
                    </template>
                </div>

                <!-- ===================================================== -->
                <!-- AWAY TEAM -->
                <!-- ===================================================== -->
                <div
                    class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-900/70"
                >
                    <!-- Team accent -->
                    <div
                        class="absolute inset-x-0 top-0 h-1"
                        :style="{
                            background: awayGradient,
                        }"
                    ></div>

                    <!-- Loading -->
                    <div
                        v-if="awayLoading"
                        class="flex flex-col items-center justify-center min-h-[420px]"
                    >
                        <div
                            class="flex items-center justify-center w-11 h-11 rounded-xl bg-slate-950 border border-slate-800 mb-3"
                        >
                            <i
                                class="fas fa-spinner fa-spin text-indigo-400"
                            ></i>
                        </div>

                        <p class="text-sm font-semibold text-slate-400">
                            Loading Away Team
                        </p>

                        <p class="mt-1 text-xs text-slate-600">
                            Retrieving team statistics...
                        </p>
                    </div>

                    <!-- Content -->
                    <template v-else>
                        <!-- Team header -->
                        <div
                            class="relative p-4 border-b border-slate-800"
                            :style="{
                                background: `linear-gradient(135deg, ${hexColor(
                                    away?.teams?.primary_color,
                                    '#334155'
                                )}20, transparent 70%)`,
                            }"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-xl border border-white/10 shadow-lg"
                                        :style="{
                                            background: awayGradient,
                                        }"
                                    >
                                        <span
                                            class="text-xs font-black text-white"
                                        >
                                            {{
                                                away?.teams?.acronym ?? "AWAY"
                                            }}
                                        </span>
                                    </div>

                                    <div class="min-w-0">
                                        <h3
                                            class="text-sm sm:text-base font-black text-white truncate"
                                        >
                                            {{
                                                away?.teams?.team_name ??
                                                "Away Team"
                                            }}
                                        </h3>

                                        <div
                                            class="flex flex-wrap items-center gap-2 mt-1.5"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-white/5 border border-white/10 text-[9px] font-semibold text-slate-300"
                                            >
                                                <i
                                                    class="fas fa-building-columns text-slate-500"
                                                ></i>

                                                {{
                                                    away?.teams
                                                        ?.conference_name ??
                                                    "-"
                                                }}
                                            </span>

                                            <span
                                                v-if="away?.latestSeason?.[0]"
                                                class="text-[10px] font-bold text-slate-500"
                                            >
                                                {{
                                                    away.latestSeason[0].wins ??
                                                    0
                                                }}
                                                -
                                                {{
                                                    away.latestSeason[0].losses ??
                                                    0
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Streak -->
                                <div
                                    v-if="away?.latestSeason?.[0]"
                                    class="flex-shrink-0 text-right"
                                >
                                    <p
                                        class="text-[8px] uppercase tracking-wider text-slate-600"
                                    >
                                        Streak
                                    </p>

                                    <p
                                        class="text-sm font-black"
                                        :class="
                                            streakClass(
                                                away.latestSeason[0]
                                                    .streak_status
                                            )
                                        "
                                    >
                                        {{
                                            away.latestSeason[0]
                                                .streak_status ?? "-"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="p-3 sm:p-4 space-y-4">
                            <!-- All-Time -->
                            <StatSection
                                title="All-Time Record"
                                icon="fas fa-clock-rotate-left"
                                icon-class="text-cyan-400"
                                v-if="away.allTimeStats"
                            >
                                <StatRow
                                    label="Wins"
                                    :value="
                                        away.allTimeStats.all_time_wins ?? 0
                                    "
                                />

                                <StatRow
                                    label="Losses"
                                    :value="
                                        away.allTimeStats.all_time_losses ?? 0
                                    "
                                />

                                <StatRow
                                    label="Win Rate"
                                    :value="
                                        calculateWinRate(away.allTimeStats) +
                                        '%'
                                    "
                                    value-class="text-cyan-300"
                                />
                            </StatSection>

                            <!-- Playoffs -->
                            <StatSection
                                title="Playoff History"
                                icon="fas fa-trophy"
                                icon-class="text-amber-400"
                            >
                                <StatRow
                                    label="Playoff Appearances"
                                    :value="
                                        away.playoffStats
                                            ?.playoff_appearances ?? 0
                                    "
                                />

                                <StatRow
                                    label="Playoff Wins"
                                    :value="
                                        away.playoffStats?.playoff_wins ?? 0
                                    "
                                />

                                <StatRow
                                    label="Playoff Losses"
                                    :value="
                                        away.playoffStats?.playoff_losses ?? 0
                                    "
                                />

                                <StatRow
                                    label="Semi-Final Appearances"
                                    :value="
                                        away.roundStats
                                            ?.semi_final_appearances ?? 0
                                    "
                                />

                                <StatRow
                                    label="Quarter-Final Appearances"
                                    :value="
                                        away.roundStats
                                            ?.quarter_final_appearances ?? 0
                                    "
                                />
                            </StatSection>

                            <!-- Championships -->
                            <StatSection
                                title="Championship History"
                                icon="fas fa-crown"
                                icon-class="text-yellow-400"
                            >
                                <StatRow
                                    label="Finals Appearances"
                                    :value="
                                        away.finalsStats?.finals_appearances ??
                                        0
                                    "
                                />

                                <div
                                    v-if="away.finalsStats"
                                    class="flex items-center justify-between gap-3 py-2 border-b border-slate-800/70"
                                >
                                    <span class="stat-label">
                                        National Championships
                                    </span>

                                    <div
                                        class="flex items-center gap-2 min-w-0"
                                    >
                                        <span
                                            class="text-xs font-black text-white"
                                        >
                                            {{
                                                away.finalsStats.finals_wins ??
                                                0
                                            }}
                                        </span>

                                        <TrophyIcons
                                            :wins="
                                                away.finalsStats.finals_wins
                                            "
                                            :losses="
                                                away.finalsStats.finals_losses
                                            "
                                        />
                                    </div>
                                </div>

                                <StatRow
                                    label="Conference Championships"
                                    :value="
                                        away.seasonStats
                                            ?.conferenceChampions ?? 0
                                    "
                                    value-class="text-amber-300"
                                />
                            </StatSection>

                            <!-- Rankings -->
                            <StatSection
                                title="Season Rankings"
                                icon="fas fa-ranking-star"
                                icon-class="text-purple-400"
                            >
                                <StatRow
                                    label="National Rank #1"
                                    :value="
                                        away.seasonStats?.overallRank1Count ??
                                        0
                                    "
                                    value-class="text-purple-300"
                                />

                                <StatRow
                                    label="Conference Rank #1"
                                    :value="
                                        away.seasonStats
                                            ?.conferenceRank1Count ?? 0
                                    "
                                    value-class="text-purple-300"
                                />

                                <StatRow
                                    label="Worst Rankings"
                                    :value="
                                        away.seasonStats
                                            ?.lastOverallRankCount ?? 0
                                    "
                                    value-class="text-red-400"
                                />
                            </StatSection>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MATCH / SERIES HISTORY -->
            <!-- ========================================================= -->
            <div class="mt-4">
                <MatchAndSeriesHistory
                    :key="
                        `${props.home_id}-${props.away_id}-${props.season_id}`
                    "
                    :home_id="props.home_id"
                    :away_id="props.away_id"
                    :season_id="props.season_id"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import MatchAndSeriesHistory from "./MatchAndSeriesHistory.vue";

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    home_id: {
        type: Number,
        required: true,
    },

    away_id: {
        type: Number,
        required: true,
    },

    season_id: {
        type: Number,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const home = ref([]);
const away = ref([]);

const homeLoading = ref(true);
const awayLoading = ref(true);

/*
|--------------------------------------------------------------------------
| Colors
|--------------------------------------------------------------------------
*/

const hexColor = (color, fallback = "#334155") => {
    if (!color) {
        return fallback;
    }

    return `#${String(color).replace("#", "")}`;
};

const teamGradient = (
    primary,
    secondary,
    fallbackPrimary = "#334155",
    fallbackSecondary = "#0f172a"
) => {
    return `linear-gradient(
        135deg,
        ${hexColor(primary, fallbackPrimary)} 0%,
        ${hexColor(secondary, fallbackSecondary)} 100%
    )`;
};

const homeGradient = computed(() => {
    return teamGradient(
        home.value?.teams?.primary_color,
        home.value?.teams?.secondary_color
    );
});

const awayGradient = computed(() => {
    return teamGradient(
        away.value?.teams?.primary_color,
        away.value?.teams?.secondary_color
    );
});

const comparisonGradient = computed(() => {
    const homePrimary = hexColor(
        home.value?.teams?.primary_color,
        "#334155"
    );

    const awayPrimary = hexColor(
        away.value?.teams?.primary_color,
        "#475569"
    );

    return {
        background: `linear-gradient(
            90deg,
            ${homePrimary},
            ${awayPrimary}
        )`,
    };
});

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const calculateWinRate = (stats) => {
    if (!stats) {
        return "0.00";
    }

    const wins = Number(stats.all_time_wins ?? 0);
    const losses = Number(stats.all_time_losses ?? 0);

    const totalGames = wins + losses;

    if (totalGames === 0) {
        return "0.00";
    }

    return ((wins / totalGames) * 100).toFixed(2);
};

const streakClass = (streak) => {
    if (!streak) {
        return "text-slate-500";
    }

    const value = String(streak).toUpperCase();

    if (value.startsWith("W")) {
        return "text-emerald-400";
    }

    if (value.startsWith("L")) {
        return "text-red-400";
    }

    return "text-slate-400";
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchHomeTeamInfo = async () => {
    homeLoading.value = true;

    try {
        const response = await axios.post(
            route("teams.latest.season"),
            {
                team_id: props.home_id,
                season_id: props.season_id,
            }
        );

        home.value = response.data?.data ?? [];
    } catch (error) {
        console.error(
            "Error fetching home team info:",
            error
        );

        home.value = [];
    } finally {
        homeLoading.value = false;
    }
};

const fetchAwayTeamInfo = async () => {
    awayLoading.value = true;

    try {
        const response = await axios.post(
            route("teams.latest.season"),
            {
                team_id: props.away_id,
                season_id: props.season_id,
            }
        );

        away.value = response.data?.data ?? [];
    } catch (error) {
        console.error(
            "Error fetching away team info:",
            error
        );

        away.value = [];
    } finally {
        awayLoading.value = false;
    }
};

const fetchDataForTeam = async () => {
    await Promise.all([
        fetchHomeTeamInfo(),
        fetchAwayTeamInfo(),
    ]);
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchDataForTeam();
});
</script>

<script>
/*
|--------------------------------------------------------------------------
| Reusable local components
|--------------------------------------------------------------------------
*/

export default {
    components: {
        StatSection: {
            props: {
                title: String,
                icon: String,
                iconClass: String,
            },

            template: `
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span
                            class="flex items-center justify-center w-6 h-6 rounded-md bg-slate-950 border border-slate-800"
                        >
                            <i
                                :class="[icon, iconClass]"
                                class="text-[9px]"
                            ></i>
                        </span>

                        <h4
                            class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500"
                        >
                            {{ title }}
                        </h4>
                    </div>

                    <div
                        class="rounded-lg border border-slate-800/80 bg-slate-950/60 px-3"
                    >
                        <slot></slot>
                    </div>
                </div>
            `,
        },

        StatRow: {
            props: {
                label: String,
                value: [String, Number],
                valueClass: {
                    type: String,
                    default: "text-slate-200",
                },
            },

            template: `
                <div
                    class="flex items-center justify-between gap-4 py-2 border-b border-slate-800/70 last:border-b-0"
                >
                    <span class="text-[10px] sm:text-xs text-slate-500 truncate">
                        {{ label }}
                    </span>

                    <span
                        class="text-xs font-bold whitespace-nowrap"
                        :class="valueClass"
                    >
                        {{ value }}
                    </span>
                </div>
            `,
        },

        TrophyIcons: {
            props: {
                wins: [String, Number],
                losses: [String, Number],
            },

            template: `
                <div
                    class="flex items-center gap-0.5 max-w-[130px] overflow-hidden"
                >
                    <template
                        v-for="i in Math.min(Number(wins || 0), 8)"
                        :key="'win-' + i"
                    >
                        <i class="fas fa-trophy text-[10px] text-yellow-400"></i>
                    </template>

                    <template
                        v-for="i in Math.min(Number(losses || 0), 8)"
                        :key="'loss-' + i"
                    >
                        <i class="fas fa-trophy text-[10px] text-slate-700"></i>
                    </template>
                </div>
            `,
        },
    },
};
</script>

<style scoped>
.stat-label {
    font-size: 0.625rem;
    line-height: 1rem;
    color: rgb(100 116 139);
}

@media (min-width: 640px) {
    .stat-label {
        font-size: 0.75rem;
    }
}
</style>