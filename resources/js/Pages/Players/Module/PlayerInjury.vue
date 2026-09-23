<template>
  <div class="w-full">
    <!-- =========================================================
         HEADER
    ========================================================== -->
    <div
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4"
    >
      <div>
        <div class="flex items-center gap-2">
          <div
            class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500/10 border border-red-500/20"
          >
            <i class="fa fa-heartbeat text-red-400 text-sm"></i>
          </div>

          <div>
            <h2 class="text-sm font-semibold text-white">
              Injury History
            </h2>

            <p class="text-[11px] text-gray-500">
              Player injuries, recovery periods, and team history
            </p>
          </div>
        </div>
      </div>

      <!-- Injury count -->
      <div
        v-if="!loading && injuries.data?.length"
        class="self-start sm:self-auto inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-800 border border-gray-700"
      >
        <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>

        <span class="text-[11px] font-medium text-gray-300">
          {{ injuries.data.length }}
          {{ injuries.data.length === 1 ? "record" : "records" }}
        </span>
      </div>
    </div>

    <!-- =========================================================
         LOADING STATE
    ========================================================== -->
    <div
      v-if="loading"
      class="rounded-xl border border-gray-800 bg-gray-950/80 overflow-hidden"
    >
      <!-- Skeleton header -->
      <div
        class="grid grid-cols-6 gap-4 px-4 py-3 bg-gray-900 border-b border-gray-800"
      >
        <div
          v-for="i in 6"
          :key="`head-${i}`"
          class="h-3 rounded bg-gray-800 animate-pulse"
        ></div>
      </div>

      <!-- Skeleton rows -->
      <div class="divide-y divide-gray-800/80">
        <div
          v-for="row in 5"
          :key="`row-${row}`"
          class="grid grid-cols-6 gap-4 px-4 py-4"
        >
          <div
            v-for="col in 6"
            :key="`col-${row}-${col}`"
            class="h-4 rounded bg-gray-800 animate-pulse"
          ></div>
        </div>
      </div>
    </div>

    <!-- =========================================================
         EMPTY STATE
    ========================================================== -->
    <div
      v-else-if="!injuries.data?.length"
      class="rounded-xl border border-gray-800 bg-gray-950/80 px-6 py-10 text-center"
    >
      <div
        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-500/10 border border-green-500/20"
      >
        <i class="fa fa-shield text-green-400 text-lg"></i>
      </div>

      <h3 class="text-sm font-semibold text-gray-200">
        No Injury History
      </h3>

      <p class="mt-1 text-xs text-gray-500">
        No recorded injuries are available for this player.
      </p>
    </div>

    <!-- =========================================================
         TABLE
    ========================================================== -->
    <div
      v-else
      class="rounded-xl border border-gray-800 bg-gray-950/80 overflow-hidden shadow-xl"
    >
      <!-- Table toolbar -->
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 px-4 py-3 border-b border-gray-800 bg-gray-900/70"
      >
        <div class="flex items-center gap-2">
          <span
            class="inline-flex items-center justify-center h-6 w-6 rounded-md bg-red-500/10 border border-red-500/20"
          >
            <i class="fa fa-medkit text-red-400 text-[11px]"></i>
          </span>

          <span class="text-xs font-semibold text-gray-300">
            Medical Record
          </span>
        </div>

        <div class="flex items-center gap-3 text-[10px]">
          <div class="flex items-center gap-1.5 text-gray-500">
            <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>
            Injured
          </div>

          <div class="flex items-center gap-1.5 text-gray-500">
            <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
            Recovered
          </div>
        </div>
      </div>

      <!--
        Important:
        Keep a sensible minimum width so columns don't become cramped.
        The parent scrolls horizontally on smaller screens.
      -->
      <div class="overflow-x-auto">
        <table
          class="w-full min-w-[1100px] text-xs border-collapse"
        >
          <!-- =====================================================
               TABLE HEADER
          ====================================================== -->
          <thead>
            <!-- Group headers -->
            <tr
              class="bg-gray-950 border-b border-gray-800 text-[9px] uppercase tracking-[0.14em] text-gray-600"
            >
              <th
                colspan="3"
                class="px-4 py-2 text-left border-r border-gray-800"
              >
                Player / Team
              </th>

              <th
                colspan="2"
                class="px-4 py-2 text-left border-r border-gray-800"
              >
                Medical Information
              </th>

              <th class="px-4 py-2 text-left">
                Outcome
              </th>
            </tr>

            <!-- Column headers -->
            <tr
              class="bg-gray-900 text-[10px] uppercase tracking-wider text-gray-500"
            >
              <th
                class="sticky left-0 z-20 px-4 py-3 text-left font-semibold bg-gray-900 border-r border-gray-800 whitespace-nowrap"
              >
                Season
              </th>

              <th
                class="px-4 py-3 text-left font-semibold whitespace-nowrap"
              >
                Role
              </th>

              <th
                class="px-4 py-3 text-left font-semibold whitespace-nowrap"
              >
                Team
              </th>

              <th
                class="px-4 py-3 text-left font-semibold min-w-[350px]"
              >
                Injury Details
              </th>

              <th
                class="px-4 py-3 text-left font-semibold whitespace-nowrap"
              >
                Games Missed
              </th>

              <th
                class="px-4 py-3 text-left font-semibold whitespace-nowrap"
              >
                Status
              </th>
            </tr>
          </thead>

          <!-- =====================================================
               TABLE BODY
          ====================================================== -->
          <tbody class="divide-y divide-gray-800/80">
            <tr
              v-for="injury in injuries.data"
              :key="injury.id"
              class="group relative cursor-pointer transition-all duration-200 hover:bg-white/[0.03]"
              @click.prevent="isViewModalOpen = injury.season_id"
            >
              <!--
                Team color accent.
                Kept separate from the entire row background so text
                remains readable regardless of team colors.
              -->
              <td
                class="sticky left-0 z-10 px-4 py-3 bg-gray-950 group-hover:bg-gray-900 border-r border-gray-800 whitespace-nowrap"
              >
                <div class="flex items-center gap-2">
                  <span
                    class="h-7 w-1 rounded-full"
                    :style="teamAccentStyle(injury)"
                  ></span>

                  <div>
                    <div class="font-semibold text-gray-200">
                      Season {{ injury.season_id }}
                    </div>

                    <div class="text-[9px] uppercase tracking-wider text-gray-600">
                      Injury Record
                    </div>
                  </div>
                </div>
              </td>

              <!-- Role -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-md border text-[10px] font-semibold uppercase tracking-wide"
                  :class="roleBadge(injury.role)"
                >
                  {{ formatText(injury.role) }}
                </span>
              </td>

              <!-- Team -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span
                    class="h-7 w-7 rounded-lg flex items-center justify-center border border-white/10 shadow-inner"
                    :style="teamBadgeStyle(injury)"
                  >
                    <i class="fa fa-users text-[10px] text-white/80"></i>
                  </span>

                  <span class="font-medium text-gray-300">
                    {{ injury.team_when_injured || "Unknown Team" }}
                  </span>
                </div>
              </td>

              <!-- Injury Details -->
              <td class="px-4 py-3">
                <div class="max-w-[500px]">
                  <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span
                      class="text-[10px] font-bold uppercase tracking-wide text-red-300"
                    >
                      {{ formatText(injury.injury_type) }}
                    </span>

                    <span
                      v-if="injury.status"
                      class="h-1 w-1 rounded-full bg-gray-700"
                    ></span>

                    <span class="text-[10px] text-gray-600">
                      Medical Report
                    </span>
                  </div>

                  <p
                    class="leading-relaxed text-gray-400"
                  >
                    {{ injury.details || "No additional details available." }}
                  </p>
                </div>
              </td>

              <!-- Games Missed -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span
                    class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-gray-900 border border-gray-800 px-2 text-sm font-bold text-gray-200"
                  >
                    {{ injury.recovery_games ?? 0 }}
                  </span>

                  <span class="text-[10px] text-gray-600">
                    games
                  </span>
                </div>
              </td>

              <!-- Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-[10px] font-bold uppercase tracking-wide"
                  :class="statusBadge(injury.status)"
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="statusDot(injury.status)"
                  ></span>

                  {{ injury.status || "Unknown" }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div
        class="flex items-center justify-between px-4 py-2.5 border-t border-gray-800 bg-gray-900/40"
      >
        <span class="text-[10px] text-gray-600">
          {{ injuries.data.length }}
          {{ injuries.data.length === 1 ? "injury record" : "injury records" }}
        </span>

        <span class="text-[10px] text-gray-700">
          Click a record to view details
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";

const props = defineProps({
  player_id: {
    type: Number,
    required: true,
  },
});

const injuries = ref({
  data: [],
});

const loading = ref(false);
const player_id = ref(props.player_id);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const formatText = (value) => {
  if (value === null || value === undefined || value === "") {
    return "—";
  }

  return String(value)
    .replaceAll("_", " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

const normalizeColor = (color, fallback = "374151") => {
  if (!color) {
    return `#${fallback}`;
  }

  const cleaned = String(color).replace("#", "").trim();

  return `#${cleaned}`;
};

const teamAccentStyle = (injury) => {
  const primary = normalizeColor(injury.primary_color, "ef4444");

  return {
    backgroundColor: primary,
    boxShadow: `0 0 12px ${primary}55`,
  };
};

const teamBadgeStyle = (injury) => {
  const primary = normalizeColor(injury.primary_color, "374151");
  const secondary = normalizeColor(
    injury.secondary_color,
    injury.primary_color || "1f2937"
  );

  return {
    background: `linear-gradient(135deg, ${primary}, ${secondary})`,
  };
};

/*
|--------------------------------------------------------------------------
| Role Badge
|--------------------------------------------------------------------------
*/

const roleBadge = (role) => {
  const value = String(role || "").toLowerCase();

  if (
    value.includes("star") ||
    value.includes("franchise") ||
    value.includes("all-star")
  ) {
    return "bg-purple-500/10 border-purple-500/20 text-purple-300";
  }

  if (value.includes("starter")) {
    return "bg-blue-500/10 border-blue-500/20 text-blue-300";
  }

  if (value.includes("bench")) {
    return "bg-yellow-500/10 border-yellow-500/20 text-yellow-300";
  }

  if (value.includes("reserve")) {
    return "bg-gray-500/10 border-gray-600/30 text-gray-400";
  }

  return "bg-cyan-500/10 border-cyan-500/20 text-cyan-300";
};

/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

const statusBadge = (status) => {
  const value = String(status || "").toLowerCase();

  if (value === "injured") {
    return "bg-red-500/10 border-red-500/20 text-red-300";
  }

  if (value === "recovered") {
    return "bg-green-500/10 border-green-500/20 text-green-300";
  }

  return "bg-gray-500/10 border-gray-600/30 text-gray-400";
};

const statusDot = (status) => {
  const value = String(status || "").toLowerCase();

  if (value === "injured") {
    return "bg-red-400 shadow-[0_0_8px_rgba(248,113,113,0.7)]";
  }

  if (value === "recovered") {
    return "bg-green-400 shadow-[0_0_8px_rgba(74,222,128,0.7)]";
  }

  return "bg-gray-500";
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchPlayerInjuryHistory = async () => {
  if (!player_id.value) {
    injuries.value = { data: [] };
    return;
  }

  try {
    loading.value = true;

    const response = await axios.post(
      route("players.season.injury"),
      {
        player_id: player_id.value,
      }
    );

    /*
     * The original API appears to return:
     *
     * {
     *     data: [...]
     * }
     *
     * Keep the component tolerant in case the endpoint returns
     * the array directly.
     */
    if (Array.isArray(response.data)) {
      injuries.value = {
        data: response.data,
      };
    } else {
      injuries.value = response.data || {
        data: [],
      };
    }
  } catch (error) {
    console.error(
      "Error fetching player injury history:",
      error
    );

    injuries.value = {
      data: [],
    };
  } finally {
    loading.value = false;
  }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
  fetchPlayerInjuryHistory();
});

watch(
  () => props.player_id,
  (newPlayerId) => {
    player_id.value = newPlayerId;
    fetchPlayerInjuryHistory();
  }
);
</script>