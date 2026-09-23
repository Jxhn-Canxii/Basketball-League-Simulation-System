<template>
    <div class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl">
        <!-- Top Accent -->
        <div class="h-px w-full bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"></div>

        <!-- Header -->
        <div class="border-b border-slate-800 px-4 py-4 sm:px-6">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                    >
                        <i class="fas fa-trophy text-sm text-slate-300"></i>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                        >
                            Championship Count
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Historical league championship records by team
                        </p>
                    </div>
                </div>

                <!-- Championship Count -->
                <div
                    class="flex items-center gap-3 rounded-lg border border-slate-800 bg-[#0d1117] px-4 py-2.5"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-slate-800"
                    >
                        <i class="fas fa-crown text-xs text-slate-300"></i>
                    </div>

                    <div>
                        <div
                            class="text-lg font-bold leading-none text-white"
                        >
                            {{ champions.total ?? 0 }}
                        </div>

                        <div
                            class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                        >
                            Teams with titles
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="border-b border-slate-800 bg-[#0a0d12] px-4 py-4 sm:px-6">
            <div class="relative w-full">
                <i
                    class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-600"
                ></i>

                <input
                    id="LeagueName"
                    v-model="search_champions.search"
                    type="text"
                    placeholder="Search team name..."
                    autocomplete="off"
                    class="w-full rounded-lg border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-4 text-sm text-slate-200 outline-none transition placeholder:text-slate-600 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                    @input="fetchChampions(1)"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#0d1117]">
                        <th
                            class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500 sm:px-6"
                        >
                            Team
                        </th>

                        <th
                            class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500"
                        >
                            Championships
                        </th>

                        <th
                            class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-500 sm:px-6"
                        >
                            Last Appearance
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Loading -->
                    <template v-if="loading">
                        <tr
                            v-for="index in 5"
                            :key="`loading-${index}`"
                            class="border-b border-slate-800/70"
                        >
                            <td class="px-4 py-4 sm:px-6">
                                <div
                                    class="h-4 w-40 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="h-4 w-16 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4 sm:px-6">
                                <div
                                    class="h-4 w-24 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>
                        </tr>
                    </template>

                    <!-- Results -->
                    <template v-else-if="champions.data?.length">
                        <tr
                            v-for="team in champions.data"
                            :key="team.id"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                            :title="
                                'Conference: ' +
                                (team.conference_name ?? '-') +
                                ', Last Appearance: ' +
                                (team.last_finals_appearance ?? '-')
                            "
                        >
                            <!-- Team -->
                            <td class="px-4 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <!-- Team Color Indicator -->
                                    <div
                                        class="h-9 w-1 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor: getTeamColor(team)
                                        }"
                                    ></div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-sm font-bold uppercase tracking-wide text-white"
                                        >
                                            {{ team.name }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500"
                                        >
                                            {{ team.acronym ?? "—" }}
                                            <span
                                                v-if="team.conference_name"
                                                class="mx-1 text-slate-700"
                                            >
                                                •
                                            </span>
                                            {{ team.conference_name ?? "" }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Championships -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-8 min-w-8 items-center justify-center rounded-md border border-slate-700 bg-[#0d1117] px-2"
                                    >
                                        <span
                                            class="text-sm font-black text-white"
                                        >
                                            {{ team.championships ?? 0 }}
                                        </span>
                                    </div>

                                    <span
                                        class="hidden text-[10px] font-semibold uppercase tracking-wider text-slate-600 sm:inline"
                                    >
                                        {{
                                            Number(team.championships) === 1
                                                ? "Title"
                                                : "Titles"
                                        }}
                                    </span>
                                </div>
                            </td>

                            <!-- Last Appearance -->
                            <td class="px-4 py-4 sm:px-6">
                                <div
                                    class="text-xs font-semibold uppercase tracking-wide text-slate-300"
                                >
                                    {{
                                        team.last_finals_appearance ??
                                        "No appearance"
                                    }}
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else>
                        <td colspan="3" class="px-6 py-14 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-trophy text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Championship Records
                            </p>

                            <p class="mt-1 text-xs text-slate-600">
                                No teams matched your search.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Pagination -->
        <div
            v-if="champions.total"
            class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-6"
        >
            <div class="flex w-full overflow-x-auto">
                <Paginator
                    :page_number="search_champions.page_num"
                    :total_rows="champions.total ?? 0"
                    :itemsperpage="search_champions.itemsperpage"
                    @page_num="handleChampionsPagination"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import Paginator from "@/Components/Paginator.vue";

const champions = ref({
    data: [],
    total: 0,
    total_pages: 0,
});

const loading = ref(false);

const search_champions = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});

const fetchChampions = async (page = search_champions.value.page_num) => {
    try {
        loading.value = true;

        search_champions.value.page_num = page;

        const response = await axios.post(
            route("records.champions"),
            search_champions.value
        );

        champions.value = response.data ?? {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } catch (error) {
        console.error("Error fetching champions:", error);

        champions.value = {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } finally {
        loading.value = false;
    }
};

const handleChampionsPagination = (page_num) => {
    search_champions.value.page_num = page_num;
    fetchChampions(page_num);
};

const getTeamColor = (team) => {
    if (team?.primary_color) {
        return `#${team.primary_color.replace("#", "")}`;
    }

    if (team?.secondary_color) {
        return `#${team.secondary_color.replace("#", "")}`;
    }

    return "#64748b";
};

onMounted(() => {
    fetchChampions(1);
});
</script>