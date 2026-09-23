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
                <table class="w-full min-w-[1250px] border-collapse text-xs">

                    <!-- Header -->
                    <thead>
                        <tr class="border-b border-white/[0.07] bg-[#080a0c]">

                            <th
                                class="sticky left-0 z-30 w-[105px] bg-[#080a0c] px-4 py-3 text-left"
                            >
                                <span class="stat-heading">
                                    Season
                                </span>
                            </th>

                            <th
                                class="sticky left-[105px] z-30 w-[200px] bg-[#080a0c] px-4 py-3 text-left"
                            >
                                <span class="stat-heading">
                                    Team
                                </span>
                            </th>

                            <th class="w-[125px] px-3 py-3 text-left">
                                <span class="stat-heading">
                                    Role
                                </span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">GP</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">PPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">RPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">APG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">SPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">BPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">TOPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">FPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">Rating</span>
                            </th>

                            <th class="w-[130px] px-4 py-3 text-right">
                                <span class="stat-heading">
                                    Value
                                </span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Data -->
                        <template
                            v-if="seasonCount > 0 && !season_stats_loading"
                        >
                            <tr
                                v-for="(player, index) in season_logs.player_stats"
                                :key="`season-${player.player_id}-${player.season_id}-${index}`"
                                @click="openGameLogs(player.season_id)"
                                class="group cursor-pointer border-b border-white/[0.045] transition-colors duration-150 hover:bg-white/[0.025]"
                            >

                                <!-- Season -->
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-white/30 transition-all group-hover:bg-white/70"
                                        ></span>

                                        <span
                                            class="whitespace-nowrap font-semibold text-white/80"
                                        >
                                            {{ player.season_name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Team -->
                                <td
                                    class="sticky left-[105px] z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex min-w-0 items-center gap-3">

                                        <!-- Team color -->
                                        <span
                                            class="h-8 w-1 shrink-0 rounded-full"
                                            :style="teamAccentStyle(player)"
                                        ></span>

                                        <span
                                            class="truncate font-medium text-white/65 transition-colors group-hover:text-white/90"
                                            :title="player.team_names"
                                        >
                                            {{ player.team_names || "Free Agent" }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="px-3 py-3">
                                    <span
                                        :class="roleBadgeClass(player.player_role)"
                                        class="inline-flex whitespace-nowrap rounded-md px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                    >
                                        {{ player.player_role || "N/A" }}
                                    </span>
                                </td>

                                <!-- GP -->
                                <td class="stat-td">
                                    <span class="font-semibold text-white/75">
                                        {{ player.total_games_played ?? 0 }}
                                    </span>
                                </td>

                                <!-- PPG -->
                                <td class="stat-td">
                                    <span class="font-bold text-white">
                                        {{ formatStat(player.average_points_per_game) }}
                                    </span>
                                </td>

                                <!-- RPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_rebounds_per_game) }}
                                </td>

                                <!-- APG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_assists_per_game) }}
                                </td>

                                <!-- SPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_steals_per_game) }}
                                </td>

                                <!-- BPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_blocks_per_game) }}
                                </td>

                                <!-- TOPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_turnovers_per_game) }}
                                </td>

                                <!-- FPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_fouls_per_game) }}
                                </td>

                                <!-- Rating -->
                                <td class="px-3 py-3 text-right">
                                    <span
                                        class="inline-flex min-w-[58px] items-center justify-center rounded-lg border border-white/[0.08] bg-white/[0.045] px-2.5 py-1.5 font-bold tabular-nums text-white/90"
                                    >
                                        {{ formatRating(player.overall_rating) }}
                                    </span>
                                </td>

                                <!-- Value -->
                                <td class="px-4 py-3 text-right">
                                    <span class="font-semibold tabular-nums text-white/50">
                                        {{ player.player_valuation ?? 0 }}
                                    </span>
                                </td>

                            </tr>
                        </template>

                        <!-- Loading -->
                        <tr v-else-if="season_stats_loading">
                            <td colspan="13" class="px-5 py-8">
                                <div class="space-y-2">
                                    <div
                                        v-for="i in 5"
                                        :key="i"
                                        class="h-11 animate-pulse rounded-lg bg-white/[0.035]"
                                    ></div>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty -->
                        <tr v-else>
                            <td colspan="13" class="px-5 py-12">
                                <div class="flex flex-col items-center justify-center text-center">

                                    <div
                                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.025]"
                                    >
                                        <svg
                                            class="h-5 w-5 text-white/20"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M4 19V5m0 14h16M7 15l3-4 3 2 5-7"
                                            />
                                        </svg>
                                    </div>

                                    <p class="text-sm font-semibold text-white/45">
                                        No regular season logs
                                    </p>

                                    <p class="mt-1 text-[11px] text-white/25">
                                        No regular-season performance data is available.
                                    </p>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
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
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1150px] border-collapse text-xs">

                    <thead>
                        <tr class="border-b border-white/[0.07] bg-[#080a0c]">

                            <th
                                class="sticky left-0 z-30 w-[105px] bg-[#080a0c] px-4 py-3 text-left"
                            >
                                <span class="stat-heading">
                                    Season
                                </span>
                            </th>

                            <th
                                class="sticky left-[105px] z-30 w-[200px] bg-[#080a0c] px-4 py-3 text-left"
                            >
                                <span class="stat-heading">
                                    Team
                                </span>
                            </th>

                            <th class="w-[125px] px-3 py-3 text-left">
                                <span class="stat-heading">
                                    Role
                                </span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">GP</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">PPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">RPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">APG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">SPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">BPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">TOPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">FPG</span>
                            </th>

                            <th class="stat-th">
                                <span class="stat-heading">Rating</span>
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        <!-- Data -->
                        <template
                            v-if="playoffCount > 0 && !playoff_loading"
                        >
                            <tr
                                v-for="(player, index) in playoff_logs.player_stats"
                                :key="`playoff-${player.player_id}-${player.season_id}-${index}`"
                                @click="openGameLogs(player.season_id)"
                                class="group cursor-pointer border-b border-white/[0.045] transition-colors duration-150 hover:bg-white/[0.025]"
                            >

                                <!-- Season -->
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-300/50 transition-all group-hover:bg-amber-200"
                                        ></span>

                                        <span
                                            class="whitespace-nowrap font-semibold text-white/80"
                                        >
                                            {{ player.season_name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Team -->
                                <td
                                    class="sticky left-[105px] z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex min-w-0 items-center gap-3">

                                        <span
                                            class="h-8 w-1 shrink-0 rounded-full"
                                            :style="teamAccentStyle(player)"
                                        ></span>

                                        <span
                                            class="truncate font-medium text-white/65 transition-colors group-hover:text-white/90"
                                            :title="player.team_names"
                                        >
                                            {{ player.team_names || "Free Agent" }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="px-3 py-3">
                                    <span
                                        :class="roleBadgeClass(player.player_role)"
                                        class="inline-flex whitespace-nowrap rounded-md px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                    >
                                        {{ player.player_role || "N/A" }}
                                    </span>
                                </td>

                                <!-- GP -->
                                <td class="stat-td">
                                    <span class="font-semibold text-white/75">
                                        {{ player.total_games_played ?? 0 }}
                                    </span>
                                </td>

                                <!-- PPG -->
                                <td class="stat-td">
                                    <span class="font-bold text-white">
                                        {{ formatStat(player.average_points_per_game) }}
                                    </span>
                                </td>

                                <!-- RPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_rebounds_per_game) }}
                                </td>

                                <!-- APG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_assists_per_game) }}
                                </td>

                                <!-- SPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_steals_per_game) }}
                                </td>

                                <!-- BPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_blocks_per_game) }}
                                </td>

                                <!-- TOPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_turnovers_per_game) }}
                                </td>

                                <!-- FPG -->
                                <td class="stat-td">
                                    {{ formatStat(player.average_fouls_per_game) }}
                                </td>

                                <!-- Rating -->
                                <td class="px-3 py-3 text-right">
                                    <span
                                        class="inline-flex min-w-[58px] items-center justify-center rounded-lg border border-amber-300/[0.10] bg-amber-300/[0.04] px-2.5 py-1.5 font-bold tabular-nums text-amber-100/80"
                                    >
                                        {{ formatRating(player.overall_rating) }}
                                    </span>
                                </td>

                            </tr>
                        </template>

                        <!-- Loading -->
                        <tr v-else-if="playoff_loading">
                            <td colspan="12" class="px-5 py-8">
                                <div class="space-y-2">
                                    <div
                                        v-for="i in 4"
                                        :key="i"
                                        class="h-11 animate-pulse rounded-lg bg-white/[0.035]"
                                    ></div>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty -->
                        <tr v-else>
                            <td colspan="12" class="px-5 py-12">
                                <div class="flex flex-col items-center justify-center text-center">

                                    <div
                                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-amber-300/[0.07] bg-amber-300/[0.025]"
                                    >
                                        <svg
                                            class="h-5 w-5 text-amber-200/20"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"
                                            />
                                        </svg>
                                    </div>

                                    <p class="text-sm font-semibold text-white/45">
                                        No playoff logs
                                    </p>

                                    <p class="mt-1 text-[11px] text-white/25">
                                        No postseason performance data is available.
                                    </p>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </section>


        <!-- =========================================================
             GAME LOG MODAL
        ========================================================== -->
        <Modal
            :show="!!isGameLogsModalOpen"
            :maxWidth="'fullscreen'"
            title="Player Game Logs"
            @close="isGameLogsModalOpen = false"
        >
            <div class="bg-[#08090b] p-3 sm:p-5">
                <PlayerGameLogs
                    v-if="isGameLogsModalOpen"
                    :key="`${props.player_id}-${isGameLogsModalOpen}`"
                    :player_id="props.player_id"
                    :season_id="isGameLogsModalOpen"
                />
            </div>
        </Modal>

    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";

import PlayerGameLogs from "./PlayerGameLogs.vue";
import Modal from "@/Components/Modal.vue";
import { roleBadgeClass } from "@/Utility/Formatter";

const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});

const isGameLogsModalOpen = ref(false);

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
|--------------------------------------------------------------------------
| Game Logs
|--------------------------------------------------------------------------
*/

function openGameLogs(seasonId) {
    if (!seasonId) {
        return;
    }

    isGameLogsModalOpen.value = seasonId;
}


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