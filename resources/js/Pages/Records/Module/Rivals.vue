<template>
    <div class="bg-white overflow-hidden shadow-sm rounded min-h-full p-3">
        <h3 class="text-md font-semibold text-gray-800">Top Rivals</h3>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5" v-if="rivals">
            <div
                v-for="(team,tt) in rivals.data"
                :key="tt"
                class="col-span-1"
            >
                <div
                    class="bg-white shadow-md rounded-md overflow-hidden"
                >
                    <div class="px-4 py-5 sm:px-6">
                        <h3
                            class="text-xs font-bold text-nowrap uppercase leading-6 text-gray-800"
                        >
                            {{ team.team_name }}
                        </h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <div
                            class="bg-gray-100 px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                        >
                            <dt
                                class="text-sm font-medium text-gray-500"
                            >
                                Win
                            </dt>
                            <dd
                                class="mt-1 text-sm text-gray-900 sm:col-span-2"
                            >
                                {{ team.wins }}
                                <span
                                    v-if="
                                        team.wins >
                                        team.losses
                                    "
                                    class="ml-2 text-yellow-500"
                                >
                                    <i class="fas fa-medal"></i>
                                </span>
                            </dd>
                        </div>
                        <div
                            class="bg-gray-200 px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
                        >
                            <dt
                                class="text-sm font-medium text-gray-500"
                            >
                                Loss
                            </dt>
                            <dd
                                class="mt-1 text-sm text-gray-900 sm:col-span-2"
                            >
                                {{ team.losses }}
                                <span
                                    v-if="
                                        team.losses >
                                        team.wins
                                    "
                                    class="ml-2 text-yellow-500"
                                >
                                    <i class="fas fa-medal"></i>
                                </span>
                            </dd>
                        </div>
                        <!-- Additional details can be added here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from "vue";
import { roundNameFormatter,generateRandomKey, moneyFormatter } from "@/Utility/Formatter";
import Paginator from "@/Components/Paginator.vue";

const rivals = ref([]);

const fetchRivalry = async () => {
    try {
        const response = await axios.post(route("records.rivalries"));
        rivals.value = response.data;
} catch (error) {
        console.error("Error fetching recent results:", error);
    }
};

onMounted(()=>{
    fetchRivalry();
});
</script>
