<template>
    <div class="min-h-screen bg-black text-white p-4 md:p-6">

        <!-- =========================================================
             HEADER
        ========================================================== -->
        <div class="max-w-7xl mx-auto">

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">

                <div>
                    <div class="text-orange-500 font-black tracking-[0.4em] uppercase text-sm">
                        LIGA 2
                    </div>

                    <h1 class="text-3xl md:text-5xl font-black italic uppercase tracking-tight">
                        Exhibition Battle
                    </h1>

                    <p class="text-gray-500 text-sm mt-1">
                        Choose your fighters and start the match.
                    </p>
                </div>

                <div class="text-right">
                   <button
                        @click.prevent="simulateAll()"
                        v-if="!isHide"
                        :disabled="isHide"
                        :class="isHide ? 'opacity-50' : ''"
                        class="text-indigo-600 bg-orange-400 shadow rounded-full p-2 font-bold text-md text-nowrap hover:text-indigo-900"
                    >
                        <span class="text-end">Simulate All Season</span>
                    </button>
                </div>

            </div>


            <!-- =====================================================
                 SEASON / CONFERENCE SELECTOR
            ====================================================== -->
            <div class="bg-gray-950 border border-gray-800 rounded-xl p-4 mb-6 shadow-2xl">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- League -->
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">
                            League
                        </label>

                        <select
                            v-model="selectedLeague"
                            disabled
                            class="w-full bg-black border border-gray-700 text-gray-300 rounded-lg px-4 py-3 focus:outline-none"
                        >
                            <option :value="1">
                                Liga Pilipinas
                            </option>
                        </select>
                    </div>

                    <!-- Season -->
                    <div>
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">
                            Season
                        </label>

                        <select
                            v-model="selectedConference"
                            @change="fetchTeams"
                            class="w-full bg-black border border-gray-700 text-white rounded-lg px-4 py-3 focus:outline-none focus:border-orange-500"
                        >
                            <option value="">
                                Select Conference
                            </option>

                            <option
                                v-for="conference in conferences"
                                :key="conference.id"
                                :value="conference.id"
                            >
                                {{ conference.name }}
                            </option>
                        </select>
                    </div>

                </div>
            </div>


            <!-- =====================================================
                 TEKKEN STYLE CHARACTER SELECT
            ====================================================== -->
            <div
                v-if="!showResult"
                class="relative overflow-hidden rounded-2xl border border-gray-800 bg-gradient-to-b from-gray-950 to-black shadow-[0_0_80px_rgba(255,255,255,0.04)]"
            >

                <!-- Decorative top -->
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-orange-500 to-blue-600"></div>


                <div class="px-4 md:px-10 pt-8 pb-10">

                    <div class="text-center mb-8">

                        <div class="text-gray-600 text-xs uppercase tracking-[0.5em] font-bold">
                            Exhibition Match
                        </div>

                        <div class="text-2xl md:text-4xl font-black italic uppercase mt-2">
                            Choose Your Fighters
                        </div>

                    </div>


                    <!-- =================================================
                        FIGHTER SELECT
                    ================================================== -->
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-6 md:gap-2 items-stretch">


                        <!-- ================= HOME ================= -->
                        <div
                            class="relative group min-h-[360px] md:min-h-[460px] rounded-xl overflow-hidden border-2 transition-all duration-300"
                            :class="homeTeam
                                ? 'border-red-600 shadow-[0_0_40px_rgba(220,38,38,0.18)]'
                                : 'border-gray-800'"
                        >

                            <!-- background -->
                            <div class="absolute inset-0 bg-gradient-to-br from-red-950 via-gray-950 to-black"></div>

                            <!-- diagonal -->
                            <div class="absolute -right-20 top-0 w-64 h-full bg-red-600/10 -skew-x-12"></div>

                            <div class="relative z-10 h-full flex flex-col justify-between p-6">

                                <div class="flex justify-between items-start">

                                    <div>
                                        <div class="text-red-500 text-xs font-black tracking-[0.35em] uppercase">
                                            Player 1
                                        </div>

                                        <div class="text-3xl font-black italic uppercase">
                                            Home
                                        </div>
                                    </div>

                                    <div class="text-5xl font-black italic text-red-900">
                                        01
                                    </div>

                                </div>


                                <!-- Team emblem placeholder -->
                                <div class="flex justify-center py-6">

                                    <div
                                        class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-red-600 bg-black flex items-center justify-center shadow-[0_0_35px_rgba(220,38,38,0.25)]"
                                    >

                                        <span
                                            v-if="!homeTeam"
                                            class="text-red-900 text-5xl font-black"
                                        >
                                            ?
                                        </span>

                                        <span
                                            v-else
                                            class="text-red-500 text-4xl md:text-5xl font-black text-center px-3"
                                        >
                                            {{ teamInitials(homeTeam.name) }}
                                        </span>

                                    </div>

                                </div>


                                <div>

                                    <div
                                        class="text-center text-2xl md:text-4xl font-black uppercase italic min-h-[48px]"
                                    >
                                        {{ homeTeam?.name ?? 'SELECT TEAM' }}
                                    </div>

                                    <select
                                        v-model="homeTeamId"
                                        @change="selectHomeTeam"
                                        class="mt-4 w-full bg-black/80 border border-red-800 text-white rounded-lg px-4 py-3 font-bold focus:outline-none focus:border-red-500"
                                    >

                                        <option value="">
                                            Select Home Team
                                        </option>

                                        <option
                                            v-for="team in availableHomeTeams"
                                            :key="team.id"
                                            :value="team.id"
                                        >
                                            {{ team.name }}
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <!-- ================= VS ================= -->
                        <div class="flex md:flex-col items-center justify-center px-2">

                            <div class="hidden md:block w-px h-20 bg-gradient-to-b from-transparent via-gray-700 to-transparent"></div>

                            <div class="relative my-3 md:my-5">

                                <div
                                    class="absolute inset-0 blur-xl bg-orange-600/30"
                                ></div>

                                <div
                                    class="relative text-5xl md:text-6xl font-black italic text-white"
                                >
                                    VS
                                </div>

                            </div>

                            <div class="hidden md:block w-px h-20 bg-gradient-to-b from-transparent via-gray-700 to-transparent"></div>

                        </div>


                        <!-- ================= AWAY ================= -->
                        <div
                            class="relative group min-h-[360px] md:min-h-[460px] rounded-xl overflow-hidden border-2 transition-all duration-300"
                            :class="awayTeam
                                ? 'border-blue-600 shadow-[0_0_40px_rgba(37,99,235,0.18)]'
                                : 'border-gray-800'"
                        >

                            <div class="absolute inset-0 bg-gradient-to-bl from-blue-950 via-gray-950 to-black"></div>

                            <div class="absolute -left-20 top-0 w-64 h-full bg-blue-600/10 skew-x-12"></div>

                            <div class="relative z-10 h-full flex flex-col justify-between p-6">

                                <div class="flex justify-between items-start">

                                    <div>
                                        <div class="text-blue-500 text-xs font-black tracking-[0.35em] uppercase">
                                            Player 2
                                        </div>

                                        <div class="text-3xl font-black italic uppercase">
                                            Away
                                        </div>
                                    </div>

                                    <div class="text-5xl font-black italic text-blue-900">
                                        02
                                    </div>

                                </div>


                                <div class="flex justify-center py-6">

                                    <div
                                        class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-blue-600 bg-black flex items-center justify-center shadow-[0_0_35px_rgba(37,99,235,0.25)]"
                                    >

                                        <span
                                            v-if="!awayTeam"
                                            class="text-blue-900 text-5xl font-black"
                                        >
                                            ?
                                        </span>

                                        <span
                                            v-else
                                            class="text-blue-500 text-4xl md:text-5xl font-black text-center px-3"
                                        >
                                            {{ teamInitials(awayTeam.name) }}
                                        </span>

                                    </div>

                                </div>


                                <div>

                                    <div
                                        class="text-center text-2xl md:text-4xl font-black uppercase italic min-h-[48px]"
                                    >
                                        {{ awayTeam?.name ?? 'SELECT TEAM' }}
                                    </div>

                                    <select
                                        v-model="awayTeamId"
                                        @change="selectAwayTeam"
                                        class="mt-4 w-full bg-black/80 border border-blue-800 text-white rounded-lg px-4 py-3 font-bold focus:outline-none focus:border-blue-500"
                                    >

                                        <option value="">
                                            Select Away Team
                                        </option>

                                        <option
                                            v-for="team in availableAwayTeams"
                                            :key="team.id"
                                            :value="team.id"
                                        >
                                            {{ team.name }}
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                    </div>


                   
                    <div
                        v-if="homeTeam && awayTeam"
                        class="mt-8 border border-gray-800 bg-black/70 rounded-xl p-5"
                    >

                        <div class="flex flex-col md:flex-row justify-center items-center gap-4 md:gap-10">

                            <div class="text-red-500 font-black uppercase text-xl">
                                {{ homeTeam.name }}
                            </div>

                            <div class="text-gray-700 font-black">
                                VS
                            </div>

                            <div class="text-blue-500 font-black uppercase text-xl">
                                {{ awayTeam.name }}
                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         FIGHT BUTTON
                    ================================================== -->
                    <div class="flex justify-center mt-8">

                        <button
                            @click="createSchedule"
                            :disabled="!canStart || processing"
                            class="relative overflow-hidden group px-12 md:px-24 py-5 rounded-lg font-black uppercase italic text-xl md:text-3xl tracking-widest transition-all duration-300 disabled:opacity-30 disabled:cursor-not-allowed"
                            :class="canStart
                                ? 'bg-orange-600 hover:bg-orange-500 shadow-[0_0_45px_rgba(234,88,12,0.35)]'
                                : 'bg-gray-800'"
                        >

                            <span class="relative z-10">
                                {{ processing ? 'FIGHTING...' : 'FIGHT!' }}
                            </span>

                        </button>

                    </div>

                </div>

            </div>

            <div
                v-if="!isHide"
                class="mt-8"
            >

                <div class="flex items-center justify-between mb-4">

                    <div>
                        <div class="text-xs uppercase tracking-widest text-gray-600">
                            History
                        </div>

                        <h2 class="text-2xl font-black italic uppercase">
                            Exhibition Matches
                        </h2>
                    </div>

                    <button
                        @click="fetchExhibitionSchedules"
                        class="text-sm text-gray-500 hover:text-white"
                    >
                        Refresh
                    </button>

                </div>


                <div
                    v-if="schedules.length"
                    class="grid grid-cols-1 md:grid-cols-2 gap-3"
                >

                    <div
                        v-for="game in schedules"
                        :key="game.id"
                        class="bg-gray-950 border border-gray-800 rounded-lg p-4 hover:border-gray-600 transition"
                    >

                        <div class="flex justify-between items-center">

                            <div class="text-right flex-1">
                                <div class="font-bold">
                                    {{ game.home_team?.name ?? game.home_team_name ?? 'Home' }}
                                </div>
                            </div>

                            <div class="px-4 text-xs text-gray-600 font-black">
                                VS
                            </div>

                            <div class="flex-1">
                                <div class="font-bold">
                                    {{ game.away_team?.name ?? game.away_team_name ?? 'Away' }}
                                </div>
                            </div>

                        </div>


                        <div class="text-center mt-3 text-xs uppercase tracking-widest">
                            <span
                                :class="game.status == 2
                                    ? 'text-green-500'
                                    : 'text-orange-500'"
                            >
                                {{ game.status == 2 ? 'Completed' : 'Scheduled' }}
                            </span>
                        </div>

                    </div>

                </div>


                <div v-else class="block px-2">
                    <transition name="fade" mode="out-in">
                        <!-- //add on v-if showGameResults &&  -->
                        <div v-if="activeGameId != 0" :key="'game-' + activeGameId">
                            <GameResults :game_id="activeGameId" :season_id="props.season_id" :showBoxScore="false" :showGameNews="false" />
                        </div>
                        <!-- <div v-else-if="!showGameResults && activeGameId != 0" :key="'transactions-' + activeGameId"> -->
                            <!-- <RecentTransactions :key="activeGameId"/> -->
                            <!-- <Top15MVPCandidate  :key="activeGameId" :current_round="currentRound"/> -->
                        <!-- </div> -->
                    </transition>
                </div>

            </div>

        </div>

    </div>
