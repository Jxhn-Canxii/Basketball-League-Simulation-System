<template>
    <div
        class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
    >
        <!-- Top Accent -->
        <div
            class="h-px w-full bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
        ></div>

        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-800 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                >
                    <i class="fas fa-calendar-check text-sm text-slate-300"></i>
                </div>

                <div>
                    <h3
                        class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                    >
                        Last 12 Games
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Most recent completed games
                    </p>
                </div>
            </div>

            <div
                v-if="recent_results?.data?.length"
                class="flex items-center gap-2 self-start rounded-md border border-slate-800 bg-[#0d1117] px-3 py-2 sm:self-auto"
            >
                <i class="fas fa-chart-line text-[10px] text-slate-500"></i>

                <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-500"
                >
                    Recent Form
                </span>

                <span class="text-xs font-bold text-white">
                    {{ recent_results.data.length }} Games
                </span>
            </div>
        </div>

        <!-- Games -->
        <div class="p-3 sm:p-4 lg:p-5">
            <!-- Loading -->
            <div
                v-if="loading"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="index in 8"
                    :key="`loading-${index}`"
                    class="overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]"
                >
                    <div class="border-b border-slate-800 px-4 py-4">
                        <div
                            class="h-3 w-3/4 animate-pulse rounded bg-slate-800"
                        ></div>

                        <div
                            class="mt-2 h-2.5 w-1/3 animate-pulse rounded bg-slate-800"
                        ></div>
                    </div>

                    <div class="space-y-3 p-4">
                        <div
                            class="h-10 animate-pulse rounded bg-slate-800"
                        ></div>

                        <div
                            class="h-10 animate-pulse rounded bg-slate-800"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div
                v-else-if="recent_results?.data?.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="game in recent_results.data"
                    :key="game.id"
                    class="group overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117] transition-all duration-200 hover:border-slate-700 hover:bg-[#10151c]"
                >
                    <!-- Game Header -->
                    <div
                        class="border-b border-slate-800 px-4 py-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div
                                    class="truncate text-xs font-bold uppercase tracking-wide text-slate-200"
                                >
                                    {{ game.home_team_name }}
                                    <span class="px-1 text-slate-600">vs</span>
                                    {{ game.away_team_name }}
                                </div>

                                <div
                                    class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-slate-600"
                                >
                                    {{ roundNameFormatter(game.round) }}
                                </div>
                            </div>

                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-800 bg-[#080b10]"
                            >
                                <i
                                    class="fas fa-basketball-ball text-[10px] text-slate-600"
                                ></i>
                            </div>
                        </div>
                    </div>

                    <!-- Scoreboard -->
                    <div class="space-y-2 p-3">
                        <!-- Home -->
                        <div
                            :class="[
                                'flex items-center justify-between rounded-lg border px-3 py-3 transition',
                                isWinner(game, 'home')
                                    ? 'border-slate-600 bg-slate-800/50'
                                    : 'border-slate-800 bg-[#080b10]'
                            ]"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    :class="[
                                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-[10px] font-bold',
                                        isWinner(game, 'home')
                                            ? 'bg-slate-700 text-white'
                                            : 'bg-slate-900 text-slate-600'
                                    ]"
                                >
                                    H
                                </div>

                                <div class="min-w-0">
                                    <div
                                        :class="[
                                            'truncate text-xs uppercase tracking-wide',
                                            isWinner(game, 'home')
                                                ? 'font-bold text-white'
                                                : 'font-medium text-slate-400'
                                        ]"
                                    >
                                        {{ game.home_team_name }}
                                    </div>

                                    <div
                                        v-if="isWinner(game, 'home')"
                                        class="mt-0.5 text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                    >
                                        Winner
                                    </div>
                                </div>
                            </div>

                            <div
                                :class="[
                                    'ml-3 text-xl font-black tabular-nums',
                                    isWinner(game, 'home')
                                        ? 'text-white'
                                        : 'text-slate-500'
                                ]"
                            >
                                {{ game.home_score }}
                            </div>
                        </div>

                        <!-- Away -->
                        <div
                            :class="[
                                'flex items-center justify-between rounded-lg border px-3 py-3 transition',
                                isWinner(game, 'away')
                                    ? 'border-slate-600 bg-slate-800/50'
                                    : 'border-slate-800 bg-[#080b10]'
                            ]"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    :class="[
                                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-[10px] font-bold',
                                        isWinner(game, 'away')
                                            ? 'bg-slate-700 text-white'
                                            : 'bg-slate-900 text-slate-600'
                                    ]"
                                >
                                    A
                                </div>

                                <div class="min-w-0">
                                    <div
                                        :class="[
                                            'truncate text-xs uppercase tracking-wide',
                                            isWinner(game, 'away')
                                                ? 'font-bold text-white'
                                                : 'font-medium text-slate-400'
                                        ]"
                                    >
                                        {{ game.away_team_name }}
                                    </div>

                                    <div
                                        v-if="isWinner(game, 'away')"
                                        class="mt-0.5 text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                    >
                                        Winner
                                    </div>
                                </div>
                            </div>

                            <div
                                :class="[
                                    'ml-3 text-xl font-black tabular-nums',
                                    isWinner(game, 'away')
                                        ? 'text-white'
                                        : 'text-slate-500'
                                ]"
                            >
                                {{ game.away_score }}
                            </div>
                        </div>
                    </div>

                    <!-- Result Footer -->
                    <div
                        class="flex items-center justify-between border-t border-slate-800 px-4 py-2.5"
                    >
                        <span
                            class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Final
                        </span>

                        <span
                            v-if="getMargin(game) > 0"
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                        >
                            +{{ getMargin(game) }} Margin
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-else
                class="flex min-h-[240px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-800 bg-[#0a0d12] px-6 text-center"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                >
                    <i
                        class="fas fa-calendar-times text-sm text-slate-600"
                    ></i>
                </div>

                <p
                    class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                >
                    No Recent Games
                </p>

                <p class="mt-1 text-xs text-slate-600">
                    There are no completed games to display.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { roundNameFormatter } from "@/Utility/Formatter";

const recent_results = ref({
    data: [],
});

const loading = ref(false);

const fetchRecentResults = async () => {
    try {
        loading.value = true;

        const response = await axios.post(route("records.recent"));

        recent_results.value = response.data ?? {
            data: [],
        };
    } catch (error) {
        console.error("Error fetching recent results:", error);

        recent_results.value = {
            data: [],
        };
    } finally {
        loading.value = false;
    }
};

const isWinner = (game, side) => {
    const homeScore = Number(game.home_score ?? 0);
    const awayScore = Number(game.away_score ?? 0);

    if (homeScore === awayScore) {
        return false;
    }

    return side === "home"
        ? homeScore > awayScore
        : awayScore > homeScore;
};

const getMargin = (game) => {
    const homeScore = Number(game.home_score ?? 0);
    const awayScore = Number(game.away_score ?? 0);

    return Math.abs(homeScore - awayScore);
};

onMounted(() => {
    fetchRecentResults();
});
</script>