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

import TeamInfo from "./TeamInfo.vue";
import TeamHistory from "./TeamHistory.vue";
import TeamRoster from "./TeamRoster.vue";
import Top10Player from "./Top10Player.vue";
import SeasonTimeLine from "@/Pages/Analytics/Module/SeasonTimeLine.vue";
import TeamTransactions from "./TeamTransactions.vue";

const isTeamModalOpen = ref(false);
const currentTab  = ref('info');

const props = defineProps({
    team_id: Number,
    showButton: Number,
    text: String,
});

</script>
