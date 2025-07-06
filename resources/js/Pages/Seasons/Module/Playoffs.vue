```vue
<template>
    <div
        class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6 border-b-2 border-dashed"
        v-if="season_info.seasons && season_info.seasons[0].status > 1 && !loading"
    >
        <div class="md:col-span-4 overflow-y-auto">
            <div class="flex justify-between">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Playoffs</h2>
                 <button
                    :disabled="season_info.seasons && season_info.seasons[0].status > 2"
                    :class="season_info.seasons && season_info.seasons[0].status > 2 ? 'bg-gray-500 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600 hover:text-red-900'"
                    @click="simulateFullPlayoffs"
                    type="button"
                    class="text-white bg-red-500 bg-gradient-to-br p-3 shadow rounded-full font-bold text-md text-nowrap"
                >
                    Simulate Full Playoffs
                </button>
            </div>
            <div
                class="flex justify-center"
                v-if="season_info.seasons && season_info.seasons[0].status == 2"
            >
                <button
                    v-if="!isHide"
                    @click="createPlayOffSchedule('start')"
                    class="text-white bg-red-500 bg-gradient-to-br p-3 shadow rounded-full font-bold text-md text-nowrap hover:text-indigo-900"
                >
                    Start Play-offs
                </button>
                <div class="flex justify-center" v-else>
                    <p class="text-red-500 animate-pulse">
                        Preparing Playoff Schedules
                    </p>
                </div>
            </div>
            <div
                class="flex justify-center text-red-500 pt-4"
                v-if="season_info.seasons && season_info.seasons[0].status == 3 && !loading"
            >
                <small>Please click to start play-offs simulation!</small>
            </div>
            <!-- Display playoff tree -->
            <div class="grid grid-cols-1 gap-6" v-if="season_playoffs.playoffs">
                <div
                    v-for="(
                        roundMatches, roundName
                    ) in season_playoffs.playoffs"
                    :key="roundName"
                    class="block"
                >
                    <div v-if="season_playoffs.playoffs[roundName].length > 0">
                        <h3
                            v-if="season_playoffs.playoffs[roundName].length > 0"
                            class="text-lg font-semibold mt-4"
                            :class="{
                                'text-start text-orange-400': season_playoffs.playoffs[roundName].length > 2,
                                'text-center text-orange-600': season_playoffs.playoffs[roundName].length <= 2,
                            }"
                        >
                            {{ roundNameFormatter(roundName) }}
                        </h3>
                        <div
                            class="grid gap-4"
                            :class="{
                                'grid-cols-4': season_playoffs.playoffs[roundName].length >= 4,
                                'grid-cols-2 justify-center': season_playoffs.playoffs[roundName].length === 2,
                                'grid-cols-1 justify-center max-w-md mx-auto': season_playoffs.playoffs[roundName].length === 1
                            }"
                        >
                            <!-- Add container for centering when 2 cards -->
                            <div 
                                v-if="season_playoffs.playoffs[roundName].length === 2"
                                class="col-span-2 flex justify-center gap-4"
                            >
                                <div 
                                    v-for="(match, mm) in season_playoffs.playoffs[roundName]" 
                                    :key="match.game_id"
                                    class="w-full max-w-md"
                                >
                                    <!-- Existing card content -->
                                    <div 
                                        :style="{
                                            background: `
                                                linear-gradient(45deg, 
                                                    #${match?.home_team?.secondary_color} 0%, 
                                                    #${match?.home_team?.secondary_color} 50%, 
                                                    #${match?.home_team?.primary_color} 50%, 
                                                    #${match?.home_team?.primary_color} 100%
                                                ),
                                                linear-gradient(-45deg, 
                                                    #${match?.away_team?.primary_color} 0%, 
                                                    #${match?.away_team?.primary_color} 50%, 
                                                    #${match?.away_team?.secondary_color} 50%, 
                                                    #${match?.away_team?.secondary_color} 100%
                                                )`,
                                            backgroundSize: '50% 100%',
                                            backgroundPosition: 'left, right',
                                            backgroundRepeat: 'no-repeat'
                                        }"
                                        class="shadow-md rounded-md overflow-hidden"
                                    >
                                        <div
                                            class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center"
                                        >
                                            <div>
                                                <h3>
                                                    {{ match.home_team.home_score }}
                                                </h3>
                                            </div>
                                            <div>
                                                <h3>
                                                    {{ match.away_team.away_score }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div
                                            class="px-1 py-1 flex justify-between items-center"
                                        >
                                            <h3>
                                                <TeamDetails
                                                :team_id="match.home_team.id" 
                                                :key="match.home_team.id" 
                                                :showButton="0"
                                                :showInfo="false"
                                                class="text-white text-md uppercase text-wrap text-left"
                                                :current_conference_rank="match.home_team.conference_rank"
                                                :text="`#${match.home_team.overall_rank ?? 'TBD'} ${match.home_team.name ?? 'TBD'}`"/>
                                            </h3>
                                            <h3>
                                                <TeamDetails
                                                    :team_id="match.away_team.id" 
                                                    :key="match.away_team.id" 
                                                    :showButton="0"
                                                    :showInfo="false"
                                                    class="text-white text-md uppercase text-wrap text-right"
                                                    :current_conference_rank="match.away_team.conference_rank"
                                                    :text="`#${match.away_team.overall_rank ?? 'TBD'} ${match.away_team.name ?? 'TBD'}`" />
                                            </h3>
                                        </div>
                                        <div
                                            class="px-4 text-nowrap text-xs py-0 flex justify-center"
                                        >
                                            <span class="px-2 text-xs text-white py-1 rounded">
                                                {{ roundNameFormatter(roundName) }}
                                            </span>
                                        </div>
                                        <div
                                            class="border-gray-200 flex justify-between mt-4 mb-4 bg-white"
                                        >
                                            <div
                                                class="px-2 text-nowrap text-xs py-3"
                                            >
                                                <span 
                                                :class="getConferenceClass(match.home_team.conference,match.away_team.conference)"
                                                class="px-2 shadow py-1 rounded">
                                                    {{ match.home_team.conference }} #{{
                                                        match.home_team.conference_rank
                                                    }}
                                                    vs {{ match.away_team.conference }} #{{
                                                        match.away_team.conference_rank
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="px-2 text-nowrap text-red-600 text-xs py-2 flex items-center"
                                            >
                                                <button
                                                    class="text-white bg-orange-500 rounded-full px-2 py-1"
                                                    @click.prevent="
                                                        compareTeams(
                                                            match.home_team.id,
                                                            match.away_team.id
                                                        )
                                                    "
                                                >
                                                    Compare
                                                    <i
                                                        class="fa fa-exchange-alt ml-1"
                                                    ></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="border-gray-200 flex justify-center">
                                            <button
                                                v-if="!isHide && match.winner == 0"
                                                @click.prevent="
                                                    simulateGame(
                                                        match.id,
                                                        match.game_id,
                                                        2,
                                                        mm,
                                                        roundName
                                                    )
                                                "
                                                class="bg-slate-900 rounded-t text-orange-500 px-2 hover:bg-slate-300 text-sm font-bold"
                                            >
                                                Simulate Game
                                            </button>
                                            <a
                                                href="#"
                                                v-if="!isHide && match.winner != 0"
                                                class="bg-slate-900 rounded-t text-blue-500 underlined px-2 hover:bg-slate-300 text-sm font-bold"
                                                @click.prevent="
                                                    isGameResultModalOpen =
                                                        match.game_id
                                                "
                                            >
                                                View Result
                                            </a>
                                            <p v-if="isHide && mm == activeIndex" class="bg-slate-900 rounded-t text-red-500 px-2 hover:bg-slate-300 text-sm font-bold">
                                                Simulating...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Single card centered -->
                            <div 
                                v-if="season_playoffs.playoffs[roundName].length === 1"
                                class="flex justify-center"
                            >
                                <div 
                                    v-for="(match, mm) in season_playoffs.playoffs[roundName]" 
                                    :key="match.game_id"
                                    class="w-full max-w-md"
                                >
                                    <!-- Existing card content -->
                                    <div 
                                        :style="{
                                            background: `
                                                linear-gradient(45deg, 
                                                    #${match?.home_team?.secondary_color} 0%, 
                                                    #${match?.home_team?.secondary_color} 50%, 
                                                    #${match?.home_team?.primary_color} 50%, 
                                                    #${match?.home_team?.primary_color} 100%
                                                ),
                                                linear-gradient(-45deg, 
                                                    #${match?.away_team?.primary_color} 0%, 
                                                    #${match?.away_team?.primary_color} 50%, 
                                                    #${match?.away_team?.secondary_color} 50%, 
                                                    #${match?.away_team?.secondary_color} 100%
                                                )`,
                                            backgroundSize: '50% 100%',
                                            backgroundPosition: 'left, right',
                                            backgroundRepeat: 'no-repeat'
                                        }"
                                        class="shadow-md rounded-md overflow-hidden"
                                    >
                                        <div
                                            class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center"
                                        >
                                            <div>
                                                <h3>
                                                    {{ match.home_team.home_score }}
                                                </h3>
                                            </div>
                                            <div>
                                                <h3>
                                                    {{ match.away_team.away_score }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div
                                            class="px-1 py-1 flex justify-between items-center"
                                        >
                                            <h3>
                                                <TeamDetails
                                                :team_id="match.home_team.id" 
                                                :key="match.home_team.id" 
                                                :showButton="0"
                                                :showInfo="false"
                                                class="text-white text-md uppercase text-wrap text-left"
                                                :current_conference_rank="match.home_team.conference_rank"
                                                :text="`#${match.home_team.overall_rank ?? 'TBD'} ${match.home_team.name ?? 'TBD'}`"/>
                                            </h3>
                                            <h3>
                                                <TeamDetails
                                                    :team_id="match.away_team.id" 
                                                    :key="match.away_team.id" 
                                                    :showButton="0"
                                                    :showInfo="false"
                                                    class="text-white text-md uppercase text-wrap text-right"
                                                    :current_conference_rank="match.away_team.conference_rank"
                                                    :text="`#${match.away_team.overall_rank ?? 'TBD'} ${match.away_team.name ?? 'TBD'}`" />
                                            </h3>
                                        </div>
                                        <div
                                            class="px-4 text-nowrap text-xs py-0 flex justify-center"
                                        >
                                            <span class="px-2 text-xs text-white py-1 rounded">
                                                {{ roundNameFormatter(roundName) }}
                                            </span>
                                        </div>
                                        <div
                                            class="border-gray-200 flex justify-between mt-4 mb-4 bg-white"
                                        >
                                            <div
                                                class="px-2 text-nowrap text-xs py-3"
                                            >
                                                <span 
                                                :class="getConferenceClass(match.home_team.conference,match.away_team.conference)"
                                                class="px-2 shadow py-1 rounded">
                                                    {{ match.home_team.conference }} #{{
                                                        match.home_team.conference_rank
                                                    }}
                                                    vs {{ match.away_team.conference }} #{{
                                                        match.away_team.conference_rank
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="px-2 text-nowrap text-red-600 text-xs py-2 flex items-center"
                                            >
                                                <button
                                                    class="text-white bg-orange-500 rounded-full px-2 py-1"
                                                    @click.prevent="
                                                        compareTeams(
                                                            match.home_team.id,
                                                            match.away_team.id
                                                        )
                                                    "
                                                >
                                                    Compare
                                                    <i
                                                        class="fa fa-exchange-alt ml-1"
                                                    ></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="border-gray-200 flex justify-center">
                                            <button
                                                v-if="!isHide && match.winner == 0"
                                                @click.prevent="
                                                    simulateGame(
                                                        match.id,
                                                        match.game_id,
                                                        2,
                                                        mm,
                                                        roundName
                                                    )
                                                "
                                                class="bg-slate-900 rounded-t text-orange-500 px-2 hover:bg-slate-300 text-sm font-bold"
                                            >
                                                Simulate Game
                                            </button>
                                            <a
                                                href="#"
                                                v-if="!isHide && match.winner != 0"
                                                class="bg-slate-900 rounded-t text-blue-500 underlined px-2 hover:bg-slate-300 text-sm font-bold"
                                                @click.prevent="
                                                    isGameResultModalOpen =
                                                        match.game_id
                                                "
                                            >
                                                View Result
                                            </a>
                                            <p v-if="isHide && mm == activeIndex" class="bg-slate-900 rounded-t text-red-500 px-2 hover:bg-slate-300 text-sm font-bold">
                                                Simulating...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Regular grid for 4 or more cards -->
                            <div 
                                v-if="season_playoffs.playoffs[roundName].length >= 4"
                                v-for="(match, mm) in season_playoffs.playoffs[roundName]"
                                :key="match.game_id"
                                class="col-span-1"
                            >
                                <!-- Existing card content -->
                                <div 
                                    :style="{
                                        background: `
                                            linear-gradient(45deg, 
                                                #${match?.home_team?.secondary_color} 0%, 
                                                #${match?.home_team?.secondary_color} 50%, 
                                                #${match?.home_team?.primary_color} 50%, 
                                                #${match?.home_team?.primary_color} 100%
                                            ),
                                            linear-gradient(-45deg, 
                                                #${match?.away_team?.primary_color} 0%, 
                                                #${match?.away_team?.primary_color} 50%, 
                                                #${match?.away_team?.secondary_color} 50%, 
                                                #${match?.away_team?.secondary_color} 100%
                                            )`,
                                        backgroundSize: '50% 100%',
                                        backgroundPosition: 'left, right',
                                        backgroundRepeat: 'no-repeat'
                                    }"
                                    class="shadow-md rounded-md overflow-hidden"
                                >
                                    <div
                                        class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center"
                                    >
                                        <div>
                                            <h3>
                                                {{ match.home_team.home_score }}
                                            </h3>
                                        </div>
                                        <div>
                                            <h3>
                                                {{ match.away_team.away_score }}
                                            </h3>
                                        </div>
                                    </div>
                                    <div
                                        class="px-1 py-1 flex justify-between items-center"
                                    >
                                        <h3>
                                            <TeamDetails
                                            :team_id="match.home_team.id" 
                                            :key="match.home_team.id" 
                                            :showButton="0"
                                            :showInfo="false"
                                            class="text-white text-md uppercase text-wrap text-left"
                                            :current_conference_rank="match.home_team.conference_rank"
                                            :text="`#${match.home_team.overall_rank ?? 'TBD'} ${match.home_team.name ?? 'TBD'}`"/>
                                        </h3>
                                        <h3>
                                            <TeamDetails
                                                :team_id="match.away_team.id" 
                                                :key="match.away_team.id" 
                                                :showButton="0"
                                                :showInfo="false"
                                                class="text-white text-md uppercase text-wrap text-right"
                                                :current_conference_rank="match.away_team.conference_rank"
                                                :text="`#${match.away_team.overall_rank ?? 'TBD'} ${match.away_team.name ?? 'TBD'}`" />
                                        </h3>
                                    </div>
                                    <div
                                        class="px-4 text-nowrap text-xs py-0 flex justify-center"
                                    >
                                        <span class="px-2 text-xs text-white py-1 rounded">
                                            {{ roundNameFormatter(roundName) }}
                                        </span>
                                    </div>
                                    <div
                                        class="border-gray-200 flex justify-between mt-4 mb-4 bg-white"
                                    >
                                        <div
                                            class="px-2 text-nowrap text-xs py-3"
                                        >
                                            <span 
                                            :class="getConferenceClass(match.home_team.conference,match.away_team.conference)"
                                            class="px-2 shadow py-1 rounded">
                                                {{ match.home_team.conference }} #{{
                                                    match.home_team.conference_rank
                                                }}
                                                vs {{ match.away_team.conference }} #{{
                                                    match.away_team.conference_rank
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="px-2 text-nowrap text-red-600 text-xs py-2 flex items-center"
                                        >
                                            <button
                                                class="text-white bg-orange-500 rounded-full px-2 py-1"
                                                @click.prevent="
                                                    compareTeams(
                                                        match.home_team.id,
                                                        match.away_team.id
                                                    )
                                                "
                                            >
                                                Compare
                                                <i
                                                    class="fa fa-exchange-alt ml-1"
                                                ></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="border-gray-200 flex justify-center">
                                        <button
                                            v-if="!isHide && match.winner == 0"
                                            @click.prevent="
                                                simulateGame(
                                                    match.id,
                                                    match.game_id,
                                                    2,
                                                    mm,
                                                    roundName
                                                )
                                            "
                                            class="bg-slate-900 rounded-t text-orange-500 px-2 hover:bg-slate-300 text-sm font-bold"
                                        >
                                            Simulate Game
                                        </button>
                                        <a
                                            href="#"
                                            v-if="!isHide && match.winner != 0"
                                            class="bg-slate-900 rounded-t text-blue-500 underlined px-2 hover:bg-slate-300 text-sm font-bold"
                                            @click.prevent="
                                                isGameResultModalOpen =
                                                    match.game_id
                                            "
                                        >
                                            View Result
                                        </a>
                                        <p v-if="isHide && mm == activeIndex" class="bg-slate-900 rounded-t text-red-500 px-2 hover:bg-slate-300 text-sm font-bold">
                                            Simulating...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex justify-end"
                            v-if="
                                !isHide &&
                                season_playoffs.playoffs[roundName].length > 0
                            "
                        >
                            <button
                                v-if="
                                    season_info.seasons[0].status ==
                                        roundGridFormatter(
                                            roundName,
                                            season_info.seasons[0].start_playoffs
                                        ) &&
                                    season_playoffs.playoffs[roundName].length >
                                        0 &&
                                    roundName != 'finals'
                                "
                                @click="createPlayOffSchedule(roundName)"
                                class="text-indigo-600 font-bold text-md flex bg-orange-200 shadow p-1 rounded-full hover:text-indigo-900 mt-4"
                            >
                                End
                                {{ roundNameFormatter(roundName) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div
        class="flex justify-center min-h-screen items-center border-b-2 border-dashed p-4 bg-white"
         v-if="season_info.seasons && season_info.seasons[0].status == 1 && !loading"
    >
        <div
            class="text-center bg-white p-8 rounded-lg shadow-lg border-2 border-red-500"
        >
            <p
                class="text-red-500 font-bold text-3xl md:text-4xl leading-relaxed mb-4"
            >
                Not playoffs season yet! Finish the Regular season first.
            </p>
            <p class="text-gray-700 text-lg md:text-xl">
                Please make sure all regular season games are completed before
                proceeding to playoffs.
            </p>
            <div class="mt-6">
                <a :href="route('seasons.details', { season_id: props.season_id })"
                    class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-red-300"
                >
                        Go to Regular Season
                </a>
            </div>
        </div>
    </div>
    <div class="flex justify-center items-center p-4" v-if="loading">
        <p class="text-red-500 font-bold text-2xl">Loading...</p>
    </div>
    <Modal :show="isTeamComparisonModalOpen" :maxWidth="'6xl'" title="Team Comparison" @close="isTeamComparisonModalOpen = false">
        <div class="mt-4">
            <TeamComparison
                :home_id="comparison.home_id"
                :away_id="comparison.away_id"
                :season_id="comparison.season_id"
            />
        </div>
    </Modal>
   <Modal :show="isGameResultModalOpen" :maxWidth="'fullscreen'" title="Game Results" @close="isGameResultModalOpen = false">
        <div class="mt-4">
            <GameResults :key="isGameResultModalOpen" :game_id="isGameResultModalOpen" />
        </div>
    </Modal>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import axios from "axios";
import {
    roundNameFormatter,
    roundGridFormatter,
    roundStatusFormatter,
} from "@/Utility/Formatter.js";

import TeamComparison from "@/Pages/Teams/Module/TeamComparison.vue";
import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";

const isAddModalOpen = ref(false);
const isTeamModalOpen = ref(false);
const isTeamComparisonModalOpen = ref(false);
const isGameResultModalOpen = ref(false);
const loading = ref(false);
const change_key = ref(localStorage.getItem("season-key"));
const isHide = ref(false);
const activeIndex = ref(0);
const season_info = ref(false);
const season_playoffs = ref(false);
const is_play_ins = ref(false);
const form = useForm({
    seasons_id: 0,
});
const comparison = useForm({
    season_id: 0,
    home_id: 0,
    away_id: 0,
});
const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

const compareTeams = (home_id, away_id) => {
    comparison.season_id = props.season_id;
    comparison.home_id = home_id;
    comparison.away_id = away_id;
    isTeamComparisonModalOpen.value = true;
};

const createPlayOffSchedule = async (round) => {
    try {
        let prev_round = round;
        let start_playoffs = season_info.value.seasons[0].start_playoffs;
        round = roundStatusFormatter(round, start_playoffs, is_play_ins.value);

        Swal.fire({
            title: "Simulating...",
            text: "Please wait while creating the schedule for " + roundNameFormatter(round),
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(route("create.schedule.playoff"), {
            season_id: form.seasons_id,
            round: round,
            prev_round: prev_round,
            start: start_playoffs,
        });
        isHide.value = true;
        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);
        isHide.value = false;
        isAddModalOpen.value = false;
        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });
    } catch (error) {
        console.error("Error creating playoff schedule:", error);
        Swal.close();
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to create playoff schedule.",
        });
        throw error; // Rethrow to allow caller to handle
    }
};
const createPlayOffScheduleAuto = async (round) => {
    try {
        let prev_round = round;
        let start_playoffs = season_info.value.seasons[0].start_playoffs;
        round = roundStatusFormatter(round, start_playoffs, is_play_ins.value);

        Swal.fire({
            title: "Simulating...",
            text: "Please wait while creating the schedule for " + roundNameFormatter(round),
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(route("create.schedule.playoff"), {
            season_id: form.seasons_id,
            round: round,
            prev_round: prev_round,
            start: start_playoffs,
        });

        isHide.value = true;
        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);
        isHide.value = false;
        isAddModalOpen.value = false;

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });

        // ✅ Return message so it can be checked by caller
        return response.data.message;

    } catch (error) {
        console.error("Error creating playoff schedule:", error);
        Swal.close();
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to create playoff schedule.",
        });
        throw error; // Still rethrow to let the simulation function decide
    }
};

