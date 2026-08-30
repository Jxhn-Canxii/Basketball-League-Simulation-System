```vue
<template>
    <div class="team-roster p-2 sm:p-3">
        <h2 class="mb-3 text-sm font-semibold text-gray-800">
            Regular Season Logs
        </h2>

        <div class="overflow-hidden rounded-lg bg-white shadow-lg">

            <!-- Table Scroll Container -->
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[1100px] border-collapse text-xs">

                    <!-- Header -->
                    <thead class="bg-gray-900 text-white">
                        <tr class="text-nowrap">

                            <th class="sticky left-0 z-20 bg-gray-900 px-2 py-3 text-left font-medium uppercase tracking-wider">
                                Season
                            </th>

                            <th class="px-2 py-3 text-left font-medium uppercase tracking-wider">
                                Team
                            </th>

                            <th class="px-2 py-3 text-right font-medium uppercase tracking-wider">
                                Wins
                            </th>

                            <th class="px-2 py-3 text-right font-medium uppercase tracking-wider">
                                Losses
                            </th>

                            <th class="px-2 py-3 text-right font-medium uppercase tracking-wider">
                                Conf. Rank
                            </th>

                            <th class="min-w-[150px] px-2 py-3 text-left font-medium uppercase tracking-wider">
                                Conf. Champion
                            </th>

                            <th class="px-2 py-3 text-right font-medium uppercase tracking-wider">
                                Nat'l Rank
                            </th>

                            <th class="min-w-[150px] px-2 py-3 text-left font-medium uppercase tracking-wider">
                                Nat'l Champion
                            </th>

                            <th class="min-w-[150px] px-2 py-3 text-left font-medium uppercase tracking-wider">
                                Conf. Finals
                            </th>

                            <th class="min-w-[150px] px-2 py-3 text-left font-medium uppercase tracking-wider">
                                Nat'l Finals
                            </th>

                            <th class="px-2 py-3 text-center font-medium uppercase tracking-wider">
                                Chemistry
                            </th>

                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="divide-y divide-gray-200">

                        <tr
                            v-for="(season, index) in coach_history.history"
                            :key="`${season.season_id}-${season.team_id}`"
                            class="transition-colors hover:bg-gray-100 text-white"
                            :style="{
                                backgroundColor: '#' + season.primary_color
                            }"
                        >

                            <!-- Season -->
                            <td
                                class="sticky left-0 z-10 border px-2 py-3 font-semibold whitespace-nowrap"
                                :style="{
                                    backgroundColor: '#' + season.primary_color
                                }"
                            >
                                {{ season.season_name }}
                            </td>

                            <!-- Team -->
                            <td class="border px-2 py-3 whitespace-nowrap font-medium">
                                {{ season.team_name }}
                            </td>

                            <!-- Wins -->
                            <td class="border px-2 py-3 text-right whitespace-nowrap">
                                {{ season.wins }}
                            </td>

                            <!-- Losses -->
                            <td class="border px-2 py-3 text-right whitespace-nowrap">
                                {{ season.losses }}
                            </td>

                            <!-- Conference Rank -->
                            <td class="border px-2 py-3 text-right whitespace-nowrap">
                                {{ season.conference_rank }}
                            </td>

                            <!-- Conference Champion -->
                            <td class="border px-2 py-3">
                                <span
                                    v-if="season.conference_rank == 1"
                                    class="inline-flex whitespace-nowrap rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700"
                                >
                                    Conference Champions
                                </span>

                                <span
                                    v-else-if="season.conference_rank == 2"
                                    class="inline-flex whitespace-nowrap rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                                >
                                    Conference Runner Up
                                </span>

                                <span
                                    v-else-if="season.conference_rank == 3"
                                    class="inline-flex whitespace-nowrap rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-900"
                                >
                                    Conference Third Place
                                </span>

                                <span
                                    v-else
                                    class="inline-flex whitespace-nowrap rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- National Rank -->
                            <td class="border px-2 py-3 text-right whitespace-nowrap">
                                {{ season.overall_rank }}
                            </td>

                            <!-- National Champion -->
                            <td class="border px-2 py-3">
                                <span
                                    v-if="season.overall_rank == 1"
                                    class="inline-flex whitespace-nowrap rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700"
                                >
                                    National Champions
                                </span>

                                <span
                                    v-else-if="season.overall_rank == 2"
                                    class="inline-flex whitespace-nowrap rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                                >
                                    National Runner Up
                                </span>

                                <span
                                    v-else-if="season.overall_rank == 3"
                                    class="inline-flex whitespace-nowrap rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-900"
                                >
                                    National Third Place
                                </span>

                                <span
                                    v-else
                                    class="inline-flex whitespace-nowrap rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- Conference Finals -->
                            <td class="border px-2 py-3">
                                <span
                                    v-if="season.won_semi_finals == 1"
                                    class="inline-flex whitespace-nowrap rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700"
                                >
                                    Champion
                                </span>

                                <span
                                    v-else
                                    class="inline-flex whitespace-nowrap rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- National Finals -->
                            <td class="border px-2 py-3">
                                <span
                                    v-if="season.won_finals == 1"
                                    class="inline-flex whitespace-nowrap rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700"
                                >
                                    Champion
                                </span>

                                <span
                                    v-else
                                    class="inline-flex whitespace-nowrap rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                >
                                    No Awards
                                </span>
                            </td>

                            <!-- Chemistry -->
                            <td class="border px-2 py-3 text-center whitespace-nowrap">
                                <i
                                    :class="getMoraleIcon(season.chemistry)"
                                    class="mr-1"
                                    :title="`${getMoraleTitle(season.chemistry)} ${season.chemistry}%`"
                                ></i>

                                {{ season.chemistry }}%
                            </td>

                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!coach_history.history?.length">
                            <td
                                colspan="11"
                                class="px-4 py-8 text-center text-sm text-gray-500"
                            >
                                No season history found.
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <!-- Mobile Scroll Hint -->
            <div class="border-t bg-gray-50 px-3 py-2 text-center text-[10px] text-gray-500 sm:hidden">
                ← Swipe horizontally to view more columns →
            </div>

            <!-- Pagination -->
            <div class="w-full overflow-x-auto border-t bg-white p-2">
                <Paginator
                    v-if="coach_history.total_items"
                    :page_number="search.page_num"
                    :total_rows="coach_history.total_items ?? 0"
                    :itemsperpage="search.itemsperpage"
                    @page_num="handlePagination"
                />
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import { roleBadgeClass } from "@/Utility/Formatter";

const props = defineProps({
  coach_id: {
    type: Number,
    required: true,
  },
});
const isViewModalOpen = ref(false);
const activeTab = ref("profile");
const coach_history = ref([]);
const loading = ref(false);
const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 10,
});
// Watch for changes in player_id
// Fetch data on component mount
onMounted(() => {
  fetchCoachSeasonPerformance();
});
const setActiveTab = (tab) => {
  activeTab.value = tab;
};

const fetchCoachSeasonPerformance = async () => {
  try {
    search.value.coach_id = props.coach_id;
    loading.value = true;
    const response = await axios.post(route("coach.season.history"),search.value);
    coach_history.value = response.data;
    loading.value = false;
  } catch (error) {
    loading.value = false;
    coach_history.value = [];
    console.error("Error fetching coach season performance:", error);
  } finally {
    loading.value = false;
  }
};

const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;
    fetchCoachSeasonPerformance();
}

const getMoraleIcon = (chemistry) => {
  if (chemistry >= 80) return 'fa-solid fa-face-laugh-beam text-yellow-500';
  if (chemistry >= 60) return 'fa-solid fa-face-smile text-green-500';
  if (chemistry >= 40) return 'fa-solid fa-face-meh text-gray-500';
  if (chemistry >= 20) return 'fa-solid fa-face-frown text-orange-500';
  return 'fa-solid fa-face-angry text-red-600';
}
const getMoraleTitle = (chemistry) => {
  if (chemistry >= 80) return 'Locked In';
  if (chemistry >= 60) return 'Confident';
  if (chemistry >= 40) return 'Steady';
  if (chemistry >= 20) return 'Uncertain';
  return 'Frustrated';
}
</script>

<style scoped>
.table {
  font-size: 0.75rem; /* Smaller text size */
}

.table th,
.table td {
  padding: 0.5rem; /* Smaller padding */
}
</style>
