<template>
  <div
    class="relative w-full overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-xl transition-all duration-200 hover:border-gray-700"
  >
    <!-- =========================================================
         DEPTH INDICATOR
    ========================================================== -->
    <div
      v-if="!full && depth !== null"
      class="absolute left-0 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 -translate-x-1/2 items-center justify-center rounded-full border border-gray-700 bg-gray-900 text-xs font-black text-gray-300 shadow-lg"
    >
      {{ depth }}
    </div>

    <!-- =========================================================
         TOP ACCENT
    ========================================================== -->
    <div
      class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gray-600 to-transparent"
    ></div>

    <!-- =========================================================
         PLAYER HEADER
    ========================================================== -->
    <div
      class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <!-- Player -->
      <div class="flex min-w-0 items-center gap-3">
        <!-- Avatar -->
        <div
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-700 bg-gray-900 shadow-inner"
        >
          <span
            class="text-sm font-black uppercase text-gray-300"
          >
            {{ playerInitials }}
          </span>
        </div>

        <!-- Player info -->
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h2
              class="truncate text-sm font-bold tracking-tight text-white sm:text-base"
            >
              {{ player.name || "Unknown Player" }}
            </h2>

            <span
              :class="roleBadgeClass(player.role)"
              class="!rounded-md !px-2 !py-1 text-[9px] font-bold uppercase tracking-wide"
            >
              {{ player.role || "Player" }}
            </span>
          </div>

          <div
            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-gray-500"
          >
            <span class="font-medium text-gray-400">
              {{ player.position || "—" }}
            </span>

            <span class="text-gray-700">•</span>

            <span>
              {{ adjustedMinutes(player) }} MPG
            </span>

            <span class="text-gray-700">•</span>

            <span
              :class="
                isPlayerInjured(player)
                  ? 'text-red-400'
                  : 'text-green-400'
              "
            >
              {{ isPlayerInjured(player) ? "Injured" : "Healthy" }}
            </span>
          </div>
        </div>
      </div>

      <!-- =======================================================
           COMPACT STATS
      ======================================================== -->
      <div
        v-if="!full"
        class="grid grid-cols-3 divide-x divide-gray-800 rounded-lg border border-gray-800 bg-gray-900/70"
      >
        <!-- PPG -->
        <div class="min-w-[65px] px-3 py-2 text-center">
          <div class="text-sm font-bold text-white">
            {{ formatNumber(player.average_points_per_game) }}
          </div>

          <div
            class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-gray-600"
          >
            PPG
          </div>
        </div>

        <!-- FG -->
        <div class="min-w-[65px] px-3 py-2 text-center">
          <div class="text-sm font-bold text-white">
            {{ formatPercentage(player.field_goal_percentage) }}
          </div>

          <div
            class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-gray-600"
          >
            FG%
          </div>
        </div>

        <!-- EFF -->
        <div class="min-w-[65px] px-3 py-2 text-center">
          <div
            class="text-sm font-bold"
            :class="efficiencyClass(player.effeciency)"
          >
            {{ formatNumber(player.effeciency) }}
          </div>

          <div
            class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-gray-600"
          >
            EFF
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================
         FULL PROFILE
    ========================================================== -->
    <div
      v-if="full"
      class="border-t border-gray-800 bg-gray-900/30 px-4 py-4"
    >
      <!-- Section heading -->
      <div class="mb-3 flex items-center gap-2">
        <div
          class="flex h-6 w-6 items-center justify-center rounded-md border border-gray-800 bg-gray-900"
        >
          <i class="fa fa-bar-chart text-[10px] text-gray-500"></i>
        </div>

        <span
          class="text-[10px] font-bold uppercase tracking-[0.15em] text-gray-500"
        >
          Season Statistics
        </span>
      </div>

      <!-- Stats -->
      <div
        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6"
      >
        <!-- PPG -->
        <StatItem
          label="PPG"
          :value="formatNumber(player.average_points_per_game)"
        />

        <!-- RPG -->
        <StatItem
          label="RPG"
          :value="formatNumber(player.average_rebounds_per_game)"
        />

        <!-- APG -->
        <StatItem
          label="APG"
          :value="formatNumber(player.average_assists_per_game)"
        />

        <!-- SPG -->
        <StatItem
          label="SPG"
          :value="formatNumber(player.average_steals_per_game)"
        />

        <!-- BPG -->
        <StatItem
          label="BPG"
          :value="formatNumber(player.average_blocks_per_game)"
        />

        <!-- TO -->
        <StatItem
          label="TO"
          :value="formatNumber(player.average_turnovers_per_game)"
        />

        <!-- FG% -->
        <StatItem
          label="FG%"
          :value="formatPercentage(player.field_goal_percentage)"
        />

        <!-- 3P% -->
        <StatItem
          label="3P%"
          :value="formatPercentage(player.three_point_percentage)"
        />

        <!-- FT% -->
        <StatItem
          label="FT%"
          :value="formatPercentage(player.free_throw_percentage)"
        />

        <!-- EFF -->
        <StatItem
          label="EFF"
          :value="formatNumber(player.effeciency)"
          :value-class="efficiencyClass(player.effeciency)"
        />

        <!-- Minutes -->
        <StatItem
          label="Minutes"
          :value="`${adjustedMinutes(player)} MPG`"
        />

        <!-- Status -->
        <StatItem
          label="Status"
          :value="isPlayerInjured(player) ? 'Injured' : 'Healthy'"
          :value-class="
            isPlayerInjured(player)
              ? 'text-red-400'
              : 'text-green-400'
          "
        />
      </div>
    </div>

    <!-- =========================================================
         LATEST GAME LOGS
    ========================================================== -->
    <div class="border-t border-gray-800">
      <LatestPlayerGameLogs
        :key="`${player.player_id}-${season_id}`"
        :player_id="player.player_id"
        :season_id="season_id"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { roleBadgeClass } from "@/Utility/Formatter";
