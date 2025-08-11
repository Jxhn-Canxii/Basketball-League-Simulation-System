<template>
    <div class="p-4 border rounded bg-white" v-if="series_info && !loading">
        <!-- 3-column grid -->
        <div class="grid grid-cols-4 gap-4">
            
            <!-- Left: Games List & TBDs -->
            <div class="col-span-1 space-y-2">
                <!-- Played Games -->
                <div
                    v-if="series_info.length > 0"
                    v-for="(match, index) in series_info"
                    :key="match.id || index"
                    @click="isGameResultModalOpen = match.game_id"
                    class="cursor-pointer hover:scale-105 transition"
                >
                    <ScoreCard :match="match" />
                </div>

                <!-- TBD placeholders -->
                <div
                    v-for="n in Math.max(0, ((seriesMeta?.series_length || 0) - series_info.length))"
                    :key="'tbd-' + n"
                    class="bg-gray-200 text-gray-500 text-lg h-56 flex items-center justify-center rounded"
                >
                    {{ seriesMeta.winner_team_id != 0 
                        ? '' 
                        : 'GAME ' + (series_info.length + n) + ' TBD' 
                    }}

                </div>
            </div>

            <!-- Right: Game Result & Stats -->
            <div class="col-span-2 space-y-4">
                <!-- Game Result -->
                <GameResults
                    v-if="isGameResultModalOpen"
                    :key="isGameResultModalOpen"
                    :game_id="isGameResultModalOpen"
                    :showBoxScore="false"
                />
            </div>
           <!-- Right Column -->
            <div class="col-span-1 flex flex-col h-full">
                <!-- Top 1/4: Best Player -->
                <div class="flex-none min-h-[25%] border-b border-gray-300 py-4">
                    <div class="flex justify-between">
                        <h3 class="font-bold mb-2">Series Best Player</h3>
                        <h3 lass="font-bold mb-2 text-red-500">{{ seriesLead || "Loading series info..." }}</h3>
                    </div>
                    <div v-if="seriesBestPlayer?.name" 
                     :style="{
                        backgroundColor:
                            '#' + (seriesBestPlayer?.primary_color || 'cccccc'),
                    }"
                    class="h-full flex flex-col items-center justify-center">
                        <p class="text-4xl font-extrabold mb-1 relative text-nowrap text-white" :title="seriesBestPlayer.name">
                            {{ playerFormatter(seriesBestPlayer.name) }}
                            <sup class="text-xs absolute top-0 ml-2 mt-2 text-nowrap" v-if="seriesBestPlayer?.age">
                                {{ seriesBestPlayer.age }} | {{ seriesBestPlayer.position }}
                            </sup>
                        </p>
                        <div class="flex justify-center p-2">
                            <span :class="roleBadgeClass(seriesBestPlayer.role)">
                                {{ seriesBestPlayer.role }}
                            </span>
                        </div>
                        <div class="flex w-full justify-center px-0 mx-0"
                            :style="{
                                backgroundColor:
                                    '#' + (seriesBestPlayer?.secondary_color || 'cccccc'),
                            }"
                        >
                            <p class="text-xl text-white font-bold py-1">
                                {{ seriesBestPlayer.team }}
                            </p>
                        </div>
                        <ul class="grid grid-cols-3 gap-4 p-4 w-full">
                            <li class="flex flex-col items-center">
                                <span class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-6xl font-bold text-white">{{ seriesBestPlayer.points }}</span>
                                </span>
                                <p class="text-xl text-white font-bold">PTS</p>
                            </li>
                            <li class="flex flex-col items-center">
                                <span class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-6xl font-bold text-white">{{ seriesBestPlayer.rebounds }}</span>
                                </span>
                                <p class="text-xl text-white font-bold">REB</p>
                            </li>
                            <li class="flex flex-col items-center">
                                <span class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-6xl font-bold text-white">{{ seriesBestPlayer.assists }}</span>
                                </span>
                                <p class="text-xl text-white font-bold">AST</p>
                            </li>
                            <li class="flex flex-col items-center">
                                <span class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-6xl font-bold text-white">{{ seriesBestPlayer.steals }}</span>
                                </span>
                                <p class="text-xl text-white font-bold">STL</p>
                            </li>
                            <li class="flex flex-col items-center">
                                <span class="flex-shrink-0 w-25 h-25 p-2 bg-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-6xl font-bold text-white">{{ seriesBestPlayer.blocks }}</span>
                                </span>
                                <p class="text-xl text-white font-bold">BLK</p>
                            </li>
                            <li class="flex flex-col items-center">
                                <span class="flex-shrink-0 w-25 h-25 p-2 bg-red-600 rounded-full flex items-center justify-center">
                                    <span class="text-6xl font-bold text-white">{{ seriesBestPlayer.turnovers }}</span>
                                </span>
                                <p class="text-xl text-white font-bold">TO</p>
                            </li>
                        </ul>
                    </div>
                    <p v-else class="text-gray-500">No best player yet</p>
                </div>

                <!-- Bottom 3/4: Stat Leaders -->
                <div class="flex-1 overflow-y-auto p-4">
                    <h3 class="text-lg font-semibold mb-2">Stat Leaders</h3>
                    <div class="min-w-full shadow-lg border-gray-300 p-4">
                        <ul class="space-y-4">
                            <!-- Points -->
                            <li v-if="statLeaders.points" class="flex items-center border-b border-gray-300 pb-2">
                                <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-basketball-ball text-gray-600"></i>
                                </span>
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-bold">{{ statLeaders.points.player_name }}</span>
                                            <small class="text-gray-400 block">{{ statLeaders.points.team_name }}</small>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-2xl">{{ statLeaders.points.points }} pts</p>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Assists -->
                            <li v-if="statLeaders.assists" class="flex items-center border-b border-gray-300 pb-2">
                                <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-hand-point-right text-gray-600" title="Assist"></i>
                                </span>
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-semibold">{{ statLeaders.assists.player_name }}</span>
                                            <small class="text-gray-400 block">{{ statLeaders.assists.team_name }}</small>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-2xl">{{ statLeaders.assists.assists }} ast</p>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Rebounds -->
                            <li v-if="statLeaders.rebounds" class="flex items-center border-b border-gray-300 pb-2">
                                <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-arrow-alt-circle-up text-gray-600" title="Rebounds"></i>
                                </span>
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-semibold">{{ statLeaders.rebounds.player_name }}</span>
                                            <small class="text-gray-400 block">{{ statLeaders.rebounds.team_name }}</small>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-2xl">{{ statLeaders.rebounds.rebounds }} reb</p>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Steals -->
                            <li v-if="statLeaders.steals" class="flex items-center border-b border-gray-300 pb-2">
                                <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-shield text-gray-600" title="Steals"></i>
                                </span>
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-semibold">{{ statLeaders.steals.player_name }}</span>
                                            <small class="text-gray-400 block">{{ statLeaders.steals.team_name }}</small>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-2xl">{{ statLeaders.steals.steals }} stl</p>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Blocks -->
                            <li v-if="statLeaders.blocks" class="flex items-center border-b border-gray-300 pb-2">
                                <span class="flex-shrink-0 w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-stop-circle text-gray-600" title="Blocks"></i>
                                </span>
                                <div class="ml-3 flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-semibold">{{ statLeaders.blocks.player_name }}</span>
                                            <small class="text-gray-400 block">{{ statLeaders.blocks.team_name }}</small>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-2xl">{{ statLeaders.blocks.blocks }} blk</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <div v-else class="flex items-center justify-center py-8">
        <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <span class="ml-3 text-blue-600 font-semibold text-lg">Loading series data...</span>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { roundNameFormatter, roleBadgeClass, playerFormatter } from "@/Utility/Formatter";

