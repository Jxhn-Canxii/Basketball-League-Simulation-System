<template>
    <!-- New Season Button -->
    <button
        type="button"
        @click.prevent="newSeasonBehavior"
        :disabled="isAddModalOpen"
        :class="[
            'group inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-black uppercase tracking-wider transition-all duration-200',
            isAddModalOpen
                ? 'cursor-not-allowed border-white/[0.05] bg-white/[0.03] text-gray-700 opacity-50'
                : 'border-blue-500/20 bg-blue-500/[0.08] text-blue-400 hover:border-blue-500/40 hover:bg-blue-500/[0.14] hover:text-blue-300',
        ]"
    >
        <span
            class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-500/[0.12] transition group-hover:bg-blue-500/[0.2]"
        >
            <i class="fa fa-calendar-plus text-[11px]"></i>
        </span>

        <span class="hidden sm:inline">
            New Season
        </span>

        <span class="sm:hidden">
            New
        </span>
    </button>

    <!-- New Season Modal -->
    <Modal
        :show="isAddModalOpen"
        :maxWidth="'2xl'"
        title="New Season"
        @close="!isProcessing && (isAddModalOpen = false)"
    >
        <div class="relative overflow-hidden bg-[#080808] text-white">
            <!-- Processing Overlay -->
            <Transition name="fade">
                <div
                    v-if="isProcessing"
                    class="absolute inset-0 z-50 flex items-center justify-center bg-black/80 px-6 backdrop-blur-sm"
                >
                    <div
                        class="w-full max-w-xs rounded-2xl border border-white/[0.08] bg-[#101010] p-6 text-center shadow-2xl"
                    >
                        <div
                            class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/[0.08]"
                        >
                            <i
                                class="fas fa-circle-notch animate-spin text-lg text-blue-400"
                            ></i>
                        </div>

                        <h3
                            class="text-sm font-black uppercase tracking-wider text-white"
                        >
                            Preparing Schedule
                        </h3>

                        <p class="mt-2 text-xs leading-relaxed text-gray-500">
                            Please wait while the new season schedule is being
                            generated.
                        </p>
                    </div>
                </div>
            </Transition>

            <!-- Modal Header -->
            <div
                class="border-b border-white/[0.07] bg-gradient-to-r from-[#111111] via-[#0c0c0c] to-black px-5 py-4"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/[0.08]"
                    >
                        <i
                            class="fas fa-calendar-plus text-sm text-blue-400"
                        ></i>
                    </div>

                    <div>
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-blue-400/70"
                        >
                            League Management
                        </div>

                        <h2 class="text-lg font-black tracking-tight text-white">
                            Create New Season
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form
                class="p-5 sm:p-6"
                @submit.prevent="createNewSeason"
            >
                <div class="space-y-5">
                    <!-- Season Name -->
                    <div>
                        <label
                            for="season_name"
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            <i class="fas fa-tag text-[9px] text-blue-400"></i>
                            Season Name
                        </label>

                        <input
                            id="season_name"
                            v-model="form.season_name"
                            type="text"
                            minlength="1"
                            placeholder="Input Season Name"
                            name="season_name"
                            :disabled="isProcessing"
                            class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03] px-3.5 py-3 text-sm font-medium text-white outline-none transition placeholder:text-gray-700 focus:border-blue-500/40 focus:bg-white/[0.045] focus:ring-1 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                        />

                        <InputError
                            class="mt-2"
                            :message="form.errors.season_name"
                        />
                    </div>

                    <!-- Type -->
                    <div>
                        <label
                            for="season_type"
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            <i
                                class="fas fa-layer-group text-[9px] text-blue-400"
                            ></i>
                            Season Type
                        </label>

                        <select
                            id="season_type"
                            v-model="form.type"
                            name="type"
                            :disabled="isProcessing"
                            class="w-full appearance-none rounded-xl border border-white/[0.08] bg-[#111111] px-3.5 py-3 text-sm font-medium text-white outline-none transition focus:border-blue-500/40 focus:ring-1 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="0">
                                Select Type
                            </option>

                            <option value="1" disabled>
                                Single Elimination (Coming Soon)
                            </option>

                            <option value="2">
                                Single Round Robin
                            </option>

                            <option value="3">
                                Double Round Robin
                            </option>

                            <option value="5">
                                Super 5 (Test only)
                            </option>

                            <option value="6">
                                Power 10 (Test only)
                            </option>

                            <option value="4" disabled>
                                Round Robin + Inter Conference
                            </option>
                        </select>

                        <InputError
                            class="mt-2"
                            :message="form.errors.type"
                        />
                    </div>

                    <!-- Start Playoffs -->
                    <div>
                        <label
                            for="start_playoffs"
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            <i
                                class="fas fa-flag-checkered text-[9px] text-gray-600"
                            ></i>
                            Start Playoffs
                            <span
                                class="rounded-md border border-white/[0.06] bg-white/[0.03] px-1.5 py-0.5 text-[7px] text-gray-600"
                            >
                                LOCKED
                            </span>
                        </label>

                        <select
                            id="start_playoffs"
                            v-model="form.start"
                            name="start"
                            disabled
                            class="w-full appearance-none rounded-xl border border-white/[0.06] bg-white/[0.02] px-3.5 py-3 text-sm font-medium text-gray-600 outline-none"
                        >
                            <option value="0">
                                Select Start
                            </option>

                            <option value="8" disabled>
                                on Quarter Finals
                            </option>

                            <option value="16">
                                on Round of 16
                            </option>
                        </select>

                        <InputError
                            class="mt-2"
                            :message="form.errors.start"
                        />
                    </div>

                    <!-- Playoff Type -->
                    <div>
                        <label
                            for="playoff_type"
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            <i
                                class="fas fa-trophy text-[9px] text-yellow-500"
                            ></i>
                            Playoff Type
                        </label>

                        <select
                            id="playoff_type"
                            v-model="form.playoff_type"
                            name="playoff_type"
                            :disabled="isProcessing"
                            class="w-full appearance-none rounded-xl border border-white/[0.08] bg-[#111111] px-3.5 py-3 text-sm font-medium text-white outline-none transition focus:border-yellow-500/40 focus:ring-1 focus:ring-yellow-500/20 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="0">
                                Select Playoff Type
                            </option>

                            <option value="1">
                                Single Elimination
                            </option>

                            <option value="2">
                                Series
                            </option>
                        </select>

                        <InputError
                            class="mt-2"
                            :message="form.errors.playoff_type"
                        />
                    </div>

                    <!-- Match Type -->
                    <div>
                        <label
                            for="match_type"
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            <i
                                class="fas fa-users text-[9px] text-gray-600"
                            ></i>
                            Match Type
                            <span
                                class="rounded-md border border-white/[0.06] bg-white/[0.03] px-1.5 py-0.5 text-[7px] text-gray-600"
                            >
                                LOCKED
                            </span>
                        </label>

                        <select
                            id="match_type"
                            v-model="form.match_type"
                            name="match_type"
                            disabled
                            class="w-full appearance-none rounded-xl border border-white/[0.06] bg-white/[0.02] px-3.5 py-3 text-sm font-medium text-gray-600 outline-none"
                        >
                            <option value="0">
                                Select Match Type
                            </option>

                            <option value="1">
                                by Conference
                            </option>

                            <option value="2" disabled>
                                All Teams
                            </option>
                        </select>

                        <InputError
                            class="mt-2"
                            :message="form.errors.match_type"
                        />
                    </div>

                    <!-- League -->
                    <div>
                        <label
                            for="league_id"
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            <i
                                class="fas fa-building text-[9px] text-gray-600"
                            ></i>
                            League
                            <span
                                class="rounded-md border border-white/[0.06] bg-white/[0.03] px-1.5 py-0.5 text-[7px] text-gray-600"
                            >
                                LOCKED
                            </span>
                        </label>

                        <select
                            id="league_id"
                            v-model="form.league_id"
                            name="league_id"
                            disabled
                            class="w-full appearance-none rounded-xl border border-white/[0.06] bg-white/[0.02] px-3.5 py-3 text-sm font-medium text-gray-600 outline-none"
                        >
                            <option value="0">
                                Select League
                            </option>

                            <option
                                v-for="league in leagues_dropdown"
                                :key="league.id"
                                :value="league.id"
                            >
                                {{ league.name }}
                            </option>
                        </select>

                        <InputError
                            class="mt-2"
                            :message="form.errors.league_id"
                        />
                    </div>
                </div>

                <!-- Form Footer -->
                <div
                    class="mt-6 flex flex-col-reverse gap-2 border-t border-white/[0.07] pt-5 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        :disabled="isProcessing"
                        @click="isAddModalOpen = false"
                        class="rounded-xl border border-white/[0.07] bg-white/[0.025] px-4 py-2.5 text-xs font-black uppercase tracking-wider text-gray-500 transition hover:border-white/[0.12] hover:bg-white/[0.05] hover:text-gray-300 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="isProcessing"
                        :class="[
                            'inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-black uppercase tracking-wider transition-all',
                            isProcessing
                                ? 'cursor-not-allowed bg-blue-500/20 text-blue-400/50'
                                : 'bg-blue-500 text-white shadow-lg shadow-blue-500/10 hover:bg-blue-400',
                        ]"
                    >
                        <i
                            v-if="isProcessing"
                            class="fas fa-circle-notch animate-spin text-[10px]"
                        ></i>

                        <i
                            v-else
                            class="fas fa-calendar-check text-[10px]"
                        ></i>

                        {{ isProcessing ? "Creating..." : "Create Season" }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";

import { ref } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

const emit = defineEmits(["transaction_id"]);

const isAddModalOpen = ref(false);
const leagues_dropdown = ref([]);
const isProcessing = ref(false);

const form = useForm({
    season_name: "",
    type: 3,
    start: 16,
    league_id: 1,
    seasons_id: 0,
    conference_id: 0,
    match_type: 1,
    playoff_type: 2,
});

/*
|--------------------------------------------------------------------------
| Open New Season
|--------------------------------------------------------------------------
*/
const newSeasonBehavior = () => {
    leagueDropdown();
    isAddModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| League Dropdown
|--------------------------------------------------------------------------
*/
const leagueDropdown = async () => {
    try {
        const response = await axios.get(
            route("leagues.dropdown")
        );

        if (!response) {
            throw new Error("Failed to fetch leagues");
        }

        leagues_dropdown.value = response.data ?? [];
    } catch (error) {
        console.error(
            "Error fetching leagues:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Unable to Load Leagues",
            text: "Failed to fetch the available leagues.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#3b82f6",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Create New Season
|--------------------------------------------------------------------------
*/
const createNewSeason = async () => {
    if (form.league_id == 0) {
        Swal.fire({
            title: "League Required",
            text: "Please assign a league before creating the season.",
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#3b82f6",
        });

        return false;
    }

    if (!form.season_name?.trim()) {
        Swal.fire({
            title: "Season Name Required",
            text: "Please enter a name for the new season.",
            icon: "warning",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#3b82f6",
        });

        return false;
    }

    try {
        isProcessing.value = true;

        const response = await axios.post(
            route("create.schedule.regular"),
            form
        );

        isAddModalOpen.value = false;

        Swal.fire({
            icon: "success",
            title: "Season Created",
            text:
                response.data?.message ??
                "The new season has been created successfully.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#3b82f6",
        });

        /*
         * Reset only the fields that should be cleared.
         * Keep your default season configuration intact.
         */
        form.season_name = "";

        form.clearErrors();

        emit(
            "transaction_id",
            Math.random()
        );
    } catch (error) {
        console.error(
            "Error creating schedule:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Creation Failed",
            text:
                error.response?.data?.message ??
                "Failed to create the new season. Please try again.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#3b82f6",
        });
    } finally {
        isProcessing.value = false;
    }
};
</script>

<style scoped>
/* Modal transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Dark select dropdown */
select option {
    background: #111111;
    color: #ffffff;
}

/* Dark scrollbar */
::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: #292929;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #444444;
}

/* Text selection */
::selection {
    background: rgba(59, 130, 246, 0.25);
    color: white;
}
</style>