<template>
    <!-- this div will be at the bottom the top div will create an ilussion of overlay make the standings and top10 players card float at the center-->
    <div>
        <div
            class="w-full flex overflow-x-auto border-b-2"
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
                        class="text-truncate"
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
            class="grid grid-cols-1 md:grid-cols-5 gap-6 p-0"
            v-if="season_info.seasons && season_info.seasons[0].type != 1"
        >
            <!-- Standings UI (Left Side) -->
            <div class="md:col-span-2 sm:col-span-1 overflow-y-auto">
                <Standings v-if="updateKey" :key="updateKey" :showLegend="false" :season_id="props.season_id" :conference_id="activeConferenceTab" :season_data="season_info.seasons" />
                <!-- <small class="text-gray-500">Transaction ID:{{ updateKey }}</small>    -->
            </div>
            <!-- Schedule and Results UI (Right Side) -->
            <div class="md:col-span-3 sm:col-span-1 overflow-y-auto pt-3">
                <SeasonSchedule v-if="season_info" 
                @transaction_id="(id) => handleTransaction(id)" 
                :season_id="props.season_id"
                :key="selectedConference"
                :conference_id="activeConferenceTab" 
                :simulate_next="isAutoSimulate"
                :season_data="season_info" />
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

const season_info = ref(false);
const activeConferenceTab = ref(false);
const isAutoSimulate = ref(false);
const updateKey = ref(0);
const selectedConference = ref(0);
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
    console.log('emitted: '+id);
    updateKey.value = id+':'+(Math.random());
    activeConferenceTab.value = id;
}
//team modal
onMounted(() => {
    fetchSeasonInfo();
});
</script>
