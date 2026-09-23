<template>
    <div>
        <Head title="Leagues" />

        <AuthenticatedLayout>
            <template #header>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-700 bg-slate-900"
                            >
                                <i class="fas fa-trophy text-slate-300"></i>
                            </div>

                            <div>
                                <h1
                                    class="text-lg font-bold tracking-tight text-white"
                                >
                                    League Management
                                </h1>

                                <p class="text-xs text-slate-500">
                                    Competition structure and conferences
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div class="min-h-screen bg-[#080b10] text-white">
                <div class="mx-auto max-w-[1600px] p-3 sm:p-5 lg:p-6">

                    <!-- TOP BAR -->
                    <div
                        class="mb-4 overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]"
                    >
                        <div
                            class="h-[2px] bg-gradient-to-r from-slate-700 via-slate-500 to-slate-800"
                        ></div>

                        <div
                            class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <div
                                    class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    ></span>

                                    Competition Office
                                </div>

                                <h2
                                    class="mt-1 text-xl font-bold tracking-tight text-white"
                                >
                                    Leagues
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    Manage your league structure and conference
                                    setup.
                                </p>
                            </div>

                            <div
                                class="flex flex-col gap-2 sm:flex-row sm:items-center"
                            >
                                <!-- League Count -->
                                <div
                                    class="flex items-center gap-3 rounded-lg border border-slate-800 bg-[#090c11] px-4 py-2.5"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-md bg-slate-800"
                                    >
                                        <i
                                            class="fas fa-layer-group text-xs text-slate-400"
                                        ></i>
                                    </div>

                                    <div>
                                        <div
                                            class="text-[10px] font-semibold uppercase tracking-wider text-slate-600"
                                        >
                                            Total Leagues
                                        </div>

                                        <div
                                            class="text-sm font-bold text-white"
                                        >
                                            {{ liga.total_count ?? 0 }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Add -->
                                <button
                                    @click.prevent="openAddModal"
                                    :disabled="isAddModalOpen"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-200 transition hover:border-slate-600 hover:bg-slate-700 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <i class="fas fa-plus text-[10px]"></i>
                                    Add League
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH / FILTER BAR -->
                    <div
                        class="mb-4 rounded-xl border border-slate-800 bg-[#0d1117]"
                    >
                        <div
                            class="flex flex-col gap-3 p-3 sm:p-4 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-slate-800"
                                >
                                    <i
                                        class="fas fa-search text-xs text-slate-500"
                                    ></i>
                                </div>

                                <div>
                                    <div
                                        class="text-xs font-bold text-slate-300"
                                    >
                                        League Directory
                                    </div>

                                    <div
                                        class="text-[10px] text-slate-600"
                                    >
                                        Search registered competitions
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative w-full md:max-w-sm"
                            >
                                <i
                                    class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-600"
                                ></i>

                                <input
                                    v-model="search_liga.search"
                                    @input="fetchLeagues"
                                    type="text"
                                    placeholder="Search league..."
                                    class="w-full rounded-lg border border-slate-800 bg-[#090c11] py-2.5 pl-9 pr-9 text-xs text-slate-200 outline-none transition placeholder:text-slate-700 focus:border-slate-600 focus:ring-1 focus:ring-slate-700"
                                />

                                <button
                                    v-if="search_liga.search"
                                    @click="clearSearch"
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-600 transition hover:text-slate-300"
                                >
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LEAGUES -->
                    <div
                        class="overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]"
                    >
                        <!-- SECTION HEADER -->
                        <div
                            class="flex items-center justify-between border-b border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-5"
                        >
                            <div
                                class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600"
                            >
                                League Directory
                            </div>

                            <div
                                v-if="!loading"
                                class="text-[10px] font-semibold text-slate-600"
                            >
                                {{ leaguesList.length }} displayed
                            </div>
                        </div>

                        <!-- LOADING -->
                        <div
                            v-if="loading"
                            class="flex min-h-[300px] items-center justify-center"
                        >
                            <div class="text-center">
                                <div
                                    class="mx-auto mb-3 h-7 w-7 animate-spin rounded-full border-2 border-slate-800 border-t-slate-400"
                                ></div>

                                <div
                                    class="text-xs font-semibold text-slate-500"
                                >
                                    Loading league data
                                </div>
                            </div>
                        </div>

                        <!-- EMPTY -->
                        <div
                            v-else-if="!leaguesList.length"
                            class="flex min-h-[300px] flex-col items-center justify-center px-6 text-center"
                        >
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg border border-slate-800 bg-[#090c11]"
                            >
                                <i
                                    class="fas fa-trophy text-slate-700"
                                ></i>
                            </div>

                            <div
                                class="text-sm font-bold text-slate-400"
                            >
                                No leagues found
                            </div>

                            <div
                                class="mt-1 max-w-xs text-xs leading-5 text-slate-600"
                            >
                                {{
                                    search_liga.search
                                        ? "No league matches your search."
                                        : "No leagues have been configured yet."
                                }}
                            </div>

                            <button
                                v-if="search_liga.search"
                                @click="clearSearch"
                                class="mt-4 text-[11px] font-bold text-slate-400 transition hover:text-white"
                            >
                                Clear search
                            </button>
                        </div>

                        <!-- LEAGUE LIST -->
                        <div v-else class="divide-y divide-slate-800/80">
                            <div
                                v-for="league in leaguesList"
                                :key="league.id"
                                class="group relative p-4 transition hover:bg-white/[0.015] sm:p-5"
                            >
                                <!-- Subtle left indicator -->
                                <div
                                    class="absolute bottom-0 left-0 top-0 w-[2px] bg-transparent transition group-hover:bg-slate-600"
                                ></div>

                                <div
                                    class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                                >
                                    <!-- LEAGUE INFO -->
                                    <div class="min-w-0">
                                        <div
                                            class="flex items-start gap-4"
                                        >
                                            <!-- Logo -->
                                            <div
                                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-[#090c11]"
                                            >
                                                <i
                                                    class="fas fa-trophy text-sm text-slate-500 transition group-hover:text-slate-300"
                                                ></i>
                                            </div>

                                            <div class="min-w-0">
                                                <div
                                                    class="flex flex-wrap items-center gap-2"
                                                >
                                                    <h3
                                                        class="text-base font-bold text-white"
                                                    >
                                                        {{ league.name }}
                                                    </h3>

                                                    <span
                                                        class="rounded border border-slate-700 bg-slate-800/70 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-500"
                                                    >
                                                        League
                                                    </span>
                                                </div>

                                                <div
                                                    class="mt-1 text-[10px] uppercase tracking-wider text-slate-600"
                                                >
                                                    Competition ID:
                                                    {{ league.id }}
                                                </div>

                                                <!-- Conferences -->
                                                <div
                                                    class="mt-4"
                                                >
                                                    <div
                                                        class="mb-2 flex items-center gap-2"
                                                    >
                                                        <i
                                                            class="fas fa-sitemap text-[9px] text-slate-700"
                                                        ></i>

                                                        <span
                                                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-600"
                                                        >
                                                            Conferences
                                                        </span>

                                                        <span
                                                            v-if="
                                                                league.conferences
                                                            "
                                                            class="text-[9px] text-slate-700"
                                                        >
                                                            {{
                                                                league
                                                                    .conferences
                                                                    ?.length ??
                                                                0
                                                            }}
                                                        </span>
                                                    </div>

                                                    <div
                                                        v-if="
                                                            league.conferences &&
                                                            league
                                                                .conferences
                                                                .length
                                                        "
                                                        class="flex flex-wrap gap-2"
                                                    >
                                                        <span
                                                            v-for="conference in league.conferences"
                                                            :key="
                                                                conference.id
                                                            "
                                                            class="inline-flex items-center gap-2 rounded-md border border-slate-800 bg-[#090c11] px-2.5 py-1.5 text-[11px] font-semibold text-slate-400"
                                                        >
                                                            <span
                                                                class="h-1 w-1 rounded-full bg-slate-600"
                                                            ></span>

                                                            {{
                                                                conference.name
                                                            }}
                                                        </span>
                                                    </div>

                                                    <div
                                                        v-else
                                                        class="text-[10px] italic text-slate-700"
                                                    >
                                                        No conferences
                                                        configured
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ACTIONS -->
                                    <div
                                        class="flex shrink-0 items-center gap-2 lg:pl-5"
                                    >
                                        <button
                                            @click.prevent="
                                                openEditModal(league)
                                            "
                                            :disabled="isEditModalOpen"
                                            class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-800 bg-[#090c11] px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 transition hover:border-slate-700 hover:bg-slate-800 hover:text-slate-200 disabled:opacity-40"
                                        >
                                            <i
                                                class="fas fa-pen text-[9px]"
                                            ></i>

                                            Edit
                                        </button>

                                        <button
                                            @click.prevent="
                                                confirmDelete(league)
                                            "
                                            class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-800 bg-[#090c11] px-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 transition hover:border-red-900/50 hover:bg-red-950/20 hover:text-red-400"
                                        >
                                            <i
                                                class="fas fa-trash text-[9px]"
                                            ></i>

                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PAGINATION -->
                        <div
                            v-if="liga.total_count"
                            class="border-t border-slate-800 bg-[#0a0d12] p-3"
                        >
                            <div class="overflow-x-auto">
                                <Paginator
                                    :page_number="search_liga.page_num"
                                    :total_rows="liga.total_count ?? 0"
                                    :itemsperpage="
                                        search_liga.itemsperpage
                                    "
                                    @page_num="handlePagination"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADD LEAGUE -->
            <Modal
                :show="isAddModalOpen"
                :maxWidth="'2xl'"
                title="Add League"
                @close="closeAddModal"
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

            <!-- EDIT LEAGUE -->
            <Modal
                :show="isEditModalOpen"
                :maxWidth="'2xl'"
                title="Edit League"
                @close="closeEditModal"
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
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import InputError from "@/Components/InputError.vue";
import { ref, computed, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);

const loading = ref(false);
const addingConference = ref(false);

const liga = ref({
    leagues: [],
    total_count: 0,
    total_pages: 0,
});

const conferences = ref([]);

const search_liga = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    total_count: 0,
    search: "",
    itemsperpage: 10,
});

const form = useForm({
    id: 0,
    name: "",
    conference: "",
});

const leaguesList = computed(() => {
    return Array.isArray(liga.value?.leagues)
        ? liga.value.leagues
        : [];
});

const fetchLeagues = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("leagues.list"),
            search_liga.value
        );

        liga.value = response.data ?? {
            leagues: [],
            total_count: 0,
            total_pages: 0,
        };
    } catch (error) {
        console.error("Error fetching leagues:", error);

        liga.value = {
            leagues: [],
            total_count: 0,
            total_pages: 0,
        };
    } finally {
        loading.value = false;
    }
};

