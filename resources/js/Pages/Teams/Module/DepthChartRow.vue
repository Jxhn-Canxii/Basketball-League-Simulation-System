<template>
  <div class="w-full">
    <!-- Depth Chart -->
    <div
      class="rounded-2xl border border-slate-800 bg-slate-950/95 shadow-2xl overflow-hidden"
    >
      <!-- Header -->
      <div
        class="px-5 py-4 border-b border-slate-800 bg-gradient-to-r from-slate-900 to-slate-950"
      >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span
                class="w-1.5 h-6 rounded-full bg-indigo-500"
              ></span>

              <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
                Depth Chart
              </h2>
            </div>

            <p class="mt-1 text-xs text-slate-500">
              Player rotation and positional depth
            </p>
          </div>

          <!-- Legend -->
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[10px] sm:text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
              <i class="fas fa-star text-yellow-400"></i>
              Star
            </span>

            <span class="flex items-center gap-1.5">
              <i class="fas fa-certificate text-pink-400"></i>
              All-Star
            </span>

            <span class="flex items-center gap-1.5">
              <i class="fas fa-shield-alt text-emerald-400"></i>
              Starter
            </span>

            <span class="flex items-center gap-1.5">
              <i class="fas fa-user-graduate text-blue-400"></i>
              Rookie
            </span>
          </div>
        </div>
      </div>

      <!-- Position Grid -->
      <div class="p-3 sm:p-4">
        <div
          class="
            grid
            grid-cols-1
            sm:grid-cols-2
            lg:grid-cols-3
            xl:grid-cols-5
            gap-3
            lg:gap-4
          "
        >
          <!-- Position -->
          <div
            v-for="position in positions"
            :key="position.code"
            class="
              min-w-0
              rounded-xl
              border
              border-slate-800
              bg-slate-900/70
              overflow-hidden
              transition-all
              duration-200
              hover:border-slate-700
              hover:bg-slate-900
            "
          >
            <!-- Position Header -->
            <div
              class="
                px-3
                py-3
                border-b
                border-slate-800
                bg-slate-900
              "
            >
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <h3
                    class="text-xs sm:text-sm font-bold text-white truncate"
                  >
                    {{ position.label }}
                  </h3>

                  <p
                    class="
                      mt-0.5
                      text-[10px]
                      font-semibold
                      uppercase
                      tracking-widest
                      text-slate-500
                    "
                  >
                    {{ position.code }}
                  </p>
                </div>

                <span
                  class="
                    shrink-0
                    inline-flex
                    items-center
                    justify-center
                    min-w-[28px]
                    h-6
                    px-2
                    rounded-md
                    bg-slate-800
                    border
                    border-slate-700
                    text-[11px]
                    font-bold
                    text-slate-300
                  "
                >
                  {{ sortedByPosition(position.code).length }}
                </span>
              </div>
            </div>

            <!-- Players -->
            <div class="p-2">
              <!-- Empty Position -->
              <div
                v-if="sortedByPosition(position.code).length === 0"
                class="
                  flex
                  flex-col
                  items-center
                  justify-center
                  min-h-[100px]
                  rounded-lg
                  border
                  border-dashed
                  border-slate-800
                  text-slate-600
                "
              >
                <i class="fas fa-user-slash text-lg mb-2"></i>

                <span class="text-[10px] font-medium">
                  No players
                </span>
              </div>

              <!-- Player List -->
              <div
                v-else
                class="flex flex-col gap-1"
              >
                <div
                  v-for="(player, index) in sortedByPosition(position.code)"
                  :key="player.id"
                  @click="selectedPlayer = player"
                  class="
                    group
                    relative
                    cursor-pointer
                    rounded-lg
                    border
                    border-transparent
                    px-2.5
                    py-2
                    transition-all
                    duration-150
                    hover:border-slate-700
                    hover:bg-slate-800/80
                  "
                  :class="{
                    'opacity-50': player.status === 2,
                    'bg-red-950/20 border-red-900/30': player.is_injured
                  }"
                >
                  <div class="flex items-center gap-2 min-w-0">
                    <!-- Depth Number -->
                    <div
                      class="
                        shrink-0
                        w-5
                        h-5
                        rounded
                        flex
                        items-center
                        justify-center
                        bg-slate-800
                        text-[9px]
                        font-bold
                        text-slate-500
                      "
                    >
                      {{ index + 1 }}
                    </div>

                    <!-- Player Info -->
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center gap-1.5 min-w-0">
                        <span
                          class="
                            min-w-0
                            truncate
                            text-xs
                            font-semibold
                            text-slate-200
                            group-hover:text-white
                            transition-colors
                          "
                          :class="{
                            'line-through text-slate-500':
                              player.status === 2
                          }"
                        >
                          {{ player.name }}
                        </span>

                        <!-- Overall -->
                        <span
                          class="
                            shrink-0
                            text-[10px]
                            font-black
                            text-indigo-400
                          "
                        >
                          {{ Math.round(player.overall_rating ?? 0) }}
                        </span>
                      </div>

                      <!-- Secondary Info -->
                      <div
                        class="
                          mt-0.5
                          flex
                          items-center
                          gap-1.5
                          min-w-0
                        "
                      >
                        <span
                          v-if="player.age"
                          class="text-[9px] text-slate-500"
                        >
                          Age {{ player.age }}
                        </span>

                        <span
                          v-if="player.role"
                          class="
                            max-w-[80px]
                            truncate
                            text-[9px]
                            font-medium
                            text-slate-500
                          "
                        >
                          {{ player.role }}
                        </span>

                        <span
                          v-if="player.is_injured"
                          class="text-[9px] font-semibold text-red-400"
                        >
                          INJ
                        </span>
                      </div>
                    </div>

                    <!-- Status / Role Icons -->
                    <div
                      class="
                        shrink-0
                        flex
                        items-center
                        gap-1
                      "
                    >
                      <!-- Role -->
                      <span
                        v-if="player.role === 'star player'"
                        title="Star Player"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-yellow-400/10
                        "
                      >
                        <i class="fas fa-star text-[9px] text-yellow-400"></i>
                      </span>

                      <span
                        v-else-if="player.role === 'all star'"
                        title="All-Star"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-pink-400/10
                        "
                      >
                        <i
                          class="fas fa-certificate text-[9px] text-pink-400"
                        ></i>
                      </span>

                      <span
                        v-else-if="player.role === 'starter'"
                        title="Starter"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-emerald-400/10
                        "
                      >
                        <i
                          class="fas fa-shield-alt text-[9px] text-emerald-400"
                        ></i>
                      </span>

                      <!-- Rookie -->
                      <span
                        v-if="player.is_rookie"
                        title="Rookie"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-blue-400/10
                        "
                      >
                        <i
                          class="
                            fas
                            fa-user-graduate
                            text-[9px]
                            text-blue-400
                          "
                        ></i>
                      </span>

                      <!-- Newly Acquired -->
                      <span
                        v-if="player.seasons_played_with_team == 1"
                        title="Newly Acquired"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-cyan-400/10
                        "
                      >
                        <i
                          class="
                            fas
                            fa-user-plus
                            text-[9px]
                            text-cyan-400
                          "
                        ></i>
                      </span>

                      <!-- Active -->
                      <span
                        v-if="player.status == 1"
                        title="Active"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-emerald-400/10
                        "
                      >
                        <i
                          class="
                            fas
                            fa-check-circle
                            text-[9px]
                            text-emerald-400
                          "
                        ></i>
                      </span>

                      <!-- Transferred -->
                      <span
                        v-if="player.status == 2"
                        title="Transferred"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-slate-700
                        "
                      >
                        <i
                          class="
                            fas
                            fa-exchange-alt
                            text-[9px]
                            text-slate-400
                          "
                        ></i>
                      </span>

                      <!-- Retired -->
                      <span
                        v-if="
                          player.status == 0 &&
                          (player.latest_season - player.draft_id != 0)
                        "
                        title="Retired"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-red-400/10
                        "
                      >
                        <i
                          class="
                            fas
                            fa-user-slash
                            text-[9px]
                            text-red-400
                          "
                        ></i>
                      </span>

                      <!-- Hardship -->
                      <span
                        v-if="player.hardship_contract > 0"
                        title="Hardship Contract"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-orange-400/10
                        "
                      >
                        <i
                          class="
                            fas
                            fa-hand-holding-medical
                            text-[9px]
                            text-orange-400
                          "
                        ></i>
                      </span>

                      <!-- Injured -->
                      <span
                        v-if="player.is_injured"
                        title="Injured"
                        class="
                          flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          bg-red-500/10
                        "
                      >
                        <i
                          class="
                            fas
                            fa-ambulance
                            text-[9px]
                            text-red-400
                          "
                        ></i>
                      </span>

                      <!-- Open Profile -->
                      <span
                        class="
                          hidden
                          sm:flex
                          items-center
                          justify-center
                          w-5
                          h-5
                          rounded
                          opacity-0
                          group-hover:opacity-100
                          text-slate-500
                          transition-opacity
                        "
                        title="View Player"
                      >
                        <i class="fas fa-chevron-right text-[8px]"></i>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div
        class="
          px-4
          py-2.5
          border-t
          border-slate-800
          bg-slate-950
          flex
          items-center
          justify-between
          gap-3
        "
      >
        <span class="text-[10px] text-slate-600">
          Click a player to view profile
        </span>

        <span
          class="
            text-[10px]
            font-semibold
            text-slate-500
          "
        >
          {{ props.players?.length ?? 0 }} Players
        </span>
      </div>
    </div>

    <!-- Player Profile Modal -->
    <Modal
      :show="!!selectedPlayer"
      :maxWidth="'6xl'"
      title="Player Statistics"
      @close="selectedPlayer = null"
    >
      <div class="p-4 sm:p-6 bg-slate-950">
        <PlayerCard
          v-if="selectedPlayer"
          :player="selectedPlayer"
          :season_id="props.season_id"
          :full="true"
        />
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
    required: true,
  },

  season_id: {
    type: Number,
    default: 0,
  },
});

const selectedPlayer = ref(null);

const positions = [
  {
    code: "PG",
    label: "Point Guard",
  },
  {
    code: "SG",
    label: "Shooting Guard",
  },
  {
    code: "SF",
    label: "Small Forward",
  },
  {
    code: "PF",
    label: "Power Forward",
  },
  {
    code: "C",
    label: "Center",
  },
];

const sortedByPosition = (position) => {
  if (!Array.isArray(props.players)) {
    return [];
  }

  return props.players.filter((player) => {
    if (!player?.position) {
      return false;
    }

    return String(player.position)
      .split("/")
      .map((value) => value.trim())
      .includes(position);
  });
};
</script>