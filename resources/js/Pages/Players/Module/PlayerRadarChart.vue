<template>
  <div class="flex overflow-auto w-full shadow">
    <div class="p-6 bg-white rounded-lg shadow-md">
      <canvas :id="'playerRatingsChart'+props.playerRatings.player_id+chart_id"></canvas>
      <p class="text-center">{{ props.playerRatings.overall_rating }} Overall</p>
      <p class="text-center first-letter:uppercase font-medium text-gray-500">
        {{ props.playerRatings.type?.replaceAll('_', ' ') ?? "-" }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, defineProps } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  playerRatings: {
    type: Object,
    required: true
  }
});

const chart_id = Math.random();
let chartInstance = null;

const renderChart = () => {
  const ctx = document.getElementById('playerRatingsChart'+props.playerRatings.player_id+chart_id).getContext('2d');

  const ratingsData = [
    props.playerRatings.shooting_rating,
    props.playerRatings.two_point_rating,
    props.playerRatings.three_point_rating,
    props.playerRatings.free_throw_rating,
    props.playerRatings.defense_rating,
    props.playerRatings.passing_rating,
    props.playerRatings.rebounding_rating,
    props.playerRatings.athleticism_rating,
    props.playerRatings.basketball_iq_rating,
    props.playerRatings.strength_rating,
    props.playerRatings.stamina_rating,
    props.playerRatings.clutch_rating,
    props.playerRatings.leadership_rating,
    props.playerRatings.work_ethic_rating,
    (99 - props.playerRatings.injury_prone_percentage),
  ];

  const labels = [
    'Shooting',
    '2PT',
    '3PT',
    'Free Throw',
    'Defense',
    'Passing',
    'Rebounding',
    'Athleticism',
    'Basketball IQ',
    'Strength',
    'Stamina',
    'Clutch',
    'Leadership',
    'Work Ethic',
    'Health'
  ];

  if (chartInstance) {
    chartInstance.destroy();
  }

  chartInstance = new Chart(ctx, {
    type: 'radar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Player Ratings',
        data: ratingsData,
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 2,
        pointBackgroundColor: 'rgba(75, 192, 192, 1)',
        pointBorderColor: '#fff',
      }],
    },
    options: {
      responsive: true,
      scales: {
        r: {
          angleLines: {
            display: true,
          },
          suggestedMin: 0,
          suggestedMax: 100,
        },
      },
      plugins: {
        title: {
          display: true,
          text: 'Player Full Ratings',
        },
        legend: {
          display: false,
        },
      },
    },
  });
};

onMounted(() => {
  renderChart();
});
</script>
