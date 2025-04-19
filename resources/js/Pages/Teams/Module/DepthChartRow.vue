<template>
  <div class="space-y-4">
    <!-- Position list -->
    <div class="flex gap-6">
      <div
        v-for="position in positions"
        :key="position.code"
        class="w-1/5"
      >
        <h3 class="text-lg font-semibold text-center">
          {{ position.label }} ({{ position.code }})
        </h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="player in sortedByPosition(position.code)"
            :key="player.id"
            @click="selectedPlayer = player"
            class="cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <span :class="{ 'line-through': player.status === 2 }">{{ player.name }}</span>
            
            <!-- Role Icons -->
            <span v-if="player.role === 'star player'" title="Star Player">
              <i class="fas fa-star text-yellow-400"></i>
            </span>
            <span v-else-if="player.role === 'all star'" title="All-Star">
              <i class="fas fa-certificate text-pink-500"></i>
            </span>
            <span v-else-if="player.role === 'starter'" title="Starter">
              <i class="fas fa-shield-alt text-green-500"></i>
            </span>

            <!-- Newly Acquired -->
            <span
              title="Newly Acquired"
              class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full"
              v-if="player.seasons_played_with_team == 1"
            >
              <i class="fas fa-user-plus text-yellow-500 mr-1"></i>
            </span>

            <!-- Active -->
            <span
              v-if="player.status == 1"
              class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-green-800 bg-green-100 rounded-full"
              title="Active"
            >
              <i class="fas fa-check-circle"></i>
            </span>

            <!-- Transferred -->
            <span
              v-if="player.status == 2"
              class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-100 rounded-full"
              title="Transferred"
            >
              <i class="fas fa-exchange-alt"></i>
            </span>

            <!-- Retired -->
            <span
              v-if="player.status == 0 && (player.latest_season - player.draft_id != 0)"
              class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-red-800 bg-red-100 rounded-full"
              title="Retired"
            >
              <i class="fas fa-user-slash"></i>
            </span>

            <!-- Hardship Contract -->
            <span
              v-if="player.hardship_contract > 0"
              class="inline-flex items-center px-3 py-1 text-xs font-bold leading-none text-orange-800 bg-orange-100 rounded-full"
              title="On a Hardship Contract"
            >
              <i class="fas fa-hand-holding-medical"></i>
            </span>

            <!-- Injured -->
            <span v-if="player.is_injured" class="text-red-500" title="Injured">
              <i class="fas fa-ambulance"></i>
            </span>

          </div>
        </div>
      </div>
    </div>

    <!-- Player Card Modal -->
    <Modal :show="selectedPlayer" :maxWidth="'6xl'" title="Player Statistics" @close="selectedPlayer = false">
      <div class="p-6 block">
        <PlayerCard :player="selectedPlayer" :season_id="props.season_id" :full="true" />
      </div>
    </Modal>
  </div>
</template>
<script setup>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import PlayerCard from "../../Players/Module/PlayerCard.vue";

const props = defineProps({
  players: {
    type: Array,
    required: true
  },
  season_id: {
    type: Number,
    default: 0
  }
})

const selectedPlayer = ref(null);

// Position code and labels
const positions = [
  { code: 'PG', label: 'Point Guard' },
  { code: 'SG', label: 'Shooting Guard' },
  { code: 'SF', label: 'Small Forward' },
  { code: 'PF', label: 'Power Forward' },
  { code: 'C', label: 'Center' }
];

const sortedByPosition = (position) => {
  return props.players?.filter(player =>
    player.position.split('/').includes(position)
  );
};

</script>
