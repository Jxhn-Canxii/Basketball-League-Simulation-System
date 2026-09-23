<template>
    <section
        class="mb-8 w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-xl"
    >
        <!-- Header -->
        <div
            class="border-b border-white/[0.07] bg-gradient-to-r from-[#111111] via-[#0b0b0b] to-black px-4 py-4 sm:px-5"
        >
            <div class="flex items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/[0.06]"
                    >
                        <i
                            class="fas fa-chart-bar text-sm text-blue-400"
                        ></i>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-blue-400/70"
                        >
                            League Records
                        </div>

                        <h2
                            class="truncate text-base font-black tracking-tight text-white sm:text-lg"
                        >
                            All-Time Win-Loss Record
                        </h2>
                    </div>
                </div>

                <!-- Summary -->
                <div
                    v-if="!loading && data.length"
                    class="hidden shrink-0 items-center gap-4 sm:flex"
                >
                    <div class="text-right">
                        <div
                            class="text-[8px] font-black uppercase tracking-wider text-gray-600"
                        >
                            Teams
                        </div>

                        <div
                            class="mt-0.5 text-sm font-black text-white"
                        >
                            {{ data.length }}
                        </div>
                    </div>

                    <div class="h-7 w-px bg-white/[0.07]"></div>

                    <div class="text-right">
                        <div
                            class="text-[8px] font-black uppercase tracking-wider text-gray-600"
                        >
                            Games
                        </div>

                        <div
                            class="mt-0.5 text-sm font-black text-blue-400"
                        >
                            {{ totalGames }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="p-4 sm:p-6"
        >
            <div
                class="flex min-h-[360px] items-center justify-center rounded-xl border border-white/[0.05] bg-white/[0.02]"
            >
                <div class="flex flex-col items-center gap-4">
                    <div
                        class="h-12 w-12 animate-spin rounded-full border-2 border-white/[0.08] border-t-blue-500"
                    ></div>

                    <div
                        class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600"
                    >
                        Loading Win-Loss Records
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="!data.length"
            class="flex min-h-[320px] flex-col items-center justify-center px-6 py-10 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.03]"
            >
                <i
                    class="fas fa-chart-bar text-xl text-gray-700"
                ></i>
            </div>

            <div
                class="text-[8px] font-black uppercase tracking-[0.25em] text-gray-700"
            >
                League Records
            </div>

            <h3 class="mt-1 text-sm font-black text-gray-500">
                No Win-Loss Records
            </h3>

            <p
                class="mt-2 max-w-sm text-xs leading-relaxed text-gray-700"
            >
                All-time team records will appear here once game
                results have been recorded.
            </p>
        </div>

        <!-- Chart -->
        <div
            v-else
            class="p-4 sm:p-6"
        >
            <div
                class="relative w-full overflow-hidden rounded-xl border border-white/[0.05] bg-[#080808] p-3 sm:p-5"
            >
                <!-- Legend -->
                <div
                    class="mb-4 flex flex-wrap items-center gap-4 border-b border-white/[0.05] pb-4"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-sm bg-blue-500"
                        ></span>

                        <span
                            class="text-[9px] font-black uppercase tracking-wider text-gray-500"
                        >
                            Wins
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 rounded-sm bg-gray-600"
                        ></span>

                        <span
                            class="text-[9px] font-black uppercase tracking-wider text-gray-500"
                        >
                            Losses
                        </span>
                    </div>
                </div>

                <!-- Responsive chart viewport -->
                <div
                    class="relative h-[360px] w-full sm:h-[430px] lg:h-[500px]"
                >
                    <canvas
                        ref="allTimeWinLossChart"
                    ></canvas>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
} from "vue";
import Chart from "chart.js/auto";

const data = ref([]);
const loading = ref(true);

const allTimeWinLossChart = ref(null);

const search_topteams = ref({
    page_num: 1,
    total_pages: 0,
    per_page: 80,
    total: 0,
    search: "",
});

let chartInstance = null;

/*
|--------------------------------------------------------------------------
| Totals
|--------------------------------------------------------------------------
*/
const totalGames = computed(() => {
    return data.value.reduce((total, team) => {
        const wins = Number(team.total_wins) || 0;
        const losses = Number(team.total_losses) || 0;

        return total + wins + losses;
    }, 0);
});

/*
|--------------------------------------------------------------------------
| Team Color
|--------------------------------------------------------------------------
*/
const getTeamColor = (color, index) => {
    if (color) {
        return `#${String(color).replace("#", "")}`;
    }

    const fallbackColors = [
        "#3b82f6",
        "#ef4444",
        "#10b981",
        "#f59e0b",
        "#8b5cf6",
        "#ec4899",
        "#06b6d4",
        "#84cc16",
        "#f97316",
        "#14b8a6",
        "#6366f1",
        "#eab308",
    ];

    return fallbackColors[
        index % fallbackColors.length
    ];
};

