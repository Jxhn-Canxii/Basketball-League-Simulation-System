<template>
    <div class="m-0 min-w-0 p-0">
        <!-- =========================================================
             VIEW TEAM BUTTON
        ========================================================== -->
        <button
            v-if="props.showButton == 1"
            type="button"
            @click.prevent="showTeamDetails"
            :disabled="isTeamModalOpen || loading"
            class="group inline-flex items-center gap-2 rounded-xl border border-blue-500/20 bg-blue-500/[0.07] px-3 py-2 text-xs font-black text-blue-400 transition-all duration-200 hover:border-blue-500/40 hover:bg-blue-500/[0.12] hover:text-blue-300 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-500/10 transition group-hover:bg-blue-500/20"
            >
                <i
                    v-if="loading"
                    class="fas fa-spinner fa-spin text-[10px]"
                ></i>

                <i
                    v-else
                    class="fas fa-eye text-[10px]"
                ></i>
            </span>

            <span>
                {{ loading ? "Loading..." : props.text ?? "View" }}
            </span>
        </button>

        <!-- =========================================================
             INLINE TEAM NAME
        ========================================================== -->
        <div
            v-else
            :class="[
                'flex min-w-0 items-center gap-2 px-2',
                props.isTitleCenter
                    ? 'justify-center'
                    : 'justify-start',
            ]"
        >
            <!-- Team Color Marker -->
            <div
                v-if="
                    props.hexPrimaryColor &&
                    props.hexSecondaryColor
                "
                class="h-5 w-5 shrink-0 overflow-hidden rounded-md border border-white/10 shadow-sm"
                :style="{
                    background: `linear-gradient(135deg, ${color(props.hexPrimaryColor)} 50%, ${color(props.hexSecondaryColor)} 50%)`,
                }"
            ></div>

            <!-- Team Name -->
            <button
                type="button"
                @click.prevent="showTeamDetails"
                class="min-w-0 truncate text-left text-xs font-black transition hover:opacity-80"
                :style="{
                    color: color(props.hexPrimaryColor),
                }"
            >
                <span>
                    {{
                        props.text === "null"
                            ? "TBD"
                            : props.text
                    }}
                </span>

                <!-- Team Information Badges -->
                <sup
                    v-if="props.showInfo"
                    class="ml-1.5 inline-flex items-center gap-1 align-middle"
                >
                    <!-- Conference Champion -->
                    <i
                        v-if="data.is_conference_champion"
                        class="fas fa-trophy text-[9px] text-blue-400"
                        title="Defending Conference Champion"
                    ></i>

                    <!-- Overall Champion -->
                    <i
                        v-if="data.is_defending_champion"
                        class="fas fa-crown text-[9px] text-yellow-400"
                        title="Defending Overall Champion"
                    ></i>

                    <!-- National Champion -->
                    <i
                        v-if="data.is_finals_champion"
                        class="fas fa-globe-americas text-[9px] text-green-400"
                        title="Defending National Champion"
                    ></i>

                    <!-- Finalist -->
                    <i
                        v-if="data.is_finalist"
                        class="fas fa-star text-[9px] text-purple-400"
                        title="Last Season Finalist"
                    ></i>

                    <!-- Finals MVP -->
                    <span
                        v-if="data.finals_mvp_count > 0"
                        class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500/15 px-1 text-[8px] font-black text-orange-400 ring-1 ring-orange-500/20"
                        title="# of Finals MVP in roster"
                    >
                        {{ data.finals_mvp_count }}
                    </span>

                    <!-- Regular Season MVP -->
                    <span
                        v-if="data.overall_mvp_count > 0"
                        class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-green-500/15 px-1 text-[8px] font-black text-green-400 ring-1 ring-green-500/20"
                        title="# of Best Player of the Season in roster"
                    >
                        {{ data.overall_mvp_count }}
                    </span>

                    <!-- DPOS -->
                    <span
                        v-if="data.dpos_count > 0"
                        class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-purple-500/15 px-1 text-[8px] font-black text-purple-400 ring-1 ring-purple-500/20"
                        title="# of Defensive Player of the Season in roster"
                    >
                        {{ data.dpos_count }}
                    </span>

                    <!-- ROS -->
                    <span
                        v-if="data.ros_count > 0"
                        class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500/15 px-1 text-[8px] font-black text-red-400 ring-1 ring-red-500/20"
                        title="# of Rookie of the Season in roster"
                    >
                        {{ data.ros_count }}
                    </span>

                    <!-- Conference Rank Movement -->
                    <span
                        v-if="
                            data.prev_conference_rank &&
                            props.current_conference_rank > 0
                        "
                        :class="rankMovementClass"
                        class="ml-1 inline-flex items-center gap-0.5 text-[8px] font-black"
                        title="Conference Rank Comparison"
                    >
                        {{ rankDifference }}

                        <i
                            v-if="
                                props.current_conference_rank <
                                data.prev_conference_rank
                            "
                            class="fas fa-arrow-up"
                        ></i>

                        <i
                            v-else-if="
                                props.current_conference_rank >
                                data.prev_conference_rank
                            "
                            class="fas fa-arrow-down"
                        ></i>

                        <i
                            v-else
                            class="fas fa-minus"
                        ></i>
                    </span>
                </sup>
            </button>
        </div>

        <!-- =========================================================
             TEAM INFORMATION MODAL
        ========================================================== -->
        <Modal
            :show="isTeamModalOpen"
            :maxWidth="'fullscreen'"
            title="Team Information"
            @close="closeModal"
        >
            <div
                v-if="team_info?.teams"
                class="min-h-0 overflow-hidden bg-black text-white"
            >
                <!-- =================================================
                     TEAM HEADER
                ================================================== -->
                <div
                    class="relative overflow-hidden border-b border-white/[0.07]"
                >
                    <!-- Team Color Glow -->
                    <div
                        class="absolute inset-0 opacity-[0.14]"
                        :style="{
                            background: `
                                radial-gradient(
                                    circle at 15% 50%,
                                    ${teamPrimaryColor} 0%,
                                    transparent 45%
                                ),
                                radial-gradient(
                                    circle at 85% 50%,
                                    ${teamSecondaryColor} 0%,
                                    transparent 45%
                                )
                            `,
                        }"
                    ></div>

                    <!-- Header Content -->
                    <div
                        class="relative flex min-w-0 items-center gap-4 px-4 py-4 sm:px-6"
                    >
                        <!-- Team Mark -->
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-black/50 shadow-xl"
                            :style="{
                                boxShadow: `0 8px 30px ${teamPrimaryColor}22`,
                            }"
                        >
                            <i
                                class="fas fa-shield-alt text-lg"
                                :style="{
                                    color: teamPrimaryColor,
                                }"
                            ></i>
                        </div>

                        <!-- Team Information -->
                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-wrap items-center gap-2"
                            >
                                <span
                                    class="truncate text-base font-black tracking-tight text-white sm:text-lg"
                                >
                                    {{
                                        team_info.teams.city
                                            ? `${team_info.teams.city} ${team_info.teams.team_name}`
                                            : team_info.teams.name
                                    }}
                                    
                                </span>

                                <span
                                    v-if="team_info.teams.acronym"
                                    class="rounded-md border border-white/10 bg-white/[0.05] px-1.5 py-0.5 text-[8px] font-black tracking-widest text-gray-400"
                                >
                                    {{ team_info.teams.acronym }}
                                </span>
                            </div>

                            <div
                                class="mt-1 flex flex-wrap items-center gap-2 text-[9px] text-gray-600"
                            >
                                <span
                                    v-if="team_info.teams.league_name"
                                >
                                    {{
                                        team_info.teams.league_name
                                    }}
                                </span>

                                <span
                                    v-if="
                                        team_info.teams.league_name &&
                                        team_info.teams.conference_name
                                    "
                                    class="text-gray-800"
                                >
                                    •
                                </span>

                                <span
                                    v-if="
                                        team_info.teams.conference_name
                                    "
                                >
                                    {{
                                        team_info.teams
                                            .conference_name
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Colors -->
                        <div
                            class="hidden items-center gap-1.5 sm:flex"
                        >
                            <div
                                class="h-7 w-7 rounded-lg border border-white/10 shadow"
                                :style="{
                                    backgroundColor:
                                        teamPrimaryColor,
                                }"
                                :title="team_info.teams.primary_color"
                            ></div>

                            <div
                                class="h-7 w-7 rounded-lg border border-white/10 shadow"
                                :style="{
                                    backgroundColor:
                                        teamSecondaryColor,
                                }"
                                :title="
                                    team_info.teams
                                        .secondary_color
                                "
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- =================================================
                     TAB NAVIGATION
                ================================================== -->
                <div
                    class="border-b border-white/[0.07] bg-[#080808]"
                >
                    <div
                        class="overflow-x-auto scrollbar-thin"
                    >
                        <div
                            class="flex min-w-max px-2 sm:px-4"
                        >
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                @click="currentTab = tab.key"
                                :class="[
                                    'relative flex items-center gap-2 px-3 py-3 text-[9px] font-black uppercase tracking-wider transition-all duration-200 sm:px-4',
                                    currentTab === tab.key
                                        ? 'text-white'
                                        : 'text-gray-600 hover:text-gray-300',
                                ]"
                            >
                                <i
                                    :class="[
                                        tab.icon,
                                        'text-[10px]',
                                        currentTab ===
                                        tab.key
                                            ? 'text-yellow-500'
                                            : 'text-gray-700',
                                    ]"
                                ></i>

                                <span>
                                    {{ tab.label }}
                                </span>

                                <!-- Active Indicator -->
                                <span
                                    v-if="
                                        currentTab === tab.key
                                    "
                                    class="absolute bottom-0 left-2 right-2 h-0.5 rounded-full bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.25)]"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- =================================================
                     TAB CONTENT
                ================================================== -->
                <div class="min-w-0 bg-black">
                    <TeamInfo
                        v-if="currentTab === 'info'"
                        :key="`info-${props.team_id}`"
                        :team_id="props.team_id"
                    />

                    <TeamHistory
                        v-else-if="currentTab === 'history'"
                        :key="`history-${props.team_id}`"
                        :team_id="props.team_id"
                    />

                    <TeamRoster
                        v-else-if="currentTab === 'roster'"
                        :key="`roster-${props.team_id}`"
                        :team_id="props.team_id"
                    />

                    <TeamTransactions
                        v-else-if="currentTab === 'transactions'"
                        :key="`transactions-${props.team_id}`"
                        :team_id="props.team_id"
                    />

                    <Stars
                        v-else-if="currentTab === 'stars'"
                        :key="`stars-${props.team_id}`"
                        :team_id="props.team_id"
                    />

                    <SeasonTimeLine
                        v-else-if="currentTab === 'timeline'"
                        :key="`timeline-${props.team_id}`"
                        :teamId="props.team_id"
                    />

                    <Top15Player
                        v-else-if="currentTab === 'legend'"
                        :key="`legend-${props.team_id}`"
                        :team_id="props.team_id"
                    />
                </div>
            </div>

            <!-- Loading State -->
            <div
                v-else
                class="flex min-h-[320px] items-center justify-center bg-black text-white"
            >
                <div
                    class="flex flex-col items-center gap-3"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-yellow-500/20 bg-yellow-500/[0.06]"
                    >
                        <i
                            class="fas fa-spinner fa-spin text-yellow-500"
                        ></i>
                    </div>

                    <div class="text-center">
                        <div
                            class="text-xs font-black text-gray-300"
                        >
                            Loading Team
                        </div>

                        <div
                            class="mt-1 text-[9px] text-gray-700"
                        >
                            Fetching team information...
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";

