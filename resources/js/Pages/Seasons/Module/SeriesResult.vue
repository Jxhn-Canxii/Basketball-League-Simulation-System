<template>
  <div
    v-if="series_info && !loading"
    class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl"
  >
    <!-- =========================================================
         SERIES HEADER
    ========================================================== -->
    <div
      class="relative overflow-hidden border-b border-slate-800 bg-slate-900 px-5 py-5"
    >
      <!-- Background glow -->
      <div
        class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-blue-600/10 blur-3xl"
      ></div>

      <div
        class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
      >
        <!-- Title -->
        <div class="flex items-center gap-3">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-600/10 text-blue-400"
          >
            <i class="fas fa-trophy"></i>
          </div>

          <div>
            <p
              class="text-[9px] font-bold uppercase tracking-[0.2em] text-blue-400"
            >
              Playoff Series
            </p>

            <h1 class="text-xl font-black tracking-tight text-white">
              {{ seriesLead || "Series Overview" }} {{ seriesMeta.status == 2 ? 'Wins' : '' }}
            </h1>
          </div>
        </div>

        <!-- Meta -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Round -->
          <div
            v-if="seriesMeta?.round"
            class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2"
          >
            <p
              class="text-[8px] font-bold uppercase tracking-wider text-slate-500"
            >
              Round
            </p>

            <p class="text-sm font-bold text-slate-200">
              {{ roundNameFormatter(seriesMeta.round) }}
            </p>
          </div>

          <!-- Games -->
          <div
            class="rounded-lg border border-slate-700 bg-slate-800/70 px-3 py-2"
          >
            <p
              class="text-[8px] font-bold uppercase tracking-wider text-slate-500"
            >
              Games
            </p>

            <p class="text-sm font-bold text-white">
              {{ completedGamesCount }}
              <span class="font-normal text-slate-500">
                / {{ seriesMeta.series_length ?? 0 }}
              </span>
            </p>
          </div>

          <!-- Lead -->
          <div
            class="rounded-lg border border-blue-500/20 bg-blue-600/10 px-3 py-2"
          >
            <p
              class="text-[8px] font-bold uppercase tracking-wider text-blue-400"
            >
              Series Lead
            </p>

            <p class="text-sm font-black text-blue-300">
              {{ seriesLead || "TBD" }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->
    <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-5">
      <!-- =======================================================
           LEFT COLUMN
      ======================================================== -->
      <aside class="space-y-4 lg:col-span-1">
        <!-- =====================================================
             SERIES BEST PLAYER
        ====================================================== -->
        <section
          class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-lg"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between border-b border-slate-800 px-4 py-3"
          >
            <div>
              <p
                class="text-[8px] font-bold uppercase tracking-[0.2em] text-blue-400"
              >
                Series Award
              </p>

              <h2 class="text-sm font-black text-white">
                Series Best Player
              </h2>
            </div>

            <div
              class="flex h-8 w-8 items-center justify-center rounded-lg border border-amber-500/20 bg-amber-500/10 text-amber-400"
            >
              <i class="fas fa-star text-xs"></i>
            </div>
          </div>

          <!-- Player -->
          <div v-if="seriesBestPlayer?.name">
            <!-- Player identity -->
            <div
              class="relative overflow-hidden px-4 py-5"
              :style="{
                backgroundColor:
                  '#' +
                  (seriesBestPlayer?.primary_color || '1e293b'),
              }"
            >
              <!-- Overlay -->
              <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-black/10 via-transparent to-black/40"
              ></div>

              <div
                class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-sm"
              ></div>

              <div class="relative">
                <div
                  class="flex items-start justify-between gap-3"
                >
                  <div class="min-w-0">
                    <p
                      class="truncate text-xl font-black tracking-tight text-white"
                      :title="seriesBestPlayer.name"
                    >
                      {{ playerFormatter(seriesBestPlayer.name) }}
                    </p>

                    <p class="mt-1 text-[10px] font-medium text-white/60">
                      <span v-if="seriesBestPlayer.age">
                        {{ seriesBestPlayer.age }}
                      </span>

                      <span
                        v-if="
                          seriesBestPlayer.age &&
                          seriesBestPlayer.position
                        "
                      >
                        •
                      </span>

                      <span v-if="seriesBestPlayer.position">
                        {{ seriesBestPlayer.position }}
                      </span>
                    </p>
                  </div>

                  <span
                    v-if="seriesBestPlayer.role"
                    :class="roleBadgeClass(seriesBestPlayer.role)"
                    class="shrink-0"
                  >
                    {{ seriesBestPlayer.role }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Team -->
            <div
              class="px-4 py-2"
              :style="{
                backgroundColor:
                  '#' +
                  (seriesBestPlayer?.secondary_color || '334155'),
              }"
            >
              <div class="flex items-center gap-2">
                <i class="fas fa-shield-alt text-[10px] text-white/50"></i>

                <p
                  class="truncate text-xs font-bold text-white"
                >
                  {{ seriesBestPlayer.team }}
                </p>
              </div>
            </div>

            <!-- Stats -->
            <div
              class="grid grid-cols-3 gap-px bg-slate-800"
            >
              <div
                v-for="stat in bestPlayerStats"
                :key="stat.label"
                class="bg-slate-900 px-2 py-3 text-center"
              >
                <p
                  class="text-lg font-black leading-none text-white"
                >
                  {{ stat.value }}
                </p>

                <p
                  class="mt-1 text-[8px] font-bold uppercase tracking-wider text-slate-500"
                >
                  {{ stat.label }}
                </p>
              </div>
            </div>
          </div>

          <!-- Empty -->
          <div
            v-else
            class="px-4 py-10 text-center"
          >
            <div
              class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 text-slate-600"
            >
              <i class="fas fa-user text-sm"></i>
            </div>

            <p class="text-xs font-semibold text-slate-500">
              No best player yet
            </p>
          </div>
        </section>

        <!-- =====================================================
             STAT LEADERS
        ====================================================== -->
        <section
          class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-lg"
        >
          <!-- Header -->
          <div
            class="border-b border-slate-800 px-4 py-3"
          >
            <p
              class="text-[8px] font-bold uppercase tracking-[0.2em] text-blue-400"
            >
              Series Statistics
            </p>

            <h2 class="text-sm font-black text-white">
              Statistical Leaders
            </h2>
          </div>

          <!-- Leaders -->
          <div
            class="divide-y divide-slate-800"
          >
            <div
              v-for="leader in visibleStatLeaderList"
              :key="leader.key"
              class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-800/50"
            >
              <!-- Icon -->
              <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-slate-400"
              >
                <i :class="leader.icon"></i>
              </div>

              <!-- Player -->
              <div class="min-w-0 flex-1">
                <p
                  class="truncate text-xs font-bold text-slate-200"
                >
                  {{ leader.data.player_name }}
                </p>

                <p
                  class="truncate text-[9px] text-slate-500"
                  :title="leader.data.team_name"
                >
                  {{ leader.data.team_name }}
                </p>
              </div>

              <!-- Value -->
              <div class="shrink-0 text-right">
                <p
                  class="text-sm font-black text-white"
                >
                  {{ formatStat(leader.data[leader.valueKey]) }}
                </p>

                <p
                  class="text-[8px] font-bold uppercase tracking-wider text-slate-500"
                >
                  {{ leader.label }}
                </p>
              </div>
            </div>

            <!-- Empty -->
            <div
              v-if="visibleStatLeaderList.length === 0"
              class="px-4 py-8 text-center text-xs text-slate-600"
            >
              No statistical leaders available yet.
            </div>
          </div>
        </section>
      </aside>

      <!-- =======================================================
           RIGHT COLUMN
      ======================================================== -->
      <main class="space-y-4 lg:col-span-4">
        <!-- =====================================================
             LATEST RESULT
        ====================================================== -->
        <section
          v-if="isGameResultModalOpen"
          class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-lg"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between border-b border-slate-800 px-4 py-3"
          >
            <div class="flex items-center gap-3">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 text-emerald-400"
              >
                <i class="fas fa-chart-line text-xs"></i>
              </div>

              <div>
                <p
                  class="text-[8px] font-bold uppercase tracking-[0.2em] text-emerald-400"
                >
                  Latest Result
                </p>

                <h2 class="text-sm font-black text-white">
                  Game Box Score
                </h2>
              </div>
            </div>

            <button
              type="button"
              @click="isGameResultModalOpen = false"
              class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 text-slate-500 transition hover:border-red-500/30 hover:bg-red-500/10 hover:text-red-400"
              title="Close result"
            >
              <i class="fas fa-times text-xs"></i>
            </button>
          </div>

          <!-- Result -->
          <div class="bg-slate-950 p-3 sm:p-4">
            <GameResults
              :key="`${isGameResultModalOpen[0]}-${isGameResultModalOpen[1]}`"
              :game_id="isGameResultModalOpen[0]"
              :season_id="isGameResultModalOpen[1]"
              :showBoxScore="true"
            />
          </div>
        </section>

        <!-- =====================================================
             GAMES
        ====================================================== -->
        <section
          class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-lg"
        >
          <!-- Header -->
          <div
            class="flex flex-col gap-3 border-b border-slate-800 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p
                class="text-[8px] font-bold uppercase tracking-[0.2em] text-blue-400"
              >
                Series Timeline
              </p>

              <h2 class="text-base font-black text-white">
                Games
              </h2>
            </div>

            <div class="flex items-center gap-2">
              <span
                class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[9px] font-bold text-emerald-400"
              >
                {{ completedGamesCount }} Completed
              </span>

              <span
                v-if="series_info.length - completedGamesCount > 0 && seriesMeta.status == 1"
                class="rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-[9px] font-bold text-slate-500"
              >
                {{ series_info.length - completedGamesCount }} Remaining
              </span>
            </div>
          </div>

          <!-- Games -->
          <div class="p-4">
            <div
              v-if="series_info.length"
              class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
            >
              <button
                v-for="(match, index) in series_info"
                :key="match.id || index"
                type="button"
                :disabled="!isCompletedGame(match)"
                @click="openGameResult(match)"
                class="group relative overflow-hidden rounded-xl border text-left transition-all duration-200"
                :class="
                  isCompletedGame(match)
                    ? 'cursor-pointer border-slate-700 bg-slate-800/70 hover:-translate-y-0.5 hover:border-blue-500/50 hover:bg-slate-800 hover:shadow-lg hover:shadow-blue-900/10'
                    : 'cursor-default border-dashed border-slate-800 bg-slate-900/60'
                "
              >
                <!-- Winner accent -->
                <div
                  v-if="isCompletedGame(match)"
                  class="h-1 w-full"
                  :style="{
                    backgroundColor:
                      match.winner === match.home_team?.id
                        ? `#${match.home_team?.primary_color || '2563eb'}`
                        : `#${match.away_team?.primary_color || '2563eb'}`,
                  }"
                ></div>

                <div class="p-4">
                  <!-- Game header -->
                  <div
                    class="mb-4 flex items-center justify-between"
                  >
                    <div class="flex items-center gap-2">
                      <span
                        class="rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-slate-400"
                      >
                        Game {{ match.game_number }}
                      </span>

                      <span
                        v-if="match.is_overtime"
                        class="rounded-md border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[8px] font-black uppercase text-amber-400"
                      >
                        OT
                      </span>
                    </div>

                    <!-- Final -->
                    <span
                      v-if="isCompletedGame(match)"
                      class="flex items-center gap-1 text-[8px] font-bold uppercase tracking-wider text-emerald-400"
                    >
                      <i class="fas fa-check-circle"></i>
                      Final
                    </span>

                    <!-- TBD -->
                    <span
                      v-else
                      class="text-[8px] font-bold uppercase tracking-wider text-slate-600"
                    >
                      TBD
                    </span>
                  </div>

                  <!-- ================================
                       HOME TEAM
                  ================================= -->
                  <div
                    class="flex items-center gap-3 rounded-lg p-2"
                    :class="
                      isCompletedGame(match) &&
                      match.winner === match.home_team?.id
                        ? 'bg-emerald-500/5 ring-1 ring-emerald-500/10'
                        : ''
                    "
                  >
                    <!-- Logo -->
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[10px] font-black text-white shadow-sm"
                      :style="{
                        backgroundColor:
                          '#' +
                          (match.home_team?.primary_color ||
                            '475569'),
                      }"
                    >
                      {{ getTeamInitials(match.home_team?.name) }}
                    </div>

                    <!-- Team -->
                    <div class="min-w-0 flex-1">
                      <p
                        class="truncate text-xs"
                        :class="
                          isCompletedGame(match) &&
                          match.winner === match.home_team?.id
                            ? 'font-black text-white'
                            : 'font-semibold text-slate-400'
                        "
                      >
                        {{ match.home_team?.name || "Home Team" }}
                      </p>

                      <p
                        class="text-[8px] font-bold uppercase tracking-wider text-slate-600"
                      >
                        Home
                      </p>
                    </div>

                    <!-- Score -->
                    <div
                      class="text-xl font-black tabular-nums"
                      :class="
                        isCompletedGame(match) &&
                        match.winner === match.home_team?.id
                          ? 'text-white'
                          : 'text-slate-600'
                      "
                    >
                      {{
                        isCompletedGame(match)
                          ? match.home_team?.home_score ?? 0
                          : "—"
                      }}
                    </div>
                  </div>

                  <!-- VS -->
                  <div
                    class="my-2 flex items-center gap-2 px-2"
                  >
                    <div
                      class="h-px flex-1 bg-slate-700"
                    ></div>

                    <span
                      class="text-[7px] font-black uppercase tracking-[0.2em] text-slate-600"
                    >
                      VS
                    </span>

                    <div
                      class="h-px flex-1 bg-slate-700"
                    ></div>
                  </div>

                  <!-- ================================
                       AWAY TEAM
                  ================================= -->
                  <div
                    class="flex items-center gap-3 rounded-lg p-2"
                    :class="
                      isCompletedGame(match) &&
                      match.winner === match.away_team?.id
                        ? 'bg-emerald-500/5 ring-1 ring-emerald-500/10'
                        : ''
                    "
                  >
                    <!-- Logo -->
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[10px] font-black text-white shadow-sm"
                      :style="{
                        backgroundColor:
                          '#' +
                          (match.away_team?.primary_color ||
                            '475569'),
                      }"
                    >
                      {{ getTeamInitials(match.away_team?.name) }}
                    </div>

                    <!-- Team -->
                    <div class="min-w-0 flex-1">
                      <p
                        class="truncate text-xs"
                        :class="
                          isCompletedGame(match) &&
                          match.winner === match.away_team?.id
                            ? 'font-black text-white'
                            : 'font-semibold text-slate-400'
                        "
                      >
                        {{ match.away_team?.name || "Away Team" }}
                      </p>

                      <p
                        class="text-[8px] font-bold uppercase tracking-wider text-slate-600"
                      >
                        Away
                      </p>
                    </div>

                    <!-- Score -->
                    <div
                      class="text-xl font-black tabular-nums"
                      :class="
                        isCompletedGame(match) &&
                        match.winner === match.away_team?.id
                          ? 'text-white'
                          : 'text-slate-600'
                      "
                    >
                      {{
                        isCompletedGame(match)
                          ? match.away_team?.away_score ?? 0
                          : "—"
                      }}
                    </div>
                  </div>

                  <!-- Footer -->
                  <div
                    class="mt-3 flex items-center justify-between border-t border-slate-700 pt-3"
                  >
                    <span
                      v-if="isCompletedGame(match)"
                      class="text-[8px] font-semibold text-slate-600 transition group-hover:text-blue-400"
                    >
                      View box score
                    </span>

                    <span
                      v-else
                      class="text-[8px] font-semibold text-slate-600"
                    >
                      Awaiting result
                    </span>

                    <i
                      v-if="isCompletedGame(match)"
                      class="fas fa-chevron-right text-[8px] text-slate-600 transition group-hover:translate-x-1 group-hover:text-blue-400"
                    ></i>
                  </div>
                </div>
              </button>
            </div>

            <!-- Empty -->
            <div
              v-else
              class="rounded-xl border border-dashed border-slate-800 py-12 text-center"
            >
              <div
                class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 text-slate-600"
              >
                <i class="fas fa-calendar-times"></i>
              </div>

              <p class="text-sm font-bold text-slate-400">
                No games available
              </p>

              <p class="mt-1 text-xs text-slate-600">
                Series games will appear here once scheduled.
              </p>
            </div>
          </div>
        </section>
      </main> 
    </div>
  </div>

  <!-- ===========================================================
       LOADING
  ============================================================ -->
  <div
    v-else
    class="flex min-h-[400px] items-center justify-center rounded-2xl border border-slate-800 bg-slate-950"
  >
    <div class="flex flex-col items-center">
      <div class="relative">
        <div
          class="h-12 w-12 rounded-full border-4 border-slate-800"
        ></div>

        <div
          class="absolute inset-0 h-12 w-12 animate-spin rounded-full border-4 border-transparent border-t-blue-500"
        ></div>
      </div>

      <p class="mt-4 text-sm font-bold text-slate-300">
        Loading series data...
      </p>

      <p class="mt-1 text-xs text-slate-600">
        Preparing playoff statistics
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import {
  roundNameFormatter,
  roleBadgeClass,
  playerFormatter,
} from "@/Utility/Formatter";

