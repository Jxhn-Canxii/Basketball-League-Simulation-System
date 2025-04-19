<template>
    <div class="grid md:grid-cols-6 grid-cols-1 gap-6">
        <div class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs">
            <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
                <i class="fa fa-users"></i> <!-- Total Players -->
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">Total Players</p>
                <p class="text-lg font-semibold text-black">{{ moneyFormatter(data.total_players ?? 0) }}</p>
            </div>
        </div>

        <div class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs">
            <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
                <i class="fa fa-star"></i> <!-- Total Rookies -->
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">Rookies</p>
                <p class="text-lg font-semibold text-black">{{ moneyFormatter(data.rookie_players ?? 0) }}</p>
            </div>
        </div>

        <div class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs">
            <div class="p-3 mr-4 text-red-500 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-500">
                <i class="fa fa-user-slash"></i> <!-- Total Retired -->
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">Retired</p>
                <p class="text-lg font-semibold text-black">{{ moneyFormatter(data.retired_players ?? 0) }}</p>
            </div>
        </div>
        <div class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs">
            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                <i class="fa fa-chess"></i> <!-- Total Free Agents -->
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">Active Players</p>
                <p class="text-lg font-semibold text-black">{{ moneyFormatter(data.active_players_with_team ?? 0) }}</p>
            </div>
        </div>
        <div class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs">
            <div class="p-3 mr-4 text-green-500 bg-red-100 rounded-full dark:text-red-100 dark:bg-green-500">
                <i class="fa fa-check"></i> <!-- Total Retired -->
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">Available Slots</p>
                <p class="text-lg font-semibold text-black">{{ moneyFormatter(data.total_available_slots ?? 0) }}</p>
            </div>
        </div>
        <div class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs">
            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                <i class="fa fa-user-circle"></i> <!-- Total Free Agents -->
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600">Free Agents</p>
                <p class="text-lg font-semibold text-black">{{ moneyFormatter(data.free_agents ?? 0) }}</p>
            </div>
        </div>
    </div>
    <!-- Position Needs Summary -->
    <div
    v-if="data.position_summary"
    class="flex items-center border shadow-xs p-4 bg-white rounded-lg shadow-xs md:col-span-3 mt-3"
    >
    <div class="p-3 mr-4 text-red-600 bg-red-100 rounded-full dark:text-red-100 dark:bg-red-600">
        <i class="fa fa-exclamation-triangle"></i>
    </div>
    <div>
        <p class="mb-2 text-sm font-medium text-gray-600">Position Needs</p>
        <p class="text-sm text-black">
        <span
            v-for="(needed, position) in data.position_summary"
            :key="position"
            v-if="isPosition(position) && needed > 0"
            class="inline-block mr-2 px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold"
        >
            {{ position }}: {{ needed }}
        </span>
        <span v-if="!hasNeededPositions">All positions sufficiently staffed</span>
        </p>
    </div>
    </div>

</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import axios from "axios"; // Ensure axios is imported
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import { moneyFormatter } from "@/Utility/Formatter";

const data = ref([]);
const fetchPlayerCount = async (page = 1) => {
    try {
        const response = await axios.get(route("analytics.player.count"));
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching player count:", error);
    }
};

// Helper to check if it's a position_needed field
const isPosition = (key) => ['PG_needed', 'SG_needed', 'SF_needed', 'PF_needed', 'C_needed'].includes(key);

// Whether there are any needs
const hasNeededPositions = computed(() => {
  if (!data.value.position_summary) return false;
  return Object.entries(data.value.position_summary).some(
    ([key, value]) => isPosition(key) && value > 0
  );
});

onMounted(() => {
    fetchPlayerCount();
});
</script>
