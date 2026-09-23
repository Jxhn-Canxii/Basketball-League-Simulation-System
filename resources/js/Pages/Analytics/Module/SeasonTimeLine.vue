<template>
    <!-- Main Container -->
    <section
        v-if="
            standings &&
            standings.datasets &&
            standings.datasets.length &&
            !loading
        "
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
                            class="fas fa-chart-line text-sm text-blue-400"
                        ></i>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-blue-400/70"
                        >
                            League Analytics
                        </div>

                        <h2
                            class="truncate text-base font-black tracking-tight text-white sm:text-lg"
                        >
                            Team Season Progression
                        </h2>
                    </div>
                </div>

                <!-- Team Count -->
                <div
                    class="hidden shrink-0 rounded-lg border border-white/[0.07] bg-white/[0.03] px-3 py-2 text-right sm:block"
                >
                    <div
                        class="text-[8px] font-black uppercase tracking-wider text-gray-600"
                    >
                        Teams
                    </div>

                    <div
                        class="mt-0.5 text-sm font-black text-blue-400"
                    >
                        {{ uniqueTeams.length }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div
            v-if="props.teamId == 0"
            class="border-b border-white/[0.05] bg-[#080808] px-4 py-4 sm:px-5"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <label
                        for="teamFilter"
                        class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-600"
                    >
                        Team Filter
                    </label>

                    <p
                        class="mt-0.5 text-[10px] text-gray-700"
                    >
                        View progression for a specific team or the entire league.
                    </p>
                </div>

                <div class="w-full sm:w-64">
                    <div class="relative">
                        <i
                            class="fas fa-filter pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-600"
                        ></i>

                        <select
                            id="teamFilter"
                            v-model="selectedTeam"
                            @change="renderSeasonProgressionChart"
                            class="w-full appearance-none rounded-xl border border-white/[0.08] bg-black py-2.5 pl-9 pr-9 text-xs font-semibold text-gray-300 outline-none transition focus:border-blue-500/40 focus:ring-1 focus:ring-blue-500/20"
                        >
                            <option value="">
                                All Teams
                            </option>

                            <option
                                v-for="team in uniqueTeams"
                                :key="team"
                                :value="team"
                            >
                                {{ team }}
                            </option>
                        </select>

                        <i
                            class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-gray-700"
                        ></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="p-4 sm:p-6">
            <div
                class="w-full overflow-hidden rounded-xl border border-white/[0.05] bg-[#080808] p-3 sm:p-5"
            >
                <div
                    class="relative h-[350px] w-full sm:h-[430px] lg:h-[500px]"
                >
                    <canvas
                        ref="seasonProgressionChart"
                    ></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Loading -->
    <section
        v-else-if="loading"
        class="mb-8 w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-xl"
    >
        <div
            class="border-b border-white/[0.07] bg-[#0b0b0b] px-4 py-4 sm:px-5"
        >
            <div class="flex items-center gap-3">
                <div
                    class="h-10 w-10 animate-pulse rounded-xl bg-white/[0.05]"
                ></div>

                <div class="space-y-2">
                    <div
                        class="h-2 w-24 animate-pulse rounded bg-white/[0.06]"
                    ></div>

                    <div
                        class="h-4 w-48 animate-pulse rounded bg-white/[0.08]"
                    ></div>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div
                class="flex h-[350px] items-center justify-center rounded-xl border border-white/[0.05] bg-white/[0.02] sm:h-[430px] lg:h-[500px]"
            >
                <div class="flex flex-col items-center gap-4">
                    <div
                        class="h-10 w-10 animate-spin rounded-full border-2 border-white/[0.08] border-t-blue-500"
                    ></div>

                    <p
                        class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600"
                    >
                        Loading Season Progression
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Empty -->
    <section
        v-else
        class="mb-8 flex min-h-[300px] w-full min-w-0 items-center justify-center overflow-hidden rounded-2xl border border-white/[0.07] bg-black px-6 text-white shadow-xl"
    >
        <div class="text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.03]"
            >
                <i
                    class="fas fa-chart-line text-xl text-gray-700"
                ></i>
            </div>

            <div
                class="text-[8px] font-black uppercase tracking-[0.25em] text-gray-700"
            >
                League Analytics
            </div>

            <h3 class="mt-1 text-sm font-black text-gray-500">
                No Progression Data
            </h3>

            <p
                class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-gray-700"
            >
                Season progression data is not currently available.
            </p>
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
import axios from "axios";

const props = defineProps({
    isConference: {
        type: Number,
        default: 0,
    },

    teamId: {
        type: Number,
        default: 0,
    },
});

const standings = ref({
    labels: [],
    datasets: [],
});

const selectedTeam = ref("");
const loading = ref(false);

const seasonProgressionChart = ref(null);

let seasonChartInstance = null;

/*
|--------------------------------------------------------------------------
| Unique Teams
|--------------------------------------------------------------------------
*/
const uniqueTeams = computed(() => {
    const teams =
        standings.value?.datasets?.map(
            (dataset) => dataset.label
        ) || [];

    return [...new Set(teams)];
});

/*
|--------------------------------------------------------------------------
| Fetch Standings
|--------------------------------------------------------------------------
*/
const fetchAllStandings = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("analytics.standings", {
                conference_id: props.isConference,
                team_id: props.teamId,
            })
        );

        standings.value = {
            labels: Array.isArray(response.data?.labels)
                ? response.data.labels
                : [],

            datasets: Array.isArray(
                response.data?.datasets
            )
                ? response.data.datasets
                : [],
        };
    } catch (error) {
        console.error(
            "Error fetching season progression:",
            error
        );

        standings.value = {
            labels: [],
            datasets: [],
        };
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Dataset Styling
|--------------------------------------------------------------------------
*/
const getDatasetColor = (dataset, index) => {
    /*
     * Preserve the color returned by the API if available.
     */
    if (dataset.borderColor) {
        return dataset.borderColor;
    }

    if (dataset.backgroundColor) {
        return dataset.backgroundColor;
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
| Render Chart
|--------------------------------------------------------------------------
*/
const renderSeasonProgressionChart = async () => {
    await nextTick();

    if (!seasonProgressionChart.value) {
        return;
    }

    if (seasonChartInstance) {
        seasonChartInstance.destroy();
        seasonChartInstance = null;
    }

    /*
     * Filter by team when selected.
     */
    const filteredDatasets = selectedTeam.value
        ? standings.value.datasets.filter(
              (dataset) =>
                  dataset.label === selectedTeam.value
          )
        : standings.value.datasets;

    /*
     * Rebuild the datasets so the chart has consistent
     * dark-theme styling while preserving the original
     * data values.
     */
    const datasets = filteredDatasets.map(
        (dataset, index) => {
            const color = getDatasetColor(
                dataset,
                index
            );

            return {
                ...dataset,

                borderColor: color,

                backgroundColor: color,

                borderWidth:
                    selectedTeam.value ? 3 : 2,

                pointBackgroundColor: color,

                pointBorderColor: "#080808",

                pointBorderWidth: 2,

                pointRadius:
                    selectedTeam.value ? 4 : 3,

                pointHoverRadius: 6,

                tension: 0.35,

                fill: false,
            };
        }
    );

    seasonChartInstance = new Chart(
        seasonProgressionChart.value,
        {
            type: "line",

            data: {
                labels:
                    standings.value.labels || [],

                datasets,
            },

            options: {
                responsive: true,

                maintainAspectRatio: false,

                animation: {
                    duration: 500,
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
                        display:
                            datasets.length > 0,

                        position: "bottom",

                        labels: {
                            color: "#9ca3af",

                            padding: 16,

                            boxWidth: 12,

                            boxHeight: 3,

                            usePointStyle: true,

                            pointStyle: "line",

                            font: {
                                size: 10,

                                weight: "600",
                            },
                        },
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
                            label: (context) => {
                                const value =
                                    context.parsed.y ?? 0;

                                return ` ${
                                    context.dataset.label
                                }: ${value} Wins`;
                            },
                        },
                    },
                },

                scales: {
                    x: {
                        grid: {
                            display: false,
                        },

                        border: {
                            color:
                                "rgba(255,255,255,0.06)",
                        },

                        title: {
                            display: true,

                            text: "Seasons",

                            color: "#6b7280",

                            font: {
                                size: 10,

                                weight: "700",
                            },
                        },

                        ticks: {
                            color: "#9ca3af",

                            autoSkip: true,

                            maxTicksLimit: 15,

                            maxRotation: 45,

                            minRotation: 0,

                            font: {
                                size: 9,

                                weight: "600",
                            },
                        },
                    },

                    y: {
                        beginAtZero: true,

                        grid: {
                            color:
                                "rgba(255,255,255,0.05)",
                        },

                        border: {
                            display: false,
                        },

                        title: {
                            display: true,

                            text: "Wins",

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
    await fetchAllStandings();

    if (
        standings.value?.datasets?.length
    ) {
        await renderSeasonProgressionChart();
    }
};

/*
|--------------------------------------------------------------------------
| Mount
|--------------------------------------------------------------------------
*/
onMounted(() => {
    showChart();
});

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/
onBeforeUnmount(() => {
    if (seasonChartInstance) {
        seasonChartInstance.destroy();
        seasonChartInstance = null;
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