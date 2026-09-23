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
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                    >
                        <i
                            class="fas fa-trophy text-sm text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <h3
                            class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                        >
                            Top 16 Playoff Teams
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Franchises with the most playoff appearances
                        </p>
                    </div>
                </div>

                <!-- Record Count -->
                <div
                    v-if="playoffs?.data?.length"
                    class="flex items-center gap-3 self-start rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5 sm:self-auto"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-800"
                    >
                        <i
                            class="fas fa-list-ol text-[10px] text-slate-300"
                        ></i>
                    </div>

                    <div>
                        <div
                            class="text-lg font-black leading-none text-white"
                        >
                            {{ playoffs.data.length }}
                        </div>

                        <div
                            class="mt-1 text-[9px] font-bold uppercase tracking-widest text-slate-600"
                        >
                            Ranked Teams
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 sm:p-6">
            <!-- Loading -->
            <div
                v-if="loading"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="index in 16"
                    :key="`loading-${index}`"
                    class="overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]"
                >
                    <div class="h-1 animate-pulse bg-slate-800"></div>

                    <div class="p-4">
                        <div
                            class="h-4 w-32 animate-pulse rounded bg-slate-800"
                        ></div>

                        <div
                            class="mt-2 h-5 w-20 animate-pulse rounded bg-slate-900"
                        ></div>

                        <div
                            class="mt-5 h-px w-full bg-slate-800"
                        ></div>

                        <div
                            class="mt-4 flex items-end justify-between"
                        >
                            <div
                                class="h-3 w-20 animate-pulse rounded bg-slate-800"
                            ></div>

                            <div
                                class="h-9 w-14 animate-pulse rounded bg-slate-800"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teams -->
            <div
                v-else-if="playoffs?.data?.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="(team, index) in playoffs.data"
                    :key="team.id ?? index"
                    class="group relative overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117] transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-700 hover:bg-[#0f141b]"
                >
                    <!-- Team Color Accent -->
                    <div
                        class="h-1 w-full"
                        :style="{
                            backgroundColor: getTeamColor(team)
                        }"
                    ></div>

                    <div class="p-4">
                        <!-- Team Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-slate-800 bg-[#080b10] text-[9px] font-black tabular-nums text-slate-600"
                                    >
                                        {{ index + 1 }}
                                    </span>

                                    <h4
                                        class="truncate text-xs font-bold uppercase tracking-wide text-white"
                                        :title="team.team_name"
                                    >
                                        {{ team.team_name }}
                                    </h4>
                                </div>

                                <div class="mt-2">
                                    <span
                                        class="inline-flex items-center rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-slate-500"
                                    >
                                        <i
                                            class="fas fa-location-dot mr-1.5 text-[8px] text-slate-700"
                                        ></i>

                                        {{
                                            team.conference_name ??
                                            "Conference —"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10]"
                            >
                                <i
                                    class="fas fa-basketball text-[10px] text-slate-600 transition-colors group-hover:text-slate-400"
                                ></i>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div
                            class="my-4 h-px bg-slate-800"
                        ></div>

                        <!-- Main Stat -->
                        <div
                            class="flex items-end justify-between gap-4"
                        >
                            <div>
                                <div
                                    class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-600"
                                >
                                    Playoff Appearances
                                </div>

                                <div
                                    class="mt-1 text-[10px] leading-relaxed text-slate-500"
                                >
                                    Postseason history
                                </div>
                            </div>

                            <div
                                class="text-3xl font-black leading-none tabular-nums text-white"
                            >
                                {{ team.playoff_appearances ?? 0 }}
                            </div>
                        </div>

                        <!-- Bottom Progress -->
                        <div class="mt-4">
                            <div
                                class="mb-1.5 flex items-center justify-between"
                            >
                                <span
                                    class="text-[8px] font-bold uppercase tracking-widest text-slate-700"
                                >
                                    Historical Presence
                                </span>

                                <span
                                    class="text-[8px] font-bold tabular-nums text-slate-600"
                                >
                                    #{{ index + 1 }}
                                </span>
                            </div>

                            <div
                                class="h-1 overflow-hidden rounded-full bg-slate-800"
                            >
                                <div
                                    class="h-full rounded-full bg-slate-500 transition-all duration-500"
                                    :style="{
                                        width: `${getAppearanceWidth(team)}%`
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-else
                class="flex min-h-[260px] flex-col items-center justify-center text-center"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                >
                    <i
                        class="fas fa-trophy text-sm text-slate-600"
                    ></i>
                </div>

                <p
                    class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                >
                    No Playoff Records
                </p>

                <p class="mt-1 max-w-sm text-xs text-slate-600">
                    There are currently no playoff appearance records to
                    display.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";

const playoffs = ref({
    data: [],
});

const loading = ref(false);

const fetchMostPlayoffAppearance = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("records.playoff.appearances")
        );

        playoffs.value = response.data ?? {
            data: [],
        };
    } catch (error) {
        console.error(
            "Error fetching playoff appearance records:",
            error
        );

        playoffs.value = {
            data: [],
        };
    } finally {
        loading.value = false;
    }
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

const getAppearanceWidth = (team) => {
    const values = (playoffs.value?.data ?? []).map((item) =>
        Number(item?.playoff_appearances ?? 0)
    );

    const max = Math.max(...values, 0);
    const current = Number(team?.playoff_appearances ?? 0);

    if (!max) {
        return 0;
    }

    return Math.min(100, Math.max(0, (current / max) * 100));
};

onMounted(() => {
    fetchMostPlayoffAppearance();
});
</script>