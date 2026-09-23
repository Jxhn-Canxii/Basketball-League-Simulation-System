<template>
    <section
        class="w-full rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl overflow-hidden"
    >
        <!-- Top Accent -->
        <div class="h-1 bg-gradient-to-r from-slate-700 via-slate-400 to-slate-700"></div>

        <!-- Header -->
        <div
            class="px-5 py-5 sm:px-6 border-b border-slate-800 bg-[#0d1117]"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Title -->
                <div class="flex items-start gap-3 min-w-0">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 border border-slate-700"
                    >
                        <i class="fas fa-ranking-star text-slate-300 text-lg"></i>
                    </div>

                    <div class="min-w-0">
                        <h2
                            class="text-lg sm:text-xl font-bold tracking-tight text-white"
                        >
                            Season Leaders
                        </h2>

                        <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
                            League leaders and top individual performances
                        </p>
                    </div>
                </div>

                <!-- Leader Selector -->
                <div class="w-full lg:w-72">
                    <label
                        for="leader-select"
                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-500"
                    >
                        Leader Category
                    </label>

                    <div class="relative">
                        <select
                            id="leader-select"
                            v-model="selectedLeaderType"
                            @change="fetchTopPlayers"
                            class="w-full appearance-none rounded-xl border border-slate-700 bg-[#080b10] px-4 py-2.5 pr-10 text-sm font-medium text-slate-200 outline-none transition focus:border-slate-500 focus:ring-1 focus:ring-slate-600"
                        >
                            <option value="mvp_leaders">
                                MVP Leaders
                            </option>

                            <option value="rookie_leaders">
                                Rookie Leaders
                            </option>

                            <option value="top_point_leaders">
                                Top Point Leaders
                            </option>

                            <option value="top_rebound_leaders">
                                Top Rebound Leaders
                            </option>

                            <option value="top_assist_leaders">
                                Top Assist Leaders
                            </option>

                            <option value="top_block_leaders">
                                Top Block Leaders
                            </option>

                            <option value="top_steals_leaders">
                                Top Steals Leaders
                            </option>

                            <option value="top_turnovers_leaders">
                                Top Turnover Leaders
                            </option>

                            <option value="top_fouls_leaders">
                                Top Fouls Leaders
                            </option>
                        </select>

                        <i
                            class="fas fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-slate-500"
                        ></i>
                    </div>
                </div>
            </div>

            <!-- Current Category -->
            <div class="mt-5 flex items-center gap-2">
                <span
                    class="h-1.5 w-1.5 rounded-full bg-slate-400"
                ></span>

                <span
                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                >
                    {{ currentLeaderLabel }}
                </span>

                <span
                    v-if="!loading"
                    class="text-[10px] text-slate-700"
                >
                    •
                </span>

                <span
                    v-if="!loading"
                    class="text-[10px] font-medium text-slate-600"
                >
                    {{ data.leaders.length }} players
                </span>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="flex min-h-[360px] items-center justify-center bg-[#0a0d12]"
        >
            <div class="flex flex-col items-center gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-700 bg-slate-900"
                >
                    <i
                        class="fas fa-spinner fa-spin text-slate-400"
                    ></i>
                </div>

                <div class="text-center">
                    <p class="text-sm font-semibold text-slate-300">
                        Loading leaders
                    </p>

                    <p class="mt-1 text-xs text-slate-600">
                        Updating season statistics...
                    </p>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="!data.leaders || data.leaders.length === 0"
            class="flex min-h-[300px] items-center justify-center bg-[#0a0d12] px-6"
        >
            <div class="text-center">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-slate-800 bg-[#080b10]"
                >
                    <i class="fas fa-chart-simple text-slate-600 text-lg"></i>
                </div>

                <h3 class="mt-4 text-sm font-semibold text-slate-300">
                    No leader data available
                </h3>

                <p class="mt-1 max-w-sm text-xs text-slate-600">
                    There are currently no qualifying players for this
                    category.
                </p>
            </div>
        </div>

        <!-- Players -->
        <div
            v-else
            class="bg-[#0a0d12] p-4 sm:p-5"
        >
            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5"
            >
                <article
                    v-for="(player, index) in data.leaders"
                    :key="player.player_id"
                    class="group relative overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117] transition duration-200 hover:border-slate-700 hover:bg-[#10151d]"
                >
                    <!-- Rank -->
                    <div
                        class="absolute right-3 top-3 flex h-7 min-w-7 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10] px-2"
                    >
                        <span
                            class="text-[10px] font-black tracking-wider"
                            :class="rankClass(index)"
                        >
                            {{ formatRank(index) }}
                        </span>
                    </div>

                    <!-- Player Header -->
                    <div class="p-4 pb-3">
                        <div class="flex items-center gap-3 pr-9">
                            <!-- Avatar -->
                            <div
                                class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-700 bg-slate-900"
                            >
                                <i
                                    class="fas fa-user text-slate-500 text-lg"
                                ></i>

                                <div
                                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-slate-600"
                                ></div>
                            </div>

                            <!-- Player Info -->
                            <div class="min-w-0">
                                <h3
                                    class="truncate text-sm font-bold text-white"
                                    :title="player.player_name"
                                >
                                    {{ player.player_name || "Unknown Player" }}
                                </h3>

                                <p
                                    class="mt-0.5 truncate text-xs text-slate-400"
                                    :title="player.team_name"
                                >
                                    {{ player.team_name || "Free Agent" }}
                                </p>

                                <div class="mt-1.5 flex items-center gap-1.5">
                                    <span
                                        class="rounded-md border border-slate-800 bg-slate-900 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-slate-500"
                                    >
                                        {{
                                            isRookie(player)
                                                ? "Rookie"
                                                : "Veteran"
                                        }}
                                    </span>

                                    <span
                                        v-if="player.draft_status"
                                        class="truncate text-[9px] text-slate-600"
                                    >
                                        {{ player.draft_status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="mx-4 border-t border-slate-800"></div>

                    <!-- Primary Stat -->
                    <div class="p-4">
                        <div
                            v-if="isCategoryStat"
                            class="flex items-end justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                                >
                                    {{ currentLeaderLabel }}
                                </p>

                                <div class="mt-1 flex items-baseline gap-1.5">
                                    <span
                                        class="text-3xl font-black tracking-tight text-white"
                                    >
                                        {{ getPrimaryStat(player).value }}
                                    </span>

                                    <span
                                        class="text-xs font-bold text-slate-500"
                                    >
                                        {{ getPrimaryStat(player).label }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10]"
                            >
                                <i
                                    :class="[
                                        'text-slate-500 text-sm',
                                        getPrimaryStat(player).icon,
                                    ]"
                                ></i>
                            </div>
                        </div>

                        <!-- MVP / Rookie Summary -->
                        <div v-else>
                            <div class="mb-3 flex items-end justify-between">
                                <div>
                                    <p
                                        class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                                    >
                                        Performance Score
                                    </p>

                                    <span
                                        class="mt-1 block text-3xl font-black tracking-tight text-white"
                                    >
                                        {{
                                            formatPerformanceScore(
                                                player.performance_score
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10]"
                                >
                                    <i
                                        class="fas fa-chart-line text-slate-500 text-sm"
                                    ></i>
                                </div>
                            </div>

                            <!-- Stat Grid -->
                            <div
                                class="grid grid-cols-4 gap-1.5"
                            >
                                <div
                                    v-for="stat in summaryStats(player)"
                                    :key="stat.label"
                                    class="rounded-lg border border-slate-800 bg-[#080b10] px-1.5 py-2 text-center"
                                >
                                    <p
                                        class="text-[8px] font-bold uppercase tracking-wide text-slate-600"
                                    >
                                        {{ stat.label }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-[11px] font-bold text-slate-300"
                                    >
                                        {{ stat.value }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Accent -->
                    <div
                        class="h-px w-full bg-gradient-to-r from-transparent via-slate-800 to-transparent"
                    ></div>
                </article>
            </div>

            <!-- Footer -->
            <div
                class="mt-4 flex flex-col gap-2 border-t border-slate-800 pt-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2">
                    <i class="fas fa-circle-info text-[10px] text-slate-600"></i>

                    <span class="text-[10px] text-slate-600">
                        Rankings are based on the selected season category.
                    </span>
                </div>

                <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-700"
                >
                    Season {{ props.season_id }}
                </span>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";

const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

const data = ref({
    leaders: [],
});

const selectedLeaderType = ref("mvp_leaders");
const loading = ref(false);

const leaderLabels = {
    mvp_leaders: "MVP Leaders",
    rookie_leaders: "Rookie Leaders",
    top_point_leaders: "Scoring Leaders",
    top_rebound_leaders: "Rebounding Leaders",
    top_assist_leaders: "Assist Leaders",
    top_block_leaders: "Block Leaders",
    top_steals_leaders: "Steals Leaders",
    top_turnovers_leaders: "Turnover Leaders",
    top_fouls_leaders: "Foul Leaders",
};

const leaderStatMap = {
    top_point_leaders: {
        key: "points_per_game",
        label: "PPG",
        icon: "fas fa-basketball",
    },

    top_rebound_leaders: {
        key: "rebounds_per_game",
        label: "RPG",
        icon: "fas fa-arrow-down",
    },

    top_assist_leaders: {
        key: "assists_per_game",
        label: "APG",
        icon: "fas fa-hands-helping",
    },

    top_steals_leaders: {
        key: "steals_per_game",
        label: "SPG",
        icon: "fas fa-hand",
    },

    top_block_leaders: {
        key: "blocks_per_game",
        label: "BPG",
        icon: "fas fa-shield-halved",
    },

    top_turnovers_leaders: {
        key: "turnovers_per_game",
        label: "TOPG",
        icon: "fas fa-rotate",
    },

    top_fouls_leaders: {
        key: "fouls_per_game",
        label: "FPG",
        icon: "fas fa-triangle-exclamation",
    },
};

const currentLeaderLabel = computed(() => {
    return (
        leaderLabels[selectedLeaderType.value] ||
        "Season Leaders"
    );
});

const isCategoryStat = computed(() => {
    return !!leaderStatMap[selectedLeaderType.value];
});

const fetchTopPlayers = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("players.season.leaders"),
            {
                leader_type: selectedLeaderType.value,
                season_id: props.season_id,
            }
        );

        data.value = {
            ...response.data,
            leaders: Array.isArray(response.data?.leaders)
                ? response.data.leaders
                : [],
        };
    } catch (error) {
        console.error("Error fetching top players:", error);

        data.value = {
            leaders: [],
        };
    } finally {
        loading.value = false;
    }
};

const isRookie = (player) => {
    return String(player?.draft_id) === String(props.season_id);
};

const getPrimaryStat = (player) => {
    const config = leaderStatMap[selectedLeaderType.value];

    if (!config) {
        return {
            value: formatNumber(player?.performance_score),
            label: "SCORE",
            icon: "fas fa-chart-line",
        };
    }

    return {
        value: formatNumber(player?.[config.key]),
        label: config.label,
        icon: config.icon,
    };
};

const summaryStats = (player) => {
    return [
        {
            label: "PPG",
            value: formatNumber(
                player?.avg_points_per_game
            ),
        },
        {
            label: "RPG",
            value: formatNumber(
                player?.avg_rebounds_per_game
            ),
        },
        {
            label: "APG",
            value: formatNumber(
                player?.avg_assists_per_game
            ),
        },
        {
            label: "GP",
            value: formatNumber(
                player?.games_played,
                0
            ),
        },
    ];
};

const formatNumber = (value, decimals = 1) => {
    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "-";
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "-";
    }

    return number.toFixed(decimals);
};

const formatPerformanceScore = (value) => {
    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "-";
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "-";
    }

    return number.toFixed(1);
};

const formatRank = (index) => {
    if (index === 0) return "01";
    if (index === 1) return "02";
    if (index === 2) return "03";

    return String(index + 1).padStart(2, "0");
};

const rankClass = (index) => {
    if (index === 0) {
        return "text-amber-400";
    }

    if (index === 1) {
        return "text-slate-300";
    }

    if (index === 2) {
        return "text-orange-400";
    }

    return "text-slate-600";
};

onMounted(() => {
    fetchTopPlayers();
});
</script>