<template>
  <div class="bg-black shadow-sm rounded-lg mt-2 overflow-hidden shadow-lg shadow-red-400" v-if="data">
    <div
        class="flex item-center gap-2 border border-gray-800 bg-gray-900 p-1 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-gray-700"
    >
        <button
            type="button"
            class="text-yellow-500 rounded-xl px-2"
            :class="currentTab == 'all_star' ? 'bg-yellow-800' : ''"
            @click="switchTab('all_star')"
        >
            <i class="fas fa-star"></i>
            <span> All-Star</span>
        </button>
        <button
            type="button"
            class="text-yellow-500 rounded-xl px-2"
            :class="currentTab == 'all_rookie' ? 'bg-yellow-800' : ''"
            @click="switchTab('all_rookie')"
        >
            <i class="fas fa-users"></i>
            <span> All-Rookie</span>
        </button>
    </div>
    <!-- Loading State -->
    <div v-if="loading" class="p-4">
      <div v-for="n in 3" :key="n" class="animate-pulse mb-4">
        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
        <div class="h-3 bg-gray-100 rounded w-1/2"></div>
      </div>
    </div>
    <!-- Transactions List -->
    <div v-if="data && !loading" class="grid grid-cols-2 justify-between">
        <div class="bg-blue-500 min-h-500 space-y-3 border-r-2">
          <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#0d1117] text-xs">
                        <th class="uppercase py-2">
                          NORTH {{ currentTab?.replace('_','-') }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Results -->
                    <template v-if="data?.north">
                        <tr
                            v-for="(transaction,index) in northData"
                            :key="transaction.player_id ?? index"
                            :style="{
                                backgroundColor:
                                    getTeamColor(transaction)
                            }"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Team -->
                            <td class="px-2 py-3">
                                <div class="flex items-center gap-3">
                                    <!-- Team Color -->
                                    <div
                                        class="h-10 w-1 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                getTeamColor(transaction)
                                        }"
                                    ></div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-sm font-bold uppercase tracking-wide text-white"
                                        >
                                            <i v-if="index == 0" class="fa fa-star text-yellow-500"></i>
                                            <i v-if="index <= 4" class="fa fa-square text-yellow-500"></i>
                                            <i v-if="index > 4 && index < 12" class="fa fa-square text-green-500"></i>
                                            <i v-if="index > 11" class="fa fa-lock text-gray-500"></i>
                                            {{ transaction.player_name }} | <a>{{ transaction.team_acronym }}</a>
                                        </div>
                                        <div class="flex justify-start text-center gap-2 mt-2">
                                          <span
                                              class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-1 py-1 text-xs font-bold uppercase text-slate-500"
                                          >
                                              {{ transaction.avg_points_per_game ?? 0 }} PPG
                                          </span>
                                          <span
                                              class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-1 py-1 text-xs font-bold uppercase text-slate-500"
                                          >
                                              {{ transaction.avg_assists_per_game ?? 0 }} APG
                                          </span>
                                          <span
                                              class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-1 py-1 text-xs font-bold uppercase text-slate-500"
                                          >
                                              {{ transaction.avg_rebounds_per_game ?? 0 }} RPG
                                          </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else>
                        <td colspan="3" class="px-6 py-14 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-users text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Team Records
                            </p>

                            <p class="mt-1 text-xs text-slate-600">
                                No teams matched your search.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="bg-red-500 min-h-500 space-y-3">
          <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#0d1117] text-xs">
                        <th class="uppercase py-2">
                          SOUTH {{ currentTab?.replace('_','-') }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Results -->
                    <template v-if="data?.south">
                        <tr
                            v-for="(transaction,index) in southData"
                            :key="transaction.player_id ?? index"
                            :style="{
                                backgroundColor:
                                    getTeamColor(transaction)
                            }"
                            class="group border-b border-slate-800/70 transition-colors duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Team -->
                            <td class="px-2 py-3">
                                <div class="flex items-center gap-3">
                                    <!-- Team Color -->
                                    <div
                                        class="h-10 w-1 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                getTeamColor(transaction)
                                        }"
                                    ></div>

                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-sm font-bold uppercase tracking-wide text-white"
                                        >
                                            <i v-if="index == 0" class="fa fa-star text-yellow-500"></i>
                                            <i v-if="index <= 4" class="fa fa-square text-yellow-500"></i>
                                            <i v-if="index > 4 && index < 12" class="fa fa-square text-green-500"></i>
                                            <i v-if="index > 11" class="fa fa-lock text-gray-500"></i>
                                            {{ transaction.player_name }} | <a>{{ transaction.team_acronym }}</a>
                                        </div>
                                        <div class="flex justify-start text-center gap-2 mt-2">
                                          <span
                                              class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-1 py-1 text-xs font-bold uppercase text-slate-500"
                                          >
                                              {{ transaction.avg_points_per_game ?? 0 }} PPG
                                          </span>
                                          <span
                                              class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-1 py-1 text-xs font-bold uppercase text-slate-500"
                                          >
                                              {{ transaction.avg_assists_per_game ?? 0 }} APG
                                          </span>
                                          <span
                                              class="inline-flex items-center rounded-md border border-slate-800 bg-[#0a0d12] px-1 py-1 text-xs font-bold uppercase text-slate-500"
                                          >
                                              {{ transaction.avg_rebounds_per_game ?? 0 }} RPG
                                          </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else>
                        <td colspan="3" class="px-6 py-14 text-center">
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-users text-sm text-slate-600"
                                ></i>
                            </div>

                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-widest text-slate-400"
                            >
                                No Team Records
                            </p>

                            <p class="mt-1 text-xs text-slate-600">
                                No teams matched your search.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

const data = ref([]);
const northData = ref([]);
const southData = ref([]);
const currentTab = ref('all-star');
const loading = ref(true);

const switchTab = (type) =>{
  currentTab.value = type;

  if(type == 'all_star'){
    northData.value = data.value.north?.all_star ?? [];
    southData.value = data.value.south?.all_star ?? [];
  }

  if(type == 'all_rookie'){
    northData.value = data.value.north?.all_rookie ?? [];
    southData.value = data.value.south?.all_rookie ?? [];
  }
}

const showAllStarRace = async () => {
  try {
    loading.value = true;
    const response = await axios.post(route('allstar.candidate'),{
      season_id: props.season_id
    });
    data.value = response.data;
  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Failed to fetch season all star race. Please try again later.',
    });
  } finally {
    loading.value = false;
  }
};

const getTeamColor = (team) => {
    if (team?.primary_color) {
        return `#${String(team.primary_color).replace("#", "")}`;
    }

    if (team?.secondary_color) {
        return `#${String(team.secondary_color).replace("#", "")}`;
    }

    return "#64748b";
};

const loadData = async () => {
  await showAllStarRace();
  await switchTab('all_star');
}

onMounted(() => {
  loadData();
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

., .flip-card-back {
  backface-visibility: hidden;
  transition: transform 0.6s;
}

. {
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

.is-flipped . {
  transform: rotateY(-180deg);
}

.is-flipped .flip-card-back {
  transform: rotateY(0deg);
}
</style>