import Modal from "@/Components/Modal.vue";

import TeamInfo from "./TeamInfo.vue";
import TeamHistory from "./TeamHistory.vue";
import TeamRoster from "./TeamRoster.vue";
import Top15Player from "./Top15Player.vue";
import TeamTransactions from "./TeamTransactions.vue";
import Stars from "./Stars.vue";

import SeasonTimeLine from "@/Pages/Analytics/Module/SeasonTimeLine.vue";

const props = defineProps({
    team_id: {
        type: Number,
        default: 0,
    },

    showButton: {
        type: Number,
        default: 0,
    },

    season_id: {
        type: Number,
        default: 0,
    },

    current_conference_rank: {
        type: Number,
        default: 0,
    },

    text: {
        type: String,
        default: "",
    },

    hexPrimaryColor: {
        type: [String, Boolean],
        default: false,
    },

    hexSecondaryColor: {
        type: [String, Boolean],
        default: false,
    },

    showInfo: {
        type: Boolean,
        default: false,
    },

    isTitleCenter: {
        type: Boolean,
        default: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const isTeamModalOpen = ref(false);
const currentTab = ref("info");
const team_info = ref(null);
const loading = ref(false);

/*
|--------------------------------------------------------------------------
| Team Performance Data
|--------------------------------------------------------------------------
*/

const data = ref({
    is_conference_champion: false,
    is_defending_champion: false,
    is_finals_champion: false,
    is_finalist: false,

    finals_mvp_count: 0,
    overall_mvp_count: 0,
    dpos_count: 0,
    ros_count: 0,

    team_one_pick_count: 0,
    current_conference_rank: 0,
    prev_conference_rank: 0,
});

/*
|--------------------------------------------------------------------------
| Tabs
|--------------------------------------------------------------------------
*/

const tabs = [
    {
        key: "info",
        label: "Team Info",
        icon: "fas fa-info-circle",
    },
    {
        key: "history",
        label: "Team History",
        icon: "fas fa-history",
    },
    {
        key: "roster",
        label: "Team Roster",
        icon: "fas fa-users-cog",
    },
    {
        key: "transactions",
        label: "Transactions",
        icon: "fas fa-exchange-alt",
    },
    {
        key: "stars",
        label: "Team Stars",
        icon: "fas fa-star",
    },
    {
        key: "timeline",
        label: "Season Timeline",
        icon: "fas fa-clock",
    },
    {
        key: "legend",
        label: "Top 15 Players",
        icon: "fas fa-trophy",
    },
];

/*
|--------------------------------------------------------------------------
| Team Colors
|--------------------------------------------------------------------------
*/

const color = (value) => {
    if (!value) {
        return "#ffffff";
    }

    const cleaned = String(value)
        .replace("#", "")
        .replace(/[^0-9a-fA-F]/g, "")
        .substring(0, 6);

    return cleaned
        ? `#${cleaned}`
        : "#ffffff";
};

const teamPrimaryColor = computed(() => {
    return color(
        team_info.value?.teams?.primary_color
    );
});

const teamSecondaryColor = computed(() => {
    return color(
        team_info.value?.teams?.secondary_color
    );
});

/*
|--------------------------------------------------------------------------
| Rank Movement
|--------------------------------------------------------------------------
*/

const rankDifference = computed(() => {
    if (
        !data.value.prev_conference_rank ||
        !props.current_conference_rank
    ) {
        return 0;
    }

    return (
        data.value.prev_conference_rank -
        props.current_conference_rank
    );
});

const rankMovementClass = computed(() => {
    if (rankDifference.value > 0) {
        return "text-green-400";
    }

    if (rankDifference.value < 0) {
        return "text-red-400";
    }

    return "text-gray-400";
});

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    if (props.showInfo) {
        fetchTeamRecentPerFormance();
    }
});

/*
|--------------------------------------------------------------------------
| Modal
|--------------------------------------------------------------------------
*/

const showTeamDetails = async () => {
    if (loading.value) {
        return;
    }

    await fetchTeamInfo();

    if (team_info.value?.teams) {
        currentTab.value = "info";
        isTeamModalOpen.value = true;
    }
};

const closeModal = () => {
    isTeamModalOpen.value = false;
};

/*
|--------------------------------------------------------------------------
| Team Information
|--------------------------------------------------------------------------
*/

const fetchTeamInfo = async () => {
    if (!props.team_id) {
        return;
    }

    try {
        loading.value = true;
        team_info.value = null;

        const response = await axios.post(
            route("teams.info"),
            {
                team_id: props.team_id,
            }
        );

        team_info.value = response?.data ?? null;
    } catch (error) {
        console.error(
            "Error fetching team info:",
            error
        );

        team_info.value = null;
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Recent Performance
|--------------------------------------------------------------------------
*/

const fetchTeamRecentPerFormance = async () => {
    if (!props.team_id) {
        return;
    }

    try {
        const response = await axios.post(
            route("team.recent.performance"),
            {
                team_id: props.team_id,
                season_id: props.season_id,
            }
        );

        if (response?.data) {
            data.value = {
                ...data.value,
                ...response.data,
            };
        }
    } catch (error) {
        console.error(
            "Error fetching team performance:",
            error
        );
    }
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Horizontal Scrollbar
|--------------------------------------------------------------------------
*/

.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    height: 5px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.18);
}

/*
|--------------------------------------------------------------------------
| General Dark Scrollbar
|--------------------------------------------------------------------------
*/

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.18);
}
</style>