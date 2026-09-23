<template>
    <div class="w-full">
        <!-- =========================================================
             HEADER
        ========================================================== -->
        <div
            class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-800 bg-gray-900"
                >
                    <i class="fa fa-line-chart text-[10px] text-gray-500"></i>
                </div>

                <div>
                    <h2 class="text-xs font-semibold text-gray-200">
                        Last 5 Game Performance
                    </h2>

                    <p class="text-[9px] text-gray-600">
                        Recent game-by-game production
                    </p>
                </div>
            </div>

            <div
                v-if="game_logs.total > 0"
                class="self-start sm:self-auto inline-flex items-center gap-1.5 rounded-md border border-gray-800 bg-gray-900 px-2 py-1"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>

                <span class="text-[9px] font-semibold text-gray-400">
                    {{ game_logs.total }}
                    {{ game_logs.total === 1 ? "game" : "games" }}
                </span>
            </div>
        </div>

        <!-- =========================================================
             LOADING
        ========================================================== -->
        <div
            v-if="isLoading"
            class="border-t border-gray-800"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[900px] border-collapse text-[10px]"
                >
                    <thead>
                        <tr class="bg-gray-900">
                            <th
                                v-for="column in loadingColumns"
                                :key="column"
                                class="px-3 py-2"
                            >
                                <div
                                    class="h-2.5 animate-pulse rounded bg-gray-800"
                                ></div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-800">
                        <tr
                            v-for="row in 5"
                            :key="row"
                        >
                            <td
                                v-for="column in loadingColumns"
                                :key="`${row}-${column}`"
                                class="px-3 py-3"
                            >
                                <div
                                    class="h-3 animate-pulse rounded bg-gray-900"
                                ></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =========================================================
             EMPTY STATE
        ========================================================== -->
        <div
            v-else-if="!game_logs?.game_logs?.length"
            class="border-t border-gray-800 px-4 py-8 text-center"
        >
            <div
                class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-full border border-gray-800 bg-gray-900"
            >
                <i class="fa fa-bar-chart text-xs text-gray-600"></i>
            </div>

            <p class="text-xs font-medium text-gray-400">
                No recent game data
            </p>

            <p class="mt-1 text-[10px] text-gray-600">
                No game performances are available for this player.
            </p>
        </div>

        <!-- =========================================================
             TABLE
        ========================================================== -->
        <div
            v-else
            class="border-t border-gray-800"
        >
            <div class="overflow-x-auto">
                <!--
                    The minimum width is intentional.
                    This component can be embedded in a narrow player card,
                    so the table scrolls instead of compressing every column.
                -->
                <table
                    class="w-full min-w-[1050px] border-collapse text-[10px]"
                >
                    <!-- =================================================
                         GROUP HEADERS
                    ================================================== -->
                    <thead>
                        <tr
                            class="border-b border-gray-800 bg-gray-950 text-[8px] uppercase tracking-[0.15em] text-gray-700"
                        >
                            <th
                                colspan="3"
                                class="border-r border-gray-800 px-3 py-2 text-left"
                            >
                                Game
                            </th>

                            <th
                                v-if="full"
                                colspan="2"
                                class="border-r border-gray-800 px-3 py-2 text-left"
                            >
                                Context
                            </th>

                            <th
                                :colspan="full ? 7 : 1"
                                class="border-r border-gray-800 px-3 py-2 text-left"
                            >
                                Box Score
                            </th>

                            <th
                                v-if="full"
                                colspan="1"
                                class="border-r border-gray-800 px-3 py-2 text-right"
                            >
                                Advanced
                            </th>

                            <th
                                class="px-3 py-2 text-right"
                            >
                                Impact
                            </th>
                        </tr>

                        <!-- =================================================
                             COLUMN HEADERS
                        ================================================== -->
                        <tr
                            class="bg-gray-900 text-[9px] uppercase tracking-wider text-gray-500"
                        >
                            <th
                                class="sticky left-0 z-20 bg-gray-900 px-3 py-2.5 text-left font-semibold whitespace-nowrap"
                            >
                                Season
                            </th>

                            <th
                                class="px-3 py-2.5 text-left font-semibold whitespace-nowrap"
                            >
                                Team
                            </th>

                            <th
                                class="px-3 py-2.5 text-left font-semibold whitespace-nowrap border-r border-gray-800"
                            >
                                Opponent
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-left font-semibold whitespace-nowrap"
                            >
                                Round
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-left font-semibold whitespace-nowrap border-r border-gray-800"
                            >
                                Min
                            </th>

                            <th
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                Pts
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                Reb
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                Ast
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                Stl
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                Blk
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                TO
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap border-r border-gray-800"
                            >
                                Fouls
                            </th>

                            <th
                                v-if="full"
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap border-r border-gray-800"
                            >
                                PER
                            </th>

                            <th
                                class="px-3 py-2.5 text-right font-semibold whitespace-nowrap"
                            >
                                EFF
                            </th>
                        </tr>
                    </thead>

                    <!-- =================================================
                         BODY
                    ================================================== -->
                    <tbody class="divide-y divide-gray-800/80">
                        <tr
                            v-for="(game, index) in game_logs.game_logs"
                            :key="game.id ?? index"
                            :title="
                                !full
                                    ? roundNameFormatter(
                                          normalizedRound(game.round)
                                      )
                                    : ''
                            "
                            class="group transition-colors duration-150"
                            :class="rowClass(game)"
                        >
                            <!-- Season -->
                            <td
                                class="sticky left-0 z-10 bg-gray-950 group-hover:bg-gray-900 px-3 py-3 whitespace-nowrap border-r border-gray-800"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-5 w-1 rounded-full"
                                        :class="resultAccent(game)"
                                    ></span>

                                    <div>
                                        <div class="font-semibold text-gray-300">
                                            {{ game.season_name }}
                                        </div>

                                        <div
                                            class="text-[8px] uppercase tracking-wider text-gray-700"
                                        >
                                            Game {{ index + 1 }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Team -->
                            <td
                                class="px-3 py-3 whitespace-nowrap"
                            >
                                <span class="font-medium text-gray-400">
                                    {{ game.team_name || "—" }}
                                </span>
                            </td>

                            <!-- Opponent -->
                            <td
                                class="px-3 py-3 whitespace-nowrap border-r border-gray-800"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="resultDot(game)"
                                    ></span>

                                    <span class="font-medium text-gray-300">
                                        {{ game.opponent_team_name || "—" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Round -->
                            <td
                                v-if="full"
                                class="px-3 py-3 whitespace-nowrap"
                            >
                                <span class="text-gray-500">
                                    {{
                                        roundNameFormatter(
                                            normalizedRound(game.round)
                                        )
                                    }}
                                </span>
                            </td>

                            <!-- Minutes -->
                            <td
                                v-if="full"
                                class="px-3 py-3 whitespace-nowrap border-r border-gray-800"
                            >
                                <span
                                    v-if="isDnp(game)"
                                    class="inline-flex rounded-md border border-gray-700 bg-gray-900 px-2 py-1 text-[9px] font-bold uppercase text-gray-500"
                                >
                                    DNP
                                </span>

                                <span
                                    v-else
                                    class="font-semibold text-gray-300"
                                >
                                    {{ formatNumber(game.minutes) }}
                                </span>
                            </td>

                            <!-- Points -->
                            <td
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                <span
                                    class="font-bold"
                                    :class="
                                        Number(game.points ?? 0) >= 20
                                            ? 'text-white'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{ formatNumber(game.points) }}
                                </span>
                            </td>

                            <!-- Rebounds -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                {{ formatNumber(game.rebounds) }}
                            </td>

                            <!-- Assists -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                {{ formatNumber(game.assists) }}
                            </td>

                            <!-- Steals -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                {{ formatNumber(game.steals) }}
                            </td>

                            <!-- Blocks -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                {{ formatNumber(game.blocks) }}
                            </td>

                            <!-- Turnovers -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                {{
                                    isDnp(game)
                                        ? "0.0"
                                        : formatNumber(game.turnovers)
                                }}
                            </td>

                            <!-- Fouls -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap border-r border-gray-800"
                            >
                                {{
                                    isDnp(game)
                                        ? "0.0"
                                        : formatNumber(game.fouls)
                                }}
                            </td>

                            <!-- PER -->
                            <td
                                v-if="full"
                                class="px-3 py-3 text-right whitespace-nowrap border-r border-gray-800"
                            >
                                <span class="font-semibold text-gray-300">
                                    {{ formatPer(game.per) }}
                                </span>
                            </td>

                            <!-- EFF -->
                            <td
                                class="px-3 py-3 text-right whitespace-nowrap"
                            >
                                <span
                                    class="inline-flex min-w-[35px] justify-center rounded-md px-2 py-1 font-bold"
                                    :class="efficiencyClass(game.eff)"
                                >
                                    {{ formatNumber(game.eff) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- =========================================================
                 FOOTER
            ========================================================== -->
            <div
                class="flex flex-col gap-1 border-t border-gray-800 bg-gray-900/30 px-4 py-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <span class="text-[9px] text-gray-600">
                    Showing {{ game_logs.game_logs.length }} recent
                    {{ game_logs.game_logs.length === 1 ? "game" : "games" }}
                </span>

                <div class="flex items-center gap-3 text-[9px]">
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        Win
                    </span>

                    <span class="flex items-center gap-1.5 text-gray-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
                        Loss
                    </span>

                    <span class="flex items-center gap-1.5 text-gray-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                        DNP
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { roundNameFormatter } from "@/Utility/Formatter";

const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },

    season_id: {
        type: Number,
        required: true,
    },

    full: {
        type: Boolean,
        default: true,
    },
});

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
    itemsperpage: 5,
    search: "",
    player_id: null,
    season_id: null,
});

