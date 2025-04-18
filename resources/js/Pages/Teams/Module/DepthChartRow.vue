<template>
  <div class="space-y-4">
    <!-- Position list -->
    <div class="flex gap-6">
      <!-- PG Depth Chart -->
      <div class="w-1/5">
        <h3 class="text-lg font-semibold text-center">Point Guard (PG)</h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="player in sortedByPosition('PG')"
            :key="player.id"
            @click="selectedPlayer = player"
            class="cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <span>{{ player.name }}</span>
            <span v-if="player.role === 'star player'" title="Star Player">⭐</span>
            <span v-if="player.is_rookie" class="text-blue-500" title="Rookie">🎓</span>
            <span v-if="player.is_injured" class="text-red-500" title="Injured">🚑</span>
          </div>
        </div>
      </div>

      <!-- SG Depth Chart -->
      <div class="w-1/5">
        <h3 class="text-lg font-semibold text-center">Shooting Guard (SG)</h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="player in sortedByPosition('SG')"
            :key="player.id"
            @click="selectedPlayer = player"
            class="cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <span>{{ player.name }}</span>
            <span v-if="player.role === 'star player'" title="Star Player">⭐</span>
            <span v-if="player.is_rookie" class="text-blue-500" title="Rookie">🎓</span>
            <span v-if="player.is_injured" class="text-red-500" title="Injured">🚑</span>
          </div>
        </div>
      </div>

      <!-- SF Depth Chart -->
      <div class="w-1/5">
        <h3 class="text-lg font-semibold text-center">Small Forward (SF)</h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="player in sortedByPosition('SF')"
            :key="player.id"
            @click="selectedPlayer = player"
            class="cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <span>{{ player.name }}</span>
            <span v-if="player.role === 'star player'" title="Star Player">⭐</span>
            <span v-if="player.is_rookie" class="text-blue-500" title="Rookie">🎓</span>
            <span v-if="player.is_injured" class="text-red-500" title="Injured">🚑</span>
          </div>
        </div>
      </div>

      <!-- PF Depth Chart -->
      <div class="w-1/5">
        <h3 class="text-lg font-semibold text-center">Power Forward (PF)</h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="player in sortedByPosition('PF')"
            :key="player.id"
            @click="selectedPlayer = player"
            class="cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <span>{{ player.name }}</span>
            <span v-if="player.role === 'star player'" title="Star Player">⭐</span>
            <span v-if="player.is_rookie" class="text-blue-500" title="Rookie">🎓</span>
            <span v-if="player.is_injured" class="text-red-500" title="Injured">🚑</span>
          </div>
        </div>
      </div>

      <!-- C Depth Chart -->
      <div class="w-1/5">
        <h3 class="text-lg font-semibold text-center">Center (C)</h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="player in sortedByPosition('C')"
            :key="player.id"
            @click="selectedPlayer = player"
            class="cursor-pointer text-center flex items-center justify-center gap-1"
          >
            <span>{{ player.name }}</span>
            <span v-if="player.role === 'star player'" title="Star Player">⭐</span>
            <span v-if="player.is_rookie" class="text-blue-500" title="Rookie">🎓</span>
            <span v-if="player.is_injured" class="text-red-500" title="Injured">🚑</span>
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

const sortedByPosition = (position) => {
  return props.players?.filter(player => {
    return player.position.split('/').includes(position);
  });
}
</script>

<style scoped>
.cursor-pointer:hover {
  text-decoration: underline;
}
</style>
