<template>
    <div class="w-full">

        <!-- =========================================================
             GAME LOG CONTAINER
        ========================================================== -->
        <section
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0b0d10] shadow-[0_20px_70px_rgba(0,0,0,0.4)]"
        >

            <!-- =====================================================
                 HEADER
            ====================================================== -->
            <div
                class="relative overflow-hidden border-b border-white/[0.07] bg-gradient-to-r from-white/[0.035] via-transparent to-transparent"
            >
                <div class="relative flex items-center justify-between gap-4 px-5 py-4">

                    <!-- Left -->
                    <div class="flex min-w-0 items-center gap-3">

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
                                    d="M4 5h16M4 10h16M4 15h16M4 20h16"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">

                            <div class="flex items-center gap-2">
                                <h2
                                    class="truncate text-sm font-bold tracking-wide text-white sm:text-base"
                                >
                                    Regular Season Logs
                                </h2>

                                <span
                                    v-if="game_logs.total > 0"
                                    class="rounded-full border border-white/[0.08] bg-white/[0.045] px-2 py-0.5 text-[9px] font-bold tracking-wider text-white/45"
                                >
                                    {{ game_logs.total }}
                                </span>
                            </div>

                            <p
                                class="mt-0.5 hidden text-[11px] text-white/30 sm:block"
                            >
                                Complete game-by-game player performance
                            </p>

                        </div>
                    </div>

                    <!-- Right -->
                    <div class="hidden items-center gap-3 md:flex">

                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <span class="text-[10px] font-medium text-white/30">
                                Win
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                            <span class="text-[10px] font-medium text-white/30">
                                Loss
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-white/20"></span>
                            <span class="text-[10px] font-medium text-white/30">
                                DNP
                            </span>
                        </div>

                    </div>

                </div>
            </div>


            <!-- =====================================================
                 TABLE
            ====================================================== -->
            <div class="relative overflow-x-auto scrollbar-thin">

                <table
                    class="w-full min-w-[1900px] border-collapse text-xs"
                >

                    <!-- =================================================
                         TABLE HEAD
                    ================================================== -->
                    <thead>

                        <!-- Group row -->
                        <tr class="border-b border-white/[0.05] bg-[#080a0c]">

                            <th
                                colspan="5"
                                class="sticky left-0 z-40 bg-[#080a0c] px-4 py-2 text-left"
                            >
                                <span class="group-heading">
                                    Game
                                </span>
                            </th>

                            <th
                                colspan="8"
                                class="px-4 py-2 text-center"
                            >
                                <span class="group-heading">
                                    Box Score
                                </span>
                            </th>

                            <th
                                colspan="3"
                                class="px-4 py-2 text-center"
                            >
                                <span class="group-heading">
                                    Shooting
                                </span>
                            </th>

                            <th
                                colspan="2"
                                class="px-4 py-2 text-center"
                            >
                                <span class="group-heading">
                                    Advanced
                                </span>
                            </th>

                        </tr>

                        <!-- Column row -->
                        <tr class="border-b border-white/[0.07] bg-[#0a0c0f]">

                            <!-- Season -->
                            <th
                                class="sticky left-0 z-30 w-[105px] bg-[#0a0c0f] px-4 py-3 text-left"
                            >
                                <span class="heading">
                                    Season
                                </span>
                            </th>

                            <!-- Team -->
                            <th
                                class="sticky left-[105px] z-30 w-[185px] bg-[#0a0c0f] px-4 py-3 text-left"
                            >
                                <span class="heading">
                                    Team
                                </span>
                            </th>

                            <!-- Opponent -->
                            <th
                                class="sticky left-[290px] z-30 w-[185px] bg-[#0a0c0f] px-4 py-3 text-left"
                            >
                                <span class="heading">
                                    Opponent
                                </span>
                            </th>

                            <!-- Round -->
                            <th class="w-[120px] px-3 py-3 text-left">
                                <span class="heading">
                                    Round
                                </span>
                            </th>

                            <!-- Role -->
                            <th class="w-[120px] px-3 py-3 text-left">
                                <span class="heading">
                                    Role
                                </span>
                            </th>

                            <!-- Minutes -->
                            <th class="stat-heading-cell">
                                <span class="heading">MIN</span>
                            </th>

                            <!-- Points -->
                            <th class="stat-heading-cell">
                                <span class="heading">PTS</span>
                            </th>

                            <!-- Rebounds -->
                            <th class="stat-heading-cell">
                                <span class="heading">REB</span>
                            </th>

                            <!-- Assists -->
                            <th class="stat-heading-cell">
                                <span class="heading">AST</span>
                            </th>

                            <!-- Steals -->
                            <th class="stat-heading-cell">
                                <span class="heading">STL</span>
                            </th>

                            <!-- Blocks -->
                            <th class="stat-heading-cell">
                                <span class="heading">BLK</span>
                            </th>

                            <!-- Turnovers -->
                            <th class="stat-heading-cell">
                                <span class="heading">TO</span>
                            </th>

                            <!-- Fouls -->
                            <th class="stat-heading-cell">
                                <span class="heading">FOULS</span>
                            </th>

                            <!-- FG -->
                            <th class="shooting-heading-cell">
                                <span class="heading">
                                    FG
                                </span>
                            </th>

                            <!-- 2PT -->
                            <th class="shooting-heading-cell">
                                <span class="heading">
                                    2PT
                                </span>
                            </th>

                            <!-- 3PT -->
                            <th class="shooting-heading-cell">
                                <span class="heading">
                                    3PT
                                </span>
                            </th>

                            <!-- FT -->
                            <th class="shooting-heading-cell">
                                <span class="heading">
                                    FT
                                </span>
                            </th>

                            <!-- PER -->
                            <th class="advanced-heading-cell">
                                <span class="heading">
                                    PER
                                </span>
                            </th>

                            <!-- EFF -->
                            <th class="advanced-heading-cell">
                                <span class="heading">
                                    EFF
                                </span>
                            </th>

                        </tr>
                    </thead>


                    <!-- =================================================
                         BODY
                    ================================================== -->
                    <tbody>

                        <!-- Loading -->
                        <template v-if="isLoading">

                            <tr
                                v-for="i in 8"
                                :key="`loading-${i}`"
                                class="border-b border-white/[0.04]"
                            >
                                <td
                                    colspan="19"
                                    class="px-4 py-3"
                                >
                                    <div
                                        class="h-8 animate-pulse rounded-lg bg-white/[0.035]"
                                    ></div>
                                </td>
                            </tr>

                        </template>


                        <!-- Data -->
                        <template
                            v-else-if="
                                game_logs.game_logs &&
                                game_logs.game_logs.length
                            "
                        >

                            <tr
                                v-for="(player, index) in game_logs.game_logs"
                                :key="index"
                                class="group border-b border-white/[0.045] transition-colors duration-150"
                                :class="rowClass(player)"
                            >

                                <!-- =================================================
                                     SEASON
                                ================================================== -->
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex items-center gap-2">

                                        <span
                                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                                            :class="resultDotClass(player)"
                                        ></span>

                                        <span
                                            class="whitespace-nowrap font-semibold text-white/80"
                                        >
                                            {{ player.season_name }}
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     TEAM
                                ================================================== -->
                                <td
                                    class="sticky left-[105px] z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex min-w-0 items-center gap-2.5">

                                        <span
                                            class="h-7 w-1 shrink-0 rounded-full"
                                            :style="teamAccentStyle(player)"
                                        ></span>

                                        <span
                                            class="truncate font-medium text-white/70"
                                            :title="player.team_name"
                                        >
                                            {{ player.team_name || "—" }}
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     OPPONENT
                                ================================================== -->
                                <td
                                    class="sticky left-[290px] z-20 bg-[#0b0d10] px-4 py-3 group-hover:bg-[#111419]"
                                >
                                    <div class="flex min-w-0 items-center gap-2">

                                        <span class="text-white/20">
                                            vs
                                        </span>

                                        <span
                                            class="truncate font-medium text-white/55"
                                            :title="player.opponent_team_name"
                                        >
                                            {{ player.opponent_team_name || "—" }}
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     ROUND
                                ================================================== -->
                                <td class="px-3 py-3">
                                    <span class="whitespace-nowrap text-white/50">
                                        {{
                                            roundNameFormatter(
                                                isNaN(parseFloat(player.round))
                                                    ? player.round
                                                    : parseFloat(player.round)
                                            )
                                        }}
                                    </span>
                                </td>


                                <!-- =================================================
                                     ROLE
                                ================================================== -->
                                <td
                                    class="px-3 py-3"
                                    v-html="
                                        gameRoleBadgeClass(
                                            player.role,
                                            player.minutes
                                        )
                                    "
                                ></td>


                                <!-- =================================================
                                     MINUTES
                                ================================================== -->
                                <td class="stat-cell">
                                    <span
                                        v-if="Number(player.minutes) === 0"
                                        class="rounded-md border border-white/[0.06] bg-white/[0.035] px-2 py-1 text-[10px] font-bold text-white/30"
                                    >
                                        DNP
                                    </span>

                                    <span
                                        v-else
                                        class="font-semibold tabular-nums text-white/80"
                                    >
                                        {{ formatNumber(player.minutes, 1) }}
                                    </span>
                                </td>


                                <!-- =================================================
                                     POINTS
                                ================================================== -->
                                <td class="stat-cell">
                                    <span
                                        class="font-bold tabular-nums"
                                        :class="
                                            Number(player.points) >= 20
                                                ? 'text-white'
                                                : 'text-white/65'
                                        "
                                    >
                                        {{ formatNumber(player.points, 1) }}
                                    </span>
                                </td>


                                <!-- =================================================
                                     REBOUNDS
                                ================================================== -->
                                <td class="stat-cell">
                                    {{ formatNumber(player.rebounds, 1) }}
                                </td>


                                <!-- =================================================
                                     ASSISTS
                                ================================================== -->
                                <td class="stat-cell">
                                    {{ formatNumber(player.assists, 1) }}
                                </td>


                                <!-- =================================================
                                     STEALS
                                ================================================== -->
                                <td class="stat-cell">
                                    {{ formatNumber(player.steals, 1) }}
                                </td>


                                <!-- =================================================
                                     BLOCKS
                                ================================================== -->
                                <td class="stat-cell">
                                    {{ formatNumber(player.blocks, 1) }}
                                </td>


                                <!-- =================================================
                                     TURNOVERS
                                ================================================== -->
                                <td class="stat-cell">
                                    {{
                                        Number(player.minutes) === 0
                                            ? "0.0"
                                            : formatNumber(player.turnovers, 1)
                                    }}
                                </td>


                                <!-- =================================================
                                     FOULS
                                ================================================== -->
                                <td class="stat-cell">
                                    {{
                                        Number(player.minutes) === 0
                                            ? "0.0"
                                            : formatNumber(player.fouls, 1)
                                    }}
                                </td>


                                <!-- =================================================
                                     FIELD GOALS
                                ================================================== -->
                                <td class="shooting-cell">
                                    <div class="flex flex-col items-end">

                                        <span class="font-semibold tabular-nums text-white/70">
                                            {{ player.field_goals_made }}
                                            /
                                            {{ player.field_goal_attempts }}
                                        </span>

                                        <span class="text-[9px] tabular-nums text-white/30">
                                            {{ formatNumber(player.field_goal_percentage, 1) }}%
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     2PT
                                ================================================== -->
                                <td class="shooting-cell">
                                    <div class="flex flex-col items-end">

                                        <span class="font-semibold tabular-nums text-white/70">
                                            {{ player.two_pointers_made }}
                                            /
                                            {{ player.two_point_attempts }}
                                        </span>

                                        <span class="text-[9px] tabular-nums text-white/30">
                                            {{ formatNumber(player.two_point_percentage, 1) }}%
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     3PT
                                ================================================== -->
                                <td class="shooting-cell">
                                    <div class="flex flex-col items-end">

                                        <span class="font-semibold tabular-nums text-white/70">
                                            {{ player.three_pointers_made }}
                                            /
                                            {{ player.three_point_attempts }}
                                        </span>

                                        <span class="text-[9px] tabular-nums text-white/30">
                                            {{ formatNumber(player.three_point_percentage, 1) }}%
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     FREE THROWS
                                ================================================== -->
                                <td class="shooting-cell">
                                    <div class="flex flex-col items-end">

                                        <span class="font-semibold tabular-nums text-white/70">
                                            {{ player.free_throws_made }}
                                            /
                                            {{ player.free_throw_attempts }}
                                        </span>

                                        <span class="text-[9px] tabular-nums text-white/30">
                                            {{ formatNumber(player.free_throw_percentage, 1) }}%
                                        </span>

                                    </div>
                                </td>


                                <!-- =================================================
                                     PER
                                ================================================== -->
                                <td class="advanced-cell">
                                    <span class="font-semibold tabular-nums text-white/65">
                                        {{ formatNumber(player.per ?? 0, 2) }}
                                    </span>
                                </td>


                                <!-- =================================================
                                     EFF
                                ================================================== -->
                                <td class="advanced-cell">

                                    <span
                                        class="inline-flex min-w-[45px] justify-center rounded-md border px-2 py-1 font-bold tabular-nums"
                                        :class="
                                            Number(player.eff) <= 0
                                                ? 'border-red-400/10 bg-red-400/[0.04] text-red-300/80'
                                                : 'border-lime-400/10 bg-lime-400/[0.04] text-lime-300/80'
                                        "
                                    >
                                        {{ player.eff ?? 0 }}
                                    </span>

                                </td>

                            </tr>

                        </template>


                        <!-- =================================================
                             EMPTY
                        ================================================== -->
                        <tr v-else>

                            <td
                                colspan="19"
                                class="px-5 py-16"
                            >

                                <div class="flex flex-col items-center justify-center text-center">

                                    <div
                                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.025]"
                                    >
                                        <svg
                                            class="h-6 w-6 text-white/20"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M4 5h16M4 10h16M4 15h16M4 20h16"
                                            />
                                        </svg>
                                    </div>

                                    <p class="text-sm font-semibold text-white/45">
                                        No game logs found
                                    </p>

                                    <p class="mt-1 text-[11px] text-white/25">
                                        There are no recorded games for this player and season.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>
            </div>


            <!-- =========================================================
                 FOOTER / PAGINATION
            ========================================================== -->
            <div
                v-if="game_logs.total_records"
                class="border-t border-white/[0.07] bg-[#090b0d] px-4 py-3"
            >
                <div class="flex w-full overflow-x-auto">
                    <Paginator
                        :page_number="search.page_num"
                        :total_rows="game_logs.total_records ?? 0"
                        :itemsperpage="search.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>

        </section>
    </div>
