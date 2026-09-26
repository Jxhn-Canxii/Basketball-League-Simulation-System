<template>
    <div class="w-full">
        <section
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0b0d10] shadow-[0_20px_70px_rgba(0,0,0,0.4)]"
        >
            <!-- Header -->
            <div
                class="border-b border-white/[0.07] bg-gradient-to-r from-white/[0.04] via-transparent to-transparent"
            >
                <div
                    class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-400/15 bg-amber-400/[0.07]"
                        >
                            <i class="fa fa-medal"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    class="truncate text-sm font-semibold tracking-wide text-white"
                                >
                                    Player Career Awards
                                </h2>

                                <span
                                    v-if="transactions.length > 0"
                                    class="rounded-full border border-amber-400/15 bg-amber-400/[0.07] px-2 py-0.5 text-[10px] font-semibold text-amber-300"
                                >
                                    {{ transactions.length }} Awards
                                </span>
                            </div>

                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Personal awards and achievements
                            </p>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div
                        v-if="!loading && transactions.length"
                        class="flex items-center gap-2"
                    >
                        <div
                            class="rounded-xl border border-white/[0.06] bg-white/[0.025] px-3 py-2 text-right"
                        >
                            <div
                                class="text-[9px] uppercase tracking-wider text-slate-600"
                            >
                                Records
                            </div>

                            <div
                                class="mt-0.5 text-sm font-bold tabular-nums text-white"
                            >
                                {{ transactions.length }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-4 sm:p-5">
                <!-- Loading -->
                <div
                    v-if="loading"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <div
                        v-for="i in 8"
                        :key="`skeleton-${i}`"
                        class="min-h-[175px] animate-pulse rounded-2xl border border-white/[0.06] bg-white/[0.025] p-4"
                    >
                        <div class="flex justify-between">
                            <div
                                class="h-7 w-7 rounded-lg bg-white/[0.07]"
                            ></div>

                            <div
                                class="h-4 w-12 rounded bg-white/[0.07]"
                            ></div>
                        </div>

                        <div
                            class="mx-auto mt-7 h-12 w-24 rounded bg-white/[0.07]"
                        ></div>

                        <div
                            class="mx-auto mt-3 h-3 w-28 rounded bg-white/[0.07]"
                        ></div>

                        <div
                            class="mx-auto mt-3 h-2 w-40 rounded bg-white/[0.05]"
                        ></div>
                    </div>
                </div>

                <!-- Career High Cards -->
                <div
                    v-else-if="transactions.length"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <article
                        v-for="(transaction, index) in transactions"
                        :key="transaction.id ?? index"
                        class="group relative overflow-hidden rounded-2xl border border-white/[0.07] bg-[#101318] transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-300/20 hover:bg-[#13171d] hover:shadow-[0_12px_35px_rgba(0,0,0,0.3)]"
                    >
                        <!-- Accent glow -->
                        <div
                            class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full bg-amber-400/[0.05] blur-3xl transition-opacity group-hover:bg-amber-400/[0.09]"
                        ></div>

                        <!-- Top -->
                        <div
                            class="relative flex items-center justify-between border-b border-white/[0.05] px-4 py-3"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-amber-400/15 bg-amber-400/[0.07]"
                                >
                                    <i class="fa fa-medal"></i>
                                </div>

                                <span
                                    class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600"
                                >
                                    Awards
                                </span>
                            </div>

                            <!-- Status -->
                            <span
                                v-if="transaction.team_name"
                                :style="{ 
                                    color: '#'+transaction.primary_color,
                                    backgroundColor: '#'+transaction.secondary_color,
                                }"
                                class="rounded-full border px-2 py-1 text-[9px] font-semibold uppercase tracking-wide"
                            >
                                {{
                                    formatText(transaction.team_name)
                                }}
                            </span>
                        </div>

                        <!-- Main Value -->
                        <div
                            class="relative flex max-h-[170px] flex-col items-center justify-center px-4 py-5 text-center"
                        >
                            <div
                                class="text-xl font-black leading-none tracking-tight capitalize text-white sm:text-3xl"
                            >
                                {{ transaction.award_name }}
                            </div>

                            <div
                                class="mt-3 text-xs font-bold uppercase tracking-[0.12em] text-amber-300"
                            >
                                {{
                                    formatText(
                                        transaction.description
                                    )
                                }}
                            </div>
                        </div>

                        <!-- Details -->
                        <div
                            class="relative border-t border-white/[0.05] bg-black/[0.12] px-4 py-3"
                        >
                            <div
                                class="flex items-start gap-2"
                            >
                                <i class="fa fa-info-circle"></i>

                                <p
                                    class="text-[11px] leading-5 text-slate-500"
                                >
                                    {{
                                        formatText(
                                            transaction.acronym
                                        )
                                    }}

                                    <span class="text-slate-700">
                                        ·
                                    </span>

                                    <span
                                        class="font-medium text-slate-400"
                                    >
                                        
                                        {{ transaction.season_name }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Bottom Accent -->
                        <div
                            class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber-400/30 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        ></div>
                    </article>
                </div>

                <!-- Empty -->
                <div
                    v-else
                    class="flex min-h-[260px] flex-col items-center justify-center text-center"
                >
                    <div
                        class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.025]"
                    >
                        <i class="fa fa-medal"></i>
                    </div>

                    <p
                        class="text-sm font-medium text-slate-300"
                    >
                        No awards available
                    </p>

                    <p
                        class="mt-1 max-w-sm text-xs leading-5 text-slate-600"
                    >
                        Career awards records will appear here once
                        the player has received awards from the league.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";

const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});

const transactions = ref([]);
const player_id = ref(props.player_id);
const loading = ref(true);

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatText = (value) => {
    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .replaceAll("-", " ")
        .replace(/\s+/g, " ")
        .trim();
};

/*
|--------------------------------------------------------------------------
| Player Initial Data
|--------------------------------------------------------------------------
*/

const fetchPlayerCareerAwards = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("players.awards.history"),
            {
                player_id: player_id.value,
            }
        );

        transactions.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching player career highs:",
            error
        );

        transactions.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchPlayerCareerAwards();
});

/*
|--------------------------------------------------------------------------
| Player Change
|--------------------------------------------------------------------------
*/

watch(
    () => props.player_id,
    (newPlayerId) => {
        if (!newPlayerId) return;

        player_id.value = newPlayerId;
        fetchPlayerCareerAwards();
    }
);
</script>

<style scoped>
/* Keep horizontal scrolling smooth if the component is placed
   inside a narrower parent in the future. */
:deep(.scrollbar-thin) {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.18) transparent;
}
</style>