const fetchSeasonInfo = async (id) => {
    try {
        form.seasons_id = id;
        const response = await axios.post(route("seasons.info"), {
            season_id: form.seasons_id,
        });

        season_info.value = response.data;
        is_play_ins.value = response.data.is_play_ins ? 1 : 2;
        await fetchSeasonPlayoffs(is_play_ins.value);
    } catch (error) {
        console.error("Error fetching season information:", error);
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to fetch season information.",
        });
    }
};

const fetchSeasonPlayoffs = async (type) => {
    try {
        let status = season_info.value.seasons[0].status;
        let start_playoffs = season_info.value.seasons[0].start_playoffs;
        const response = await axios.post(route("conferences.playoffs"), {
            season_id: form.seasons_id,
            type: type,
            status: status,
            start: start_playoffs,
        });

        if (type === 2) {
            if (
                typeof season_playoffs.value.playoffs !== "object" ||
                season_playoffs.value.playoffs === null
            ) {
                season_playoffs.value.playoffs = {};
            }
            season_playoffs.value.playoffs = {
                ...season_playoffs.value.playoffs,
                ...response.data.playoffs,
            };
        } else {
            loading.value = true;
            season_playoffs.value = response.data;
            loading.value = false;
        }
    } catch (error) {
        loading.value = false;
        console.error("Error fetching season playoffs:", error);
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to fetch playoff data.",
        });
    }
};

