<template>
    <div>
        <!-- Edit Button -->
        <button
            type="button"
            @click.prevent="fillForm"
            :disabled="isEditModalOpen"
            class="group inline-flex items-center gap-2 rounded-xl border border-yellow-500/20 bg-yellow-500/[0.08] px-3 py-2 text-xs font-black text-yellow-500 transition-all duration-200 hover:border-yellow-500/40 hover:bg-yellow-500/[0.13] hover:text-yellow-400 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-lg bg-yellow-500/10 transition group-hover:bg-yellow-500/20"
            >
                <i class="fas fa-edit text-[10px]"></i>
            </span>

            <span>Edit</span>
        </button>

        <!-- Modal -->
        <Modal
            :show="isEditModalOpen"
            :maxWidth="'2xl'"
            title="Edit League"
            @close="isEditModalOpen = false"
        >
            <div class="bg-[#080b10] p-5 sm:p-6">
                    <form @submit.prevent="Update">

                        <!-- League -->
                        <div class="mb-5">
                            <label
                                for="EditLeagueName"
                                class="mb-2 block text-[10px] font-bold uppercase tracking-wider text-slate-500"
                            >
                                League Name
                            </label>

                            <input
                                id="EditLeagueName"
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

                        <!-- Conference -->
                        <div class="mb-5">
                            <div
                                class="mb-2 flex items-center justify-between"
                            >
                                <label
                                    for="ConferenceName"
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Add Conference
                                </label>

                                <span
                                    class="text-[9px] uppercase tracking-wider text-slate-700"
                                >
                                    League Structure
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <input
                                    id="ConferenceName"
                                    v-model="form.conference"
                                    type="text"
                                    placeholder="Conference name"
                                    @keyup.enter.prevent="
                                        addConference(
                                            form.id,
                                            form.conference
                                        )
                                    "
                                    class="min-w-0 flex-1 rounded-lg border border-slate-800 bg-[#0d1117] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-700 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                                />

                                <button
                                    type="button"
                                    @click.prevent="
                                        addConference(
                                            form.id,
                                            form.conference
                                        )
                                    "
                                    :disabled="
                                        !form.conference.trim() ||
                                        addingConference
                                    "
                                    class="rounded-lg border border-slate-700 bg-slate-800 px-4 text-xs font-bold text-slate-300 transition hover:bg-slate-700 hover:text-white disabled:opacity-40"
                                >
                                    <i
                                        :class="
                                            addingConference
                                                ? 'fas fa-spinner fa-spin'
                                                : 'fas fa-plus'
                                        "
                                    ></i>
                                </button>
                            </div>

                            <InputError
                                class="mt-2"
                                :message="form.errors.conference"
                            />
                        </div>

                        <!-- Conferences -->
                        <div class="mb-6">
                            <div
                                class="mb-2 flex items-center justify-between"
                            >
                                <label
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Current Conferences
                                </label>

                                <span
                                    class="rounded bg-slate-800 px-2 py-1 text-[9px] font-bold text-slate-500"
                                >
                                    {{ conferences.length }}
                                </span>
                            </div>

                            <div
                                v-if="conferences.length"
                                class="rounded-lg border border-slate-800 bg-[#0d1117] p-3"
                            >
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div
                                        v-for="conference in conferences"
                                        :key="conference.id"
                                        class="flex items-center gap-3 rounded-md border border-slate-800 bg-[#090c11] px-3 py-2.5"
                                    >
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded bg-slate-800"
                                        >
                                            <i
                                                class="fas fa-sitemap text-[9px] text-slate-500"
                                            ></i>
                                        </div>

                                        <span
                                            class="text-xs font-semibold text-slate-300"
                                        >
                                            {{ conference.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-lg border border-dashed border-slate-800 bg-[#0d1117] p-5 text-center"
                            >
                                <i
                                    class="fas fa-sitemap mb-2 text-slate-700"
                                ></i>

                                <p
                                    class="text-[10px] uppercase tracking-wider text-slate-700"
                                >
                                    No conferences configured
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex justify-end gap-2 border-t border-slate-800 pt-4"
                        >
                            <button
                                type="button"
                                @click="closeEditModal"
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
                                            : 'fas fa-save mr-2'
                                    "
                                ></i>

                                {{
                                    form.processing
                                        ? "Saving..."
                                        : "Save Changes"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
        </Modal>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import Swal from "sweetalert2";
import axios from "axios";

const emits = defineEmits(["transaction_id"]);

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});

const isEditModalOpen = ref(false);

const leagues = ref([]);
const conferences = ref([]);
const conferenceLoading = ref(false);
const addingConference = ref(false); 

const form = useForm({
    id: 0,
    name: "",
    conference: "",
});

const fetchConference = async (league_id) => {
    try {
        const response = await axios.post(
            route("conference.season.dropdown"),
            {
                league_id,
            }
        );

        conferences.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error("Error fetching conferences:", error);
        conferences.value = [];
    }
};

/*
|--------------------------------------------------------------------------
| Fill Form
|--------------------------------------------------------------------------
*/

const fillForm = (data) => {
    isEditModalOpen.value = true;

    let leagueData = props.data;

    form.id = leagueData?.id ?? 0;
    form.name = leagueData?.name ?? "";
    form.conference = "";

    form.clearErrors();

    fetchConference(leagueData.id);
};

const addConference = async (league_id, conference_name) => {
    const name = conference_name?.trim();

    if (!league_id || !name || addingConference.value) {
        return;
    }

    addingConference.value = true;

    try {
        const response = await axios.post(
            route("conferences.add"),
            {
                name,
                league_id,
            }
        );

        if (response) {
            await Swal.fire({
                title: "Conference Added",
                text: "The conference has been added successfully.",
                icon: "success",
                background: "#0d1117",
                color: "#e2e8f0",
                confirmButtonColor: "#475569",
            });

            form.conference = "";

            await fetchConference(league_id);
        }
    } catch (error) {
        console.error("Error adding conference:", error);

        Swal.fire({
            title: "Unable to Add Conference",
            text:
                error?.response?.data?.message ??
                "Something went wrong while adding the conference.",
            icon: "error",
            background: "#0d1117",
            color: "#e2e8f0",
            confirmButtonColor: "#475569",
        });
    } finally {
        addingConference.value = false;
    }
};

const Update = async () => {
    if (!form.name.trim() || form.processing) {
        return;
    }

    form.processing = true;

    try {
        const response = await axios.post(
            route("leagues.update"),
            {
                id: form.id,
                name: form.name.trim(),
            }
        );

        if (response) {
            await Swal.fire({
                title: "League Updated",
                text: "League information has been updated successfully.",
                icon: "success",
                background: "#0d1117",
                color: "#e2e8f0",
                confirmButtonColor: "#475569",
            });

            form.reset();
            form.clearErrors();

            conferences.value = [];
            isEditModalOpen.value = false;

            emits('transaction_id',Math.random());
        }
    } catch (error) {
        console.error("Error updating league:", error);

        Swal.fire({
            title: "Unable to Update League",
            text:
                error?.response?.data?.message ??
                "Something went wrong while updating the league.",
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

/* Native color picker */
.color-picker {
    appearance: none;
    -webkit-appearance: none;

    width: 48px;
    height: 40px;

    flex-shrink: 0;

    cursor: pointer;

    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.5rem;

    background: transparent;
    padding: 4px;
}

.color-picker:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.color-picker::-webkit-color-swatch-wrapper {
    padding: 0;
}

.color-picker::-webkit-color-swatch {
    border: none;
    border-radius: 5px;
}

.color-picker::-moz-color-swatch {
    border: none;
    border-radius: 5px;
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