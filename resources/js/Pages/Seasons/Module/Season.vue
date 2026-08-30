<template>
    <!-- this div will be at the bottom the top div will create an ilussion of overlay make the standings and top10 players card float at the center-->
    <div>
        <div
            class="w-full flex overflow-x-auto border-b-2 bg-black rounded"
        >
            <ul class="flex flex-wrap">
                <li
                    v-for="conference in season_info.conferences"
                    :key="conference.id"
                    @click.prevent="fetchConferenceData(conference.id)"
                    :class="
                        activeConferenceTab == conference.id
                            ? 'animate-pulse font-bold text-orange-500'
                            : ''
                    "
                    class="whitespace-nowrap group flex items-center px-3 py-2 cursor-pointer relative flex-shrink-0 max-w-xs"
                >
                    <i
                        :class="
                            activeConferenceTab == conference.id
                                ? 'text-orange-500'
                                : 'text-gray-500'
                        "
                        class="fa fa-shield mr-2"
                        :title="conference.name + ' Conference'"
                    ></i>
                    <span
                        class="text-truncate text-white"
                        >{{ conference.name }}
                        {{
                            conference.champions_count > 0
                                ? "( " + conference.champions_count + " )"
                                : ""
                        }}</span
                    >
                    <!-- Warning Badge Notification Counter -->
                </li>
            </ul>
        </div>
        <div
            class="grid grid-cols-1 md:grid-cols-7 gap-6 p-2 bg-black rounded"
            v-if="season_info.seasons && season_info.seasons[0].type != 1"
        >
            <!-- Standings UI (Left Side) -->
            <div class="md:col-span-3 sm:col-span-1 overflow-y-auto p-2 bg-black">
                <Standings v-if="selectedConference != 0" :key="updateKey" :showLegend="false" :season_id="props.season_id" :conference_id="activeConferenceTab" :season_data="season_info.seasons" />
                <Top15MVPCandidate v-else :key="updateKey" :current_round="updateKey"/>

                <small class="text-white mt-3">Transaction ID:{{ updateKey }} || Conference ID: {{ selectedConference }} || Round {{ currentRound ?? 0 }} || Transaction Update: {{ transactionUpdate }}</small>   
                <!-- <Top15MVPCandidate v-if="activeConferenceTab" :key="updateKey" /> -->
                <RecentTransactions v-if="seasonStatus < 3" :key="transactionUpdate"/>
                <!-- <Top15MVPCandidate v-if="seasonStatus == 2 && (currentRound % 2 == 0)" :key="currentRound" :current_round="currentRound"/> -->
            </div>
            <!-- Schedule and Results UI (Right Side) -->
            <div class="md:col-span-4 sm:col-span-1 overflow-y-auto pt-0 bg-black">
                <SeasonSchedule v-if="season_info" 
                @transaction_id="(id) => handleTransaction(id)" 
                @season_status="(status) => handleSeasonStatus(status)" 
                @transaction_update="(transaction_change) => handleTransactionUpdate(transaction_change)" 
                @round="(round) => handleCurrentRound(round)" 
                :season_id="props.season_id"
                :key="selectedConference"
                :conference_id="activeConferenceTab" 
                :simulate_next="isAutoSimulate"
                :season_data="season_info" />
                <br>
                <Top15MVPCandidate v-if="seasonStatus < 3 && (currentRound % 3 == 0) && currentRound != 0" :key="currentRound" :current_round="currentRound"/>
                <!-- <RecentNews v-if="seasonStatus < 3 && (currentRound % 2 != 0)" :key="updateKey" :season_status="seasonStatus" :season_id="props.season_id"/> -->
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Standings from "./Standings.vue";
import SeasonSchedule from "./SeasonSchedule.vue";
import Top15MVPCandidate from "@/Pages/Seasons/Module/Top15MVPCandidate.vue";
import RecentTransactions from "@/Pages/Seasons/Module/RecentTransactions.vue";
import RecentNews from "@/Pages/Seasons/Module/RecentNews.vue";

const season_info = ref(false);
const seasonStatus = ref(false);
const activeConferenceTab = ref(false);
const currentRound = ref(0);
const isAutoSimulate = ref(false);
const updateKey = ref(0);
const selectedConference = ref(1);
const transactionUpdate = ref(false);
const props = defineProps({
    season_id: {
        type: [Number,String],
        required: true,
    },
});
const fetchConferenceData = async (id) => {
    // await fetchSeasonInfo();
    activeConferenceTab.value = id;
    updateKey.value = id;
    selectedConference.value = id;
}
const fetchSeasonInfo = async () => {
    try {
        const response = await axios.post(route("seasons.info"), {
            season_id: props.season_id,
        });
        season_info.value = response.data;
        activeConferenceTab.value = season_info.value.conferences[0].id;
        updateKey.value = season_info.value.conferences[0].id;
    } catch (error) {
        console.error("Error fetching season information:", error);
    }
};
const handleTransaction = (id) => {
    console.log('Conference ID: '+id);
    updateKey.value = id+':'+(Math.random());
    activeConferenceTab.value = id;
}

const handleSeasonStatus = (status) => {
    console.log('Season Status: '+status);
    seasonStatus.value = status;
}

const handleCurrentRound = (round) => {
    console.log('Active Round: '+round);
    currentRound.value = round;
}

const handleTransactionUpdate = (transaction_change) => {

    let lastCount = localStorage.getItem('transaction_change');
    let hasChanged = (lastCount > transaction_change);

    console.log('Transaction Updated: '+transaction_change+' Last Change: '+lastCount);

    localStorage.setItem('transaction_change',transaction_change);

    transactionUpdate.value = hasChanged;

    console.log('Transaction Update: '+hasChanged);
}

//team modal
onMounted(() => {
    fetchSeasonInfo();
});
</script>
