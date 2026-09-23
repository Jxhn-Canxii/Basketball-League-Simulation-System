<template>
    <Head title="Players" />

    <AuthenticatedLayout>
        <template #header>
            Players
        </template>

        <div class="min-h-screen bg-gray-950 p-3 sm:p-4">
            <div class="w-full">
                <!-- Main Player Panel -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-2xl"
                >
                    <!-- Header -->
                    <div
                        class="border-b border-gray-800 bg-gradient-to-r from-gray-900 via-gray-900 to-gray-950 px-4 py-4"
                    >
                        <div
                            class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-700 bg-gray-800"
                                >
                                    <i
                                        class="fa fa-users text-sm text-gray-300"
                                    ></i>
                                </div>

                                <div>
                                    <h3
                                        class="text-sm font-semibold tracking-wide text-white"
                                    >
                                        Player List
                                    </h3>

                                    <p
                                        class="mt-0.5 text-xs text-gray-500"
                                    >
                                        Browse players, ratings, contracts,
                                        roles and team status
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="data.total"
                                class="text-xs text-gray-500"
                            >
                                <span class="text-gray-300">
                                    {{ data.total }}
                                </span>
                                player{{ data.total === 1 ? "" : "s" }}
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div
                        class="border-b border-gray-800 bg-gray-900/80 px-4 py-4"
                    >
                        <div
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5"
                        >
                            <!-- Search -->
                            <div class="relative lg:col-span-1">
                                <i
                                    class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-600"
                                ></i>

                                <input
                                    v-model="search.search"
                                    @input="fetchAllPlayers"
                                    type="text"
                                    placeholder="Search player..."
                                    class="h-10 w-full rounded-lg border border-gray-700 bg-gray-950 pl-9 pr-3 text-xs text-gray-200 outline-none transition placeholder:text-gray-600 focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                                />
                            </div>

                            <!-- Position -->
                            <select
                                v-model="search.position"
                                @change="fetchAllPlayers"
                                class="h-10 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 text-xs text-gray-300 outline-none transition focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                            >
                                <option value="">All Positions</option>
                                <option value="PG">Point Guard</option>
                                <option value="SG">Shooting Guard</option>
                                <option value="SF">Small Forward</option>
                                <option value="PF">Power Forward</option>
                                <option value="C">Center</option>
                            </select>

                            <!-- Injury -->
                            <select
                                v-model="search.injury_status"
                                @change="fetchAllPlayers"
                                class="h-10 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 text-xs text-gray-300 outline-none transition focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                            >
                                <option value="2">All Injury Status</option>
                                <option value="1">Injured</option>
                                <option value="0">Healthy</option>
                            </select>

                            <!-- Active -->
                            <select
                                v-model="search.is_active"
                                @change="fetchAllPlayers"
                                class="h-10 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 text-xs text-gray-300 outline-none transition focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                            >
                                <option value="2">All Players</option>
                                <option value="1">Active</option>
                                <option value="0">Retired</option>
                            </select>

                            <!-- Team -->
                            <select
                                v-model="search.with_a_team"
                                @change="fetchAllPlayers"
                                class="h-10 w-full rounded-lg border border-gray-700 bg-gray-950 px-3 text-xs text-gray-300 outline-none transition focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                            >
                                <option value="1">With Team</option>
                                <option value="0">Free Agent</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="relative">
                        <!-- Loading overlay -->
                        <div
                            v-if="loading"
                            class="absolute inset-0 z-20 flex items-center justify-center bg-gray-950/70 backdrop-blur-[1px]"
                        >
                            <div
                                class="flex items-center gap-3 rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 shadow-xl"
                            >
                                <i
                                    class="fa fa-spinner fa-spin text-sm text-gray-400"
                                ></i>

                                <span class="text-xs text-gray-400">
                                    Fetching player list...
                                </span>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="!loading && (!data.players || data.players.length === 0)"
                            class="flex min-h-[260px] items-center justify-center px-4"
                        >
                            <div class="text-center">
                                <div
                                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border border-gray-800 bg-gray-950"
                                >
                                    <i
                                        class="fa fa-user-slash text-gray-600"
                                    ></i>
                                </div>

                                <p
                                    class="text-sm font-medium text-gray-400"
                                >
                                    No players found
                                </p>

                                <p
                                    class="mt-1 text-xs text-gray-600"
                                >
                                    Try changing your search or filter
                                    criteria.
                                </p>
                            </div>
                        </div>

                        <!-- Player Table -->
                        <div
                            v-else
                            class="overflow-x-auto"
                        >
                            <table
                                class="min-w-[1900px] w-full border-collapse text-xs"
                            >
                                <thead>
                                    <!-- Group Header -->
                                    <tr
                                        class="border-b border-gray-800 bg-gray-950"
                                    >
                                        <th
                                            colspan="2"
                                            class="border-r border-gray-800 px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-600"
                                        >
                                            Player
                                        </th>

                                        <th
                                            colspan="4"
                                            class="border-r border-gray-800 px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-600"
                                        >
                                            Profile
                                        </th>

                                        <th
                                            colspan="2"
                                            class="border-r border-gray-800 px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-600"
                                        >
                                            Team & Contract
                                        </th>

                                        <th
                                            colspan="2"
                                            class="border-r border-gray-800 px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-600"
                                        >
                                            Career
                                        </th>

                                        <th
                                            class="border-r border-gray-800 px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-600"
                                        >
                                            Role
                                        </th>

                                        <th
                                            class="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-600"
                                        >
                                            Status
                                        </th>
                                    </tr>

                                    <!-- Column Header -->
                                    <tr
                                        class="border-b border-gray-800 bg-gray-900"
                                    >
                                        <th
                                            class="sticky left-0 z-10 min-w-[220px] border-r border-gray-800 bg-gray-900 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Name
                                        </th>

                                        <th
                                            class="min-w-[150px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Draft
                                        </th>

                                        <th
                                            class="min-w-[90px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Rating
                                        </th>

                                        <th
                                            class="min-w-[100px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Position
                                        </th>

                                        <th
                                            class="min-w-[150px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Country
                                        </th>

                                        <th
                                            class="min-w-[220px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Awards
                                        </th>

                                        <th
                                            class="min-w-[180px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Current Team
                                        </th>

                                        <th
                                            class="min-w-[150px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Remaining Contract
                                        </th>

                                        <th
                                            class="min-w-[80px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Age
                                        </th>

                                        <th
                                            class="min-w-[100px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Retirement
                                        </th>

                                        <th
                                            class="min-w-[140px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Role
                                        </th>

                                        <th
                                            class="min-w-[140px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            Status
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="player in data.players"
                                        :key="player.player_id"
                                        @click.prevent="showPlayerProfile(player)"
                                        class="group cursor-pointer border-b border-gray-800/80 transition hover:bg-gray-800/60"
                                    >
                                        <!-- Name -->
                                        <td
                                            class="sticky left-0 z-10 border-r border-gray-800 bg-gray-900 px-3 py-2.5 group-hover:bg-gray-800/95"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <!-- Player Avatar -->
                                                <div
                                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-700 bg-gray-950 text-[10px] font-bold text-gray-400"
                                                >
                                                    {{
                                                        playerInitials(
                                                            player.name
                                                        )
                                                    }}
                                                </div>

                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-1.5"
                                                    >
                                                        <span
                                                            class="truncate font-semibold text-gray-200"
                                                        >
                                                            {{ player.name }}
                                                        </span>

                                                        <!-- Finals MVP -->
                                                        <i
                                                            v-if="player.is_finals_mvp"
                                                            class="fa fa-star shrink-0 text-[10px] text-yellow-500"
                                                            title="Finals MVP"
                                                        ></i>

                                                        <!-- Injury -->
                                                        <i
                                                            v-if="
                                                                player.is_injured &&
                                                                player.age <=
                                                                    player.retirement_age &&
                                                                player.is_active
                                                            "
                                                            class="fa fa-crutch shrink-0 text-[10px] text-red-400"
                                                            title="Injured"
                                                        ></i>
                                                    </div>

                                                    <div
                                                        class="mt-0.5 text-[10px] text-gray-600"
                                                    >
                                                        ID:
                                                        {{
                                                            player.player_id
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Draft -->
                                        <td
                                            class="border-r border-gray-800 px-3 py-2.5 text-gray-400"
                                        >
                                            <span
                                                class="inline-flex max-w-[140px] rounded-md border border-gray-800 bg-gray-950 px-2 py-1 text-[10px] leading-4 text-gray-400"
                                            >
                                                {{
                                                    player.formatted_draft_status ??
                                                    "-"
                                                }}
                                            </span>
                                        </td>

                                        <!-- Rating -->
                                        <td class="px-3 py-2.5">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="text-sm font-bold"
                                                    :class="
                                                        ratingClass(
                                                            player.overall_rating
                                                        )
                                                    "
                                                >
                                                    {{
                                                        player.overall_rating ??
                                                        "-"
                                                    }}
                                                </span>

                                                <div
                                                    v-if="
                                                        player.overall_rating !=
                                                        null
                                                    "
                                                    class="h-1.5 w-10 overflow-hidden rounded-full bg-gray-800"
                                                >
                                                    <div
                                                        class="h-full rounded-full transition-all"
                                                        :class="
                                                            ratingBarClass(
                                                                player.overall_rating
                                                            )
                                                        "
                                                        :style="{
                                                            width:
                                                                Math.min(
                                                                    Number(
                                                                        player.overall_rating
                                                                    ) || 0,
                                                                    100
                                                                ) + '%',
                                                        }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Position -->
                                        <td class="px-3 py-2.5">
                                            <span
                                                class="inline-flex min-w-[32px] items-center justify-center rounded-md border border-gray-700 bg-gray-950 px-2 py-1 text-[10px] font-bold text-gray-300"
                                            >
                                                {{
                                                    player.position ?? "-"
                                                }}
                                            </span>
                                        </td>

                                        <!-- Country -->
                                        <td
                                            class="px-3 py-2.5 text-gray-400"
                                        >
                                            {{ player.country ?? "-" }}
                                        </td>

                                        <!-- Awards -->
                                        <td
                                            class="border-r border-gray-800 px-3 py-2.5"
                                        >
                                            <div
                                                class="max-w-[220px] whitespace-normal break-words leading-4 text-gray-400"
                                            >
                                                {{ player.awards ?? "-" }}
                                            </div>
                                        </td>

                                        <!-- Current Team -->
                                        <td class="px-3 py-2.5">
                                            <span
                                                v-if="player.team_name"
                                                class="inline-flex max-w-[160px] truncate rounded-md border border-gray-700 bg-gray-950 px-2 py-1 text-gray-300"
                                            >
                                                {{ player.team_name }}
                                            </span>

                                            <span
                                                v-else
                                                class="text-gray-600"
                                            >
                                                Free Agent
                                            </span>
                                        </td>

                                        <!-- Contract -->
                                        <td
                                            class="border-r border-gray-800 px-3 py-2.5"
                                        >
                                            <span
                                                class="font-medium text-gray-300"
                                            >
                                                {{
                                                    player.contract_years ??
                                                    0
                                                }}
                                            </span>

                                            <span
                                                class="ml-1 text-gray-600"
                                            >
                                                yrs.
                                            </span>
                                        </td>

                                        <!-- Age -->
                                        <td class="px-3 py-2.5">
                                            <span
                                                class="font-medium text-gray-300"
                                            >
                                                {{ player.age ?? "-" }}
                                            </span>
                                        </td>

                                        <!-- Retirement -->
                                        <td
                                            class="border-r border-gray-800 px-3 py-2.5"
                                        >
                                            <span
                                                class="text-gray-400"
                                            >
                                                {{
                                                    player.retirement_age ??
                                                    "-"
                                                }}
                                            </span>

                                            <span
                                                v-if="
                                                    player.age != null &&
                                                    player.retirement_age !=
                                                        null
                                                "
                                                class="ml-1 text-[10px] text-gray-600"
                                            >
                                                ({{
                                                    retirementRemaining(
                                                        player.age,
                                                        player.retirement_age
                                                    )
                                                }})
                                            </span>
                                        </td>

                                        <!-- Role -->
                                        <td
                                            class="border-r border-gray-800 px-3 py-2.5"
                                        >
                                            <span
                                                :class="roleClasses(player.role)"
                                                class="inline-flex items-center rounded-md border px-2 py-1 text-[10px] font-semibold capitalize"
                                            >
                                                {{ player.role ?? "-" }}
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-3 py-2.5">
                                            <span
                                                :class="
                                                    statusClasses(player)
                                                "
                                                class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-[10px] font-semibold"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full"
                                                    :class="
                                                        statusDotClass(player)
                                                    "
                                                ></span>

                                                {{
                                                    playerStatus(player)
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer / Pagination -->
                    <div
                        v-if="data.total"
                        class="border-t border-gray-800 bg-gray-950/60 px-3 py-2"
                    >
                        <div class="overflow-x-auto">
                            <Paginator
                                :page_number="search.page_num"
                                :total_rows="data.total ?? 0"
                                :itemsperpage="search.itemsperpage"
                                @page_num="handlePagination"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Player Profile -->
        <Modal
            :show="showPlayerProfileModal"
            :maxWidth="'6xl'"
            title="Player Information"
            @close="showPlayerProfileModal = false"
        >
            <div class="bg-gray-950 p-4 sm:p-6">
                <PlayerPerformance
                    v-if="selectedPlayer?.player_id"
                    :key="selectedPlayer.player_id"
                    :player_id="selectedPlayer.player_id"
                />
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";

import PlayerPerformance from "./Module/PlayerPerformance.vue";

const showPlayerProfileModal = ref(false);

const selectedPlayer = ref(null);

const loading = ref(false);

const data = ref({
    players: [],
    free_agents: [],
    current_page: 1,
    total_pages: 0,
    position: "",
    total: 0,
});

const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    position: "",
    injury_status: 2,
    is_active: 2,
    with_a_team: 1,
    itemsperpage: 10,
});

/*
|--------------------------------------------------------------------------
| Fetch Players
|--------------------------------------------------------------------------
*/

const fetchAllPlayers = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("players.list.all"),
            search.value
        );

        data.value = {
            players: response.data?.players ?? [],
            free_agents: response.data?.free_agents ?? [],
            current_page: response.data?.current_page ?? 1,
            total_pages: response.data?.total_pages ?? 0,
            position: response.data?.position ?? "",
            total: response.data?.total ?? 0,
        };
    } catch (error) {
        console.error("Error fetching player list:", error);

        data.value.players = [];
        data.value.total = 0;
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
    search.value.page_num = page_num;
    fetchAllPlayers();
};