import LatestPlayerGameLogs from "./LatestPlayerGameLogs.vue";

const props = defineProps({
  player: {
    type: Object,
    required: true,
  },

  depth: {
    type: Number,
    default: null,
  },

  full: {
    type: Boolean,
    default: false,
  },

  season_id: {
    type: Number,
    default: 0,
  },
});

/*
|--------------------------------------------------------------------------
| Player Initials
|--------------------------------------------------------------------------
*/

const playerInitials = computed(() => {
  const name = String(props.player?.name || "").trim();

  if (!name) {
    return "??";
  }

  const parts = name.split(/\s+/).filter(Boolean);

  if (parts.length === 1) {
    return parts[0].substring(0, 2).toUpperCase();
  }

  return (
    parts[0].charAt(0) +
    parts[parts.length - 1].charAt(0)
  ).toUpperCase();
});

/*
|--------------------------------------------------------------------------
| Injury
|--------------------------------------------------------------------------
|
| Supports both naming conventions:
| - is_injured
| - isInjured
|
*/

const isPlayerInjured = (player) => {
  return Boolean(
    player?.is_injured ??
    player?.isInjured ??
    false
  );
};

/*
|--------------------------------------------------------------------------
| Minutes
|--------------------------------------------------------------------------
*/

const adjustedMinutes = (player) => {
  if (isPlayerInjured(player)) {
    return "0.0";
  }

  const minutes = Number(
    player?.average_minutes_per_game ?? 0
  );

  return Number.isFinite(minutes)
    ? minutes.toFixed(1)
    : "0.0";
};

/*
|--------------------------------------------------------------------------
| Number Formatting
|--------------------------------------------------------------------------
*/

const formatNumber = (value) => {
  const number = Number(value ?? 0);

  if (!Number.isFinite(number)) {
    return "0.0";
  }

  return number.toFixed(1);
};

const formatPercentage = (value) => {
  const number = Number(value ?? 0);

  if (!Number.isFinite(number)) {
    return "0.0%";
  }

  return `${number.toFixed(1)}%`;
};

/*
|--------------------------------------------------------------------------
| Efficiency Styling
|--------------------------------------------------------------------------
*/

const efficiencyClass = (value) => {
  const number = Number(value ?? 0);

  if (!Number.isFinite(number) || number <= 0) {
    return "text-red-400";
  }

  if (number >= 20) {
    return "text-green-400";
  }

  if (number >= 10) {
    return "text-lime-400";
  }

  return "text-yellow-400";
};
</script>

<script>
/*
|--------------------------------------------------------------------------
| Local Stat Component
|--------------------------------------------------------------------------
|
| Kept inside this file so the parent component remains self-contained.
|
*/

export default {
  components: {
    StatItem: {
      props: {
        label: {
          type: String,
          required: true,
        },

        value: {
          type: [String, Number],
          default: "—",
        },

        valueClass: {
          type: String,
          default: "text-gray-200",
        },
      },

      template: `
        <div
          class="rounded-lg border border-gray-800 bg-gray-950/70 px-3 py-2.5 transition-colors hover:border-gray-700 hover:bg-gray-900"
        >
          <div
            class="text-[9px] font-semibold uppercase tracking-wider text-gray-600"
          >
            {{ label }}
          </div>

          <div
            class="mt-1 text-sm font-bold"
            :class="valueClass"
          >
            {{ value }}
          </div>
        </div>
      `,
    },
  },
};
</script>