<template>
    <div class="overflow-hidden shadow-sm sm:rounded-lg w-screen min-h-screen p-3">
        <div class="grid grid-cols-1 gap-6">
            <h2 class="text-xl font-semibold text-gray-800" v-if="props.showControls">Coach Signings</h2>

            <!-- Coach Table -->
            <div class="overflow-hidden w-full">
                <div
                    class="bg-white overflow-hidden rounded shadow p-2"
                >
                    <h3 class="text-md font-semibold mb-4 text-gray-800">
                        Free Agents Coach List
                    </h3>
                    <!-- Show the count -->
                    <div class="text-gray-600 mb-6">
                    Total: <span class="font-semibold">{{ data?.teams_without_coach?.length }}</span> team(s) without a coach.
                    </div>

                    <!-- Debugging / Viewing the Raw Data -->
                    <!-- <pre class="bg-gray-100 p-4 rounded mb-6 text-sm">{{ teams_without_coach }}</pre> -->

                    <!-- Display as Badges -->
                    <div v-if="data?.teams_without_coach?.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="team in data.teams_without_coach"
                            :key="team.id"
                            class="px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-medium shadow hover:bg-red-200 transition"
                        >
                            {{ team.name }}
                        </span>
                    </div>
                    <div class="flex justify-between mt-4" v-if="props.showControls">
                        <div class="space-x-2">
                            <button
                                @click="assignTeamsAuto()"
                                v-if="data?.teams_without_coach_count > 0"
                                class="px-4 py-2 bg-rose-500 text-white rounded mb-2 text-sm"
                            >
                                <i class="fa fa-users"></i> Auto Sign Coaches
                            </button>
                            <button 
                                @click="endCoachSigning"
                                v-if="data?.teams_without_coach_count == 0"
                                class="px-4 py-2 bg-gray-950 text-white rounded mb-2 text-sm">
                                <i class="fa fa-arrow-right"></i> Skip
                            </button>
                        </div>
                        <div>
                            <button
                                @click.prevent="addMultiplePlayers(80)"
                                class="px-4 py-2 bg-green-700 text-white rounded mb-4 text-sm"
                            >
                                <i class="fa fa-user"></i> Invite Coaches
                            </button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <input
                            type="text"
                            v-model="search.search"
                            @input="fetchFreeAgent()"
                            id="LeagueName"
                            placeholder="Enter Player name"
                            class="mb-2 p-2 border rounded w-full"
                        />
                        <div
                            v-if="data.coaches?.length === 0"
                            class="text-center text-gray-500"
                        >
                            No coaches signed found.
                        </div>
                        <!-- <div v-else class="overflow-auto w-screen mt-4"> -->
                        <div v-else class="overflow-auto mt-4 w-full">

                            <table class="divide-y w-full divide-gray-200 text-xs">
                                <thead class="bg-gray-50 text-nowrap">
                                    <tr>
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">Coach IQ</th>
                                        <th class="px-2 py-1 text-left text-wrap font-medium text-gray-500 uppercase tracking-wider">Contract (Years)</th>
                                        <th class="px-2 py-1 text-left text-wrap font-medium text-gray-500 uppercase tracking-wider">Exp (Years)</th>
                                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">Retirement Age</th>
                                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">Career Wins</th>
                                        <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">Career Losses</th>
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Winning %</th>
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="coach in data.coaches" @click.prevent="showCoachProfile(coach)" :key="coach.id" class="hover:bg-gray-100">
                                        <td class="px-2 py-1 whitespace-nowrap border">
                                            {{ coach.id }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border">
                                            {{ coach.name }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border">
                                            {{ coach.team_name || 'Free Agent' }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border text-right">
                                            {{ coach.coach_iq }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border">
                                            {{ coach.contract_years }} yrs
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border">
                                            {{ coach.experience_years }} yrs
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border text-right">
                                            {{ coach.age }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border text-right">
                                            {{ coach.retirement_age }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border text-right">
                                            {{ coach.career_wins }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border text-right">
                                            {{ coach.career_losses }}
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border text-center">
                                            {{ parseFloat(coach.winning_percentage || 0).toFixed(1) }}%
                                        </td>
                                        <td class="px-2 py-1 whitespace-nowrap border">
                                            <span v-if="coach.is_active"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                            <span v-else
                                                class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                Retired
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Controls -->
                        <div class="flex w-full overflow-auto">
                            <Paginator
                                v-if="data.total_count"
                                :page_number="search.page_num"
                                :total_rows="data.total_count ?? 0"
                                :itemsperpage="search.itemsperpage"
                                @page_num="handlePagination"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <Modal  :show="showCoachProfileModal" :maxWidth="'8xl'" title="Coach Information" @close="showCoachProfileModal = false">
            <div class="p-6 block">
                <CoachProfile :key="selectedCoach.id" :coach_id="selectedCoach.id" />
            </div>
    </Modal>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios"; // Ensure axios is imported
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import CoachProfile from './Module/CoachProfile.vue';

import { roleBadgeClass } from "@/Utility/Formatter";

const showCoachProfileModal = ref(false);
const selectedCoach = ref([]);
const loading = ref(false);
const props = defineProps({
    showControls:{
        type: Boolean,
        default: true,
    }
});
const data = ref([]);
const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});
const teams = ref([]);
const key = ref(0);
const emits = defineEmits(["newSeason"]);

const showCoachProfile = (coach) => {
    selectedCoach.value = coach;
    showCoachProfileModal.value = true;
};

const fetchRandomFullName = async () => {
    try {
        // https://randomuser.me/api/?inc=name,gender,location,nat&gender=male
        const response = await axios.get(route('generate.new.player')); // API URL for random male user
        const { name, country, address } = response.data; // Extract first and last name
        const data = {
            name: name,
            country: country,
            address: address,
        };
        
        return data; // Return full name if valid
       
    } catch (error) {
        console.error("Error fetching random player name:", error);
        return null; // Return null on error
    }
};

const inviteCoach = async (info) => {
    try {
        const response = await axios.post(route("coaches.add.free.agent"), {
            name: info.name,
        });
        // return response.data.message; // Return success message for logging
        Swal.fire({
            icon: "success",
            title: response.data.message,
            text: 'Intend to apply as leagues coach', // Assuming the response contains a 'message' field
        });

        key.value = Math.random();
        fetchFreeAgent();
    } catch (error) {
        console.error("Error adding player:", error.response.data.message);
        throw new Error(error.response.data.message); // Throw error to be caught in Promise.all
    }
};

const addMultiplePlayers = async (count) => {
    try {
        const promises = [];

        for (let i = 0; i < count; i++) {    
            const randomFullName = await fetchRandomFullName(); // Fetch random full name
            console.log(randomFullName);
            if (randomFullName != null) {
                promises.push(inviteCoach(randomFullName)); // Add the promise to the array
            }
        }


        // Wait for all promises to resolve
        const results = await Promise.all(promises);
        fetchFreeAgent(); // Refresh free agent list
         key.value = Math.random();
        // Notify success
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: `Successfully added ${count} players.`,
        });

        // Optionally log results
        results.forEach((message, index) => {
            console.log(`Player ${index + 1}: ${message}`);
        });

    } catch (error) {
        console.error("Error adding multiple players:", error);
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: error.message, // Show the first error message encountered
        });
    }
};
const fetchFreeAgent = async (page = 1) => {
    try {
        const response = await axios.post(
            route("coaches.list"),
            search.value
        );
        data.value = response.data;
    } catch (error) {
        console.error("Error fetching free agents:", error);
    }
};
const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;
    fetchFreeAgent();
};