/*
|--------------------------------------------------------------------------
| Fetch Teams
|--------------------------------------------------------------------------
*/
const fetchTopTeams = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("records.team.winningest"),
            search_topteams.value
        );

        data.value = Array.isArray(response.data?.data)
            ? response.data.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching win-loss records:",
            error
        );

        data.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Render Chart
|--------------------------------------------------------------------------
*/
const renderChart = async () => {
    await nextTick();

    if (!allTimeWinLossChart.value) {
        return;
    }

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    const labels = data.value.map(
        (team) => team.name ?? "Unknown Team"
    );

    const wins = data.value.map(
        (team) => Number(team.total_wins) || 0
    );

    const losses = data.value.map(
        (team) => Number(team.total_losses) || 0
    );

    const teamColors = data.value.map(
        (team, index) =>
            getTeamColor(team.primary_color, index)
    );

    chartInstance = new Chart(
        allTimeWinLossChart.value,
        {
            type: "bar",

            data: {
                labels,

                datasets: [
                    {
                        label: "Wins",

                        data: wins,

                        backgroundColor: teamColors,

                        borderColor: teamColors,

                        borderWidth: 1,

                        borderRadius: 5,

                        borderSkipped: false,

                        stack: "combined",

                        maxBarThickness: 42,
                    },

                    {
                        label: "Losses",

                        data: losses,

                        backgroundColor:
                            "rgba(107, 114, 128, 0.55)",

                        borderColor:
                            "rgba(156, 163, 175, 0.25)",

                        borderWidth: 1,

                        borderRadius: 5,

                        borderSkipped: false,

                        stack: "combined",

                        maxBarThickness: 42,
                    },
                ],
            },

            options: {
                responsive: true,

                maintainAspectRatio: false,

                animation: {
                    duration: 600,
                },

                interaction: {
                    mode: "index",

                    intersect: false,
                },

                plugins: {
                    title: {
                        display: false,
                    },

                    legend: {
                        display: false,
                    },

                    tooltip: {
                        backgroundColor: "#111111",

                        borderColor:
                            "rgba(255,255,255,0.08)",

                        borderWidth: 1,

                        titleColor: "#ffffff",

                        bodyColor: "#d1d5db",

                        padding: 12,

                        displayColors: true,

                        callbacks: {
                            title: (items) => {
                                return items[0]?.label ?? "";
                            },

                            label: (context) => {
                                const value =
                                    Number(context.raw) || 0;

                                return ` ${
                                    context.dataset.label
                                }: ${value}`;
                            },

                            afterBody: (items) => {
                                if (!items.length) {
                                    return "";
                                }

                                const index =
                                    items[0].dataIndex;

                                const team =
                                    data.value[index];

                                const wins =
                                    Number(
                                        team?.total_wins
                                    ) || 0;

                                const losses =
                                    Number(
                                        team?.total_losses
                                    ) || 0;

                                const games =
                                    wins + losses;

                                const percentage =
                                    games > 0
                                        ? (
                                              (wins /
                                                  games) *
                                              100
                                          ).toFixed(1)
                                        : "0.0";

                                return [
                                    "",
                                    `Games Played: ${games}`,
                                    `Win Rate: ${percentage}%`,
                                ];
                            },
                        },
                    },
                },

                scales: {
                    x: {
                        stacked: true,

                        grid: {
                            display: false,
                        },

                        border: {
                            color:
                                "rgba(255,255,255,0.06)",
                        },

                        title: {
                            display: true,

                            text: "Teams",

                            color: "#6b7280",

                            font: {
                                size: 10,

                                weight: "700",
                            },
                        },

                        ticks: {
                            color: "#9ca3af",

                            autoSkip: true,

                            maxTicksLimit: Math.min(
                                data.value.length,
                                15
                            ),

                            maxRotation: 45,

                            minRotation: 0,

                            font: {
                                size: 9,

                                weight: "600",
                            },
                        },
                    },

                    y: {
                        stacked: true,

                        beginAtZero: true,

                        grid: {
                            color:
                                "rgba(255,255,255,0.05)",

                            drawBorder: false,
                        },

                        border: {
                            display: false,
                        },

                        title: {
                            display: true,

                            text: "Games Played",

                            color: "#6b7280",

                            font: {
                                size: 10,

                                weight: "700",
                            },
                        },

                        ticks: {
                            color: "#6b7280",

                            precision: 0,

                            font: {
                                size: 9,
                            },
                        },
                    },
                },
            },
        }
    );
};

/*
|--------------------------------------------------------------------------
| Initialize
|--------------------------------------------------------------------------
*/
const showChart = async () => {
    await fetchTopTeams();

    if (data.value.length) {
        await renderChart();
    }
};

onMounted(() => {
    showChart();
});

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/
onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }
});
</script>

<style scoped>
canvas {
    display: block;
    width: 100% !important;
    height: 100% !important;
}
</style>