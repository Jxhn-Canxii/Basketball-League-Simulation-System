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
                            class="fas fa-ranking-star text-sm text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                        >
                            Team Rankings All-Time
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Franchise records across league history
                        </p>
                    </div>
                </div>

                <!-- Total Teams -->
                <div
                    v-if="top_teams?.total"
                    class="flex items-center gap-3 self-start rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5 lg:self-auto"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-800"
                    >
                        <i
                            class="fas fa-layer-group text-[10px] text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <div
                            class="text-lg font-black leading-none text-white"
                        >
                            {{ top_teams.total }}
                        </div>

                        <div
                            class="mt-1 text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Franchises
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div
            class="border-b border-slate-800 bg-[#0a0d12] px-4 py-4 sm:px-6"
        >
            <div class="relative w-full lg:max-w-xl">
                <i
                    class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-600"
                ></i>

                <input
                    id="LeagueName"
                    v-model="search_topteams.search"
                    type="search"
                    autocomplete="off"
                    placeholder="Search team..."
                    class="w-full rounded-lg border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-4 text-sm text-slate-200 outline-none transition placeholder:text-slate-600 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                    @input="fetchTopTeams(1)"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px]">
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
                            Team
                        </th>

                        <th
                            class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Conference
                        </th>

                        <th
                            class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            W
                        </th>

                        <th
                            class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            L
                        </th>

                        <th
                            class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-600 sm:px-6"
                        >
                            Win Rate
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
                                    class="h-7 w-7 animate-pulse rounded-md bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-9 w-1 animate-pulse rounded-full bg-slate-800"
                                    ></div>

                                    <div
                                        class="h-4 w-40 animate-pulse rounded bg-slate-800"
                                    ></div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="mx-auto h-6 w-24 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="mx-auto h-4 w-8 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="mx-auto h-4 w-8 animate-pulse rounded bg-slate-800"
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
                    <template v-else-if="top_teams.data?.length">
                        <tr
                            v-for="(team, index) in top_teams.data"
                            :key="team.id ?? index"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Rank -->
                            <td class="px-4 py-4 sm:px-6">
                                <span
                                    :class="[
                                        'flex h-7 w-7 items-center justify-center rounded-md text-xs font-black tabular-nums',
                                        getRankClass(index, team)
                                    ]"
                                >
                                    {{ getRank(team, index) }}
                                </span>
                            </td>

                            <!-- Team -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
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

                            <!-- Wins -->
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="text-sm font-black tabular-nums text-white"
                                >
                                    {{ team.total_wins ?? 0 }}
                                </span>
                            </td>

                            <!-- Losses -->
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="text-sm font-bold tabular-nums text-slate-500"
                                >
                                    {{ team.total_losses ?? 0 }}
                                </span>
                            </td>

                            <!-- Win Rate -->
                            <td class="px-4 py-4 sm:px-6">
                                <div class="flex flex-col items-end">
                                    <div
                                        class="text-base font-black tabular-nums text-white sm:text-lg"
                                    >
                                        {{ formatWinRate(team.win_rate) }}%
                                    </div>

                                    <div
                                        class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-slate-800 sm:w-28"
                                    >
                                        <div
                                            class="h-full rounded-full bg-slate-500 transition-all duration-500"
                                            :style="{
                                                width: `${getWinRate(team)}%`
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else>
                        <td colspan="6" class="px-6 py-14 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-ranking-star text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Team Rankings
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
            v-if="top_teams.total"
            class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-6"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                >
                    All-Time Franchise Records
                </div>

                <div class="flex overflow-x-auto">
                    <Paginator
                        :page_number="search_topteams.page_num"
                        :total_rows="top_teams.total ?? 0"
                        :itemsperpage="search_topteams.itemsperpage"
                        @page_num="handleTopScorerPagination"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import Paginator from "@/Components/Paginator.vue";
import { moneyFormatter } from "@/Utility/Formatter";

const top_teams = ref({
    data: [],
    total: 0,
    total_pages: 0,
});

const loading = ref(false);

const search_topteams = ref({
    page_num: 1,
    itemsperpage: 10,
    total_pages: 0,
    total: 0,
    search: "",
});

const fetchTopTeams = async (
    page = search_topteams.value.page_num
) => {
    try {
        loading.value = true;

        search_topteams.value.page_num = page;

        const response = await axios.post(
            route("records.team.winningest"),
            search_topteams.value
        );

        top_teams.value = response.data ?? {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } catch (error) {
        console.error("Error fetching team rankings:", error);

        top_teams.value = {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } finally {
        loading.value = false;
    }
};

const handleTopScorerPagination = (page_num) => {
    search_topteams.value.page_num = page_num;
    fetchTopTeams(page_num);
};

const getRank = (team, index) => {
    return team.rank ?? (
        (search_topteams.value.page_num - 1) *
            search_topteams.value.itemsperpage +
        index +
        1
    );
};

const getRankClass = (index, team) => {
    const rank = Number(getRank(team, index));

    if (rank === 1) {
        return "border border-slate-600 bg-slate-700 text-white";
    }

    if (rank === 2) {
        return "border border-slate-700 bg-slate-800 text-slate-300";
    }

    if (rank === 3) {
        return "border border-slate-800 bg-slate-900 text-slate-400";
    }

    return "border border-slate-800 bg-[#080b10] text-slate-600";
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

const getWinRate = (team) => {
    const wins = Number(team?.total_wins ?? 0);
    const losses = Number(team?.total_losses ?? 0);
    const total = wins + losses;

    if (!total) {
        return 0;
    }

    return Math.min(100, Math.max(0, (wins / total) * 100));
};

const formatWinRate = (value) => {
    if (value === null || value === undefined) {
        return "0";
    }

    return moneyFormatter(value);
};

onMounted(() => {
    fetchTopTeams(1);
});
</script>