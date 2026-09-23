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
                                    Seasonal Star Players
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Player count -->
                    <div
                        class="self-start sm:self-auto flex items-center gap-3 px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800"
                    >
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500/10"
                        >
                            <i
                                class="fas fa-star text-xs text-amber-400"
                            ></i>
                        </div>

                        <div>
                            <p
                                class="text-[9px] uppercase tracking-wider text-slate-600"
                            >
                                Records
                            </p>

                            <p class="text-sm font-black text-white">
                                {{ players?.length ?? 0 }} Seasons
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- CONTENT -->
            <!-- ========================================================= -->
            <div class="p-3 sm:p-4">
                <!-- Section heading -->
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
                                    Star Players Per Season
                                </h3>

                                <p
                                    class="mt-0.5 text-[10px] sm:text-xs text-slate-600"
                                >
                                    Season-by-season performance of the
                                    franchise's star players
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div
                        class="flex flex-wrap items-center gap-2 text-[9px] sm:text-[10px]"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-slate-500"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-emerald-400"
                            ></span>
                            Current Team
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 text-slate-500"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-red-400"
                            ></span>
                            Former Team
                        </span>
                    </div>
                </div>

                <!-- ===================================================== -->
                <!-- TABLE -->
                <!-- ===================================================== -->
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950 overflow-hidden"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-[1350px] w-full">
                            <!-- HEADER -->
                            <thead>
                                <tr
                                    class="border-b border-slate-800 bg-slate-900"
                                >
                                    <th
                                        class="table-header text-left min-w-[100px]"
                                    >
                                        Season
                                    </th>

                                    <th
                                        class="table-header text-left min-w-[220px]"
                                    >
                                        Star Player
                                    </th>

                                    <th
                                        class="table-header text-left min-w-[130px]"
                                    >
                                        Current Role
                                    </th>

                                    <th
                                        class="table-header text-left min-w-[170px]"
                                    >
                                        Current Team
                                    </th>

                                    <th class="table-header text-right">
                                        FG %
                                    </th>

                                    <th class="table-header text-right">
                                        PPG
                                    </th>

                                    <th class="table-header text-right">
                                        APG
                                    </th>

                                    <th class="table-header text-right">
                                        RPG
                                    </th>

                                    <th class="table-header text-right">
                                        BPG
                                    </th>

                                    <th class="table-header text-right">
                                        SPG
                                    </th>

                                    <th class="table-header text-right">
                                        PER
                                    </th>

                                    <th class="table-header text-right">
                                        EFF
                                    </th>
                                </tr>
                            </thead>

                            <!-- BODY -->
                            <tbody class="divide-y divide-slate-800/70">
                                <template v-if="players?.length">
                                    <tr
                                        v-for="(player, ii) in players"
                                        :key="
                                            player.player_id +
                                            '-' +
                                            player.season_id +
                                            '-' +
                                            ii
                                        "
                                        class="group cursor-pointer transition-all duration-150"
                                        :class="
                                            isCurrentTeam(player)
                                                ? 'bg-emerald-500/[0.025] hover:bg-emerald-500/[0.07]'
                                                : 'bg-red-500/[0.025] hover:bg-red-500/[0.07]'
                                        "
                                        @click="showPlayerProfile(player)"
                                    >
                                        <!-- ========================= -->
                                        <!-- SEASON -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 text-[10px] font-black text-slate-300"
                                                >
                                                    {{
                                                        ii + 1
                                                    }}
                                                </span>

                                                <span
                                                    class="text-xs font-bold text-slate-300 whitespace-nowrap"
                                                >
                                                    {{
                                                        player.season_name ??
                                                        player.season_id ??
                                                        "-"
                                                    }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- PLAYER -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <div
                                                class="flex items-center gap-3 min-w-0"
                                            >
                                                <!-- Player icon -->
                                                <div
                                                    class="relative flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-lg border border-amber-500/10 bg-amber-500/5"
                                                >
                                                    <i
                                                        class="fas fa-star text-[10px] text-amber-400"
                                                    ></i>

                                                    <!-- Current team marker -->
                                                    <span
                                                        class="absolute -right-0.5 -bottom-0.5 w-2 h-2 rounded-full border-2 border-slate-950"
                                                        :class="
                                                            isCurrentTeam(
                                                                player
                                                            )
                                                                ? 'bg-emerald-400'
                                                                : 'bg-red-400'
                                                        "
                                                    ></span>
                                                </div>

                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <p
                                                            class="text-xs sm:text-sm font-bold uppercase truncate max-w-[190px]"
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

                                                        <span
                                                            v-if="ii === 0"
                                                            class="hidden sm:inline-flex items-center px-1.5 py-0.5 rounded bg-amber-500/10 border border-amber-500/10 text-[8px] uppercase tracking-wider font-bold text-amber-400"
                                                        >
                                                            Star
                                                        </span>
                                                    </div>

                                                    <p
                                                        class="mt-0.5 text-[9px] text-slate-600 truncate max-w-[220px]"
                                                        :title="
                                                            draftTitle(player)
                                                        "
                                                    >
                                                        {{
                                                            draftLabel(player)
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- ROLE -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-indigo-500/10 border border-indigo-500/10 text-[9px] font-bold uppercase text-indigo-300"
                                            >
                                                <i
                                                    class="fas fa-user-tie text-[8px]"
                                                ></i>

                                                {{
                                                    player.current_role ??
                                                    "—"
                                                }}
                                            </span>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- CURRENT TEAM -->
                                        <!-- ========================= -->
                                        <td class="table-cell">
                                            <div
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
                                                    v-if="player.current_team"
                                                    class="text-xs font-semibold uppercase truncate max-w-[160px]"
                                                    :class="
                                                        isCurrentTeam(player)
                                                            ? 'text-emerald-300'
                                                            : 'text-slate-400'
                                                    "
                                                >
                                                    {{
                                                        player.current_team
                                                    }}
                                                </span>

                                                <span
                                                    v-else
                                                    class="text-xs font-semibold text-slate-600"
                                                >
                                                    FREE AGENT
                                                </span>
                                            </div>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- FG % -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            <span
                                                class="inline-flex min-w-[55px] justify-end"
                                            >
                                                {{
                                                    formatDecimal(
                                                        player.field_goal_percentage
                                                    )
                                                }}%
                                            </span>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- PPG -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatDecimal(
                                                    player.avg_points_per_game
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- APG -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatDecimal(
                                                    player.avg_assists_per_game
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- RPG -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatDecimal(
                                                    player.avg_rebounds_per_game
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- BPG -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatDecimal(
                                                    player.avg_blocks_per_game
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- SPG -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right stat-value"
                                        >
                                            {{
                                                formatDecimal(
                                                    player.avg_steals_per_game
                                                )
                                            }}
                                        </td>

                                        <!-- ========================= -->
                                        <!-- PER -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right"
                                        >
                                            <span
                                                class="inline-flex items-center justify-end min-w-[55px] px-2 py-1 rounded-md bg-purple-500/10 border border-purple-500/10 text-xs font-black text-purple-300"
                                            >
                                                {{
                                                    formatDecimal(
                                                        player.per,
                                                        2
                                                    )
                                                }}
                                            </span>
                                        </td>

                                        <!-- ========================= -->
                                        <!-- EFF -->
                                        <!-- ========================= -->
                                        <td
                                            class="table-cell text-right"
                                        >
                                            <span
                                                class="inline-flex items-center justify-end min-w-[55px] px-2 py-1 rounded-md bg-amber-500/10 border border-amber-500/10 text-xs font-black text-amber-300"
                                            >
                                                {{
                                                    formatDecimal(
                                                        player.eff,
                                                        2
                                                    )
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                </template>

                                <!-- EMPTY -->
                                <tr v-else-if="!loading">
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
                                                    class="fas fa-star text-slate-600"
                                                ></i>
                                            </div>

                                            <p
                                                class="text-sm font-semibold text-slate-400"
                                            >
                                                No Star Player Data
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-slate-600"
                                            >
                                                No seasonal star-player
                                                statistics were found for this
                                                team.
                                            </p>
                                        </div>
                                    </td>
                                </tr>

                                <!-- LOADING -->
                                <tr v-if="loading">
                                    <td
                                        colspan="12"
                                        class="px-4 py-14 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center"
                                        >
                                            <div
                                                class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/10 mb-3"
                                            >
                                                <i
                                                    class="fas fa-spinner fa-spin text-indigo-400"
                                                ></i>
                                            </div>

                                            <p
                                                class="text-sm font-semibold text-slate-400"
                                            >
                                                Loading Star Players
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-slate-600"
                                            >
                                                Retrieving seasonal
                                                performance...
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    v-if="players?.length"
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-3 px-1"
                >
                    <div
                        class="flex items-center gap-1.5 text-[9px] uppercase tracking-wider text-slate-700"
                    >
                        <i class="fas fa-hand-pointer"></i>
                        Click a player to view profile
                    </div>

                    <p class="text-[9px] text-slate-700">
                        {{ players.length }} seasonal record{{
                            players.length === 1 ? "" : "s"
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- INITIAL LOADING -->
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
                    Loading Season Star Players
                </p>

                <p class="mt-1 text-xs text-slate-600">
                    Retrieving franchise star-player history...
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
                Unable to load seasonal star-player statistics.
            </p>
        </div>

        <!-- ========================================================= -->
        <!-- PLAYER PROFILE MODAL -->
        <!-- ========================================================= -->
        <Modal
            :show="!!showPlayerProfileModal"
            :maxWidth="'6xl'"
            title="Player Profile"
            @close="showPlayerProfileModal = null"
        >
            <div class="p-4 sm:p-6 bg-slate-950">
                <PlayerPerformance
                    v-if="showPlayerProfileModal"
                    :key="showPlayerProfileModal"
                    :player_id="showPlayerProfileModal"
                />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});

const showPlayerProfileModal = ref(null);
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
        Number(player?.current_team) ===
            Number(team_info.value?.teams?.team_name) ||
        Number(player?.current_team_id) ===
            Number(team_info.value?.teams?.id) ||
        player?.current_team === team_info.value?.teams?.team_name
    );
};

const formatDecimal = (value, decimals = 1) => {
    if (value === null || value === undefined || value === "") {
        return "0.0";
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "0.0";
    }

    return number.toFixed(decimals);
};

const draftLabel = (player) => {
    if (!player?.draft_status) {
        return "Draft information unavailable";
    }

    if (
        player.draft_status === "Special Draft" ||
        player.draft_status === "Undrafted"
    ) {
        return `S${player.draft_id ?? "-"} ${player.draft_status}`;
    }

    return player.draft_status;
};

const draftTitle = (player) => {
    return draftLabel(player);
};

/*
|--------------------------------------------------------------------------
| Player Modal
|--------------------------------------------------------------------------
*/

const showPlayerProfile = (player) => {
    if (!player?.player_id) {
        return;
    }

    showPlayerProfileModal.value = player.player_id;
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
            route("best.team.star.players"),
            {
                team_id: props.team_id,
            }
        );

        players.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching seasonal star players:",
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
    min-height: 54px;
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