</template>


<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";

import {
    roundNameFormatter,
    gameRoleBadgeClass,
} from "@/Utility/Formatter";

import Paginator from "@/Components/Paginator.vue";


const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },

    season_id: {
        type: Number,
        required: true,
    },
});


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const game_logs = ref({
    game_logs: [],
    total: 0,
    total_records: 0,
});

const player_id = ref(props.player_id);
const season_id = ref(props.season_id);

const isLoading = ref(true);

const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    itemsperpage: 10,
    search: "",
});


/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

function formatNumber(value, decimals = 1) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return (0).toFixed(decimals);
    }

    return number.toFixed(decimals);
}


/*
|--------------------------------------------------------------------------
| Row styling
|--------------------------------------------------------------------------
*/

function rowClass(player) {
    const minutes = Number(player.minutes || 0);

    if (minutes === 0) {
        return "bg-white/[0.008] opacity-60";
    }

    if (player.game_result === "Win") {
        return "hover:bg-emerald-400/[0.025]";
    }

    if (player.game_result === "Loss") {
        return "hover:bg-red-400/[0.025]";
    }

    return "hover:bg-white/[0.02]";
}


function resultDotClass(player) {
    const minutes = Number(player.minutes || 0);

    if (minutes === 0) {
        return "bg-white/20";
    }

    if (player.game_result === "Win") {
        return "bg-emerald-400";
    }

    if (player.game_result === "Loss") {
        return "bg-red-400";
    }

    return "bg-white/30";
}


