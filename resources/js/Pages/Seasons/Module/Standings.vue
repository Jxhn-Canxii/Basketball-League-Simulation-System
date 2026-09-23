<template>
  <div
    class="standings-shell rounded-lg overflow-hidden border border-gray-800 bg-gray-950 shadow-2xl shadow-black/40"
  >
    <!-- Header -->
    <div
      class="flex items-center hidden justify-between p-0 border-b border-gray-800 bg-gray-950"
    >
      <div class="flex items-center gap-2 min-w-0">
        <div
          class="w-7 h-7 rounded-md bg-gray-900 border border-gray-800 flex items-center justify-center shrink-0"
        >
          <i class="fas fa-chart-line text-gray-300 text-xs"></i>
        </div>

        <div class="min-w-0">
          <h2 class="text-sm font-semibold text-gray-100 truncate">
            Conference Standings
          </h2>

          <p class="text-xs text-gray-500">
            Current season
          </p>
        </div>
      </div>

      <div
        v-if="season_standings?.standings?.length"
        class="text-xs text-gray-500 shrink-0"
      >
        {{ season_standings.standings.length }} Teams
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loadingStandings" class="p-6 bg-gray-950">
      <div class="space-y-2 animate-pulse">
        <div
          v-for="n in 10"
          :key="n"
          class="h-8 rounded bg-gray-900 border border-gray-800"
        ></div>
      </div>
    </div>

    <!-- Empty -->
    <div
      v-else-if="!season_standings?.standings?.length"
      class="p-8 text-center bg-gray-950 text-gray-500"
    >
      <div
        class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-900 border border-gray-800 flex items-center justify-center"
      >
        <i class="fas fa-chart-bar text-gray-600 text-lg"></i>
      </div>

      <p class="text-sm">
        No standings available
      </p>
    </div>

    <!-- Standings -->
    <div v-else class="standings-container">
      <table class="w-full table-fixed border-collapse text-nowrap">
        <colgroup>
          <col class="team-column" />
          <col class="stat-column" />
          <col class="stat-column" />
          <col class="stat-column" />
          <col class="stat-column" />
          <col class="last-five-column" />
          <col class="stat-column" />
          <col class="next-column" />
        </colgroup>

        <!-- Table Header -->
        <thead class="bg-gray-900 border-b border-gray-800">
          <tr>
            <th
              scope="col"
              class="px-2 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider"
            >
              Team
            </th>

            <th
              scope="col"
              class="px-1 py-2 text-center text-xs text-blue-400 font-medium uppercase tracking-wider"
            >
              W
            </th>

            <th
              scope="col"
              class="px-1 py-2 text-center text-xs text-red-400 font-medium uppercase tracking-wider"
            >
              L
            </th>

            <th
              scope="col"
              class="px-1 py-2 text-center text-xs font-medium text-gray-400 uppercase tracking-wider"
            >
              OVR
            </th>

            <th
              scope="col"
              class="px-1 py-2 text-center text-xs font-medium text-gray-400 uppercase tracking-wider"
            >
              STRK
            </th>

            <th
              scope="col"
              class="px-1 py-2 text-center text-xs font-medium text-gray-400 uppercase tracking-wider"
            >
              LAST 5 GAMES
            </th>

            <th
              scope="col"
              class="px-1 py-2 text-center text-xs font-medium text-gray-400 uppercase tracking-wider"
            >
              DIFF
            </th>

            <th
              scope="col"
              class="px-2 py-2 text-left text-xs font-medium text-gray-400 uppercase tracking-wider"
            >
              NEXT
            </th>
          </tr>
        </thead>

        <transition-group
          tag="tbody"
          name="rank-change"
        >
          <tr
            v-for="(team, index) in season_standings.standings"
            :key="team.team_id"
            :class="[
              getTeamRowClass(team),
              getMovementClass(team.team_id)
            ]"
            :data-prev-rank="getPreviousRank(team.team_id)"
            class="standings-row"
          >
            <!-- Team -->
            <td class="px-2 py-2">
              <div class="flex items-center min-w-0">
                <!-- Chemistry -->
                <span
                  class="w-4 shrink-0 flex items-center justify-center"
                  :title="`${getChemistryTitle(team.chemistry)} ${team.chemistry}%`"
                >
                  <i
                    :class="getChemistryIcon(team.chemistry)"
                    class="text-xs"
                  ></i>
                </span>

                <!-- Team -->
                <Tooltip
                  :content="teamAchievements(team)"
                  class="ml-2 min-w-0"
                >
                  <TeamDetails
                    :team_id="team.team_id"
                    :showInfo="props.showLegend"
                    :current_conference_rank="team.conference_rank"
                    :season_id="team.season_id"
                    class="team-name"
                    :textColor="'ffffff'"
                    :hexPrimaryColor="'team.primary_color'"
                    :hexSecondaryColor="team.secondary_color"
                    :showButton="0"
                    :text="`${team.team_city} ${team.team_name}`"
                  />
                </Tooltip>

                <!-- Champion -->
                <span
                  v-if="team.is_defending_champion == 1"
                  class="ml-1.5 shrink-0"
                  title="Defending Champion"
                >
                  <i
                    class="fa fa-trophy text-yellow-500 text-xs"
                  ></i>
                </span>

                <!-- Rank Movement -->
                <span
                  v-if="showRankChange(team.team_id)"
                  class="ml-1.5 text-sm font-semibold shrink-0"
                  :class="getChangeColor(team.team_id)"
                >
                  {{ getChangeSymbol(team.team_id) }}
                </span>
              </div>
            </td>

            <!-- Wins -->
            <td
              class="px-1 py-2 text-center text-sm font-semibold text-blue-400"
            >
              {{ team.wins }}
            </td>

            <!-- Losses -->
            <td
              class="px-1 py-2 text-center text-sm font-semibold text-red-400"
            >
              {{ team.losses }}
            </td>

            <!-- Overall -->
            <td
              class="px-1 py-2 text-center text-gray-400 text-xs font-medium"
            >
              {{ team.overall_rank }}
            </td>

            <!-- Streak -->
            <td class="px-1 py-2 text-center text-sm">
              <span
                class="font-bold"
                :class="
                  team.streak_status &&
                  team.streak_status
                    .toLowerCase()
                    .startsWith('w')
                    ? 'text-green-400'
                    : 'text-red-400'
                "
              >
                {{ team.streak_status }}
              </span>
            </td>

            <!-- Last 5 -->
            <td class="px-1 py-2">
              <div
                class="flex justify-center items-center gap-0.5"
              >
                <template
                  v-for="(result, resultIndex) in team.last_5_games
                    ?.split('')
                    .reverse()"
                  :key="resultIndex"
                >
                  <span
                    class="last-five-result"
                    :class="{
                      'result-win': result === 'W',
                      'result-loss': result === 'L',
                      'result-empty':
                        !['W', 'L'].includes(result),
                    }"
                    :title="
                      result === 'W'
                        ? 'Win'
                        : result === 'L'
                        ? 'Loss'
                        : 'No result'
                    "
                  >
                    <span v-if="result === 'W'">
                      ✓
                    </span>

                    <span v-else-if="result === 'L'">
                      ✕
                    </span>

                    <span v-else>
                      -
                    </span>
                  </span>
                </template>
              </div>
            </td>

            <!-- Difference -->
            <td
              class="px-1 py-2 text-center text-xs font-semibold"
              :class="
                team.score_difference < 0
                  ? 'text-red-400'
                  : 'text-green-400'
              "
            >
              {{ team.score_difference }}
            </td>

            <!-- Next -->
            <td
              class="px-2 py-2 text-left text-pink-400 text-xs font-semibold truncate"
            >
              {{ team.next_opponent_acronym || "-" }}
            </td>
          </tr>
        </transition-group>
      </table>
    </div>

    <!-- Latest News -->
    <div
      v-if="
        season_standings.latest_news &&
        season_info?.[0]?.status < 2
      "
      class="hidden"
    >
      <GameNews :data="season_standings.latest_news" />
    </div>

    <!-- Season Summary -->
    <div
      v-if="season_info?.seasons"
      class="border-t border-gray-800 bg-gray-950 p-3"
    >
      <div class="flex items-center gap-2 mb-3">
        <div
          class="w-6 h-6 rounded bg-gray-900 border border-gray-800 flex items-center justify-center"
        >
          <i
            class="fas fa-star text-yellow-500 text-xs"
          ></i>
        </div>

        <h3 class="text-sm font-semibold text-gray-200">
          Season Highlights
        </h3>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <SummaryItem
          icon="trophy"
          label="Finals Champion"
          :teamId="season_info.seasons[0].finals_winner_id"
          :teamName="season_info.seasons[0].finals_winner_name"
        />

        <SummaryItem
          icon="medal"
          label="Finals Runner Up"
          :teamId="season_info.seasons[0].finals_loser_id"
          :teamName="season_info.seasons[0].finals_loser_name"
        />

        <SummaryItem
          icon="crown"
          label="Regular Season Champion"
          :teamId="season_info.seasons[0].champion_id"
          :teamName="season_info.seasons[0].champion_name"
        />

        <SummaryItem
          icon="exclamation-circle"
          label="Lowest Ranked"
          :teamId="season_info.seasons[0].weakest_id"
          :teamName="season_info.seasons[0].weakest_name"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