</template>


<script setup>

import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";


// ================================================================
// PROPS
// ================================================================

const props = defineProps({
    season_id: {
        type: [Number, String],
        default: null,
    },

    conference_id: {
        type: [Number, String],
        default: null,
    },
});


// ================================================================
// STATE
// ================================================================

const selectedLeague = ref(1);

const selectedSeason = ref(
    props.season_id ?? ""
);

const selectedConference = ref(
    props.conference_id ?? ""
);

const isHide = ref(false);
const seasons = ref([]);
const conferences = ref([]);
const teams = ref([]);

const homeTeamId = ref("");
const awayTeamId = ref("");

const homeTeam = ref(null);
const awayTeam = ref(null);

const schedules = ref([]);

const activeGameId = ref(null);

const processing = ref(false);
const showResult = ref(false);


// ================================================================
// COMPUTED
// ================================================================

const canStart = computed(() => {
    return (
        selectedConference.value &&
        homeTeamId.value &&
        awayTeamId.value &&
        homeTeamId.value !== awayTeamId.value
    );
});


const selectedSeasonLabel = computed(() => {

    const season = seasons.value.find(
        item => String(item.id) === String(selectedConference.value)
    );

    return season
        ? (season.name ?? `Season ${season.id}`)
        : "Select Season";

});


