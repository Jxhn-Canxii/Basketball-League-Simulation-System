<template>
    <div class="team-roster">
        <h2 class="text-xl font-semibold text-gray-800" v-if="props.showControls">Coach Signings</h2>

        <!-- Players Table -->
        <div class="overflow-hidden">
            <div
                class="bg-white inline-block min-w-full overflow-hidden rounded shadow p-2"
            >
                <h3 class="text-md font-semibold mb-4 text-gray-800">
                    Free Agents Coach List
                </h3>
               
                <div class="flex justify-between mt-4" v-if="props.showControls">
                    <div class="space-x-2">
                        <button
                            @click="assignTeamsAuto()"
                            class="px-4 py-2 bg-rose-500 text-white rounded mb-2 text-sm"
                        >
                            <i class="fa fa-users"></i> Auto Sign Coaches
                        </button>
                        <button 
                            @click="endCoachSigning" 
                            class="px-4 py-2 bg-gray-950 text-white rounded mb-2 text-sm">
                            <i class="fa fa-arrow-right"></i> Skip
                        </button>
                    </div>
                    <div>

                        <!-- <button
                            @click="showAddPlayerModal = true"
                            class="px-4 py-2 bg-green-500 text-white rounded mb-4 mr-2 text-sm"
                        >
                            <i class="fa fa-user"></i> Add Rookie Player
                        </button> -->
                        <button
                            @click.prevent="addMultiplePlayers(80)"
                            class="px-4 py-2 bg-green-700 text-white rounded mb-4 text-sm"
                        >
                            <i class="fa fa-user"></i> Invite Coaches
                        </button>
                    </div>
                </div>
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
                <div v-else class="overflow-auto mt-4">
                   <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-nowrap">
                            <tr>
                                <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                <th class="px-2 py-1 text-right font-medium text-gray-500 uppercase tracking-wider">Coach IQ</th>
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
                            <tr v-for="coach in data.coaches" :key="coach.id" class="hover:bg-gray-100">
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
                        v-if="data.total_pages"
                        :page_number="search.page_num"
                        :total_rows="data.total_pages ?? 0"
                        :itemsperpage="search.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
        </div>
        <Modal :show="showAddPlayerModal" :maxWidth="'6xl'" title="Add Player" @close="showAddPlayerModal = false">
            <!-- Modal Content -->
            <div class="flex flex-col">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    Add New Player
                </h3>
                <!-- Form Fields -->
                <div class="mb-4">
                    <label
                        for="playerName"
                        class="block text-sm font-medium text-gray-700"
                        >Player Name</label
                    >
                    <input
                        id="playerName"
                        v-model="newPlayer.name"
                        type="text"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    />
                </div>
                <div class="mb-4">
                    <label
                        for="playerTeam"
                        class="block text-sm font-medium text-gray-700"
                        >Team ID</label
                    >
                    <input
                        id="playerTeam"
                        v-model="newPlayer.team_id"
                        type="text"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    />
                </div>
                <div class="flex items-center justify-end">
                    <button
                        @click="addPlayer()"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md"
                    >
                        Add Player
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import axios from "axios"; // Ensure axios is imported
import Swal from "sweetalert2";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import { roleBadgeClass } from "@/Utility/Formatter";

const showAddPlayerModal = ref(false);
const showPlayerProfileModal = ref(false);
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

const fetchRandomFullName = async () => {
    try {
        // https://randomuser.me/api/?inc=name,gender,location,nat&gender=male
        const response = await axios.get(' https://randomuser.me/api/?inc=name,gender,location,nat&gender=male'); // API URL for random male user
        const { first, last } = response.data.results[0].name; // Extract first and last name
        const { city, state, country} = response.data.results[0].location; // Extract first and last name
        const nationality = response.data.results[0].nat; // Extract first and last name
        const address = `${city}, ${state}, ${country}`; // Extract first and last name
        const name = `${first} ${last}`;
        const country_formatted = `${country}`;
        const data = {
            name: name,
            country: country_formatted,
            address: address,
        };
        // Function to check if a name contains only English alphabet letters
        const isEnglishReadable = (name) => /^[A-Za-z]+$/.test(name);

        if (isEnglishReadable(first) && isEnglishReadable(last)) {
            return data; // Return full name if valid
        } else {
            return null; // Return null if the name is not valid
        }
    } catch (error) {
        console.error("Error fetching random player name:", error);
        return null; // Return null on error
    }
};
const addPlayer = async (info) => {
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

const fetchRandomFullName1 = async () => {
    try {
        // https://randomuser.me/api/?inc=name,gender,location,nat&gender=male
        const response = await axios.get('https://randomuser.me/api/?inc=name,gender,location,nat&gender=male'); // API URL for random male user
        const { first, last } = response.data.results[0].name; // Extract first and last name
        const { city, state, country} = response.data.results[0].location; // Extract first and last name
        const nationality = response.data.results[0].nat; // Extract first and last name
        const address = `${city}, ${state}, ${country}`; // Extract first and last name
        const name = `${first} ${last}`;
        const country_formatted = `${country}`;
        const data = {
            name: name,
        };
        // Function to check if a name contains only English alphabet letters
        const isEnglishReadable = (name) => /^[A-Za-z]+$/.test(name);

        if (isEnglishReadable(first) && isEnglishReadable(last)) {
            return data; // Return full name if valid
        } else {
            return null; // Return null if the name is not valid
        }
    } catch (error) {
        console.error("Error fetching random player name:", error);
        return null; // Return null on error
    }
};
const fetchRandomFullName2 = async () => {
    try {
        let data = null;
        
        while (!data) {  // Keep retrying until a valid male name is found
            const response = await axios.get('https://fakerapi.it/api/v1/persons?_quantity=1&gender=male');

            const person = response.data.data[0];
            const first_name = person.firstname;
            const last_name = person.lastname;
            const city = person.address.city;
            const country = person.address.country;
            const gender = person.gender.toLowerCase();

            if (gender === "male") {  // Ensure we get a male name
                const fullName = `${first_name} ${last_name}`;

                data = {
                    name: fullName,
                };

                // Check if both first and last names contain only English alphabet letters
                const isEnglishReadable = (name) => /^[A-Za-z]+$/.test(name);
                
                if (!isEnglishReadable(first_name) || !isEnglishReadable(last_name)) {
                    data = null; // Reset and retry if the name contains non-English characters
                }
            }
        }

        return data;
    } catch (error) {
        console.error("Error fetching random player name:", error);
        return null; 
    }
};


const addMultiplePlayers = async (count) => {
    try {
        const promises = [];

        for (let i = 0; i < count; i++) {
            // Randomly choose between fetchRandomFullName1 or fetchRandomFullName2
            const fetchRandomFullName = Math.random() < 0.5 ? await fetchRandomFullName2 : await fetchRandomFullName2; // 50% chance for each

            const randomFullName = await fetchRandomFullName(); // Fetch random full name
            console.log(randomFullName);
            if (randomFullName != null) {
                promises.push(addPlayer(randomFullName)); // Add the promise to the array
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

            emits("newSeason", is_new_season);

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
                text: 'Signing successfully ended!',
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
