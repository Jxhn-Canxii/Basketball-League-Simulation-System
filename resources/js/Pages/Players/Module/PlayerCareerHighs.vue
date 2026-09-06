<template>
    <div>
        <h2 class="text-sm font-semibold text-gray-800 mb-4">
            Player Career High
        </h2>
        <div class="overflow-x-auto p-2 grid md:grid-cols-4 xs:grid-cols-1 gap-2">
            <div 
            v-if="transactions?.length > 0 && !loading" 
            v-for="(transaction, index) in transactions" :key="transaction.id" 
            class="border-lg bg-yellow-500 p-2 flex flex-col text-center rounded-xl shadow-xl">
                <span class="px-2 py-1 rounded-full text-[11px] uppercase hidden font-semibold bg-green-500 fixed text-white">
                    {{ transaction.status.replaceAll('-',' ') }}
                </span>
                <b class="text-6xl text-red-500">{{ transaction.stats_value }}</b>
                <div class="border-lg uppercase text-bold text-md">
                    {{ transaction.stats_type?.replaceAll('_',' ') }}
                </div>
                <p class="text-xs text-gray-800">{{ transaction.details.replaceAll('_',' ') }} in Season {{ transaction.season_id }}</p>
            </div>
            <div 
            v-if="transactions?.length > 0 && !loading"
            v-for="i in (16 - transactions?.length)"
            :key="i"
            class="border-lg bg-gray-600 opacity-60 p-2 flex flex-col text-center rounded-xl shadow-xl"
            >
                
            </div>
            <div v-if="!transactions?.length && !loading">
                
            </div>
            <div v-if="loading" class="md:col-span-4 xs:col-span-1 flex justify-center">
                <div class="block text-center">
                    <i class="fa fa-spinner fa-spin text-blue-500 text-4xl"></i>
                    <p>Loading player career highs...</p>
                </div>
            </div>
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
const loading = ref(false);
// Watch for changes in player_id
// Fetch data on component mount
onMounted(() => {
    fetchPlayerTransactions();
});
const fetchPlayerTransactions = async () => {
    try {
        loading.value = true;
        const response = await axios.post(route("players.career.highs"), {
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
