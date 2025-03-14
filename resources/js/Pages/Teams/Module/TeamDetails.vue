<template>
    <div class="p-0 m-0">
        <button
            @click.prevent="showTeamDetails()"
            v-if="props.showButton == 1"
            :class="{
                'opacity-25': isTeamModalOpen,
            }"
            :disabled="isTeamModalOpen"
            class="px-2 py-2 bg-blue-500 font-bold text-md float-center text-white shadow"
        >
            <i class="fa fa-eye"></i> {{ props.text?? 'View' }}
        </button>
        <b v-if="props.showButton == 0" class="hover:text-blue-500" @click.prevent="showTeamDetails()">
            {{ props.text == 'null' ? 'TBD' : props.text }}
            <sup v-if="props.showInfo" class="space-x-1">
               <!-- Defending Conference Champion (CC) -->
                <i v-if="data.is_conference_champion" class="fas fa-trophy text-blue-500" title="Defending Conference Champion"></i>

                <!-- Defending Overall Champion (OC) -->
                <i v-if="data.is_defending_champion" class="fas fa-crown text-yellow-500" title="Defending Overall Champion"></i>

                <!-- Defending National Champion (NC) -->
                <i v-if="data.is_finals_champion" class="fas fa-globe-americas text-green-500" title="Defending National Champion"></i>

                <!-- Last Season Finalist (NF) -->
                <i v-if="data.is_finalist" class="fas fa-star text-purple-500" title="Last Season Finalist"></i>

                <!-- Finals MVP Count -->
                <b v-if="data.finals_mvp_count > 0" class="text-white p-1 rounded-full text-xs bg-orange-500" title="# of Finals MVP in A Roster Count">{{ data.finals_mvp_count }}</b>

                <!-- Regular Season MVP Count -->
                <b v-if="data.overall_mvp_count > 0" class="text-white p-1 rounded-full text-xs bg-green-500" title="# of Best Player of the Season in A Roster Count">{{ data.overall_mvp_count }}</b>

                <!-- Regular Season MVP Count -->
                <b v-if="data.dpos_count > 0" class="text-white p-1 rounded-full text-xs bg-purple-500" title="# of Defensive Player of the Season in A Roster Count">{{ data.dpos_count }}</b>

                 <!-- # of Finals MVP in A Roster Count -->
                 <b v-if="data.ros_count > 0" class="text-white p-1 rounded-full text-xs bg-red-500" title="# of Rookie of The Season in A Roster Count">{{ data.ros_count }}</b>

                <!-- Overall Rank and Arrow Comparison -->
                <b v-if="data.prev_conference_rank && props.current_conference_rank > 0" :class="{
                    'text-green-500': props.current_conference_rank < data.prev_conference_rank, 
                    'text-red-500': props.current_conference_rank > data.prev_conference_rank, 
                    'text-white': props.current_conference_rank === data.prev_conference_rank
                }" title="Overall Rank Comparison" class="text-xs">
                    {{ data.prev_conference_rank - props.current_conference_rank }}
                    <span v-if="props.current_conference_rank < data.prev_conference_rank" class="text-green-500">
                        <i class="fa fa-arrow-up"></i>
                    </span>
                    <span v-if="props.current_conference_rank > data.prev_conference_rank" class="text-red-500">
                        <i class="fa fa-arrow-down"></i>
                    </span>
                    <span v-if="props.current_conference_rank === data.prev_conference_rank" class="text-white">
                        <i class="fa fa-minus"></i>
                    </span>

                </b>
            </sup>
        </b>
        <Modal :show="isTeamModalOpen" :maxWidth="'fullscreen'" title="Team Information" @close="isTeamModalOpen = false">
            <div 
                class="flex justify-start" 
                :style="{ backgroundColor: '#' + team_info.teams.primary_color, color: 'white' }"
                v-if="team_info?.teams"
            >
                <button
                    :class="['px-4 py-2', currentTab === 'info' ? 'text-white' : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'info' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'info'"
                >
                    <i class="fas fa-info-circle mr-1"></i> Team Info
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'history' ? 'text-white' : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'history' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'history'"
                >
                    <i class="fas fa-history mr-1"></i> Team History
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'roster' ? ` text-white` : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'roster' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'roster'"
                >
                    <i class="fas fa-users-cog mr-1"></i> Team Roster
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'transactions' ? ` text-white` : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'transactions' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'transactions'"
                >
                    <i class="fas fa-exchange-alt mr-1"></i> Team Transactions
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'stars' ? ` text-white` : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'stars' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'stars'"
                >
                    <i class="fas fa-star mr-1"></i> Team Stars
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'timeline' ? ` text-white` : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'timeline' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'timeline'"
                >
                    <i class="fas fa-clock mr-1"></i> Season Timeline
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'legend' ? ` text-white` : 'text-white hover:text-gray-700']"
                    :style="currentTab === 'legend' ? { backgroundColor: '#' + team_info.teams.secondary_color } : {}"
                    @click="currentTab = 'legend'"
                >
                    <i class="fas fa-trophy mr-1"></i> Top 15 Players
                </button>

            </div>

            <div class="mt-0">
                <TeamInfo :key="props.team_id" v-if="currentTab === 'info'" :team_id="props.team_id" />
                <TeamHistory :key="props.team_id"  v-if="currentTab === 'history'" :team_id="props.team_id" />
                <TeamRoster :key="props.team_id" v-if="currentTab === 'roster'" :team_id="props.team_id" />
                <TeamTransactions :key="props.team_id" v-if="currentTab === 'transactions'" :team_id="props.team_id" />
                <Top15Player :key="props.team_id" v-if="currentTab === 'legend'" :team_id="props.team_id" />
                <Stars :key="props.team_id" v-if="currentTab === 'stars'" :team_id="props.team_id" />
                <SeasonTimeLine :key="props.team_id" v-if="currentTab === 'timeline'" :teamId="props.team_id" />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import { ref, onMounted } from "vue";
