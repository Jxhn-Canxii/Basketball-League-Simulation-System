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
    team_id: Number,
});

const form = useForm({
    id: 0,
});

const Delete = async () => {
    // Show a confirmation dialog
    Swal.fire({
        title: "Are you sure?",
        text: "You are about to delete this team.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel!",
        reverseButtons: true,
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                form.team_id = props.team_id ?? 0;
                const response = await axios.post(route("teams.delete"), form);
                if (response) {
                    Swal.fire({
                        title: "Success!",
                        text: "Team removed successfully.",
                        icon: "success",
                    });
                    // Refresh leagues
                    emits('transaction_id',Math.random());
                } else {
                    throw new Error("Failed to delete team");
                }
            } catch (error) {
                console.error("Error deleting team:", error);
            }
        }
    });
};
</script>
