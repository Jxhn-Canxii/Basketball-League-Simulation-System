<template>
    <div class="grid md:grid-cols-5 grid-cols-1 gap-6">

        <!-- Score Details Cards -->
        <div
            v-for="(item, index) in scoreData"
            :key="index"
            @click.prevent="isGameResultModalOpen = item"
            class="flex flex-col items-center border border-gray-300 p-6 bg-gray-900 rounded-lg shadow-md relative"
        >
            <div :class="item.scoreColor" class="text-5xl font-bold">
                {{ item.score }}
            </div>
            <div class="text-center mt-3">
                <p class="text-lg font-semibold text-nowrap text-gray-600">{{ item.home_team }} vs {{ item.away_team }} <sup>S{{ item.season_id }}</sup></p>
                <p class="text-sm text-gray-500"></p>
            </div>
            <div class="text-center mt-4">
                <p class="text-lg text-nowrap font-bold">{{ item.title }}</p>
                <sup class="text-sm text-gray-500">{{ item.game_id }}</sup>
            </div>
        </div>

    </div>

    <!-- Modal for Game Results -->
    <Modal :show="isGameResultModalOpen" :maxWidth="'fullscreen'" title="Game Result" @close="isGameResultModalOpen = false">
        <div class="mt-4">
            <GameResults 
            :season_id="isGameResultModalOpen.season_id" 
            :key="isGameResultModalOpen.game_id" 
            :game_id="isGameResultModalOpen.game_id" 
            />
        </div>
    </Modal>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios"; // Ensure axios is imported
import Modal from "@/Components/Modal.vue";
import GameResults from "@/Pages/Seasons/Module/GameResults.vue";

const isGameResultModalOpen = ref(false);
const scoreData = ref([]);

const fetchGameRecords = async () => {
    try {
        // Adjust the API call to fetch data based on the SQL query
        const response = await axios.get(route("alltime.game.records"));  // Adjust to correct route
        const records = response.data;

        scoreData.value = [
            {
                score: records.lowest_score_by_team,
                home_team: records.home_team_for_lowest_score,
                away_team: records.away_team_for_lowest_score,
                season_id: records.lowest_score_season_id, // You may need to add this to the response or adjust
                game_id: records.lowest_score_game_id, // You may need to add this to the response or adjust
                title: `Lowest Score(${records.team_with_lowest_score})`,
                scoreColor: "text-red-600",
            },
            {
                score: records.highest_score_by_team,
                home_team: records.home_team_for_highest_score,
                away_team: records.away_team_for_highest_score,
                season_id: records.highest_score_season_id, // You may need to add this to the response or adjust
                game_id: records.highest_score_game_id, // You may need to add this to the response or adjust
                title: `Highest Score(${records.team_with_highest_score})`,
                scoreColor: "text-green-600",
            },
            {
                score: records.highest_combined_score,
                home_team: records.home_team_for_highest_combined_score,
                away_team: records.away_team_for_highest_combined_score,
                season_id: records.highest_combined_score_season_id, // You may need to add this to the response or adjust
                game_id: records.highest_combined_score_game_id, // You may need to add this to the response or adjust
                title: "Highest Combined Score",
                scoreColor: "text-yellow-600",
            },
            {
                score: records.lowest_combined_score,
                home_team: records.home_team_for_lowest_combined_score,
                away_team: records.away_team_for_lowest_combined_score,
                season_id: records.lowest_combined_score_season_id, // You may need to add this to the response or adjust
                game_id: records.lowest_combined_score_game_id, // You may need to add this to the response or adjust
                title: "Lowest Combined Score",
                scoreColor: "text-orange-600",
            },
            {
                score: records.biggest_winning_margin,
                home_team: records.home_team_for_biggest_margin,
                away_team: records.away_team_for_biggest_margin,
                season_id: records.biggest_margin_season_id, // You may need to add this to the response or adjust
                game_id: records.biggest_margin_game_id, // You may need to add this to the response or adjust
                title: "Biggest Winning Margin",
                scoreColor: "text-purple-600",
            },
        ];

    } catch (error) {
        console.error("Error fetching game records:", error);
    }
};

onMounted(() => {
    fetchGameRecords();
});
</script>