/*
|--------------------------------------------------------------------------
| Loading skeleton columns
|--------------------------------------------------------------------------
*/

const loadingColumns = Array.from(
    { length: props.full ? 14 : 5 },
    (_, index) => index
);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const normalizedRound = (round) => {
    const parsed = parseFloat(round);

    return Number.isNaN(parsed) ? round : parsed;
};

const isDnp = (game) => {
    return Number(game?.minutes ?? 0) === 0;
};

const formatNumber = (value) => {
    const number = Number(value ?? 0);

    if (!Number.isFinite(number)) {
        return "0.0";
    }

    return number.toFixed(1);
};

const formatPer = (value) => {
    const number = Number(value ?? 0);

    if (!Number.isFinite(number)) {
        return "0.00";
    }

    return number.toFixed(2);
};

/*
|--------------------------------------------------------------------------
| Row / Result Styling
|--------------------------------------------------------------------------
*/

const rowClass = (game) => {
    if (isDnp(game)) {
        return "bg-gray-950/80 hover:bg-gray-900";
    }

    if (game?.game_result === "Win") {
        return "bg-green-500/[0.025] hover:bg-green-500/[0.06]";
    }

    if (game?.game_result === "Loss") {
        return "bg-red-500/[0.025] hover:bg-red-500/[0.06]";
    }

    return "bg-gray-950 hover:bg-gray-900";
};

