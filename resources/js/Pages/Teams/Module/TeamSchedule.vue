<template>
  <div>
    <div class="block">
      <div class="flex justify-left items-center mb-2">
        <h2 class="text-lg font-semibold text-gray-800">
          Schedule and Results ({{ data?.total_count }})
        </h2>
      </div>
      <div
        v-if="data && data.schedules?.length > 0 && !loadingSchedules"
        class="grid sm:col-span-1 md:grid-cols-2 gap-6"
      >
        <ScoreCard v-for="(match, index) in data.schedules" :key="index" :match="match" />
      </div>
      <div v-if="loadingSchedules">
        <p class="text-gray-500">Loading Schedules.</p>
      </div>
      <div v-if="data && data.schedules?.length == 0 && !loadingSchedules">
        <p class="text-gray-500">No schedule available.</p>
      </div>
      <div
        class="flex w-full overflow-auto"
        v-if="data && data.schedules?.length > 0 && !loadingSchedules"
      >
        <Paginator
          v-if="data.total_count"
          :page_number="search_schedule.page_num"
          :total_rows="data.total_count ?? 0"
          :itemsperpage="search_schedule.itemsperpage"
          @page_num="handlePagination"
        />
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";

const loadingSchedules = ref(false);
const data = ref([]);

const props = defineProps({
  season_id: { type: [Number, String], required: true },
  team_id: { type: [Number, String], required: true },
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
const searchInput = () => {
  search_schedule.value.page_num = 1;
  fetchConferenceSchedules();
};

const fetchConferenceSchedules = async () => {
  try {
    loadingSchedules.value = true;
    search_schedule.value.season_id = props.season_id;
    search_schedule.value.team_id = props.team_id;

    const response = await axios.post(
      route("team.season.schedules"),
      search_schedule.value
    );
    data.value = response.data;
    loadingSchedules.value = false;
  } catch (error) {
    console.error("Error fetching season standings:", error);
  }
};

const handlePagination = (page_num) => {
  console.log(page_num);
  search_schedule.value.page_num = page_num ?? 1;
  fetchConferenceSchedules();
};

onMounted(async () => {
  await fetchConferenceSchedules();
});
</script>
<style></style>
