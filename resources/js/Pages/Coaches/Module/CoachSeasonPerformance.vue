<template>
    <section class="w-full min-w-0 overflow-hidden">
        <!-- =========================================================
             HEADER
        ========================================================== -->
        <div
            class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div
                    class="text-[9px] font-black uppercase tracking-[0.22em] text-yellow-500/60"
                >
                    Coaching History
                </div>

                <h2 class="mt-0.5 text-sm font-black text-white">
                    Regular Season Logs
                </h2>
            </div>

            <div
                v-if="coach_history?.total_items"
                class="text-[10px] font-medium text-gray-600"
            >
                {{ coach_history.total_items }} season<span
                    v-if="coach_history.total_items != 1"
                >s</span>
            </div>
        </div>

        <!-- =========================================================
             TABLE CONTAINER
        ========================================================== -->
        <div
            class="overflow-hidden rounded-xl border border-white/[0.07] bg-[#090909]"
        >
            <!-- Loading -->
            <div
                v-if="loading"
                class="space-y-2 p-3"
            >
                <div
                    v-for="n in 7"
                    :key="n"
                    class="h-12 animate-pulse rounded-lg bg-white/[0.035]"
                ></div>
            </div>

            <!-- Table -->
            <div
                v-else
                ref="tableContainer"
                class="w-full overflow-x-auto"
            >
                <table
                    class="w-full min-w-[1250px] border-collapse text-[11px]"
                >
                    <!-- =================================================
                         HEADER
                    ================================================== -->
                    <thead>
                        <tr
                            class="border-b border-white/[0.08] bg-[#0d0d0d]"
                        >
                            <!-- Sticky Season -->
                            <th
                                class="sticky left-0 z-30 min-w-[125px] border-r border-white/[0.07] bg-[#0d0d0d] px-3 py-3 text-left text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Season
                            </th>

                            <th
                                class="min-w-[150px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Team
                            </th>

                            <th
                                class="min-w-[70px] px-3 py-3 text-right text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Wins
                            </th>

                            <th
                                class="min-w-[70px] px-3 py-3 text-right text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Losses
                            </th>

                            <th
                                class="min-w-[90px] px-3 py-3 text-right text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Conf. Rank
                            </th>

                            <th
                                class="min-w-[170px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Conf. Result
                            </th>

                            <th
                                class="min-w-[90px] px-3 py-3 text-right text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Nat'l Rank
                            </th>

                            <th
                                class="min-w-[170px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Nat'l Result
                            </th>

                            <th
                                class="min-w-[150px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Conf. Finals
                            </th>

                            <th
                                class="min-w-[150px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Nat'l Finals
                            </th>

                            <th
                                class="min-w-[110px] px-3 py-3 text-center text-[8px] font-black uppercase tracking-[0.12em] text-gray-600"
                            >
                                Chemistry
                            </th>
                        </tr>
                    </thead>

                    <!-- =================================================
                         BODY
                    ================================================== -->
                    <tbody>
                        <tr
                            v-for="season in coach_history.history"
                            :key="`${season.season_id}-${season.team_id}`"
                            class="group border-b border-white/[0.05] text-white transition last:border-b-0"
                            :style="{
                                backgroundColor: getTeamBackground(
                                    season.primary_color
                                )
                            }"
                        >
                            <!-- =========================================
                                 SEASON
                            ========================================== -->
                            <td
                                class="sticky left-0 z-20 border-r border-white/[0.08] px-3 py-3 font-bold whitespace-nowrap"
                                :style="{
                                    backgroundColor: getTeamColor(
                                        season.primary_color
                                    )
                                }"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-1.5 w-1.5 shrink-0 rounded-full bg-yellow-500 shadow-[0_0_8px_rgba(234,179,8,0.35)]"
                                    ></span>

                                    <span class="text-gray-100">
                                        {{ season.season_name }}
                                    </span>
                                </div>
                            </td>

                            <!-- =========================================
                                 TEAM
                            ========================================== -->
                            <td
                                class="whitespace-nowrap px-3 py-3"
                            >
                                <div
                                    class="font-bold text-gray-100"
                                >
                                    {{ season.team_name }}
                                </div>

                                <div
                                    v-if="season.team_id"
                                    class="mt-0.5 text-[9px] text-gray-500"
                                >
                                    Team #{{ season.team_id }}
                                </div>
                            </td>

                            <!-- =========================================
                                 WINS
                            ========================================== -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-right"
                            >
                                <span
                                    class="font-mono font-black text-emerald-400"
                                >
                                    {{ season.wins }}
                                </span>
                            </td>

                            <!-- =========================================
                                 LOSSES
                            ========================================== -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-right"
                            >
                                <span
                                    class="font-mono font-black text-rose-400"
                                >
                                    {{ season.losses }}
                                </span>
                            </td>

                            <!-- =========================================
                                 CONFERENCE RANK
                            ========================================== -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-right"
                            >
                                <span
                                    :class="
                                        getRankClass(
                                            season.conference_rank
                                        )
                                    "
                                    class="inline-flex min-w-[30px] items-center justify-center rounded-md border px-2 py-1 font-mono text-[10px] font-black"
                                >
                                    {{ season.conference_rank }}
                                </span>
                            </td>

                            <!-- =========================================
                                 CONFERENCE RESULT
                            ========================================== -->
                            <td class="px-3 py-3">
                                <span
                                    v-if="
                                        season.conference_rank == 1
                                    "
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-yellow-500/20 bg-yellow-500/[0.08] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-yellow-400"
                                >
                                    <i class="fa fa-trophy text-[8px]"></i>
                                    Conference Champions
                                </span>

                                <span
                                    v-else-if="
                                        season.conference_rank == 2
                                    "
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-gray-400/15 bg-white/[0.06] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-gray-300"
                                >
                                    <i class="fa fa-medal text-[8px]"></i>
                                    Conference Runner Up
                                </span>

                                <span
                                    v-else-if="
                                        season.conference_rank == 3
                                    "
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-orange-500/15 bg-orange-500/[0.06] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-orange-400"
                                >
                                    <i class="fa fa-medal text-[8px]"></i>
                                    Conference Third Place
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-white/[0.06] bg-white/[0.025] px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wide text-gray-600"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- =========================================
                                 NATIONAL RANK
                            ========================================== -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-right"
                            >
                                <span
                                    :class="
                                        getRankClass(
                                            season.overall_rank
                                        )
                                    "
                                    class="inline-flex min-w-[30px] items-center justify-center rounded-md border px-2 py-1 font-mono text-[10px] font-black"
                                >
                                    {{ season.overall_rank }}
                                </span>
                            </td>

                            <!-- =========================================
                                 NATIONAL RESULT
                            ========================================== -->
                            <td class="px-3 py-3">
                                <span
                                    v-if="
                                        season.overall_rank == 1
                                    "
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-yellow-500/20 bg-yellow-500/[0.08] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-yellow-400"
                                >
                                    <i class="fa fa-trophy text-[8px]"></i>
                                    National Champions
                                </span>

                                <span
                                    v-else-if="
                                        season.overall_rank == 2
                                    "
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-gray-400/15 bg-white/[0.06] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-gray-300"
                                >
                                    <i class="fa fa-medal text-[8px]"></i>
                                    National Runner Up
                                </span>

                                <span
                                    v-else-if="
                                        season.overall_rank == 3
                                    "
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-orange-500/15 bg-orange-500/[0.06] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-orange-400"
                                >
                                    <i class="fa fa-medal text-[8px]"></i>
                                    National Third Place
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-white/[0.06] bg-white/[0.025] px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wide text-gray-600"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- =========================================
                                 CONFERENCE FINALS
                            ========================================== -->
                            <td class="px-3 py-3">
                                <span
                                    v-if="season.won_semi_finals == 1"
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-yellow-500/20 bg-yellow-500/[0.08] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-yellow-400"
                                >
                                    <i class="fa fa-trophy text-[8px]"></i>
                                    Champion
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-white/[0.06] bg-white/[0.025] px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wide text-gray-600"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- =========================================
                                 NATIONAL FINALS
                            ========================================== -->
                            <td class="px-3 py-3">
                                <span
                                    v-if="season.won_finals == 1"
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-yellow-500/20 bg-yellow-500/[0.08] px-2.5 py-1.5 text-[9px] font-black uppercase tracking-wide text-yellow-400"
                                >
                                    <i class="fa fa-trophy text-[8px]"></i>
                                    Champion
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-white/[0.06] bg-white/[0.025] px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wide text-gray-600"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- =========================================
                                 CHEMISTRY
                            ========================================== -->
                            <td
                                class="whitespace-nowrap px-3 py-3 text-center"
                            >
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <i
                                        :class="
                                            getMoraleIcon(
                                                season.chemistry
                                            )
                                        "
                                        :title="
                                            getMoraleTitle(
                                                season.chemistry
                                            )
                                        "
                                    ></i>

                                    <span
                                        class="font-mono text-[10px] font-black"
                                        :class="
                                            getChemistryTextClass(
                                                season.chemistry
                                            )
                                        "
                                    >
                                        {{ season.chemistry }}%
                                    </span>
                                </div>

                                <div
                                    class="mx-auto mt-1.5 h-1 w-16 overflow-hidden rounded-full bg-white/[0.07]"
                                >
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="
                                            getChemistryBarClass(
                                                season.chemistry
                                            )
                                        "
                                        :style="{
                                            width: `${chemistryPercentage(season.chemistry)}%`
                                        }"
                                    ></div>
                                </div>
                            </td>
                        </tr>

                        <!-- =================================================
                             EMPTY STATE
                        ================================================== -->
                        <tr
                            v-if="
                                !coach_history?.history?.length
                            "
                        >
                            <td
                                colspan="11"
                                class="px-4 py-12 text-center"
                            >
                                <div
                                    class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.025]"
                                >
                                    <i
                                        class="fa fa-calendar-xmark text-gray-700"
                                    ></i>
                                </div>

                                <div
                                    class="mt-3 text-xs font-bold text-gray-400"
                                >
                                    No season history found
                                </div>

                                <div
                                    class="mt-1 text-[10px] text-gray-700"
                                >
                                    This coach has no regular season logs.
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- =========================================================
                 MOBILE SCROLL HINT
            ========================================================== -->
            <div
                v-if="!loading && coach_history?.history?.length"
                class="border-t border-white/[0.06] bg-white/[0.015] px-3 py-2 text-center text-[9px] font-medium text-gray-700 sm:hidden"
            >
                <i class="fa fa-arrows-left-right mr-1"></i>
                Swipe horizontally to view all columns
            </div>

            <!-- =========================================================
                 PAGINATION
            ========================================================== -->
            <div
                v-if="coach_history?.total_items"
                class="overflow-x-auto border-t border-white/[0.06] bg-[#090909] p-2"
            >
                <Paginator
                    :page_number="search.page_num"
                    :total_rows="coach_history.total_items ?? 0"
                    :itemsperpage="search.itemsperpage"
                    @page_num="handlePagination"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import Paginator from "@/Components/Paginator.vue";

