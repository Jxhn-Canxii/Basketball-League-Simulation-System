<template>
  <div class="mt-3">
    <div class="flex border-b mb-2">
      <button
        class="px-4 py-2 focus:outline-none font-semibold text-sm border-b-2"
        :class="
          activeTab === 'games'
            ? 'border-indigo-600 text-indigo-700'
            : 'border-transparent text-gray-500'
        "
        @click="activeTab = 'games'"
      >
        Last 10 Games
      </button>
      <button
        class="px-4 py-2 focus:outline-none font-semibold text-sm border-b-2"
        :class="
          activeTab === 'series'
            ? 'border-indigo-600 text-indigo-700'
            : 'border-transparent text-gray-500'
        "
        @click="activeTab = 'series'"
      >
        Series History
      </button>
      <button
        class="px-4 py-2 focus:outline-none font-semibold text-sm border-b-2"
        :class="
          activeTab === 'trivia'
            ? 'border-indigo-600 text-indigo-700'
            : 'border-transparent text-gray-500'
        "
        @click="activeTab = 'trivia'"
      >
        Trivia
      </button>
    </div>
    <div v-if="activeTab === 'games'">
      <div v-if="!loading && matches && matches.length" class="grid grid-cols-1 gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Last 10 Games</h2>
        <div class="mb-2 text-base font-bold text-center">
          Head to Head:
          <span
            class="text-white px-2 py-1 rounded"
            :style="
              gradientStyle(
                matches[0]?.home_primary_color,
                matches[0]?.home_secondary_color,
                matches[0]?.away_primary_color,
                matches[0]?.away_secondary_color
              )
            "
          >
            {{ matches[0]?.home_name }} {{ homeWins }} - {{ awayWins }}
            {{ matches[0]?.away_name }}
          </span>
        </div>
        <table class="min-w-full divide-y divide-gray-200 p-2">
          <thead class="bg-gray-50 text-nowrap">
            <tr>
              <th
                class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Season
              </th>
              <th
                class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Round
              </th>
              <th
                class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Home Team
              </th>
              <th
                class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Score
              </th>
              <th
                class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Away Team
              </th>
              <th
                class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Result
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="match in matches"
              :key="match.id"
              class="hover:bg-gray-200"
              :style="
                gradientStyle(
                  match.home_primary_color,
                  match.home_secondary_color,
                  match.away_primary_color,
                  match.away_secondary_color
                )
              "
            >
              <td class="px-2 py-2 whitespace-nowrap border">
                Season {{ match.season_name || match.season_id }}
              </td>
              <td class="px-2 py-2 whitespace-nowrap border">
                {{ roundNameFormatter(match.round) }}
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-right">
                <span :class="{ 'font-semibold': match.home_id === match.winner_id }">
                  {{ match.home_name }}
                </span>
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-right">
                <span :class="{ 'font-semibold': match.home_id === match.winner_id }">
                  {{ match.home_score }} - {{ match.away_score }}
                </span>
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-right">
                <span :class="{ 'font-semibold': match.away_id === match.winner_id }">
                  {{ match.away_name }}
                </span>
              </td>
              <td class="px-2 py-2 whitespace-nowrap border">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium"
                  :style="
                    match.winner_id === match.home_id
                      ? gradientStyle(
                          match.home_primary_color,
                          match.home_primary_color
                        )
                      : gradientStyle(
                          match.away_primary_color,
                          match.away_primary_color
                        )
                  "
                >
                  {{ match.season_name ? match.season_name : "" }}
                  {{ match.result_summary || "" }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="loading" class="border p-4 team-stats text-center mt-4">
        <p class="text-gray-500 font-semibold">Loading Matches...</p>
      </div>
      <div v-else class="border p-4 team-stats text-center mt-4">
        <p class="text-gray-500 font-semibold">No Matches Found...</p>
      </div>
    </div>
    <div v-else-if="activeTab === 'series'">
      <div v-if="!loading && series && series.length" class="grid grid-cols-1 gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Series History</h2>
        <table class="min-w-full divide-y divide-gray-200 p-2">
          <thead class="bg-gray-50 text-nowrap">
            <tr>
              <th
                class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Season
              </th>
              <th
                class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Round
              </th>
              <th
                class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Home Team
              </th>
              <th
                class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Away Team
              </th>
              <th
                class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Best Of
              </th>
              <th
                class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Result
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="s in series"
              :key="s.id"
              class="hover:bg-gray-200"
              :style="
                gradientStyle(
                  s.home_primary_color,
                  s.home_secondary_color,
                  s.away_primary_color,
                  s.away_secondary_color
                )
              "
            >
              <td class="px-2 py-2 whitespace-nowrap border">Season {{ s.season_id }}</td>
              <td class="px-2 py-2 whitespace-nowrap border">
                {{ roundNameFormatter(s.round) }}
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-right">
                <span :class="{ 'font-semibold': s.home_team_id === s.winner_team_id }">
                  {{ s.home_name }} ({{ s.home_acronym }})
                </span>
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-right">
                <span :class="{ 'font-semibold': s.away_team_id === s.winner_team_id }">
                  {{ s.away_name }} ({{ s.away_acronym }})
                </span>
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-center">
                {{ s.series_length }}
              </td>
              <td class="px-2 py-2 whitespace-nowrap border text-center">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium"
                  :style="
                    s.winner_team_id === s.home_team_id
                      ? gradientStyle(s.home_primary_color, s.home_primary_color)
                      : gradientStyle(s.away_primary_color, s.away_primary_color)
                  "
                >
                  {{ s.result_summary }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="loading" class="border p-4 team-stats text-center mt-4">
        <p class="text-gray-500 font-semibold">Loading Series...</p>
      </div>
      <div v-else class="border p-4 team-stats text-center mt-4">
        <p class="text-gray-500 font-semibold">No Series Found...</p>
      </div>
    </div>
    <div v-else-if="activeTab === 'trivia'">
      <div v-if="matches.length" class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Home Team Trivia -->
        <div class="trivia-card bg-white rounded-xl shadow-lg p-0 overflow-hidden">
          <div
            class="trivia-header px-4 py-3 text-white font-bold text-lg flex items-center"
            :style="
              gradientStyle(
                matches[0]?.home_primary_color,
                matches[0]?.home_secondary_color
              )
            "
          >
            <i class="fas fa-lightbulb mr-2"></i> {{ matches[0]?.home_name }} Trivia
          </div>
          <ul class="trivia-list px-4 py-4 space-y-2">
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-trophy text-yellow-500 mr-2"></i>
              <span class="font-semibold">Biggest Win Margin:</span>
              <span class="ml-auto">{{ biggestWinMarginHome }} pts</span>
            </li>
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-sad-tear text-blue-400 mr-2"></i>
              <span class="font-semibold">Biggest Losing Margin:</span>
              <span class="ml-auto">{{ biggestLoseMarginHome }} pts</span>
            </li>
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-fire text-red-500 mr-2"></i>
              <span class="font-semibold">Most Consecutive Wins:</span>
              <span class="ml-auto">{{ mostConsecutiveWinsHome }}</span>
            </li>
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-thumbs-down text-gray-500 mr-2"></i>
              <span class="font-semibold">Most Consecutive Losses:</span>
              <span class="ml-auto">{{ mostConsecutiveLossesHome }}</span>
            </li>
          </ul>
        </div>

        <!-- Away Team Trivia -->
        <div class="trivia-card bg-white rounded-xl shadow-lg p-0 overflow-hidden">
          <div
            class="trivia-header px-4 py-3 text-white font-bold text-lg flex items-center"
            :style="
              gradientStyle(
                matches[0]?.away_primary_color,
                matches[0]?.away_secondary_color
              )
            "
          >
            <i class="fas fa-lightbulb mr-2"></i> {{ matches[0]?.away_name }} Trivia
          </div>
          <ul class="trivia-list px-4 py-4 space-y-2">
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-trophy text-yellow-500 mr-2"></i>
              <span class="font-semibold">Biggest Win Margin:</span>
              <span class="ml-auto">{{ biggestWinMarginAway }} pts</span>
            </li>
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-sad-tear text-blue-400 mr-2"></i>
              <span class="font-semibold">Biggest Losing Margin:</span>
              <span class="ml-auto">{{ biggestLoseMarginAway }} pts</span>
            </li>
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-fire text-red-500 mr-2"></i>
              <span class="font-semibold">Most Consecutive Wins:</span>
              <span class="ml-auto">{{ mostConsecutiveWinsAway }}</span>
            </li>
            <li class="flex items-center trivia-item rounded hover:bg-gray-50 transition">
              <i class="fas fa-thumbs-down text-gray-500 mr-2"></i>
              <span class="font-semibold">Most Consecutive Losses:</span>
              <span class="ml-auto">{{ mostConsecutiveLossesAway }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div v-else class="border p-4 team-stats text-center mt-4">
        <p class="text-gray-500 font-semibold">No Trivia Found...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { roundNameFormatter, gradientStyle } from "@/Utility/Formatter";
const props = defineProps({
  home_id: { type: Number, required: true },
  away_id: { type: Number, required: true },
  season_id: { type: Number, required: true },
});
const matches = ref([]);
const series = ref([]);
const loading = ref(true);
const activeTab = ref("games");

const homeWins = computed(
  () => matches.value.filter((m) => m.home_id === m.winner_id).length
);
const awayWins = computed(
  () => matches.value.filter((m) => m.away_id === m.winner_id).length
);

const biggestWinMarginHome = computed(() => {
  return Math.max(
    ...matches.value
      .filter((m) => m.home_id === m.winner_id)
      .map((m) => m.home_score - m.away_score),
    0
  );
});
const biggestWinMarginAway = computed(() => {
  return Math.max(
    ...matches.value
      .filter((m) => m.away_id === m.winner_id)
      .map((m) => m.away_score - m.home_score),
    0
  );
});
const biggestLoseMarginHome = computed(() => {
  return Math.max(
    ...matches.value
      .filter((m) => m.home_id !== m.winner_id)
      .map((m) => m.away_score - m.home_score),
    0
  );
});
const biggestLoseMarginAway = computed(() => {
  return Math.max(
    ...matches.value
      .filter((m) => m.away_id !== m.winner_id)
      .map((m) => m.home_score - m.away_score),
    0
  );
});
const mostPointsScored = computed(() => {
  return Math.max(...matches.value.map((m) => Math.max(m.home_score, m.away_score)), 0);
});
const lowestPointsScored = computed(() => {
  return Math.min(...matches.value.map((m) => Math.min(m.home_score, m.away_score)), 0);
});

function getStreak(arr, teamKey, winKey) {
  let maxWin = 0,
    maxLoss = 0,
    curWin = 0,
    curLoss = 0;
  for (let i = 0; i < arr.length; i++) {
    if (arr[i][teamKey] === arr[i][winKey]) {
      curWin++;
      maxWin = Math.max(maxWin, curWin);
      curLoss = 0;
    } else {
      curLoss++;
      maxLoss = Math.max(maxLoss, curLoss);
      curWin = 0;
    }
  }
  return { maxWin, maxLoss };
}
const mostConsecutiveWinsHome = computed(() => {
  return getStreak(matches.value, "home_id", "winner_id").maxWin;
});
const mostConsecutiveWinsAway = computed(() => {
  return getStreak(matches.value, "away_id", "winner_id").maxWin;
});
const mostConsecutiveLossesHome = computed(() => {
  return getStreak(matches.value, "home_id", "winner_id").maxLoss;
});
const mostConsecutiveLossesAway = computed(() => {
  return getStreak(matches.value, "away_id", "winner_id").maxLoss;
});

const getMatchAndSeriesHistory = async () => {
  loading.value = true;
  try {
    const response = await axios.post(route("match.history"), {
      home_id: props.home_id,
      away_id: props.away_id,
      season_id: props.season_id,
    });
    matches.value = response.data.matches || [];
    series.value = response.data.series || [];
  } catch (error) {
    console.error("Error fetching match and series history:", error);
    matches.value = [];
    series.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getMatchAndSeriesHistory();
});
</script>

<style scoped>
.team-stats .stat {
  margin-bottom: 8px;
  display: flex;
  justify-content: space-between;
}
button {
  transition: color 0.2s, border-color 0.2s;
}
.trivia-card {
  background: linear-gradient(135deg, #f8fafc 60%, #e0e7ef 100%);
  border: 1px solid #e5e7eb;
}
.trivia-header {
  border-bottom: 1px solid #e5e7eb;
  letter-spacing: 1px;
}
.trivia-list {
  padding-top: 1rem;
  padding-bottom: 1rem;
}
.trivia-item {
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  transition: background 0.2s;
}
</style>