import {
  numberFormatter,
  formatHex,
} from "@/Utility/Formatter.js";

import Tooltip from "@/Components/Tooltip.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import SummaryItem from "@/Pages/Seasons/Module/SummaryItem.vue";
import GameNews from "@/Pages/Seasons/Module/GameNews.vue";

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
    const standingsKey =
      `previousStandings_${props.conference_id}`;

    const historyKey =
      `rankHistory_${props.conference_id}`;

    const saved =
      localStorage.getItem(standingsKey);

    const savedHistory =
      localStorage.getItem(historyKey);

    if (saved) {
      previousStandings.value =
        JSON.parse(saved);
    }

    let rankHistory = savedHistory
      ? JSON.parse(savedHistory)
      : {};

    loadingStandings.value = true;
    season_standings.value = [];

    const response = await axios.post(
      route("conferences.standings"),
      {
        season_id: props.season_id,
        conference_id: props.conference_id,
      }
    );

    season_standings.value =
      response.data;

    if (response.data?.standings) {
      response.data.standings.forEach((team) => {
        const teamId = team.team_id;
        const currentRank =
          team.conference_rank;

        if (!rankHistory[teamId]) {
          rankHistory[teamId] = [];
        }

        const history =
          rankHistory[teamId];

        const lastRank =
          history[history.length - 1];

        if (lastRank !== currentRank) {
          history.push(currentRank);

          if (history.length > 10) {
            history.shift();
          }
        }
      });

      localStorage.setItem(
        standingsKey,
        JSON.stringify(
          response.data.standings
        )
      );

      localStorage.setItem(
        historyKey,
        JSON.stringify(rankHistory)
      );
    }

    loadingStandings.value = false;
  } catch (error) {
    console.error(
      "Error fetching season standings:",
      error
    );

    loadingStandings.value = false;
  }
};