const props = defineProps({
    coach_id: {
        type: Number,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const coach_history = ref({
    history: [],
    total_items: 0,
});

const loading = ref(false);

const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 10,
    coach_id: props.coach_id,
});

/*
|--------------------------------------------------------------------------
| Team Colors
|--------------------------------------------------------------------------
*/

const normalizeColor = (color) => {
    if (!color) {
        return "#111111";
    }

    const value = String(color).replace("#", "");

    if (!/^[0-9a-fA-F]{6}$/.test(value)) {
        return "#111111";
    }

    return `#${value}`;
};

const getTeamColor = (color) => {
    return normalizeColor(color);
};

const getTeamBackground = (color) => {
    const normalized = normalizeColor(color);

    /*
     * Use the team color as a subtle transparent-looking
     * dark background rather than making the entire row bright.
     */
    return `linear-gradient(
        90deg,
        ${normalized}33 0%,
        rgba(9, 9, 9, 0.96) 55%,
        rgba(9, 9, 9, 1) 100%
    )`;
};

/*
|--------------------------------------------------------------------------
| Rank Styling
|--------------------------------------------------------------------------
*/

const getRankClass = (rank) => {
    const value = Number(rank);

    if (value === 1) {
        return "border-yellow-500/20 bg-yellow-500/[0.08] text-yellow-400";
    }

    if (value === 2) {
        return "border-white/[0.12] bg-white/[0.06] text-gray-300";
    }

    if (value === 3) {
        return "border-orange-500/20 bg-orange-500/[0.08] text-orange-400";
    }

    if (value > 0 && value <= 5) {
        return "border-blue-500/15 bg-blue-500/[0.06] text-blue-400";
    }

    return "border-white/[0.07] bg-white/[0.025] text-gray-500";
};

/*
|--------------------------------------------------------------------------
| Chemistry
|--------------------------------------------------------------------------
*/

const chemistryPercentage = (chemistry) => {
    const value = parseFloat(chemistry);

    if (Number.isNaN(value)) {
        return 0;
    }

    return Math.min(100, Math.max(0, value));
};

const getChemistryTextClass = (chemistry) => {
    const value = Number(chemistry) || 0;

    if (value >= 80) {
        return "text-yellow-400";
    }

    if (value >= 60) {
        return "text-emerald-400";
    }

    if (value >= 40) {
        return "text-gray-400";
    }

    if (value >= 20) {
        return "text-orange-400";
    }

    return "text-rose-400";
};

const getChemistryBarClass = (chemistry) => {
    const value = Number(chemistry) || 0;

    if (value >= 80) {
        return "bg-yellow-500";
    }

    if (value >= 60) {
        return "bg-emerald-500";
    }

    if (value >= 40) {
        return "bg-gray-500";
    }

    if (value >= 20) {
        return "bg-orange-500";
    }

    return "bg-rose-500";
};

const getMoraleIcon = (chemistry) => {
    const value = Number(chemistry) || 0;

    if (value >= 80) {
        return "fa-solid fa-face-laugh-beam text-yellow-400";
    }

    if (value >= 60) {
        return "fa-solid fa-face-smile text-emerald-400";
    }

    if (value >= 40) {
        return "fa-solid fa-face-meh text-gray-400";
    }

    if (value >= 20) {
        return "fa-solid fa-face-frown text-orange-400";
    }

    return "fa-solid fa-face-angry text-rose-400";
};

const getMoraleTitle = (chemistry) => {
    const value = Number(chemistry) || 0;

    if (value >= 80) {
        return "Locked In";
    }

    if (value >= 60) {
        return "Confident";
    }

    if (value >= 40) {
        return "Steady";
    }

    if (value >= 20) {
        return "Uncertain";
    }

    return "Frustrated";
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchCoachSeasonPerformance = async () => {
    loading.value = true;

    try {
        search.value.coach_id = props.coach_id;

        const response = await axios.post(
            route("coach.season.history"),
            search.value
        );

        coach_history.value = {
            history: Array.isArray(response.data?.history)
                ? response.data.history
                : [],

            total_items:
                Number(response.data?.total_items) || 0,
        };
    } catch (error) {
        console.error(
            "Error fetching coach season performance:",
            error
        );

        coach_history.value = {
            history: [],
            total_items: 0,
        };
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;

    fetchCoachSeasonPerformance();
};

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchCoachSeasonPerformance();
});
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Horizontal scrollbar
|--------------------------------------------------------------------------
*/

.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 5px;
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

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>