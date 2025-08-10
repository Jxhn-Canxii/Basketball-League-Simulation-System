<template>
    <div>
        <div v-for="game in series_info" :key="game.id" class="mb-4">
            <!-- Game Result Card -->
            <div class="bg-white shadow rounded overflow-hidden">
                <!-- Header with team names and score -->
                <div class="flex justify-between items-center p-4" 
                     :style="{ backgroundColor: game.home_primary_color }">
                    <div class="text-white font-bold">
                        {{ game.home_team_city }} {{ game.home_team_name }}
                    </div>
                    <div class="text-white font-bold text-lg">
                        {{ game.home_score }} - {{ game.away_score }}
                    </div>
                    <div class="text-white font-bold">
                        {{ game.away_team_city }} {{ game.away_team_name }}
                    </div>
                </div>

                <!-- Winner / Status -->
                <div class="p-4 text-sm">
                    <p v-if="game.status === 2">
                        Winner: <strong>{{ game.winning_city }} {{ game.winning_name }}</strong>
                    </p>
                    <p v-else class="text-gray-500">
                        Game not completed
                    </p>
                </div>

                <!-- View Result Link -->
                <div class="bg-gray-100 p-2 text-right">
                    <a href="#"
                       class="text-blue-500 hover:underline text-sm font-bold"
                       @click.prevent="isGameResultModalOpen = game.game_id">
                        View Result
                    </a>
                </div>
            </div>
        </div>

        <!-- Game Result Modal -->
        <Modal :show="isGameResultModalOpen" :maxWidth="'fullscreen'" title="Game Results"
               @close="isGameResultModalOpen = false">
            <div class="mt-4">
                <GameResults :key="isGameResultModalOpen" :game_id="isGameResultModalOpen" />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import GameResults from "@/Pages/Seasons/Module/GameResults.vue";

const props = defineProps({
    series_id: {
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
