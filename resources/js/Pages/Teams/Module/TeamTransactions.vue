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
                    )}22, transparent 60%)`,
                }"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <!-- Team Initial -->
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
                                    class="text-sm sm:text-base font-black text-white"
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
                                        <i class="fas fa-map-marker-alt text-slate-500"></i>
                                        {{ team_info.teams.conference_name ?? "-" }}
                                    </span>

                                    <span
                                        class="text-[10px] uppercase tracking-widest text-slate-600"
                                    >
                                        Transaction History
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction count -->
                    <div
                        class="self-start sm:self-auto flex items-center gap-3 px-3 py-2.5 rounded-xl border border-slate-800 bg-slate-900"
                    >
                        <div
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-500/10"
                        >
                            <i class="fas fa-exchange-alt text-xs text-indigo-400"></i>
                        </div>

                        <div>
                            <p
                                class="text-[9px] uppercase tracking-wider text-slate-600"
                            >
                                Records
                            </p>

                            <p class="text-sm font-black text-white">
                                {{ team_history?.total_items ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- CONTENT -->
            <!-- ===================================================== -->
            <div class="p-3 sm:p-4">
                <!-- Section Header -->
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="w-1.5 h-6 rounded-full bg-indigo-500"
                            ></span>

                            <h3
                                class="text-sm sm:text-base font-bold text-white"
                            >
                                Team Transactions
                            </h3>
                        </div>

                        <p class="mt-1 ml-3.5 text-[10px] sm:text-xs text-slate-600">
                            Player movements, signings, waivers and roster activity
                        </p>
                    </div>

                    <!-- Current page indicator -->
                    <div
                        class="self-start md:self-auto inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800"
                    >
                        <span class="text-[9px] uppercase tracking-wider text-slate-600">
                            Page
                        </span>

                        <span class="text-xs font-bold text-slate-300">
                            {{ search.page_num }}
                        </span>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- TRANSACTION TABLE -->
                <!-- ================================================= -->
                <div
                    class="rounded-xl border border-slate-800 bg-slate-950 overflow-hidden"
                >
                    <!-- Table scroll container -->
                    <div class="overflow-x-auto">
                        <table class="min-w-[1050px] w-full">
                            <thead>
                                <tr
                                    class="bg-slate-900 border-b border-slate-800"
                                >
                                    <th class="table-header text-left">
                                        Season
                                    </th>

                                    <th class="table-header text-left">
                                        Player
                                    </th>

                                    <th class="table-header text-left">
                                        Movement
                                    </th>

                                    <th class="table-header text-left">
                                        Details
                                    </th>

                                    <th class="table-header text-center">
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-800/70">
                                <tr
                                    v-for="transaction in team_history?.transactions ?? []"
                                    :key="transaction.id"
                                    tabindex="0"
                                    role="button"
                                    @click="
                                        transaction.player_id
                                            ? (showPlayerProfileModal =
                                                  transaction.player_id)
                                            : null
                                    "
                                    @keydown.enter="
                                        transaction.player_id
                                            ? (showPlayerProfileModal =
                                                  transaction.player_id)
                                            : null
                                    "
                                    class="group transition-all duration-150"
                                    :class="
                                        transaction.player_id
                                            ? 'cursor-pointer hover:bg-slate-900/90 focus:bg-slate-900/90 focus:outline-none'
                                            : 'cursor-default hover:bg-slate-900/50'
                                    "
                                >
                                    <!-- Season -->
                                    <td class="table-cell">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-xs font-bold text-slate-300"
                                            >
                                                Season
                                                {{
                                                    transaction.season_id ?? "-"
                                                }}
                                            </span>

                                            <span
                                                class="mt-0.5 text-[9px] text-slate-600"
                                            >
                                                #{{ transaction.id }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Player -->
                                    <td class="table-cell">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-lg border border-slate-800 bg-slate-900"
                                            >
                                                <i
                                                    :class="
                                                        transaction.player_id
                                                            ? 'fas fa-user text-slate-500'
                                                            : 'fas fa-users text-slate-600'
                                                    "
                                                    class="text-[10px]"
                                                ></i>
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="text-xs sm:text-sm font-semibold truncate max-w-[190px]"
                                                    :class="
                                                        transaction.player_id
                                                            ? 'text-slate-200 group-hover:text-white'
                                                            : 'text-slate-500'
                                                    "
                                                >
                                                    {{
                                                        transaction.player_name ??
                                                        "Staff"
                                                    }}
                                                </p>

                                                <p
                                                    v-if="transaction.player_id"
                                                    class="text-[9px] text-slate-600"
                                                >
                                                    Player #{{ transaction.player_id }}
                                                </p>

                                                <p
                                                    v-else
                                                    class="text-[9px] text-slate-600"
                                                >
                                                    Team transaction
                                                </p>
                                            </div>

                                            <i
                                                v-if="transaction.player_id"
                                                class="fas fa-chevron-right text-[8px] text-slate-700 opacity-0 group-hover:opacity-100 transition-opacity"
                                            ></i>
                                        </div>
                                    </td>

                                    <!-- Movement -->
                                    <td class="table-cell">
                                        <div
                                            class="flex items-center gap-2 min-w-[260px]"
                                        >
                                            <!-- From -->
                                            <div
                                                class="flex-1 min-w-0"
                                            >
                                                <p
                                                    class="mb-1 text-[8px] uppercase tracking-wider text-slate-600"
                                                >
                                                    From
                                                </p>

                                                <span
                                                    class="block text-xs font-semibold truncate"
                                                    :class="
                                                        isFreeAgent(
                                                            transaction.from_team_name
                                                        )
                                                            ? 'text-emerald-400'
                                                            : 'text-slate-300'
                                                    "
                                                >
                                                    {{
                                                        transaction.from_team_name ??
                                                        "Free Agent"
                                                    }}
                                                </span>
                                            </div>

                                            <!-- Arrow -->
                                            <div
                                                class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-slate-800 border border-slate-700"
                                            >
                                                <i
                                                    class="fas fa-arrow-right text-[9px] text-slate-500"
                                                ></i>
                                            </div>

                                            <!-- To -->
                                            <div
                                                class="flex-1 min-w-0"
                                            >
                                                <p
                                                    class="mb-1 text-[8px] uppercase tracking-wider text-slate-600"
                                                >
                                                    To
                                                </p>

                                                <span
                                                    class="block text-xs font-semibold truncate"
                                                    :class="
                                                        isFreeAgent(
                                                            transaction.to_team_name
                                                        )
                                                            ? 'text-emerald-400'
                                                            : 'text-slate-300'
                                                    "
                                                >
                                                    {{
                                                        transaction.to_team_name ??
                                                        "Free Agent"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Details -->
                                    <td class="table-cell">
                                        <div
                                            class="max-w-[360px] text-xs leading-relaxed text-slate-400 whitespace-normal"
                                        >
                                            <span
                                                class="capitalize"
                                            >
                                                {{
                                                    formatDetails(
                                                        transaction.details
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="table-cell text-center">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border"
                                            :class="
                                                statusClass(
                                                    transaction.status
                                                )
                                            "
                                        >
                                            <i
                                                :class="
                                                    statusIcon(
                                                        transaction.status
                                                    )
                                                "
                                                class="text-[8px]"
                                            ></i>

                                            {{
                                                formatStatus(
                                                    transaction.status
                                                )
                                            }}
                                        </span>
                                    </td>
                                </tr>

                                <!-- Empty -->
                                <tr
                                    v-if="
                                        !team_history?.transactions ||
                                        !team_history.transactions.length
                                    "
                                >
                                    <td
                                        colspan="5"
                                        class="px-4 py-12 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center"
                                        >
                                            <div
                                                class="flex items-center justify-center w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 mb-3"
                                            >
                                                <i
                                                    class="fas fa-exchange-alt text-slate-600"
                                                ></i>
                                            </div>

                                            <p
                                                class="text-sm font-semibold text-slate-400"
                                            >
                                                No Transactions Found
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-slate-600"
                                            >
                                                This team has no recorded
                                                transaction activity.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- PAGINATION -->
                <!-- ================================================= -->
                <div
                    v-if="team_history?.total_items"
                    class="mt-3 rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2"
                >
                    <div class="overflow-x-auto">
                        <Paginator
                            :page_number="search.page_num"
                            :total_rows="team_history.total_items ?? 0"
                            :itemsperpage="search.itemsperpage"
                            @page_num="handlePagination"
                        />
                    </div>
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
                class="flex flex-col items-center justify-center min-h-[280px] p-8"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/10 mb-4"
                >
                    <i
                        class="fas fa-spinner fa-spin text-indigo-400 text-lg"
                    ></i>
                </div>

                <p class="text-sm font-semibold text-slate-300">
                    Loading Transaction History
                </p>

                <p class="mt-1 text-xs text-slate-600">
                    Retrieving team activity...
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
                Unable to load the team's transaction history.
            </p>
        </div>

        <!-- ========================================================= -->
        <!-- PLAYER PROFILE MODAL -->
        <!-- ========================================================= -->
        <Modal
            :show="!!showPlayerProfileModal"
            :maxWidth="'6xl'"
            title="Player Profile"
            @close="showPlayerProfileModal = false"
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
import Paginator from "@/Components/Paginator.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});

const showPlayerProfileModal = ref(false);

const team_info = ref([]);
const team_history = ref([]);
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
| Transaction Helpers
|--------------------------------------------------------------------------
*/

const formatDetails = (details) => {
    if (!details) {
        return "No details available";
    }

    return String(details)
        .replaceAll("_", " ")
        .replace(/\s+/g, " ")
        .trim();
};

const formatStatus = (status) => {
    if (status === null || status === undefined || status === "") {
        return "Unknown";
    }

    return String(status)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const statusClass = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("complete") ||
        value.includes("approved") ||
        value.includes("success") ||
        value.includes("signed")
    ) {
        return "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
    }

    if (
        value.includes("pending") ||
        value.includes("process") ||
        value.includes("wait")
    ) {
        return "bg-yellow-500/10 text-yellow-400 border-yellow-500/20";
    }

    if (
        value.includes("reject") ||
        value.includes("cancel") ||
        value.includes("fail")
    ) {
        return "bg-red-500/10 text-red-400 border-red-500/20";
    }

    if (
        value.includes("waive") ||
        value.includes("release")
    ) {
        return "bg-orange-500/10 text-orange-400 border-orange-500/20";
    }

    return "bg-slate-800 text-slate-400 border-slate-700";
};

const statusIcon = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("complete") ||
        value.includes("approved") ||
        value.includes("success") ||
        value.includes("signed")
    ) {
        return "fas fa-check";
    }

    if (
        value.includes("pending") ||
        value.includes("process") ||
        value.includes("wait")
    ) {
        return "fas fa-clock";
    }

    if (
        value.includes("reject") ||
        value.includes("cancel") ||
        value.includes("fail")
    ) {
        return "fas fa-times";
    }

    if (
        value.includes("waive") ||
        value.includes("release")
    ) {
        return "fas fa-user-minus";
    }

    return "fas fa-info";
};

const isFreeAgent = (teamName) => {
    if (!teamName) {
        return true;
    }

    const value = String(teamName).toLowerCase();

    return (
        value === "free agent" ||
        value === "free agents"
    );
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

const fetchTeamTransactionHistory = async () => {
    loading.value = true;

    try {
        search.value.team_id = props.team_id;

        const response = await axios.post(
            route("teams.transaction.history"),
            search.value
        );

        team_history.value = response.data ?? [];
    } catch (error) {
        console.error(
            "Error fetching team transaction history:",
            error
        );

        team_history.value = [];
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

    fetchTeamTransactionHistory();
};

onMounted(async () => {
    await Promise.all([
        fetchTeamTransactionHistory(),
        fetchTeamInfo(),
    ]);
});
</script>

<style scoped>
.table-header {
    padding: 0.75rem;
    white-space: nowrap;
    font-size: 0.625rem;
    line-height: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgb(100 116 139);
}

.table-cell {
    padding: 0.8rem 0.75rem;
    white-space: nowrap;
    vertical-align: middle;
}

button {
    -webkit-tap-highlight-color: transparent;
}

@media (max-width: 640px) {
    .table-cell {
        padding: 0.65rem 0.6rem;
    }

    .table-header {
        padding: 0.65rem 0.6rem;
    }
}
</style>