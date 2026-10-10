<template>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1250px] border-collapse text-xs">

            <!-- Header -->
            <thead>
                <tr class=" border-b border-white/[0.07] bg-[#080a0c]">
                    <th
                        class="sticky left-0 z-30 w-[80px] bg-[#080a0c] px-4 py-3 text-left"
                    >
                        <span class="stat-heading">
                            Season
                        </span>
                    </th>

                    <th colspan="3" class="w-[125px] px-3 py-3 text-left hidden">
                        <span class="stat-heading">
                            Role
                        </span>
                    </th>
                    <th class="stat-th-title">
                        <span class="stat-heading">TEAM</span>
                    </th>
                    <th colspan="2" class="stat-th-title">
                        <span class="stat-heading">GAMES</span>
                    </th>
                    <th colspan="10" class="stat-th-title">
                        <span class="stat-heading">Average</span>
                    </th>
                    <th colspan="10" class="stat-th-title">
                        <span class="stat-heading">Total</span>
                    </th>
                    <th colspan="8" class="stat-th-title">
                        <span class="stat-heading">Attempts</span>
                    </th>
                    <th colspan="4" class="stat-th-title">
                        <span class="stat-heading">Percentage</span>
                    </th>
                    <th colspan="5" class="stat-th-title">
                        <span class="stat-heading">Advanced</span>
                    </th>
                </tr>
                <tr class="border-b border-white/[0.07] bg-[#080a0c]">

                    <th
                        class="sticky left-0 z-30 w-[80px] bg-[#080a0c] px-4 py-3 text-left"
                    >
                        <span class="stat-heading">
                            Season
                        </span>
                    </th>

                    <th
                        class="sticky left-[80px] z-30 w-[5px] bg-[#080a0c] px-4 py-3 text-left"
                    >
                        <span class="stat-heading">
                            Team
                        </span>
                    </th>

                    <th class="w-[125px] px-3 py-3 text-left hidden">
                        <span class="stat-heading">
                            Role
                        </span>
                    </th>

                    <th class="stat-th" title="Games Played">
                        <span class="stat-heading">GP</span>
                    </th>

                    <th class="stat-th" title="Games Started">
                        <span class="stat-heading">GS</span>
                    </th>

                    <th class="stat-th border-l border-gray-200" title="Minutes Per Game">
                        <span class="stat-heading">MPG</span>
                    </th>

                    <th class="stat-th" title="Points Per Game">
                        <span class="stat-heading">PPG</span>
                    </th>

                    <th class="stat-th" title="Assists Per Game">
                        <span class="stat-heading">APG</span>
                    </th>

                    <th class="stat-th" title="Rebounds Per Game">
                        <span class="stat-heading">RPG</span>
                    </th>

                    <th class="stat-th" title="Offensive Rebounds Per Game">
                        <span class="stat-heading">ORPG</span>
                    </th>

                     <th class="stat-th" title="Defensive Rebounds Per Game">
                        <span class="stat-heading">DRPG</span>
                    </th>

                    <th class="stat-th" title="Steals Per Game">
                        <span class="stat-heading">SPG</span>
                    </th>

                    <th class="stat-th" title="Blocks Per Game">
                        <span class="stat-heading">BPG</span>
                    </th>

                    <th class="stat-th" title="Turnovers Per Game">
                        <span class="stat-heading">TOPG</span>
                    </th>

                    <th class="stat-th" title="Fouls Per Game">
                        <span class="stat-heading">FPG</span>
                    </th>

                    <th class="stat-th border-l border-gray-200" title="Total Minutes">
                        <span class="stat-heading">TMIN</span>
                    </th>

                    <th class="stat-th" title="Total Points">
                        <span class="stat-heading">TPNT</span>
                    </th>

                    <th class="stat-th" title="Total Assists">
                        <span class="stat-heading">TAST</span>
                    </th>

                    <th class="stat-th" title="Total Rebounds">
                        <span class="stat-heading">TREB</span>
                    </th>

                    <th class="stat-th" title="Total Offensive Rebounds">
                        <span class="stat-heading">TOREB</span>
                    </th>

                    <th class="stat-th" title="Total Defensive Rebounds">
                        <span class="stat-heading">TDREB</span>
                    </th>

                    <th class="stat-th" title="Total Steals">
                        <span class="stat-heading">TSTL</span>
                    </th>

                    <th class="stat-th" title="Total Blocks">
                        <span class="stat-heading">TBLCK</span>
                    </th>

                    <th class="stat-th" title="Total Turnovers">
                        <span class="stat-heading">TTO</span>
                    </th>

                    <th class="stat-th" title="Total Fouls">
                        <span class="stat-heading">TFLS</span>
                    </th>

                    <!---Attempts And Made--->
                    <th class="stat-th border-l border-gray-200" title="Field Goals Made">
                        <span class="stat-heading">FGM</span>
                    </th>

                    <th class="stat-th" title="Field Goals Attempt">
                        <span class="stat-heading">FGA</span>
                    </th>

                    <th class="stat-th" title="Two Points Attempt">
                        <span class="stat-heading">2PA</span>
                    </th>

                    <th class="stat-th" title="Two Points Made">
                        <span class="stat-heading">2PM</span>
                    </th>

                    <th class="stat-th" title="Three Points Attempt">
                        <span class="stat-heading">3PA</span>
                    </th>

                    <th class="stat-th" title="Three Points Made">
                        <span class="stat-heading">3PM</span>
                    </th>

                    <th class="stat-th" title="Free Throws Made">
                        <span class="stat-heading">FTM</span>
                    </th>

                    <th class="stat-th" title="Free Throws Attempt">
                        <span class="stat-heading">FTA</span>
                    </th>

                    <!---Percentage--->
                    <th class="stat-th border-l border-gray-200" title="Field Goal Percentage">
                        <span class="stat-heading">FG %</span>
                    </th>

                    <th class="stat-th" title="Two Point Percentage">
                        <span class="stat-heading">2PT %</span>
                    </th>

                    <th class="stat-th" title="Three Point Percentage">
                        <span class="stat-heading">3PT %</span>
                    </th>

                    <th class="stat-th" title="Free throw Percentage">
                        <span class="stat-heading">FT %</span>
                    </th>

                    <!---Advanced--->
                    <th class="stat-th border-l border-gray-200" title="True Shooting Percentage">
                        <span class="stat-heading">TS %</span>
                    </th>

                    <th class="stat-th" title="Performance Efficiency Rating">
                        <span class="stat-heading">PER</span>
                    </th>

                    <th class="stat-th" title="Efficiency">
                        <span class="stat-heading">EFF</span>
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
                    v-if="seasonCount > 0"
                >
                    <tr
                        v-for="(player, index) in props?.playerStats"
                        :key="`season-${player.player_id}-${player.season_id}-${index}`"
                        @click.prevent="openGameLogs(player)"
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
                        <td class="px-3 py-3" hidden>
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

                        <td class="stat-td">
                            <span class="font-semibold text-white/75">
                                {{ player.total_games_started ?? 0 }}
                            </span>
                        </td>

                        <!-- MPG -->
                        <td class="stat-td">
                            {{ formatStat(player.average_minutes_per_game) }}
                        </td>

                        <!-- PPG -->
                        <td class="stat-td">
                            <span class="font-bold text-white">
                                {{ formatStat(player.average_points_per_game) }}
                            </span>
                        </td>

                        <!-- APG -->
                        <td class="stat-td">
                            {{ formatStat(player.average_assists_per_game) }}
                        </td>

                        <!-- RPG -->
                        <td class="stat-td">
                            {{ formatStat(player.average_rebounds_per_game) }}
                        </td>

                        <!-- RPG -->
                        <td class="stat-td">
                            {{ formatStat(player.average_offensive_rebounds_per_game) }}
                        </td>

                        <!-- RPG -->
                        <td class="stat-td">
                            {{ formatStat(player.average_defensive_rebounds_per_game) }}
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

                        <td class="stat-td">
                            {{ player.total_minutes_played }}
                        </td>

                        <!-- TPTS -->
                        <td class="stat-td">
                            {{ player.total_points }}
                        </td>

                        <!-- TPTS -->
                        <td class="stat-td">
                            {{ player.total_assists }}
                        </td>

                        <!-- TREB -->
                        <td class="stat-td">
                            {{ player.total_rebounds }}
                        </td>

                        <td class="stat-td">
                            {{ player.total_offensive_rebounds }}
                        </td>

                        <td class="stat-td">
                            {{ player.total_defensive_rebounds }}
                        </td>

                        <td class="stat-td">
                            {{ player.total_steals }}
                        </td>

                        <td class="stat-td">
                            {{ player.total_blocks }}
                        </td>

                        <td class="stat-td">
                            {{ player.total_turnovers }}
                        </td>

                        <td class="stat-td">
                            {{ player.total_fouls }}
                        </td>

                        <!-- FGM -->
                        <td class="stat-td">
                            {{ player.total_field_goals_made }}
                        </td>

                        <!-- FGA -->
                        <td class="stat-td">
                            {{ player.total_field_goal_attempts }}
                        </td>

                        <!-- 2PM -->
                        <td class="stat-td">
                            {{ player.total_two_pointers_made }}
                        </td>

                        <!-- 2PA -->
                        <td class="stat-td">
                            {{ player.total_two_point_attempts }}
                        </td>

                        <!-- 3PM -->
                        <td class="stat-td">
                            {{ player.total_three_pointers_made }}
                        </td>

                        <!-- 3PA -->
                        <td class="stat-td">
                            {{ player.total_three_point_attempts }}
                        </td>

                        <!-- FTM -->
                        <td class="stat-td">
                            {{ player.total_free_throws_made }}
                        </td>

                        <!-- FTA -->
                        <td class="stat-td">
                            {{ player.total_free_throw_attempts }}
                        </td>
        
                        <td class="stat-td">
                            {{ formatStat(player.field_goal_percentage) }}%
                        </td>

                        <td class="stat-td">
                            {{ formatStat(player.two_point_percentage) }}%
                        </td>

                        <td class="stat-td">
                            {{ formatStat(player.three_point_percentage) }}%
                        </td>

                        <td class="stat-td">
                            {{ formatStat(player.free_throw_percentage) }}%
                        </td>

                        <td class="stat-td">
                            {{ formatStat(player.ts_percent * 100) }}%
                        </td>

                        <td class="stat-td">
                            {{ formatStat(player.per) }}
                        </td>

                        <td class="stat-td">
                            {{ formatStat(player.eff) }}
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
                <tr v-else-if="loading">
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
                    <td colspan="24" class="px-5 py-12">
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
    <!-- =========================================================
        GAME LOG MODAL
    ========================================================== -->
    <Modal
        :show="isGameLogsModalOpen"
        :maxWidth="'fullscreen'"
        title="Player Game Logs"
        @close="isGameLogsModalOpen = false"
    >
        <div class="bg-[#08090b] p-3 sm:p-5">
            <PlayerGameLogs
                v-if="isGameLogsModalOpen"
                :key="`P${playerId}-S${seasonId}`"
                :player_id="playerId"
                :season_id="seasonId"
                :is_playoff="props.isPlayOff"
            />
        </div>
    </Modal>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import axios from "axios";

import PlayerGameLogs from "./PlayerGameLogs.vue";
import Modal from "@/Components/Modal.vue";
import { roleBadgeClass } from "@/Utility/Formatter";

const props = defineProps({
    playerStats: {
        type: Object,
        required: true,
    },
    isPlayOff:{
        type: Boolean,
        default: false,
    }
});

const isGameLogsModalOpen = ref(false);
const playerId = ref(false);
const seasonId = ref(false);
const loading = ref(false);
/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const seasonCount = computed(() => {
    return props?.playerStats?.length || 0;
});


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatStat = (value) => {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "—";
    }

    return number.toFixed(2);
}

const formatRating = (value) => {
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

const teamAccentStyle = (player) => {
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

const openGameLogs = (player) => {
    if (!player) {
        return;
    }

    isGameLogsModalOpen.value = true;
    seasonId.value = player.season_id;
    playerId.value = player.player_id;
}

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

.stat-th-title {
    width: 78px;
    padding: 12px;
    text-align: center;
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