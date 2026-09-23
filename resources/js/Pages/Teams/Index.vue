<template>
    <div>
        <Head title="Teams" />

        <AuthenticatedLayout>
            <template #header>
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl border border-yellow-500/20 bg-yellow-500/[0.07]"
                    >
                        <i class="fas fa-shield-alt text-sm text-yellow-500"></i>
                    </div>

                    <div>
                        <h1 class="text-sm font-black uppercase tracking-[0.18em] text-white">
                            Teams
                        </h1>
                        <p class="mt-0.5 text-[10px] font-medium text-gray-600">
                            League teams and organizational details
                        </p>
                    </div>
                </div>
            </template>

            <div class="w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-xl">
                <!-- Header -->
                <div class="border-b border-white/[0.07] px-4 py-4 sm:px-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-yellow-500 shadow-[0_0_8px_rgba(234,179,8,0.5)]"
                                ></span>

                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">
                                    League Directory
                                </span>
                            </div>

                            <h2 class="mt-1 text-lg font-black tracking-tight text-white">
                                Team Management
                            </h2>

                            <p class="mt-1 text-xs text-gray-600">
                                Browse, search, edit, and manage league teams.
                            </p>
                        </div>

                        <!-- Add Team -->
                        <div class="shrink-0">
                            <Add @transaction_id="handleTransaction()" />
                        </div>
                    </div>
                </div>

                <!-- Search / Summary -->
                <div class="border-b border-white/[0.07] bg-[#050505] px-4 py-3 sm:px-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <!-- Search -->
                        <div class="relative w-full md:max-w-md">
                            <i
                                class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-600"
                            ></i>

                            <input
                                id="LeagueName"
                                v-model="search_teams.search"
                                type="text"
                                placeholder="Search team name..."
                                autocomplete="off"
                                class="w-full rounded-xl border border-white/[0.08] bg-white/[0.035] py-2.5 pl-9 pr-3 text-xs font-medium text-white placeholder-gray-700 outline-none transition focus:border-yellow-500/40 focus:bg-white/[0.05] focus:ring-1 focus:ring-yellow-500/10"
                                @input.prevent="fetchTeams()"
                            />
                        </div>

                        <!-- Results -->
                        <div class="flex items-center gap-2">
                            <div
                                class="rounded-lg border border-white/[0.06] bg-white/[0.025] px-3 py-2"
                            >
                                <span class="text-[9px] font-bold uppercase tracking-wider text-gray-600">
                                    Teams
                                </span>

                                <span class="ml-2 text-xs font-black text-gray-300">
                                    {{ teams.total_count ?? 0 }}
                                </span>
                            </div>

                            <div
                                v-if="teams.current_season"
                                class="rounded-lg border border-yellow-500/10 bg-yellow-500/[0.035] px-3 py-2"
                            >
                                <span class="text-[9px] font-bold uppercase tracking-wider text-yellow-500/50">
                                    Season
                                </span>

                                <span class="ml-2 text-xs font-black text-yellow-500">
                                    {{ teams.current_season }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="w-full overflow-x-auto">
                    <table class="min-w-[1050px] w-full border-collapse">
                        <thead>
                            <tr class="border-b border-white/[0.07] bg-[#090909]">
                                <th class="px-5 py-3 text-left">
                                    <span class="table-heading">Team</span>
                                </th>

                                <th class="px-5 py-3 text-left">
                                    <span class="table-heading">League</span>
                                </th>

                                <th class="px-5 py-3 text-left">
                                    <span class="table-heading">Conference</span>
                                </th>

                                <th class="px-5 py-3 text-left">
                                    <span class="table-heading">Colors</span>
                                </th>

                                <th class="px-5 py-3 text-left">
                                    <span class="table-heading">Actions</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <!-- Loading -->
                            <tr v-if="loading">
                                <td colspan="5" class="px-5 py-10">
                                    <div class="flex items-center justify-center gap-3">
                                        <div
                                            class="h-5 w-5 animate-spin rounded-full border-2 border-white/10 border-t-yellow-500"
                                        ></div>

                                        <span class="text-xs font-bold text-gray-600">
                                            Loading teams...
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Teams -->
                            <tr
                                v-else-if="teams.teams?.length"
                                v-for="team in teams.teams"
                                :key="team.id"
                                class="group border-b border-white/[0.05] transition hover:bg-white/[0.025]"
                            >
                                <!-- Team -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Team color marker -->
                                        <div
                                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/[0.08] bg-white/[0.025]"
                                        >
                                            <div
                                                class="absolute inset-0 opacity-30"
                                                :style="{
                                                    background: `linear-gradient(135deg, #${team.primary_color || '333333'}, #${team.secondary_color || '111111'})`
                                                }"
                                            ></div>

                                            <i class="fas fa-shield-alt relative text-sm text-white/70"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <TeamDetails
                                                :team_id="team.id"
                                                :key="team.id"
                                                :showInfo="false"
                                                :current_conference_rank="0"
                                                :isTitleCenter="false"
                                                :season_id="teams.current_season"
                                                :showButton="0"
                                                :text="`${team.city} ${team.name} (${team.acronym})`"
                                            />

                                            <div class="mt-1 flex items-center gap-2">
                                                <span
                                                    class="text-[9px] font-bold uppercase tracking-wider text-gray-700"
                                                >
                                                    ID
                                                </span>

                                                <span class="text-[9px] font-black text-gray-500">
                                                    #{{ team.id }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- League -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025]"
                                        >
                                            <i class="fas fa-basketball-ball text-[10px] text-gray-600"></i>
                                        </div>

                                        <span class="text-xs font-bold text-gray-300">
                                            {{ team.league_name || "—" }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Conference -->
                                <td class="px-5 py-4">
                                    <div
                                        class="inline-flex items-center rounded-lg border border-white/[0.06] bg-white/[0.025] px-2.5 py-1.5"
                                    >
                                        <span
                                            class="mr-2 h-1.5 w-1.5 rounded-full"
                                            :class="
                                                team.conference_name
                                                    ? 'bg-blue-400'
                                                    : 'bg-gray-700'
                                            "
                                        ></span>

                                        <span class="text-[10px] font-bold text-gray-400">
                                            {{ team.conference_name ?? "Special" }}
                                            Conference
                                        </span>
                                    </div>
                                </td>

                                <!-- Colors -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center -space-x-1.5">
                                            <span
                                                class="h-7 w-7 rounded-full border-2 border-black shadow-lg"
                                                :style="{
                                                    backgroundColor: `#${team.primary_color || '333333'}`
                                                }"
                                                :title="`Primary: #${team.primary_color || '333333'}`"
                                            ></span>

                                            <span
                                                class="h-7 w-7 rounded-full border-2 border-black shadow-lg"
                                                :style="{
                                                    backgroundColor: `#${team.secondary_color || '111111'}`
                                                }"
                                                :title="`Secondary: #${team.secondary_color || '111111'}`"
                                            ></span>
                                        </div>

                                        <div class="hidden xl:block">
                                            <div class="text-[8px] font-bold uppercase tracking-wider text-gray-700">
                                                Primary / Secondary
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <TeamDetails
                                            :team_id="team.id"
                                            :key="`view-${team.id}`"
                                            :showButton="1"
                                            text="View"
                                        />

                                        <Edit
                                            :key="`edit-${team.id}`"
                                            :data="team"
                                            @transaction_id="handleTransaction()"
                                        />

                                        <Delete
                                            :key="`delete-${team.id}`"
                                            :team_id="team.id"
                                            @transaction_id="handleTransaction()"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty -->
                            <tr v-else>
                                <td colspan="5" class="px-5 py-14">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <div
                                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.025]"
                                        >
                                            <i class="fas fa-shield-alt text-lg text-gray-700"></i>
                                        </div>

                                        <h3 class="text-sm font-black text-gray-400">
                                            No Teams Found
                                        </h3>

                                        <p class="mt-1 max-w-sm text-xs text-gray-700">
                                            No teams match your current search.
                                            Try another team name or add a new team.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer / Pagination -->
                <div
                    v-if="teams.total_count"
                    class="border-t border-white/[0.07] bg-[#050505] px-4 py-3 sm:px-5"
                >
                    <div class="w-full overflow-x-auto">
                        <Paginator
                            :page_number="search_teams.page_num"
                            :total_rows="teams.total_count ?? 0"
                            :itemsperpage="search_teams.itemsperpage"
                            @page_num="handlePagination"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Paginator from "@/Components/Paginator.vue";
