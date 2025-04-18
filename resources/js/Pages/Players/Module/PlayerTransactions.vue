<template>
    <div>
        <h2 class="text-sm font-semibold text-gray-800 mb-4">
            Player Transactions
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
                            Role
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Transfer Details
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Old Team
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            New Team
                        </th>
                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-nowrap">
                    <tr v-for="(transaction, index) in transactions" v-if="transactions?.length > 0" :key="transaction.id" @click.prevent="isViewModalOpen = transaction.season_id" class="hover:bg-gray-100">
                        <td class="px-2 py-1 text-gray-700">Season {{ transaction.season_id }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.player_name }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.latest_role }}</td>
                        <td class="px-2 py-1 text-gray-700 text-wrap">{{ transaction.merged_details }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.from_team_name ?? 'Free Agent' }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.to_team_name ?? 'Free Agent' }}</td>
                        <td class="px-2 py-1 text-gray-700">{{ transaction.status }}</td>
                    </tr>
                    <tr class="hover:bg-gray-100" v-else>
                        <td class="px-2 py-1 text-red-500 text-center font-semibold" colspan="7">No data available</td>
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
const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});
const transactions = ref([]);
const player_id = ref(props.player_id);
// Watch for changes in player_id
// Fetch data on component mount
onMounted(() => {
    fetchPlayerTransactions();
});
const fetchPlayerTransactions = async () => {
    try {
        const response = await axios.post(route("players.season.transactions"), {
            player_id:  player_id.value,
        });
        transactions.value = response.data;
    } catch (error) {
        console.error("Error fetching player season performance:", error);
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
