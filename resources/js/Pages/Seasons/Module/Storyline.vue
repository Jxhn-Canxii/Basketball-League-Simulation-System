<template>
    <div class="draft-board">
        <div class="overflow-x-auto mb-8" v-if="data">
            <p v-for="(paragraph, index) in storylineParagraphs" :key="index" :class="index === 0 ? 'storyline-title' : 'storyline-paragraph'">
                {{ paragraph }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import Paginator from "@/Components/Paginator.vue";
import TopStatistics from "@/Pages/Analytics/Module/TopStatistics.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const emits = defineEmits(["newSeason"]);
const data = ref([]);
const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

// Computed property to split storyline into paragraphs
const storylineParagraphs = computed(() => {
    if (data.value.storyline) {
        return data.value.storyline.split('\n\n').filter(paragraph => paragraph.trim());
    }
    return [];
});

onMounted(async () => {
    await fetchStoryLine();
});

const fetchStoryLine = async () => {
    try {
        data.value = [];
        const response = await axios.post(route("seasons.storyline"), { season_id: props.season_id });
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching storyline:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load storyline. Please try again.',
        });
    }
};
</script>

<style scoped>
.storyline-title {
    font-weight: bold;
    font-size: 1.2em;
    margin-bottom: 1.5rem;
}

.storyline-paragraph {
    margin-bottom: 1rem;
}
</style>