const handlePagination = (page_num) => {
    search_liga.value.page_num = page_num;
    fetchLeagues();
};

const clearSearch = () => {
    search_liga.value.search = "";
    search_liga.value.page_num = 1;
    fetchLeagues();
};

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

const openAddModal = () => {
    form.reset();
    form.clearErrors();

    conferences.value = [];

    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    if (form.processing) {
        return;
    }

    form.reset();
    form.clearErrors();

    isAddModalOpen.value = false;
};

const openEditModal = async (league) => {
    fillForm(league);

    isEditModalOpen.value = true;

    await fetchConference(league.id);
};

const closeEditModal = () => {
    if (form.processing || addingConference.value) {
        return;
    }

    form.reset();
    form.clearErrors();

    conferences.value = [];

    isEditModalOpen.value = false;
};

const fillForm = (data) => {
    form.id = data?.id ?? 0;
    form.name = data?.name ?? "";
    form.conference = "";

    form.clearErrors();
};

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

            await fetchLeagues();
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
            await fetchLeagues();
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

            await fetchLeagues();
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

const confirmDelete = (league) => {
    fillForm(league);

    Swal.fire({
        title: "Remove League?",
        text: `Remove "${league.name}" from the league system?`,
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
            const response = await axios.post(
                route("leagues.delete"),
                {
                    id: form.id,
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

                await fetchLeagues();
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
        }
    });
};

onMounted(fetchLeagues);
</script>