/*
|--------------------------------------------------------------------------
| Rank Helpers
|--------------------------------------------------------------------------
*/

const getPreviousRank = (teamId) => {
  const prevTeam =
    previousStandings.value?.find(
      (team) =>
        team.team_id === teamId
    );

  return prevTeam
    ? prevTeam.conference_rank
    : null;
};

const getRankTrend = (teamId) => {
  const historyKey =
    `rankHistory_${props.conference_id}`;

  const history = JSON.parse(
    localStorage.getItem(historyKey) ||
      "{}"
  );

  const ranks =
    history[teamId] || [];

  if (ranks.length < 2) {
    return null;
  }

  const prev =
    ranks[ranks.length - 2];

  const current =
    ranks[ranks.length - 1];

  const diff =
    prev - current;

  return {
    symbol:
      diff > 0
        ? `↑${diff}`
        : diff < 0
        ? `↓${Math.abs(diff)}`
        : "-",

    color:
      diff > 0
        ? "text-green-400"
        : diff < 0
        ? "text-red-400"
        : "text-gray-500",
  };
};

const showRankChange = (teamId) => {
  const trend =
    getRankTrend(teamId);

  return (
    trend &&
    trend.symbol !== "-"
  );
};

const getChangeSymbol = (teamId) =>
  getRankTrend(teamId)?.symbol || "";

const getChangeColor = (teamId) =>
  getRankTrend(teamId)?.color || "";

const getMovementClass = (teamId) => {
  const trend =
    getRankTrend(teamId);

  if (
    !trend ||
    trend.symbol === "-"
  ) {
    return "";
  }

  return trend.symbol.startsWith("↑")
    ? "rank-rise"
    : "rank-fall";
};

/*
|--------------------------------------------------------------------------
| Playoff / Play-In / Eliminated Zone
|--------------------------------------------------------------------------
|
| Rank 1 - 6  = Direct Playoffs
| Rank 7 - 10 = Play-In
| Rank 11+    = Eliminated
|
*/

