```vue
<template>
    <div
        class="min-h-screen text-white overflow-hidden"
        v-if="team_info?.teams && !loading"
        :style="pageStyle"
    >
        <!-- =====================================================
             HEADER
        ====================================================== -->
        <section
            class="relative overflow-hidden border-b border-white/10"
            :style="heroStyle"
        >
            <!-- Decorative background -->
            <div class="absolute inset-0 pointer-events-none">
                <div
                    class="absolute -right-32 -top-40 w-96 h-96 rounded-full blur-3xl opacity-20"
                    :style="{ backgroundColor: primaryColor }"
                ></div>

                <div
                    class="absolute -left-40 -bottom-40 w-96 h-96 rounded-full blur-3xl opacity-10"
                    :style="{ backgroundColor: primaryColor }"
                ></div>
            </div>

            <div class="relative px-4 sm:px-6 lg:px-8 py-5">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <!-- Team -->
                    <div class="flex items-center gap-4 min-w-0">
                        <!-- Team badge -->
                        <div
                            class="flex-shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-2xl overflow-hidden border border-white/20 shadow-xl flex items-center justify-center"
                            :style="teamBadgeStyle"
                        >
                            <span
                                class="text-sm sm:text-base font-black text-white drop-shadow-lg"
                            >
                                {{
                                    team_info.teams.acronym ??
                                    "TEAM"
                                }}
                            </span>
                        </div>

                        <!-- Name -->
                        <div class="min-w-0">
                            <div
                                class="text-[9px] sm:text-[10px] uppercase tracking-[0.2em] text-white/35 font-bold"
                            >
                                Franchise History
                            </div>

                            <h1
                                class="mt-1 text-2xl sm:text-3xl font-black tracking-tight truncate"
                            >
                                {{
                                    team_info.teams.team_name ??
                                    "-"
                                }}
                            </h1>

                            <div
                                class="mt-1 flex flex-wrap items-center gap-2"
                            >
                                <span
                                    class="text-sm text-white/50"
                                >
                                    {{
                                        team_info.teams.acronym ??
                                        "-"
                                    }}
                                </span>

                                <span
                                    class="text-white/20"
                                >
                                    •
                                </span>

                                <span
                                    class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide"
                                    :style="conferenceBadgeStyle"
                                >
                                    {{
                                        team_info.teams
                                            .conference_name ??
                                        "-"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right summary -->
                    <div
                        class="hidden sm:flex items-center gap-3"
                    >
                        <div
                            class="text-right px-4 py-3 rounded-xl bg-black/20 border border-white/10"
                        >
                            <div
                                class="text-[9px] uppercase tracking-widest text-white/30"
                            >
                                Seasons
                            </div>

                            <div
                                class="mt-1 text-xl font-black"
                            >
                                {{
                                    team_history?.total_items ??
                                    0
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =====================================================
             CONTENT
        ====================================================== -->
        <main class="p-3 sm:p-5 lg:p-8">
            <!-- Section heading -->
            <div
                class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-white/30 text-[9px] uppercase tracking-[0.2em] font-bold"
                    >
                        <i class="fas fa-chart-line"></i>
                        Franchise Timeline
                    </div>

                    <h2
                        class="mt-1 text-xl sm:text-2xl font-black"
                    >
                        Season History
                    </h2>

                    <p
                        class="mt-1 text-xs sm:text-sm text-white/35"
                    >
                        Complete regular season and postseason
                        performance by year.
                    </p>
                </div>

                <!-- Current team colors -->
                <div
                    class="flex items-center gap-2"
                >
                    <span
                        class="text-[9px] uppercase tracking-widest text-white/25"
                    >
                        Team Colors
                    </span>

                    <span
                        class="w-6 h-6 rounded-lg border border-white/20 shadow"
                        :style="{
                            backgroundColor: primaryColor
                        }"
                    ></span>

                    <span
                        class="w-6 h-6 rounded-lg border border-white/20 shadow"
                        :style="{
                            backgroundColor: secondaryColor
                        }"
                    ></span>
                </div>
            </div>

            <!-- =================================================
                 TABLE CARD
            ================================================== -->
            <section
                class="rounded-2xl border border-white/10 bg-white/[0.035] shadow-2xl overflow-hidden"
            >
                <!-- Table toolbar -->
                <div
                    class="px-4 sm:px-5 py-3 border-b border-white/10 bg-black/10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div
                        class="flex items-center gap-2"
                    >
                        <div
                            class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center"
                        >
                            <i
                                class="fas fa-calendar-alt text-white/40 text-xs"
                            ></i>
                        </div>

                        <div>
                            <div
                                class="text-xs font-bold text-white/80"
                            >
                                Franchise Seasons
                            </div>

                            <div
                                class="text-[10px] text-white/30"
                            >
                                Click a season to view its
                                complete schedule
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-2 text-[10px] text-white/30"
                    >
                        <i
                            class="fas fa-mouse-pointer"
                        ></i>

                        Select a season
                    </div>
                </div>

                <!-- =================================================
                     HORIZONTAL TABLE
                ================================================== -->
                <div
                    class="overflow-x-auto custom-scrollbar"
                >
                    <table
                        class="min-w-[1500px] w-full border-collapse"
                    >
                        <!-- Header -->
                        <thead>
                            <tr
                                class="border-b border-white/10"
                                :style="tableHeaderStyle"
                            >
                                <th
                                    class="sticky left-0 z-20 px-4 py-3 text-left text-[9px] uppercase tracking-wider font-black whitespace-nowrap bg-[#111113]"
                                >
                                    Season
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Record
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Conference Rank
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Conference Result
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    National Rank
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    National Result
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Finals
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Coach
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Chemistry
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    Last Game
                                </th>

                                <th
                                    class="px-4 py-3 text-center text-[9px] uppercase tracking-wider font-black whitespace-nowrap"
                                >
                                    View
                                </th>
                            </tr>
                        </thead>

                        <!-- Body -->
                        <tbody>
                            <tr
                                v-for="season in team_history.history"
                                :key="season.season"
                                @click.prevent="
                                    openSeasonSchedule(
                                        season.season_id
                                    )
                                "
                                class="group border-b border-white/5 cursor-pointer transition-all duration-150 hover:bg-white/[0.045]"
                            >
                                <!-- =================================================
                                     SEASON
                                ================================================== -->
                                <td
                                    class="sticky left-0 z-10 px-4 py-4 whitespace-nowrap bg-[#0d0d0f] group-hover:bg-[#151518] border-r border-white/5"
                                >
                                    <div
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="w-1 h-8 rounded-full"
                                            :style="{
                                                backgroundColor:
                                                    primaryColor
                                            }"
                                        ></div>

                                        <div>
                                            <div
                                                class="font-black text-white"
                                            >
                                                {{
                                                    season.season_name ??
                                                    "-"
                                                }}
                                            </div>

                                            <div
                                                class="text-[9px] uppercase tracking-wider text-white/25"
                                            >
                                                Season
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- =================================================
                                     RECORD
                                ================================================== -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap"
                                >
                                    <div
                                        class="flex justify-center gap-4"
                                    >
                                        <div
                                            class="text-center"
                                        >
                                            <div
                                                class="text-base font-black text-emerald-400"
                                            >
                                                {{
                                                    season.wins ??
                                                    0
                                                }}
                                            </div>

                                            <div
                                                class="text-[8px] uppercase tracking-wider text-white/25"
                                            >
                                                W
                                            </div>
                                        </div>

                                        <div
                                            class="text-center"
                                        >
                                            <div
                                                class="text-base font-black text-red-400"
                                            >
                                                {{
                                                    season.losses ??
                                                    0
                                                }}
                                            </div>

                                            <div
                                                class="text-[8px] uppercase tracking-wider text-white/25"
                                            >
                                                L
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- =================================================
                                     CONFERENCE RANK
                                ================================================== -->
                                <td
                                    class="px-4 py-4 text-center whitespace-nowrap"
                                >
                                    <RankBadge
                                        :rank="
                                            season.conference_rank
                                        "
                                        :label="'CONF'"
                                    />
                                </td>

                                <!-- =================================================
                                     CONFERENCE RESULT
                                ================================================== -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap"
                                >
                                    <AwardBadge
                                        :rank="
                                            season.conference_rank
                                        "
                                        type="conference"
                                    />
                                </td>

                                <!-- =================================================
                                     NATIONAL RANK
                                ================================================== -->
                                <td
                                    class="px-4 py-4 text-center whitespace-nowrap"
                                >
                                    <RankBadge
                                        :rank="
                                            season.overall_rank
                                        "
                                        :label="'NAT'"
                                    />
                                </td>

                                <!-- =================================================
                                     NATIONAL RESULT
                                ================================================== -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap"
                                >
                                    <AwardBadge
                                        :rank="
                                            season.overall_rank
                                        "
                                        type="national"
                                    />
                                </td>

                                <!-- =================================================
                                     FINALS
                                ================================================== -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap"
                                >
                                    <span
                                        v-if="
                                            season.round_info
                                                ?.won &&
                                            season.round_info
                                                ?.round ===
                                                'finals'
                                        "
                                        class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold"
                                    >
                                        <i
                                            class="fas fa-trophy"
                                        ></i>
                                        Champion
                                    </span>

                                    <span
                                        v-else
                                        class="text-[10px] text-white/20"
                                    >
                                        —
                                    </span>
                                </td>

                                <!-- =================================================
                                     COACH
                                ================================================== -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap"
                                >
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center"
                                        >
                                            <i
                                                class="fas fa-user-tie text-white/30 text-xs"
                                            ></i>
                                        </div>

                                        <div>
                                            <div
                                                class="text-xs font-bold text-white/80"
                                            >
                                                {{
                                                    season
                                                        .coach_info
                                                        ?.coach_name ??
                                                    "-"
                                                }}
                                            </div>

                                            <div
                                                class="text-[9px] text-white/25"
                                            >
                                                Head Coach
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- =================================================
                                     CHEMISTRY
                                ================================================== -->
                                <td
                                    class="px-4 py-4 text-center whitespace-nowrap"
                                >
                                    <div
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white/5 border border-white/10"
                                    >
                                        <i
                                            class="fas fa-flask text-xs"
                                            :style="{
                                                color: primaryColor
                                            }"
                                        ></i>

                                        <span
                                            class="text-xs font-bold"
                                        >
                                            {{
                                                season
                                                    .coach_info
                                                    ?.chemistry ??
                                                "-"
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- =================================================
                                     LAST GAME
                                ================================================== -->
                                <td
                                    class="px-4 py-4 whitespace-nowrap max-w-[350px]"
                                >
                                    <div
                                        v-if="
                                            season.season_status >
                                            11
                                        "
                                        class="flex items-start gap-2"
                                    >
                                        <div
                                            class="flex-shrink-0 w-7 h-7 rounded-lg flex items-center justify-center"
                                            :class="
                                                season
                                                    .round_info
                                                    ?.won
                                                    ? 'bg-emerald-500/10 text-emerald-400'
                                                    : 'bg-red-500/10 text-red-400'
                                            "
                                        >
                                            <i
                                                :class="
                                                    season
                                                        .round_info
                                                        ?.won
                                                        ? 'fas fa-check'
                                                        : 'fas fa-xmark'
                                                "
                                                class="text-xs"
                                            ></i>
                                        </div>

                                        <div
                                            class="min-w-0"
                                        >
                                            <div
                                                class="text-xs text-white/70 truncate"
                                            >
                                                {{
                                                    season
                                                        .round_info
                                                        ?.won
                                                        ? "Won"
                                                        : "Lost"
                                                }}
                                                vs
                                                <span
                                                    class="font-bold text-white"
                                                >
                                                    {{
                                                        season
                                                            .round_info
                                                            ?.opponent_name ??
                                                        "-"
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-0.5 text-[9px] text-white/25"
                                            >
                                                {{
                                                    formatRound(
                                                        season
                                                    )
                                                }}

                                                <span
                                                    class="mx-1"
                                                >
                                                    •
                                                </span>

                                                <span
                                                    class="font-bold text-white/50"
                                                >
                                                    {{
                                                        season
                                                            .round_info
                                                            ?.score ??
                                                        "-"
                                                    }}
                                                    -
                                                    {{
                                                        season
                                                            .round_info
                                                            ?.opponent_score ??
                                                        "-"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <span
                                        v-else
                                        class="text-xs text-white/20"
                                    >
                                        Season in progress
                                    </span>
                                </td>

                                <!-- =================================================
                                     VIEW
                                ================================================== -->
                                <td
                                    class="px-4 py-4 text-center"
                                >
                                    <div
                                        class="w-8 h-8 mx-auto rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white/20 group-hover:text-white group-hover:bg-white/10 transition-all"
                                    >
                                        <i
                                            class="fas fa-chevron-right text-xs"
                                        ></i>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty -->
                <div
                    v-if="
                        !team_history?.history?.length
                    "
                    class="py-16 text-center"
                >
                    <div
                        class="w-12 h-12 mx-auto rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white/20"
                    >
                        <i
                            class="fas fa-calendar-xmark"
                        ></i>
                    </div>

                    <p
                        class="mt-3 text-sm text-white/30"
                    >
                        No season history available.
                    </p>
                </div>

                <!-- =================================================
                     PAGINATION
                ================================================== -->
                <div
                    v-if="team_history?.total_items"
                    class="px-4 sm:px-5 py-3 border-t border-white/10 bg-black/10"
                >
                    <Paginator
                        :page_number="
                            search.page_num
                        "
                        :total_rows="
                            team_history.total_items ??
                            0
                        "
                        :itemsperpage="
                            search.itemsperpage
                        "
                        @page_num="handlePagination"
                    />
                </div>
            </section>

            <!-- Hint -->
            <div
                class="flex items-center justify-center gap-2 mt-3 text-[10px] text-white/20"
            >
                <i
                    class="fas fa-arrows-left-right"
                ></i>

                Scroll horizontally to view all season
                statistics
            </div>
        </main>
    </div>

    <!-- =========================================================
         LOADING
    ========================================================== -->
    <div
        v-else
        class="min-h-screen bg-[#09090b] p-3 sm:p-5 lg:p-8 animate-pulse"
    >
        <!-- Header skeleton -->
        <div
            class="rounded-2xl border border-white/10 bg-white/[0.035] p-5"
        >
            <div
                class="flex items-center gap-4"
            >
                <div
                    class="w-16 h-16 rounded-2xl bg-white/10"
                ></div>

                <div class="space-y-3">
                    <div
                        class="h-2.5 w-28 bg-white/10 rounded"
                    ></div>

                    <div
                        class="h-7 w-64 max-w-[60vw] bg-white/10 rounded"
                    ></div>

                    <div
                        class="h-3 w-32 bg-white/10 rounded"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Content skeleton -->
        <div class="mt-5">
            <div
                class="h-5 w-48 bg-white/10 rounded mb-2"
            ></div>

            <div
                class="h-3 w-72 max-w-full bg-white/5 rounded mb-4"
            ></div>

            <div
                class="rounded-2xl border border-white/10 bg-white/[0.035] overflow-hidden"
            >
                <div
                    class="h-12 bg-white/5"
                ></div>

                <div
                    v-for="n in 8"
                    :key="n"
                    class="h-16 border-t border-white/5"
                ></div>
            </div>
        </div>
    </div>

    <!-- =========================================================
         SEASON SCHEDULE MODAL
    ========================================================== -->
    <Modal
        :show="showTeamSchedule"
        :maxWidth="'6xl'"
        :title="
            'Season ' +
            showTeamSchedule +
            ' Match Results'
        "
        @close="showTeamSchedule = false"
    >
        <div class="p-3 sm:p-6">
            <TeamSchedule
                :season_id="showTeamSchedule"
                :team_id="props.team_id"
                :key="showTeamSchedule"
            />
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";

import { roundNameFormatter } from "@/Utility/Formatter";

import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import TeamSchedule from "./TeamSchedule.vue";

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const showTeamSchedule = ref(false);

const team_history = ref([]);
const team_info = ref([]);

const loading = ref(false);

const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 10,
    team_id: props.team_id,
});

/*
|--------------------------------------------------------------------------
| Colors
|--------------------------------------------------------------------------
*/

const primaryColor = computed(() => {
    const color =
        team_info.value?.teams?.primary_color;

    if (!color) return "#ffffff";

    return `#${String(color).replace("#", "")}`;
});

