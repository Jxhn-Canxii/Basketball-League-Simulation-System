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
            class="flex flex-col gap-3 border-b border-slate-800 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                >
                    <i
                        class="fas fa-fire text-sm text-slate-300"
                    ></i>
                </div>

                <div>
                    <h3
                        class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                    >
                        Top Rivals
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Most competitive team matchups
                    </p>
                </div>
            </div>

            <div
                v-if="rivals?.data?.length"
                class="flex items-center gap-2 self-start rounded-md border border-slate-800 bg-[#0d1117] px-3 py-2 sm:self-auto"
            >
                <i
                    class="fas fa-bolt text-[10px] text-slate-500"
                ></i>

                <span
                    class="text-[10px] font-bold uppercase tracking-widest text-slate-500"
                >
                    Rivalries
                </span>

                <span class="text-xs font-bold text-white">
                    {{ rivals.data.length }}
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="p-3 sm:p-4 lg:p-5">
            <!-- Loading -->
            <div
                v-if="loading"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
            >
                <div
                    v-for="index in 5"
                    :key="`loading-${index}`"
                    class="overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]"
                >
                    <div class="border-b border-slate-800 px-4 py-4">
                        <div
                            class="h-3 w-3/4 animate-pulse rounded bg-slate-800"
                        ></div>

                        <div
                            class="mt-2 h-2.5 w-1/2 animate-pulse rounded bg-slate-800"
                        ></div>
                    </div>

                    <div class="space-y-2 p-4">
                        <div
                            class="h-10 animate-pulse rounded bg-slate-800"
                        ></div>

                        <div
                            class="h-10 animate-pulse rounded bg-slate-800"
                        ></div>

                        <div
                            class="mt-3 h-3 w-full animate-pulse rounded bg-slate-800"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Rivalries -->
            <div
                v-else-if="rivals?.data?.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
            >
                <div
                    v-for="(team, index) in rivals.data"
                    :key="team.id ?? index"
                    class="group overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117] transition-all duration-200 hover:border-slate-700 hover:bg-[#10151c]"
                >
                    <!-- Matchup Header -->
                    <div class="border-b border-slate-800 px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="text-[9px] font-black uppercase tracking-widest text-slate-600"
                            >
                                Rivalry {{ String(index + 1).padStart(2, "0") }}
                            </span>

                            <i
                                class="fas fa-arrows-left-right text-[9px] text-slate-700"
                            ></i>
                        </div>

                        <div class="mt-2">
                            <div
                                class="truncate text-xs font-bold uppercase tracking-wide text-white"
                            >
                                {{ team.team_name }}
                            </div>

                            <div
                                class="mt-0.5 flex items-center gap-2"
                            >
                                <span
                                    class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                                >
                                    vs
                                </span>

                                <span
                                    class="truncate text-xs font-bold uppercase tracking-wide text-slate-300"
                                >
                                    {{ team.opponent_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Record -->
                    <div class="space-y-2 p-3">
                        <!-- Wins -->
                        <div
                            :class="[
                                'flex items-center justify-between rounded-lg border px-3 py-2.5',
                                Number(team.wins) > Number(team.losses)
                                    ? 'border-slate-600 bg-slate-800/50'
                                    : 'border-slate-800 bg-[#080b10]'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    :class="[
                                        'flex h-7 w-7 items-center justify-center rounded-md text-[9px] font-black',
                                        Number(team.wins) >
                                        Number(team.losses)
                                            ? 'bg-slate-700 text-white'
                                            : 'bg-slate-900 text-slate-600'
                                    ]"
                                >
                                    W
                                </div>

                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-500"
                                >
                                    Wins
                                </span>
                            </div>

                            <span
                                :class="[
                                    'text-lg font-black tabular-nums',
                                    Number(team.wins) > Number(team.losses)
                                        ? 'text-white'
                                        : 'text-slate-400'
                                ]"
                            >
                                {{ team.wins ?? 0 }}
                            </span>
                        </div>

                        <!-- Losses -->
                        <div
                            :class="[
                                'flex items-center justify-between rounded-lg border px-3 py-2.5',
                                Number(team.losses) > Number(team.wins)
                                    ? 'border-slate-600 bg-slate-800/50'
                                    : 'border-slate-800 bg-[#080b10]'
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    :class="[
                                        'flex h-7 w-7 items-center justify-center rounded-md text-[9px] font-black',
                                        Number(team.losses) >
                                        Number(team.wins)
                                            ? 'bg-slate-700 text-white'
                                            : 'bg-slate-900 text-slate-600'
                                    ]"
                                >
                                    L
                                </div>

                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-slate-500"
                                >
                                    Losses
                                </span>
                            </div>

                            <span
                                :class="[
                                    'text-lg font-black tabular-nums',
                                    Number(team.losses) > Number(team.wins)
                                        ? 'text-white'
                                        : 'text-slate-400'
                                ]"
                            >
                                {{ team.losses ?? 0 }}
                            </span>
                        </div>
                    </div>

                    <!-- Win Percentage -->
                    <div
                        class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3"
                    >
                        <div
                            class="flex items-center justify-between"
                        >
                            <span
                                class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                            >
                                Win Rate
                            </span>

                            <span
                                class="text-xs font-black tabular-nums text-slate-300"
                            >
                                {{ winPercentage(team) }}%
                            </span>
                        </div>

                        <!-- Progress -->
                        <div
                            class="mt-2 h-1 overflow-hidden rounded-full bg-slate-800"
                        >
                            <div
                                class="h-full rounded-full bg-slate-500 transition-all duration-500"
                                :style="{
                                    width: `${winPercentage(team)}%`
                                }"
                            ></div>
                        </div>

                        <!-- Edge -->
                        <div
                            class="mt-2 flex items-center justify-between"
                        >
                            <span
                                class="text-[9px] uppercase tracking-wide text-slate-700"
                            >
                                Historical record
                            </span>

                            <span
                                v-if="Number(team.wins) !== Number(team.losses)"
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                {{
                                    Number(team.wins) >
                                    Number(team.losses)
                                        ? "Team edge"
                                        : "Opponent edge"
                                }}
                            </span>

                            <span
                                v-else
                                class="text-[9px] font-bold uppercase tracking-wider text-slate-600"
                            >
                                Even
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div
                v-else
                class="flex min-h-[240px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-800 bg-[#0a0d12] px-6 text-center"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                >
                    <i
                        class="fas fa-fire text-sm text-slate-600"
                    ></i>
                </div>

                <p
                    class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                >
                    No Rivalries Found
                </p>

                <p class="mt-1 text-xs text-slate-600">
                    There are no historical rivalry records available.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";

const rivals = ref({
    data: [],
});

const loading = ref(false);

const fetchRivalry = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("records.rivalries")
        );

        rivals.value = response.data ?? {
            data: [],
        };
    } catch (error) {
        console.error("Error fetching rivalries:", error);

        rivals.value = {
            data: [],
        };
    } finally {
        loading.value = false;
    }
};

const winPercentage = (team) => {
    const wins = Number(team?.wins ?? 0);
    const losses = Number(team?.losses ?? 0);
    const total = wins + losses;

    if (!total) {
        return 0;
    }

    return Math.round((wins / total) * 100);
};

onMounted(() => {
    fetchRivalry();
});
</script>