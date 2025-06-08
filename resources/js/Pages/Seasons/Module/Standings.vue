<template>
  <div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <!-- Loading State -->
    <div v-if="loadingStandings" class="p-8 text-center">
      <div class="animate-pulse flex flex-col items-center">
        <div class="w-12 h-12 rounded-full bg-gray-200 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!season_standings?.standings?.length" class="p-8 text-center text-gray-500">
      <i class="fas fa-chart-bar text-3xl mb-2"></i>
      <p>No standings available</p>
    </div>

    <!-- Standings Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-nowrap">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
            <th scope="col" class="px-4 py-2 text-left text-xs text-yellow-500 font-medium text-gray-500 uppercase tracking-wider">W</th>
            <th scope="col" class="px-4 py-2 text-left text-xs text-red-500 font-medium text-gray-500 uppercase tracking-wider">L</th>
            <!-- <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PPG</th> -->
            <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">OVR</th>
            <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">LAST 5 GAMES</th>
          </tr>
        </thead>
        <transition-group 
          tag="tbody"
          name="rank-change"
          class="bg-white relative"
          @before-enter="beforeEnter"
          @enter="enter"
          @leave="leave"
        >
          <tr 
            v-for="(team, index) in season_standings.standings" 
            :key="team.team_id"
            :class="[getTeamRowClass(index), getMovementClass(team.team_id)]"
            :data-prev-rank="getPreviousRank(team.team_id)"
            class="transition-all duration-500 ease-in-out"
          >
            <td class="px-4 py-2 whitespace-nowrap">
              <div class="flex items-center">
                <TeamDetails
                  :title="'Playoff Appearances: ' + team.playoff_appearances"
                  :team_id="team.team_id"
                  :showInfo="props.showLegend"
                  :current_conference_rank="team.conference_rank"
                  :season_id="team.season_id"
                  class="text-sm text-black"
                  :hexPrimaryColor="team.primary_color"
                  :hexSecondaryColor="team.secondary_color"
                  :showButton="0"
                  :text="`${team.team_city} ${team.team_name}`"
                />
                <span v-if="team.is_defending_champion == 1">
                  <i class="fa fa-trophy text-yellow-600"></i>
                </span>
                <span 
                  v-if="showRankChange(team.team_id)"
                  class="ml-2 text-sm font-medium"
                  :class="getChangeColor(team.team_id)"
                >
                  {{ getChangeSymbol(team.team_id) }}
                </span>
              </div>
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ team.wins }}</td>
            <td class="px-4 py-2 whitespace-nowrap text-sm">{{ team.losses }}</td>
            <!-- <td class="px-4 py-2 whitespace-nowrap text-sm">
              {{ 
                (team.wins + team.losses > 0
                  ? ((parseFloat(team.total_home_score ?? 0) + parseFloat(team.total_away_score ?? 0)) / (parseFloat(team.wins ?? 0) + parseFloat(team.losses ?? 0))).toFixed(2)
                  : '0.00')
              }}
            </td> -->
            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">{{ team.overall_rank }}</td>
            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
              <div class="flex justify-center space-x-1">
                <template v-for="(result, index) in team.last_5_games?.split('').reverse()" :key="index">
                  <span :class="{
                    'bg-green-100 text-green-800 px-1 rounded-full text-xs font-semibold': result === 'W',
                    'bg-red-100 text-red-800 px-1 rounded-full': result === 'L',
                    'bg-gray-100 text-gray-500 px-1 rounded': !['W', 'L'].includes(result)
                  }">
                    <span v-if="result === 'W'">✓</span>
                    <span v-else-if="result === 'L'">✕</span>
                    <span v-else>-</span>
                  </span>
                </template>
              </div>
            </td>
            <!-- <td class="px-4 py-2 whitespace-nowrap">
              <div class="grid grid-cols-3 gap-1 place-items-center w-fit mx-auto text-xs">
                <Achievement v-if="team.championships > 0" 
                           type="championship" 
                           :count="team.championships" />
                <Achievement v-if="team.conference_championships > 0" 
                           type="conference" 
                           :count="team.conference_championships" />
                <Achievement v-if="team.overall_1_rank > 0" 
                           type="overall" 
                           :count="team.overall_1_rank" />
                <Achievement v-if="team.conference_1_rank > 0" 
                           type="conference_best" 
                           :count="team.conference_1_rank" />
                <Achievement v-if="team.is_grandslam > 0" 
                           type="grandslam" 
                           :count="team.is_grandslam" />
              </div>
            </td> -->
          </tr>
        </transition-group>
      </table>
    </div>

    <!-- Season Summary -->
    <div v-if="season_info.seasons" class="bg-gray-50 p-4 space-y-3">
      <h3 class="font-semibold text-gray-700 mb-2">Season Highlights</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <SummaryItem icon="trophy" label="Finals Champion" 
                      :teamId="season_info.seasons[0].finals_winner_id"
                      :teamName="season_info.seasons[0].finals_winner_name" />
          <SummaryItem icon="medal" label="Finals Runner Up"
                      :teamId="season_info.seasons[0].finals_loser_id"
                      :teamName="season_info.seasons[0].finals_loser_name" />
        </div>
        <div class="space-y-2">
          <SummaryItem icon="crown" label="Regular Season Champion"
                      :teamId="season_info.seasons[0].champion_id"
                      :teamName="season_info.seasons[0].champion_name" />
          <SummaryItem icon="exclamation-circle" label="Lowest Ranked"
                      :teamId="season_info.seasons[0].weakest_id"
                      :teamName="season_info.seasons[0].weakest_name" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import Achievement from "@/Pages/Seasons/Module/Achievement.vue";
