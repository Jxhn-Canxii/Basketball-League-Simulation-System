<template>
    <Head title="Players" />

    <AuthenticatedLayout>
        <template #header>
            Players
        </template>

        <div class="overflow-hidden shadow-sm sm:rounded-lg min-h-screen p-3">
            <div class="grid grid-cols-1 gap-6">

                <div class="bg-white inline-block min-w-full overflow-hidden rounded shadow p-2">
                    <h3 class="text-md font-semibold text-gray-800">Player List</h3>
                    <div class="flex justify-between items-center space-x-3 mb-2">
                        <input
                            type="text"
                            v-model="search.search"
                            @input="fetchAllPlayers()"
                            id="LeagueName"
                            placeholder="Enter Player name"
                            class="mt-1 mb-2 p-2 border rounded w-full"
                        />
                        <select class="mt-1 mb-2 p-2 border rounded w-full" v-model="search.position" @change="fetchAllPlayers()">
                            <option value="">All Positions</option>
                            <option value="PG">Point Guard</option>
                            <option value="SG">Shooting Guard</option>
                            <option value="SF">Small Forward</option>
                            <option value="PF">Power Forward</option>
                            <option value="C">Center</option>
                        </select>
                        <select class="mt-1 mb-2 p-2 border rounded w-full" v-model="search.injury_status" @change="fetchAllPlayers()">
                            <option value="2">All</option>
                            <option value="1">Injured</option>
                            <option value="0">Healthy</option>
                        </select>
                        <select class="mt-1 mb-2 p-2 border rounded w-full" v-model="search.is_active" @change="fetchAllPlayers()">
                            <option value="2">All</option>
                            <option value="1">Active</option>
                            <option value="0">Retired</option>
                        </select>
                    </div>
                  
                    <div v-if="data.players?.length === 0" class="text-center text-gray-500">No player found.</div>
                    <div v-else class="overflow-x-auto mt-4">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-nowrap">
                                <tr>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Draft</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Country</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Awards</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Current Team</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Remaining Contract</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Retirement</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="!loading && data.players?.length > 0" v-for="player in data.players" :key="player.player_id" @click.prevent="showPlayerProfile(player)" class="hover:bg-gray-100">
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        {{ player.name }}
                                        <sup v-if="player.is_finals_mvp">
                                            <i class="fa fa-star fa-sm text-yellow-500"></i>
                                        </sup>
                                         <!-- Display "Injured" if player is injured -->
                                        <span v-if="player.is_injured && player.age <= player.retirement_age && player.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium text-red-800">
                                            <i class="fa fa-crutch"></i>
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.formatted_draft_status }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.overall_rating }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.position }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.country }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border text-wrap">{{ player.awards }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.team_name ?? '-' }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.contract_years ?? 0 }} yrs.</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.age }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">{{ player.retirement_age }}</td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <span :class="roleClasses(player.role)" class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium">
                                            {{ player.role }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 whitespace-nowrap border">
                                        <!-- Display "Active" if player is active and retirement age is less than their age -->
                                        <span v-if="player.team_id > 0 && player.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>

                                        <!-- Display "Waived/Free Agent" if player is waived/free agent and retirement age is less than their age -->
                                        <span v-if="player.team_id == 0 && player.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Free Agent</span>
                                        
                                        <!-- Display "Retired" if player is not active and retirement age is greater than or equal to their age -->
                                        <span v-if="!player.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Retired</span>

                                    </td>
                                </tr>
                                <tr v-if="!loading && data.players?.length == 0" class="hover:bg-gray-100">
                                    <td colspan="11" class="px-2 py-1 whitespace-nowrap border text-center text-red-500">
                                        <b>No player found!</b>
                                    </td>
                                </tr>
                                <tr v-if="loading" class="hover:bg-gray-100">
                                    <td colspan="11" class="px-2 py-1 whitespace-nowrap border text-center text-red-500">
                                        <div class="block text-center">
                                            <i class="fa fa-spinner fa-spin text-blue-500 text-4xl"></i>
                                            <p>Fetching player list...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex w-full overflow-auto">
                        <Paginator
                            v-if="data.total"
                            :page_number="search.page_num"
                            :total_rows="data.total ?? 0"
                            :itemsperpage="search.itemsperpage"
                            @page_num="handlePagination"
                        />
                    </div>

                </div>
            </div>
        </div>
        <Modal  :show="showPlayerProfileModal" :maxWidth="'6xl'" title="Player Information" @close="showPlayerProfileModal = false">
            <div class="p-6 block">
                <PlayerPerformance :key="selectedPlayer.player_id" :player_id="selectedPlayer.player_id" />
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from "vue";
import axios from 'axios'; // Ensure axios is imported
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";

import PlayerPerformance from './Module/PlayerPerformance.vue';

const showPlayerProfileModal = ref(false);
const selectedPlayer = ref([]);
const loading = ref(false);
const data = ref({
    free_agents: [],
    current_page: 1,
    total_pages: 0,
    position: '',
    total: 0,
});
const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: '',
    position: '',
    injury_status: 2, // 2 means all, 1 means injured, 0 means healthy
    is_active: 2, // 2 means all, 1 means active, 0 means retired
    itemsperpage: 10,
});
const teams = ref([]);


const fetchAllPlayers = async () => {
    try {
        loading.value = true;
        const response = await axios.post(route("players.list.all"), search.value);
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching free agents:", error);
    } finally{
        loading.value = false;
    }
};
const handlePagination = (page_num) => {
    search.value.page_num = page_num;
    fetchAllPlayers();
};
const showPlayerProfile = (player) => {
    selectedPlayer.value = player;
    showPlayerProfileModal.value = true;
};
const roleClasses = (role) => {
    switch (role) {
        case "starter":
            return "bg-blue-100 text-blue-800";
        case "star player":
            return "bg-yellow-100 text-yellow-800";
        case "all star":
            return "bg-red-100 text-red-800";
        case "role player":
            return "bg-green-100 text-green-800";
        case "bench":
            return "bg-gray-100 text-gray-800";
        default:
            return "bg-gray-200 text-gray-800"; // Default case
    }
};

onMounted(() => {
    fetchAllPlayers();
});
</script>
