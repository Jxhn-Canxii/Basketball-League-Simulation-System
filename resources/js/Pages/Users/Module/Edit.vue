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
        <Modal :show="isEditModalOpen" :maxWidth="'2xl'" title="Edit Team" @close="isEditModalOpen = false">
            <div class="grid grid-cols-1 gap-6 p-6">
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
                                placeholder="Enter new password"
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
    email: "",
    password: "",
});

onMounted(() => {
});

const Update = async () => {
    try {
        const response = await axios.post(route("users.update"), form);
        if (response) {
            Swal.fire({
                title: "Success!",
                text: "User info updated successfully.",
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
        console.error("Error updating users:", error);
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
    form.email = data.email;
    form.password = data.password;
    
    isEditModalOpen.value = true;
};
</script>
