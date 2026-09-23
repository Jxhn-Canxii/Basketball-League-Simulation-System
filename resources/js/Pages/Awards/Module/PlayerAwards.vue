<template>
    <section
        class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
    >
        <!-- Top Accent -->
        <div
            class="h-1 bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
        ></div>

        <!-- Header -->
        <div
            class="border-b border-slate-800 bg-[#0d1117] px-5 py-5 sm:px-6"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-[#080b10]"
                    >
                        <i
                            class="fas fa-award text-slate-300 text-lg"
                        ></i>
                    </div>

                    <div class="min-w-0">
                        <h3
                            class="text-lg font-bold tracking-tight text-white sm:text-xl"
                        >
                            Player Awards
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                            Historical player awards and league honors
                        </p>
                    </div>
                </div>

                <!-- Result Count -->
                <div
                    class="flex items-center gap-2 self-start rounded-lg border border-slate-800 bg-[#080b10] px-3 py-2 lg:self-auto"
                >
                    <i
                        class="fas fa-list-ol text-[10px] text-slate-600"
                    ></i>

                    <span
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-500"
                    >
                        {{ awards.length }} Records
                    </span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="border-b border-slate-800 bg-[#0a0d12] px-5 py-4 sm:px-6"
        >
            <div
                class="mb-3 flex items-center gap-2"
            >
                <i
                    class="fas fa-filter text-[10px] text-slate-600"
                ></i>

                <span
                    class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500"
                >
                    Archive Filters
                </span>
            </div>

            <div
                class="grid grid-cols-1 gap-3 md:grid-cols-2"
            >
                <!-- Season -->
                <div>
                    <label
                        for="award-season"
                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-600"
                    >
                        Season
                    </label>

                    <div class="relative">
                        <select
                            id="award-season"
                            v-model="search_filters.season_id"
                            @change="fetchFilteredPlayers"
                            class="w-full appearance-none rounded-xl border border-slate-700 bg-[#080b10] px-4 py-2.5 pr-10 text-sm font-medium text-slate-300 outline-none transition focus:border-slate-500 focus:ring-1 focus:ring-slate-600"
                        >
                            <option value="0">
                                All Seasons
                            </option>

                            <option
                                v-for="season in seasons"
                                :key="season.season_id"
                                :value="season.season_id"
                            >
                                {{ season.name }}
                            </option>
                        </select>

                        <i
                            class="fas fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-slate-600"
                        ></i>
                    </div>
                </div>

                <!-- Award -->
                <div>
                    <label
                        for="award-name"
                        class="mb-1.5 block text-[10px] font-bold uppercase tracking-widest text-slate-600"
                    >
                        Award
                    </label>

                    <div class="relative">
                        <select
                            id="award-name"
                            v-model="search_filters.awards_name"
                            @change="fetchFilteredPlayers"
                            class="w-full appearance-none rounded-xl border border-slate-700 bg-[#080b10] px-4 py-2.5 pr-10 text-sm font-medium text-slate-300 outline-none transition focus:border-slate-500 focus:ring-1 focus:ring-slate-600"
                        >
                            <option value="0">
                                All Awards
                            </option>

                            <option
                                v-for="award in awardOptions"
                                :key="award.award_name"
                                :value="award.award_name"
                            >
                                {{ award.award_name }}
                            </option>
                        </select>

                        <i
                            class="fas fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-[10px] text-slate-600"
                        ></i>
                    </div>
                </div>
            </div>

            <!-- Active Filter Summary -->
            <div
                class="mt-3 flex flex-wrap items-center gap-2"
            >
                <span
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-700"
                >
                    Viewing
                </span>

                <span
                    class="rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[9px] font-semibold text-slate-500"
                >
                    {{ selectedSeasonName }}
                </span>

                <span
                    class="rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[9px] font-semibold text-slate-500"
                >
                    {{ selectedAwardName }}
                </span>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="flex min-h-[360px] items-center justify-center bg-[#0a0d12]"
        >
            <div class="flex flex-col items-center gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-700 bg-[#080b10]"
                >
                    <i
                        class="fas fa-spinner fa-spin text-slate-400"
                    ></i>
                </div>

                <div class="text-center">
                    <p class="text-sm font-semibold text-slate-300">
                        Loading awards
                    </p>

                    <p class="mt-1 text-xs text-slate-600">
                        Updating award archive...
                    </p>
                </div>
            </div>
        </div>

        <!-- No Results -->
        <div
            v-else-if="awards.length === 0"
            class="flex min-h-[320px] items-center justify-center bg-[#0a0d12] px-6"
        >
            <div class="text-center">
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-slate-800 bg-[#080b10]"
                >
                    <i
                        class="fas fa-award text-slate-700 text-lg"
                    ></i>
                </div>

                <h4
                    class="mt-4 text-sm font-semibold text-slate-300"
                >
                    No award records found
                </h4>

                <p
                    class="mx-auto mt-1 max-w-sm text-xs text-slate-600"
                >
                    Try selecting another season or award category.
                </p>
            </div>
        </div>

        <!-- Awards Table -->
        <div
            v-else
            class="bg-[#0a0d12]"
        >
            <!-- Horizontal Scroll -->
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[850px] border-collapse"
                >
                    <thead>
                        <tr
                            class="border-b border-slate-800 bg-[#080b10]"
                        >
                            <th
                                class="px-5 py-3 text-left text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                            >
                                Season
                            </th>

                            <th
                                class="px-5 py-3 text-left text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                            >
                                Award
                            </th>

                            <th
                                class="px-5 py-3 text-left text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                            >
                                Player
                            </th>

                            <th
                                class="px-5 py-3 text-left text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                            >
                                Draft
                            </th>

                            <th
                                class="px-5 py-3 text-left text-[9px] font-bold uppercase tracking-[0.16em] text-slate-600"
                            >
                                Team
                            </th>

                            <th
                                class="w-10 px-4 py-3"
                            ></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="award in awards"
                            :key="award.id"
                            @click="openPlayerProfile(award.player_id)"
                            class="group cursor-pointer border-b border-slate-800/70 transition-colors duration-150 hover:bg-[#10151d]"
                        >
                            <!-- Season -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-800 bg-[#080b10]"
                                    >
                                        <i
                                            class="fas fa-calendar text-[9px] text-slate-600"
                                        ></i>
                                    </div>

                                    <span
                                        class="whitespace-nowrap text-xs font-semibold text-slate-400"
                                    >
                                        {{ award.season_name || "-" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Award -->
                            <td class="px-5 py-4">
                                <div
                                    class="flex items-center gap-2.5"
                                >
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10]"
                                    >
                                        <i
                                            class="fas fa-trophy text-[10px] text-slate-500"
                                        ></i>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="max-w-[220px] truncate text-xs font-bold text-slate-200"
                                            :title="award.award_name"
                                        >
                                            {{ award.award_name || "-" }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-[9px] uppercase tracking-wide text-slate-700"
                                        >
                                            League Honor
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Player -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-slate-900"
                                    >
                                        <i
                                            class="fas fa-user text-[11px] text-slate-500"
                                        ></i>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="max-w-[200px] truncate text-sm font-bold text-white"
                                            :title="award.player_name"
                                        >
                                            {{
                                                award.player_name ||
                                                "Unknown Player"
                                            }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-[9px] uppercase tracking-wide text-slate-600"
                                        >
                                            View Profile
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Draft -->
                            <td class="px-5 py-4">
                                <div
                                    class="flex flex-col gap-1"
                                >
                                    <span
                                        class="w-fit rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-slate-500"
                                    >
                                        {{ award.draft_status || "-" }}
                                    </span>

                                    <span
                                        v-if="award.drafted_team"
                                        class="max-w-[180px] truncate text-[9px] text-slate-600"
                                        :title="award.drafted_team"
                                    >
                                        {{ award.drafted_team }}
                                    </span>
                                </div>
                            </td>

                            <!-- Team -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <i
                                        class="fas fa-shield-halved text-[10px] text-slate-700"
                                    ></i>

                                    <span
                                        class="max-w-[180px] truncate text-xs font-medium text-slate-400"
                                        :title="award.team_name"
                                    >
                                        {{ award.team_name || "Free Agent" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-4 text-right">
                                <div
                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-800 bg-[#080b10] transition group-hover:border-slate-700"
                                >
                                    <i
                                        class="fas fa-chevron-right text-[9px] text-slate-700 transition-transform group-hover:translate-x-0.5 group-hover:text-slate-400"
                                    ></i>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div
                class="flex flex-col gap-2 border-t border-slate-800 px-5 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2">
                    <i
                        class="fas fa-circle-info text-[9px] text-slate-700"
                    ></i>

                    <span class="text-[9px] text-slate-600">
                        Click any award record to view the player's profile.
                    </span>
                </div>

                <span
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-700"
                >
                    Award Archive
                </span>
            </div>
        </div>
    </section>

    <!-- Player Profile -->
    <Modal
        :show="!!showPlayerProfileModal"
        :maxWidth="'6xl'"
        title="Player Profile"
        @close="showPlayerProfileModal = false"
    >
        <div class="bg-[#080b10] p-4 sm:p-6">
            <PlayerPerformance
                v-if="showPlayerProfileModal"
                :key="showPlayerProfileModal"
                :player_id="showPlayerProfileModal"
            />
        </div>
    </Modal>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { useForm } from "@inertiajs/vue3";

import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const showPlayerProfileModal = ref(false);

const awards = ref([]);
const awardsName = ref({
    awardNames: [],
});
const seasons = ref([]);
const loading = ref(false);

const search_filters = useForm({
    page_num: 1,
    itemsperpage: 10,
    search: "",
    season_id: 0,
    awards_name: 0,
});

const awardOptions = computed(() => {
    return Array.isArray(awardsName.value?.awardNames)
        ? awardsName.value.awardNames
        : [];
});

const selectedSeasonName = computed(() => {
    if (
        String(search_filters.season_id) === "0"
    ) {
        return "All Seasons";
    }

    const season = seasons.value.find(
        (item) =>
            String(item.season_id) ===
            String(search_filters.season_id)
    );

    return season?.name || "Selected Season";
});

const selectedAwardName = computed(() => {
    if (
        String(search_filters.awards_name) === "0"
    ) {
        return "All Awards";
    }

    return search_filters.awards_name;
});

const fetchFilteredPlayers = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("player.awards.filter"),
            {
                ...search_filters.data(),
            }
        );

        awards.value = Array.isArray(response.data?.awards)
            ? response.data.awards
            : [];
    } catch (error) {
        console.error(
            "Error fetching filtered players:",
            error
        );

        awards.value = [];
    } finally {
        loading.value = false;
    }
};

const seasonsDropdown = async () => {
    try {
        const storedSeasons =
            localStorage.getItem("seasons");

        const parsedSeasons = storedSeasons
            ? JSON.parse(storedSeasons)
            : [];

        seasons.value = Array.isArray(parsedSeasons)
            ? parsedSeasons
            : [];
    } catch (error) {
        console.error(
            "Error fetching seasons dropdown:",
            error
        );

        seasons.value = [];
    }
};

const awardsDropdown = async () => {
    try {
        const response = await axios.get(
            route("player.awards.dropdown")
        );

        awardsName.value = response.data || {
            awardNames: [],
        };
    } catch (error) {
        console.error(
            "Error fetching awards dropdown:",
            error
        );

        awardsName.value = {
            awardNames: [],
        };
    }
};

const openPlayerProfile = (playerId) => {
    if (!playerId) {
        return;
    }

    showPlayerProfileModal.value = playerId;
};

onMounted(async () => {
    await Promise.all([
        seasonsDropdown(),
        awardsDropdown(),
    ]);

    await fetchFilteredPlayers();
});
</script>