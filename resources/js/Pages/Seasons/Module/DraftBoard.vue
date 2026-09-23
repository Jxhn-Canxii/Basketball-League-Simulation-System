<template>
    <div class="min-h-screen w-full min-w-0 bg-black p-2 text-white md:p-3">
        <!-- ========================================================= -->
        <!-- HEADER                                                    -->
        <!-- ========================================================= -->

        <section
            class="relative mb-3 overflow-hidden rounded-2xl border border-white/[0.08] bg-gradient-to-br from-[#151515] via-[#0b0b0b] to-black shadow-2xl"
        >
            <!-- Decorative glow -->
            <div
                class="pointer-events-none absolute -right-24 -top-32 h-72 w-72 rounded-full bg-yellow-500/[0.04] blur-3xl"
            ></div>

            <div
                class="pointer-events-none absolute -bottom-32 left-1/3 h-64 w-64 rounded-full bg-white/[0.02] blur-3xl"
            ></div>

            <!-- Season watermark -->
            <div
                class="pointer-events-none absolute -right-4 -top-8 select-none text-[140px] font-black leading-none tracking-tighter text-white/[0.025] md:text-[190px]"
            >
                {{ props.season_id }}
            </div>

            <div
                class="relative z-10 flex flex-col gap-5 px-4 py-5 sm:px-5 md:px-7 md:py-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Title -->
                <div class="min-w-0">
                    <div
                        class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.3em] text-yellow-500 sm:text-[10px]"
                    >
                        <span
                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.7)]"
                        ></span>

                        Draft History
                    </div>

                    <h1
                        class="truncate text-2xl font-black tracking-tight text-white sm:text-3xl md:text-4xl lg:text-5xl"
                    >
                        Season {{ props.season_id }}
                    </h1>

                    <p
                        class="mt-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-gray-600 sm:text-[10px]"
                    >
                        Rookie Draft Results
                    </p>
                </div>

                <!-- Summary -->
                <div
                    class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap lg:justify-end"
                >
                    <!-- Total -->
                    <div
                        class="min-w-0 rounded-xl border border-white/[0.08] bg-white/[0.025] px-3 py-2.5 transition hover:border-white/[0.14] hover:bg-white/[0.04] sm:min-w-[105px] sm:px-4 sm:py-3"
                    >
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.18em] text-gray-600"
                        >
                            Total Picks
                        </div>

                        <div
                            class="mt-1 text-xl font-black text-white sm:text-2xl"
                        >
                            {{ draftResults.length }}
                        </div>
                    </div>

                    <!-- Stayed -->
                    <div
                        class="min-w-0 rounded-xl border border-green-500/[0.12] bg-green-500/[0.035] px-3 py-2.5 transition hover:border-green-500/[0.2] hover:bg-green-500/[0.05] sm:min-w-[105px] sm:px-4 sm:py-3"
                    >
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.18em] text-gray-600"
                        >
                            Stayed
                        </div>

                        <div
                            class="mt-1 text-xl font-black text-green-400 sm:text-2xl"
                        >
                            {{ stayedWithDraftedTeam }}
                        </div>
                    </div>

                    <!-- Changed -->
                    <div
                        class="min-w-0 rounded-xl border border-red-500/[0.12] bg-red-500/[0.035] px-3 py-2.5 transition hover:border-red-500/[0.2] hover:bg-red-500/[0.05] sm:min-w-[105px] sm:px-4 sm:py-3"
                    >
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.18em] text-gray-600"
                        >
                            Changed
                        </div>

                        <div
                            class="mt-1 text-xl font-black text-red-400 sm:text-2xl"
                        >
                            {{ changedTeam }}
                        </div>
                    </div>

                    <!-- Unsigned -->
                    <div
                        class="min-w-0 rounded-xl border border-white/[0.08] bg-white/[0.025] px-3 py-2.5 transition hover:border-white/[0.14] hover:bg-white/[0.04] sm:min-w-[105px] sm:px-4 sm:py-3"
                    >
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.18em] text-gray-600"
                        >
                            Unsigned
                        </div>

                        <div
                            class="mt-1 text-xl font-black text-gray-400 sm:text-2xl"
                        >
                            {{ unsignedPlayers }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- ROUND TABS                                                -->
        <!-- ========================================================= -->

        <section
            v-if="draftResults.length > 0 && !isLoading"
            class="mb-3 overflow-hidden rounded-xl border border-white/[0.08] bg-[#0a0a0a]"
        >
            <div class="grid grid-cols-2">
                <!-- Round 1 -->
                <button
                    type="button"
                    class="relative flex min-w-0 items-center justify-center gap-2 px-3 py-3.5 text-[10px] font-black uppercase tracking-[0.15em] transition sm:px-5 sm:py-4 sm:text-xs sm:tracking-widest"
                    :class="
                        selectedRound === 1
                            ? 'bg-yellow-500 text-black'
                            : 'text-gray-500 hover:bg-white/[0.04] hover:text-white'
                    "
                    @click="selectedRound = 1"
                >
                    <span>Round 1</span>

                    <span
                        class="rounded-md px-1.5 py-0.5 text-[8px]"
                        :class="
                            selectedRound === 1
                                ? 'bg-black/15 text-black'
                                : 'bg-white/5 text-gray-500'
                        "
                    >
                        {{ round1Results.length }}
                    </span>
                </button>

                <!-- Round 2 -->
                <button
                    type="button"
                    class="relative flex min-w-0 items-center justify-center gap-2 border-l border-white/[0.08] px-3 py-3.5 text-[10px] font-black uppercase tracking-[0.15em] transition sm:px-5 sm:py-4 sm:text-xs sm:tracking-widest"
                    :class="
                        selectedRound === 2
                            ? 'bg-yellow-500 text-black'
                            : 'text-gray-500 hover:bg-white/[0.04] hover:text-white'
                    "
                    @click="selectedRound = 2"
                >
                    <span>Round 2</span>

                    <span
                        class="rounded-md px-1.5 py-0.5 text-[8px]"
                        :class="
                            selectedRound === 2
                                ? 'bg-black/15 text-black'
                                : 'bg-white/5 text-gray-500'
                        "
                    >
                        {{ round2Results.length }}
                    </span>
                </button>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- EMPTY STATE                                               -->
        <!-- ========================================================= -->

        <section
            v-if="draftResults.length === 0 && !isLoading"
            class="flex min-h-[400px] items-center justify-center rounded-2xl border border-white/[0.08] bg-[#0a0a0a] px-5"
        >
            <div class="text-center">
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/[0.06] bg-white/[0.025]"
                >
                    <i
                        class="fa fa-basketball-ball text-xl text-gray-700"
                    ></i>
                </div>

                <h2
                    class="text-lg font-black tracking-tight text-white sm:text-xl"
                >
                    NO DRAFT RECORDS
                </h2>

                <p
                    class="mx-auto mt-2 max-w-md text-[10px] font-medium leading-relaxed text-gray-600 sm:text-xs"
                >
                    No draft history is available for Season
                    {{ props.season_id }}.
                </p>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- DRAFT RESULTS                                             -->
        <!-- ========================================================= -->

        <section
            v-if="draftResults.length > 0 && !isLoading"
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0a0a0a] shadow-xl"
        >
            <!-- Table Header -->
            <div
                class="flex flex-col gap-3 border-b border-white/[0.08] bg-gradient-to-r from-white/[0.025] to-transparent px-4 py-4 sm:flex-row sm:items-center sm:justify-between md:px-5"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-yellow-500"
                        ></span>

                        <h2
                            class="text-xs font-black uppercase tracking-wide text-white sm:text-sm"
                        >
                            Round {{ selectedRound }} Results
                        </h2>
                    </div>

                    <p
                        class="mt-1 text-[8px] font-bold uppercase tracking-[0.2em] text-gray-600 sm:text-[9px]"
                    >
                        Complete draft recap
                    </p>
                </div>

                <div
                    class="w-fit rounded-lg border border-white/[0.08] bg-white/[0.025] px-3 py-2 text-[9px] font-black uppercase tracking-wider text-gray-500"
                >
                    <span class="text-white">
                        {{ selectedRoundResults.length }}
                    </span>
                    Selections
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- TABLE                                                  -->
            <!-- ===================================================== -->

            <div class="min-w-0 overflow-x-auto">
                <table class="w-full min-w-[1250px] border-collapse text-xs">
                    <thead>
                        <tr
                            class="border-b border-white/[0.08] bg-white/[0.02]"
                        >
                            <th
                                class="w-[70px] px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Pick
                            </th>

                            <th
                                class="w-[80px] px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Draft #
                            </th>

                            <th
                                class="w-[70px] px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Rank
                            </th>

                            <th
                                class="min-w-[220px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Player
                            </th>

                            <th
                                class="w-[90px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Position
                            </th>

                            <th
                                class="w-[90px] px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Overall
                            </th>

                            <th
                                class="w-[180px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Archetype
                            </th>

                            <th
                                class="w-[200px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Drafted By
                            </th>

                            <th
                                class="w-[200px] px-3 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Signed By
                            </th>

                            <th
                                class="w-[110px] px-3 py-3 text-center text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="player in selectedRoundResults"
                            :key="`${player.round}-${player.pick_number}-${player.player_id}`"
                            class="group cursor-pointer border-b border-white/[0.045] transition duration-150 hover:bg-white/[0.035]"
                            @click.prevent="
                                showPlayerProfileModal =
                                    player.player_id
                            "
                        >
                            <!-- PICK -->
                            <td class="px-3 py-3.5 text-center">
                                <div
                                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.035] text-sm font-black transition duration-150 group-hover:border-yellow-500/30 group-hover:bg-yellow-500 group-hover:text-black"
                                >
                                    {{ player.pick_number ?? "-" }}
                                </div>
                            </td>

                            <!-- DRAFT # -->
                            <td class="px-3 py-3.5 text-center">
                                <span
                                    class="inline-flex min-w-[42px] justify-center rounded-md border border-transparent px-2 py-1 font-black"
                                    :class="draftValueClass(player)"
                                >
                                    {{ getOverallDraftNumber(player) }}
                                </span>
                            </td>

                            <!-- RANK -->
                            <td class="px-3 py-3.5 text-center">
                                <span class="font-black text-gray-300">
                                    {{ player.rank ?? "-" }}
                                </span>
                            </td>

                            <!-- PLAYER -->
                            <td class="px-3 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/[0.06] bg-gradient-to-br from-white/[0.06] to-white/[0.02]"
                                    >
                                        <i
                                            class="fa fa-user text-sm text-gray-600"
                                        ></i>
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate font-black text-white transition group-hover:text-yellow-400"
                                        >
                                            {{ player.player_name ?? "Unknown Player" }}

                                            <sup
                                                v-if="player.age != null"
                                                class="ml-1 text-[9px] font-bold text-gray-600"
                                            >
                                                {{ player.age }}
                                            </sup>
                                        </div>

                                        <div
                                            class="mt-1 text-[8px] font-bold uppercase tracking-wider text-gray-600"
                                        >
                                            Player #{{ player.player_id ?? "-" }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- POSITION -->
                            <td class="px-3 py-3.5">
                                <span
                                    class="inline-flex rounded-md border border-white/[0.06] bg-white/[0.035] px-2 py-1 text-[9px] font-black uppercase text-gray-400"
                                >
                                    {{ player.position ?? "-" }}
                                </span>
                            </td>

                            <!-- OVERALL -->
                            <td class="px-3 py-3.5 text-center">
                                <div
                                    class="text-lg font-black"
                                    :class="
                                        ratingClass(
                                            player.overall_rating
                                        )
                                    "
                                >
                                    {{ player.overall_rating ?? 0 }}
                                </div>

                                <div
                                    class="text-[7px] font-bold uppercase tracking-wider text-gray-700"
                                >
                                    Overall
                                </div>
                            </td>

                            <!-- ARCHETYPE -->
                            <td class="px-3 py-3.5">
                                <span
                                    class="font-bold capitalize text-gray-300"
                                >
                                    {{
                                        formatArchetype(
                                            player.archetype
                                        )
                                    }}
                                </span>
                            </td>

                            <!-- DRAFTED TEAM -->
                            <td class="px-3 py-3.5">
                                <div
                                    class="font-black text-gray-200"
                                >
                                    {{
                                        player.team_name ??
                                        "Undrafted"
                                    }}
                                </div>

                                <div
                                    class="mt-1 text-[8px] font-bold uppercase tracking-wider"
                                    :class="
                                        Number(player.team_id) ===
                                        Number(player.original_team_id)
                                            ? 'text-gray-600'
                                            : 'text-red-500'
                                    "
                                >
                                    {{
                                        Number(player.team_id) ===
                                        Number(player.original_team_id)
                                            ? "Original Selection"
                                            : "Picked from " +
                                              (player.original_team_name ??
                                                  "Unknown Team")
                                    }}
                                </div>
                            </td>

                            <!-- SIGNED TEAM -->
                            <td class="px-3 py-3.5">
                                <div
                                    class="font-black"
                                    :class="
                                        signingTextClass(
                                            player.drafted_team_id,
                                            player.signed_team_id
                                        )
                                    "
                                >
                                    {{
                                        player.signed_team_name ??
                                        "Unsigned"
                                    }}
                                </div>

                                <div
                                    class="mt-1 text-[8px] font-bold uppercase tracking-wider text-gray-600"
                                >
                                    Final Destination
                                </div>
                            </td>

                            <!-- STATUS -->
                            <td class="px-3 py-3.5 text-center">
                                <span
                                    class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-[8px] font-black uppercase tracking-wider"
                                    :class="
                                        signingStatusBadge(
                                            player.drafted_team_id,
                                            player.signed_team_id
                                        )
                                    "
                                >
                                    {{
                                        signingStatusText(
                                            player.drafted_team_id,
                                            player.signed_team_id
                                        )
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ===================================================== -->
            <!-- FOOTER                                                 -->
            <!-- ===================================================== -->

            <div
                class="border-t border-white/[0.08] bg-white/[0.012] px-4 py-3 md:px-5"
            >
                <div
                    class="flex flex-col gap-3 text-[8px] font-bold uppercase tracking-wider text-gray-600 sm:flex-row sm:items-center sm:justify-between sm:text-[9px]"
                >
                    <div class="flex items-center gap-2">
                        <i class="fa fa-mouse-pointer text-gray-700"></i>

                        Click any player to view their profile
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-x-4 gap-y-2"
                    >
                        <span class="flex items-center gap-1.5">
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-green-500"
                            ></span>

                            Stayed
                        </span>

                        <span class="flex items-center gap-1.5">
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-red-500"
                            ></span>

                            Changed
                        </span>

                        <span class="flex items-center gap-1.5">
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-gray-500"
                            ></span>

                            Unsigned
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- LOADING                                                   -->
        <!-- ========================================================= -->

        <section
            v-if="isLoading"
            class="flex min-h-[400px] items-center justify-center rounded-2xl border border-white/[0.08] bg-[#0a0a0a]"
        >
            <div class="text-center">
                <div
                    class="relative mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-yellow-500/10 bg-yellow-500/[0.03]"
                >
                    <div
                        class="absolute inset-0 animate-ping rounded-2xl border border-yellow-500/10"
                    ></div>

                    <i
                        class="fa fa-spinner fa-spin text-xl text-yellow-500/70"
                    ></i>
                </div>

                <h2 class="text-lg font-black sm:text-xl">
                    PREPARING DRAFT RECORDS
                </h2>

                <p
                    class="mt-2 text-[10px] font-medium text-gray-600 sm:text-xs"
                >
                    Loading for Season
                    {{ props.season_id }}.
                </p>
            </div>
        </section>
    </div>

    <!-- ============================================================= -->
    <!-- PLAYER PROFILE                                                -->
    <!-- ============================================================= -->

    <Modal
        :show="!!showPlayerProfileModal"
        :maxWidth="'6xl'"
        title="Player Profile"
        @close="showPlayerProfileModal = false"
    >
        <div class="bg-black p-3 sm:p-4 md:p-6">
            <PlayerPerformance
                :key="showPlayerProfileModal"
                :player_id="showPlayerProfileModal"
            />
        </div>
    </Modal>
</template>

<script setup>
import {
    ref,
    onMounted,
    computed,
} from "vue";

import axios from "axios";

import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

const draftResults = ref([]);
const selectedRound = ref(1);
const isLoading = ref(true);
const showPlayerProfileModal = ref(false);

/*
|--------------------------------------------------------------------------
| COMPUTED RESULTS
|--------------------------------------------------------------------------
*/

const round1Results = computed(() => {
    return draftResults.value.filter(
        (player) => Number(player.round) === 1
    );
});

const round2Results = computed(() => {
    return draftResults.value.filter(
        (player) => Number(player.round) === 2
    );
});

const selectedRoundResults = computed(() => {
    return selectedRound.value === 1
        ? round1Results.value
        : round2Results.value;
});

/*
|--------------------------------------------------------------------------
| SIGNING SUMMARY
|--------------------------------------------------------------------------
*/

const stayedWithDraftedTeam = computed(() => {
    return draftResults.value.filter(
        (player) =>
            player.signed_team_id != null &&
            Number(player.drafted_team_id) ===
                Number(player.signed_team_id)
    ).length;
});

const changedTeam = computed(() => {
    return draftResults.value.filter(
        (player) =>
            player.signed_team_id != null &&
            Number(player.drafted_team_id) !==
                Number(player.signed_team_id)
    ).length;
});

const unsignedPlayers = computed(() => {
    return draftResults.value.filter(
        (player) => player.signed_team_id == null
    ).length;
});

/*
|--------------------------------------------------------------------------
| LOAD DATA
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await fetchDraftResults();
});

const fetchDraftResults = async () => {
    isLoading.value = true;
    draftResults.value = [];

    try {
        const response = await axios.post(
            route("draft.season.results"),
            {
                season_id: props.season_id,
            }
        );

        draftResults.value =
            response.data?.draft_results ?? [];

        /*
         * If Round 1 has no results but Round 2 does,
         * automatically select Round 2.
         */
        if (
            round1Results.value.length === 0 &&
            round2Results.value.length > 0
        ) {
            selectedRound.value = 2;
        } else if (
            round1Results.value.length > 0
        ) {
            selectedRound.value = 1;
        }
    } catch (error) {
        console.error(
            "Error fetching draft history:",
            error
        );

        draftResults.value = [];
    } finally {
        isLoading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| DRAFT NUMBER
|--------------------------------------------------------------------------
|
| Round 1:
|   Pick 1 -> Draft #1
|
| Round 2:
|   Pick 1 -> Draft #(round1 picks + 1)
|
*/

const getOverallDraftNumber = (player) => {
    const round = Number(player?.round);
    const pick = Number(player?.pick_number);

    if (!pick) {
        return "-";
    }

    if (round === 1) {
        return pick;
    }

    return round1Results.value.length + pick;
};

/*
|--------------------------------------------------------------------------
| DRAFT VALUE
|--------------------------------------------------------------------------
|
| Better than expected:
|   Draft # > Rank
|
| Worse than expected:
|   Draft # < Rank
|
*/

const draftValueClass = (player) => {
    const pick = Number(
        getOverallDraftNumber(player)
    );

    const rank = Number(player?.rank);

    if (!rank || !pick) {
        return "bg-white/5 text-gray-400";
    }

    if (pick > rank) {
        return "bg-green-500/10 text-green-400 border-green-500/10";
    }

    if (pick < rank) {
        return "bg-red-500/10 text-red-400 border-red-500/10";
    }

    return "bg-white/5 text-gray-400";
};

/*
|--------------------------------------------------------------------------
| RATING
|--------------------------------------------------------------------------
*/

const ratingClass = (rating) => {
    const value = Number(rating);

    if (value >= 85) {
        return "text-yellow-400";
    }

    if (value >= 75) {
        return "text-green-400";
    }

    if (value >= 65) {
        return "text-blue-400";
    }

    return "text-gray-400";
};

/*
|--------------------------------------------------------------------------
| ARCHETYPE
|--------------------------------------------------------------------------
*/

const formatArchetype = (archetype) => {
    if (!archetype) {
        return "-";
    }

    return String(archetype)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (letter) =>
            letter.toUpperCase()
        );
};

/*
|--------------------------------------------------------------------------
| SIGNING STATUS
|--------------------------------------------------------------------------
*/

const signingStatusText = (
    draftedTeam,
    signedTeam
) => {
    if (signedTeam == null) {
        return "Unsigned";
    }

    if (
        Number(draftedTeam) !==
        Number(signedTeam)
    ) {
        return "Changed Team";
    }

    return "Signed";
};

const signingStatusBadge = (
    draftedTeam,
    signedTeam
) => {
    if (signedTeam == null) {
        return "border-gray-500/10 bg-gray-500/10 text-gray-400";
    }

    if (
        Number(draftedTeam) !==
        Number(signedTeam)
    ) {
        return "border-red-500/10 bg-red-500/10 text-red-400";
    }

    return "border-green-500/10 bg-green-500/10 text-green-400";
};

const signingTextClass = (
    draftedTeam,
    signedTeam
) => {
    if (signedTeam == null) {
        return "text-gray-500";
    }

    if (
        Number(draftedTeam) !==
        Number(signedTeam)
    ) {
        return "text-red-400";
    }

    return "text-green-400";
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Scrollbar
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
    background: #2f2f2f;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #4a4a4a;
}

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

table {
    border-collapse: collapse;
}

/*
|--------------------------------------------------------------------------
| Mobile horizontal scrolling
|--------------------------------------------------------------------------
*/

.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: #2f2f2f #050505;
}

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>