import axios from 'axios';
import TeamInfo from "./TeamInfo.vue";
import TeamHistory from "./TeamHistory.vue";
import TeamRoster from "./TeamRoster.vue";
import Top15Player from "./Top15Player.vue";
import SeasonTimeLine from "@/Pages/Analytics/Module/SeasonTimeLine.vue";
import TeamTransactions from "./TeamTransactions.vue";
import Stars from "./Stars.vue";

const isTeamModalOpen = ref(false);
const currentTab  = ref('info');
const team_info = ref(false);
const loading = ref(false);
const data = ref({
    is_conference_champion: false,
    is_defending_champion: false,
    is_finals_champion: false,
    is_finalist: false,
    finals_mvp_count: 0,
    team_one_pick_count: 0,
    current_conference_rank: 0,
});

const props = defineProps({
    team_id: Number,
    showButton: Number,
    season_id: {
        type: Number,
        default: 0,
    },
    current_conference_rank:  {
        type: Number,
        default: 0,
    },
    text: String,
    showInfo: {
        type: Boolean,
        default: false, // Default to false if not explicitly set
    },
});

onMounted(() => {
    if (props.showInfo) {
        fetchTeamRecentPerFormance(); // Only fetch data if showInfo is true
    }
});

const showTeamDetails = async () => {
    await fetchTeamInfo();
    isTeamModalOpen.value = true;
}
const fetchTeamInfo = async () => {
    try {
        loading.value = true;
        team_info.value = false;
        const response = await axios.post(route("teams.info"), {
            team_id: props.team_id,
        });
        team_info.value = response.data;
        loading.value = false;
    } catch (error) {
        loading.value = false;
        console.error("Error fetching team info:", error);
    }
};

const fetchTeamRecentPerFormance = async () => {
    try {
        const response = await axios.post(route("team.recent.performance"), {
            team_id: props.team_id,
            season_id: props.season_id,
        });

        // Check if the response data is valid
        if (response.data) {
            data.value = response.data;
        } else {
            console.error("Received invalid data", response.data);
        }
    } catch (error) {
        console.error("Error fetching home team info:", error);
    }
};

</script>
