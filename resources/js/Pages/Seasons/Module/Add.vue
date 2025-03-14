<template>
    <button
        @click.prevent="newSeasonBehavior()"
        v-bind:class="{
            'opacity-25': isAddModalOpen,
        }"
        v-bind:disabled="isAddModalOpen"
        class="px-2 py-2 bg-blue-500 rounded font-bold text-md float-end text-white shadow"
    >
        <i class="fa fa-calendar-plus"></i> New Season
    </button>
    <Modal :show="isAddModalOpen" :maxWidth="'2xl'" title="New Season" @close="isAddModalOpen = false">
        <div
            v-if="isProcessing"
            class="fixed inset-0 bg-black top-50 left-50 text-white text-center text-sm bg-opacity-50 z-40"
        >
            Preparing Schedule...
        </div>
        <button
            class="flex float-end bg-gray-100 p-3"
            @click.prevent="isAddModalOpen = false"
        >
            <i class="fa fa-times text-black-600"></i>
        </button>
        <div class="relative grid grid-cols-1 gap-6 p-6">
            <h2 class="text-lg font-semibold text-gray-800">
                New Seasons
            </h2>
            <form class="mt-4" @submit.prevent="createNewSeason()">
                <div class="mb-4">
                    <label
                        for="FloorNo"
                        class="block text-sm font-medium text-gray-700"
                        >Name</label
                    >
                    <input
                        type="text"
                        id="FloorNo"
                        v-model="form.season_name"
                        minlength="1"
                        placeholder="Input Season Name"
                        name="FloorNo"
                        class="mt-1 p-2 border rounded-md w-full"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.season_name"
                    />
                </div>
                <div class="mb-4">
                    <label
                        for="FloorNo"
                        class="block text-sm font-medium text-gray-700"
                        >Type</label
                    >
                    <select
                        name=""
                        id=""
                        class="mt-1 p-2 border rounded-md w-full bg-gray-200"
                        v-model="form.type"
                        disabled
                    >
                        <option value="0">Select Type</option>
                        <option value="1" disabled>
                            Single Elimination
                        </option>
                        <option value="2">Single Round Robin</option>
                        <option value="3">Double Round Robin</option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.type"
                    />
                </div>
                <div class="mb-4">
                    <label
                        for="FloorNo"
                        class="block text-sm font-medium text-gray-700"
                        >Start Playoffs</label
                    >
                    <select
                        name=""
                        id=""
                        class="mt-1 p-2 border rounded-md w-full bg-gray-200"
                        v-model="form.start"
                        disabled
                    >
                        <option value="0">Select Start</option>
                        <option value="8" disabled>
                            on Quarter Finals
                        </option>
                        <option value="16">on Round of 16</option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.type"
                    />
                </div>
                <div class="mb-4">
                    <label
                        for="FloorNo"
                        class="block text-sm font-medium text-gray-700"
                        >Match Type</label
                    >
                    <select
                        name=""
                        id=""
                        class="mt-1 p-2 border rounded-md w-full bg-gray-200"
                        v-model="form.match_type"
                        disabled
                    >
                        <option value="0">Select Match Type</option>
                        <option value="1">by Conference</option>
                        <option value="2" disabled>All Teams</option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.type"
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
                        class="mt-1 p-2 border rounded-md w-full bg-gray-200"
                        v-model="form.league_id"
                        disabled
                    >
                        <option value="0">Select League</option>
                        <option
                            :value="league.id"
                            v-for="league in leagues_dropdown"
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
                <!-- Add more form fields as needed -->

                <div class="flex items-center">
                    <button
                        type="submit"
                        :disabled="isProcessing"
                        :class="isProcessing ? 'opacity-50' : ''"
                        class="bg-blue-500 text-white font-bold py-2 px-4 rounded"
                    >
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

const emit = defineEmits(["transaction_id"]);
const isAddModalOpen = ref(false);
const leagues_dropdown = ref([]);
const isProcessing = ref(false);
const form = useForm({
    type: 3,
    start: 16,
    league_id: 1,
    seasons_id: 0,
    conference_id: 0,
    match_type: 1,
    errors: [],
});
const newSeasonBehavior = () => {
    leagueDropdown();
    isAddModalOpen.value = true;
};
const leagueDropdown = async () => {
    try {
        const response = await axios.get(route("leagues.dropdown"));
        if (!response) {
            throw new Error("Failed to fetch leagues");
        }
        leagues_dropdown.value = await response.data; // Parse JSON response and assign it to the leagues array
    } catch (error) {
        console.error("Error fetching leagues:", error);
    }
};
const createNewSeason = async () => {
    if (form.league_id == 0) {
        Swal.fire({
            title: "Warning!",
            text: "Please assign league!",
            icon: "warning",
        });
        return false;
    } else {
        try {
            isProcessing.value = true;
            const response = await axios.post(route("create.schedule.regular"), form);
            isAddModalOpen.value = false;
            Swal.fire({
                icon: "success",
                title: "Success!",
                text: response.data.message, // Assuming the response contains a 'message' field
            });
            form.reset("name", "type", "league_id");
            isProcessing.value = false;
            emit('transaction_id',Math.random());
        } catch (error) {
            console.error("Error creating schedule:", error);
            // Show error message using Swal2 if needed
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: error.response.data.message,
            });
        }
    }
};
</script>
