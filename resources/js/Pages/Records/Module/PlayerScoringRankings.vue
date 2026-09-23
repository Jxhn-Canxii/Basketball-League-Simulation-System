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
                            class="fas fa-chart-bar text-sm text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                        >
                            All-Time Top Stats
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Career statistical leaders across league history
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
                        v-model="search_leaders.search"
                        type="search"
                        autocomplete="off"
                        placeholder="Search player or team..."
                        class="w-full rounded-lg border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-10 text-sm text-slate-200 outline-none transition placeholder:text-slate-600 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                        @input="fetchLeaders(1)"
                    />

                    <button
                        v-if="search_leaders.search"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-800 hover:text-slate-300"
                        aria-label="Clear search"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Sort -->
                <div class="relative w-full lg:w-64">
                    <i
                        class="fas fa-sort-amount-down pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-600"
                    ></i>

                    <select
                        v-model="search_leaders.sort_by"
                        @change="fetchLeaders(1)"
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
            <table class="w-full min-w-[760px]">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#0d1117]">
                        <th
                            class="w-20 px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-600 sm:px-6"
                        >
                            Rank
                        </th>

                        <th
                            class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Player
                        </th>

                        <th
                            class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Team
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
                                <div
                                    class="h-4 w-6 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="h-4 w-40 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="h-4 w-28 animate-pulse rounded bg-slate-800"
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
                    <template v-else-if="leaders.data?.length">
                        <tr
                            v-for="(player, index) in leaders.data"
                            :key="player.id ?? index"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Rank -->
                            <td class="px-4 py-4 sm:px-6">
                                <div class="flex items-center">
                                    <span
                                        :class="[
                                            'flex h-7 w-7 items-center justify-center rounded-md text-xs font-black tabular-nums',
                                            getRankClass(player.rank)
                                        ]"
                                    >
                                        {{ player.rank }}
                                    </span>
                                </div>
                            </td>

                            <!-- Player -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <!-- Team Accent -->
                                    <div
                                        class="h-9 w-1 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                getTeamColor(player)
                                        }"
                                    ></div>

                                    <div class="min-w-0">
                                        <div
                                            :class="[
                                                'truncate text-sm uppercase tracking-wide',
                                                player.is_active
                                                    ? 'font-bold text-white'
                                                    : 'font-semibold text-slate-400'
                                            ]"
                                        >
                                            {{ player.player_name }}

                                            <span
                                                :class="[
                                                    'ml-1 text-[9px] font-bold',
                                                    player.is_active
                                                        ? 'text-slate-600'
                                                        : 'text-slate-700'
                                                ]"
                                            >
                                                {{
                                                    player.is_active
                                                        ? "ACTIVE"
                                                        : "RETIRED"
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Team -->
                            <td class="px-4 py-4">
                                <div
                                    class="truncate text-xs font-semibold uppercase tracking-wide text-slate-400"
                                >
                                    {{ player.team_name ?? "—" }}
                                </div>
                            </td>

                            <!-- Stat -->
                            <td class="px-4 py-4 text-right sm:px-6">
                                <div
                                    class="text-base font-black tabular-nums text-white sm:text-lg"
                                >
                                    {{
                                        formatStat(
                                            player.total_stat
                                        )
                                    }}
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
                        <td colspan="4" class="px-6 py-14 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-chart-bar text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Statistical Records
                            </p>

                            <p class="mt-1 text-xs text-slate-600">
                                No players or teams matched your search.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div
            v-if="leaders.total"
            class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-6"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                >
                    {{ leaders.total }} Career Records
                </div>

                <div class="flex overflow-x-auto">
                    <Paginator
                        :page_number="search_leaders.page_num"
                        :total_rows="leaders.total ?? 0"
                        :itemsperpage="search_leaders.itemsperpage"
                        @page_num="handleLeadersPagination"
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

const leaders = ref({
    data: [],
    total: 0,
    total_pages: 0,
});

const loading = ref(false);

const search_leaders = ref({
    page_num: 1,
    itemsperpage: 10,
    search: "",
    sort_by: "total_points",
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
    return statLabels[search_leaders.value.sort_by] ?? "Stat";
});

const selectedStatIcon = computed(() => {
    return statIcons[search_leaders.value.sort_by] ?? "fa-chart-bar";
});

const fetchLeaders = async (page = search_leaders.value.page_num) => {
    try {
        loading.value = true;

        search_leaders.value.page_num = page;

        const response = await axios.post(
            route("records.player.stats.leaders"),
            search_leaders.value
        );

        leaders.value = response.data ?? {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } catch (error) {
        console.error("Error fetching top leaders:", error);

        leaders.value = {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } finally {
        loading.value = false;
    }
};

const handleLeadersPagination = (page_num) => {
    search_leaders.value.page_num = page_num;
    fetchLeaders(page_num);
};

const clearSearch = () => {
    search_leaders.value.search = "";
    fetchLeaders(1);
};

const formatStat = (value) => {
    if (value === null || value === undefined) {
        return "0";
    }

    return moneyFormatter(value);
};

const getTeamColor = (player) => {
    if (player?.primary_color) {
        return `#${String(player.primary_color).replace("#", "")}`;
    }

    if (player?.secondary_color) {
        return `#${String(player.secondary_color).replace("#", "")}`;
    }

    return "#64748b";
};

const getRankClass = (rank) => {
    const numericRank = Number(rank);

    if (numericRank === 1) {
        return "border border-slate-600 bg-slate-700 text-white";
    }

    if (numericRank === 2) {
        return "border border-slate-700 bg-slate-800 text-slate-300";
    }

    if (numericRank === 3) {
        return "border border-slate-800 bg-slate-900 text-slate-400";
    }

    return "border border-slate-800 bg-[#080b10] text-slate-600";
};

onMounted(() => {
    fetchLeaders(1);
});
</script>