<template>
    <div class="min-w-0">
        <!-- Optional Section Header -->
        <div v-if="props.showControls" class="mb-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-800 bg-gray-900"
                >
                    <i class="fa fa-user-plus text-sm text-rose-500"></i>
                </div>

                <div>
                    <h2
                        class="text-sm font-semibold tracking-wide text-white"
                    >
                        Player Signings
                    </h2>

                    <p class="mt-0.5 text-xs text-gray-600">
                        Manage free agents and distribute available players
                    </p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="mb-4">
            <TopStatistics :key="key" />
        </div>

        <!-- Free Agents Panel -->
        <div
            class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow-2xl"
        >
            <!-- Panel Header -->
            <div
                class="border-b border-gray-800 bg-gradient-to-r from-gray-900 via-gray-900 to-gray-950 px-4 py-4"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <i
                                class="fa fa-users text-xs text-gray-500"
                            ></i>

                            <h3
                                class="text-sm font-semibold text-gray-200"
                            >
                                Free Agents List
                            </h3>

                            <span
                                v-if="data.total"
                                class="rounded-md border border-gray-800 bg-gray-950 px-2 py-0.5 text-[10px] text-gray-500"
                            >
                                {{ data.total }}
                            </span>
                        </div>

                        <p
                            class="mt-1 text-[10px] text-gray-600"
                        >
                            Available players currently outside team rosters
                        </p>
                    </div>

                    <!-- Controls -->
                    <div
                        v-if="props.showControls"
                        class="flex flex-wrap items-center gap-2"
                    >
                        <button
                            type="button"
                            @click="assignTeamsAuto"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-rose-500/20 bg-rose-500/10 px-3 text-[11px] font-semibold text-rose-400 transition hover:bg-rose-500/20 hover:text-rose-300"
                        >
                            <i class="fa fa-users text-[10px]"></i>
                            <span>Distribute Free Agents</span>
                        </button>

                        <button
                            type="button"
                            @click.prevent="addMultiplePlayers(60)"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border border-green-500/20 bg-green-500/10 px-3 text-[11px] font-semibold text-green-400 transition hover:bg-green-500/20 hover:text-green-300"
                        >
                            <i class="fa fa-user-plus text-[10px]"></i>
                            <span>Add Undrafted Rookie</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div
                class="border-b border-gray-800 bg-gray-900/80 px-4 py-3"
            >
                <div class="relative max-w-xl">
                    <i
                        class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-600"
                    ></i>

                    <input
                        v-model="search.search"
                        @input="fetchFreeAgent"
                        type="text"
                        placeholder="Search free agent..."
                        class="h-9 w-full rounded-lg border border-gray-700 bg-gray-950 pl-8 pr-3 text-xs text-gray-200 outline-none transition placeholder:text-gray-600 focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                    />
                </div>
            </div>

            <!-- Table Area -->
            <div class="relative">
                <!-- Loading -->
                <div
                    v-if="loading"
                    class="absolute inset-0 z-20 flex items-center justify-center bg-gray-950/70 backdrop-blur-[1px]"
                >
                    <div
                        class="flex items-center gap-3 rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 shadow-xl"
                    >
                        <i
                            class="fa fa-spinner fa-spin text-xs text-gray-400"
                        ></i>

                        <span class="text-xs text-gray-400">
                            Fetching free agents...
                        </span>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="
                        !loading &&
                        (!data.free_agents ||
                            data.free_agents.length === 0)
                    "
                    class="flex min-h-[260px] items-center justify-center"
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
                            No free agents found
                        </p>

                        <p
                            class="mt-1 text-xs text-gray-600"
                        >
                            Try a different player name.
                        </p>
                    </div>
                </div>

                <!-- Player Table -->
                <div
                    v-else
                    class="overflow-x-auto"
                >
                    <table
                        class="min-w-[2200px] w-full border-collapse text-xs"
                    >
                        <thead>
                            <!-- Group Header -->
                            <tr
                                class="border-b border-gray-800 bg-gray-950"
                            >
                                <th
                                    colspan="2"
                                    class="border-r border-gray-800 px-3 py-2 text-left text-[9px] font-semibold uppercase tracking-widest text-gray-600"
                                >
                                    Player
                                </th>

                                <th
                                    colspan="5"
                                    class="border-r border-gray-800 px-3 py-2 text-left text-[9px] font-semibold uppercase tracking-widest text-gray-600"
                                >
                                    Profile
                                </th>

                                <th
                                    colspan="2"
                                    class="border-r border-gray-800 px-3 py-2 text-left text-[9px] font-semibold uppercase tracking-widest text-gray-600"
                                >
                                    Role
                                </th>

                                <th
                                    colspan="6"
                                    class="border-r border-gray-800 px-3 py-2 text-left text-[9px] font-semibold uppercase tracking-widest text-gray-600"
                                >
                                    Ratings
                                </th>

                                <th
                                    class="px-3 py-2 text-left text-[9px] font-semibold uppercase tracking-widest text-gray-600"
                                >
                                    Status
                                </th>
                            </tr>

                            <!-- Column Header -->
                            <tr
                                class="border-b border-gray-800 bg-gray-900"
                            >
                                <th
                                    class="min-w-[170px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Draft
                                </th>

                                <th
                                    class="sticky left-0 z-10 min-w-[220px] border-r border-gray-800 bg-gray-900 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Name
                                </th>

                                <th
                                    class="min-w-[70px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    POS
                                </th>

                                <th
                                    class="min-w-[220px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Awards
                                </th>

                                <th
                                    class="min-w-[100px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Experience
                                </th>

                                <th
                                    class="min-w-[140px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Country
                                </th>

                                <th
                                    class="min-w-[70px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Age
                                </th>

                                <th
                                    class="min-w-[140px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Role
                                </th>

                                <th
                                    class="min-w-[150px] border-r border-gray-800 px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Archetype
                                </th>

                                <th
                                    class="min-w-[90px] px-3 py-2 text-center font-medium uppercase tracking-wider text-gray-500"
                                    title="Overall Rating"
                                >
                                    Overall
                                </th>

                                <th
                                    class="min-w-[80px] px-3 py-2 text-center font-medium uppercase tracking-wider text-gray-500"
                                    title="Potential Rating"
                                >
                                    POT
                                </th>

                                <th
                                    class="min-w-[80px] px-3 py-2 text-center font-medium uppercase tracking-wider text-gray-500"
                                    title="Shooting Rating"
                                >
                                    SH
                                </th>

                                <th
                                    class="min-w-[80px] px-3 py-2 text-center font-medium uppercase tracking-wider text-gray-500"
                                    title="Defense Rating"
                                >
                                    DEF
                                </th>

                                <th
                                    class="min-w-[80px] px-3 py-2 text-center font-medium uppercase tracking-wider text-gray-500"
                                    title="Passing Rating"
                                >
                                    PAS
                                </th>

                                <th
                                    class="min-w-[80px] border-r border-gray-800 px-3 py-2 text-center font-medium uppercase tracking-wider text-gray-500"
                                    title="Rebounding Rating"
                                >
                                    REB
                                </th>

                                <th
                                    class="min-w-[110px] px-3 py-2 text-left font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="player in data.free_agents"
                                :key="player.id"
                                @click.prevent="
                                    showPlayerProfileModal = player.id
                                "
                                class="group cursor-pointer border-b border-gray-800/80 transition hover:bg-gray-800/60"
                            >
                                <!-- Draft -->
                                <td
                                    class="border-r border-gray-800 px-3 py-2.5"
                                >
                                    <span
                                        class="inline-flex max-w-[155px] rounded-md border border-gray-800 bg-gray-950 px-2 py-1 text-[10px] leading-4 text-gray-400"
                                    >
                                        {{
                                            formatDraftStatus(player)
                                        }}
                                    </span>
                                </td>

                                <!-- Name -->
                                <td
                                    class="sticky left-0 z-10 border-r border-gray-800 bg-gray-900 px-3 py-2.5 group-hover:bg-gray-800/95"
                                >
                                    <div
                                        class="flex items-center gap-2"
                                    >
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

                                                <i
                                                    v-if="
                                                        player.is_finals_mvp
                                                    "
                                                    class="fa fa-star shrink-0 text-[10px] text-yellow-500"
                                                    title="Finals MVP"
                                                ></i>
                                            </div>

                                            <div
                                                class="mt-0.5 text-[10px] text-gray-600"
                                            >
                                                ID:
                                                {{ player.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Position -->
                                <td class="px-3 py-2.5">
                                    <span
                                        class="inline-flex min-w-[32px] items-center justify-center rounded-md border border-gray-700 bg-gray-950 px-2 py-1 text-[10px] font-bold text-gray-300"
                                    >
                                        {{ player.position ?? "-" }}
                                    </span>
                                </td>

                                <!-- Awards -->
                                <td class="px-3 py-2.5">
                                    <div
                                        class="max-w-[220px] whitespace-normal break-words leading-4 text-gray-400"
                                    >
                                        {{ player.awards ?? "-" }}
                                    </div>
                                </td>

                                <!-- Experience -->
                                <td class="px-3 py-2.5">
                                    <span
                                        :class="
                                            player.is_rookie == 1
                                                ? 'border-purple-500/20 bg-purple-500/10 text-purple-400'
                                                : 'border-blue-500/20 bg-blue-500/10 text-blue-400'
                                        "
                                        class="inline-flex rounded-md border px-2 py-1 text-[10px] font-semibold"
                                    >
                                        {{
                                            player.is_rookie == 1
                                                ? "Rookie"
                                                : "Veteran"
                                        }}
                                    </span>
                                </td>

                                <!-- Country -->
                                <td
                                    class="px-3 py-2.5 text-gray-400"
                                >
                                    {{ player.country ?? "-" }}
                                </td>

                                <!-- Age -->
                                <td
                                    class="border-r border-gray-800 px-3 py-2.5"
                                >
                                    <span
                                        class="font-medium text-gray-300"
                                    >
                                        {{ player.age ?? "-" }}
                                    </span>
                                </td>

                                <!-- Role -->
                                <td class="px-3 py-2.5">
                                    <span
                                        :class="
                                            roleBadgeClass(player.role)
                                        "
                                        class="inline-flex rounded-md border px-2 py-1 text-[10px] font-semibold"
                                    >
                                        {{ player.role ?? "-" }}
                                    </span>
                                </td>

                                <!-- Archetype -->
                                <td
                                    class="border-r border-gray-800 px-3 py-2.5"
                                >
                                    <span
                                        class="inline-flex max-w-[140px] rounded-md border border-gray-800 bg-gray-950 px-2 py-1 text-[10px] uppercase tracking-wide text-gray-400"
                                    >
                                        {{
                                            formatArchetype(
                                                player.type
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Overall -->
                                <td class="px-3 py-2.5">
                                    <div
                                        class="flex flex-col items-center gap-1"
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
                                                formatRating(
                                                    player.overall_rating
                                                )
                                            }}
                                        </span>

                                        <div
                                            class="h-1 w-10 overflow-hidden rounded-full bg-gray-800"
                                        >
                                            <div
                                                class="h-full rounded-full"
                                                :class="
                                                    ratingBarClass(
                                                        player.overall_rating
                                                    )
                                                "
                                                :style="{
                                                    width:
                                                        ratingWidth(
                                                            player.overall_rating
                                                        ),
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Potential -->
                                <td
                                    class="px-3 py-2.5 text-center"
                                >
                                    <span
                                        :class="
                                            ratingClass(
                                                player.potential_rating
                                            )
                                        "
                                        class="font-semibold"
                                    >
                                        {{
                                            formatRating(
                                                player.potential_rating
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Shooting -->
                                <td
                                    class="px-3 py-2.5 text-center"
                                >
                                    <span
                                        class="font-medium text-gray-300"
                                    >
                                        {{
                                            formatRating(
                                                player.shooting_rating
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Defense -->
                                <td
                                    class="px-3 py-2.5 text-center"
                                >
                                    <span
                                        class="font-medium text-gray-300"
                                    >
                                        {{
                                            formatRating(
                                                player.defense_rating
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Passing -->
                                <td
                                    class="px-3 py-2.5 text-center"
                                >
                                    <span
                                        class="font-medium text-gray-300"
                                    >
                                        {{
                                            formatRating(
                                                player.passing_rating
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Rebounding -->
                                <td
                                    class="border-r border-gray-800 px-3 py-2.5 text-center"
                                >
                                    <span
                                        class="font-medium text-gray-300"
                                    >
                                        {{
                                            formatRating(
                                                player.rebounding_rating
                                            )
                                        }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-2.5">
                                    <span
                                        :class="
                                            player.is_active
                                                ? 'border-green-500/20 bg-green-500/10 text-green-400'
                                                : 'border-red-500/20 bg-red-500/10 text-red-400'
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-[10px] font-semibold"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                player.is_active
                                                    ? 'bg-green-400'
                                                    : 'bg-red-400'
                                            "
                                        ></span>

                                        {{
                                            player.is_active
                                                ? "Active"
                                                : "Waived"
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
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

        <!-- Add Player Modal -->
        <Modal
            :show="showAddPlayerModal"
            :maxWidth="'6xl'"
            title="Add Player"
            @close="showAddPlayerModal = false"
        >
            <div class="bg-gray-950 p-6">
                <div class="mb-5">
                    <h3 class="text-sm font-semibold text-white">
                        Add New Player
                    </h3>

                    <p class="mt-1 text-xs text-gray-500">
                        Enter the basic information for the new player.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label
                            for="playerName"
                            class="mb-1.5 block text-xs font-medium text-gray-400"
                        >
                            Player Name
                        </label>

                        <input
                            id="playerName"
                            v-model="newPlayer.name"
                            type="text"
                            class="h-10 w-full rounded-lg border border-gray-700 bg-gray-900 px-3 text-xs text-gray-200 outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        />
                    </div>

                    <div>
                        <label
                            for="playerTeam"
                            class="mb-1.5 block text-xs font-medium text-gray-400"
                        >
                            Team ID
                        </label>

                        <input
                            id="playerTeam"
                            v-model="newPlayer.team_id"
                            type="text"
                            class="h-10 w-full rounded-lg border border-gray-700 bg-gray-900 px-3 text-xs text-gray-200 outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-600"
                        />
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="addPlayer(newPlayer)"
                            class="rounded-lg border border-blue-500/20 bg-blue-500/10 px-4 py-2 text-xs font-semibold text-blue-400 transition hover:bg-blue-500/20"
                        >
                            Add Player
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Player Profile -->
        <Modal
            :show="!!showPlayerProfileModal"
            :maxWidth="'6xl'"
            title="Player Profile"
            @close="showPlayerProfileModal = false"
        >
            <div class="bg-gray-950 p-4 sm:p-6">
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
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";

import { roleBadgeClass } from "@/Utility/Formatter";

import TopStatistics from "@/Pages/Analytics/Module/TopStatistics.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    showControls: {
        type: Boolean,
        default: true,
    },
});

const emits = defineEmits(["newSeason"]);

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const showAddPlayerModal = ref(false);
const showPlayerProfileModal = ref(false);

const loading = ref(false);

const selectedPlayer = ref(null);

const newPlayer = ref({
    name: "",
    team_id: "",
});

const data = ref({
    free_agents: [],
    current_page: 1,
    total_pages: 0,
    total: 0,
});

const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});

const key = ref(0);

/*
|--------------------------------------------------------------------------
| Fetch Free Agents
|--------------------------------------------------------------------------
*/

const fetchFreeAgent = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("players.free.agents"),
            search.value
        );

        data.value = {
            free_agents: response.data?.free_agents ?? [],
            current_page: response.data?.current_page ?? 1,
            total_pages: response.data?.total_pages ?? 0,
            total: response.data?.total ?? 0,
        };
    } catch (error) {
        console.error("Error fetching free agents:", error);

        data.value.free_agents = [];
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
    search.value.page_num = page_num ?? 1;
    fetchFreeAgent();
};

/*
|--------------------------------------------------------------------------
| Player Profile
|--------------------------------------------------------------------------
*/

const showProfile = (player) => {
    if (!player?.id) return;

    selectedPlayer.value = player;
    showPlayerProfileModal.value = player.id;
};

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatRating = (value) => {
    const number = Number.parseFloat(value);

    return Number.isFinite(number) ? number.toFixed(1) : "0.0";
};

const ratingWidth = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
        return "0%";
    }

    return `${Math.min(Math.max(number, 0), 100)}%`;
};

const ratingClass = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
        return "text-gray-500";
    }

    if (number >= 90) return "text-yellow-400";
    if (number >= 80) return "text-green-400";
    if (number >= 70) return "text-blue-400";
    if (number >= 60) return "text-gray-300";

    return "text-gray-500";
};

const ratingBarClass = (value) => {
    const number = Number.parseFloat(value);

    if (!Number.isFinite(number)) {
        return "bg-gray-700";
    }

    if (number >= 90) return "bg-yellow-500";
    if (number >= 80) return "bg-green-500";
    if (number >= 70) return "bg-blue-500";
    if (number >= 60) return "bg-gray-500";

    return "bg-gray-700";
};

const formatDraftStatus = (player) => {
    if (player?.draft_status === "Undrafted") {
        return `S${player.draft_id ?? "-"} Undrafted`;
    }

    if (!player?.draft_status) {
        return "-";
    }

    return (
        player.draft_status +
        (player.drafted_team
            ? ` (${player.drafted_team})`
            : "")
    );
};

const formatArchetype = (type) => {
    if (!type) return "-";

    return String(type).replaceAll("_", " ");
};

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
| Add Players
|--------------------------------------------------------------------------
*/

const fetchRandomFullName = async () => {
    try {
        const response = await axios.get(
            route("generate.new.player")
        );

        const { name, country, address } = response.data;

        return {
            name,
            country,
            address,
        };
    } catch (error) {
        console.error(
            "Error fetching random player name:",
            error
        );

        return null;
    }
};

const addPlayer = async (info) => {
    if (!info) return;

    try {
        const response = await axios.post(
            route("players.add.free.agent"),
            {
                name: info.name,
                address: info.address,
                country: info.country,
            }
        );

        Swal.fire({
            icon: "success",
            title: `${response.data.player.name} | ${response.data.player.age} | ${response.data.player.position} has been added to Draft Pool!`,
            text: response.data.message,
        });

        key.value = Math.random();
    } catch (error) {
        const message =
            error.response?.data?.message ||
            "Unable to add player.";

        console.error("Error adding player:", message);

        throw new Error(message);
    }
};

const addMultiplePlayers = async (count) => {
    try {
        const promises = [];

        for (let i = 0; i < count; i++) {
            const randomFullName =
                await fetchRandomFullName();

            if (randomFullName) {
                promises.push(addPlayer(randomFullName));
            }
        }

        await Promise.all(promises);

        await fetchFreeAgent();

        key.value = Math.random();

        Swal.fire({
            icon: "success",
            title: "Success!",
            text: `Successfully added ${count} players.`,
        });
    } catch (error) {
        console.error(
            "Error adding multiple players:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.message ||
                "Unable to add players.",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Manual Team Assignment
|--------------------------------------------------------------------------
*/

const assignTeams = async (player_id) => {
    try {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to assign this player to a team?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, assign it!",
            cancelButtonText: "No, cancel",
            reverseButtons: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        const response = await axios.post(
            route("assign.freeagent.teams"),
            {
                player_id,
            }
        );

        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });

        const is_new_season =
            response.data.team_count == 0;

        if (is_new_season) {
            emits("newSeason", is_new_season);
        }

        fetchFreeAgent();
    } catch (error) {
        console.error("Error assigning team:", error);

        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text:
                error.response?.data?.message ||
                "Unable to assign player.",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Automatic Free Agent Distribution
|--------------------------------------------------------------------------
*/

const assignTeamsAuto = async () => {
    try {
        const result = await Swal.fire({
            title: "Distribute Free Agents?",
            text: "Free agents will be assigned to teams automatically.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, assign them!",
            cancelButtonText: "Cancel",
            reverseButtons: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        await archiveSeason();

        Swal.fire({
            title: "Processing...",
            text: "Assigning free agents to teams. Please wait.",
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(
            route("auto.assign.freeagent.teams")
        );

        const responseData = response.data;

        let message = "";

        if (
            responseData.message ===
            "All teams have signed 12 players."
        ) {
            message = `<p>${responseData.message}</p>`;
        } else if (
            responseData.message ===
            "No free agents available."
        ) {
            message = `<p>${responseData.message}</p>`;

            if (
                responseData.incomplete_teams?.length
            ) {
                message += "<ul>";

                responseData.incomplete_teams.forEach(
                    (team) => {
                        message += `
                            <li>
                                <strong>${team.team_name}</strong>:
                                ${team.players_needed} player(s) needed
                            </li>
                        `;
                    }
                );

                message += "</ul>";
            }
        } else {
            message = `<p>${responseData.message}</p>`;

            if (
                responseData.remaining_free_agents > 0
            ) {
                message += `
                    <p>
                        Remaining free agents:
                        ${responseData.remaining_free_agents}
                    </p>
                `;
            }

            if (
                responseData.incomplete_teams?.length
            ) {
                message += "<ul>";

                responseData.incomplete_teams.forEach(
                    (team) => {
                        message += `
                            <li>
                                <strong>${team.team_name}</strong>:
                                ${team.players_needed}
                                player(s) still needed
                            </li>
                        `;
                    }
                );

                message += "</ul>";
            }
        }

        Swal.close();

        Swal.fire({
            icon: "success",
            title: "Distribution Complete",
            html: message,
        });

        const is_new_season =
            responseData.team_count === 0;

        if (is_new_season) {
            emits("newSeason", is_new_season);
        }

        await fetchFreeAgent();
    } catch (error) {
        console.error(
            "Error assigning teams:",
            error
        );

        Swal.close();

        Swal.fire({
            icon: "warning",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "An unexpected error occurred.",
        });

        emits("newSeason", true);
    }
};

/*
|--------------------------------------------------------------------------
| Archive Season
|--------------------------------------------------------------------------
*/

const archiveSeason = async () => {
    try {
        Swal.fire({
            title: "Archiving...",
            text: "Archiving past results... please wait.",
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.get(
            route("archive.season")
        );

        Swal.close();

        Swal.fire({
            icon: "success",
            title: "Archived",
            text: response.data.message,
        });
    } catch (error) {
        Swal.close();

        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text:
                error.response?.data?.message ||
                "Unable to archive season.",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchFreeAgent();
});
</script>