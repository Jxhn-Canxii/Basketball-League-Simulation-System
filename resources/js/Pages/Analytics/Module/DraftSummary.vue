<template>
    <div class="relative max-w-screen">
        <!-- Carousel container -->
        <div class="overflow-hidden">
            <div
                class="flex transition-transform duration-500 ease-in-out"
                :style="{ transform: `translateX(-${currentIndex * cardWidth}px)` }"
            >
                <div
                    v-for="(item, index) in data"
                    :key="index"
                    class="min-w-[250px] max-w-[250px] mx-2 flex-shrink-0 relative flex flex-col items-center border border-gray-300 p-4 bg-white rounded-lg shadow-lg group hover:shadow-xl transition-shadow duration-300"
                >
                    <!-- Draft ID -->
                    <div class="text-xl absolute left-2 top-0 font-semibold text-black">
                        Draft Batch {{ item.draft_id }}
                    </div>
                    <div class="text-3xl absolute right-2 top-0 font-semibold text-black">
                        
                    </div>

                    <!-- Active Players -->
                    <div class="text-3xl block font-semibold mt-7 text-black">
                        <div class="flex items-center text-sm text-green-700 mb-2">
                            <i class="fas fa-users me-2"></i>
                            <span>{{ item.active_players_with_team }}</span>
                        </div>
                        <div class="flex items-center text-sm text-blue-700 mb-2">
                            <i class="fas fa-user-check me-2"></i>
                            <span>{{ item.active_players }}</span>
                        </div>
                        <div class="flex items-center text-sm text-red-700 mb-2">
                            <i class="fas fa-user-times me-2"></i>
                            <span>{{ item.total_players - item.active_players }}</span>
                        </div>
                    </div>

                    <!-- Hover Details -->
                    <div
                        class="absolute inset-0 bg-black bg-opacity-80 space-y-3 text-white p-4 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        v-if="item.active_players_with_team > 0"
                    >
                        <div class="text-sm font-bold">Percentage</div>
                        <div class="flex items-center text-sm">
                            <i class="fas fa-users me-2"></i>
                            <span>{{ item.active_percentage_with_team }}%</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <i class="fas fa-user-check me-2"></i>
                            <span>{{ item.active_percentage }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Arrows -->
        <button
            @click="prevSlide"
            class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-white border rounded-full p-2 shadow-md z-10"
            :disabled="currentIndex === 0"
        >
            ‹
        </button>
        <button
            @click="nextSlide"
            class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-white border rounded-full p-2 shadow-md z-10"
            :disabled="currentIndex >= maxIndex"
        >
            ›
        </button>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const data = ref([]);
const currentIndex = ref(0);
const visibleCards = 4; // Adjust depending on screen size if needed
const cardWidth = 270;  // Approx width + margin of each card

const fetchGameRecords = async () => {
    try {
        const response = await axios.get(route("draft.statistics"));
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching draft statistics:", error);
    }
};

const maxIndex = computed(() => {
    return Math.max(0, data.value.length - visibleCards);
});

const nextSlide = () => {
    if (currentIndex.value < maxIndex.value) {
        currentIndex.value++;
    }
};

const prevSlide = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
};

onMounted(() => {
    fetchGameRecords();
});

</script>