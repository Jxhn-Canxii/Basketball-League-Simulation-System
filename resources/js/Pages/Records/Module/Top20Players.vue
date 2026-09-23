<template>
    <div
        class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
    >
        <!-- Top Accent -->
        <div
            class="h-px w-full bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
        ></div>

        <!-- Header -->
        <div class="border-b border-slate-800 px-4 py-4 sm:px-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
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
                            Top 20 Players All-Time
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Career production, honors and statistical impact
                        </p>
                    </div>
                </div>

                <!-- Dataset Summary -->
                <div
                    v-if="players?.total"
                    class="flex items-center gap-3 self-start rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5 sm:self-auto"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-800"
                    >
                        <i
                            class="fas fa-users text-[10px] text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <div
                            class="text-lg font-black leading-none text-white"
                        >
                            {{ players.total }}
                        </div>

                        <div
                            class="mt-1 text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Players
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Info -->
        <div
            class="flex items-center justify-between border-b border-slate-800 bg-[#0a0d12] px-4 py-2.5 sm:px-6"
        >
            <div
                class="flex items-center gap-2 text-[9px] font-bold uppercase tracking-widest text-slate-600"
            >
                <i class="fas fa-chart-line text-[9px]"></i>
                Career Leaders
            </div>

            <div
                class="text-[9px] font-semibold uppercase tracking-widest text-slate-700"
            >
                Scroll horizontally for more
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1450px] border-collapse">
                <thead>
                    <tr
                        class="border-b border-slate-800 bg-[#0d1117] text-left"
                    >
                        <th
                            class="sticky left-0 z-20 w-16 border-r border-slate-800 bg-[#0d1117] px-4 py-3 text-center text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Rank
                        </th>

                        <th
                            class="sticky left-16 z-20 min-w-[230px] border-r border-slate-800 bg-[#0d1117] px-4 py-3 text-left text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Player
                        </th>

                        <th
                            class="min-w-[100px] px-4 py-3 text-left text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Status
                        </th>

                        <th
                            class="min-w-[170px] px-4 py-3 text-left text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Current Team
                        </th>

                        <th
                            class="min-w-[100px] px-4 py-3 text-center text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Finals MVP
                        </th>

                        <th
                            class="min-w-[260px] px-4 py-3 text-left text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Awards
                        </th>

                        <th
                            class="min-w-[120px] px-4 py-3 text-right text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Points
                        </th>

                        <th
                            class="min-w-[120px] px-4 py-3 text-right text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Assists
                        </th>

                        <th
                            class="min-w-[120px] px-4 py-3 text-right text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Rebounds
                        </th>

                        <th
                            class="min-w-[120px] px-4 py-3 text-right text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Blocks
                        </th>

                        <th
                            class="min-w-[120px] px-4 py-3 text-right text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Steals
                        </th>

                        <th
                            class="min-w-[145px] px-4 py-3 text-right text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Statistical Points
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Loading -->
                    <template v-if="loading">
                        <tr
                            v-for="index in 10"
                            :key="`loading-${index}`"
                            class="border-b border-slate-800/70"
                        >
                            <td
                                class="sticky left-0 z-10 border-r border-slate-800 bg-[#080b10] px-4 py-4"
                            >
                                <div
                                    class="mx-auto h-7 w-7 animate-pulse rounded-md bg-slate-800"
                                ></div>
                            </td>

                            <td
                                class="sticky left-16 z-10 border-r border-slate-800 bg-[#080b10] px-4 py-4"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-9 w-1 animate-pulse rounded-full bg-slate-800"
                                    ></div>

                                    <div
                                        class="h-4 w-36 animate-pulse rounded bg-slate-800"
                                    ></div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="h-5 w-16 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="h-4 w-28 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="mx-auto h-4 w-8 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="h-4 w-44 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>

                            <td
                                v-for="stat in 6"
                                :key="`loading-stat-${index}-${stat}`"
                                class="px-4 py-4"
                            >
                                <div
                                    class="ml-auto h-4 w-16 animate-pulse rounded bg-slate-800"
                                ></div>
                            </td>
                        </tr>
                    </template>

                    <!-- Players -->
                    <template v-else-if="players.data?.length">
                        <tr
                            v-for="(player, index) in players.data"
                            :key="player.player_id ?? index"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Rank -->
                            <td
                                class="sticky left-0 z-10 border-r border-slate-800 bg-[#080b10] px-4 py-3 text-center group-hover:bg-[#0b0f15]"
                            >
                                <span
                                    :class="[
                                        'mx-auto flex h-7 w-7 items-center justify-center rounded-md text-xs font-black tabular-nums',
                                        getRankClass(index)
                                    ]"
                                >
                                    {{ index + 1 }}
                                </span>
                            </td>

                            <!-- Player -->
                            <td
                                class="sticky left-16 z-10 border-r border-slate-800 bg-[#080b10] px-4 py-3 group-hover:bg-[#0b0f15]"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-9 w-1 shrink-0 rounded-full"
                                        :class="getPlayerAccentClass(index)"
                                    ></div>

                                    <div class="min-w-0">
                                        <div
                                            class="max-w-[190px] truncate text-sm font-bold uppercase tracking-wide text-white"
                                        >
                                            {{ player.player_name }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-[9px] font-semibold uppercase tracking-widest text-slate-600"
                                        >
                                            Career Leader
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3">
                                <span
                                    v-if="player.is_active"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/70 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-slate-400"
                                    ></span>
                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-md border border-slate-800 bg-[#0a0d12] px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-600"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-slate-700"
                                    ></span>
                                    Retired
                                </span>
                            </td>

                            <!-- Current Team -->
                            <td class="px-4 py-3">
                                <div
                                    v-if="player.is_active"
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="h-5 w-1 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                getTeamColor(player)
                                        }"
                                    ></div>

                                    <span
                                        class="max-w-[145px] truncate text-xs font-semibold text-slate-300"
                                    >
                                        {{
                                            player.current_team_name ??
                                            "Free Agent"
                                        }}
                                    </span>
                                </div>

                                <span
                                    v-else
                                    class="text-xs font-medium text-slate-700"
                                >
                                    —
                                </span>
                            </td>

                            <!-- Finals MVP -->
                            <td class="px-4 py-3 text-center">
                                <div
                                    class="inline-flex min-w-[42px] items-center justify-center rounded-md border border-slate-800 bg-[#0a0d12] px-2 py-1"
                                >
                                    <span
                                        class="text-sm font-black tabular-nums text-slate-200"
                                    >
                                        {{ player.finals_mvp_count ?? 0 }}
                                    </span>
                                </div>
                            </td>

                            <!-- Awards -->
                            <td class="px-4 py-3">
                                <div
                                    class="max-w-[240px] text-xs leading-relaxed text-slate-400"
                                >
                                    {{
                                        player.all_awards ??
                                        "No major awards"
                                    }}
                                </div>
                            </td>

                            <!-- Points -->
                            <td class="px-4 py-3 text-right">
                                <span
                                    class="text-sm font-bold tabular-nums text-white"
                                >
                                    {{ formatNumber(player.total_points) }}
                                </span>
                            </td>

                            <!-- Assists -->
                            <td class="px-4 py-3 text-right">
                                <span
                                    class="text-sm font-semibold tabular-nums text-slate-300"
                                >
                                    {{ formatNumber(player.total_assists) }}
                                </span>
                            </td>

                            <!-- Rebounds -->
                            <td class="px-4 py-3 text-right">
                                <span
                                    class="text-sm font-semibold tabular-nums text-slate-300"
                                >
                                    {{ formatNumber(player.total_rebounds) }}
                                </span>
                            </td>

                            <!-- Blocks -->
                            <td class="px-4 py-3 text-right">
                                <span
                                    class="text-sm font-semibold tabular-nums text-slate-300"
                                >
                                    {{ formatNumber(player.total_blocks) }}
                                </span>
                            </td>

                            <!-- Steals -->
                            <td class="px-4 py-3 text-right">
                                <span
                                    class="text-sm font-semibold tabular-nums text-slate-300"
                                >
                                    {{ formatNumber(player.total_steals) }}
                                </span>
                            </td>

                            <!-- Statistical Points -->
                            <td class="px-4 py-3 text-right">
                                <div
                                    class="inline-flex min-w-[90px] flex-col items-end"
                                >
                                    <span
                                        class="text-base font-black tabular-nums text-white"
                                    >
                                        {{
                                            formatNumber(
                                                player.base_statistical_points
                                            )
                                        }}
                                    </span>

                                    <span
                                        class="mt-0.5 text-[8px] font-bold uppercase tracking-widest text-slate-600"
                                    >
                                        Impact
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else>
                        <td colspan="12" class="px-6 py-16 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-user-slash text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Players Found
                            </p>

                            <p class="mt-1 text-xs text-slate-600">
                                There are no all-time player records available.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div
            v-if="players.total"
            class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-6"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                >
                    All-Time Player Records
                </div>

                <div class="flex overflow-x-auto">
                    <Paginator
                        :page_number="search_filters.page_num"
                        :total_rows="players.total ?? 0"
                        :itemsperpage="search_filters.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import Paginator from "@/Components/Paginator.vue";
import axios from "axios";

const props = defineProps({
    team_id: {
        type: Number,
        default: null,
    },
});

const players = ref({
    data: [],
    total: 0,
    total_pages: 0,
});

const loading = ref(false);

const search_filters = ref({
    page_num: 1,
    itemsperpage: 20,
});

const fetchTopPlayers = async (
    page = search_filters.value.page_num
) => {
    try {
        loading.value = true;

        search_filters.value.page_num = page;

        const response = await axios.get(
            route("best.players.alltime")
        );

        players.value = response.data ?? {
            data: [],
            total: 0,
            total_pages: 0,
        };

        console.log("loaded top 20 module");
    } catch (error) {
        console.error(
            "Error fetching all-time player records:",
            error
        );

        players.value = {
            data: [],
            total: 0,
            total_pages: 0,
        };
    } finally {
        loading.value = false;
    }
};

const handlePagination = (page_num) => {
    search_filters.value.page_num = page_num;
    fetchTopPlayers(page_num);
};

const formatNumber = (value) => {
    if (value === null || value === undefined) {
        return "0";
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return value;
    }

    return new Intl.NumberFormat("en-US", {
        maximumFractionDigits: 0,
    }).format(number);
};

const getRankClass = (index) => {
    if (index === 0) {
        return "border border-slate-600 bg-slate-700 text-white";
    }

    if (index === 1) {
        return "border border-slate-700 bg-slate-800 text-slate-300";
    }

    if (index === 2) {
        return "border border-slate-800 bg-slate-900 text-slate-400";
    }

    return "border border-slate-800 bg-[#080b10] text-slate-600";
};

const getPlayerAccentClass = (index) => {
    if (index === 0) {
        return "bg-slate-400";
    }

    if (index === 1) {
        return "bg-slate-500";
    }

    if (index === 2) {
        return "bg-slate-600";
    }

    return "bg-slate-800";
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

onMounted(() => {
    fetchTopPlayers(1);
});
</script>