const simulateGame = async (id, game_id, type, index, round) => {
    try {
        isHide.value = true;
        activeIndex.value = index;

        Swal.fire({
            title: 'Simulating...',
            text: 'Please wait while the game is being simulated.',
            icon: 'info',
            toast: true,
            position: 'top',
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            timerProgressBar: true,
            didOpen: (toast) => {
                Swal.showLoading();
            }
        });


        const response = await axios.post(route("game.simulate.playoff"), {
            schedule_id: id,
        });

        season_playoffs.value.playoffs[round][index] = response.data.schedule;

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
            timer: 2000, // Auto-close after 2 seconds (2000ms)
            showConfirmButton: false,
            timerProgressBar: true
        });


        isGameResultModalOpen.value = game_id;
        isHide.value = false;
    } catch (error) {
        console.error("Error simulating the game:", error);
        Swal.close();
        isHide.value = false;
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to simulate game.",
        });
        throw error; // Rethrow to allow caller to handle
    }
};

const getConferenceClass = (home_conference, away_conference) => {
    const conferenceClasses = {
        NCR: "bg-blue-100 text-blue-500",
        Luzon: "bg-green-100 text-green-500",
        Visayas: "bg-yellow-100 text-yellow-500",
        Mindanao: "bg-red-100 text-red-500",
    };

    if (home_conference !== away_conference) {
        return "bg-orange-100 text-orange-500";
    }

    return conferenceClasses[home_conference] || "bg-gray-100 text-gray-500";
};

