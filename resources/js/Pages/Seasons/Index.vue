<template>
    <div>
        <Head title="Seasons" />

        <AuthenticatedLayout>
            <template #header> Seasons </template>
            <div
                class="inline-block min-w-full bg-white overflow-auto shadow rounded p-2"
            >
                <div class="flex items-center overflow-hidden gap-5 p-2">
                    <input
                        type="text"
                        v-model="search_seasons.search"
                        @input.prevent="fetchSeasons(1)"
                        id="LeagueName"
                        placeholder="Enter season name"
                        class="mt-1 p-2 text-md shadow border rounded-md w-full"
                    />
                    <div class="flex space-x-2 items-center text-nowrap">
                        <button
                            @click.prevent="isPlayerAwardsModalOpen = true"
                            v-if="seasons.is_new_season == 1"
                            v-bind:class="{
                                'opacity-25': isPlayerAwardsModalOpen,
                            }"
                            v-bind:disabled="isPlayerAwardsModalOpen"
                            class="px-2 py-2 bg-yellow-800 rounded font-bold text-md float-end text-white shadow"
                        >
                            <i class="fa fa-trophy"></i> Season Awards
                        </button>
                        <button
                            @click.prevent="updatePlayerStatus()"
                            v-if="seasons.is_new_season == 2"
                            class="px-2 py-2 bg-blue-500 rounded font-bold text-md float-end text-white shadow"
                        >
                            <i class="fa fa-users"></i> Update Player Status
                        </button>
                        <button
                            @click.prevent="isDraftModalOpen = true"
                            v-if="seasons.is_new_season == 3"
                            v-bind:class="{
                                'opacity-25': isDraftModalOpen,
                            }"
                            v-bind:disabled="isDraftModalOpen"
                            class="px-2 py-2 bg-yellow-500 rounded font-bold text-md float-end text-white shadow"
                        >
                            <i class="fa fa-user-plus"></i> Rookie Draft
                        </button>
                        <button
                            @click.prevent="isTradeModalOpen = true"
                            v-if="seasons.is_new_season == 4"
                            v-bind:class="{
                                'opacity-25': isTradeModalOpen,
                            }"
                            v-bind:disabled="isTradeModalOpen"
                            class="px-2 py-2 bg-orange-500 rounded font-bold text-md float-end text-white shadow"
                        >
                            <i class="fa fa-sync"></i> Trade Season
                        </button>
                        <button
                            @click.prevent="isPlayerSigningModalOpen = true"
                            v-if="seasons.is_new_season == 5 || seasons.is_new_season == 8"
                            v-bind:class="{
                                'opacity-25': isPlayerSigningModalOpen,
                            }"
                            v-bind:disabled="isPlayerSigningModalOpen"
                            class="px-2 py-2 bg-red-500 rounded font-bold text-md float-end text-white shadow"
                        >
                            <i class="fa fa-users"></i> Player Signings
                        </button>
                        <button
                            @click.prevent="isCoachSigningModalOpen = true"
                            v-if="seasons.is_new_season == 6 || seasons.is_new_season == 8"
                            v-bind:class="{
                                'opacity-25': isCoachSigningModalOpen,
                            }"
                            v-bind:disabled="isCoachSigningModalOpen"
                            class="px-2 py-2 bg-red-700 rounded font-bold text-md float-end text-white shadow"
                        >
                            <i class="fa fa-chalkboard-teacher"></i> Coach Signings
                        </button>
                        <Add v-if="seasons.is_new_season == 7 || seasons.is_new_season == 8" @transaction_id="handleCreateSeason" />
                    </div>
                </div>
                <div class="flex overflow-hidden gap-5 p-2">
                    <table
                        class="w-full whitespace-no-wrap overflow-x-auto border border-gray-200"
                    >
                        <thead>
                            <tr
                                class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Season
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Finals MVP
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Finals Champion
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Finals Runner Up
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Regular Champion
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    NCR
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Luzon
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Visayas
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Mindanao
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 bg-gray-100 px-1 py-1 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Worst
                                </th>
                                <th
                                    class="border-b-2 border-gray-200 text-center bg-gray-100 px-1 py-1 text-xs font-semibold uppercase tracking-wider text-gray-600"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(season, index) in seasons.seasons"
                                v-if="seasons.total_pages"
                                :key="season.id"
                                :class="[
                                    season.finals_winner_id === season.champion_id
                                        ? 'bg-stone-700 text-yellow-400 font-extrabold'
                                        : '',
                                    season.winner_conference_name ===
                                    season.loser_conference_name
                                        ? 'bg-slate-600 text-yellow-500 font-extrabold'
                                        : '',
                                    index > 0 &&
                                    seasons.seasons[index - 1].champion_name ===
                                        season.champion_name
                                        ? 'bg-green-200'
                                        : '',
                                    'border border-gray-200',
                                ]"
                            >
                                <td class="border px-1 py-1 text-xs text-nowrap">
                                    <p class="whitespace-no-wrap uppercase">
                                        {{ season.name }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap"
                                >
                                    <p>{{ season.finals_mvp ?? "TBD" }}</p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap"
                                >
                                    <p class="font-extrabold text-yellow-500">
                                        {{ season.finals_winner_name ?? "TBD" }} ({{
                                            season.finals_winner_score >
                                            season.finals_loser_score
                                                ? season.finals_winner_score
                                                : season.finals_loser_score
                                        }})
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap"
                                >
                                    <p>
                                        {{ season.finals_loser_name ?? "TBD" }} ({{
                                            season.finals_winner_score <
                                            season.finals_loser_score
                                                ? season.finals_winner_score
                                                : season.finals_loser_score
                                        }})
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap"
                                >
                                    <p>
                                        {{
                                            season.type == 1
                                                ? "n/a"
                                                : season.champion_name
                                        }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap bg-red-100"
                                >
                                    <p
                                        :class="
                                            season.finals_winner_id ==
                                            season.west_champion_id
                                                ? 'font-bold text-red-500'
                                                : ''
                                        "
                                    >
                                        {{ season.west_champion_name ?? "TBD" }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap bg-blue-100"
                                >
                                    <p
                                        :class="
                                            season.finals_winner_id ==
                                            season.east_champion_id
                                                ? 'font-bold text-blue-500'
                                                : ''
                                        "
                                    >
                                        {{ season.east_champion_name ?? "TBD" }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap bg-green-100"
                                >
                                    <p
                                        :class="
                                            season.finals_winner_id ==
                                            season.north_champion_id
                                                ? 'font-bold text-green-500'
                                                : ''
                                        "
                                    >
                                        {{ season.north_champion_name ?? "TBD" }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap bg-yellow-100"
                                >
                                    <p
                                        :class="
                                            season.finals_winner_id ==
                                            season.south_champion_id
                                                ? 'font-bold text-yellow-500'
                                                : ''
                                        "
                                    >
                                        {{ season.south_champion_name ?? "TBD" }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-1 text-xs text-nowrap"
                                >
                                    <p>
                                        {{
                                            season.type == 1
                                                ? "n/a"
                                                : season.weakest_name
                                        }}
                                    </p>
                                </td>
                                <td
                                    class="border border-gray-200 px-1 py-4 text-center text-xs text-nowrap"
                                >
                                    <div class="flex justify-center items-center">
                                        <a :href="route('seasons.details', { season_id: season.id })" class="px-2 py-2 bg-blue-500 rounded-l font-bold text-md text-white shadow">
                                            <i class="fa fa-list"></i>
                                            Season
                                        </a>
                                        <button type="button" @click.prevent="isSeasonAwardsModalOpen = season.id" class="px-2 py-2 bg-yellow-500 font-bold text-md text-white shadow">
                                            <i class="fa fa-medal"></i>
                                            Awards
                                        </button>
                                        <button  :disabled="season.status <= 12" :class="season.status <= 12 ? 'opacity-50' : ''" type="button" @click.prevent="isSeasonStoryLineModalOpen = season.id" class="px-2 py-2 bg-blue-500 font-bold text-md text-white shadow">
                                            <i class="fa fa-image"></i>
                                            Storyline
                                        </button>
                                        <button :disabled="season.id == 1" :class="season.id == 1 ? 'opacity-50' : ''" type="button" @click.prevent="isSeasonDraftModalOpen = season.id" class="px-2 py-2 bg-pink-500 rounded-r font-bold text-md text-white shadow">
                                            <i class="fa fa-users"></i>
                                            Draft
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else>
                                <td
                                    colspan="11"
                                    class="border-b text-center font-bold text-sm border-gray-200 bg-white px-2 py-1"
                                >
                                    <p class="text-red-500 whitespace-no-wrap">
                                        No Data Found!
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex w-full overflow-auto">
                    <Paginator
                        v-if="seasons.total_count"
                        :page_number="search_seasons.page_num"
                        :total_rows="seasons.total_count ?? 0"
                        :itemsperpage="search_seasons.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
            <Modal :show="isPlayerSigningModalOpen" :maxWidth="'6xl'" title="Free Agent Signings" @close="isPlayerSigningModalOpen = false">
                <div class="p-3 block">
                    <FreeAgents @newSeason="handleNewSeason" />
                </div>
            </Modal>
            <Modal :show="isDraftModalOpen" :maxWidth="'6xl'" title="Draft Rookie Players" @close="isDraftModalOpen = false">
                <div class="p-3 block">
                    <Draft @newSeason="handleNewSeason" />
                </div>
            </Modal>
            <Modal :show="isPlayerAwardsModalOpen" :maxWidth="'6xl'" title="Season Awards" @close="closeAwardsModal()">
                <div class="p-3 block">
                    <Awards
                        @newSeason="handleNewSeason"
                        :team_ids="seasons.team_ids"
                    />
                </div>
            </Modal>
            <Modal :show="isTradeModalOpen" :maxWidth="'fullscreen'" title="Trade Plyers" @close="isTradeModalOpen = false">
                <div class="mt-4 p-3 block">
                    <Trade
                        @newSeason="handleTradeSeason"
                    />
                </div>
            </Modal>
            <Modal :show="isSeasonAwardsModalOpen" :maxWidth="'fullscreen'" :title="`Season ${isSeasonAwardsModalOpen} Awards`" @close="isSeasonAwardsModalOpen = false">
                <div class="mt-4 p-3 block">
                    <SeasonAwards :key="isSeasonAwardsModalOpen" :season_id="isSeasonAwardsModalOpen" @newSeason="handleTradeSeason" />
                </div>
            </Modal>
             <Modal :show="isSeasonDraftModalOpen" :maxWidth="'fullscreen'" :title="`Season ${isSeasonDraftModalOpen} Draft Results`" @close="isSeasonDraftModalOpen = false">
                <div class="mt-4 p-3 block">
                    <DraftBoard :key="isSeasonDraftModalOpen" :season_id="isSeasonDraftModalOpen" @newSeason="handleTradeSeason" />
                </div>
            </Modal>
            <Modal :show="isCoachSigningModalOpen" :maxWidth="'fullscreen'" :title="`Season ${isCoachSigningModalOpen} Coach Signing`" @close="isCoachSigningModalOpen = false">
                <div class="mt-4 p-3 block">
                    <FreeAgentsCoach :showControls="true" :key="isCoachSigningModalOpen" @newSeason="handleTradeSeason"  />
                </div>
            </Modal>
            <Modal :show="isSeasonStoryLineModalOpen" :maxWidth="'4xl'" :title="`Season ${isSeasonStoryLineModalOpen} Storyline`" @close="isSeasonStoryLineModalOpen = false">
                <div class="mt-4 p-3 block">
                    <StoryLine :key="isSeasonStoryLineModalOpen" :season_id="isSeasonStoryLineModalOpen" @newSeason="handleTradeSeason"  />
                </div>
            </Modal>
           
             
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import NavLink from '@/Components/NavLink.vue';
import Paginator from "@/Components/Paginator.vue";
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Trade from "@/Pages/Seasons/Module/Trade.vue";
import FreeAgents from "@/Pages/Seasons/Module/FreeAgents.vue";
import FreeAgentsCoach from "@/Pages/Coaches/Module/FreeAgentsCoach.vue";
import SeasonAwards from "@/Pages/Seasons/Module/SeasonAwards.vue";
import Awards from "@/Pages/Seasons/Module/Awards.vue";
import Draft from "@/Pages/Seasons/Module/Draft.vue";
import DraftBoard from "@/Pages/Seasons/Module/DraftBoard.vue"
import Add from "@/Pages/Seasons/Module/Add.vue";
import StoryLine from "@/Pages/Seasons/Module/Storyline.vue";

const isAddModalOpen = ref(false);
const isTradeModalOpen = ref(false);
const isPlayerSigningModalOpen = ref(false);
const isDraftModalOpen = ref(false);
const isPlayerAwardsModalOpen = ref(false);
const isSeasonAwardsModalOpen = ref(false);
const isSeasonDraftModalOpen = ref(false);
const isCoachSigningModalOpen = ref(false);
const isSeasonStoryLineModalOpen = ref(false);
const seasons = ref([]);
const leagues_dropdown = ref([]);
const season_id = ref(0);
const isProcessing = ref(false);
const currentTab = ref("Regular"); // Set the default tab
const search_seasons = ref({
    current_page: 1,
    total_pages: 0,
    total: 0,
    search: "",
    change_key: "",
    itemsperpage: 10,
});
const form = useForm({
    type: 3,
    start: 16,
    league_id: 1,
    seasons_id: 0,
    conference_id: 0,
    match_type: 1,
    errors: [],
});
const fetchSeasons = async (page = 1) => {
    try {
        search_seasons.value.current_page = page;
        const response = await axios.post(
            route("seasons.list"),
            search_seasons.value
        );
        seasons.value = response.data;
    } catch (error) {
        console.error("Error fetching seasons:", error);
    }
};
const handlePagination = (page_num) => {
    search_seasons.value.page_num = page_num ?? 1;
    fetchSeasons();
};

const handleTradeSeason = (newSeason) => {
    fetchSeasons();
};
const handleCreateSeason = (newSeason) => {
    fetchSeasons();
};
const handleNewSeason = (newSeason) => {
    if (newSeason) {
        isPlayerSigningModalOpen.value = false;
    }
    fetchSeasons();
};
const leagueDropdown = async () => {
    try {
        const response = await axios.get(route("leagues.dropdown"));
        if (!response) {
            throw new Error("Failed to fetch leagues");
        }
        leagues_dropdown.value = await response.data; // Parse JSON response and assign it to the leagues array
    } catch (error) {
        console.error("Error fetching leagues:", error);
    }
};

const prepareCurrentSeasonStats = async () => {
    try {
        const team_ids = seasons.value.team_ids;

        for (let i = 0; i < team_ids.length; i++) {
            const team_id = team_ids[i];
            const is_last = i === team_ids.length - 1;

            // Update player status for each team and get the response
            await updatePlayerStatsPerTeam(i, team_id,team_ids.length);
        }
    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to update player status for some or all teams. Please try again later.',
        });
    }
}
const updatePlayerStatsPerTeam = async (index, team_id, team_count) => {
    try {
        // const total_teams = team_count + 1;

        // Show the initial Swal with a progress bar
        Swal.fire({
            title: 'Preparing Season Stats',
            html: `<div id="progress-container">
                    <p>Processing team ${team_id}/${team_count}</p>
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar" role="progressbar" style="width: ${((index / team_count) * 100)}%;" aria-valuenow="${index}" aria-valuemin="0" aria-valuemax="${team_count}"></div>
                    </div>
                   </div>`,
            showConfirmButton: true,
            allowOutsideClick: false,
            position: 'top',
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Make the request
        const response = await axios.post(route('store.player.stats'), { team_id: team_id });

        // Update progress bar after response
        const progressPercentage = ((index / team_count) * 100);
        document.getElementById('progress-bar').style.width = `${progressPercentage}%`;

        // After completion of all teams, show a success message
        if (index === team_count) {
            Swal.fire({
                title: 'Success!',
                text: 'All player stats updated successfully.',
                icon: 'success',
                showConfirmButton: true,
            });
        }

    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to update player stats. Please try again later.',
        });
    }
};
const updatePlayerStatus = async () => {
    try {
        const team_ids = seasons.value.team_ids;
        console.log(team_ids);

        for (let i = 0; i < team_ids.length; i++) {
            const team_id = team_ids[i];
            const is_last = i === team_ids.length - 1;

            // Update player status for each team and get the response
            await updatePlayerStatusPerTeam(i, team_id, is_last);
        }
        await fetchSeasons(); // Refresh seasons after each team update
    } catch (error) {
        console.error(error);

        // Show error message using Swal2 if there's an error in the loop
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to update player status for some or all teams. Please try again later.",
        });
    }
};

const updatePlayerStatusPerTeam = async (index, team_id, is_last) => {
    try {
        // isProcessing.value = true;

        form.is_last = is_last;
        form.team_id = team_id;

        // Call the update function
        const response = await axios.post(route("update.player.status"), form);

        // Extract improved, declined, and re-signed players from the response
        const improvedPlayers = response.data.improved_players || [];
        const declinedPlayers = response.data.declined_players || [];
        const reSignedPlayers = response.data.re_signed_players || [];
        const teamName = response.data.team_name || "none";

        // Build the HTML message for Swal
        let htmlMessage = `
            <p style="font-size: 12px;">Player status for team #${
                index + 1
            } ${teamName} has been updated.</p>
            <div style="display: flex; flex-direction: column; align-items: center;">

                <!-- Improved Players Table -->
                <table style="width:90%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px;">
                    <thead>
                        <tr><th colspan="3" style="text-align: center; padding: 4px;">Improved Players</th></tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 4px;">Player</th>
                            <th style="border: 1px solid #ddd; padding: 4px;">Role</th>
                            <th style="border: 1px solid #ddd; padding: 4px;">Contract Years</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${improvedPlayers
                            .map(
                                (player) => `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.name}</td>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.role}</td>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.contract_years} years</td>
                            </tr>
                        `
                            )
                            .join("")}
                    </tbody>
                </table>

                <!-- Declined Players Table -->
                <table style="width:90%; border-collapse: collapse; font-size: 10px; margin-bottom: 10px;">
                    <thead>
                        <tr><th colspan="3" style="text-align: center; padding: 4px;">Declined Players</th></tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 4px;">Player</th>
                            <th style="border: 1px solid #ddd; padding: 4px;">Role</th>
                            <th style="border: 1px solid #ddd; padding: 4px;">Contract Years</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${declinedPlayers
                            .map(
                                (player) => `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.name}</td>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.role}</td>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.contract_years} years</td>
                            </tr>
                        `
                            )
                            .join("")}
                    </tbody>
                </table>

                <!-- Re-Signed Players Table -->
                 <table style="width:90%; border-collapse: collapse; font-size: 10px;">
                    <thead>
                        <tr><th colspan="3" style="text-align: center; padding: 4px;">Re-Signed Players</th></tr>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 4px;">Player</th>
                            <th style="border: 1px solid #ddd; padding: 4px;">Role</th>
                            <th style="border: 1px solid #ddd; padding: 4px;">Contract Years</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${reSignedPlayers
                            .map(
                                (player) => `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.name}</td>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.role}</td>
                                <td style="border: 1px solid #ddd; padding: 4px;">${player.contract_years} years</td>
                            </tr>
                        `
                            )
                            .join("")}
                    </tbody>
                </table>
            </div>
        `;

        // Show success alert with the table-like message
        Swal.fire({
            title: `Team ${teamName} Player Update`,
            html: htmlMessage,
            showConfirmButton: true,
            position: "top", // Position the alert at the top of the screen
        });

        // Close the processing status alert

        // isAddModalOpen.value = false;
        // isProcessing.value = false;
        // Return the response to the main function
        return response;
    } catch (error) {
        console.error(error);

        // Close the processing status alert if there's an error
        // Swal.close();

        // Show error message using Swal2
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: `Failed to update player status for team ID ${team_id}. Please try again later.`,
        });

        // isProcessing.value = false;
    }
};
const seasonsDropdown = async () => {
    try {
        const response = await axios.post(route("seasons.dropdown"), {
            season_id: 0,
        });
        localStorage.setItem("seasons", JSON.stringify(response.data));
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};
const changeTab = (tab) => {
    currentTab.value = tab;
};

const closeAwardsModal = async () => {
    await fetchSeasons();
    isPlayerAwardsModalOpen.value = false;
}
onMounted(() => {
    fetchSeasons();
    leagueDropdown();
});
</script>