const getTeamRowClass = (team) => {
  const rank =
    Number(team?.conference_rank);

  let zoneClass = "";

  if (rank >= 1 && rank <= 6) {
    zoneClass =
      "standings-playoff";
  } else if (rank >= 7 && rank <= 10) {
    zoneClass =
      "standings-playin";
  } else if (rank >= 11) {
    zoneClass =
      "standings-eliminated";
  }

  let separatorClass = "";

  /*
   * Separator immediately before rank 7.
   * This separates Playoffs from Play-In.
   */
  if (rank === 7) {
    separatorClass =
      "conference-separator separator-orange";
  }

  /*
   * Separator immediately before rank 11.
   * This separates Play-In from Eliminated.
   */
  if (rank === 11) {
    separatorClass =
      "conference-separator separator-blue";
  }

  return [
    zoneClass,
    separatorClass,
  ]
    .filter(Boolean)
    .join(" ");
};

/*
|--------------------------------------------------------------------------
| Chemistry
|--------------------------------------------------------------------------
*/

const getChemistryIcon = (chemistry) => {
  if (chemistry >= 80) {
    return "fa-solid fa-face-laugh-beam text-yellow-400";
  }

  if (chemistry >= 60) {
    return "fa-solid fa-face-smile text-green-400";
  }

  if (chemistry >= 40) {
    return "fa-solid fa-face-meh text-gray-400";
  }

  if (chemistry >= 20) {
    return "fa-solid fa-face-frown text-orange-400";
  }

  return "fa-solid fa-face-angry text-red-500";
};

const getChemistryTitle = (chemistry) => {
  if (chemistry >= 80)
    return "Excellent Chemistry";

  if (chemistry >= 60)
    return "Good Chemistry";

  if (chemistry >= 40)
    return "Average Chemistry";

  if (chemistry >= 20)
    return "Poor Chemistry";

  return "Very Poor Chemistry";
};

/*
|--------------------------------------------------------------------------
| Team Tooltip
|--------------------------------------------------------------------------
*/

