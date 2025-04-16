<template>
  <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="px-4 pb-2 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 flex items-center">
          <i class="fas fa-exchange-alt text-red-500 mr-2"></i>
          Recent Transactions
        </h2>
        <span class="bg-red-100 text-red-600 px-2 py-1 rounded-full text-sm">
          {{ transactions.length }} transactions
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-4">
      <div v-for="n in 3" :key="n" class="animate-pulse mb-4">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-3 bg-gray-100 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Transactions List -->
    <div v-else class="divide-y divide-gray-100">
      <div v-for="transaction in sortedTransactions" 
           :key="transaction.id"
           class="p-4 hover:bg-gray-50 transition-colors">
        <div class="flex items-start space-x-3">
          <!-- Icon based on transaction type -->
          <div class="flex-shrink-0 mt-1">
            <i :class="getTransactionIcon(transaction.status)" class="text-lg"></i>
          </div>

          <!-- Transaction Content -->
          <div class="flex-grow">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-gray-900">
                    {{ transaction.player_name }} ,{{ transaction.age }}
                </span>
                <span :class="roleBadgeClass(transaction.player_role)">
                    {{ transaction.player_role }}
                </span>
                <span :class="getStatusBadgeClass(transaction.status)">
                    {{ formatStatus(transaction.status) }}
                </span>
            </div>

            <!-- Transaction Details -->
            <p class="text-sm text-gray-600 mt-1">
              {{ transaction.details }}
              <template v-if="transaction.status !== 'star player change'">
                <span class="text-gray-400 mx-1">•</span>
                {{ transaction.from_team_name }}
                <i class="fas fa-arrow-right text-xs mx-1 text-gray-400"></i>
                {{ transaction.to_team_name }}
              </template>
            </p>

            <!-- Source -->
            <div class="text-xs text-gray-500 mt-1">
              Source: <i class="text-blue-500">{{ getSourceTeam(transaction) }}</i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
    roleBadgeClass,
    getTransactionIcon,
    getStatusBadgeClass,
    formatStatus
} from "@/Utility/Formatter";

const transactions = ref([]);
const loading = ref(true);

const showRecentTransactions = async () => {
  try {
    loading.value = true;
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

const sortedTransactions = computed(() => {
  return transactions.value.sort((a, b) => new Date(b.date) - new Date(a.date));
});



const getSourceTeam = (transaction) => {
  const teamName = transaction.status === 'waived' || transaction.status === 'released'
    ? transaction.from_team_name
    : transaction.to_team_name;

    return `${teamName.replaceAll(' ','-').toLowerCase()}.com`;
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
