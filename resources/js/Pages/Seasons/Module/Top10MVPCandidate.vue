<template>
  <div class="mvp-leaderboard max-w-full mx-auto p-4 bg-white">
    <h2 class="text-2xl font-bold mb-4 text-center">Top 10 MVP Candidates</h2>

    <div v-if="loading" class="text-center text-gray-500">Loading...</div>

    <div v-else>
      <div v-if="leaders.length === 0" class="text-center text-red-500 font-semibold">
        No data available
      </div>

      <ul v-else class="space-y-3">
        <li
          v-for="(player, index) in leaders"
          :key="player.player_id"
          class="flex items-center space-x-4 p-3 border rounded shadow-sm transition-transform duration-500"
          :class="{
            'bg-green-100': rankChange(player.player_id) > 0,  // rank rose (better)
            'bg-red-100': rankChange(player.player_id) < 0,    // rank fell (worse)
            'bg-white': rankChange(player.player_id) === 0
          }"
          :style="[
            getAnimationStyle(player.player_id),
            {
              borderLeft: `6px solid #${player.primary_color}`,
              backgroundImage: `linear-gradient(to right, #${player.primary_color}20, #${player.secondary_color}20)`
            }
          ]"
          title="Team colors accent"
        >
          <div class="w-8 text-center font-bold text-lg text-gray-700">{{ index + 1 }}</div>
          <div class="flex-1">
            <div class="font-semibold text-gray-900 flex items-center space-x-2">
              <span>{{ player.player_name }}</span>
              <span v-if="rankChange(player.player_id) > 0" title="Rank rose" class="text-green-600">
                ▲
              </span>
              <span v-else-if="rankChange(player.player_id) < 0" title="Rank fell" class="text-red-600">
                ▼
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
import { ref, onMounted } from "vue";
import axios from "axios";

const leaders = ref([]);
const loading = ref(true);

const previousRanks = ref({}); // { player_id: rank_index }
const animationTimers = new Map();

const STORAGE_KEY = "mvp_leaderboard_ranks";

const loadPreviousRanks = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    return saved ? JSON.parse(saved) : {};
  } catch {
    return {};
  }
};

const saveRanks = (ranks) => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ranks));
  } catch {
    // Fail silently
  }
};

const rankChange = (playerId) => {
  const oldRank = previousRanks.value[playerId];
  const newRank = leaders.value.findIndex((p) => p.player_id === playerId);
  if (oldRank === undefined || newRank === -1) return 0;
  return oldRank - newRank; // positive = rank rose (better), negative = rank fell
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

const fetchMVPLeaders = async () => {
  loading.value = true;
  try {
    const response = await axios.get(route('season.mvp.leaders'));
    const data = response.data.data || [];

    if (Object.keys(previousRanks.value).length === 0) {
      previousRanks.value = loadPreviousRanks();
    }

    const newRanks = {};
    data.forEach((player, index) => {
      newRanks[player.player_id] = index;
    });

    leaders.value = data;
    saveRanks(newRanks);
    previousRanks.value = newRanks;
  } catch (error) {
    console.error("Failed to fetch MVP leaders", error);
    leaders.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchMVPLeaders();
  setInterval(fetchMVPLeaders, 30000);
});
</script>

<style scoped>
.mvp-leaderboard li {
  will-change: transform, background-color;
  /* Added left border as accent via inline style */
}
</style>