const secondaryColor = computed(() => {
    const color =
        team_info.value?.teams?.secondary_color;

    if (!color) return "#09090b";

    return `#${String(color).replace("#", "")}`;
});

const pageStyle = computed(() => ({
    background: `
        radial-gradient(
            circle at 10% 0%,
            ${primaryColor.value}18 0%,
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #09090b 0%,
            ${secondaryColor.value}45 50%,
            #09090b 100%
        )
    `,
}));

const heroStyle = computed(() => ({
    background: `
        linear-gradient(
            135deg,
            ${secondaryColor.value} 0%,
            ${secondaryColor.value}cc 55%,
            #09090b 100%
        )
    `,
}));

const teamBadgeStyle = computed(() => ({
    background: `
        linear-gradient(
            135deg,
            ${primaryColor.value} 0%,
            ${primaryColor.value}cc 48%,
            ${secondaryColor.value} 49%,
            ${secondaryColor.value} 100%
        )
    `,
}));

const conferenceBadgeStyle = computed(() => ({
    color: primaryColor.value,
    backgroundColor:
        primaryColor.value + "18",
    border:
        "1px solid " +
        primaryColor.value +
        "30",
}));

const tableHeaderStyle = computed(() => ({
    background: `
        linear-gradient(
            90deg,
            ${primaryColor.value}15,
            transparent
        )
    `,
}));

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const openSeasonSchedule = (seasonId) => {
    if (!seasonId) return;

    showTeamSchedule.value = seasonId;
};