const availableHomeTeams = computed(() => {
    return teams.value;
});


const availableAwayTeams = computed(() => {
    return teams.value.filter(
        team => String(team.id) !== String(homeTeamId.value)
    );
});


// ================================================================
// TEAM INITIALS
// ================================================================

const teamInitials = (name) => {

    if (!name) {
        return "?";
    }

    return name
        .split(" ")
        .filter(Boolean)
        .slice(0, 3)
        .map(word => word.charAt(0))
        .join("")
        .toUpperCase();

};

const fetchConferences = async () => {

    try {

        const response = await axios.post(
            route("conference.season.dropdown"),
            {
                league_id: 1,
            }
        );

        /*
         * If your endpoint returns conferences separately,
         * replace this assignment with the corresponding field.
         */

        conferences.value = response.data ?? [];

    } catch (error) {

        console.error(
            "Error fetching conferences:",
            error
        );

    }

};


// ================================================================
// LOAD TEAMS
//
// conference.team.dropdown
// payload:
// conference_id
// ================================================================

const fetchTeams = async () => {

    teams.value = [];
    homeTeamId.value = "";
    awayTeamId.value = "";
    homeTeam.value = null;
    awayTeam.value = null;

    if (!selectedConference.value) {
        return;
    }

    try {

        const response = await axios.post(
            route("conference.team.dropdown", {
                conference_id: selectedConference.value,
            }),
            {
                conference_id: selectedConference.value,
            }
        );

        teams.value =
            response.data?.data ??
            response.data ??
            [];

    } catch (error) {

        console.error(
            "Error fetching teams:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Unable to load teams",
            text: error.response?.data?.message ??
                "Failed to load teams.",
        });

    }

};