import GameResults from "@/Pages/Seasons/Module/GameResults.vue";

const emits = defineEmits(["finish"]);

const props = defineProps({
  series_id: [String, Number],
  season_id: [String, Number],
});

const isGameResultModalOpen = ref(false);
const series_info = ref([]);
const lastFinishedGameId = ref(null);

const statLeaders = ref({});
const seriesBestPlayer = ref({});
const seriesMeta = ref({});
const seriesLead = ref("");
const loading = ref(false);

/*
|--------------------------------------------------------------------------
| Best Player Stats
|--------------------------------------------------------------------------
*/
const bestPlayerStats = computed(() => [
  {
    label: "PTS",
    value: seriesBestPlayer.value?.points ?? "—",
  },
  {
    label: "REB",
    value: seriesBestPlayer.value?.rebounds ?? "—",
  },
  {
    label: "AST",
    value: seriesBestPlayer.value?.assists ?? "—",
  },
  {
    label: "STL",
    value: seriesBestPlayer.value?.steals ?? "—",
  },
  {
    label: "BLK",
    value: seriesBestPlayer.value?.blocks ?? "—",
  },
  {
    label: "TO",
    value: seriesBestPlayer.value?.turnovers ?? "—",
  },
]);

/*
|--------------------------------------------------------------------------
| Stat Leaders
|--------------------------------------------------------------------------
*/
const statLeaderList = computed(() => [
  {
    key: "points",
    label: "PTS",
    icon: "fas fa-basketball-ball text-xs",
    valueKey: "points",
    data: statLeaders.value.points,
  },
  {
    key: "assists",
    label: "AST",
    icon: "fas fa-hand-point-right text-xs",
    valueKey: "assists",
    data: statLeaders.value.assists,
  },
  {
    key: "rebounds",
    label: "REB",
    icon: "fas fa-arrow-up text-xs",
    valueKey: "rebounds",
    data: statLeaders.value.rebounds,
  },
  {
    key: "steals",
    label: "STL",
    icon: "fas fa-user-shield text-xs",
    valueKey: "steals",
    data: statLeaders.value.steals,
  },
  {
    key: "blocks",
    label: "BLK",
    icon: "fas fa-stop-circle text-xs",
    valueKey: "blocks",
    data: statLeaders.value.blocks,
  },
  {
    key: "efficiency",
    label: "EFF",
    icon: "fas fa-chart-line text-xs",
    valueKey: "efficiency",
    data: statLeaders.value.efficiency,
  },
]);