const formatRound = (season) => {
    if (!season?.round_info?.round) {
        return "-";
    }

    const round =
        season.round_info.round;

    const prefix = isNumberChecker(round)
        ? "Round "
        : "";

    return (
        prefix +
        roundNameFormatter(round)
    );
};

const isNumberChecker = (round) => {
    return (
        !isNaN(round) &&
        !isNaN(parseFloat(round))
    );
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchData();
});

watch(
    () => props.team_id,
    async (newId, oldId) => {
        if (newId !== oldId) {
            search.value.team_id = newId;
            search.value.page_num = 1;

            await fetchData();
        }
    }
);

const fetchData = async () => {
    loading.value = true;

    try {
        await Promise.all([
            fetchTeamHistory(),
            fetchTeamInfo(),
        ]);
    } catch (error) {
        console.error(
            "Error fetching team data:",
            error
        );
    } finally {
        loading.value = false;
    }
};

const fetchTeamInfo = async () => {
    try {
        const response =
            await axios.post(
                route("teams.info"),
                {
                    team_id:
                        props.team_id,
                }
            );

        team_info.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching team info:",
            error
        );
    }
};

const fetchTeamHistory = async () => {
    try {
        search.value.team_id =
            props.team_id;

        const response =
            await axios.post(
                route(
                    "teams.season.history"
                ),
                search.value
            );

        team_history.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching team history:",
            error
        );
    }
};

