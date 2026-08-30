<template>
    <div class="team-roster">
        <h2 class="text-xl font-semibold text-gray-800" v-if="props.showControls">Player Signings</h2>

        <!-- Divider -->
        <hr class="my-4 border-t border-gray-200" v-if="props.showControls" />
        <div class="mb-4">
            <TopStatistics :key="key" />
        </div>
        <!-- Players Table -->
        <div class="overflow-hidden">
            <div
                class="bg-white inline-block min-w-full overflow-hidden rounded shadow p-2"
            >
                <h3 class="text-md font-semibold text-gray-800">
                    Free Agents List
                </h3>
               
                <div class="flex justify-between mt-4" v-if="props.showControls">
                    <div>
                        <button
                            @click="assignTeamsAuto()"
                            class="px-4 py-2 bg-rose-500 text-white rounded mb-2 text-sm"
                        >
                            <i class="fa fa-users"></i> Distribute Free Agents
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
                            @click.prevent="addMultiplePlayers(60)"
                            class="px-4 py-2 bg-green-700 text-white rounded mb-4 text-sm"
                        >
                            <i class="fa fa-user"></i> Add Undrafted Rookie
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
                    v-if="data.free_agents?.length === 0"
                    class="text-center text-gray-500"
                >
                    No free agents found.
                </div>
                <div v-else class="overflow-auto mt-4">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-nowrap">
                            <tr>
                                <!-- Existing columns -->
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Draft
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    POS
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Awards
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Experience
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Country
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Age
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Role
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Archetype
                                </th>
                                <!-- New columns for ratings -->
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                    title="Overall Rating"
                                >
                                    Overall
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                    title="Potential Rating"
                                >
                                    POT
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                    title="Shooting Rating"
                                >
                                    SH
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                    title="Defense Rating"
                                >
                                    DEF
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                    title="Passing Rating"
                                >
                                    PAS
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                    title="Rebounding Rating"
                                >
                                   REB
                                </th>
                                <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <!-- <th
                                    class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th> -->
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="player in data.free_agents"
                                :key="player.id"
                                @click.prevent="showPlayerProfileModal = player.id"
                                class="hover:bg-gray-100"
                            >
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{ player.draft_status == 'Undrafted' ? 'S'+player.draft_id+' '+player.draft_status : player.draft_status + (player.drafted_team ? ' ('+player.drafted_team+ ')' : '')}}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    <span>
                                        {{ player.name }}
                                        <sup v-if="player.is_finals_mvp">
                                            <i class="fa fa-star fa-sm text-yellow-500"></i>
                                        </sup>
                                    </span>
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border text-wrap">
                                    {{ player.position }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border text-wrap">
                                    {{ player.awards }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{ player.is_rookie == 1 ? 'Rookie' : 'Veteran' }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{ player.country }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{ player.age }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    <span
                                        :class="roleBadgeClass(player.role)"
                                    >
                                        {{ player.role }}
                                    </span>
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border uppercase">
                                    {{ player.type.replaceAll('_',' ') }}
                                </td>
                                <!-- New columns for ratings -->
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{
                                        parseFloat(
                                            player.overall_rating || 0
                                        ).toFixed(1)
                                    }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{
                                        parseFloat(
                                            player.potential_rating || 0
                                        ).toFixed(1)
                                    }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{
                                        parseFloat(
                                            player.shooting_rating || 0
                                        ).toFixed(1)
                                    }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{
                                        parseFloat(
                                            player.defense_rating || 0
                                        ).toFixed(1)
                                    }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{
                                        parseFloat(
                                            player.passing_rating || 0
                                        ).toFixed(1)
                                    }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    {{
                                        parseFloat(
                                            player.rebounding_rating || 0
                                        ).toFixed(1)
                                    }}
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap border">
                                    <span
                                        v-if="player.is_active"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                                        >Active</span
                                    >
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                                        >Waived</span
                                    >
                                </td>
                                <!-- <td class="px-2 py-1 whitespace-nowrap border">
                                    <button
                                        @click="assignTeams(player.player_id)"
                                        class="px-2 py-1 bg-blue-500 text-white rounded-l text-xs"
                                    >
                                        Assign New Team
                                    </button>
                                </td> -->
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
        <Modal :show="showPlayerProfileModal" :maxWidth="'6xl'" title="Player Profile" @close="showPlayerProfileModal = false">
            <div class="p-6 block">
                <!-- Image Section -->
                <PlayerPerformance :key="showPlayerProfileModal" :player_id="showPlayerProfileModal" />
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
import TopStatistics from "@/Pages/Analytics/Module/TopStatistics.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";
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

const addPlayer = async (info) => {
    try {
        const response = await axios.post(route("players.add.free.agent"), {
            name: info.name,
            address: info.address,
            country: info.country,
        });
        // return response.data.message; // Return success message for logging
        Swal.fire({
            icon: "success",
            title:`${ response.data.player.name } | ${ response.data.player.age } | ${ response.data.player.position } has added to Draft Pool!`,
            text: response.data.message, // Assuming the response contains a 'message' field
        });

        key.value = Math.random();
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
                promises.push(addPlayer(randomFullName)); // Add the promise to the array
            }
        }


        // Wait for all promises to resolve
        const results = await Promise.all(promises);
        fetchAvailablePlayers(); // Refresh free agent list
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
            route("players.free.agents"),
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
const assignTeams = async (player_id) => {
    try {
        // Show confirmation dialog
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to assign this player to a team?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, assign it!",
            cancelButtonText: "No, cancel",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            // Proceed with the request if confirmed
            const response = await axios.post(route("assign.freeagent.teams"), {
                player_id: player_id,
            });

            Swal.fire({
                icon: "success",
                title: "Success!",
                text: response.data.message, // Assuming the response contains a 'message' field
            });
            const is_new_season = response.data.team_count == 0;
            if (is_new_season) {
                emits("newSeason", is_new_season);
            }
            fetchFreeAgent();
        } else {
            // Show cancellation message if canceled
            Swal.fire({
                icon: "info",
                title: "Cancelled",
                text: "The player was not assigned to a team.",
            });
        }
    } catch (error) {
        console.error("Error assigning team:", error);
        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text: error.response.data.message,
        });
    }
};

