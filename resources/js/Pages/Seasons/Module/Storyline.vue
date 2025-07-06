<template>
    <div class="draft-board">
        <div class="overflow-x-auto mb-8" v-if="data">
            <p>{{ data.storyline }}</p>
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

onMounted(async () => {
    await fetchStoryLine();
});

const fetchStoryLine = async () => {
    try {
        data.value = [];
        const response = await axios.post(route("seasons.storyline"), { season_id: props.season_id });
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching draft history:", error);
    }
};
</script>