const handlePagination = (
    page_num
) => {
    search.value.page_num =
        page_num ?? 1;

    fetchTeamHistory();
};
</script>

<script>
/*
|--------------------------------------------------------------------------
| Presentation Components
|--------------------------------------------------------------------------
*/

export default {
    components: {
        RankBadge: {
            props: {
                rank: [Number, String],
                label: String,
            },

            computed: {
                numericRank() {
                    return Number(
                        this.rank
                    );
                },

                rankClass() {
                    if (
                        this.numericRank ===
                        1
                    ) {
                        return "bg-yellow-400/10 text-yellow-400 border-yellow-400/20";
                    }

                    if (
                        this.numericRank ===
                        2
                    ) {
                        return "bg-slate-300/10 text-slate-300 border-slate-300/20";
                    }

                    if (
                        this.numericRank ===
                        3
                    ) {
                        return "bg-amber-700/10 text-amber-500 border-amber-700/20";
                    }

                    return "bg-white/5 text-white/60 border-white/10";
                },
            },

            template: `
                <div
                    class="inline-flex flex-col items-center justify-center min-w-[50px] px-2 py-1.5 rounded-lg border"
                    :class="rankClass"
                >
                    <span class="text-base font-black leading-none">
                        {{ rank ?? '-' }}
                    </span>

                    <span class="mt-1 text-[7px] uppercase tracking-wider opacity-50">
                        {{ label }}
                    </span>
                </div>
            `,
        },

        AwardBadge: {
            props: {
                rank: [Number, String],
                type: String,
            },

            computed: {
                numericRank() {
                    return Number(
                        this.rank
                    );
                },

                isConference() {
                    return (
                        this.type ===
                        "conference"
                    );
                },

                label() {
                    if (
                        this.numericRank ===
                        1
                    ) {
                        return this.isConference
                            ? "Conference Champion"
                            : "National Champion";
                    }

                    if (
                        this.numericRank ===
                        2
                    ) {
                        return this.isConference
                            ? "Conference Runner Up"
                            : "National Runner Up";
                    }

                    if (
                        this.numericRank ===
                        3
                    ) {
                        return this.isConference
                            ? "Conference 3rd Place"
                            : "National 3rd Place";
                    }

                    return "No Award";
                },

                icon() {
                    if (
                        this.numericRank ===
                        1
                    ) {
                        return "fas fa-trophy";
                    }

                    if (
                        this.numericRank ===
                        2
                    ) {
                        return "fas fa-medal";
                    }

                    if (
                        this.numericRank ===
                        3
                    ) {
                        return "fas fa-medal";
                    }

                    return "fas fa-minus";
                },

                badgeClass() {
                    if (
                        this.numericRank ===
                        1
                    ) {
                        return "bg-yellow-400/10 text-yellow-400 border-yellow-400/20";
                    }

                    if (
                        this.numericRank ===
                        2
                    ) {
                        return "bg-slate-300/10 text-slate-300 border-slate-300/20";
                    }

                    if (
                        this.numericRank ===
                        3
                    ) {
                        return "bg-amber-700/10 text-amber-500 border-amber-700/20";
                    }

                    return "bg-white/5 text-white/25 border-white/5";
                },
            },

            template: `
                <span
                    class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-[9px] font-bold"
                    :class="badgeClass"
                >
                    <i :class="icon"></i>
                    {{ label }}
                </span>
            `,
        },
    },
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Table scrollbar
|--------------------------------------------------------------------------
*/

.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.15)
        transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    height: 7px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 999px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
}

/*
|--------------------------------------------------------------------------
| Prevent table content from collapsing
|--------------------------------------------------------------------------
*/

table {
    font-variant-numeric: tabular-nums;
}

th,
td {
    vertical-align: middle;
}
</style>
