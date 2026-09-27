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
                                <div class="shrink-0">
                                    <Add @transaction_id="handleTransaction()" />
                                </div>
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
                                        <Edit
                                            :key="`edit-${league.id}`"
                                            :data="league"
                                            @transaction_id="handleTransaction()"
                                        />

                                        <Delete
                                            :key="`delete-${league.id}`"
                                            :league_id="league.id"
                                            @transaction_id="handleTransaction()"
                                        />
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

import Add from "./Module/Add.vue";
import Edit from "./Module/Edit.vue";
import Delete from "./Module/Delete.vue";

const loading = ref(false);
const addingConference = ref(false);

const liga = ref({
    leagues: [],
    total_count: 0,
    total_pages: 0,
});

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

const handleTransaction = () => {
    fetchLeagues();
};

onMounted(fetchLeagues);
</script>