import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";
import GameResults from "@/Pages/Seasons/Module/GameResults.vue";

const props = defineProps({
    series_id: [String, Number],
    season_id: [String, Number],
});

const isGameResultModalOpen = ref(false);
const series_info = ref([]);
const series_count = ref(0);
const lastFinishedGameId = ref(null);
const statLeaders = ref([]);
const seriesBestPlayer = ref({});
const seriesMeta = ref({});
const seriesLead = ref("");
const loading = ref(false);

const fetchSeriesInfo = async () => {
  try {
    loading.value = true;
    const response = await axios.post(route("seasons.playoff.series.info"), {
      series_id: props.series_id,
      season_id: props.season_id,
    });

    series_info.value = response.data.games || [];
    series_count.value = series_info.value.length;
    lastFinishedGameId.value = response.data.last_finished_game_id ?? null;
    seriesMeta.value = response.data.series_info ?? {};
    seriesLead.value = response.data.series_lead ?? "";

    // Transform player_stat_leaders into the correct format for your styled UI
    const leadersObj = response.data.player_stat_leaders ?? {};
    statLeaders.value = {
      points: leadersObj.points ? {
        player_name: leadersObj.points.player_name,
        team_name: leadersObj.points.team_name || "",
        points: Number(leadersObj.points.total_points) || 0,
      } : null,
      rebounds: leadersObj.rebounds ? {
        player_name: leadersObj.rebounds.player_name,
        team_name: leadersObj.rebounds.team_name || "",
        rebounds: Number(leadersObj.rebounds.total_rebounds) || 0,
      } : null,
      assists: leadersObj.assists ? {
        player_name: leadersObj.assists.player_name,
        team_name: leadersObj.assists.team_name || "",
        assists: Number(leadersObj.assists.total_assists) || 0,
      } : null,
      steals: leadersObj.steals ? {
        player_name: leadersObj.steals.player_name,
        team_name: leadersObj.steals.team_name || "",
        steals: Number(leadersObj.steals.total_steals) || 0,
      } : null,
      blocks: leadersObj.blocks ? {
        player_name: leadersObj.blocks.player_name,
        team_name: leadersObj.blocks.team_name || "",
        blocks: Number(leadersObj.blocks.total_blocks) || 0,
      } : null,
      efficiency: leadersObj.efficiency ? {
        player_name: leadersObj.efficiency.player_name,
        team_name: leadersObj.efficiency.team_name || "",
        efficiency: Number(leadersObj.efficiency.total_eff) || 0,
      } : null,
    };

    // Best player, with fallback for turnovers and fouls
    seriesBestPlayer.value = {
      ...(response.data.series_best_player ?? {}),
      turnovers: response.data.series_best_player?.turnovers ?? '—',
      fouls: response.data.series_best_player?.fouls ?? '—',
    };

    loading.value = false;
    // Default GameResult to last finished game
    if (lastFinishedGameId.value) {
      isGameResultModalOpen.value = lastFinishedGameId.value;
    }

  } catch (error) {
    loading.value = false;
    console.error("Error fetching series information:", error);
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: error.response?.data?.message || "Failed to fetch series information.",
    });
  }
};


onMounted(fetchSeriesInfo);
</script>
