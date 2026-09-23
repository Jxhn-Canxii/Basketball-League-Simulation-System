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
                            <svg
                                class="h-5 w-5 text-amber-300"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3l2.7 5.47 6.03.88-4.36 4.25 1.03 6.01L12 18.77l-5.4 2.84 1.03-6.01-4.36-4.25 6.03-.88L12 3Z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    class="truncate text-sm font-semibold tracking-wide text-white"
                                >
                                    Player Career Highs
                                </h2>

                                <span
                                    v-if="transactions.length > 0"
                                    class="rounded-full border border-amber-400/15 bg-amber-400/[0.07] px-2 py-0.5 text-[10px] font-semibold text-amber-300"
                                >
                                    {{ transactions.length }} Records
                                </span>
                            </div>

                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Personal best statistical achievements
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
                                    <svg
                                        class="h-4 w-4 text-amber-300"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 3l2.7 5.47 6.03.88-4.36 4.25 1.03 6.01L12 18.77l-5.4 2.84 1.03-6.01-4.36-4.25 6.03-.88L12 3Z"
                                        />
                                    </svg>
                                </div>

                                <span
                                    class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-600"
                                >
                                    Career High
                                </span>
                            </div>

                            <!-- Status -->
                            <span
                                v-if="transaction.status"
                                class="rounded-full border px-2 py-1 text-[9px] font-semibold uppercase tracking-wide"
                                :class="
                                    statusClasses(
                                        transaction.status
                                    )
                                "
                            >
                                {{
                                    formatText(transaction.status)
                                }}
                            </span>
                        </div>

                        <!-- Main Value -->
                        <div
                            class="relative flex min-h-[120px] flex-col items-center justify-center px-4 py-5 text-center"
                        >
                            <div
                                class="text-5xl font-black leading-none tracking-tight text-white sm:text-6xl"
                            >
                                {{ transaction.stats_value }}
                            </div>

                            <div
                                class="mt-3 text-xs font-bold uppercase tracking-[0.12em] text-amber-300"
                            >
                                {{
                                    formatText(
                                        transaction.stats_type
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
                                <svg
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-600"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="9"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        d="M12 10v6"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        d="M12 7h.01"
                                    />
                                </svg>

                                <p
                                    class="text-[11px] leading-5 text-slate-500"
                                >
                                    {{
                                        formatText(
                                            transaction.details
                                        )
                                    }}

                                    <span class="text-slate-700">
                                        ·
                                    </span>

                                    <span
                                        class="font-medium text-slate-400"
                                    >
                                        Season
                                        {{ transaction.season_id }}
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
                        <svg
                            class="h-7 w-7 text-slate-600"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3l2.7 5.47 6.03.88-4.36 4.25 1.03 6.01L12 18.77l-5.4 2.84 1.03-6.01-4.36-4.25 6.03-.88L12 3Z"
                            />
                        </svg>
                    </div>

                    <p
                        class="text-sm font-medium text-slate-300"
                    >
                        No career highs available
                    </p>

                    <p
                        class="mt-1 max-w-sm text-xs leading-5 text-slate-600"
                    >
                        Career-high records will appear here once
                        the player has recorded statistical milestones.
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
| Status
|--------------------------------------------------------------------------
*/

const statusClasses = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("record") ||
        value.includes("career") ||
        value.includes("active") ||
        value.includes("current")
    ) {
        return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";
    }

    if (
        value.includes("season") ||
        value.includes("best")
    ) {
        return "border-amber-400/20 bg-amber-400/10 text-amber-300";
    }

    if (
        value.includes("broken") ||
        value.includes("previous")
    ) {
        return "border-sky-400/20 bg-sky-400/10 text-sky-300";
    }

    return "border-white/[0.08] bg-white/[0.04] text-slate-500";
};

/*
|--------------------------------------------------------------------------
| Player Initial Data
|--------------------------------------------------------------------------
*/

const fetchPlayerCareerHighs = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("players.career.highs"),
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
    fetchPlayerCareerHighs();
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
        fetchPlayerCareerHighs();
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