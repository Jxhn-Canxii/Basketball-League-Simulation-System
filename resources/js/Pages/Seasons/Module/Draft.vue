<template>
    <div class="min-h-screen bg-[#050505] text-white">
        <!-- TOP BAR -->
        <div
            class="sticky top-0 z-30 border-b border-white/10 bg-black/95 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-3"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600"
                        >
                            <i class="fa fa-basketball-ball text-lg"></i>
                        </div>

                        <div>
                            <h1 class="text-xl font-black tracking-tight">
                                DRAFT NIGHT
                            </h1>

                            <p class="text-[10px] uppercase tracking-[0.25em] text-gray-500">
                                Rookie Draft
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="!isHide"
                        @click.prevent="addMultiplePlayers(400)"
                        class="rounded-lg bg-green-600 px-3 py-2 text-xs font-bold shadow-lg transition hover:bg-green-500"
                    >
                        <i class="fa fa-user mr-1"></i>
                        Add Rookies
                    </button>

                    <button
                        v-if="!isHide && !draftFinished"
                        @click.prevent="draftPlayer"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-black uppercase tracking-wide shadow-lg transition hover:bg-blue-500"
                    >
                        <i class="fa fa-bolt mr-1"></i>
                        Start / Draft
                    </button>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-[1600px] px-3 py-4 md:px-6">
            <!-- STATS -->
            <div class="mb-4 rounded-xl border border-white/10 bg-[#0b0b0b] p-2">
                <TopStatistics :key="key" />
            </div>

            <!-- ========================================= -->
            <!-- DRAFT STAGE                              -->
            <!-- ========================================= -->

            <div
                class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]"
            >
                <!-- MAIN STAGE -->
                <main>
                    <!-- EMPTY STATE -->
                    <div
                        v-if="!currentPick"
                        class="flex min-h-[620px] items-center justify-center rounded-2xl border border-white/10 bg-gradient-to-br from-[#111] to-[#050505]"
                    >
                        <div class="text-center">
                            <div
                                class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full border border-white/10 bg-white/5"
                            >
                                <i
                                    class="fa fa-basketball-ball text-3xl text-gray-500"
                                ></i>
                            </div>

                            <h2 class="text-3xl font-black">
                                {{ draftFinished ? "DRAFT COMPLETE" : "DRAFT NIGHT" }}
                            </h2>

                            <p class="mt-2 text-sm text-gray-500">
                                {{
                                    draftFinished
                                        ? "All available picks have been completed."
                                        : "The next selection is ready."
                                }}
                            </p>

                            <button
                                v-if="!draftFinished && draftOrder.length > 0"
                                @click="draftPlayer"
                                class="mt-6 rounded-lg bg-blue-600 px-8 py-3 text-sm font-black uppercase tracking-wider transition hover:bg-blue-500"
                            >
                                Start Draft
                            </button>
                        </div>
                    </div>

                    <!-- DRAFT STAGE -->
                    <div
                        v-else
                        class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b0b0b] shadow-2xl"
                    >
                        <!-- ROUND / PICK HEADER -->
                        <div
                            class="border-b border-white/10 bg-gradient-to-r from-[#101010] to-[#080808] px-5 py-4"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-4"
                            >
                                <div>
                                    <div
                                        class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-400"
                                    >
                                        Round {{ currentPick.round }}
                                    </div>

                                    <div class="mt-1 flex items-end gap-3">
                                        <span
                                            class="text-5xl font-black leading-none"
                                        >
                                            #{{ currentPick.pick }}
                                        </span>

                                        <span
                                            class="pb-1 text-sm font-bold uppercase text-gray-500"
                                        >
                                            Overall Pick
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div
                                        class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500"
                                    >
                                        Selection
                                    </div>

                                    <div
                                        class="mt-1 text-lg font-black text-white"
                                    >
                                        {{
                                            draftProgress.current
                                        }}
                                        /
                                        {{
                                            draftProgress.total
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ON THE CLOCK -->
                        <div
                            v-if="!showingPlayer"
                            class="relative flex min-h-[560px] items-center justify-center overflow-hidden"
                        >
                            <!-- Background numbers -->
                            <div
                                class="pointer-events-none absolute -right-10 -top-20 text-[260px] font-black leading-none text-white/[0.025]"
                            >
                                {{ currentPick.pick }}
                            </div>

                            <div
                                class="relative z-10 w-full max-w-3xl px-6 py-14 text-center"
                            >
                                <div
                                    class="mx-auto mb-6 inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-2 text-xs font-black uppercase tracking-[0.2em] text-red-400"
                                >
                                    <span
                                        class="h-2 w-2 animate-pulse rounded-full bg-red-500"
                                    ></span>

                                    On The Clock
                                </div>

                                <div
                                    class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-2xl border border-blue-500/20 bg-blue-500/10"
                                >
                                    <i
                                        class="fa fa-shield-alt text-4xl text-blue-400"
                                    ></i>
                                </div>

                                <p
                                    class="text-xs font-bold uppercase tracking-[0.25em] text-gray-500"
                                >
                                    The next team selecting is
                                </p>

                                <h2
                                    class="mt-3 text-4xl font-black uppercase tracking-tight md:text-6xl"
                                >
                                    {{ currentPick.team_name }}
                                </h2>

                                <div
                                    class="mx-auto mt-6 h-px w-24 bg-blue-500"
                                ></div>

                                <p class="mt-5 text-sm text-gray-500">
                                    Waiting for the selection...
                                </p>

                                <button
                                    @click.prevent="draftPlayer"
                                    class="mt-8 rounded-xl bg-blue-600 px-10 py-4 text-sm font-black uppercase tracking-widest shadow-lg shadow-blue-900/30 transition hover:scale-[1.02] hover:bg-blue-500"
                                >
                                    <i class="fa fa-check mr-2"></i>
                                    Make Pick
                                </button>
                            </div>
                        </div>

                        <!-- PLAYER REVEAL -->
                        <div
                            v-else
                            class="relative min-h-[560px] overflow-hidden"
                        >
                            <!-- Background -->
                            <div
                                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-blue-950/30 via-transparent to-transparent"
                            ></div>

                            <div
                                class="pointer-events-none absolute -right-16 top-0 text-[280px] font-black leading-none text-white/[0.025]"
                            >
                                {{ currentPick.pick }}
                            </div>

                            <div
                                class="relative z-10 flex min-h-[560px] items-center justify-center px-5 py-10"
                            >
                                <div class="w-full max-w-4xl">
                                    <!-- PICK IS IN -->
                                    <div class="mb-8 text-center">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-full bg-blue-500/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.3em] text-blue-400"
                                        >
                                            <i class="fa fa-check-circle"></i>
                                            Pick Is In
                                        </div>

                                        <div
                                            class="mt-3 text-xs font-bold uppercase tracking-[0.2em] text-gray-500"
                                        >
                                            With the #{{ currentPick.pick }}
                                            pick
                                        </div>
                                    </div>

                                    <!-- PLAYER -->
                                    <div
                                        class="grid items-center gap-8 md:grid-cols-[280px_1fr]"
                                    >
                                        <!-- PLAYER VISUAL -->
                                        <div class="relative">
                                            <div
                                                class="absolute inset-0 scale-90 rounded-full bg-blue-500/10 blur-3xl"
                                            ></div>

                                            <div
                                                class="relative mx-auto flex aspect-square max-w-[280px] items-center justify-center rounded-2xl border border-white/10 bg-gradient-to-br from-[#191919] to-[#080808] shadow-2xl"
                                            >
                                                <div class="text-center">
                                                    <div
                                                        class="mx-auto mb-4 flex h-28 w-28 items-center justify-center rounded-full bg-white/5"
                                                    >
                                                        <i
                                                            class="fa fa-user text-5xl text-gray-600"
                                                        ></i>
                                                    </div>

                                                    <div
                                                        class="text-[10px] font-black uppercase tracking-[0.25em] text-gray-600"
                                                    >
                                                        Rookie
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- PLAYER INFO -->
                                        <div>
                                            <div
                                                class="text-xs font-black uppercase tracking-[0.25em] text-blue-400"
                                            >
                                                {{ currentPick.team_name }}
                                                selects
                                            </div>

                                            <h2
                                                class="mt-2 text-4xl font-black leading-none tracking-tight md:text-6xl"
                                            >
                                                {{ currentPick.player_name }}
                                            </h2>

                                            <div
                                                class="mt-3 text-sm text-gray-500"
                                            >
                                                Age {{ currentPick.age }}
                                            </div>

                                            <!-- Player stats -->
                                            <div
                                                class="mt-8 grid grid-cols-2 gap-2 sm:grid-cols-4"
                                            >
                                                <div
                                                    class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                                                >
                                                    <div
                                                        class="text-[9px] font-black uppercase tracking-widest text-gray-500"
                                                    >
                                                        Position
                                                    </div>

                                                    <div
                                                        class="mt-1 text-xl font-black"
                                                    >
                                                        {{
                                                            currentPick.position ??
                                                            "-"
                                                        }}
                                                    </div>
                                                </div>

                                                <div
                                                    class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                                                >
                                                    <div
                                                        class="text-[9px] font-black uppercase tracking-widest text-gray-500"
                                                    >
                                                        Overall
                                                    </div>

                                                    <div
                                                        class="mt-1 text-xl font-black text-blue-400"
                                                    >
                                                        {{
                                                            currentPick.overall_rating ??
                                                            0
                                                        }}
                                                    </div>
                                                </div>

                                                <div
                                                    class="rounded-xl border border-white/10 bg-white/[0.03] p-4 sm:col-span-2"
                                                >
                                                    <div
                                                        class="text-[9px] font-black uppercase tracking-widest text-gray-500"
                                                    >
                                                        Archetype
                                                    </div>

                                                    <div
                                                        class="mt-1 text-xl font-black capitalize"
                                                    >
                                                        {{
                                                            formatArchetype(
                                                                currentPick.archetype
                                                            )
                                                        }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Team selection -->
                                            <div
                                                class="mt-6 flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.02] p-4"
                                            >
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600"
                                                >
                                                    <i
                                                        class="fa fa-users"
                                                    ></i>
                                                </div>

                                                <div>
                                                    <div
                                                        class="text-[9px] font-black uppercase tracking-widest text-gray-500"
                                                    >
                                                        Drafted By
                                                    </div>

                                                    <div
                                                        class="font-black"
                                                    >
                                                        {{
                                                            currentPick.team_name
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- NEXT PICK -->
                                    <div
                                        class="mt-10 flex items-center justify-between border-t border-white/10 pt-5"
                                    >
                                        <div>
                                            <div
                                                class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                                            >
                                                Next Pick
                                            </div>

                                            <div
                                                v-if="nextPick"
                                                class="mt-1 text-sm font-bold"
                                            >
                                                #{{ nextPick.pick }}
                                                —
                                                {{ nextPick.team_name }}
                                            </div>

                                            <div
                                                v-else
                                                class="mt-1 text-sm font-bold text-gray-500"
                                            >
                                                Draft Complete
                                            </div>
                                        </div>

                                        <button
                                            v-if="nextPick"
                                            @click.prevent="prepareNextPick"
                                            class="rounded-lg border border-white/10 bg-white/5 px-5 py-3 text-xs font-black uppercase tracking-wider transition hover:bg-white/10"
                                        >
                                            Next Pick
                                            <i class="fa fa-arrow-right ml-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- ========================================= -->
                <!-- PICK HISTORY                             -->
                <!-- ========================================= -->

                <aside
                    class="rounded-2xl border border-white/10 bg-[#0b0b0b]"
                >
                    <div
                        class="border-b border-white/10 px-4 py-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-black uppercase">
                                    Draft Board
                                </h3>

                                <p
                                    class="mt-1 text-[9px] uppercase tracking-[0.2em] text-gray-600"
                                >
                                    Latest selections
                                </p>
                            </div>

                            <div
                                class="rounded-md bg-white/5 px-2 py-1 text-[9px] font-black text-gray-500"
                            >
                                {{ draftHistory.length }}
                                PICKS
                            </div>
                        </div>
                    </div>

                    <div class="max-h-[650px] overflow-y-auto">
                        <div
                            v-if="draftHistory.length === 0"
                            class="px-4 py-12 text-center text-xs text-gray-600"
                        >
                            No selections yet.
                        </div>

                        <div
                            v-for="(pick, index) in draftHistory"
                            :key="pick.id ?? index"
                            class="border-b border-white/5 px-4 py-3 transition hover:bg-white/[0.03]"
                        >
                            <div
                                class="flex items-center gap-3"
                            >
                                <!-- Pick number -->
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/5 text-xs font-black"
                                >
                                    {{ pick.pick }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="truncate text-[9px] font-black uppercase tracking-wider text-gray-600"
                                    >
                                        R{{ pick.round }}
                                        ·
                                        {{ pick.team_name }}
                                    </div>

                                    <div
                                        class="mt-1 truncate text-sm font-black"
                                    >
                                        {{ pick.player_name }}
                                    </div>

                                    <div
                                        class="mt-1 flex gap-2 text-[9px] font-bold uppercase text-gray-600"
                                    >
                                        <span>
                                            {{ pick.position ?? "-" }}
                                        </span>

                                        <span>
                                            OVR
                                            {{ pick.overall_rating ?? 0 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- ========================================= -->
            <!-- AVAILABLE ROOKIES                         -->
            <!-- ========================================= -->

            <section class="mt-5">
                <div
                    class="rounded-2xl border border-white/10 bg-[#0b0b0b]"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-4"
                    >
                        <div>
                            <h3 class="text-sm font-black uppercase">
                                Available Prospects
                            </h3>

                            <p
                                class="mt-1 text-[9px] uppercase tracking-[0.2em] text-gray-600"
                            >
                                Rookie pool
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold"
                        >
                            {{ availablePlayers.total ?? 0 }}
                            Available
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr
                                    class="border-b border-white/10 bg-white/[0.02]"
                                >
                                    <th
                                        class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-500"
                                    >
                                        Prospect
                                    </th>

                                    <th
                                        class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-500"
                                    >
                                        Overall
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="player in availablePlayers.rookies"
                                    :key="player.id"
                                    class="border-b border-white/5 transition hover:bg-white/[0.03]"
                                >
                                    <td class="px-4 py-3">
                                        <div
                                            class="font-bold"
                                        >
                                            {{ player.name }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="font-black text-blue-400"
                                        >
                                            {{ player.overall_rating }}
                                        </span>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        !availablePlayers.rookies ||
                                        availablePlayers.rookies.length === 0
                                    "
                                >
                                    <td
                                        colspan="2"
                                        class="px-4 py-10 text-center text-xs text-gray-600"
                                    >
                                        No available rookies.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="availablePlayers.total"
                        class="border-t border-white/10 p-4"
                    >
                        <Paginator
                            :page_number="search.page_num"
                            :total_rows="availablePlayers.total ?? 0"
                            :itemsperpage="search.itemsperpage"
                            @page_num="handlePagination"
                        />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Paginator from "@/Components/Paginator.vue";
import TopStatistics from "@/Pages/Analytics/Module/TopStatistics.vue";

const emits = defineEmits(["newSeason"]);

const availablePlayers = ref({
    rookies: [],
    total: 0,
});

const draftOrder = ref([]);
const draftResults = ref([]);

const selectedPickIndex = ref(0);
const showingPlayer = ref(false);

const isHide = ref(true);
const key = ref(0);

const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});

/*
|--------------------------------------------------------------------------
| COMPUTED
|--------------------------------------------------------------------------
*/

const currentPick = computed(() => {
    if (
        !draftOrder.value.length ||
        selectedPickIndex.value >= draftOrder.value.length
    ) {
        return null;
    }

    return draftOrder.value[selectedPickIndex.value];
});

const nextPick = computed(() => {
    const nextIndex = selectedPickIndex.value + 1;

    if (nextIndex >= draftOrder.value.length) {
        return null;
    }

    return draftOrder.value[nextIndex];
});

const draftHistory = computed(() => {
    return draftResults.value
        .slice()
        .sort((a, b) => {
            if (a.round !== b.round) {
                return a.round - b.round;
            }

            return a.pick - b.pick;
        });
});

const draftFinished = computed(() => {
    return (
        draftOrder.value.length > 0 &&
        selectedPickIndex.value >= draftOrder.value.length
    );
});

const draftProgress = computed(() => {
    return {
        current: Math.min(
            selectedPickIndex.value + 1,
            draftOrder.value.length
        ),
        total: draftOrder.value.length,
    };
});

/*
|--------------------------------------------------------------------------
| LIFECYCLE
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await fetchDraftOrder();
    await fetchDraftResults();
    await fetchAvailablePlayers();

    syncCurrentPick();
});

/*
|--------------------------------------------------------------------------
| DRAFT DATA
|--------------------------------------------------------------------------
*/

const fetchDraftOrder = async () => {
    try {
        const response = await axios.get(route("draft.orders"));

        draftOrder.value = response.data.draft_order ?? [];

        isHide.value = false;

        syncCurrentPick();
    } catch (error) {
        console.error("Error fetching draft order:", error);

        draftOrder.value = [];
    }
};

const fetchDraftResults = async () => {
    try {
        const response = await axios.get(route("draft.results"));

        draftResults.value = response.data.draft_results ?? [];

        syncCurrentPick();
    } catch (error) {
        console.error("Error fetching draft results:", error);

        draftResults.value = [];
    }
};

const fetchAvailablePlayers = async () => {
    try {
        const response = await axios.post(
            route("draft.list"),
            search.value
        );

        availablePlayers.value = response.data ?? {
            rookies: [],
            total: 0,
        };
    } catch (error) {
        console.error(
            "Error fetching available players:",
            error
        );
    }
};

/*
|--------------------------------------------------------------------------
| FIND CURRENT PICK
|--------------------------------------------------------------------------
|
| We determine the current pick from draft results rather than simply
| assuming the first item in draftOrder is always the next selection.
|
*/

const syncCurrentPick = () => {
    if (!draftOrder.value.length) {
        return;
    }

    const completedKeys = new Set(
        draftResults.value.map(
            (pick) => `${pick.round}-${pick.pick}`
        )
    );

    const nextIndex = draftOrder.value.findIndex(
        (pick) =>
            !completedKeys.has(
                `${pick.round}-${pick.pick}`
            )
    );

    if (nextIndex === -1) {
        selectedPickIndex.value = draftOrder.value.length;
        showingPlayer.value = false;
        return;
    }

    selectedPickIndex.value = nextIndex;
    showingPlayer.value = false;
};

/*
|--------------------------------------------------------------------------
| DRAFT PLAYER
|--------------------------------------------------------------------------
*/

const draftPlayer = async () => {
    if (!currentPick.value) {
        return;
    }

    try {
        Swal.fire({
            title: "Processing Pick",
            html: `
                <div style="margin-top:10px">
                    <strong>${currentPick.value.team_name}</strong>
                    is making pick #${currentPick.value.pick}
                </div>
            `,
            icon: "info",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.get(
            route("draft.players")
        );

        Swal.close();

        /*
         * Refresh results so the newly drafted player becomes available
         * to the presentation.
         */
        await fetchDraftResults();
        await fetchDraftOrder();
        await fetchAvailablePlayers();

        /*
         * Find the player that belongs to the current selection.
         */
        const draftedPlayer = draftResults.value.find(
            (pick) =>
                Number(pick.round) ===
                    Number(currentPick.value.round) &&
                Number(pick.pick) ===
                    Number(currentPick.value.pick)
        );

        if (draftedPlayer) {
            /*
             * Show the selection.
             */
            showingPlayer.value = true;
        } else {
            /*
             * If backend response does not yet expose the drafted player,
             * refresh and synchronize.
             */
            syncCurrentPick();
        }

        key.value = Math.random();

        emits("newSeason", Math.random());
    } catch (error) {
        console.error(error);

        Swal.close();

        await Swal.fire({
            title: "Draft Error",
            text:
                error?.response?.data?.message ??
                "Unable to complete the draft pick.",
            icon: "error",
            confirmButtonText: "OK",
        });
    }
};

/*
|--------------------------------------------------------------------------
| NEXT PICK
|--------------------------------------------------------------------------
*/

const prepareNextPick = () => {
    if (!nextPick.value) {
        showingPlayer.value = false;

        selectedPickIndex.value =
            draftOrder.value.length;

        return;
    }

    selectedPickIndex.value++;
    showingPlayer.value = false;
};

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

const handlePagination = (page_num) => {
    search.value.page_num = page_num;

    fetchAvailablePlayers();
};

/*
|--------------------------------------------------------------------------
| FORMAT
|--------------------------------------------------------------------------
*/

const formatArchetype = (archetype) => {
    if (!archetype) {
        return "-";
    }

    return archetype
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) =>
            letter.toUpperCase()
        );
};

/*
|--------------------------------------------------------------------------
| PLAYER GENERATION
|--------------------------------------------------------------------------
*/

const fetchRandomFullName3 = async () => {
    try {
        const response = await axios.get(
            route("generate.new.player")
        );

        return {
            name: response.data.name,
            country: response.data.country,
            address: response.data.address,
        };
    } catch (error) {
        console.error(
            "Error fetching random player name:",
            error
        );

        return null;
    }
};

const addMultiplePlayers = async (count) => {
    try {
        Swal.fire({
            title: "Generating Rookies",
            text: `Adding ${count} players to the draft pool...`,
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const promises = [];

        for (let i = 0; i < count; i++) {
            const randomFullName =
                await fetchRandomFullName3();

            if (randomFullName) {
                promises.push(
                    addPlayer(randomFullName, false)
                );
            }
        }

        await Promise.all(promises);

        Swal.close();

        await fetchAvailablePlayers();

        key.value = Math.random();

        await Swal.fire({
            icon: "success",
            title: "Draft Pool Updated",
            text: `Successfully added ${count} players.`,
        });
    } catch (error) {
        console.error(
            "Error adding multiple players:",
            error
        );

        Swal.close();

        await Swal.fire({
            icon: "error",
            title: "Error",
            text:
                error?.message ??
                "Unable to add rookie players.",
        });
    }
};

const addPlayer = async (info, showAlert = true) => {
    try {
        const response = await axios.post(
            route("players.add.free.agent"),
            {
                name: info.name,
                address: info.address,
                country: info.country,
            }
        );

        if (showAlert) {
            await Swal.fire({
                icon: "success",
                title: `${response.data.player.name} added`,
                text:
                    response.data.message ??
                    "Player added to Draft Pool.",
            });
        }

        key.value = Math.random();

        return response.data;
    } catch (error) {
        console.error(
            "Error adding player:",
            error?.response?.data?.message
        );

        throw new Error(
            error?.response?.data?.message ??
                "Unable to add player."
        );
    }
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Draft Night Scrollbar
|--------------------------------------------------------------------------
*/

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