/*
|--------------------------------------------------------------------------
| Team colors
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
| Fetch
|--------------------------------------------------------------------------
*/

async function fetchPlayerGameLogs() {
    isLoading.value = true;

    try {
        search.value.player_id = player_id.value;
        search.value.season_id = season_id.value;

        const response = await axios.post(
            route("players.game.logs"),
            search.value
        );

        game_logs.value = response.data || {
            game_logs: [],
            total: 0,
            total_records: 0,
        };

    } catch (error) {

        console.error(
            "Error fetching player game logs:",
            error
        );

        game_logs.value = {
            game_logs: [],
            total: 0,
            total_records: 0,
        };

    } finally {
        isLoading.value = false;
    }
}


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

function handlePagination(page_num) {
    search.value.page_num = page_num;

    fetchPlayerGameLogs();
}


/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchPlayerGameLogs();
});


/*
|--------------------------------------------------------------------------
| React when modal/player changes
|--------------------------------------------------------------------------
*/

watch(
    () => props.player_id,
    (newPlayerId, oldPlayerId) => {

        if (
            newPlayerId &&
            newPlayerId !== oldPlayerId
        ) {
            player_id.value = newPlayerId;

            search.value.page_num = 1;

            fetchPlayerGameLogs();
        }
    }
);


watch(
    () => props.season_id,
    (newSeasonId, oldSeasonId) => {

        if (
            newSeasonId &&
            newSeasonId !== oldSeasonId
        ) {
            season_id.value = newSeasonId;

            search.value.page_num = 1;

            fetchPlayerGameLogs();
        }
    }
);
</script>


