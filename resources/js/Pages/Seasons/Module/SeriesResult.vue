<template>
    <div>
        <div v-for="(match, mm)  in series_info" :key="match.id" class="mb-4">
            <ScoreCard :key="match.id" :match="match" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import {
    roundNameFormatter,
    roundGridFormatter,
    roundStatusFormatter,
} from "@/Utility/Formatter.js";

import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";

const props = defineProps({
    series_id: {
        type: [String, Number], // Allow number or string
        required: true
    },
    season_id: {
        type: [String, Number], // Allow number or string
        required: true
    },
});

const isGameResultModalOpen = ref(false);
const series_info = ref([]);

const fetchSeriesInfo = async () => {
    try {
        const response = await axios.post(route("seasons.playoff.series.info"), {
            series_id: props.series_id,
            season_id: props.season_id,
        });
        series_info.value = response.data.games;
    } catch (error) {
        console.error("Error fetching series information:", error);
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.response?.data?.message || "Failed to fetch series information.",
        });
    }
};

onMounted(fetchSeriesInfo);
</script>
