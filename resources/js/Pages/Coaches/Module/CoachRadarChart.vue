<template>
    <div class="grid gap-6 mb-8 md:grid-cols-1 xl:grid-cols-1 overflow-auto shadow">
        <div class="p-6 bg-white rounded-lg shadow-md">
            <div class="flex justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Career Wins</h2>
                
            </div>
            <!-- {{ champions }} -->
            <canvas id="championsChart"></canvas>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const champions = ref([]);
let chartInstance = null; // Reference to the chart instance

const props = defineProps({
    coachDetails: {
        type: Object,
        required: true,
    },
});

const showChart = async () => {
    await formatData();
    await renderChart();
}

const formatData = async () => {
    try {
        
        champions.value = [
          { name: 'Win', championships: props.coachDetails?.career_wins ?? 0, colors: '0076B6' },
          { name: 'Loss', championships: props.coachDetails?.career_losses ?? 0, colors: 'E03A3E' }, 
        ];
} catch (error) {
        console.error("Error fetching data:", error);
    }
};
const renderChart = async () => {
    const ctx = document.getElementById('championsChart').getContext('2d');

    const labels =  champions.value.map(team => team.name);
    const data =  champions.value.map(team => team.championships);
    const backgroundColors = champions.value.map(team => `#${team.colors}`);

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                label: 'Win-Loss Record',
                data: data,
                backgroundColor: backgroundColors,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Win-Loss Ratio',
                },
                legend: {
                    position: 'top',
                },
            },
        },
    });
};

const getRandomColor = () => {
    const r = Math.floor(Math.random() * 256);
    const g = Math.floor(Math.random() * 256);
    const b = Math.floor(Math.random() * 256);
    return `rgba(${r}, ${g}, ${b}, 0.6)`; // Semi-transparent color
};

onMounted(() => {
    showChart();
});
</script>

<style scoped>
/* Add custom styles here */
</style>
