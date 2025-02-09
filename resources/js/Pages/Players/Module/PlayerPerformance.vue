<template>
    <div class="team-roster p-3">
        <!-- Tab Navigation -->
        <div class="flex space-x-4">
            <button
                 class="text-sm font-medium text-gray-600 border-b-2 hover:border-blue-600"
                :class="activeTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent'"
                @click="setActiveTab('profile')"
            >
                <i class="fas fa-user"></i> Player Profile
            </button>
            <button
                class="text-sm font-medium text-gray-600 border-b-2 hover:border-blue-600"
                :class="activeTab === 'stats' ? 'border-blue-600 text-blue-600' : 'border-transparent'"
                @click="setActiveTab('stats')"
            >
                <i class="fas fa-chart-bar"></i> Season Stats
            </button>
            <button
                 class="text-sm font-medium text-gray-600 border-b-2 hover:border-blue-600"
                :class="activeTab === 'transactions' ? 'border-blue-600 text-blue-600' : 'border-transparent'"
                @click="setActiveTab('transactions')"
            >
                <i class="fas fa-exchange-alt"></i> Player Transactions
            </button>
            <button
                class="text-sm font-medium text-gray-600 border-b-2 hover:border-blue-600"
                :class="activeTab === 'injury' ? 'border-blue-600 text-blue-600' : 'border-transparent'"
                @click="setActiveTab('injury')"
            >
                <i class="fas fa-medkit"></i> Injury History
            </button>
        </div>
        <!-- Divider -->
        <hr class="my-4 border-t border-gray-200" />

        <!-- Tab Content -->
        <div v-if="activeTab === 'profile'">
            <ProfileHeader v-if="props.player_id" :key="player_id" :player_id="player_id" />
        </div>
        <div v-if="activeTab === 'stats'">
            <PlayerSeasonStats :key="props.player_id" :player_id="props.player_id" />
        </div>
        <div v-if="activeTab === 'transactions'">
            <PlayerTransactions :key="props.player_id" :player_id="props.player_id" />
        </div>

        <div v-if="activeTab === 'injury'">
            <PlayerInjury :key="props.player_id" :player_id="props.player_id" />
        </div>
    </div>
    <Modal :show="isGameLogsModalOpen" :maxWidth="'fullscreen'">
        <button
            class="flex float-end bg-gray-100 p-3"
            @click.prevent="isGameLogsModalOpen = false"
        >
            <i class="fa fa-times text-black-600"></i>
        </button>
        <div class="mt-4 p-3 block">
            <PlayerGameLogs
                :key="props.player_id"
                :player_id="props.player_id"
                :season_id="isGameLogsModalOpen"
            />
        </div>
    </Modal>
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

import ProfileHeader from "./ProfileHeader.vue";
import PlayerInjury from "./PlayerInjury.vue";
import PlayerTransactions from "./PlayerTransactions.vue";
import PlayerSeasonStats from "./PlayerSeasonStats.vue";
import PlayerGameLogs from "./PlayerGameLogs.vue";
const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});

const isGameLogsModalOpen = ref(false);
const activeTab = ref('profile');
const player_id = ref(props.player_id);

const setActiveTab = (tab) => {
    activeTab.value = tab;
}

</script>

<style scoped>
.table {
    font-size: 0.75rem; /* Smaller text size */
}

.table th,
.table td {
    padding: 0.5rem; /* Smaller padding */
}
</style>