<style scoped>
/*
|--------------------------------------------------------------------------
| Table headings
|--------------------------------------------------------------------------
*/

.heading {
    display: inline-block;
    color: rgba(255, 255, 255, 0.28);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.13em;
    text-transform: uppercase;
    white-space: nowrap;
}

.group-heading {
    color: rgba(255, 255, 255, 0.18);
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}


/*
|--------------------------------------------------------------------------
| Standard stat cells
|--------------------------------------------------------------------------
*/

.stat-heading-cell {
    width: 72px;
    padding: 12px;
    text-align: right;
}

.stat-cell {
    width: 72px;
    padding: 11px 12px;
    text-align: right;
    color: rgba(255, 255, 255, 0.5);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| Shooting cells
|--------------------------------------------------------------------------
*/

.shooting-heading-cell {
    width: 125px;
    padding: 12px;
    text-align: right;
}

.shooting-cell {
    width: 125px;
    padding: 10px 12px;
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| Advanced stats
|--------------------------------------------------------------------------
*/

.advanced-heading-cell {
    width: 85px;
    padding: 12px;
    text-align: right;
}

.advanced-cell {
    width: 85px;
    padding: 10px 12px;
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| Scrollbar
|--------------------------------------------------------------------------
*/

.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    height: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 999px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>