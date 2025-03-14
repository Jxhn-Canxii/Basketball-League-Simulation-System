<template>
    <div>
            <button
                @click.prevent="isAddModalOpen = true"
                v-bind:class="{ 'opacity-25': isAddModalOpen }"
                v-bind:disabled="isAddModalOpen"
                class="px-2 py-2 bg-blue-500 font-bold mb-4 text-md float-end text-white rounded shadow"
            >
                <i class="fa fa-plus"></i> Add Team
            </button>
            <Modal :show="isAddModalOpen" :maxWidth="'2xl'" title="Add Team" @close="isAddModalOpen = false">
                <div class="grid grid-cols-1 gap-6 p-6">
                    <form class="mt-4" @submit.prevent="Add()">
                        <div class="mb-4">
                            <label
                                for="LeagueName"
                                class="block text-sm font-medium text-gray-700"
                                >Name</label
                            >
                            <input
                                type="text"
                                v-model="form.name"
                                id="LeagueName"
                                placeholder="Enter team name"
                                class="mt-1 p-2 border rounded-md w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                for="LeagueName"
                                class="block text-sm font-medium text-gray-700"
                                >Acronym</label
                            >
                            <input
                                type="text"
                                v-model="form.acronym"
                                id="LeagueName"
                                maxlength="4"
                                placeholder="Enter team Acronym"
                                class="mt-1 p-2 border rounded-md w-full uppercase"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.acronym"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                for="FloorNo"
                                class="block text-sm font-medium text-gray-700"
                                >League</label
                            >
                            <select
                                name=""
                                id=""
                                class="mt-1 p-2 border rounded-md w-full"
                                v-model="form.league_id" @change.prevent="conferenceDropdown(form.league_id)"
                            >
                                <option value="0">Select League</option>
                                <option
                                    :value="league.id"
                                    v-for="league in leagues"
                                    :key="league.id"
                                >
                                    {{ league.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.league_id"
                            />
                        </div>
                        <div class="mb-4">
                            <label
                                for="FloorNo"
                                class="block text-sm font-medium text-gray-700"
                                >League</label
                            >
                            <select
                                name=""
                                id=""
                                class="mt-1 p-2 border rounded-md w-full"
                                v-model="form.conference_id"
                            >
                                <option value="0">Select conference</option>
                                <option
                                    :value="conference.id"
                                    v-for="conference in conferences"
                                    :key="conference.id"
                                >
                                    {{ conference.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.conference_id"
                            />
                        </div>
                        <div class="flex items-center">
                            <button
                                type="submit"
                                class="bg-blue-500 text-white font-bold py-2 px-4 rounded"
                            >
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
    </div>
</template>

<script setup>
import {useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);
const isAddModalOpen = ref(false);
const leagues = ref(false);
const conferences = ref(false);


const form = useForm({
    id: 0,
    name: "",
    acronym: "",
    league_id: 0,
    conference_id: 0,
});

onMounted(() => {
    leagueDropdown();
});

const leagueDropdown = async () => {
    try {
        const response = await axios.get(route("leagues.dropdown")); // Fetch data from the API endpoint
        leagues.value = response.data; // Parse JSON response and assign it to the leagues array
    } catch (error) {
        console.error("Error fetching leagues:", error);
    }
};
const conferenceDropdown = async (league_id) => {
    try {
        const response = await axios.post(route("conference.season.dropdown"),{league_id : league_id}); // Fetch data from the API endpoint
        conferences.value = response.data; // Parse JSON response and assign it to the league conference array
    } catch (error) {
        console.error("Error fetching leagues:", error);
    }
};

const Add = async () => {
    try {
        const response = await axios.post(route("teams.add"), form);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: "Team added successfully.",
                icon: "success",
            });
            // Close the modal and reset form
            form.reset("name");
            // Refresh leagues
            fetchTeams();
        } else {
            Swal.fire({
                title: "Warning!",
                text: response.data.message,
                icon: "warning",
            });
        }
        isAddModalOpen.value = false;
        emits('transaction_id',Math.random());
    } catch (error) {
        console.error("Error adding team:", error);
        Swal.fire({
            title: "Warning!",
            text: error.response.data.message,
            icon: "warning",
        });
    }
};
</script>
