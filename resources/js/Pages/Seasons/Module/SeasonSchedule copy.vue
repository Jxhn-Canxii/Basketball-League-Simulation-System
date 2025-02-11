<template>
<div class="block">
    <div class="flex justify-end mb-2"></div>
    <h2 class="text-lg font-semibold text-gray-800 mb-2">
        Schedule and Results ({{
            season_schedules?.schedules?.length
        }})
    </h2>
    <div class="flex justify-end mb-2"></div>
    <div
        v-if="
            season_schedules &&
            season_schedules.schedules?.length > 0 && !loadingSchedules
        "
        class="grid md:grid-cols-2 sm:col-span-1 gap-6"
    >
        <div
            v-for="(game, index) in season_schedules.schedules"
            :key="index"
            class="bg-white shadow overflow-hidden sm:rounded-lg"
        >
            <div class="px-4 py-5 sm:px-6">
                <h3
                    class="text-xs flex font-extrabold text-nowrap leading-6 space-x-2 uppercase text-gray-900"
                >
                    <TeamDetails
                        :team_id="game.home_team_id" 
                        :key="game.home_team_id" 
                        :showButton="0" 
                        :text="`${game.home_team_name}`" />
                    <br />
                    <small class="text-red-500">vs</small>
                    <br />
                    <TeamDetails
                        :team_id="game.away_team_id" 
                        :key="game.away_team_id" 
                        :showButton="0" 
                        :text="`${game.away_team_name}`" />
                </h3>
                <p
                    class="mt-1 max-w-2xl text-xs uppercase text-gray-500"
                >
                    {{ "Round #" + (parseFloat(game.round) + 1) }}
                </p>
                <code class="mt-1 max-w-2xl text-xs text-gray-300">
                    #R{{ game.round }}-{{ game.game_id }}
                </code>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <template
                        v-if="
                            game.home_score === 0 &&
                            game.away_score === 0
                        "
                    >
                        <div
                            v-if="!isHide"
                            class="bg-white px-4 py-5 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-6"
                        >
                            <a
                                href="#"
                                class="text-sm text-blue-500 underline font-bold"
                                @click.prevent="
                                    isGameResultModalOpen =
                                        game.game_id
                                "
                                >View Result</a
                            >
                        </div>
                        <div
                            v-else
                            class="bg-white px-4 py-3 text-center sm:grid sm:grid-cols-1 sm:gap-4 sm:px-6"
                        >
                            <p
                                class="text-red-500 animate-pulse text-xs text-nowrap"
                            >
                                Getting results
                            </p>
                        </div>
                    </template>
                    <template v-else>
                        <div
                            class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                        >
                            <dt
                                class="text-sm font-medium text-gray-500"
                            >
                                Home
                            </dt>
                            <dd
                                class="mt-1 text-sm text-gray-900 sm:col-span-2"
                            >
                                {{ game.home_score }}
                            </dd>
                        </div>
                        <div
                            class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                        >
                            <dt
                                class="text-sm font-medium text-gray-500"
                            >
                                Away
                            </dt>
                            <dd
                                class="mt-1 text-sm text-gray-900 sm:col-span-2"
                            >
                                {{ game.away_score }}
                            </dd>
                        </div>
                        <div
                            class="bg-gray-200 px-4 py-1 flex justify-end sm:gap-4 sm:px-6"
                        >
                            <a
                                href="#"
                                class="text-sm text-blue-500 underline font-bold"
                                @click.prevent="
                                    isGameResultModalOpen =
                                        game.game_id
                                "
                                >View Result</a
                            >
                        </div>
                    </template>
                </dl>
            </div>
        </div>
    </div>
    <div v-if="loadingSchedules">
        <p class="text-gray-500">Loading Schedules.</p>
    </div>
    <div v-if="
            season_schedules &&
            season_schedules.schedules?.length == 0 && !loadingSchedules
        ">
        <p class="text-gray-500">No schedule available.</p>
    </div>
</div>
<Modal :show="isGameResultModalOpen" :maxWidth="'4xl'">
    <button
        class="flex float-end bg-gray-100 p-3"
        @click.prevent="isGameResultModalOpen = false"
    >
        <i class="fa fa-times text-black-600"></i>
    </button>
    <div class="mt-4">
        <GameResults :game_id="isGameResultModalOpen" />
    </div>
</Modal>
</template>
<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch, computed } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";