const resultAccent = (game) => {
    if (isDnp(game)) {
        return "bg-gray-600";
    }

    if (game?.game_result === "Win") {
        return "bg-green-400";
    }

    if (game?.game_result === "Loss") {
        return "bg-red-400";
    }

    return "bg-gray-600";
};

const resultDot = (game) => {
    if (isDnp(game)) {
        return "bg-gray-600";
    }

    if (game?.game_result === "Win") {
        return "bg-green-400 shadow-[0_0_6px_rgba(74,222,128,0.5)]";
    }

    if (game?.game_result === "Loss") {
        return "bg-red-400 shadow-[0_0_6px_rgba(248,113,113,0.5)]";
    }

    return "bg-gray-600";
};

/*
|--------------------------------------------------------------------------
| Efficiency
|--------------------------------------------------------------------------
*/

const efficiencyClass = (value) => {
    const number = Number(value ?? 0);

    if (!Number.isFinite(number) || number <= 0) {
        return "bg-red-500/10 border border-red-500/20 text-red-400";
    }

    if (number >= 20) {
        return "bg-green-500/10 border border-green-500/20 text-green-400";
    }

    if (number >= 10) {
        return "bg-lime-500/10 border border-lime-500/20 text-lime-400";
    }

    return "bg-yellow-500/10 border border-yellow-500/20 text-yellow-400";
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchPlayerGameLogs = async () => {
    try {
        isLoading.value = true;

        search.value.player_id = player_id.value;
        search.value.season_id = season_id.value;

        const response = await axios.post(
            route("players.latest.game.logs"),
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
};

const handlePagination = (page_num) => {
    search.value.page_num = page_num;
    fetchPlayerGameLogs();
};

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
| Watch player / season
|--------------------------------------------------------------------------
*/

watch(
    () => props.player_id,
    (newPlayerId) => {
        player_id.value = newPlayerId;
        search.value.page_num = 1;
        fetchPlayerGameLogs();
    }
);

watch(
    () => props.season_id,
    (newSeasonId) => {
        season_id.value = newSeasonId;
        search.value.page_num = 1;
        fetchPlayerGameLogs();
    }
);
</script>