const visibleStatLeaderList = computed(() =>
  statLeaderList.value.filter((leader) => leader.data)
);

/*
|--------------------------------------------------------------------------
| Game Helpers
|--------------------------------------------------------------------------
*/
const isCompletedGame = (match) => {
  return Number(match?.status) == 2;
};

const completedGamesCount = computed(() => {
  return series_info.value.filter(isCompletedGame).length;
});

const formatStat = (value) => {
  const number = Number(value);

  if (!Number.isFinite(number)) {
    return "—";
  }

  return number.toFixed(2);
};

const getTeamInitials = (name) => {
  if (!name) return "TM";

  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join("");
};

/*
|--------------------------------------------------------------------------
| Open Game Result
|--------------------------------------------------------------------------
*/
const openGameResult = (match) => {
  if (!isCompletedGame(match)) {
    return;
  }

  isGameResultModalOpen.value = [
    match.game_id,
    match.season_id || props.season_id,
  ];
};

/*
|--------------------------------------------------------------------------
| Fetch Series Info
|--------------------------------------------------------------------------
*/
const fetchSeriesInfo = async () => {
  try {
    loading.value = true;

    const response = await axios.post(
      route("seasons.playoff.series.info"),
      {
        series_id: props.series_id,
        season_id: props.season_id,
      }
    );

    series_info.value = response.data.games || [];

    lastFinishedGameId.value = response.data.last_finished_game_id ?? null;

    seriesMeta.value = response.data.series_info ?? {};
    seriesLead.value = response.data.series_lead ?? "";

    const leadersObj =
      response.data.player_stat_leaders ?? {};

    statLeaders.value = {
      points: leadersObj.points
        ? {
            player_name: leadersObj.points.player_name,
            team_name:
              leadersObj.points.team_name || "",
            points:
              Number(
                leadersObj.points.total_points
              ) || 0,
          }
        : null,

      rebounds: leadersObj.rebounds
        ? {
            player_name:
              leadersObj.rebounds.player_name,
            team_name:
              leadersObj.rebounds.team_name || "",
            rebounds:
              Number(
                leadersObj.rebounds.total_rebounds
              ) || 0,
          }
        : null,

      assists: leadersObj.assists
        ? {
            player_name:
              leadersObj.assists.player_name,
            team_name:
              leadersObj.assists.team_name || "",
            assists:
              Number(
                leadersObj.assists.total_assists
              ) || 0,
          }
        : null,

      steals: leadersObj.steals
        ? {
            player_name:
              leadersObj.steals.player_name,
            team_name:
              leadersObj.steals.team_name || "",
            steals:
              Number(
                leadersObj.steals.total_steals
              ) || 0,
          }
        : null,

      blocks: leadersObj.blocks
        ? {
            player_name:
              leadersObj.blocks.player_name,
            team_name:
              leadersObj.blocks.team_name || "",
            blocks:
              Number(
                leadersObj.blocks.total_blocks
              ) || 0,
          }
        : null,

      efficiency: leadersObj.efficiency
        ? {
            player_name:
              leadersObj.efficiency.player_name,
            team_name:
              leadersObj.efficiency.team_name || "",
            efficiency:
              Number(
                leadersObj.efficiency.total_eff
              ) || 0,
          }
        : null,
    };

    seriesBestPlayer.value = {
      ...(response.data.series_best_player ?? {}),
      turnovers:
        response.data.series_best_player?.turnovers ??
        "—",
      fouls:
        response.data.series_best_player?.fouls ?? "—",
    };

    loading.value = false;

    emits("finish", props.series_id);

    /*
    |--------------------------------------------------------------------------
    | Automatically show latest finished game
    |--------------------------------------------------------------------------
    */
    if (lastFinishedGameId.value) {
      const latestGame = series_info.value.find(
        (game) =>
          Number(game.game_id) ===
          Number(lastFinishedGameId.value)
      );

      if (latestGame) {
        isGameResultModalOpen.value = [
          latestGame.game_id,
          latestGame.season_id || props.season_id,
        ];
      }
    }
  } catch (error) {
    loading.value = false;

    console.error(
      "Error fetching series information:",
      error
    );

    Swal.fire({
      icon: "error",
      title: "Error!",
      text:
        error.response?.data?.message ||
        "Failed to fetch series information.",
    });
  }
};

onMounted(fetchSeriesInfo);
</script>