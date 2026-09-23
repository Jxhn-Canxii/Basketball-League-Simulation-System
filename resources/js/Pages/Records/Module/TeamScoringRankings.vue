<template>
    <div
        class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
    >
        <!-- Top Accent -->
        <div
            class="h-px w-full bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
        ></div>

        <!-- Header -->
        <div
            class="border-b border-slate-800 px-4 py-4 sm:px-6"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                    >
                        <i
                            class="fas fa-users text-sm text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                        >
                            All-Time Team Stats
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Career statistical records by franchise
                        </p>
                    </div>
                </div>

                <!-- Current Category -->
                <div
                    class="flex items-center gap-3 self-start rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5 lg:self-auto"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-800"
                    >
                        <i
                            :class="[
                                'fas text-[10px] text-slate-300',
                                selectedStatIcon
                            ]"
                        ></i>
                    </div>

                    <div>
                        <div
                            class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Ranking By
                        </div>

                        <div
                            class="mt-0.5 text-xs font-bold uppercase tracking-wide text-white"
                        >
                            {{ selectedStatLabel }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div
            class="border-b border-slate-800 bg-[#0a0d12] px-4 py-4 sm:px-6"
        >
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Search -->
                <div class="relative w-full lg:max-w-xl">
                    <i
                        class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-600"
                    ></i>

                    <input
                        v-model="search_topstats.search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search team..."
                        class="w-full rounded-lg border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-4 text-sm text-slate-200 outline-none transition placeholder:text-slate-600 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                        @input="filterTopStats(1)"
                    />
                </div>

                <!-- Sort -->
                <div class="relative w-full lg:w-64">
                    <i
                        class="fas fa-sort-amount-down pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-600"
                    ></i>

                    <select
                        v-model="search_topstats.sort_by"
                        @change="filterTopStats(1)"
                        class="w-full appearance-none rounded-lg border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-9 text-xs font-semibold uppercase tracking-wide text-slate-300 outline-none transition focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                    >
                        <option value="total_points">
                            Sort by Points
                        </option>
                        <option value="total_rebounds">
                            Sort by Rebounds
                        </option>
                        <option value="total_assists">
                            Sort by Assists
                        </option>
                        <option value="total_steals">
                            Sort by Steals
                        </option>
                        <option value="total_blocks">
                            Sort by Blocks
                        </option>
                    </select>

                    <i
                        class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-600"
                    ></i>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#0d1117]">
                        <th
                            class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-600 sm:px-6"
                        >
                            Team
                        </th>

                        <th
                            class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Conference
                        </th>

                        <th
                            class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-600 sm:px-6"
                        >
                            {{ selectedStatLabel }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Loading -->
                    <template v-if="loading">
                        <tr
                            v-for="index in 8"
                            :key="`loading-${index}`"
                            class="border-b border-slate-800/70"
                        >
                            <td class="px-4 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-8 w-1 animate-pulse rounded-full bg-slate-800"
                                    ></div>

                                    <div
                                        class="h-4 w-40 animate-pulse rounded bg-slate-800"
                                    ></div>
                                </div>
                            </td>

                            <td class="px-4 py-4 text-center">
                                <div
                                    class="mx-auto h-6 w-24 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4 sm:px-6">
                                <div
                                    class="ml-auto h-5 w-20 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>
                        </tr>
                    </template>

                    <!-- Results -->
                    <template v-else-if="top_scorers.data?.length">
                        <tr
                            v-for="(team, index) in top_scorers.data"
                            :key="team.id ?? index"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Team -->
                            <td class="px-4 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <!-- Team Color -->
                                    <div
                                        class="h-10 w-1 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                getTeamColor(team)
                                        }"
                                    ></div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-sm font-bold uppercase tracking-wide text-white"
                                        >
                                            {{ team.name }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-[9px] font-semibold uppercase tracking-widest text-slate-600"
                                        >
                                            Franchise Record
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Conference -->
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-2.5 py-1 text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                >
                                    {{ team.conference ?? "—" }}
                                </span>
                            </td>

                            <!-- Score -->
                            <td class="px-4 py-4 text-right sm:px-6">
                                <div
                                    class="text-base font-black tabular-nums text-white sm:text-lg"
                                >
                                    {{ formatStat(team.total_points) }}
                                </div>

                                <div
                                    class="mt-0.5 text-[9px] font-semibold uppercase tracking-widest text-slate-600"
                                >
                                    Career Total
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
                                    class="fas fa-users text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Team Records
                            </p>

                            <p class="mt-1 text-xs text-slate-600">
                                No teams matched your search.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div
            v-if="top_scorers.total"
            class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-6"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                >
                    {{ top_scorers.total }} Franchise Records
                </div>

                <div class="flex overflow-x-auto">
                    <Paginator
                        :page_number="search_topstats.page_num"
                        :total_rows="top_scorers.total ?? 0"
                        :itemsperpage="search_topstats.itemsperpage"
                        @page_num="handleTopScorerPagination"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import Paginator from "@/Components/Paginator.vue";
import { moneyFormatter } from "@/Utility/Formatter";

const top_scorers = ref({
    data: [],
    total: 0,
    total_pages: 0,
});

const loading = ref(false);

const search_topstats = ref({
    page_num: 1,
    itemsperpage: 10,
    sort_by: "total_points",
    search: "",
});

const statLabels = {
    total_points: "Points",
    total_rebounds: "Rebounds",
    total_assists: "Assists",
    total_steals: "Steals",
    total_blocks: "Blocks",
};

const statIcons = {
    total_points: "fa-basketball-ball",
    total_rebounds: "fa-circle",
    total_assists: "fa-hands",
    total_steals: "fa-hand-paper",
    total_blocks: "fa-shield-alt",
};

const selectedStatLabel = computed(() => {
    return statLabels[search_topstats.value.sort_by] ?? "Stat";
});

const selectedStatIcon = computed(() => {
    return statIcons[search_topstats.value.sort_by] ?? "fa-chart-bar";
});

const filterTopStats = async (
    page = search_topstats.value.page_num
) => {
    try {
        loading.value = true;

        search_topstats.value.page_num = page;

        const response = await axios.post(
            route("records.team.topscorer"),
            search_topstats.value
        );

        top_scorers.value = response.data ?? {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } catch (error) {
        console.error("Error fetching team statistics:", error);

        top_scorers.value = {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } finally {
        loading.value = false;
    }
};

const handleTopScorerPagination = (page_num) => {
    search_topstats.value.page_num = page_num;
    filterTopStats(page_num);
};

const formatStat = (value) => {
    if (value === null || value === undefined) {
        return "0";
    }

    return moneyFormatter(value);
};

const getTeamColor = (team) => {
    if (team?.primary_color) {
        return `#${String(team.primary_color).replace("#", "")}`;
    }

    if (team?.secondary_color) {
        return `#${String(team.secondary_color).replace("#", "")}`;
    }

    return "#64748b";
};

onMounted(() => {
    filterTopStats(1);
});
</script>