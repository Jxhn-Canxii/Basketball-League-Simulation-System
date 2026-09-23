<template>
  <div
    class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
  >
    <!-- Top accent -->
    <div
      class="h-px bg-gradient-to-r from-transparent via-slate-500 to-transparent"
    ></div>

    <!-- Header -->
    <div class="border-b border-slate-800 px-4 py-4 sm:px-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-[#0d1117]"
            >
              <i class="fas fa-basketball-ball text-sm text-slate-300"></i>
            </div>

            <div>
              <h3 class="text-base font-bold tracking-tight text-white sm:text-lg">
                Player Playoff Filters
              </h3>
              <p class="mt-0.5 text-xs text-slate-500">
                Career playoff appearances, advancement and championship history
              </p>
            </div>
          </div>
        </div>

        <!-- Result count -->
        <div
          v-if="players.total !== undefined"
          class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2"
        >
          <i class="fas fa-users text-xs text-slate-500"></i>
          <span class="text-xs font-medium text-slate-400">
            {{ players.total ?? 0 }} players
          </span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="border-b border-slate-800 bg-[#0a0d12] px-4 py-4 sm:px-5">
      <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_280px]">
        <!-- Search -->
        <div class="relative">
          <label
            class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500"
          >
            Search Player
          </label>

          <div class="relative">
            <i
              class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-600"
            ></i>

            <input
              type="text"
              v-model="search_filters.search"
              @input.prevent="fetchFilteredPlayers()"
              placeholder="Enter player name..."
              class="w-full rounded-xl border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-3 text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
            />
          </div>
        </div>

        <!-- Sort -->
        <div>
          <label
            class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500"
          >
            Sort By
          </label>

          <div class="relative">
            <i
              class="fas fa-sort-amount-down pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-600"
            ></i>

            <select
              v-model="search_filters.sort_by"
              @change="fetchFilteredPlayers()"
              class="w-full appearance-none rounded-xl border border-slate-800 bg-[#080b10] py-2.5 pl-9 pr-9 text-sm text-slate-300 outline-none transition focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
            >
              <option value="playoff_appearances">Most Playoff Appearances</option>
              <option value="finals_appearances">Most Finals Appearances</option>
              <option value="big_four">Most Big 4 Appearances</option>
              <option value="seasons_played">Seasons Played</option>
              <option value="championships_won">Championships</option>
            </select>

            <i
              class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-600"
            ></i>
          </div>
        </div>
      </div>

      <!-- Active sort -->
      <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">
          Active ranking
        </span>

        <span
          class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-[#0d1117] px-2.5 py-1 text-[10px] font-semibold text-slate-300"
        >
          <i class="fas fa-arrow-down text-[8px] text-slate-500"></i>
          {{ sortLabel }}
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="p-4 sm:p-5">
      <div class="space-y-2">
        <div
          v-for="index in 7"
          :key="index"
          class="animate-pulse rounded-xl border border-slate-800 bg-[#0d1117] p-4"
        >
          <div class="flex items-center gap-4">
            <div class="h-8 w-8 rounded-lg bg-slate-800"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-40 rounded bg-slate-800"></div>
              <div class="h-2.5 w-24 rounded bg-slate-900"></div>
            </div>
            <div class="hidden gap-2 md:flex">
              <div class="h-8 w-14 rounded bg-slate-800"></div>
              <div class="h-8 w-14 rounded bg-slate-800"></div>
              <div class="h-8 w-14 rounded bg-slate-800"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div
      v-else-if="!players.data || players.data.length === 0"
      class="flex min-h-[300px] items-center justify-center px-4"
    >
      <div class="text-center">
        <div
          class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-800 bg-[#0d1117]"
        >
          <i class="fas fa-user-slash text-lg text-slate-700"></i>
        </div>

        <p class="mt-4 text-sm font-semibold text-slate-400">No players found</p>

        <p class="mt-1 text-xs text-slate-600">
          Try changing your search or sorting criteria.
        </p>
      </div>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-[1450px] w-full border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-700 bg-[#11161d]">
            <!-- Player -->
            <th
              class="sticky left-0 z-30 min-w-[220px] border-r border-slate-800 bg-[#11161d] px-3 py-3 text-left"
            >
              <div class="flex items-center gap-2">
                <i class="fas fa-user text-[10px] text-slate-500"></i>
                <span
                  :class="
                    search_filters.sort_by === 'player_name'
                      ? 'text-white'
                      : 'text-slate-300'
                  "
                  class="text-[10px] font-black uppercase tracking-wider"
                >
                  Player
                </span>
              </div>
            </th>

            <!-- Status -->
            <th class="min-w-[95px] px-3 py-3 text-left">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Status
              </span>
            </th>

            <!-- Team -->
            <th class="min-w-[180px] px-3 py-3 text-left">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Current Team
              </span>
            </th>

            <!-- Playoffs -->
            <th
              class="min-w-[105px] px-3 py-3 text-right"
              :class="
                search_filters.sort_by === 'playoff_appearances'
                  ? 'bg-slate-800/80 text-white'
                  : ''
              "
            >
              <span
                :class="
                  search_filters.sort_by === 'playoff_appearances'
                    ? 'text-white'
                    : 'text-slate-300'
                "
                class="text-[10px] font-black uppercase tracking-wider"
              >
                Playoffs
              </span>
            </th>

            <!-- Play-in R1 -->
            <th class="min-w-[110px] px-3 py-3 text-right">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Play-in R1
              </span>
              <span
                class="mt-0.5 block text-[8px] font-normal normal-case text-slate-500"
              >
                10th vs 9th
              </span>
            </th>

            <!-- Play-in R2 -->
            <th class="min-w-[110px] px-3 py-3 text-right">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Play-in R2
              </span>
              <span
                class="mt-0.5 block text-[8px] font-normal normal-case text-slate-500"
              >
                8th vs 7th
              </span>
            </th>

            <!-- Play-in Finals -->
            <th class="min-w-[110px] px-3 py-3 text-right">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Play-in Finals
              </span>
            </th>

            <!-- Conf QF -->
            <th class="min-w-[105px] px-3 py-3 text-right">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Conf. QF
              </span>
            </th>

            <!-- Conf SF -->
            <th class="min-w-[105px] px-3 py-3 text-right">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Conf. SF
              </span>
            </th>

            <!-- Conf Finals -->
            <th class="min-w-[105px] px-3 py-3 text-right">
              <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-300"
              >
                Conf. Finals
              </span>
            </th>

            <!-- Big 4 -->
            <th
              class="min-w-[95px] px-3 py-3 text-right"
              :class="search_filters.sort_by === 'big_four' ? 'bg-slate-800/80' : ''"
            >
              <span
                :class="
                  search_filters.sort_by === 'big_four' ? 'text-white' : 'text-slate-300'
                "
                class="text-[10px] font-black uppercase tracking-wider"
              >
                Big 4
              </span>
            </th>

            <!-- Finals -->
            <th
              class="min-w-[110px] px-3 py-3 text-right"
              :class="
                search_filters.sort_by === 'finals_appearances' ? 'bg-slate-800/80' : ''
              "
            >
              <span
                :class="
                  search_filters.sort_by === 'finals_appearances'
                    ? 'text-white'
                    : 'text-slate-300'
                "
                class="text-[10px] font-black uppercase tracking-wider"
              >
                Nat'l Finals
              </span>
            </th>

            <!-- Championships -->
            <th
              class="min-w-[110px] px-3 py-3 text-right"
              :class="
                search_filters.sort_by === 'championships_won' ? 'bg-slate-800/80' : ''
              "
            >
              <span
                :class="
                  search_filters.sort_by === 'championships_won'
                    ? 'text-white'
                    : 'text-slate-300'
                "
                class="text-[10px] font-black uppercase tracking-wider"
              >
                Championships
              </span>
            </th>

            <!-- Seasons -->
            <th
              class="min-w-[105px] px-3 py-3 text-right"
              :class="
                search_filters.sort_by === 'seasons_played' ? 'bg-slate-800/80' : ''
              "
            >
              <span
                :class="
                  search_filters.sort_by === 'seasons_played'
                    ? 'text-white'
                    : 'text-slate-300'
                "
                class="text-[10px] font-black uppercase tracking-wider"
              >
                Seasons
              </span>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-900">
          <tr
            v-for="(player, index) in players.data"
            :key="player.id"
            class="group transition-colors hover:bg-slate-900/40"
          >
            <!-- Player -->
            <td
              class="sticky left-0 z-10 bg-[#080b10] px-3 py-3 group-hover:bg-[#0b0f15]"
            >
              <div class="flex items-center gap-3">
                <!-- Rank -->
                <div
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border"
                  :class="getRankClass(index)"
                >
                  <span class="text-[10px] font-black">
                    {{ getRank(index) }}
                  </span>
                </div>

                <div class="min-w-0">
                  <p
                    class="truncate font-bold text-slate-100"
                    :title="player.player_name"
                  >
                    {{ player.player_name }}
                  </p>

                  <p
                    v-if="player.total_playoff_games !== undefined"
                    class="mt-0.5 text-[10px] text-slate-600"
                  >
                    {{ player.total_playoff_games ?? 0 }} playoff games
                  </p>
                </div>
              </div>
            </td>

            <!-- Status -->
            <td class="px-3 py-3">
              <span
                v-if="player.active_status"
                class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-300"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                Active
              </span>

              <span
                v-else
                class="inline-flex items-center gap-1.5 rounded-md border border-slate-800 bg-slate-900 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-600"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>
                Retired
              </span>
            </td>

            <!-- Current Team -->
            <td class="px-3 py-3">
              <div class="max-w-[180px]">
                <p
                  class="truncate font-medium text-slate-300"
                  :title="player.current_team_name"
                >
                  {{ player.current_team_name ?? "—" }}
                </p>
              </div>
            </td>

            <!-- Playoff Series -->
            <td
              class="px-3 py-3 text-right"
              :title="`${player.total_playoff_games ?? 0} games`"
            >
              <StatCell
                :value="player.total_playoff_series_appearances"
                :active="search_filters.sort_by === 'playoff_appearances'"
              />
            </td>

            <!-- Play-in R1 -->
            <td class="px-3 py-3 text-right">
              <StatCell :value="player.play_ins_elims_round_1_appearances" />
            </td>

            <!-- Play-in R2 -->
            <td class="px-3 py-3 text-right">
              <StatCell :value="player.play_ins_elims_round_2_appearances" />
            </td>

            <!-- Play-in Finals -->
            <td class="px-3 py-3 text-right">
              <StatCell :value="player.play_ins_finals_appearances" />
            </td>

            <!-- Conference QF -->
            <td class="px-3 py-3 text-right">
              <StatCell :value="player.round_of_16_appearances" />
            </td>

            <!-- Conference SF -->
            <td class="px-3 py-3 text-right">
              <StatCell :value="player.quarter_finals_appearances" />
            </td>

            <!-- Conference Finals -->
            <td class="px-3 py-3 text-right">
              <StatCell :value="player.semi_finals_appearances" />
            </td>

            <!-- Big 4 -->
            <td class="px-3 py-3 text-right">
              <StatCell
                :value="player.interconference_semi_finals_appearances"
                :active="search_filters.sort_by === 'big_four'"
              />
            </td>

            <!-- Finals -->
            <td class="px-3 py-3 text-right">
              <StatCell
                :value="player.finals_appearances"
                :active="search_filters.sort_by === 'finals_appearances'"
              />
            </td>

            <!-- Championships -->
            <td class="px-3 py-3 text-right">
              <StatCell
                :value="player.championships_won"
                :active="search_filters.sort_by === 'championships_won'"
                :strong="true"
              />
            </td>

            <!-- Seasons -->
            <td class="px-3 py-3 text-right">
              <StatCell
                :value="player.experience ?? 0"
                :active="search_filters.sort_by === 'seasons_played'"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer / Pagination -->
    <div
      v-if="!isLoading && players.total"
      class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-5"
    >
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="text-[10px] uppercase tracking-wider text-slate-600">
          Showing
          <span class="font-semibold text-slate-400">
            {{ players.data?.length ?? 0 }}
          </span>
          of
          <span class="font-semibold text-slate-400">
            {{ players.total }}
          </span>
          players
        </div>

        <div class="overflow-x-auto">
          <Paginator
            :page_number="search_filters.page_num"
            :total_rows="players.total ?? 0"
            :itemsperpage="search_filters.itemsperpage"
            @page_num="handlePagination"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import Paginator from "@/Components/Paginator.vue";

const players = ref({
  data: [],
  total: 0,
  total_pages: 0,
});

const isLoading = ref(false);

const search_filters = useForm({
  page_num: 1,
  itemsperpage: 10,
  search: "",
  sort_by: "playoff_appearances",
  sort_order: "desc",
});

const sortLabel = computed(() => {
  const labels = {
    playoff_appearances: "Most Playoff Appearances",
    finals_appearances: "Most Finals Appearances",
    big_four: "Most Big 4 Appearances",
    seasons_played: "Seasons Played",
    championships_won: "Championships",
  };

  return labels[search_filters.sort_by] ?? "Playoff Appearances";
});

const fetchFilteredPlayers = async () => {
  isLoading.value = true;

  try {
    const response = await axios.post(route("filter.playoffs.player"), search_filters);

    players.value = response.data ?? {
      data: [],
      total: 0,
      total_pages: 0,
    };
  } catch (error) {
    console.error("Error fetching filtered players:", error);

    players.value = {
      data: [],
      total: 0,
      total_pages: 0,
    };
  } finally {
    isLoading.value = false;
  }
};

const handlePagination = (page_num) => {
  search_filters.page_num = page_num;
  fetchFilteredPlayers();
};

const getThClass = (column) => {
  const base = "px-3 py-3 text-[10px] font-bold uppercase tracking-wider";

  if (search_filters.sort_by === column) {
    return `${base} text-slate-200 bg-[#0d1117]`;
  }

  return `${base} text-slate-600`;
};

const getRank = (index) => {
  const currentPage = Number(search_filters.page_num) || 1;
  const perPage = Number(search_filters.itemsperpage) || 10;

  return (currentPage - 1) * perPage + index + 1;
};

const getRankClass = (index) => {
  if (index === 0) {
    return "border-slate-500 bg-slate-200 text-slate-950";
  }

  if (index === 1) {
    return "border-slate-600 bg-slate-800 text-slate-200";
  }

  if (index === 2) {
    return "border-slate-700 bg-slate-900 text-slate-300";
  }

  return "border-slate-800 bg-[#0d1117] text-slate-600";
};

/*
 * Small reusable stat component.
 * Keeps the large playoff table visually consistent without
 * creating a separate .vue file.
 */
const StatCell = defineComponent({
  props: {
    value: {
      type: [Number, String],
      default: 0,
    },
    active: {
      type: Boolean,
      default: false,
    },
    strong: {
      type: Boolean,
      default: false,
    },
  },

  setup(props) {
    return () =>
      h(
        "span",
        {
          class: [
            "inline-flex min-w-[36px] items-center justify-center rounded-md px-2 py-1.5 font-mono text-xs transition",
            props.active
              ? "border border-slate-600 bg-slate-800 text-white"
              : props.strong && Number(props.value) > 0
              ? "border border-slate-700 bg-slate-900 text-slate-200"
              : "text-slate-500",
          ],
        },
        props.value ?? 0
      );
  },
});

onMounted(() => {
  fetchFilteredPlayers();
});
</script>
