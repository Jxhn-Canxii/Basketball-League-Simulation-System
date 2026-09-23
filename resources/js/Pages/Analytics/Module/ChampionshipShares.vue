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
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-yellow-500/20 bg-yellow-500/[0.06]"
                    >
                        <i
                            class="fas fa-trophy text-sm text-yellow-500"
                        ></i>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-yellow-500/70"
                        >
                            League Records
                        </div>

                        <h2
                            class="truncate text-base font-black tracking-tight text-white sm:text-lg"
                        >
                            Championships by Team
                        </h2>
                    </div>
                </div>

                <!-- Total Championships -->
                <div
                    v-if="!loading && champions.length"
                    class="hidden shrink-0 rounded-lg border border-white/[0.07] bg-white/[0.03] px-3 py-2 text-right sm:block"
                >
                    <div
                        class="text-[8px] font-black uppercase tracking-wider text-gray-600"
                    >
                        Total Titles
                    </div>

                    <div class="mt-0.5 text-sm font-black text-yellow-500">
                        {{ totalChampionships }}
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
                        class="h-12 w-12 animate-spin rounded-full border-2 border-white/[0.08] border-t-yellow-500"
                    ></div>

                    <div
                        class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600"
                    >
                        Loading Championship Records
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="!champions.length"
            class="flex min-h-[320px] flex-col items-center justify-center px-6 py-10 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.03]"
            >
                <i class="fas fa-trophy text-xl text-gray-700"></i>
            </div>

            <div
                class="text-[8px] font-black uppercase tracking-[0.25em] text-gray-700"
            >
                League Records
            </div>

            <h3 class="mt-1 text-sm font-black text-gray-500">
                No Championship Records
            </h3>

            <p
                class="mt-2 max-w-sm text-xs leading-relaxed text-gray-700"
            >
                Championship statistics will appear here once
                league records have been recorded.
            </p>
        </div>

        <!-- Chart -->
        <div
            v-else
            class="p-4 sm:p-6"
        >
            <!-- Chart container controls the chart size -->
            <div
                class="relative mx-auto w-full max-w-5xl rounded-xl border border-white/[0.05] bg-[#080808] p-3 sm:p-5"
            >
                <div
                    class="relative h-[320px] w-full sm:h-[400px] lg:h-[460px]"
                >
                    <canvas
                        ref="championsChart"
                    ></canvas>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import Chart from "chart.js/auto";

const championsChart = ref(null);
const champions = ref([]);
const loading = ref(true);

let chartInstance = null;

const search_champions = ref({
    page_num: 1,
    total_pages: 0,
    per_page: 80,
    total: 0,
    search: "",
});

/*
|--------------------------------------------------------------------------
| Total Championships
|--------------------------------------------------------------------------
*/
const totalChampionships = computed(() => {
    return champions.value.reduce((total, team) => {
        return total + (Number(team.championships) || 0);
    }, 0);
});

/*
|--------------------------------------------------------------------------
| Fetch Championship Records
|--------------------------------------------------------------------------
*/
const fetchChampions = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("records.champions"),
            search_champions.value
        );

        champions.value = Array.isArray(response.data?.data)
            ? response.data.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching champions:",
            error
        );

        champions.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Convert Team Color
|--------------------------------------------------------------------------
*/
const getTeamColor = (color, index) => {
    if (color) {
        return `#${String(color).replace("#", "")}`;
    }

    /*
     * Fallback colors are generated from a neutral palette.
     * This prevents invalid Chart.js colors when a team has no
     * primary_color value.
     */
    const fallbackColors = [
        "#f59e0b",
        "#3b82f6",
        "#10b981",
        "#ef4444",
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
const renderChart = async () => {
    await nextTick();

    if (!championsChart.value) {
        return;
    }

    const labels = champions.value.map(
        (team) => team.name ?? "Unknown Team"
    );

    const data = champions.value.map(
        (team) => Number(team.championships) || 0
    );

    const backgroundColors = champions.value.map(
        (team, index) =>
            getTeamColor(team.primary_color, index)
    );

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    chartInstance = new Chart(
        championsChart.value,
        {
            type: "pie",

            data: {
                labels,

                datasets: [
                    {
                        label: "Championships Won",

                        data,

                        backgroundColor:
                            backgroundColors,

                        borderColor: "#080808",

                        borderWidth: 3,

                        hoverBorderColor: "#ffffff",

                        hoverBorderWidth: 2,

                        spacing: 2,
                    },
                ],
            },

            options: {
                responsive: true,

                maintainAspectRatio: false,

                animation: {
                    duration: 700,
                },

                interaction: {
                    mode: "nearest",
                },

                plugins: {
                    title: {
                        display: false,
                    },

                    legend: {
                        position: "bottom",

                        labels: {
                            color: "#9ca3af",

                            padding: 16,

                            boxWidth: 12,

                            boxHeight: 12,

                            usePointStyle: true,

                            pointStyle: "circle",

                            font: {
                                size: 11,

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
                                    context.raw ?? 0;

                                return ` ${value} ${
                                    Number(value) === 1
                                        ? "Championship"
                                        : "Championships"
                                }`;
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
    await fetchChampions();

    if (champions.value.length) {
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