import { ref, onMounted } from "vue";
import axios from "axios";

import TeamDetails from "./Module/TeamDetails.vue";
import Add from "./Module/Add.vue";
import Edit from "./Module/Edit.vue";
import Delete from "./Module/Delete.vue";

const teams = ref({
    teams: [],
    total_count: 0,
    total_pages: 0,
    current_season: null,
});

const loading = ref(false);

const search_teams = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});

onMounted(() => {
    fetchTeams();
});

const fetchTeams = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("teams.list"),
            search_teams.value
        );

        teams.value = {
            teams: response.data?.teams ?? [],
            total_count: response.data?.total_count ?? 0,
            total_pages: response.data?.total_pages ?? 0,
            current_season: response.data?.current_season ?? null,
        };
    } catch (error) {
        console.error("Error fetching teams:", error);

        teams.value = {
            teams: [],
            total_count: 0,
            total_pages: 0,
            current_season: null,
        };
    } finally {
        loading.value = false;
    }
};

const handlePagination = (page_num) => {
    search_teams.value.page_num = page_num ?? 1;
    fetchTeams();
};

const handleTransaction = () => {
    fetchTeams();
};
</script>

<style scoped>
.table-heading {
    @apply text-[9px] font-black uppercase tracking-[0.18em] text-gray-600;
}

/* Keep the page visually dark even if surrounding components contain
   older light-theme styles. */
:deep(.text-gray-900) {
    color: rgb(229 231 235);
}

:deep(.text-gray-800) {
    color: rgb(209 213 219);
}

:deep(.text-gray-700) {
    color: rgb(156 163 175);
}

/* Prevent legacy TeamDetails styling from creating a bright table cell. */
:deep(a),
:deep(button) {
    transition:
        background-color 150ms ease,
        border-color 150ms ease,
        color 150ms ease,
        opacity 150ms ease;
}

/* Thin dark scrollbar for the table/pagination areas */
::-webkit-scrollbar {
    height: 6px;
    width: 6px;
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