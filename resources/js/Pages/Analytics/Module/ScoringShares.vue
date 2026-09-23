<template>
    <div
        class="overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117] shadow-xl"
    >
        <!-- Accent -->
        <div
            class="h-[2px] bg-gradient-to-r from-slate-700 via-slate-500 to-slate-800"
        ></div>

        <!-- Header -->
        <div
            class="flex flex-col gap-3 border-b border-slate-800 bg-[#0a0d12] px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-[#090c11]"
                >
                    <i class="fas fa-chart-bar text-xs text-slate-400"></i>
                </div>

                <div>
                    <h2
                        class="text-sm font-bold tracking-tight text-white sm:text-base"
                    >
                        Scoring by Team
                    </h2>

                    <p
                        class="mt-0.5 text-[10px] uppercase tracking-[0.15em] text-slate-600"
                    >
                        League scoring distribution
                    </p>
                </div>
            </div>

            <!-- Total -->
            <div
                v-if="totalScore > 0"
                class="flex items-center gap-2 self-start rounded-md border border-slate-800 bg-[#090c11] px-3 py-2 sm:self-auto"
            >
                <i
                    class="fas fa-basketball-ball text-[10px] text-slate-600"
                ></i>

                <div>
                    <div
                        class="text-[9px] font-bold uppercase tracking-wider text-slate-600"
                    >
                        Total Points
                    </div>

                    <div class="text-xs font-bold text-slate-300">
                        {{ formatNumber(totalScore) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Area -->
        <div class="relative p-4 sm:p-5">
            <!-- Loading -->
            <div
                v-if="loading"
                class="flex min-h-[360px] items-center justify-center"
            >
                <div class="text-center">
                    <div
                        class="mx-auto mb-3 h-7 w-7 animate-spin rounded-full border-2 border-slate-800 border-t-slate-400"
                    ></div>

                    <p class="text-xs font-semibold text-slate-500">
                        Loading scoring data...
                    </p>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-else-if="!top_scorers.length"
                class="flex min-h-[360px] flex-col items-center justify-center text-center"
            >
                <div
                    class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg border border-slate-800 bg-[#090c11]"
                >
                    <i
                        class="fas fa-chart-bar text-slate-700"
                    ></i>
                </div>

                <h3 class="text-sm font-bold text-slate-400">
                    No scoring data
                </h3>

                <p class="mt-1 text-xs text-slate-600">
                    Team scoring information is not currently available.
                </p>
            </div>

            <!-- Chart -->
            <div
                v-else
                class="relative h-[420px] w-full sm:h-[500px] lg:h-[560px]"
            >
                <canvas ref="scoringChart"></canvas>
            </div>
        </div>

        <!-- Footer -->
        <div
            v-if="top_scorers.length"
            class="flex flex-col gap-2 border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
        >
            <div class="flex items-center gap-2">
                <span
                    class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                ></span>

                <span
                    class="text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-600"
                >
                    {{ top_scorers.length }} teams tracked
                </span>
            </div>

            <div
                class="text-[9px] uppercase tracking-wider text-slate-700"
            >
                Scoring Records
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";
import Chart from "chart.js/auto";

const scoringChart = ref(null);
const chartInstance = ref(null);

const top_scorers = ref([]);
const loading = ref(false);

const search_topscorers = ref({
    page_num: 1,
    total_pages: 0,
    per_page: 80,
    total: 0,
    search: "",
});

const totalScore = computed(() => {
    return top_scorers.value.reduce((total, team) => {
        return total + Number(team?.total_score ?? 0);
    }, 0);
});

const formatNumber = (value) => {
    return Number(value ?? 0).toLocaleString();
};

const hexToRgba = (hex, alpha = 0.85) => {
    if (!hex) {
        return `rgba(100, 116, 139, ${alpha})`;
    }

    let clean = String(hex).replace("#", "").trim();

    if (clean.length === 3) {
        clean = clean
            .split("")
            .map((char) => char + char)
            .join("");
    }

    if (clean.length !== 6) {
        return `rgba(100, 116, 139, ${alpha})`;
    }

    const r = parseInt(clean.substring(0, 2), 16);
    const g = parseInt(clean.substring(2, 4), 16);
    const b = parseInt(clean.substring(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const getTeamColor = (team) => {
    return team?.primary_color
        ? `#${String(team.primary_color).replace("#", "")}`
        : "#64748b";
};

const fetchTopScorers = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("records.team.topscorer"),
            search_topscorers.value
        );

        top_scorers.value = Array.isArray(response?.data?.data)
            ? response.data.data
            : [];
    } catch (error) {
        console.error("Error fetching team scoring data:", error);
        top_scorers.value = [];
    } finally {
        loading.value = false;
    }
};

const destroyChart = () => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
};

const renderChart = async () => {
    await nextTick();

    if (!scoringChart.value || !top_scorers.value.length) {
        return;
    }

    destroyChart();

    const labels = top_scorers.value.map(
        (team) => team?.name ?? "Unknown Team"
    );

    const data = top_scorers.value.map((team) =>
        Number(team?.total_score ?? 0)
    );

    const backgroundColors = top_scorers.value.map((team) =>
        hexToRgba(getTeamColor(team), 0.78)
    );

    const borderColors = top_scorers.value.map((team) =>
        hexToRgba(getTeamColor(team), 1)
    );

    const ctx = scoringChart.value.getContext("2d");

    chartInstance.value = new Chart(ctx, {
        type: "bar",

        data: {
            labels,

            datasets: [
                {
                    label: "Points",
                    data,

                    backgroundColor: backgroundColors,
                    borderColor: borderColors,

                    borderWidth: 1,

                    borderRadius: 4,
                    borderSkipped: false,

                    barPercentage: 0.72,
                    categoryPercentage: 0.82,
                },
            ],
        },

        options: {
            indexAxis: "y",

            responsive: true,
            maintainAspectRatio: false,

            animation: {
                duration: 600,
            },

            interaction: {
                mode: "nearest",
                intersect: false,
            },

            plugins: {
                legend: {
                    display: false,
                },

                tooltip: {
                    backgroundColor: "#090c11",
                    titleColor: "#e2e8f0",
                    bodyColor: "#94a3b8",
                    borderColor: "#1e293b",
                    borderWidth: 1,

                    padding: 12,

                    displayColors: true,

                    callbacks: {
                        label: (context) => {
                            return `  ${formatNumber(
                                context.raw
                            )} points`;
                        },
                    },
                },
            },

            scales: {
                x: {
                    beginAtZero: true,

                    grid: {
                        color: "rgba(51, 65, 85, 0.25)",
                        drawBorder: false,
                    },

                    border: {
                        display: false,
                    },

                    ticks: {
                        color: "#475569",
                        font: {
                            size: 10,
                        },

                        padding: 8,

                        callback: (value) =>
                            Number(value).toLocaleString(),
                    },
                },

                y: {
                    grid: {
                        display: false,
                        drawBorder: false,
                    },

                    border: {
                        display: false,
                    },

                    ticks: {
                        color: "#94a3b8",

                        font: {
                            size: 10,
                            weight: "600",
                        },

                        padding: 8,

                        autoSkip: false,
                    },
                },
            },
        },
    });
};

const showChart = async () => {
    await fetchTopScorers();
    await renderChart();
};

onMounted(() => {
    showChart();
});

onBeforeUnmount(() => {
    destroyChart();
});
</script>

<style scoped>
canvas {
    max-width: 100%;
}
</style>