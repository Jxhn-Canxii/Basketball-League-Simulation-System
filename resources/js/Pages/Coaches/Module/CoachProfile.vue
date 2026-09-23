<template>
    <section
        class="w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white"
    >
        <!-- =========================================================
             TAB HEADER
        ========================================================== -->
        <div
            class="border-b border-white/[0.07] bg-[#090909] px-3 sm:px-4"
        >
            <div class="flex items-center gap-1 overflow-x-auto">
                <!-- Profile -->
                <button
                    type="button"
                    @click="setActiveTab('profile')"
                    :class="
                        activeTab === 'profile'
                            ? 'border-yellow-500 text-yellow-400'
                            : 'border-transparent text-gray-500 hover:border-white/[0.12] hover:text-gray-300'
                    "
                    class="group relative flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-xs font-bold transition sm:px-4"
                >
                    <span
                        :class="
                            activeTab === 'profile'
                                ? 'bg-yellow-500/[0.08] text-yellow-400'
                                : 'bg-white/[0.035] text-gray-600 group-hover:text-gray-400'
                        "
                        class="flex h-6 w-6 items-center justify-center rounded-md transition"
                    >
                        <i class="fas fa-user text-[10px]"></i>
                    </span>

                    <span>Coach Profile</span>
                </button>

                <!-- Stats -->
                <button
                    type="button"
                    @click="setActiveTab('stats')"
                    :class="
                        activeTab === 'stats'
                            ? 'border-yellow-500 text-yellow-400'
                            : 'border-transparent text-gray-500 hover:border-white/[0.12] hover:text-gray-300'
                    "
                    class="group relative flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-xs font-bold transition sm:px-4"
                >
                    <span
                        :class="
                            activeTab === 'stats'
                                ? 'bg-yellow-500/[0.08] text-yellow-400'
                                : 'bg-white/[0.035] text-gray-600 group-hover:text-gray-400'
                        "
                        class="flex h-6 w-6 items-center justify-center rounded-md transition"
                    >
                        <i class="fas fa-chart-bar text-[10px]"></i>
                    </span>

                    <span>Season Stats</span>
                </button>
            </div>
        </div>

        <!-- =========================================================
             TAB CONTENT
        ========================================================== -->
        <div class="min-w-0 bg-black">
            <!-- Profile -->
            <Transition
                name="tab"
                mode="out-in"
            >
                <div
                    v-if="activeTab === 'profile'"
                    key="profile"
                    class="min-w-0 p-3 sm:p-4"
                >
                    <ProfileHeader
                        v-if="props.coach_id"
                        :key="`profile-${props.coach_id}`"
                        :coach_id="props.coach_id"
                    />
                </div>

                <!-- Stats -->
                <div
                    v-else
                    key="stats"
                    class="min-w-0 p-3 sm:p-4"
                >
                    <CoachSeasonPerformance
                        v-if="props.coach_id"
                        :key="`stats-${props.coach_id}`"
                        :coach_id="props.coach_id"
                    />
                </div>
            </Transition>
        </div>
    </section>
</template>

<script setup>
import { ref } from "vue";

import ProfileHeader from "./ProfileHeader.vue";
import CoachSeasonPerformance from "./CoachSeasonPerformance.vue";

const props = defineProps({
    coach_id: {
        type: Number,
        required: true,
    },
});

const activeTab = ref("profile");

const setActiveTab = (tab) => {
    if (tab === activeTab.value) {
        return;
    }

    activeTab.value = tab;
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Tab transition
|--------------------------------------------------------------------------
*/

.tab-enter-active,
.tab-leave-active {
    transition:
        opacity 0.16s ease,
        transform 0.16s ease;
}

.tab-enter-from {
    opacity: 0;
    transform: translateY(4px);
}

.tab-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/*
|--------------------------------------------------------------------------
| Horizontal scrollbar
|--------------------------------------------------------------------------
*/

.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.18);
}
</style>