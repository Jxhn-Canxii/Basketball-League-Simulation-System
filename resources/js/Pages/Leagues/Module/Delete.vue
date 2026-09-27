<template>
    <div>
        <!-- Delete Button -->
        <button
            type="button"
            @click.prevent="confirmDelete"
            :disabled="deleting"
            class="group inline-flex items-center gap-2 rounded-xl border border-red-500/20 bg-red-500/[0.07] px-3 py-2 text-xs font-black text-red-400 transition-all duration-200 hover:border-red-500/40 hover:bg-red-500/[0.12] hover:text-red-300 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-lg bg-red-500/10 transition group-hover:bg-red-500/20"
            >
                <i
                    v-if="!deleting"
                    class="fas fa-trash text-[10px]"
                ></i>

                <i
                    v-else
                    class="fas fa-spinner fa-spin text-[10px]"
                ></i>
            </span>

            <span>
                {{ deleting ? "Removing..." : "Remove" }}
            </span>
        </button>
    </div>
</template>

<script setup>
import { ref } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);

const props = defineProps({
    league_id: {
        type: Object,
        default: 0,
    },
});

const deleting = ref(false);

const confirmDelete = (league) => {

    Swal.fire({
        title: "Remove League?",
        text: `Remove the league system?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Remove League",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        background: "#0d1117",
        color: "#e2e8f0",
        confirmButtonColor: "#7f1d1d",
        cancelButtonColor: "#334155",
        customClass: {
            popup: "rounded-xl border border-slate-800",
        },
    }).then(async (result) => {
        if (!result.isConfirmed) {
            return;
        }
        try {
            deleting.value = true;

            const response = await axios.post(
                route("leagues.delete"),
                {
                    id: props.league_id,
                }
            );

            if (response) {
                await Swal.fire({
                    title: "League Removed",
                    text: "The league has been removed successfully.",
                    icon: "success",
                    background: "#0d1117",
                    color: "#e2e8f0",
                    confirmButtonColor: "#475569",
                });

                form.reset();

                emits('transaction_id',Math.random());
            }
        } catch (error) {
            console.error("Error deleting league:", error);

            Swal.fire({
                title: "Unable to Remove League",
                text:
                    error?.response?.data?.message ??
                    "Something went wrong while removing the league.",
                icon: "error",
                background: "#0d1117",
                color: "#e2e8f0",
                confirmButtonColor: "#475569",
            });
        }finally{
            deleting.value = false;
        }
    });
};
</script>

<style scoped>
:deep(.liga2-swal-popup) {
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    box-shadow:
        0 25px 60px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(255, 255, 255, 0.02);
}

:deep(.liga2-swal-title) {
    font-size: 18px;
    font-weight: 900;
    letter-spacing: -0.02em;
}

:deep(.liga2-swal-confirm) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    padding: 9px 16px;
    margin: 0 4px;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

:deep(.liga2-swal-confirm:hover) {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.5);
}

:deep(.liga2-swal-cancel) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    padding: 9px 16px;
    margin: 0 4px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #6b7280;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
}

:deep(.liga2-swal-cancel:hover) {
    background: rgba(255, 255, 255, 0.07);
    color: #d1d5db;
}
</style>