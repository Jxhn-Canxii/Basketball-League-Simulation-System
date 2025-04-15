<template>
  <div class="relative overflow-hidden bg-white shadow-md rounded-xl p-2">
    <h2 class="text-md font-bold mb-1 text-red-600">📰 Recent Transactions</h2>
    
    <!-- Skeleton Loader -->
    <div v-if="loading" class="whitespace-nowrap flex space-x-10 text-sm">
      <div v-for="n in 3" :key="n" class="flex-shrink-0 inline-block animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-96 mb-2"></div>
        <div class="h-4 bg-gray-100 rounded w-64"></div>
      </div>
    </div>

    <!-- Actual Content -->
    <div v-else class="whitespace-nowrap animate-marquee flex space-x-5 text-sm text-gray-800">
      <div
        v-for="transaction in transactions"
        :key="transaction.id"
        class="flex-shrink-0 inline-block"
      >
        <p>
          <b>{{ transaction.player_name }} <sup>{{ transaction.age}}</sup></b> <a class="first-letter:lower">&nbsp;{{ transaction.details }}</a>
          [{{transaction.from_team_name}} <i class="fa fa-arrow-right"></i> {{ transaction.to_team_name }}]
          <span class="text-blue-500">&nbsp; Source: {{ transaction.status == 'waived' || transaction.status == 'released' ? transaction.from_team_name : transaction.to_team_name }}.com</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const transactions = ref([]);
const loading = ref(true);

const showRecentTransactions = async () => {
  try {
    loading.value = true;
    transactions.value = []; // Reset transactions before fetching new data
    const response = await axios.post(route('recent.transactions'));
    transactions.value = response.data.transactions;
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Failed to fetch season transactions. Please try again later.',
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  showRecentTransactions();
});
</script>

<style scoped>
@keyframes marquee {
  0% {
    transform: translateX(100%);
  }
  100% {
    transform: translateX(-100%);
  }
}

.animate-marquee {
  display: inline-block;
  animation: marquee 80s linear infinite;
}

/* Add these new styles for skeleton animation */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}
</style>