const season_schedules = ref(false);
const isGameResultModalOpen = ref(false);
const isHide = ref(false);
const currentRound = ref(0);
const topPlayersKey = ref(0); // Key for TopPlayers component
const loadingSchedules = ref(false);
const activeGameId = ref(0);
const props = defineProps({
    season_id: {
        type: [Number,String],
        required: true,
    },
    conference_id: {
        type: [Number,String],
        required: true,
    },
    season_data: Object,
});
const fetchConferenceSchedules = async () => {
    try {
        season_schedules.value = [];
        loadingSchedules.value = true;
        const response = await axios.post(route("conferences.schedules"), {
            season_id: props.season_id,
            conference_id: props.conference_id,
        });
        season_schedules.value = response.data;
        loadingSchedules.value = false;
    } catch (error) {
        console.error("Error fetching season standings:", error);
    }
};

const simulatePerRound = async () => {
    const rounds = season_schedules.value.rounds;
    const lastRoundIndex = rounds.length - 1; // Get the index of the last round

    for (const [index, round] of rounds.entries()) {
        // Check if it's the last round
        const isLastRound = index === lastRoundIndex;

        // Pass an additional parameter if it's the last round
        await simulateRoundGames(round, isLastRound);
    }

};
const simulateAll = async () => {
    const rounds = season_schedules.value.rounds;
    const lastRoundIndex = rounds.length - 1; // Get the index of the last round

    for (let mode = 1; mode <= 4; mode++) {
        for (const [index, round] of rounds.entries()) {
            // Check if it's the last round
            const isLastRound = index === lastRoundIndex;

            // Pass an additional parameter if it's the last round
            await simulateAllRoundGames(round, isLastRound,mode);
        }
    }
};
const simulateAllRoundGames = async (round, isLast,conference_id) => {
    try {
        isHide.value = true;
        currentRound.value = round;
        const response = await axios.post(route("game.per.round"), {
            season_id: props.season_id, // Assuming the parameter name should be schedule_id
            round: round,
            conference_id: conference_id,
        });
        // await localStorage.setItem('season-key',generateRandomKey());
        const gameIds = response.data.schedule_ids; // Assuming the response contains 'game_ids'
        // Loop through each game ID
        for (const gameId of gameIds) {
            // Perform an action with each game ID
            console.log(`Processing Game ID: ${gameId}`);
            await simulateGame(gameId,conference_id);
            // You can also add more logic here, like fetching game details or updating the state
        }

        topPlayersKey.value = round;
        if (isLast && conference_id == 4) {
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: response.data.message, // Assuming the response contains a 'message' field
            });

            await fetchConferenceSchedules(conference_id);
            isHide.value = false;
            currentRound.value = false;
        }
    } catch (error) {
        console.error("Error simulating the game:", error);
        // Show error message using Swal2 if needed
        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text: error.response.data.error,
        });
    }
};
const simulateRoundGames = async (round, isLast) => {
    try {
        isHide.value = true;
        currentRound.value = round;
        const response = await axios.post(route("game.per.round"), {
            season_id: props.season_id, // Assuming the parameter name should be schedule_id
            round: round,
            conference_id: activeConferenceTab.value,
        });
        // await localStorage.setItem('season-key',generateRandomKey());
        const gameIds = response.data.schedule_ids; // Assuming the response contains 'game_ids'
        // Loop through each game ID
        for (const gameId of gameIds) {
            // Perform an action with each game ID
            console.log(`Processing Game ID: ${gameId}`);
            await simulateGame(gameId,activeConferenceTab.value);
            // You can also add more logic here, like fetching game details or updating the state
        }

        topPlayersKey.value = round;
        if (isLast) {
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: response.data.message, // Assuming the response contains a 'message' field
            });

            await fetchConferenceSchedules(conference_id);
            isHide.value = false;
            currentRound.value = false;
        }
    } catch (error) {
        console.error("Error simulating the game:", error);
        // Show error message using Swal2 if needed
        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text: error.response.data.error,
        });
    }
};
const simulateGame = async (schedule_id,conference_id) => {
    try {
        isHide.value = true;

        const response = await axios.post(route("game.simulate.regular"), {
            schedule_id: schedule_id, // Assuming the parameter name should be schedule_id
        });
        // await localStorage.setItem('season-key',generateRandomKey());
        // isHide.value = false;
        topPlayersKey.value++; // Trigger update of TopPlayers component
        await fetchConferenceStandings(conference_id);
        activeGameId.value = response.data.game_id ?? 0;
        // Show success message using Swal2
        // Swal.fire({
        //     icon: "success",
        //     title: "Success!",
        //     text: response.data.message, // Assuming the response contains a 'message' field
        // });
    } catch (error) {
        console.error("Error simulating per conference:", error);
        // Show error message using Swal2 if needed
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response.data.message,
            timer: 3000, // Auto-hide after 3 seconds (3000 ms)
            showConfirmButton: false, // Hide the "OK" button
        });

    }
};

onMounted(() => {
    fetchConferenceSchedules();
});
</script>