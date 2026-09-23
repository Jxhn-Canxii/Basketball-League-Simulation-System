```vue
<template>
  <section class="w-full">
    <!-- Header -->
    <div
      class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-800 bg-gray-950/95 p-5 shadow-xl shadow-black/10 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/10"
        >
          <i class="fas fa-calendar-alt text-lg text-white"></i>
        </div>

        <div>
          <h2 class="text-base font-bold tracking-tight text-white sm:text-lg">
            Schedule & Results
          </h2>

          <p class="mt-0.5 text-xs text-gray-500 sm:text-sm">
            Team fixtures and completed games
          </p>
        </div>
      </div>

      <!-- Match count -->
      <div
        v-if="!loadingSchedules && data?.total_count !== undefined"
        class="flex w-fit items-center gap-2 rounded-full border border-gray-800 bg-gray-900 px-3 py-1.5"
      >
        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>

        <span class="text-xs font-medium text-gray-400">
          {{ data.total_count }}
          {{ data.total_count === 1 ? "Game" : "Games" }}
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="loadingSchedules"
      class="grid grid-cols-1 gap-5 xl:grid-cols-2"
    >
      <div
        v-for="n in search_schedule.itemsperpage"
        :key="n"
        class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 p-5"
      >
        <div class="animate-pulse space-y-5">
          <div class="flex items-center justify-between">
            <div class="h-3 w-24 rounded bg-gray-800"></div>
            <div class="h-3 w-16 rounded bg-gray-800"></div>
          </div>

          <div class="flex items-center justify-center gap-8">
            <div class="flex flex-col items-center gap-3">
              <div class="h-14 w-14 rounded-full bg-gray-800"></div>
              <div class="h-3 w-20 rounded bg-gray-800"></div>
            </div>

            <div class="h-8 w-10 rounded bg-gray-800"></div>

            <div class="flex flex-col items-center gap-3">
              <div class="h-14 w-14 rounded-full bg-gray-800"></div>
              <div class="h-3 w-20 rounded bg-gray-800"></div>
            </div>
          </div>

          <div class="h-8 w-full rounded-lg bg-gray-900"></div>
        </div>
      </div>
    </div>

    <!-- Schedule -->
    <div
      v-else-if="data?.schedules?.length > 0"
      class="grid grid-cols-1 gap-5 xl:grid-cols-2"
    >
      <div
        v-for="(match, index) in data.schedules"
        :key="match.id ?? index"
        class="group min-w-0 overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 shadow-lg shadow-black/10 transition-all duration-300 hover:-translate-y-0.5 hover:border-gray-700 hover:shadow-xl"
      >
        <ScoreCard :match="match" />
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="flex min-h-[260px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-800 bg-gray-950/70 px-6 text-center"
    >
      <div
        class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-900 ring-1 ring-gray-800"
      >
        <i class="fas fa-calendar-xmark text-xl text-gray-600"></i>
      </div>

      <h3 class="text-sm font-semibold text-gray-300 sm:text-base">
        No schedule available
      </h3>

      <p class="mt-1 max-w-sm text-xs leading-5 text-gray-600 sm:text-sm">
        There are currently no games scheduled for this team and season.
      </p>
    </div>

    <!-- Pagination -->
    <div
      v-if="data?.schedules?.length > 0 && !loadingSchedules && data?.total_count"
      class="mt-5 flex w-full justify-center overflow-x-auto rounded-2xl border border-gray-800 bg-gray-950 p-3"
    >
      <Paginator
        :page_number="search_schedule.page_num"
        :total_rows="data.total_count ?? 0"
        :itemsperpage="search_schedule.itemsperpage"
        @page_num="handlePagination"
      />
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Paginator from "@/Components/Paginator.vue";
import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";

const props = defineProps({
  season_id: {
    type: [Number, String],
    required: true,
  },

  team_id: {
    type: [Number, String],
    required: true,
  },
});

const loadingSchedules = ref(false);

const data = ref({
  schedules: [],
  total_count: 0,
});

const search_schedule = ref({
  page_num: 1,
  total_pages: 0,
  total: 0,
  search: "",
  conference_id: 0,
  team_id: 0,
  season_id: 0,
  itemsperpage: 6,
});

const fetchConferenceSchedules = async () => {
  loadingSchedules.value = true;

  try {
    search_schedule.value.season_id = props.season_id;
    search_schedule.value.team_id = props.team_id;

    const response = await axios.post(
      route("team.season.schedules"),
      search_schedule.value
    );

    data.value = response.data ?? {
      schedules: [],
      total_count: 0,
    };
  } catch (error) {
    console.error("Error fetching season schedules:", error);

    data.value = {
      schedules: [],
      total_count: 0,
    };
  } finally {
    loadingSchedules.value = false;
  }
};

const handlePagination = (page_num) => {
  search_schedule.value.page_num = page_num ?? 1;
  fetchConferenceSchedules();
};

onMounted(() => {
  fetchConferenceSchedules();
});
</script>
```
