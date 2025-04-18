<template>
  <div class="relative bg-white rounded-lg p-4 shadow-md space-y-4 transition-colors hover:bg-gray-50">
    <!-- Depth badge (optional, for compact mode only) -->
    <div
      v-if="!full"
      class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center bg-gray-300 text-black rounded-full text-xs font-bold"
    >
      {{ depth }}
    </div>

    <!-- Player Info -->
    <div class="flex items-center gap-4">
      <div class="flex-grow">
        <div class="flex items-center gap-2">
          <h2 class="text-lg font-semibold">{{ player.name }}</h2>
          <span :class="roleBadgeClass(player.role)">
            {{ player.role }}
          </span>
        </div>
        <div class="text-sm text-gray-600">
          {{ player.position }} • {{ adjustedMinutes(player) }} MPG
        </div>
      </div>

      <!-- Basic Stats (compact) -->
      <div v-if="!full" class="flex gap-4 text-sm">
        <div class="text-center">
          <div class="font-semibold">{{ formatNumber(player.average_points_per_game) }}</div>
          <div class="text-gray-500">PPG</div>
        </div>
        <div class="text-center">
          <div class="font-semibold">{{ player.field_goal_percentage }}%</div>
          <div class="text-gray-500">FG%</div>
        </div>
        <div class="text-center">
          <div class="font-semibold" :class="player.effeciency <= 0 ? 'text-red-500' : 'text-lime-500'">
            {{ player.effeciency }}
          </div>
          <div class="text-gray-500">EFF</div>
        </div>
      </div>
    </div>

    <!-- Full Profile Stats -->
    <div v-if="full" class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-700">
      <div><strong>PPG:</strong> {{ formatNumber(player.average_points_per_game) }}</div>
      <div><strong>RPG:</strong> {{ formatNumber(player.average_rebounds_per_game) }}</div>
      <div><strong>APG:</strong> {{ formatNumber(player.average_assists_per_game) }}</div>
      <div><strong>SPG:</strong> {{ formatNumber(player.average_steals_per_game) }}</div>
      <div><strong>BPG:</strong> {{ formatNumber(player.average_blocks_per_game) }}</div>
      <div><strong>TO:</strong> {{ formatNumber(player.average_turnovers_per_game) }}</div>
      <div><strong>FG%:</strong> {{ player.field_goal_percentage }}%</div>
      <div><strong>3P%:</strong> {{ player.three_point_percentage }}%</div>
      <div><strong>FT%:</strong> {{ player.free_throw_percentage }}%</div>
      <div><strong>EFF:</strong> <span :class="player.effeciency <= 0 ? 'text-red-500' : 'text-lime-500'">{{ player.effeciency }}</span></div>
      <div><strong>Minutes:</strong> {{ adjustedMinutes(player) }} MPG</div>
      <div><strong>Status:</strong> <span :class="player.isInjured ? 'text-red-500' : 'text-green-600'">{{ player.isInjured ? 'Injured' : 'Healthy' }}</span></div>
    </div>
     <LatestPlayerGameLogs
        :key="player.player_id"
        :player_id="player.player_id"
        :season_id="props.season_id"
    />
  </div>
</template>


<script setup>
import { roleBadgeClass } from "@/Utility/Formatter";
import LatestPlayerGameLogs from "./LatestPlayerGameLogs.vue";
const props = defineProps({
  player: {
    type: Object,
    required: true
  },
  depth: {
    type: Number,
    default: null // optional for full mode
  },
  full: {
    type: Boolean,
    default: false
  },
  season_id: {
    type: Number,
    default: 0,
  }
})

const adjustedMinutes = (player) => {
  return player.isInjured ? 0 : parseFloat(player.average_minutes_per_game ?? 0).toFixed(1);
}

const formatNumber = (num) => {
  return parseFloat(num ?? 0).toFixed(1);
}
</script>
