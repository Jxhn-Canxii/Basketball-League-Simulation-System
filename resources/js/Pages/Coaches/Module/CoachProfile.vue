<template>
    <div class="team-roster p-3">
        <!-- Tab Navigation -->
        <div class="flex space-x-4">
            <button
                 class="text-sm font-medium text-gray-600 border-b-2 hover:border-blue-600"
                :class="activeTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent'"
                @click="setActiveTab('profile')"
            >
                <i class="fas fa-user"></i> Coach Profile
            </button>
            <button
                class="text-sm font-medium text-gray-600 border-b-2 hover:border-blue-600"
                :class="activeTab === 'stats' ? 'border-blue-600 text-blue-600' : 'border-transparent'"
                @click="setActiveTab('stats')"
            >
                <i class="fas fa-chart-bar"></i> Season Stats
            </button>
        </div>
        <!-- Divider -->
        <hr class="my-4 border-t border-gray-200" />

        <!-- Tab Content -->
        <div v-if="activeTab === 'profile'">
            <ProfileHeader v-if="props.coach_id" :key="coach_id" :coach_id="coach_id" />
        </div>
        <div v-if="activeTab === 'stats'">
            <CoachSeasonPerformance :key="props.coach_id" :coach_id="props.coach_id" />
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

import ProfileHeader from "./ProfileHeader.vue";
import CoachSeasonPerformance from "./CoachSeasonPerformance.vue";

const props = defineProps({
    coach_id: {
        type: Number,
        required: true,
    },
});

const activeTab = ref('profile');
const coach_id = ref(props.coach_id);

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