import SummaryItem from "@/Pages/Seasons/Module/SummaryItem.vue";

const season_standings = ref(false);
const loadingStandings = ref(false);
const season_info = ref([]);
const previousStandings = ref([]);
const props = defineProps({
  showLegend: {
    type: Boolean,
    default: false,
  },
  season_id: {
    type: [Number, String],
    required: true,
  },
  conference_id: {
    type: [Number, String],
    required: true,
  },
  season_data: Object,
});

onMounted(() => {
  getStandingsInfo();
});

const getStandingsInfo = () => {
  season_info.value = props.season_data;
  fetchConferenceStandings();
};

const fetchConferenceStandings = async () => {
  try {
    const standingsKey = `previousStandings_${props.conference_id}`;
    const historyKey = `rankHistory_${props.conference_id}`;
    const saved = localStorage.getItem(standingsKey);
    const savedHistory = localStorage.getItem(historyKey);

    if (saved) previousStandings.value = JSON.parse(saved);

    let rankHistory = savedHistory ? JSON.parse(savedHistory) : {};

    loadingStandings.value = true;
    season_standings.value = [];

    const response = await axios.post(route("conferences.standings"), {
      season_id: props.season_id,
      conference_id: props.conference_id,
    });

    season_standings.value = response.data;

    // Save current standings and update rank history
    if (response.data?.standings) {
      response.data.standings.forEach(team => {
        const teamId = team.team_id;
        const currentRank = team.conference_rank;

        if (!rankHistory[teamId]) {
          rankHistory[teamId] = [];
        }

        const history = rankHistory[teamId];
        const lastRank = history[history.length - 1];

        if (lastRank !== currentRank) {
          history.push(currentRank);

          // Limit to last 10 entries
          if (history.length > 10) history.shift();
        }
      });

      localStorage.setItem(standingsKey, JSON.stringify(response.data.standings));
      localStorage.setItem(historyKey, JSON.stringify(rankHistory));
    }

    loadingStandings.value = false;
  } catch (error) {
    console.error("Error fetching season standings:", error);
  }
};

// GSAP animations
const beforeEnter = (el) => {
  el.style.opacity = 0;
  el.style.transform = "translateY(20px)";
};
const enter = (el, done) => {
  gsap.to(el, {
    duration: 0.5,
    y: 0,
    opacity: 1,
    ease: "power2.out",
    onComplete: done,
  });
};
const leave = (el, done) => {
  gsap.to(el, {
    duration: 0.3,
    y: -20,
    opacity: 0,
    ease: "power2.in",
    onComplete: done,
  });
};

// Helpers using previous standings (optional if still needed)
const getPreviousRank = (teamId) => {
  const prevTeam = previousStandings.value?.find(t => t.team_id === teamId);
  return prevTeam ? prevTeam.conference_rank : null;
};

const showRankChange = (teamId) => {
  const trend = getRankTrend(teamId);
  return trend && trend.symbol !== "-";
};

// 🚀 New helpers using rankHistory
const getRankTrend = (teamId) => {
  const historyKey = `rankHistory_${props.conference_id}`;
  const history = JSON.parse(localStorage.getItem(historyKey) || "{}");
  const ranks = history[teamId] || [];

  if (ranks.length < 2) return null;

  const prev = ranks[ranks.length - 2];
  const current = ranks[ranks.length - 1];
  const diff = prev - current;

  return {
    symbol: diff > 0 ? `↑${diff}` : diff < 0 ? `↓${Math.abs(diff)}` : "-",
    color: diff > 0 ? "text-green-600" : diff < 0 ? "text-red-600" : "text-gray-500"
  };
};

const getChangeSymbol = (teamId) => getRankTrend(teamId)?.symbol || "";
const getChangeColor = (teamId) => getRankTrend(teamId)?.color || "";

const getMovementClass = (teamId) => {
  const trend = getRankTrend(teamId);
  if (!trend || trend.symbol === "-") return "";
  return trend.symbol.startsWith("↑") ? "animate-rise" : "animate-fall";
};

const getTeamRowClass = (index) => {
  const baseClass =
    index <= 5
      ? 'bg-orange-50 hover:bg-orange-200'
      : index <= 9
      ? 'bg-blue-50 hover:bg-blue-200'
      : 'bg-red-50 hover:bg-red-200';

  const baseBorder =
    index <= 5
      ? 'border-l-4 border-orange-500'
      : index <= 9
      ? 'border-l-4 border-blue-500'
      : 'border-l-4 border-red-500';

  const topBorder =
    index === 6
      ? 'border-t-2 border-t-orange-500 border-dashed'
      : index === 10
      ? 'border-t-2 border-t-blue-500 border-dashed'
      : '';

  const borderStyleFix = (index === 6 || index === 10) ? 'border-l-solid' : '';

  return `${baseClass} ${baseBorder} ${topBorder} ${borderStyleFix}`.trim();
};
</script>

<style>
  .rank-change-move {
    transition: all 0.5s ease-in-out;
  }

  @keyframes rise {
    from {
      transform: translateY(20px);
      opacity: 0;
      background-color: rgba(72, 187, 120, 0.1);
    }
    to {
      transform: translateY(0);
      opacity: 1;
      background-color: transparent;
    }
  }

  @keyframes fall {
    from {
      transform: translateY(-20px);
      opacity: 0;
      background-color: rgba(248, 113, 113, 0.1);
    }
    to {
      transform: translateY(0);
      opacity: 1;
      background-color: transparent;
    }
  }

  .animate-rise {
    animation: rise 0.5s ease-out;
  }

  .animate-fall {
    animation: fall 0.5s ease-out;
  }
</style>