const assignTeamsAuto = async () => {
    try {
        // Show confirmation dialog
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to assign free agents to teams?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, assign them!",
            cancelButtonText: "No, cancel",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            // Show loading/swipe dialog
            const loadingSwal = Swal.fire({
                title: "Processing...",
                text: "Assigning free agents to teams. Please wait.",
                icon: "info",
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();  // Show the loading animation
                }
            });

            // Proceed with the request if confirmed
            const response = await axios.post(route("auto.assign.freeagent.teams"));
            const data = response.data;
            let message = "";
            console.log(data.message);

            if (data.message === "All teams have signed 12 players.") {
                message = `<p>${data.message}</p>`;
            } else if (data.message === "No free agents available.") {
                message = `<p>${data.message}</p>`;
                if (data.incomplete_teams.length > 0) {
                    message += "<ul>";
                    data.incomplete_teams.forEach((team) => {
                        message += `<li><strong>${team.team_name}</strong>: ${team.players_needed} player(s) needed</li>`;
                    });
                    message += "</ul>";
                }
            } else {
                message = `<p>${data.message}</p>`;
                if (data.remaining_free_agents > 0) {
                    message += `<p>Remaining free agents: ${data.remaining_free_agents}</p>`;
                }
                if (data.incomplete_teams.length > 0) {
                    message += "<ul>";
                    data.incomplete_teams.forEach((team) => {
                        message += `<li><strong>${team.team_name}</strong>: ${team.players_needed} player(s) still needed</li>`;
                    });
                    message += "</ul>";
                }
            }

            // Close loading animation and show result message
            loadingSwal.close();

            Swal.fire({
                icon: "success",
                title: "Success!",
                html: message,
            });

            // Check if a new season should be emitted
            const is_new_season = response.data.team_count === 0;
            if (is_new_season) {
                emits("newSeason", is_new_season);
            }

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
