<template>
    <div class="team-roster relative">
        <!-- Loader Overlay with Progress -->
        <div v-if="isLoading" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center w-80">
                <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-blue-500"></div>
                <p class="mt-4 text-lg font-semibold text-gray-700">Preparing Season Awards...</p>
                <div class="w-full mt-4">
                    <div class="progress">
                        <div class="progress-bar" :style="{ width: progressPercentage + '%' }">{{ progressPercentage }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-xl font-semibold text-gray-800">Season Awards</h2>

        <!-- Divider -->
        <hr class="my-4 border-t border-gray-200" />

        <!-- Update Button -->
        <div class="mb-4 flex justify-center items-center" v-if="!awards.length && !isLoading">
            <p class="text-red-500 font-bold text-2xl">***No data available***</p>
        </div>

        <!-- Awards Table -->
        <div class="overflow-x-auto" v-if="awards.length && !isLoading">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Award Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Award Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="award in awards" :key="award.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ award.award_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ award.player_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ award.team_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ award.award_description }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const awards = ref([]);
const isLoading = ref(false);
const progressPercentage = ref(0);
const props = defineProps({
    team_ids: Array,
});

const updatePlayerStatus = async () => {
    try {
        isLoading.value = true;
        const team_ids = props.team_ids;
        const team_count = team_ids.length;

        for (let i = 0; i < team_count; i++) {
            const team_id = team_ids[i];
            await updatePlayerStatusPerTeam(i, team_id, team_count);
            progressPercentage.value = Math.round(((i + 1) / team_count) * 100);
        }

        await showSeasonAwards();
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'All player stats updated successfully.',
        });
    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to update player status for some or all teams. Please try again later.',
        });
    } finally {
        isLoading.value = false;
        progressPercentage.value = 0;
    }
};

const updatePlayerStatusPerTeam = async (index, team_id, team_count) => {
    try {
        await axios.post(route('store.player.stats'), { team_id });
    } catch (error) {
        console.error(error);
        throw new Error('Failed to update player stats for team ' + team_id);
    }
};

const showSeasonAwards = async () => {
    try {
        isLoading.value = true;
        progressPercentage.value = 50; // Example: set to 50% while fetching awards
        const response = await axios.post(route('player.awards'));
        awards.value = response.data.awards;
        progressPercentage.value = 100;
    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Failed to fetch season awards. Please try again later.',
        });
    } finally {
        isLoading.value = false;
        progressPercentage.value = 0;
    }
};

onMounted(() => {
    showSeasonAwards();
    // Uncomment the following line to trigger team updates on mount
    // updatePlayerStatus();
});
</script>

<style scoped>
.table {
    font-size: 0.75rem;
}

.table th,
.table td {
    padding: 0.5rem;
}

.progress {
    width: 100%;
    background-color: #f3f3f3;
    border-radius: 5px;
    height: 20px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background-color: #4caf50;
    text-align: center;
    color: white;
    line-height: 20px;
    transition: width 0.5s ease-in-out;
}
</style>