<template>
    <section
        class="w-full rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl overflow-hidden"
    >
        <!-- Top Accent -->
        <div
            class="h-1 bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
        ></div>

        <!-- Header -->
        <div
            class="border-b border-slate-800 bg-[#0d1117] px-5 py-5 sm:px-6"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-[#080b10]"
                    >
                        <i
                            class="fas fa-trophy text-slate-300 text-lg"
                        ></i>
                    </div>

                    <div>
                        <h2
                            class="text-lg font-bold tracking-tight text-white sm:text-xl"
                        >
                            MVP History
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                            Players recognized as season MVPs
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-2 self-start rounded-lg border border-slate-800 bg-[#080b10] px-3 py-2 sm:self-auto"
                >
                    <i class="fas fa-medal text-[11px] text-slate-500"></i>

                    <span
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-500"
                    >
                        {{ data.length }} MVP
                        {{ data.length === 1 ? "Award" : "Awards" }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="flex min-h-[320px] items-center justify-center bg-[#0a0d12]"
        >
            <div class="flex flex-col items-center gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-700 bg-[#080b10]"
                >
                    <i
                        class="fas fa-spinner fa-spin text-slate-400"
                    ></i>
                </div>

                <div class="text-center">
                    <p class="text-sm font-semibold text-slate-300">
                        Loading MVP history
                    </p>

                    <p class="mt-1 text-xs text-slate-600">
                        Retrieving award records...
                    </p>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="data.length === 0"
            class="flex min-h-[300px] items-center justify-center bg-[#0a0d12] px-6"
        >
            <div class="text-center">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-slate-800 bg-[#080b10]"
                >
                    <i
                        class="fas fa-trophy text-slate-600 text-lg"
                    ></i>
                </div>

                <h3 class="mt-4 text-sm font-semibold text-slate-300">
                    No MVP records
                </h3>

                <p class="mt-1 text-xs text-slate-600">
                    There are currently no MVP award records available.
                </p>
            </div>
        </div>

        <!-- MVP Cards -->
        <div
            v-else
            class="bg-[#0a0d12] p-4 sm:p-5"
        >
            <div
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
            >
                <article
                    v-for="(player, index) in data"
                    :key="player.player_id"
                    class="group relative overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117] transition-all duration-200 hover:border-slate-700 hover:bg-[#10151d]"
                    @click="toggleView(player.player_id)"
                >
                    <!-- Ranking -->
                    <div
                        class="absolute right-3 top-3 z-10 flex h-7 min-w-7 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10] px-2"
                    >
                        <span
                            class="text-[10px] font-black tracking-wider"
                            :class="rankClass(index)"
                        >
                            {{ formatRank(index) }}
                        </span>
                    </div>

                    <!-- Main Player Area -->
                    <div class="p-5">
                        <div class="flex items-start gap-3 pr-10">
                            <!-- Trophy Icon -->
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-[#080b10]"
                            >
                                <i
                                    class="fas fa-trophy text-slate-400"
                                ></i>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-600"
                                >
                                    Season MVP
                                </p>

                                <h3
                                    class="mt-1 truncate text-base font-bold text-white"
                                    :title="player.player_name"
                                >
                                    {{ player.player_name || "Unknown Player" }}
                                </h3>

                                <p
                                    class="mt-1 truncate text-xs text-slate-400"
                                    :title="player.current_team_names"
                                >
                                    <i
                                        class="fas fa-users mr-1 text-[9px] text-slate-600"
                                    ></i>

                                    {{
                                        player.current_team_names ||
                                        "Free Agent"
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            <span
                                class="rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-slate-500"
                            >
                                {{
                                    player.is_active
                                        ? "Active"
                                        : "Inactive"
                                }}
                            </span>

                            <span
                                v-if="player.player_role"
                                class="rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-slate-500"
                            >
                                {{ player.player_role }}
                            </span>
                        </div>
                    </div>

                    <!-- Stats Preview -->
                    <div
                        class="border-y border-slate-800 bg-[#0a0d12] px-4 py-3"
                    >
                        <div
                            class="grid grid-cols-3 divide-x divide-slate-800"
                        >
                            <div class="px-2 text-center">
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wide text-slate-600"
                                >
                                    PPG
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-300"
                                >
                                    {{
                                        formatNumber(
                                            player.avg_points_per_game
                                        )
                                    }}
                                </p>
                            </div>

                            <div class="px-2 text-center">
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wide text-slate-600"
                                >
                                    RPG
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-300"
                                >
                                    {{
                                        formatNumber(
                                            player.avg_rebounds_per_game
                                        )
                                    }}
                                </p>
                            </div>

                            <div class="px-2 text-center">
                                <p
                                    class="text-[9px] font-bold uppercase tracking-wide text-slate-600"
                                >
                                    APG
                                </p>

                                <p
                                    class="mt-1 text-sm font-bold text-slate-300"
                                >
                                    {{
                                        formatNumber(
                                            player.avg_assists_per_game
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Finals MVP -->
                    <div class="px-5 py-4">
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                                >
                                    Finals MVP Teams
                                </p>

                                <p
                                    v-if="player.mvp_winning_team_names"
                                    class="mt-1.5 truncate text-xs font-medium text-slate-400"
                                    :title="
                                        player.mvp_winning_team_names
                                    "
                                >
                                    <i
                                        class="fas fa-trophy mr-1 text-[9px] text-slate-600"
                                    ></i>

                                    {{ player.mvp_winning_team_names }}
                                </p>

                                <p
                                    v-else
                                    class="mt-1.5 text-xs text-slate-700"
                                >
                                    No Finals MVP history
                                </p>
                            </div>

                            <i
                                class="fas fa-chevron-right mt-1 shrink-0 text-[9px] text-slate-700 transition-transform duration-200 group-hover:translate-x-1"
                            ></i>
                        </div>
                    </div>

                    <!-- Click Hint -->
                    <div
                        class="border-t border-slate-800 px-5 py-2.5"
                    >
                        <div
                            class="flex items-center justify-between"
                        >
                            <span
                                class="text-[9px] uppercase tracking-widest text-slate-700"
                            >
                                Click to view
                            </span>

                            <span
                                class="text-[9px] font-semibold text-slate-600"
                            >
                                {{
                                    player.viewMode === "none"
                                        ? "Career Stats"
                                        : player.viewMode === "stats"
                                        ? "Awards"
                                        : "Close"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Overlay -->
                    <transition name="fade">
                        <div
                            v-if="player.viewMode !== 'none'"
                            class="absolute inset-0 z-20 flex items-center justify-center bg-[#07090d]/95 backdrop-blur-sm"
                            @click.stop="toggleView(player.player_id)"
                        >
                            <div
                                class="w-full px-5 py-6"
                            >
                                <!-- Stats -->
                                <div
                                    v-if="player.viewMode === 'stats'"
                                    class="text-left"
                                >
                                    <div
                                        class="mb-5 flex items-center justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-600"
                                            >
                                                Career Snapshot
                                            </p>

                                            <h4
                                                class="mt-1 text-base font-bold text-white"
                                            >
                                                {{ player.player_name }}
                                            </h4>
                                        </div>

                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                                        >
                                            <i
                                                class="fas fa-chart-line text-slate-400 text-sm"
                                            ></i>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div
                                            v-for="stat in playerStats(player)"
                                            :key="stat.label"
                                            class="flex items-center justify-between rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5"
                                        >
                                            <div
                                                class="flex items-center gap-2.5"
                                            >
                                                <i
                                                    :class="[
                                                        stat.icon,
                                                        'w-4 text-center text-slate-600 text-xs',
                                                    ]"
                                                ></i>

                                                <span
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{ stat.label }}
                                                </span>
                                            </div>

                                            <span
                                                class="text-xs font-bold text-slate-200"
                                            >
                                                {{ stat.value }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Awards -->
                                <div
                                    v-else-if="
                                        player.viewMode === 'awards'
                                    "
                                    class="text-left"
                                >
                                    <div
                                        class="mb-5 flex items-center justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-600"
                                            >
                                                Career Honors
                                            </p>

                                            <h4
                                                class="mt-1 text-base font-bold text-white"
                                            >
                                                Awards
                                            </h4>
                                        </div>

                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                                        >
                                            <i
                                                class="fas fa-medal text-slate-400 text-sm"
                                            ></i>
                                        </div>
                                    </div>

                                    <div
                                        v-if="
                                            Array.isArray(
                                                player.awards_won
                                            ) &&
                                            player.awards_won.length > 0
                                        "
                                        class="space-y-2"
                                    >
                                        <div
                                            v-for="(
                                                award, awardIndex
                                            ) in player.awards_won"
                                            :key="awardIndex"
                                            class="flex items-center gap-3 rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-3"
                                        >
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#080b10] border border-slate-800"
                                            >
                                                <i
                                                    class="fas fa-trophy text-[11px] text-slate-500"
                                                ></i>
                                            </div>

                                            <span
                                                class="text-xs font-medium text-slate-300"
                                            >
                                                {{ award }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-lg border border-slate-800 bg-[#0d1117] px-4 py-5 text-center"
                                    >
                                        <i
                                            class="fas fa-award text-slate-700 text-lg"
                                        ></i>

                                        <p
                                            class="mt-2 text-xs text-slate-600"
                                        >
                                            No additional awards recorded.
                                        </p>
                                    </div>
                                </div>

                                <!-- Overlay Footer -->
                                <div
                                    class="mt-5 flex items-center justify-center gap-2 text-[9px] uppercase tracking-widest text-slate-700"
                                >
                                    <i
                                        class="fas fa-hand-pointer"
                                    ></i>

                                    Click to continue
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Bottom Accent -->
                    <div
                        class="h-px bg-gradient-to-r from-transparent via-slate-800 to-transparent"
                    ></div>
                </article>
            </div>

            <!-- Footer -->
            <div
                class="mt-4 flex flex-col gap-2 border-t border-slate-800 pt-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2">
                    <i
                        class="fas fa-circle-info text-[10px] text-slate-700"
                    ></i>

                    <span class="text-[10px] text-slate-600">
                        Select a player card to cycle through career
                        statistics and awards.
                    </span>
                </div>

                <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-700"
                >
                    MVP Archive
                </span>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";

const data = ref([]);
const loading = ref(false);

const fetchMVPLists = async () => {
    loading.value = true;

    try {
        const response = await axios.get(
            route("awards.mvp.status")
        );

        const players = Array.isArray(response.data)
            ? response.data
            : [];

        data.value = players.map((player) => ({
            ...player,
            viewMode: "none",
            awards_won: Array.isArray(player.awards_won)
                ? player.awards_won
                : [],
        }));
    } catch (error) {
        console.error("Error fetching MVP data:", error);

        data.value = [];
    } finally {
        loading.value = false;
    }
};

const toggleView = (playerId) => {
    const player = data.value.find(
        (p) => p.player_id === playerId
    );

    if (!player) {
        return;
    }

    if (player.viewMode === "none") {
        player.viewMode = "stats";
    } else if (player.viewMode === "stats") {
        player.viewMode = "awards";
    } else {
        player.viewMode = "none";
    }
};

const playerStats = (player) => {
    return [
        {
            label: "Total Games",
            value: formatNumber(player.total_games, 0),
            icon: "fas fa-gamepad",
        },
        {
            label: "Points Per Game",
            value: formatNumber(player.avg_points_per_game),
            icon: "fas fa-basketball",
        },
        {
            label: "Assists Per Game",
            value: formatNumber(player.avg_assists_per_game),
            icon: "fas fa-hands-helping",
        },
        {
            label: "Rebounds Per Game",
            value: formatNumber(player.avg_rebounds_per_game),
            icon: "fas fa-arrow-down",
        },
        {
            label: "Steals Per Game",
            value: formatNumber(player.avg_steals_per_game),
            icon: "fas fa-hand",
        },
        {
            label: "Blocks Per Game",
            value: formatNumber(player.avg_blocks_per_game),
            icon: "fas fa-shield-halved",
        },
    ];
};

const formatNumber = (value, decimals = 1) => {
    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "-";
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return "-";
    }

    return number.toFixed(decimals);
};

const formatRank = (index) => {
    return String(index + 1).padStart(2, "0");
};

const rankClass = (index) => {
    if (index === 0) {
        return "text-amber-400";
    }

    if (index === 1) {
        return "text-slate-300";
    }

    if (index === 2) {
        return "text-orange-400";
    }

    return "text-slate-600";
};

onMounted(() => {
    fetchMVPLists();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>