/*
|--------------------------------------------------------------------------
| Player Profile
|--------------------------------------------------------------------------
*/

const showPlayerProfile = (player) => {
    selectedPlayer.value = player;
    showPlayerProfileModal.value = true;
};

/*
|--------------------------------------------------------------------------
| Player Initials
|--------------------------------------------------------------------------
*/

const playerInitials = (name) => {
    if (!name) return "?";

    const parts = String(name)
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }

    return (
        parts[0].charAt(0) +
        parts[parts.length - 1].charAt(0)
    ).toUpperCase();
};

/*
|--------------------------------------------------------------------------
| Rating Styling
|--------------------------------------------------------------------------
*/

const ratingClass = (rating) => {
    const value = Number(rating);

    if (!Number.isFinite(value)) {
        return "text-gray-500";
    }

    if (value >= 90) {
        return "text-yellow-400";
    }

    if (value >= 80) {
        return "text-green-400";
    }

    if (value >= 70) {
        return "text-blue-400";
    }

    if (value >= 60) {
        return "text-gray-300";
    }

    return "text-gray-500";
};

const ratingBarClass = (rating) => {
    const value = Number(rating);

    if (!Number.isFinite(value)) {
        return "bg-gray-700";
    }

    if (value >= 90) {
        return "bg-yellow-500";
    }

    if (value >= 80) {
        return "bg-green-500";
    }

    if (value >= 70) {
        return "bg-blue-500";
    }

    if (value >= 60) {
        return "bg-gray-500";
    }

    return "bg-gray-700";
};