// ================================================================
// HOME TEAM
// ================================================================

const selectHomeTeam = () => {

    homeTeam.value =
        teams.value.find(
            team =>
                String(team.id) ===
                String(homeTeamId.value)
        ) ?? null;

    /*
     * If the user selected the same team as away,
     * clear away.
     */

    if (
        awayTeamId.value &&
        String(awayTeamId.value) ===
        String(homeTeamId.value)
    ) {

        awayTeamId.value = "";
        awayTeam.value = null;

    }

};


// ================================================================
// AWAY TEAM
// ================================================================

const selectAwayTeam = () => {

    awayTeam.value =
        teams.value.find(
            team =>
                String(team.id) ===
                String(awayTeamId.value)
        ) ?? null;

};


// ================================================================
// CREATE + SIMULATE EXHIBITION
//
// create.schedule.exhibtion
// payload:
// home_team_id
// away_team_id
//
// then:
//
// game.simulate.exhibition
// payload:
// game_id
// ================================================================
 const simulateAll = async () => {
        isHide.value = true;
        
        let failedGames = new Set(); // Store failed games

        console.log("Checking pending games for the season...");

        try {
            let gameIds = schedules.value;

            while (gameIds.length > 0) {
                for (const gameId of gameIds) {
                    console.log(`Simulating Game ID: ${gameId.id}`);

                    try {
                        await simulateGame(gameId.id);
                        await new Promise((resolve) => setTimeout(resolve, 500));

                        // Remove successfully simulated game from failedGames if it exists
                        failedGames.delete(gameId);
                    } catch (error) {
                        console.error(`Error simulating Game ID: ${gameId.id}`, error);
                        failedGames.add(gameId);
                    }
                }
            }

            Swal.fire({
                icon: "success",
                title: "All games are simulated!",
                text: "The entire season has been completed.",
            });
            isHide.value = false;
        } catch (error) {
            console.error("Error fetching or simulating games:", error);
        }

        // Retry failed games
        if (failedGames.size > 0) {
            console.warn(`Retrying ${failedGames.size} failed games...`);

            for (const gameId of [...failedGames]) {
                try {
                    console.log(`Retrying Game ID: ${gameId}`);
                    await simulateGame(gameId.id);

                    await new Promise((resolve) => setTimeout(resolve, 2000));
                    failedGames.delete(gameId); // Remove from failed list on success
                } catch (error) {
                    console.error(`Retry failed for Game ID: ${gameId}`, error);
                }
            }
        }

        isHide.value = false;
        await fetchExhibitionSchedules();

        Swal.fire({
            icon: "success",
            title: "All games simulated!",
            text: "The entire season has been completed.",
        });
};
const simulateGame = async (schedule_id) => {
        try {

            isHide.value = true;
            const response = await axios.post(route('game.simulate.exhibition'), {
                schedule_id: schedule_id,
                conference_id: 0
            });
            
             // Show a toast notification
            Swal.fire({
                icon: "success",
                title: "Game Simulated!",
                text: `Game ID ${schedule_id} has been completed.`,
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: "top-end",
            });
            
            activeGameId.value = response.data.game_id ?? 0;
    
            await new Promise((resolve) => setTimeout(resolve, 500)); // Allow 3 flips

            // Clear the interval when moving to next game
            if (flipTimer.value) {
                clearInterval(flipTimer.value);
                flipTimer.value = null;
            }


        } catch (error) {
            console.error("Error simulating game:", error);
            if (flipTimer.value) clearInterval(flipTimer.value);
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: error.response?.data?.message || "An error occurred.",
                timer: 3000,
                showConfirmButton: false,
            });
        }
};