const teamAchievements = (team) => {
  if (!team) {
    return "";
  }

  let achievementsRows = "";

  if (team.playoff_appearances > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-trophy text-yellow-300"></span>
        <span class="font-semibold text-indigo-100">
          ${team.playoff_appearances}x Playoff Appearances
        </span>
      </div>
    `;
  }

  if (team.last_playoff_season_name) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-history text-indigo-200"></span>
        <span class="font-semibold text-indigo-100">
          Last Playoff:
        </span>
        <span class="text-indigo-100">
          ${team.last_playoff_season_name}
        </span>
      </div>
    `;
  }

  if (team.conference_finals_appearances > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-flag-checkered text-indigo-200"></span>
        <span class="font-semibold text-indigo-100">
          ${team.conference_finals_appearances}x Conference Finals Appearances
        </span>
      </div>
    `;
  }

  if (team.conference_championships > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-trophy text-yellow-300"></span>
        <span class="font-semibold text-indigo-100">
          ${team.conference_championships}x Conference Championships
        </span>
      </div>
    `;
  }

  if (team.finals_appearances > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-medal text-indigo-200"></span>
        <span class="font-semibold text-indigo-100">
          ${team.finals_appearances}x National Finals Appearances
        </span>
      </div>
    `;
  }

  if (team.championships > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-trophy text-yellow-300"></span>
        <span class="font-semibold text-indigo-100">
          ${team.championships}x National Championships
        </span>
      </div>
    `;
  }

  if (team.overall_rank_count > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-star text-yellow-200"></span>
        <span class="font-semibold text-indigo-100">
          ${team.overall_rank_count}x National Ranked #1
        </span>
      </div>
    `;
  }

  if (team.conference_rank_count > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-star text-yellow-200"></span>
        <span class="font-semibold text-indigo-100">
          ${team.conference_rank_count}x Conference Ranked #1
        </span>
      </div>
    `;
  }

  if (team.conference_top_3_count > 0) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-medal text-indigo-200"></span>
        <span class="font-semibold text-indigo-100">
          ${team.conference_top_3_count}x Conference Ranked Top 3
        </span>
      </div>
    `;
  }

  if (team.estimated_fans) {
    achievementsRows += `
      <div class="flex items-center gap-2 mb-1">
        <span class="fa fa-users text-yellow-200"></span>
        <span class="font-semibold text-indigo-100">
          Estimated Fans:
        </span>
        <span class="text-indigo-100">
          ${numberFormatter(team.estimated_fans)} Fans
        </span>
      </div>
    `;
  }

  const buildPlayersBlock = (
    data,
    icon,
    iconColor,
    title
  ) => {
    if (!data) {
      return "";
    }

    const players = data
      .split("%%")
      .map((player) => player.trim())
      .filter(Boolean);

    if (!players.length) {
      return "";
    }

    return `
      <div class="flex items-center gap-2 mt-2 mb-1">
        <span class="fa ${icon} ${iconColor}"></span>

        <span class="font-semibold text-indigo-100">
          ${title}:
        </span>
      </div>

      <div class="ml-7 flex flex-col gap-1">
        ${players
          .map(
            (player) => `
              <span class="text-indigo-100 whitespace-normal">
                ${player}
              </span>
            `
          )
          .join("")}
      </div>
    `;
  };

  const topPlayersBlock =
    buildPlayersBlock(
      team.top_players,
      "fa-user-secret",
      "text-yellow-200",
      "Top 3 Players"
    );

  const newPlayersBlock =
    buildPlayersBlock(
      team.new_players,
      "fa-user-plus",
      "text-green-200",
      "New Players"
    );

  const rookiesBlock =
    buildPlayersBlock(
      team.rookies,
      "fa-user-plus",
      "text-yellow-200",
      "Rookies"
    );

  const reservedBlock =
    buildPlayersBlock(
      team.reserved,
      "fa-user-plus",
      "text-yellow-200",
      "Reserved Players"
    );

  const injuryBlock =
    buildPlayersBlock(
      team.injured_players,
      "fa-ambulance",
      "text-red-400",
      "Injury Report"
    );

  if (
    !achievementsRows &&
    !topPlayersBlock &&
    !newPlayersBlock &&
    !rookiesBlock &&
    !reservedBlock &&
    !injuryBlock
  ) {
    return "";
  }

  const primary =
    team.primary_color
      ? formatHex(team.primary_color)
      : "#111827";

  const secondary =
    team.secondary_color
      ? formatHex(team.secondary_color)
      : "#312e81";

  const cardBg = `
    background:
      linear-gradient(
        135deg,
        ${primary} 0%,
        #111827 65%,
        ${secondary} 100%
      );
  `;

  return `
    <div
      class="p-3 text-sm rounded-lg shadow-xl border border-white/10 inline-block w-auto max-w-[100vw]"
      style="${cardBg}"
    >
      <h3
        class="text-lg font-bold mb-2 flex items-center gap-2 text-yellow-200"
      >
        <span class="fa fa-basketball-ball"></span>

        <span class="text-indigo-100">
          #${team.team_id} |
          ${team.team_city} ${team.team_name}
        </span>
      </h3>

      <div class="grid grid-cols-1 gap-1">
        ${achievementsRows}
        ${topPlayersBlock}
        ${newPlayersBlock}
        ${rookiesBlock}
        ${reservedBlock}
        ${injuryBlock}
      </div>
    </div>
  `;
};
</script>

<style>
/*
|--------------------------------------------------------------------------
| Main Table
|--------------------------------------------------------------------------
*/

.standings-shell {
  width: 100%;
  max-width: 100%;
}

.standings-container {
  width: 100%;
  overflow: hidden;
}

.standings-container table {
  width: 100%;
  table-layout: fixed;
}

/*
|--------------------------------------------------------------------------
| Column Sizing
|--------------------------------------------------------------------------
*/

.team-column {
  width: 34%;
}

.stat-column {
  width: 7%;
}

.last-five-column {
  width: 14%;
}

.next-column {
  width: 10%;
}

/*
|--------------------------------------------------------------------------
| Rows
|--------------------------------------------------------------------------
*/

.standings-row {
  height: 38px;

  border-bottom:
    1px solid rgba(55, 65, 81, 0.55);

  background:
    rgba(17, 24, 39, 0.92);

  transition:
    background-color 180ms ease,
    box-shadow 180ms ease,
    transform 180ms ease;
}

.standings-row:hover {
  box-shadow:
    inset 0 0 0 1px
      rgba(255, 255, 255, 0.04),
    0 2px 8px
      rgba(0, 0, 0, 0.25);
}

/*
|--------------------------------------------------------------------------
| PLAYOFFS — Ranks 1-6
|--------------------------------------------------------------------------
*/

.standings-playoff {
  border-left:
    3px solid rgb(245 158 11);

  background:
    linear-gradient(
      90deg,
      rgba(245, 158, 11, 0.075),
      rgba(17, 24, 39, 0.96) 35%
    );
}

/*
|--------------------------------------------------------------------------
| PLAY-IN — Ranks 7-10
|--------------------------------------------------------------------------
*/

.standings-playin {
  border-left:
    3px solid rgb(59 130 246);

  background:
    linear-gradient(
      90deg,
      rgba(59, 130, 246, 0.065),
      rgba(17, 24, 39, 0.96) 35%
    );
}

/*
|--------------------------------------------------------------------------
| ELIMINATED — Ranks 11+
|--------------------------------------------------------------------------
*/

.standings-eliminated {
  border-left:
    3px solid rgb(239 68 68);

  background:
    linear-gradient(
      90deg,
      rgba(239, 68, 68, 0.045),
      rgba(17, 24, 39, 0.96) 35%
    );
}

/*
|--------------------------------------------------------------------------
| Zone Separators
|--------------------------------------------------------------------------
*/

.conference-separator {
  position: relative;
}

.separator-orange {
  border-top:
    1px dashed
    rgba(245, 158, 11, 0.65);
}

.separator-blue {
  border-top:
    1px dashed
    rgba(59, 130, 246, 0.65);
}

/*
|--------------------------------------------------------------------------
| Team Name
|--------------------------------------------------------------------------
*/

.team-name {
  color: #e5e7eb !important;
  font-weight: 500;
  min-width: 0;
}

/*
|--------------------------------------------------------------------------
| Last 5 Games
|--------------------------------------------------------------------------
*/

.last-five-result {
  width: 17px;
  height: 17px;
  min-width: 17px;

  display: inline-flex;

  align-items: center;
  justify-content: center;

  border-radius: 4px;

  font-size: 10px;
  font-weight: 700;

  border:
    1px solid
    rgba(255, 255, 255, 0.06);
}

.result-win {
  background:
    rgba(22, 101, 52, 0.35);

  color: #4ade80;
}

.result-loss {
  background:
    rgba(127, 29, 29, 0.35);

  color: #f87171;
}

.result-empty {
  background:
    rgba(55, 65, 81, 0.35);

  color: #6b7280;
}

/*
|--------------------------------------------------------------------------
| Rank Movement
|--------------------------------------------------------------------------
*/

.rank-change-enter-active,
.rank-change-leave-active {
  transition:
    opacity 0.35s ease,
    transform 0.35s ease;
}

.rank-change-enter-from {
  opacity: 0;
  transform: translateY(12px);
}

.rank-change-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}

.rank-rise {
  animation:
    rankRise 0.55s ease-out;
}

.rank-fall {
  animation:
    rankFall 0.55s ease-out;
}

@keyframes rankRise {
  0% {
    transform: translateY(8px);

    box-shadow:
      inset 0 0 0 1px
      rgba(74, 222, 128, 0.2);
  }

  100% {
    transform: translateY(0);
    box-shadow: none;
  }
}

@keyframes rankFall {
  0% {
    transform: translateY(-8px);

    box-shadow:
      inset 0 0 0 1px
      rgba(248, 113, 113, 0.2);
  }

  100% {
    transform: translateY(0);
    box-shadow: none;
  }
}

/*
|--------------------------------------------------------------------------
| Narrow Layout
|--------------------------------------------------------------------------
*/

@media (max-width: 700px) {
  .team-column {
    width: 31%;
  }

  .stat-column {
    width: 7%;
  }

  .last-five-column {
    width: 16%;
  }

  .next-column {
    width: 11%;
  }

  .standings-row {
    height: 36px;
  }

  .last-five-result {
    width: 15px;
    min-width: 15px;
    height: 15px;
  }
}

/*
|--------------------------------------------------------------------------
| Very Narrow Component
|--------------------------------------------------------------------------
*/

@media (max-width: 480px) {
  .team-column {
    width: 30%;
  }

  .stat-column {
    width: 7%;
  }

  .last-five-column {
    width: 17%;
  }

  .next-column {
    width: 11%;
  }

  .standings-row {
    height: 35px;
  }
}
</style>