const simulateFullPlayoffs = async () => {
    try {
        isHide.value = true;
        loading.value = true;

        Swal.fire({
            title: "Simulating Playoffs...",
            text: "Please wait while the entire playoff is being simulated.",
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const roundOrder = [
            'play_ins_elims_round_1',
            'play_ins_elims_round_2',
            'play_ins_finals',
            'round_of_16',
            'quarter_finals',
            'semi_finals',
            'interconference_semi_finals',
            'finals'
        ];

        let currentRoundIndex = 0;
        let initialRound = 'start';

        // Initialize playoffs if not started
        if (season_info.value.seasons[0].status == 2) {
            await createPlayOffScheduleAuto(initialRound);
        }

        while (currentRoundIndex < roundOrder.length) {
            const roundName = roundOrder[currentRoundIndex];

            // Refresh latest data
            await fetchSeasonInfo(form.seasons_id);
            await fetchSeasonPlayoffs(is_play_ins.value);

            // Check if round has matches
            if (
                !season_playoffs.value.playoffs[roundName] ||
                season_playoffs.value.playoffs[roundName].length === 0
            ) {
                try {
                    const scheduleResponse = await createPlayOffScheduleAuto(roundName);

                    // If schedule already created, log and continue
                    if (typeof scheduleResponse === 'string' &&
                        scheduleResponse.toLowerCase().includes("already created")) {
                        console.log(`Schedule for ${roundName} already exists. Proceeding.`);
                    } else {
                        await fetchSeasonPlayoffs(is_play_ins.value);
                    }
                } catch (scheduleError) {
                    console.warn(`Failed to create schedule for ${roundName}:`, scheduleError);
                }
            }

            // Get updated matches
            const matches = season_playoffs.value.playoffs[roundName] || [];
            if (matches.length === 0) {
                currentRoundIndex++;
                continue;
            }

            // Simulate each game
            for (let index = 0; index < matches.length; index++) {
                const match = matches[index];
                if (match.winner == 0) {
                    await simulateGame(match.id, match.game_id, 2, index, roundName);
                    await fetchSeasonPlayoffs(is_play_ins.value); // Refresh after each game
                }
            }

            // Try to advance to next round if not finals
            if (roundName !== 'finals') {
                try {
                    const nextRoundResponse = await createPlayOffScheduleAuto(roundName);
                    if (typeof nextRoundResponse === 'string' &&
                        nextRoundResponse.toLowerCase().includes("already created")) {
                        console.log(`Next round after ${roundName} already scheduled.`);
                    }
                } catch (advanceError) {
                    console.warn(`Could not advance from ${roundName}:`, advanceError);
                }
            }

            currentRoundIndex++;
        }

        // Final refresh
        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);

        Swal.close();
        Swal.fire({
            icon: "success",
            title: "Playoffs Completed!",
            text: "The entire playoff simulation has finished successfully.",
        });

    } catch (error) {
        console.error("Error simulating full playoffs:", error);
        Swal.close();
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to simulate playoffs.",
        });
    } finally {
        isHide.value = false;
        loading.value = false;
    }
};


watch(
    () => props.season_id,
    async (n, o) => {
        if (n !== o) {
            await fetchSeasonInfo(n); // Use new season_id
        }
    }
);

onMounted(() => {
    fetchSeasonInfo(props.season_id);
});
</script>
```