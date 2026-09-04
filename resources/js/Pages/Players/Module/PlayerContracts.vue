<template>
    <div>
        <h2 class="text-sm font-semibold text-gray-800 mb-4">
            Player Contracts
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-nowrap">
                    <tr>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Season
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Player Name
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">
                            Salary
                        </th>
                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">
                            Contract Years
                        </th>
                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">
                            Years Remaining
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Team Name
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Player Option
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Team Option
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Trade Clause
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-nowrap">
                    <tr v-for="(transaction, index) in transactions" v-if="transactions?.length > 0 && !loading" :key="transaction.id" @click.prevent="isViewModalOpen = transaction.season_id" class="hover:bg-gray-100">
                        <td class="px-2 py-1 text-gray-700">Season {{ transaction.season_id }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.player_name }}</td>
                        <td class="px-2 py-1 text-gray-700 uppercase">{{ transaction.contract_type }}</td>
                        <td class="px-2 py-1 text-gray-700 text-wrap text-right">{{ moneyFormatter(transaction.salary ?? 0) }}</td>
                        <td class="px-2 py-1 text-gray-700 text-right">{{ transaction.contract_years ?? 0 }} yrs.</td>
                        <td class="px-2 py-1 text-gray-700 text-right">{{ (transaction.status == 'signed') ? transaction.years_remaining : 0 }} yrs.</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.team_name ?? 'Free Agent' }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.player_option == 0 ? 'No' : 'Yes' }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.team_option  == 0 ? 'No' : 'Yes' }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.no_trade_clause  == 0 ? 'No' : 'Yes' }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.status }}</td>
                    </tr>
                    <tr class="hover:bg-gray-100" v-if="!transactions?.length && !loading">
                        <td class="px-2 py-1 text-red-500 text-center font-semibold" colspan="11">No data available</td>
                    </tr>
                    <tr v-if="loading">
                        <td class="px-2 py-1 text-gray-500 text-center" colspan="11">
                            <div class="block text-center">
                                <i class="fa fa-spinner fa-spin text-blue-500 text-4xl"></i>
                                <p>Loading player data...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import PlayerGameLogs from "./PlayerGameLogs.vue";
import Modal from "@/Components/Modal.vue";
import ProfileHeader from "./ProfileHeader.vue";
import { moneyFormatter} from "@/Utility/Formatter.js";
const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});
const transactions = ref([]);
const player_id = ref(props.player_id);
const loading = ref(false);
// Watch for changes in player_id
// Fetch data on component mount
onMounted(() => {
    fetchPlayerTransactions();
});
const fetchPlayerTransactions = async () => {
    try {
        loading.value = true;
        const response = await axios.post(route("players.contracts.history"), {
            player_id:  player_id.value,
        });
        transactions.value = response.data;
        loading.value = false;
    } catch (error) {
        loading.value = false;
        console.error("Error fetching player season performance:", error);
    } finally {
        loading.value = false;
    }
};
const roleClasses = (role) => {
    switch (role) {
        case "starter":
            return "bg-blue-100 text-blue-800";
        case "star player":
            return "bg-yellow-100 text-yellow-800";
        case "role player":
            return "bg-green-100 text-green-800";
        case "bench":
            return "bg-gray-100 text-gray-800";
        default:
            return "bg-gray-200 text-gray-800"; // Default case
    }
};
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
