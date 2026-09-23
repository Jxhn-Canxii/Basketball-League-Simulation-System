<template>
    <div
        ref="chartContainer"
        class="relative flex min-w-0 w-full items-center justify-center overflow-hidden rounded-xl border border-white/[0.06] bg-[#080808] p-3 sm:p-4"
    >
        <!-- Chart -->
        <div class="relative h-[260px] w-full min-w-0 sm:h-[300px]">
            <canvas ref="chartCanvas"></canvas>
        </div>

        <!-- Center Summary -->
        <div
            class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center"
        >
            <div
                class="text-[9px] font-black uppercase tracking-[0.15em] text-gray-600"
            >
                Career
            </div>

            <div class="mt-1 text-xl font-black text-white">
                {{ totalGames }}
            </div>

            <div
                class="text-[8px] font-bold uppercase tracking-widest text-gray-600"
            >
                Games
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import Chart from "chart.js/auto";

const props = defineProps({
    coachDetails: {
        type: Object,
        required: true,
    },
});

const chartCanvas = ref(null);
const chartContainer = ref(null);

let chartInstance = null;
let resizeObserver = null;

const wins = computed(() => {
    return Number(props.coachDetails?.career_wins ?? 0);
});

const losses = computed(() => {
    return Number(props.coachDetails?.career_losses ?? 0);
});

const totalGames = computed(() => {
    return wins.value + losses.value;
});

const winningPercentage = computed(() => {
    if (!totalGames.value) return 0;

    return ((wins.value / totalGames.value) * 100).toFixed(1);
});

const renderChart = async () => {
    await nextTick();

    if (!chartCanvas.value) return;

    const ctx = chartCanvas.value.getContext("2d");

    if (!ctx) return;

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    /*
     * If the coach has no recorded games, Chart.js pie charts
     * don't have anything useful to display.
     */
    const hasData = totalGames.value > 0;

    chartInstance = new Chart(ctx, {
        type: "doughnut",

        data: {
            labels: ["Wins", "Losses"],

            datasets: [
                {
                    data: hasData
                        ? [wins.value, losses.value]
                        : [1],

                    backgroundColor: hasData
                        ? [
                              "rgba(16, 185, 129, 0.85)",
                              "rgba(239, 68, 68, 0.75)",
                          ]
                        : ["rgba(255, 255, 255, 0.06)"],

                    borderColor: "#080808",

                    borderWidth: 3,

                    hoverOffset: 5,
                },
            ],
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            cutout: "68%",

            animation: {
                duration: 500,
            },

            layout: {
                padding: 8,
            },

            plugins: {
                title: {
                    display: false,
                },

                legend: {
                    display: true,
                    position: "bottom",

                    labels: {
                        color: "#9ca3af",

                        padding: 16,

                        boxWidth: 9,
                        boxHeight: 9,

                        usePointStyle: true,
                        pointStyle: "circle",

                        font: {
                            size: 10,
                            weight: "700",
                        },

                        generateLabels(chart) {
                            const data = chart.data;

                            if (!data.labels?.length) {
                                return [];
                            }

                            return data.labels.map((label, index) => {
                                const value =
                                    data.datasets[0].data[index] ?? 0;

                                return {
                                    text: `${label} ${value}`,
                                    fillStyle:
                                        data.datasets[0].backgroundColor[
                                            index
                                        ],
                                    strokeStyle: "#080808",
                                    lineWidth: 0,

                                    hidden:
                                        chart.getDataVisibility(index) ===
                                        false,

                                    index,
                                };
                            });
                        },
                    },
                },

                tooltip: {
                    backgroundColor: "#111111",

                    borderColor: "rgba(255,255,255,0.08)",
                    borderWidth: 1,

                    titleColor: "#ffffff",
                    bodyColor: "#9ca3af",

                    padding: 10,

                    displayColors: true,

                    callbacks: {
                        label(context) {
                            const value = Number(context.raw ?? 0);

                            const percentage =
                                totalGames.value > 0
                                    ? (
                                          (value / totalGames.value) *
                                          100
                                      ).toFixed(1)
                                    : 0;

                            return ` ${value} (${percentage}%)`;
                        },
                    },
                },
            },
        },
    });
};

const handleResize = () => {
    if (chartInstance) {
        chartInstance.resize();
    }
};

watch(
    () => [
        props.coachDetails?.career_wins,
        props.coachDetails?.career_losses,
    ],
    () => {
        renderChart();
    }
);

onMounted(async () => {
    await renderChart();

    if (chartContainer.value && "ResizeObserver" in window) {
        resizeObserver = new ResizeObserver(() => {
            handleResize();
        });

        resizeObserver.observe(chartContainer.value);
    } else {
        window.addEventListener("resize", handleResize);
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    } else {
        window.removeEventListener("resize", handleResize);
    }

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