```vue
<template>
    <div class="team-roster w-full min-w-0 text-gray-200">
        <!-- Main Container -->
        <div
            class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 shadow-2xl"
        >
            <!-- Tab Navigation -->
            <div
                class="border-b border-gray-800 bg-gray-900/70"
            >
                <div
                    class="scrollbar-thin flex min-w-0 overflow-x-auto px-2 sm:px-3"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="group relative flex shrink-0 items-center gap-2 px-3 py-3 text-xs font-medium transition-all duration-200 sm:px-4"
                        :class="
                            activeTab === tab.id
                                ? 'text-white'
                                : 'text-gray-500 hover:text-gray-200'
                        "
                        @click="setActiveTab(tab.id)"
                    >
                        <!-- Icon -->
                        <i
                            :class="[
                                tab.icon,
                                activeTab === tab.id
                                    ? 'text-blue-400'
                                    : 'text-gray-600 group-hover:text-gray-400',
                            ]"
                            class="text-xs transition-colors duration-200"
                        ></i>

                        <!-- Label -->
                        <span class="whitespace-nowrap">
                            {{ tab.label }}
                        </span>

                        <!-- Active Indicator -->
                        <span
                            v-if="activeTab === tab.id"
                            class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.7)]"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div
                class="min-w-0 bg-gray-950 p-3 sm:p-4 lg:p-5"
            >
                <!-- Profile -->
                <ProfileHeader
                    v-if="activeTab === 'profile' && props.player_id"
                    :key="`profile-${props.player_id}`"
                    :player_id="props.player_id"
                />

                <!-- Season Stats -->
                <PlayerSeasonStats
                    v-else-if="activeTab === 'stats'"
                    :key="`stats-${props.player_id}`"
                    :player_id="props.player_id"
                />

                <!-- Transactions -->
                <PlayerTransactions
                    v-else-if="activeTab === 'transactions'"
                    :key="`transactions-${props.player_id}`"
                    :player_id="props.player_id"
                />

                <!-- Contracts -->
                <PlayerContracts
                    v-else-if="activeTab === 'contracts'"
                    :key="`contracts-${props.player_id}`"
                    :player_id="props.player_id"
                />

                <!-- Career Highs -->
                <PlayerCareerHighs
                    v-else-if="activeTab === 'career_highs'"
                    :key="`career-highs-${props.player_id}`"
                    :player_id="props.player_id"
                />

                <!-- Injury -->
                <PlayerInjury
                    v-else-if="activeTab === 'injury'"
                    :key="`injury-${props.player_id}`"
                    :player_id="props.player_id"
                />

                <!-- Role History -->
                <PlayerRoleHistory
                    v-else-if="activeTab === 'role_history'"
                    :key="`role-history-${props.player_id}`"
                    :player_id="props.player_id"
                />
            </div>
        </div>
    </div>

    <!-- Game Logs Modal -->
    <Modal
        :show="isGameLogsModalOpen"
        maxWidth="fullscreen"
        title="Player Game Logs"
        @close="isGameLogsModalOpen = false"
    >
        <div class="min-w-0 bg-gray-950 p-3 sm:p-4">
            <PlayerGameLogs
                v-if="isGameLogsModalOpen"
                :key="`game-logs-${props.player_id}-${isGameLogsModalOpen}`"
                :player_id="props.player_id"
                :season_id="isGameLogsModalOpen"
            />
        </div>
    </Modal>
</template>

<script setup>
import { computed, ref } from "vue";

import Modal from "@/Components/Modal.vue";

import ProfileHeader from "./ProfileHeader.vue";
import PlayerInjury from "./PlayerInjury.vue";
import PlayerTransactions from "./PlayerTransactions.vue";
import PlayerSeasonStats from "./PlayerSeasonStats.vue";
import PlayerRoleHistory from "./PlayerRoleHistory.vue";
import PlayerGameLogs from "./PlayerGameLogs.vue";
import PlayerContracts from "./PlayerContracts.vue";
import PlayerCareerHighs from "./PlayerCareerHighs.vue";

const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});

const activeTab = ref("profile");
const isGameLogsModalOpen = ref(false);

const tabs = [
    {
        id: "profile",
        label: "Profile",
        icon: "fas fa-user",
    },
    {
        id: "stats",
        label: "Season Stats",
        icon: "fas fa-chart-bar",
    },
    {
        id: "transactions",
        label: "Transactions",
        icon: "fas fa-right-left",
    },
    {
        id: "contracts",
        label: "Contracts",
        icon: "fas fa-file-contract",
    },
    {
        id: "career_highs",
        label: "Career Highs",
        icon: "fas fa-star",
    },
    {
        id: "injury",
        label: "Injury",
        icon: "fas fa-kit-medical",
    },
    {
        id: "role_history",
        label: "Role History",
        icon: "fas fa-list-check",
    },
];

const currentTabLabel = computed(() => {
    return (
        tabs.find((tab) => tab.id === activeTab.value)?.label ||
        "Player Information"
    );
});

const setActiveTab = (tab) => {
    if (activeTab.value === tab) {
        return;
    }

    activeTab.value = tab;
};
</script>

<style scoped>
/* Prevent horizontal layout explosions inside nested player components */
.team-roster {
    max-width: 100%;
    overflow-x: hidden;
}

/* Horizontal tab scrollbar */
.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: #374151 transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    height: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 999px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}

/* Better touch scrolling on mobile */
.scrollbar-thin {
    -webkit-overflow-scrolling: touch;
}
</style>
```
