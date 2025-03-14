<template>
    <div class="team-info p-4" v-if="team_info.teams" :style="{ backgroundColor: '#'+team_info.teams.secondary_color, }">
        <h2 class="text-xl font-semibold text-white" v-if="team_info.teams">
            {{ team_info.teams.team_name ?? "-" }} ({{ team_info.teams.acronym ?? "-" }})
        </h2>
        <span
            v-if="team_info.teams"
            class="inline-flex items-center px-2.5 py-0.5 bg-green-300 text-green-600 rounded text-xs font-medium"
        >
            {{ team_info.teams.conference_name ?? "-" }}
        </span>
        <hr class="my-4 border-t border-gray-200" />
        <div class="gap-4 max-h-100">
            <table class="min-w-full divide-y divide-gray-200 p-2">
                <thead class="text-nowrap" :style="{ backgroundColor: '#'+team_info.teams.primary_color,color: 'white' }">
                    <tr>
                        <th class="px-2 py-3 text-left text-xs font-medium  uppercase tracking-wider">Season</th>
                        <th class="px-2 py-3 text-left text-xs font-medium  uppercase tracking-wider">Player</th>
                        <th class="px-2 py-3 text-left text-xs font-medium  uppercase tracking-wider">From Team</th>
                        <th class="px-2 py-3 text-left text-xs font-medium  uppercase tracking-wider">To Team</th>
                        <th class="px-2 py-3 text-left text-xs font-medium  uppercase tracking-wider">Details</th>
                        <th class="px-2 py-3 text-left text-xs font-medium  uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(transaction, index) in team_history.transactions" :key="transaction.id" @click.enter.prevent="showPlayerProfileModal = transaction.player_id" class="hover:bg-gray-200">
                        <td class="px-2 py-3 whitespace-nowrap border">Season {{ transaction.season_id }}</td>
                        <td class="px-2 py-3 whitespace-nowrap border">{{ transaction.player_name }}</td>
                        <td class="px-2 py-3 whitespace-nowrap border" :class="transaction.from_team_name == transaction.to_team_name ? 'text-center' : ''" :colspan="transaction.from_team_name == transaction.to_team_name ? 2 : 1">{{ transaction.from_team_name ?? 'Free Agent' }}</td>
                        <td class="px-2 py-3 whitespace-nowrap border" :hidden="transaction.from_team_name == transaction.to_team_name">{{ transaction.to_team_name ?? 'Free Agent' }}</td>
                        <td class="px-2 py-3 whitespace-nowrap border">{{ transaction.details }}</td>
                        <td class="px-2 py-3 whitespace-nowrap border">{{ transaction.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination Controls -->
        <div class="flex w-full overflow-auto">
            <Paginator
              v-if="team_history.total_items"
              :page_number="search.page_num"
              :total_rows="team_history.total_items ?? 0"
              :itemsperpage="search.itemsperpage"
              @page_num="handlePagination"
            />
          </div>
    </div>
    <Modal :show="showPlayerProfileModal" :maxWidth="'6xl'" title="Player Profile" @close="showPlayerProfileModal = false">
        <div class="p-6 block">
            <!-- Image Section -->
            <PlayerPerformance :key="showPlayerProfileModal" :player_id="showPlayerProfileModal" />
        </div>
    </Modal>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import { roundNameFormatter } from "@/Utility/Formatter";
import { ref, onMounted, computed, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import Swal from "sweetalert2";
import axios from "axios";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    team_id: {
        type: Number,
        required: true
    }
});
const showPlayerProfileModal = ref(false);
const team_info = ref([]);
const team_history = ref([]);

const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 10,
});
;
onMounted(() => {
    fetchTeamTransactionHistory();
    fetchTeamInfo();
});
const fetchTeamInfo = async () => {
    try {
        const response = await axios.post(route("teams.info"), {
            team_id: props.team_id,
        });
        team_info.value = response.data;
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};
const fetchTeamTransactionHistory = async () => {
    try {
        search.value.team_id = props.team_id;
        const response = await axios.post(route("teams.transaction.history"),search.value);
        team_history.value = response.data;
    } catch (error) {
        console.error("Error fetching team info:", error);
    }
};
const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;
    fetchTeamTransactionHistory();
}
const isNumberChecker = (round) => {
    return !isNaN(round) && !isNaN(parseFloat(round));
};
</script>