/*
|--------------------------------------------------------------------------
| Role Styling
|--------------------------------------------------------------------------
*/

const roleClasses = (role) => {
    const value = String(role ?? "").toLowerCase();

    switch (value) {
        case "star player":
            return "border-yellow-500/20 bg-yellow-500/10 text-yellow-400";

        case "all star":
            return "border-red-500/20 bg-red-500/10 text-red-400";

        case "starter":
            return "border-blue-500/20 bg-blue-500/10 text-blue-400";

        case "role player":
            return "border-green-500/20 bg-green-500/10 text-green-400";

        case "bench":
            return "border-gray-700 bg-gray-800 text-gray-400";

        case "reserved bench":
            return "border-purple-500/20 bg-purple-500/10 text-purple-400";

        default:
            return "border-gray-700 bg-gray-800 text-gray-400";
    }
};

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

const playerStatus = (player) => {
    if (!player?.is_active) {
        return "Retired";
    }

    if (Number(player.team_id) > 0) {
        return "Active";
    }

    return "Free Agent";
};

const statusClasses = (player) => {
    if (!player?.is_active) {
        return "border-gray-700 bg-gray-800 text-gray-500";
    }

    if (Number(player.team_id) > 0) {
        return "border-green-500/20 bg-green-500/10 text-green-400";
    }

    return "border-red-500/20 bg-red-500/10 text-red-400";
};

const statusDotClass = (player) => {
    if (!player?.is_active) {
        return "bg-gray-600";
    }

    if (Number(player.team_id) > 0) {
        return "bg-green-400";
    }

    return "bg-red-400";
};

/*
|--------------------------------------------------------------------------
| Retirement
|--------------------------------------------------------------------------
*/

const retirementRemaining = (age, retirementAge) => {
    const currentAge = Number(age);
    const retireAge = Number(retirementAge);

    if (
        !Number.isFinite(currentAge) ||
        !Number.isFinite(retireAge)
    ) {
        return "";
    }

    const remaining = retireAge - currentAge;

    if (remaining <= 0) {
        return "eligible";
    }

    return `${remaining} yr${remaining === 1 ? "" : "s"}`;
};

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchAllPlayers();
});
</script>