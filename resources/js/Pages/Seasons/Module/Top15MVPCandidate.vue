<template>
  <div class="mvp-leaderboard max-w-full mx-auto p-4 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-center flex-1">Top 15 MVP Candidates</h2>
      <button
        @click="fetchMVPLeaders"
        class="ml-4 bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded shadow"
        title="Reload Leaderboard"
      >
        🔄 Reload
      </button>
    </div>

    <div v-if="loading" class="text-center text-gray-500">Loading...</div>

    <div v-else class="max-h-[90vh] overflow-auto">
      <div v-if="leaders.length === 0" class="text-center text-red-500 font-semibold">
        No data available
      </div>

      <ul v-else class="grid gap-4 grid-cols-1">
        <li
          v-for="(player, index) in leaders"
          :key="player.player_id"
          class="flex items-center space-x-4 p-3 border rounded shadow-sm transition-transform duration-500"
          :class="{
            'bg-green-100': rankChange(player.player_id) > 0,
            'bg-red-100': rankChange(player.player_id) < 0,
            'bg-white': rankChange(player.player_id) === 0
          }"
          :style="[
            getAnimationStyle(player.player_id),
            {
              borderLeft: `6px solid #${player.primary_color}`,
              backgroundImage: `linear-gradient(to right, #${player.primary_color}20, #${player.secondary_color}20)`
            }
          ]"
        >
          <div class="w-8 text-center font-bold text-lg text-gray-700">
            {{ index + 1 }}
          </div>

          <div class="flex-1 flex-nowrap">
            <div class="font-semibold text-gray-900 flex items-center space-x-2">
              <span class="text-nowrap">{{ player.player_name }}</span>
              <span v-if="rankChange(player.player_id) > 0" title="Rank rose" class="text-green-600">▲</span>
              <span v-else-if="rankChange(player.player_id) < 0" title="Rank fell" class="text-red-600">▼</span>
            </div>

            <div class="text-xs text-gray-500 space-x-2 mt-0.5">
              <span v-if="player.is_rookie" class="text-nowrap bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full font-medium">
                Rookie
              </span>
              <span v-if="player.draft_status" class="text-nowrap bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-medium">
                Draft: {{ formatDraftStatus(player.draft_status) }}
              </span>
            </div>

            <div class="text-sm text-gray-500">{{ player.team_name }}</div>
          </div>

          <div class="text-right w-20 font-mono text-gray-800">
            {{ player.performance_score }}
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { defineProps } from "vue";

const props = defineProps({
  current_round: {
    type: Number,
    required: true,
  },
});

const leaders = ref([]);
const loading = ref(true);
const previousRanks = ref({});
const animationTimers = new Map();

const BASE_KEY = "mvp_leaderboard_ranks";
const getRoundKey = (round) => `${BASE_KEY}_round_${round}`;

const loadPreviousRanks = (round) => {
  const prevRound = round - 1;
  if (prevRound < 1) return {};
  try {
    const saved = localStorage.getItem(getRoundKey(prevRound));
    return saved ? JSON.parse(saved) : {};
  } catch {
    return {};
  }
};

const saveRanks = (round, ranks) => {
  try {
    localStorage.setItem(getRoundKey(round), JSON.stringify(ranks));
  } catch {
    // silent fail
  }
};

const rankChange = (playerId) => {
  const oldRank = previousRanks.value[playerId];
  const newRank = leaders.value.findIndex((p) => p.player_id === playerId);
  if (oldRank === undefined || newRank === -1) return 0;
  return oldRank - newRank;
};

const getAnimationStyle = (playerId) => {
  const change = rankChange(playerId);
  if (change === 0) return {};

  if (!animationTimers.has(playerId)) {
    animationTimers.set(
      playerId,
      setTimeout(() => {
        animationTimers.delete(playerId);
      }, 1500)
    );
  }

  return {
    transform: `translateY(${change * -10}px)`,
    transition: "transform 1s ease",
  };
};

const formatDraftStatus = (status) => {
  return status === "Undrafted" ? "Undrafted" : `${status}`;
};

const fetchMVPLeaders = async () => {
  const round = props.current_round;
  loading.value = true;

  try {
    const response = await axios.get(route("season.mvp.leaders", { round }));
    const data = response.data.data || [];

    previousRanks.value = loadPreviousRanks(round);

    const newRanks = {};
    data.forEach((player, index) => {
      newRanks[player.player_id] = index;
    });

    leaders.value = data;
    saveRanks(round, newRanks);
  } catch (error) {
    console.error("Failed to fetch MVP leaders", error);
    leaders.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchMVPLeaders();
});

watch(() => props.current_round, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    fetchMVPLeaders();
  }
});
</script>

<style scoped>
.mvp-leaderboard li {
  will-change: transform, background-color;
}
</style>
