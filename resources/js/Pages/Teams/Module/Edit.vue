<template>
    <div>
        <button
            @click.prevent="fillForm()"
            v-bind:class="{
                'opacity-25': isEditModalOpen,
            }"
            v-bind:disabled="isEditModalOpen"
            class="px-2 py-2 bg-yellow-500 font-bold text-md float-center text-white shadow"
        >
            <i class="fa fa-edit"></i> Edit
        </button>
        <Modal :show="isEditModalOpen" :maxWidth="'2xl'">
            <button
                class="flex float-end bg-gray-100 p-3"
                @click.prevent="isEditModalOpen = false"
            >
                <i class="fa fa-times text-black-600"></i>
            </button>
            <div class="grid grid-cols-1 gap-6 p-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    Add Team
                </h2>
                <form class="mt-4" @submit.prevent="Update()">
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
import { Head, useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);
const props = defineProps({
    data: Object,
});

const isEditModalOpen = ref(false);
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

const Update = async () => {
    try {
        const response = await axios.post(route("teams.update"), form);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: "Team info updated successfully.",
                icon: "success",
            });
            // Close the modal
            form.reset("name");
        } else {
            Swal.fire({
                title: "Warning!",
                text: response.data.message,
                icon: "warning",
            });
        }
        isEditModalOpen.value = false;
        emits('transaction_id',Math.random());
    } catch (error) {
        console.error("Error updating team:", error);
         Swal.fire({
            title: "Warning!",
            text: error.response.data.message,
            icon: "warning",
        });
    }
};


const fillForm = () => {
    const data = props.data;
    form.reset();
    form.id = data.id;
    form.name = data.name;
    form.acronym = data.acronym;
    form.league_id = data.league_id;
    form.conference_id = data.conference_id;

    leagueDropdown();
    conferenceDropdown(data.league_id);
    isEditModalOpen.value = true;
};
</script>
