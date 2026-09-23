<template>
    <div class="w-full">
        <!-- ========================================================= -->
        <!-- TEAM HEADER -->
        <!-- ========================================================= -->
        <div
            v-if="team_info?.teams && !loading"
            class="relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl"
        >
            <!-- Team color accent -->
            <div
                class="absolute inset-x-0 top-0 h-1"
                :style="{
                    background: teamGradient(
                        team_info.teams.primary_color,
                        team_info.teams.secondary_color
                    ),
                }"
            ></div>

            <!-- Header -->
            <div
                class="relative px-4 sm:px-5 py-4 sm:py-5 border-b border-slate-800"
                :style="{
                    background: `linear-gradient(135deg, ${hexColor(
                        team_info.teams.primary_color,
                        '#1e293b'
                    )}20, transparent 65%)`,
                }"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <!-- Team identity -->
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="flex-shrink-0 w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center border border-white/10 shadow-lg"
                            :style="{
                                background: teamGradient(
                                    team_info.teams.primary_color,
                                    team_info.teams.secondary_color
                                ),
                            }"
                        >
                            <span
                                class="text-xs sm:text-sm font-black text-white"
                            >
                                {{ team_info.teams.acronym ?? "TM" }}
                            </span>
                        </div>

                        <div class="min-w-0">
                            <h2
                                class="text-base sm:text-xl font-black text-white truncate"
                            >
                                {{ team_info.teams.team_name ?? "-" }}
                            </h2>

                            <div
                                class="flex flex-wrap items-center gap-2 mt-1.5"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-white/5 border border-white/10 text-[10px] sm:text-xs font-semibold text-slate-300"
                                >
                                    <i
                                        class="fas fa-globe-americas text-slate-500"
                                    ></i>

                                    {{
                                        team_info.teams.conference_name ?? "-"
                                    }}
                                </span>

                                <span
                                    class="text-[9px] uppercase tracking-[0.18em] text-slate-600"
                                >
                                    Franchise Leaders
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Record count -->
                    <div
                        class="self-start sm:self-auto flex items-center gap-3 px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800"
                    >
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10"
                        >
                            <i
                                class="fas fa-ranking-star text-xs text-amber-400"
                            ></i>
                        </div>

                        <div>
                            <p
                                class="text-[9px] uppercase tracking-wider text-slate-600"
                            >
                                Rankings
                            </p>

                            <p class="text-sm font-black text-white">
                                Top {{ players?.length ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- CONTENT -->
            <!-- ========================================================= -->
            <div class="p-3 sm:p-4">
                <!-- Section header -->
                <div
                    class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="w-1.5 h-6 rounded-full bg-amber-500"
                            ></span>

                            <div>
                                <h3
                                    class="text-sm sm:text-base font-bold text-white"
                                >
                                    Top 15 Players All-Time
                                </h3>

                                <p
                                    class="mt-0.5 text-[10px] sm:text-xs text-slate-600"
                                >
                                    Franchise career leaders ranked by
                                    statistical production
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Small legend -->
                    <div
                        class="flex flex-wrap items-center gap-2 text-[9px] sm:text-[10px]"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-slate-500"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-emerald-400"
                            ></span>
                            Current Player
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 text-slate-500"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-red-400"
                            ></span>
                            Former Player
                        </span>
                    </div>
                </div>

                <!-- ===================================================== -->
                <!-- TABLE -->
                <!-- ===================================================== -->
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950 overflow-hidden"
                >
                    <!-- Horizontal scrolling prevents cramped columns -->
                    <div class="overflow-x-auto">
                        <table class="min-w-[1450px] w-full">
                            <!-- HEADER -->
                            <thead>
                                <tr
                                    class="border-b border-slate-800 bg-slate-900"
                                >
                                    <th class="table-header text-center w-16">
                                        Rank
                                    </th>

                                    <th class="table-header text-left">
                                        Player
                                    </th>

                                    <th class="table-header text-left">
                                        Status
                                    </th>

                                    <th class="table-header text-left">
                                        Current Team
                                    </th>

                                    <th class="table-header text-center">
                                        MVP
                                    </th>

                                    <th class="table-header text-left">
                                        Awards
                                    </th>

                                    <th class="table-header text-right">
                                        Points
                                    </th>

                                    <th class="table-header text-right">
                                        Assists
                                    </th>

                                    <th class="table-header text-right">
                                        Rebounds
                                    </th>

                                    <th class="table-header text-right">
                                        Blocks
                                    </th>

                                    <th class="table-header text-right">
                                        Steals
                                    </th>

                                    <th class="table-header text-right">
                                        Stat Points
                                    </th>
                                </tr>
                            </thead>

                            <!-- BODY -->
                            <tbody class="divide-y divide-slate-800/70">
                                <template v-if="players?.length">
                                    <tr
                                        v-for="(player, ii) in players"
                                        :key="player.player_id"
                                        class="group transition-all duration-150"
                                        :class="
                                            isCurrentTeam(player)
                                                ? 'bg-emerald-500/[0.025] hover:bg-emerald-500/[0.06]'
                                                : 'bg-red-500/[0.025] hover:bg-red-500/[0.06]'
                                        "
                                    >
                                        <!-- ========================= -->
                                        <!-- RANK -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-center"
                                        >
                                            <div
                                                class="flex items-center justify-center"
                                            >
                                                <!-- Top 3 -->
                                                <div
                                                    v-if="ii < 3"
                                                    class="flex items-center justify-center w-8 h-8 rounded-lg font-black text-xs"
                                                    :class="
                                                        rankClass(ii)
                                                    "
                                                >
                                                    <i
                                                        v-if="ii === 0"
                                                        class="fas fa-crown text-[10px]"
                                                    ></i>

                                                    <i
                                                        v-else-if="ii === 1"
                                                        class="fas fa-medal text-[10px]"
                                                    ></i>

                                                    <i
                                                        v-else
                                                        class="fas fa-medal text-[10px]"
                                                    ></i>
                                                </div>

                                                <!-- Remaining -->
                                                <span
                                                    v-else
                                                    class="text-xs font-bold text-slate-500"
                                                >
                                                    {{ ii + 1 }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- PLAYER -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <div
                                                class="flex items-center gap-3 min-w-[210px]"
                                            >
                                                <!-- Avatar -->
                                                <div
                                                    class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-lg border border-slate-800 bg-slate-900"
                                                    :style="
                                                        ii < 3
                                                            ? {
                                                                  boxShadow: `0 0 0 1px ${hexColor(
                                                                      team_info
                                                                          .teams
                                                                          .primary_color,
                                                                      '#6366f1'
                                                                  )}22`,
                                                              }
                                                            : {}
                                                    "
                                                >
                                                    <i
                                                        class="fas fa-user text-[10px] text-slate-600"
                                                    ></i>
                                                </div>

                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <p
                                                            class="text-xs sm:text-sm font-bold truncate max-w-[190px]"
                                                            :class="
                                                                isCurrentTeam(
                                                                    player
                                                                )
                                                                    ? 'text-slate-200 group-hover:text-white'
                                                                    : 'text-slate-400 group-hover:text-slate-200'
                                                            "
                                                        >
                                                            {{
                                                                player.player_name ??
                                                                "-"
                                                            }}
                                                        </p>

                                                        <!-- Small ranking marker -->
                                                        <span
                                                            v-if="ii === 0"
                                                            class="text-[8px] uppercase tracking-wider text-amber-400 font-bold"
                                                        >
                                                            GOAT
                                                        </span>
                                                    </div>

                                                    <div
                                                        class="flex items-center gap-2 mt-0.5"
                                                    >
                                                        <span
                                                            class="text-[9px] text-slate-600"
                                                        >
                                                            Player #{{
                                                                player.player_id
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- STATUS -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <span
                                                v-if="player.is_active"
                                                class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[9px] font-bold text-emerald-400"
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full bg-emerald-400"
                                                ></span>

                                                Active
                                            </span>

                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-[9px] font-bold text-red-400"
                                            >
                                                <i
                                                    class="fas fa-user-slash text-[8px]"
                                                ></i>

                                                Retired
                                            </span>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- CURRENT TEAM -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <div
                                                v-if="player.is_active"
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                                    :class="
                                                        isCurrentTeam(player)
                                                            ? 'bg-emerald-400'
                                                            : 'bg-red-400'
                                                    "
                                                ></span>

                                                <span
                                                    class="text-xs font-semibold truncate max-w-[170px]"
                                                    :class="
                                                        isCurrentTeam(player)
                                                            ? 'text-emerald-300'
                                                            : 'text-slate-400'
                                                    "
                                                >
                                                    {{
                                                        player.current_team_name ??
                                                        "Free Agent"
                                                    }}
                                                </span>
                                            </div>

                                            <span
                                                v-else
                                                class="text-xs text-slate-700"
                                            >
                                                —
                                            </span>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- MVP -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-center"
                                        >
                                            <div
                                                class="inline-flex items-center justify-center gap-1.5"
                                            >
                                                <i
                                                    v-if="
                                                        Number(
                                                            player.finals_mvp_count
                                                        ) > 0
                                                    "
                                                    class="fas fa-trophy text-[10px] text-amber-400"
                                                ></i>

                                                <span
                                                    class="text-xs font-bold"
                                                    :class="
                                                        Number(
                                                            player.finals_mvp_count
                                                        ) > 0
                                                            ? 'text-amber-300'
                                                            : 'text-slate-500'
                                                    "
                                                >
                                                    {{
                                                        player.finals_mvp_count ??
                                                        0
                                                    }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- AWARDS -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <div
                                                class="max-w-[240px] text-xs leading-relaxed text-slate-400 whitespace-normal"
                                            >
                                                {{
                                                    player.all_awards ??
                                                    "No major awards"
                                                }}
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- POINTS -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatNumber(
                                                    player.total_points
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- ASSISTS -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatNumber(
                                                    player.total_assists
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- REBOUNDS -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatNumber(
                                                    player.total_rebounds
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- BLOCKS -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatNumber(
                                                    player.total_blocks
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- STEALS -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatNumber(
                                                    player.total_steals
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- STATISTICAL POINTS -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right"
                                        >
                                            <span
                                                class="inline-flex items-center justify-end min-w-[75px] px-2 py-1 rounded-md bg-indigo-500/10 border border-indigo-500/10 text-xs font-black text-indigo-300"
                                            >
                                                {{
                                                    formatNumber(
                                                        player.base_statistical_points
                                                    )
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                </template>

                                <!-- EMPTY STATE -->
                                <tr v-else>
                                    <td
                                        colspan="12"
                                        class="px-4 py-14 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center"
                                        >
                                            <div
                                                class="flex items-center justify-center w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 mb-3"
                                            >
                                                <i
                                                    class="fas fa-ranking-star text-slate-600"
                                                ></i>
                                            </div>

                                            <p
                                                class="text-sm font-semibold text-slate-400"
                                            >
                                                No Player Data Found
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-slate-600"
                                            >
                                                There are no all-time player
                                                statistics available for this
                                                team.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bottom note -->
                <div
                    v-if="players?.length"
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-3 px-1"
                >
                    <p
                        class="text-[9px] uppercase tracking-wider text-slate-700"
                    >
                        Franchise Career Statistics
                    </p>

                    <p class="text-[9px] text-slate-700">
                        {{ players.length }} player{{
                            players.length === 1 ? "" : "s"
                        }}
                        ranked
                    </p>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- LOADING -->
        <!-- ========================================================= -->
        <div
            v-else-if="loading"
            class="rounded-2xl border border-slate-800 bg-slate-950 shadow-xl"
        >
            <div
                class="flex flex-col items-center justify-center min-h-[300px] p-8"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/10 mb-4"
                >
                    <i
                        class="fas fa-spinner fa-spin text-indigo-400 text-lg"
                    ></i>
                </div>

                <p class="text-sm font-semibold text-slate-300">
                    Loading Franchise Leaders
                </p>

                <p class="mt-1 text-xs text-slate-600">
                    Retrieving all-time player statistics...
                </p>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- FALLBACK -->
        <!-- ========================================================= -->
        <div
            v-else
            class="rounded-2xl border border-dashed border-slate-800 bg-slate-950 p-8 text-center"
        >
            <div
                class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 mb-3"
            >
                <i class="fas fa-users text-slate-600"></i>
            </div>

            <p class="text-sm font-semibold text-slate-400">
                Team Information Unavailable
            </p>

            <p class="mt-1 text-xs text-slate-600">
                Unable to load franchise player statistics.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});

const players = ref([]);
const team_info = ref([]);
const loading = ref(false);

/*
|--------------------------------------------------------------------------
| Color Helpers
|--------------------------------------------------------------------------
*/

const hexColor = (color, fallback = "#1e293b") => {
    if (!color) {
        return fallback;
    }

    const value = String(color).replace("#", "");

    return `#${value}`;
};

const teamGradient = (
    primary,
    secondary,
    fallbackPrimary = "#334155",
    fallbackSecondary = "#0f172a"
) => {
    return `linear-gradient(
        135deg,
        ${hexColor(primary, fallbackPrimary)} 0%,
        ${hexColor(secondary, fallbackSecondary)} 100%
    )`;
};

/*
|--------------------------------------------------------------------------
| Player Helpers
|--------------------------------------------------------------------------
*/

const isCurrentTeam = (player) => {
    return (
        Number(player?.current_team_id) ===
        Number(team_info.value?.teams?.id)
    );
};

const formatNumber = (value) => {
    if (value === null || value === undefined || value === "") {
        return "0";
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "0";
    }

    return number.toLocaleString();
};

const rankClass = (index) => {
    if (index === 0) {
        return "bg-amber-500/10 border border-amber-500/20 text-amber-400";
    }

    if (index === 1) {
        return "bg-slate-400/10 border border-slate-400/20 text-slate-300";
    }

    return "bg-orange-500/10 border border-orange-500/20 text-orange-400";
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchTeamInfo = async () => {
    try {
        const response = await axios.post(
            route("teams.info"),
            {
                team_id: props.team_id,
            }
        );

        team_info.value = response.data;
    } catch (error) {
        console.error(
            "Error fetching team info:",
            error
        );
    }
};

const fetchTopPlayers = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("best.team.players.alltime"),
            {
                team_id: props.team_id,
            }
        );

        players.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching top players:",
            error
        );

        players.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await Promise.all([
        fetchTeamInfo(),
        fetchTopPlayers(),
    ]);
});
</script>

<style scoped>
.table-header {
    padding: 0.75rem 0.7rem;
    white-space: nowrap;
    font-size: 0.6rem;
    line-height: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgb(100 116 139);
}

.table-cell {
    padding: 0.75rem 0.7rem;
    vertical-align: middle;
    white-space: nowrap;
}

.stat-value {
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 700;
    color: rgb(203 213 225);
    font-variant-numeric: tabular-nums;
}

table {
    border-collapse: separate;
    border-spacing: 0;
}

tbody tr {
    min-height: 52px;
}

@media (max-width: 640px) {
    .table-header {
        padding: 0.65rem 0.55rem;
        font-size: 0.55rem;
    }

    .table-cell {
        padding: 0.65rem 0.55rem;
    }
}
</style>