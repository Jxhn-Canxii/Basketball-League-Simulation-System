<template>
    <div class="bg-white rounded-lg shadow-md p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">
                Series Logs: {{ seriesData.home_team.name }} vs {{ seriesData.away_team.name }}
            </h2>
            <span class="px-3 py-1 rounded-full text-sm font-medium"
                  :class="seriesData.completed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                {{ seriesData.completed ? 'Completed' : 'Ongoing' }}
            </span>
        </div>

        <!-- Series Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500">Series Status</h3>
                <p class="text-2xl font-semibold">
                    {{ seriesData.home_team.wins }} - {{ seriesData.away_team.wins }}
                </p>
                <p v-if="seriesData.series_lead" class="text-sm mt-1">
                    {{ seriesData.series_lead }} leads the series
                </p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500">Round</h3>
                <p class="text-lg font-semibold">
                    {{ roundNameFormatter(seriesData.round) }}
                </p>
                <p class="text-sm mt-1">
                    Best of {{ seriesData.best_of }}
                </p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500">Conference</h3>
                <p class="text-lg font-semibold">
                    {{ seriesData.conference || 'Interconference' }}
                </p>
                <p class="text-sm mt-1">
                    #{{ seriesData.home_team.conference_rank }} vs #{{ seriesData.away_team.conference_rank }}
                </p>
            </div>
        </div>

        <!-- Games List -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">Games</h3>
            <div class="space-y-3">
                <div v-for="game in games" :key="game.id" 
                     class="border rounded-lg p-3 hover:bg-gray-50 transition-colors"
                     :class="{
                         'border-green-200 bg-green-50': game.winner_id === seriesData.winner_id,
                         'border-red-200 bg-red-50': game.winner_id && game.winner_id !== seriesData.winner_id
                     }">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <span class="font-medium">Game {{ game.game_number }}</span>
                            <span class="text-sm text-gray-500">
                                {{ new Date(game.created_at).toLocaleDateString() }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="font-medium">
                                {{ game.home_score }} - {{ game.away_score }}
                            </span>
                            <span class="text-sm px-2 py-1 rounded"
                                  :class="game.winner_id === seriesData.home_team.id ? 
                                  'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'">
                                {{ game.winner_id === seriesData.home_team.id ? 
                                   seriesData.home_team.name : seriesData.away_team.name }} won
                            </span>
                        </div>
                    </div>
                    <div v-if="game.notes" class="mt-2 text-sm text-gray-600">
                        {{ game.notes }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Player Performances -->
        <div>
            <h3 class="text-lg font-semibold mb-3">Top Performers</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Home Team Top Performers -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium mb-2 flex items-center">
                        <span class="w-3 h-3 rounded-full mr-2" 
                              :style="{ backgroundColor: '#' + seriesData.home_team.primary_color }"></span>
                        {{ seriesData.home_team.name }}
                    </h4>
                    <div class="space-y-2">
                        <div v-for="player in homeTeamTopPerformers" :key="player.id" class="flex justify-between text-sm">
                            <span>{{ player.name }}</span>
                            <span class="font-medium">{{ player.points }} PTS</span>
                        </div>
                    </div>
                </div>

                <!-- Away Team Top Performers -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium mb-2 flex items-center">
                        <span class="w-3 h-3 rounded-full mr-2" 
                              :style="{ backgroundColor: '#' + seriesData.away_team.primary_color }"></span>
                        {{ seriesData.away_team.name }}
                    </h4>
                    <div class="space-y-2">
                        <div v-for="player in awayTeamTopPerformers" :key="player.id" class="flex justify-between text-sm">
                            <span>{{ player.name }}</span>
                            <span class="font-medium">{{ player.points }} PTS</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { roundNameFormatter } from '@/Utility/Formatter.js';

const props = defineProps({
    series_id: {
        type: [Number, String],
        required: true
    }
});

const seriesData = ref({
    id: null,
    home_team: { id: null, name: '', wins: 0, conference_rank: null, primary_color: '000000' },
    away_team: { id: null, name: '', wins: 0, conference_rank: null, primary_color: '000000' },
    round: '',
    best_of: 0,
    completed: false,
    winner_id: null,
    series_lead: null
});

const games = ref([]);
const homeTeamTopPerformers = ref([]);
const awayTeamTopPerformers = ref([]);
const loading = ref(false);

const fetchSeriesData = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/api/playoff-series/${props.series_id}`);
        seriesData.value = response.data.series;
        games.value = response.data.games;
        homeTeamTopPerformers.value = response.data.home_team_top_performers;
        awayTeamTopPerformers.value = response.data.away_team_top_performers;
    } catch (error) {
        console.error('Error fetching series data:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchSeriesData();
});

watch(() => props.series_id, (newVal) => {
    if (newVal) {
        fetchSeriesData();
    }
});
</script>