const createSchedule = async () => {

    if (!canStart.value || processing.value) {
        return;
    }

    processing.value = true;

    try {

        /*
         * ---------------------------------------------------------
         * STEP 1
         * Create schedule
         * ---------------------------------------------------------
         */

        const scheduleResponse = await axios.post(
            route("create.schedule.exhibtion"),
            {
                home_team_id: Number(homeTeamId.value),
                away_team_id: Number(awayTeamId.value),
            }
        );

        await fetchExhibitionSchedules();


        Swal.fire({
            icon: "success",
            title: "FIGHT!",
            text: "Exhibition game completed.",
            timer: 1200,
            showConfirmButton: false,
            toast: true,
            position: "top-end",
        });


    } catch (error) {

        console.error(
            "Error creating/simulating exhibition:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Match Failed",
            text:
                error.response?.data?.message ??
                error.message ??
                "Unable to simulate exhibition game.",
        });

    } finally {

        processing.value = false;

    }

};

const fetchExhibitionSchedules = async () => {

    try {
        const response = await axios.post(route("schedule.list.exhibition"),{season_id: props.season_id,});
        schedules.value = response.data ?? [];
    } catch (error) {
        console.error(
            "Error fetching exhibition schedules:",
            error
        );

    }

};


// ================================================================
// RESET
// ================================================================

const resetMatch = () => {

    homeTeamId.value = "";
    awayTeamId.value = "";

    homeTeam.value = null;
    awayTeam.value = null;

    gameId.value = null;
    boxscore.value = null;

    showResult.value = false;

};


// ================================================================
// INITIAL LOAD
// ================================================================

onMounted(async () => {

    await fetchConferences();
    await fetchExhibitionSchedules();

    if (selectedConference.value) {
        await fetchTeams();
    }

});

</script>


<style scoped>

button {
    -webkit-tap-highlight-color: transparent;
}

select {
    appearance: auto;
}

</style>