<template>
    <div>
        <button
            @click.prevent="Delete()"
            class="px-2 py-2 bg-red-500 font-bold text-md float-center text-white shadow"
        >
            <i class="fa fa-trash"></i> Remove
        </button>
    </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);
const props = defineProps({
    user_id: Number,
});

const form = useForm({
    id: 0,
});

const Delete = async () => {
    // Show a confirmation dialog
    Swal.fire({
        title: "Are you sure?",
        text: "You are about to delete this user.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel!",
        reverseButtons: true,
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                form.id = props.user_id ?? 0;
                const response = await axios.post(route("users.delete"), form);
                if (response) {
                    Swal.fire({
                        title: "Success!",
                        text: "User removed successfully.",
                        icon: "success",
                    });
                    // Refresh leagues
                    emits('transaction_id',Math.random());
                } else {
                    throw new Error("Failed to user team");
                }
            } catch (error) {
                console.error("Error user team:", error);
            }
        }
    });
};
</script>
