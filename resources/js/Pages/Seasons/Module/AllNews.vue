<template>
  <div class="bg-black shadow-sm rounded-lg mt-2 overflow-hidden shadow-lg shadow-red-400">
    <div class="px-4 pb-2 border-b border-gray-200 bg-gray-900">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-yellow-500 flex items-center">
          <i class="fas fa-newspaper text-red-500 mr-2"></i>
          Latest News
        </h2>
        <span class="bg-red-100 text-red-600 px-2 py-0 rounded-full text-sm">
          {{ data.total_pages ?? 0 }} transactions
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
    <div v-else class="divide-y divide-gray-100 grid grid-cols-3">
      <div v-for="news in data?.data" 
           :key="news.id"
           class="p-4 transition-colors">
        <div class="flex items-start space-x-3">
            <GameNews :key="news.id" :data="news" />
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div class="flex w-full overflow-auto">
        <Paginator
            v-if="data.total_pages"
            :page_number="search.page_num"
            :total_rows="data.total_pages ?? 0"
            :itemsperpage="search.itemsperpage"
            @page_num="handlePagination"
        />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Paginator from "@/Components/Paginator.vue";
import GameNews from "@/Pages/Seasons/Module/GameNews.vue";

import {
    roleBadgeClass,
    getTransactionIcon,
    getStatusBadgeClass,
    statusBadgeClass,
    getAwardBadgeClass,
    getAwardIcon,
    formatStatus,
    formatAwardText 
} from "@/Utility/Formatter";

const props = defineProps({
    season_id: {
        type: Number,
        default: true,
    }
});

const data = ref([]);
const loading = ref(true);
const flippedCards = ref({});
const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 9,
    season_id: props.season_id ?? 1,
});
;

const getNewsList = async () => {
  try {
    loading.value = true;
    const response = await axios.post(route('game.news.list'),search.value);
    data.value = response.data;
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

const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;
    getNewsList();
}

const getSourceTeam = (transaction) => {
  const playerSlug = transaction.player_name.replace(/\s+/g, '-').toLowerCase();

  const teamCity = transaction.to_team_city === 'None' ? 'free-agent' : transaction.to_team_city;
  const teamName = transaction.to_team_name === 'Free Agent' ? '' : transaction.to_team_name;

  const teamSlug = `${teamCity} ${teamName}`.trim().replace(/\s+/g, '-').toLowerCase();
  const statusSlug = transaction.status.replace(/\s+/g, '-').toLowerCase();

  const domain = `${teamSlug}.com`; // e.g. valenzuela-dolphins.com

  return `https://${domain}/news/${transaction.season_id ?? 0}/${playerSlug}-${teamSlug}-${statusSlug}`;
};



const togglePlayerCard = (id) => {
  flippedCards.value[id] = !flippedCards.value[id];
};

const parseAwards = (awardsInfo) => {
  return awardsInfo.split(',');
};

onMounted(() => {
  getNewsList();
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

/* Add styles for flip card */
.flip-card {
  perspective: 1000px;
}

.flip-card-front, .flip-card-back {
  backface-visibility: hidden;
  transition: transform 0.6s;
}

.flip-card-front {
  transform: rotateY(0deg);
}

.flip-card-back {
  transform: rotateY(180deg);
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.is-flipped .flip-card-front {
  transform: rotateY(-180deg);
}

.is-flipped .flip-card-back {
  transform: rotateY(0deg);
}
</style>
