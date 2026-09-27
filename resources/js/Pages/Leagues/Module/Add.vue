<template>
    <div>
        <!-- Add Team Button -->
        <button
            type="button"
            @click.prevent="isAddModalOpen = true"
            :disabled="isAddModalOpen"
            class="group inline-flex items-center gap-2 rounded-xl border border-yellow-500/20 bg-yellow-500/[0.08] px-3.5 py-2.5 text-xs font-black text-yellow-500 transition-all duration-200 hover:border-yellow-500/40 hover:bg-yellow-500/[0.13] hover:text-yellow-400 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-lg bg-yellow-500/10 transition group-hover:bg-yellow-500/20"
            >
                <i class="fas fa-plus text-[10px]"></i>
            </span>

            <span>Add League</span>
        </button>

        <!-- Modal -->
        <Modal
            :show="isAddModalOpen"
            :maxWidth="'2xl'"
            title="Add League"
            @close="isAddModalOpen = false"
        >
            <div class="bg-[#080b10] p-5 sm:p-6">
                    <form @submit.prevent="Add">
                        <div class="mb-6">
                            <div
                                class="mb-5 flex items-center gap-3 border-b border-slate-800 pb-4"
                            >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-800 bg-[#0d1117]"
                                >
                                    <i
                                        class="fas fa-trophy text-xs text-slate-400"
                                    ></i>
                                </div>

                                <div>
                                    <div
                                        class="text-sm font-bold text-white"
                                    >
                                        New League
                                    </div>

                                    <div
                                        class="text-[10px] uppercase tracking-wider text-slate-600"
                                    >
                                        Competition setup
                                    </div>
                                </div>
                            </div>

                            <label
                                for="AddLeagueName"
                                class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                League Name
                            </label>

                            <input
                                id="AddLeagueName"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter league name"
                                class="w-full rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-700 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>

                        <div
                            class="flex justify-end gap-2 border-t border-slate-800 pt-4"
                        >
                            <button
                                type="button"
                                @click="closeAddModal"
                                class="rounded-md border border-slate-800 bg-[#0d1117] px-4 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-800 hover:text-white"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    form.processing ||
                                    !form.name.trim()
                                "
                                class="rounded-md border border-slate-600 bg-slate-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <i
                                    :class="
                                        form.processing
                                            ? 'fas fa-spinner fa-spin mr-2'
                                            : 'fas fa-plus mr-2'
                                    "
                                ></i>

                                {{
                                    form.processing
                                        ? "Creating..."
                                        : "Create League"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
        </Modal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);

const isAddModalOpen = ref(false);

const form = useForm({
    id: 0,
    name: "",
    conference: "",
});

const Add = async () => {
    if (!form.name.trim() || form.processing) {
        return;
    }

    form.processing = true;

    try {
        const response = await axios.post(
            route("leagues.add"),
            {
                name: form.name.trim(),
            }
        );

        if (response) {
            await Swal.fire({
                title: "League Created",
                text: "The league has been added successfully.",
                icon: "success",
                background: "#0d1117",
                color: "#e2e8f0",
                confirmButtonColor: "#475569",
            });

            form.reset();
            form.clearErrors();

            isAddModalOpen.value = false;

            emits('transaction_id',Math.random());
        }
    } catch (error) {
        console.error("Error adding league:", error);

        Swal.fire({
            title: "Unable to Create League",
            text:
                error?.response?.data?.message ??
                "Something went wrong while creating the league.",
            icon: "error",
            background: "#0d1117",
            color: "#e2e8f0",
            confirmButtonColor: "#475569",
        });
    } finally {
        form.processing = false;
    }
};
</script>

<style scoped>
.section-label {
    @apply text-[9px] font-black uppercase tracking-[0.18em] text-gray-500;
}

.field-label {
    @apply mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500;
}

.input-icon {
    @apply pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-700;
}

.dark-input {
    @apply w-full rounded-xl border border-white/[0.08] bg-white/[0.035] py-2.5 text-xs font-medium text-white placeholder-gray-700 outline-none transition;
}

.dark-input:focus {
    border-color: rgb(234 179 8 / 0.4);
    background-color: rgb(255 255 255 / 0.05);
    box-shadow: 0 0 0 3px rgb(234 179 8 / 0.05);
}

.dark-input:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

:deep(input::placeholder) {
    color: rgb(55 65 81);
}

:deep(select option) {
    background-color: #111111;
    color: white;
}

/* Native color input */
input[type="color"] {
    appearance: none;
    -webkit-appearance: none;
}

input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 6px;
}

input[type="color"]::-moz-color-swatch {
    border: none;
    border-radius: 6px;
}

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.18);
}
</style>