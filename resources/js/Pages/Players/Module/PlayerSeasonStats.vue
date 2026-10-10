<template>
    <div class="w-full space-y-6">

        <!-- =========================================================
             REGULAR SEASON LOGS
        ========================================================== -->
        <section
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0b0d10] shadow-[0_20px_60px_rgba(0,0,0,0.35)]"
        >
            <!-- Header -->
            <div class="border-b border-white/[0.07] bg-gradient-to-r from-white/[0.035] to-transparent">
                <div class="flex items-center justify-between gap-4 px-5 py-4">

                    <div class="flex min-w-0 items-center gap-3">
                        <!-- Icon -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/[0.08] bg-white/[0.04]"
                        >
                            <svg
                                class="h-5 w-5 text-white/70"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                stroke-width="1.7"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 19V5m0 14h16M7 15l3-4 3 2 5-7"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="truncate text-sm font-bold tracking-wide text-white sm:text-base">
                                    Regular Season Logs
                                </h2>

                                <span
                                    v-if="seasonCount"
                                    class="shrink-0 rounded-full border border-white/[0.08] bg-white/[0.045] px-2 py-0.5 text-[9px] font-bold tracking-wider text-white/45"
                                >
                                    {{ seasonCount }}
                                </span>
                            </div>

                            <p class="mt-0.5 hidden text-[11px] text-white/30 sm:block">
                                Player performance by season
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="seasonCount"
                        class="hidden shrink-0 items-center gap-2 text-[10px] text-white/30 md:flex"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400/60"></span>
                        Click a season to view game logs
                    </div>
                </div>
            </div>

            <!-- Table wrapper -->
            <div class="overflow-x-auto">
                <SeasonPerformanceTable
                    v-if="season_logs.player_stats"
                    :playerStats="season_logs.player_stats"
                    :key="`season-${props.player_id}`"
                    :isPlayOff="false"
                />
            </div>
        </section>


        <!-- =========================================================
             PLAYOFF LOGS
        ========================================================== -->
        <section
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0b0d10] shadow-[0_20px_60px_rgba(0,0,0,0.35)]"
        >
            <!-- Header -->
            <div class="border-b border-white/[0.07] bg-gradient-to-r from-amber-300/[0.035] to-transparent">
                <div class="flex items-center justify-between gap-4 px-5 py-4">

                    <div class="flex min-w-0 items-center gap-3">

                        <!-- Icon -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-300/[0.10] bg-amber-300/[0.04]"
                        >
                            <svg
                                class="h-5 w-5 text-amber-200/70"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                stroke-width="1.7"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="truncate text-sm font-bold tracking-wide text-white sm:text-base">
                                    Playoff Logs
                                </h2>

                                <span
                                    v-if="playoffCount"
                                    class="shrink-0 rounded-full border border-amber-300/[0.10] bg-amber-300/[0.04] px-2 py-0.5 text-[9px] font-bold tracking-wider text-amber-100/50"
                                >
                                    {{ playoffCount }}
                                </span>
                            </div>

                            <p class="mt-0.5 hidden text-[11px] text-white/30 sm:block">
                                Player postseason performance history
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="playoffCount"
                        class="hidden shrink-0 items-center gap-2 text-[10px] text-white/30 md:flex"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-300/60"></span>
                        Click a season to view game logs
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto" v-if="playoff_logs.player_stats">
                <SeasonPerformanceTable
                    v-if="playoff_logs.player_stats"
                    :playerStats="playoff_logs.player_stats"
                    :key="`playoff-${props.player_id}`"
                    :isPlayOff="true"
                />
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";

import SeasonPerformanceTable from "./SeasonPerformanceTable.vue";
import Modal from "@/Components/Modal.vue";
import { roleBadgeClass } from "@/Utility/Formatter";

const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});

const season_logs = ref({
    player_stats: [],
});

const playoff_logs = ref({
    player_stats: [],
});

const season_stats_loading = ref(false);
const playoff_loading = ref(false);


/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const seasonCount = computed(() => {
    return season_logs.value?.player_stats?.length || 0;
});

const playoffCount = computed(() => {
    return playoff_logs.value?.player_stats?.length || 0;
});


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

function formatStat(value) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "—";
    }

    return number.toFixed(2);
}

function formatRating(value) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "—";
    }

    return number.toFixed(2);
}


/*
|--------------------------------------------------------------------------
| Team Colors
|--------------------------------------------------------------------------
*/

function teamAccentStyle(player) {
    const primary = String(player.team_primary_colors || "")
        .split(",")
        .map(color => color.trim())
        .filter(Boolean);

    const secondary = String(player.team_secondary_colors || "")
        .split(",")
        .map(color => color.trim())
        .filter(Boolean);

    const primaryColor = primary[0];
    const secondaryColor = secondary[0];

    if (!primaryColor && !secondaryColor) {
        return {
            background: "rgba(255,255,255,0.14)",
        };
    }

    const first = primaryColor
        ? `#${primaryColor.replace("#", "")}`
        : `#${secondaryColor.replace("#", "")}`;

    const second = secondaryColor
        ? `#${secondaryColor.replace("#", "")}`
        : first;

    return {
        background: `linear-gradient(to bottom, ${first}, ${second})`,
    };
}


/*

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

async function fetchPlayerSeasonPerformance() {
    season_stats_loading.value = true;

    try {
        const response = await axios.post(
            route("players.season.performance"),
            {
                player_id: props.player_id,
            }
        );

        season_logs.value = response.data || {
            player_stats: [],
        };
    } catch (error) {
        console.error(
            "Failed to fetch player season performance:",
            error
        );

        season_logs.value = {
            player_stats: [],
        };
    } finally {
        season_stats_loading.value = false;
    }
}

async function fetchPlayerPlayoffPerformance() {
    playoff_loading.value = true;

    try {
        const response = await axios.post(
            route("players.playoff.performance"),
            {
                player_id: props.player_id,
            }
        );

        playoff_logs.value = response.data || {
            player_stats: [],
        };
    } catch (error) {
        console.error(
            "Failed to fetch player playoff performance:",
            error
        );

        playoff_logs.value = {
            player_stats: [],
        };
    } finally {
        playoff_loading.value = false;
    }
}

async function fetchAllPerformance() {
    await Promise.all([
        fetchPlayerSeasonPerformance(),
        fetchPlayerPlayoffPerformance(),
    ]);
}


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchAllPerformance();
});

watch(
    () => props.player_id,
    (newPlayerId, oldPlayerId) => {
        if (
            newPlayerId &&
            newPlayerId !== oldPlayerId
        ) {
            isGameLogsModalOpen.value = false;

            fetchAllPerformance();
        }
    }
);
</script>

<style scoped>
.stat-heading {
    display: inline-block;
    color: rgba(255, 255, 255, 0.28);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    white-space: nowrap;
}

.stat-th {
    width: 78px;
    padding: 12px;
    text-align: right;
}

.stat-td {
    width: 78px;
    padding: 12px;
    text-align: right;
    color: rgba(255, 255, 255, 0.48);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* Horizontal scrollbar */
.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 999px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>