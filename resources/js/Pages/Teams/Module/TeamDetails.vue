<template>
    <div class="p-0 m-0">
        <button
            @click.prevent="isTeamModalOpen = true"
            v-if="props.showButton == 1"
            v-bind:class="{
                'opacity-25': isTeamModalOpen,
            }"
            v-bind:disabled="isTeamModalOpen"
            class="px-2 py-2 bg-blue-500 font-bold text-md float-center text-white shadow"
        >
            <i class="fa fa-eye"></i> {{ props.text?? 'View' }}
        </button>
        <b v-if="props.showButton == 0" class="hover:text-blue-500" @click.prevent="isTeamModalOpen = true">
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
                <b v-if="data.finals_mvp_count" class="text-white p-1 rounded-full text-xs bg-orange-500" title="# of Finals MVP in A Season Count">{{ data.finals_mvp_count }}</b>

                <!-- #1 Pick in A Season Count -->
                <b v-if="data.team_one_pick_count" class="text-white p-1 rounded-full text-xs bg-red-500" title="#1 Pick in A Season Count">{{ data.team_one_pick_count }}</b>

                <!-- Overall Rank and Arrow Comparison -->
                <b v-if="data.prev_conference_rank && props.current_conference_rank > 0" :class="{
                    'text-green-500': props.current_conference_rank < data.prev_conference_rank, 
                    'text-red-500': props.current_conference_rank > data.prev_conference_rank, 
                    'text-gray-500': props.current_conference_rank === data.prev_conference_rank
                }" title="Overall Rank Comparison">
               
                <span v-if="props.current_conference_rank < data.prev_conference_rank" class="text-green-500">
                    <i class="fa fa-arrow-up"></i>
                </span>
                <span v-if="props.current_conference_rank > data.prev_conference_rank" class="text-red-500">
                    <i class="fa fa-arrow-down"></i>
                </span>
                <span v-if="props.current_conference_rank === data.prev_conference_rank" class="text-gray-500">
                    <i class="fa fa-minus"></i>
                </span>

                </b>
            </sup>
        </b>
        <Modal :show="isTeamModalOpen" :maxWidth="'fullscreen'">
            <button
                class="flex float-end bg-gray-100 p-3"
                @click.prevent="isTeamModalOpen = false"
            >
                <i class="fa fa-times text-black-600"></i>
            </button>
            <div class="flex justify-start mt-5 border-b border-gray-200">
                <button
                    :class="['px-4 py-2', currentTab === 'info' ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700']"
                    @click="currentTab = 'info'"
                >
                    Team Info
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'history' ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700']"
                    @click="currentTab = 'history'"
                >
                    Team Season History
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'roster' ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700']"
                    @click="currentTab = 'roster'"
                >
                    Team Roster
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'transactions' ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700']"
                    @click="currentTab = 'transactions'"
                >
                    Team Transactions
                </button>
                <button
                    :class="['px-4 py-2', currentTab === 'timeline' ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700']"
                    @click="currentTab = 'timeline'"
                >
                    Season Timeline
                </button>
                <button
                :class="['px-4 py-2', currentTab === 'legend' ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-500 hover:text-gray-700']"
                @click="currentTab = 'legend'"
            >
                Top 15 Players
            </button>
            </div>
            <div class="mt-4">
                <TeamInfo :key="props.team_id" v-if="currentTab === 'info'" :team_id="props.team_id" />
                <TeamHistory :key="props.team_id"  v-if="currentTab === 'history'" :team_id="props.team_id" />
                <TeamRoster :key="props.team_id" v-if="currentTab === 'roster'" :team_id="props.team_id" />
                <TeamTransactions :key="props.team_id" v-if="currentTab === 'transactions'" :team_id="props.team_id" />
                <Top10Player :key="props.team_id" v-if="currentTab === 'legend'" :team_id="props.team_id" />
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
import Top10Player from "./Top10Player.vue";
import SeasonTimeLine from "@/Pages/Analytics/Module/SeasonTimeLine.vue";
import TeamTransactions from "./TeamTransactions.vue";

const isTeamModalOpen = ref(false);
const currentTab  = ref('info');
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
        fetchTeamInfo(); // Only fetch data if showInfo is true
    }
});

const fetchTeamInfo = async () => {
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
