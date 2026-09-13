<template>
    <div class="min-h-screen bg-black p-2 text-white">
        <!-- ========================================================= -->
        <!-- HEADER                                                    -->
        <!-- ========================================================= -->

        <div
            class="mb-4 overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-[#111] via-[#090909] to-black"
        >
            <div class="relative px-5 py-6 md:px-8">
                <!-- Background decoration -->
                <div
                    class="pointer-events-none absolute -right-10 -top-20 text-[180px] font-black leading-none text-white/[0.025]"
                >
                    {{ props.season_id }}
                </div>

                <div
                    class="relative z-10 flex flex-wrap items-center justify-between gap-5"
                >
                    <div>
                        <div
                            class="mb-2 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-yellow-500"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-yellow-500"
                            ></span>

                            Draft History
                        </div>

                        <h1
                            class="text-3xl font-black tracking-tight md:text-5xl"
                        >
                            Season {{ props.season_id }}
                        </h1>

                        <p
                            class="mt-2 text-xs font-medium uppercase tracking-[0.18em] text-gray-600"
                        >
                            Rookie Draft Results
                        </p>
                    </div>

                    <!-- Summary -->
                    <div class="flex flex-wrap gap-2">
                        <div
                            class="min-w-[110px] rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3"
                        >
                            <div
                                class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                            >
                                Total Picks
                            </div>

                            <div class="mt-1 text-2xl font-black">
                                {{ draftResults.length }}
                            </div>
                        </div>

                        <div
                            class="min-w-[110px] rounded-xl border border-green-500/10 bg-green-500/[0.04] px-4 py-3"
                        >
                            <div
                                class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                            >
                                Stayed
                            </div>

                            <div
                                class="mt-1 text-2xl font-black text-green-400"
                            >
                                {{ stayedWithDraftedTeam }}
                            </div>
                        </div>

                        <div
                            class="min-w-[110px] rounded-xl border border-red-500/10 bg-red-500/[0.04] px-4 py-3"
                        >
                            <div
                                class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                            >
                                Changed
                            </div>

                            <div
                                class="mt-1 text-2xl font-black text-red-400"
                            >
                                {{ changedTeam }}
                            </div>
                        </div>

                        <div
                            class="min-w-[110px] rounded-xl border border-gray-500/10 bg-gray-500/[0.04] px-4 py-3"
                        >
                            <div
                                class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                            >
                                Unsigned
                            </div>

                            <div
                                class="mt-1 text-2xl font-black text-gray-400"
                            >
                                {{ unsignedPlayers }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- ROUND TABS                                                -->
        <!-- ========================================================= -->

        <div
            v-if="draftResults.length > 0 && !isLoading"
            class="mb-4 flex overflow-hidden rounded-xl border border-white/10 bg-[#0b0b0b]"
        >
            <button
                type="button"
                class="flex-1 px-5 py-4 text-xs font-black uppercase tracking-widest transition"
                :class="
                    selectedRound === 1
                        ? 'bg-yellow-500 text-black'
                        : 'text-gray-500 hover:bg-white/5 hover:text-white'
                "
                @click="selectedRound = 1"
            >
                <span>Round 1</span>

                <span
                    class="ml-2 rounded-md px-2 py-1 text-[9px]"
                    :class="
                        selectedRound === 1
                            ? 'bg-black/20'
                            : 'bg-white/5'
                    "
                >
                    {{ round1Results.length }}
                </span>
            </button>

            <button
                type="button"
                class="flex-1 px-5 py-4 text-xs font-black uppercase tracking-widest transition"
                :class="
                    selectedRound === 2
                        ? 'bg-yellow-500 text-black'
                        : 'text-gray-500 hover:bg-white/5 hover:text-white'
                "
                @click="selectedRound = 2"
            >
                <span>Round 2</span>

                <span
                    class="ml-2 rounded-md px-2 py-1 text-[9px]"
                    :class="
                        selectedRound === 2
                            ? 'bg-black/20'
                            : 'bg-white/5'
                    "
                >
                    {{ round2Results.length }}
                </span>
            </button>
        </div>

        <!-- ========================================================= -->
        <!-- EMPTY STATE                                               -->
        <!-- ========================================================= -->

        <div
            v-if="draftResults.length === 0 && !isLoading"
            class="flex min-h-[400px] items-center justify-center rounded-2xl border border-white/10 bg-[#0b0b0b]"
        >
            <div class="text-center">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/5"
                >
                    <i
                        class="fa fa-basketball-ball text-2xl text-gray-600"
                    ></i>
                </div>

                <h2 class="text-xl font-black">
                    NO DRAFT RECORDS
                </h2>

                <p class="mt-2 text-xs text-gray-600">
                    No draft history is available for Season
                    {{ props.season_id }}.
                </p>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- DRAFT TABLE                                               -->
        <!-- ========================================================= -->

        <div
            v-if="draftResults.length > 0 && !isLoading"
            class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b0b0b]"
        >
            <!-- Table Header -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-4 md:px-5"
            >
                <div>
                    <h2 class="text-sm font-black uppercase">
                        Round {{ selectedRound }} Results
                    </h2>

                    <p
                        class="mt-1 text-[9px] font-bold uppercase tracking-[0.2em] text-gray-600"
                    >
                        Complete draft recap
                    </p>
                </div>

                <div
                    class="rounded-lg border border-white/10 bg-white/[0.03] px-3 py-2 text-[10px] font-black uppercase tracking-wider text-gray-500"
                >
                    {{ selectedRoundResults.length }}
                    Selections
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- TABLE                                                  -->
            <!-- ===================================================== -->

            <div class="overflow-x-auto">
                <table class="min-w-[1200px] w-full text-xs">
                    <thead>
                        <tr
                            class="border-b border-white/10 bg-white/[0.025]"
                        >
                            <th
                                class="w-[70px] px-3 py-3 text-center text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Pick
                            </th>

                            <th
                                class="w-[80px] px-3 py-3 text-center text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Draft #
                            </th>

                            <th
                                class="w-[70px] px-3 py-3 text-center text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Rank
                            </th>

                            <th
                                class="px-3 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Player
                            </th>

                            <th
                                class="w-[90px] px-3 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Position
                            </th>

                            <th
                                class="w-[90px] px-3 py-3 text-center text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Overall
                            </th>

                            <th
                                class="w-[180px] px-3 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Archetype
                            </th>

                            <th
                                class="w-[190px] px-3 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Drafted By
                            </th>

                            <th
                                class="w-[190px] px-3 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Signed By
                            </th>

                            <th
                                class="w-[100px] px-3 py-3 text-center text-[9px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="player in selectedRoundResults"
                            :key="`${player.round}-${player.pick_number}-${player.player_id}`"
                            class="group cursor-pointer border-b border-white/5 transition hover:bg-white/[0.035]"
                            @click.prevent="
                                showPlayerProfileModal =
                                    player.player_id
                            "
                        >
                            <!-- PICK -->
                            <td class="px-3 py-4 text-center">
                                <div
                                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-sm font-black transition group-hover:bg-yellow-500 group-hover:text-black"
                                >
                                    {{ player.pick_number }}
                                </div>
                            </td>

                            <!-- DRAFT # -->
                            <td class="px-3 py-4 text-center">
                                <span
                                    class="inline-flex min-w-[42px] justify-center rounded-md px-2 py-1 font-black"
                                    :class="
                                        draftValueClass(player)
                                    "
                                >
                                    {{ getOverallDraftNumber(player) }}
                                </span>
                            </td>

                            <!-- RANK -->
                            <td class="px-3 py-4 text-center">
                                <span
                                    class="font-black text-gray-300"
                                >
                                    {{ player.rank ?? "-" }}
                                </span>
                            </td>

                            <!-- PLAYER -->
                            <td class="px-3 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/5"
                                    >
                                        <i
                                            class="fa fa-user text-sm text-gray-600"
                                        ></i>
                                    </div>

                                    <div>
                                        <div
                                            class="font-black text-white group-hover:text-yellow-400"
                                        >
                                            {{ player.player_name }}
                                            <sup
                                                class="ml-1 text-[9px] font-bold text-gray-600"
                                            >
                                                {{ player.age }}
                                            </sup>
                                        </div>

                                        <div
                                            class="mt-1 text-[9px] font-bold uppercase tracking-wider text-gray-600"
                                        >
                                            Player #{{ player.player_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- POSITION -->
                            <td class="px-3 py-4">
                                <span
                                    class="rounded-md bg-white/5 px-2 py-1 text-[10px] font-black uppercase"
                                >
                                    {{ player.position ?? "-" }}
                                </span>
                            </td>

                            <!-- OVERALL -->
                            <td class="px-3 py-4 text-center">
                                <div
                                    class="text-lg font-black"
                                    :class="
                                        ratingClass(
                                            player.overall_rating
                                        )
                                    "
                                >
                                    {{
                                        player.overall_rating ?? 0
                                    }}
                                </div>

                                <div
                                    class="text-[8px] font-bold uppercase tracking-wider text-gray-700"
                                >
                                    Overall
                                </div>
                            </td>

                            <!-- ARCHETYPE -->
                            <td class="px-3 py-4">
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
                            <td class="px-3 py-4">
                                <div
                                    class="font-black text-gray-200"
                                >
                                    {{
                                        player.team_name ??
                                        "Undrafted"
                                    }} 
                                </div>

                                <div
                                    :class="player.team_acronym ==  player.original_team_acronym ? 'text-gray-600' : 'text-red-500'"
                                    class="mt-1 text-[8px] font-bold uppercase tracking-wider"
                                >
                                    {{ player.team_acronym ==  player.original_team_acronym ? 
                                        'Original Selection' : 
                                        'Picked from '+player.original_team_name 
                                    }}
                                </div>
                            </td>

                            <!-- SIGNED TEAM -->
                            <td class="px-3 py-4">
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
                            <td class="px-3 py-4 text-center">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-[8px] font-black uppercase tracking-wider"
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
                class="border-t border-white/10 bg-white/[0.015] px-4 py-3"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 text-[9px] font-bold uppercase tracking-wider text-gray-600"
                >
                    <div>
                        Click any player to view their profile
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1">
                            <span
                                class="h-2 w-2 rounded-full bg-green-500"
                            ></span>
                            Stayed
                        </span>

                        <span class="flex items-center gap-1">
                            <span
                                class="h-2 w-2 rounded-full bg-red-500"
                            ></span>
                            Changed
                        </span>

                        <span class="flex items-center gap-1">
                            <span
                                class="h-2 w-2 rounded-full bg-gray-500"
                            ></span>
                            Unsigned
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-if="isLoading"
            class="flex min-h-[400px] items-center justify-center rounded-2xl border border-white/10 bg-[#0b0b0b]"
        >
            <div class="text-center">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/5"
                >
                    <i
                        class="fa fa-loader fa-spin text-2xl text-gray-600"
                    ></i>
                </div>

                <h2 class="text-xl font-black">
                    PREPARING DRAFT RECORDS
                </h2>

                <p class="mt-2 text-xs text-gray-600">
                    Loading for Season
                    {{ props.season_id }}.
                </p>
            </div>
        </div>
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
        <div class="bg-black p-6">
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
    try {
        draftResults.value = [];

        const response = await axios.post(
            route("draft.season.results"),
            {
                season_id: props.season_id,
            }
        );
        isLoading.value = true;
        draftResults.value =
            response.data?.draft_results ?? [];

        /*
         * If this season has no Round 1 but has Round 2,
         * automatically show the available round.
         */
        if (
            round1Results.value.length === 0 &&
            round2Results.value.length > 0
        ) {
            selectedRound.value = 2;
        }
    } catch (error) {
        console.error(
            "Error fetching draft history:",
            error
        );
        isLoading.value = false;
        draftResults.value = [];
    }finally{
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
    const round = Number(player.round);
    const pick = Number(player.pick_number);

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
|   pick >= rank
|
| Worse than expected:
|   pick < rank
|
*/

const draftValueClass = (player) => {
    const pick = getOverallDraftNumber(player);
    const rank = Number(player.rank);

    if (!rank) {
        return "bg-white/5 text-gray-400";
    }

    if (pick > rank) {
        return "bg-green-500/15 text-green-400";
    }

    if (pick < rank) {
        return "bg-red-500/15 text-red-400";
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

    return archetype
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
        return "bg-gray-500/10 text-gray-400";
    }

    if (
        Number(draftedTeam) !==
        Number(signedTeam)
    ) {
        return "bg-red-500/10 text-red-400";
    }

    return "bg-green-500/10 text-green-400";
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
    background: #333;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

table {
    border-collapse: collapse;
}
</style>

