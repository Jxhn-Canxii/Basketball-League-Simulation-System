<template>
  <div>
    <!-- Loading state -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-6">
      <i class="fa fa-spinner fa-spin text-blue-500 text-4xl mb-2"></i>
      <p class="text-gray-600 text-sm">Loading player data...</p>
    </div>

    <!-- No Data -->
    <div
      v-if="!injuries.data?.length && !loading"
      class="text-center py-6 text-red-500 font-semibold"
    >
      No injury history available
    </div>

    <!-- Injury History Table -->
    <div v-if="injuries.data?.length > 0 && !loading" class="overflow-x-auto mt-4">
      <table class="min-w-full text-xs divide-y divide-gray-200 text-white rounded-lg overflow-hidden shadow-md">
        <thead class="bg-gray-100 text-gray-600 uppercase tracking-wider text-nowrap">
          <tr>
            <th class="px-3 py-2 text-left">Season</th>
            <!-- <th class="px-3 py-2 text-left">Player</th> -->
            <th class="px-3 py-2 text-left">Role</th>
            <th class="px-3 py-2 text-left">Team</th>
            <th class="px-3 py-2 text-left">Injury Details</th>
            <th class="px-3 py-2 text-left"># Games Missed</th>
            <th class="px-3 py-2 text-left">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="injury in injuries.data"
            :key="injury.id"
            class="cursor-pointer hover:shadow-lg transition"
            :style="{
              background: 'linear-gradient(to right, #' + injury.secondary_color + ', #' + injury.primary_color + ')'
            }"
            @click.prevent="isViewModalOpen = injury.season_id"
          >
            <td class="px-3 py-2 font-medium">Season {{ injury.season_id }}</td>
            <!-- <td class="px-3 py-2">{{ injury.player_name }}</td> -->
            <td class="px-3 py-2">{{ injury.role }}</td>
            <td class="px-3 py-2">{{ injury.team_when_injured }}</td>
            <td class="px-3 py-2 first-letter:uppercase">
              {{ injury.injury_type?.replaceAll('_',' ') }}: {{ injury.details }}
            </td>
            <td class="px-3 py-2">{{ injury.recovery_games }}</td>
            <td class="px-3 py-2">
              <span
                class="px-2 py-1 rounded-full text-[11px] font-semibold"
                :class="statusBadge(injury.status)"
              >
                {{ injury.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
  player_id: {
    type: Number,
    required: true,
  },
});

const injuries = ref([]);
const loading = ref(false);
const player_id = ref(props.player_id);

onMounted(() => {
  fetchPlayerInjuryHistory();
});

const fetchPlayerInjuryHistory = async () => {
  try {
    loading.value = true;
    const response = await axios.post(route("players.season.injury"), {
      player_id: player_id.value,
    });
    injuries.value = response.data; // injury object includes primary_color & secondary_color
  } catch (error) {
    console.error("Error fetching player injury history:", error);
  } finally {
    loading.value = false;
  }
};

const statusBadge = (status) => {
  if (status === "Injured") {
    return "bg-red-600 text-white";
  }
  if (status === "Recovered") {
    return "bg-green-600 text-white";
  }
  return "bg-gray-400 text-white"; // fallback
};
</script>