const assignTeamsAuto = async () => {
    try {
        // Show confirmation dialog
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to assign coach to teams?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, sign them!",
            cancelButtonText: "No, cancel",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            // Show loading/swipe dialog
            const loadingSwal = Swal.fire({
                title: "Processing...",
                text: "Assigning free agent coach to teams. Please wait.",
                icon: "info",
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();  // Show the loading animation
                }
            });

            // Proceed with the request if confirmed
            const response = await axios.get(route("assign.coach.teams"));
            loadingSwal.close();

            Swal.fire({
                icon: "success",
                title: "Success!",
                html: response.data.message,
            });

            emits("newSeason", Math.random());

            // Fetch updated free agents list
            fetchFreeAgent();
        } else {
            // Show cancellation message if canceled
            Swal.fire({
                icon: "info",
                title: "Cancelled",
                text: "The assignment process was canceled.",
            });
        }
    } catch (error) {
        console.error("Error assigning teams:", error);

        // Close any ongoing loading spinner if an error happens
        Swal.close();

        Swal.fire({
            icon: "warning",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "An unexpected error occurred.",
        });

        // Emitting new season status in case of error
        emits("newSeason", true);
    }
};

const endCoachSigning = async () => {
    try {

        const response = await axios.get(route('end.coach.signings'));
        if (response) {
            await Swal.fire({
                title: 'Success!',
                text: response.data.message,
                icon: 'success',
                confirmButtonText: 'OK'
            });
            fetchFreeAgent();
            emits("newSeason", Math.random());
        }
    } catch (error) {
        console.error("Error ending trade:", error);
        await Swal.fire({
            title: 'Error!',
            text: error.response.data.message,
            icon: 'error',
        });
    }
};
onMounted(() => {
    fetchFreeAgent();
});
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
