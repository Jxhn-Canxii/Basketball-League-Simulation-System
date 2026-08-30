<template>
    <div>
            <button
                @click.prevent="isAddModalOpen = true"
                v-bind:class="{ 'opacity-25': isAddModalOpen }"
                v-bind:disabled="isAddModalOpen"
                class="px-2 py-2 bg-blue-500 font-bold mb-4 text-md float-end text-white rounded shadow"
            >
                <i class="fa fa-plus"></i> Add User
            </button>
            <Modal :show="isAddModalOpen" :maxWidth="'2xl'" title="Add User" @close="isAddModalOpen = false">
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
                                placeholder="Enter name"
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
                                >Email</label
                            >
                            <input
                                type="email"
                                v-model="form.email"
                                id="LeagueName"
                                placeholder="Enter email"
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
                                >Password</label
                            >
                            <input
                                type="password"
                                v-model="form.password"
                                id="LeagueName"
                                placeholder="Enter password"
                                class="mt-1 p-2 border rounded-md w-full"
                            />
                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
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
    email: "",
    password: 0,
});

onMounted(() => {
});


const Add = async () => {
    try {
        const response = await axios.post(route("users.add"), form);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: "User added successfully.",
                icon: "success",
            });
            // Close the modal and reset form
            form.reset("name");
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
        console.error("Error adding user:", error);
        Swal.fire({
            title: "Warning!",
            text: error.response.data.message,
            icon: "warning",
        });
    }
};
</script>
