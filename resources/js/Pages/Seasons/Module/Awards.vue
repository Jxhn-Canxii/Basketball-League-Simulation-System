<template>
    <section
        class="team-roster relative min-h-screen w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-2xl"
    >
        <!-- Header -->
        <div
            class="relative border-b border-white/[0.07] bg-gradient-to-r from-[#111111] via-[#0b0b0b] to-black px-4 py-4 sm:px-5"
        >
            <!-- Yellow accent -->
            <div
                class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-yellow-400 via-yellow-500 to-transparent"
            ></div>

            <div class="flex min-w-0 items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-yellow-500/20 bg-yellow-500/[0.08]"
                    >
                        <i class="fas fa-trophy text-sm text-yellow-500"></i>
                    </div>

                    <div class="min-w-0">
                        <p
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-yellow-500/70"
                        >
                            League Honors
                        </p>

                        <h2
                            class="truncate text-lg font-black tracking-tight text-white sm:text-xl"
                        >
                            Season Awards
                        </h2>
                    </div>
                </div>

                <!-- Award count -->
                <div
                    v-if="!isLoading"
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-white/[0.07] bg-white/[0.03] px-3 py-2"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-yellow-500 shadow-[0_0_8px_rgba(234,179,8,0.6)]"
                    ></span>

                    <span
                        class="text-[9px] font-black uppercase tracking-wider text-gray-400"
                    >
                        {{ awards.length }}
                        <span class="hidden sm:inline">Awards</span>
                    </span>
                </div>

                <!-- Loading indicator -->
                <div
                    v-else
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-white/[0.07] bg-white/[0.03] px-3 py-2"
                >
                    <i
                        class="fas fa-circle-notch animate-spin text-[10px] text-yellow-500"
                    ></i>

                    <span
                        class="hidden text-[9px] font-black uppercase tracking-wider text-gray-500 sm:inline"
                    >
                        Loading
                    </span>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="p-4 sm:p-5">
            <div
                class="overflow-hidden rounded-xl border border-white/[0.06] bg-[#090909]"
            >
                <!-- Skeleton Header -->
                <div
                    class="hidden gap-4 border-b border-white/[0.06] bg-white/[0.025] px-4 py-3 md:grid md:grid-cols-[1.2fr_1fr_1fr_2fr]"
                >
                    <div class="h-2.5 w-24 animate-pulse rounded bg-white/[0.07]"></div>
                    <div class="h-2.5 w-24 animate-pulse rounded bg-white/[0.07]"></div>
                    <div class="h-2.5 w-24 animate-pulse rounded bg-white/[0.07]"></div>
                    <div class="h-2.5 w-32 animate-pulse rounded bg-white/[0.07]"></div>
                </div>

                <!-- Skeleton Rows -->
                <div class="divide-y divide-white/[0.05]">
                    <div
                        v-for="n in 5"
                        :key="n"
                        class="grid gap-4 px-4 py-4 md:grid-cols-[1.2fr_1fr_1fr_2fr]"
                    >
                        <div class="space-y-2">
                            <div
                                class="h-3.5 w-32 animate-pulse rounded bg-white/[0.07]"
                            ></div>

                            <div
                                class="h-2 w-20 animate-pulse rounded bg-white/[0.04] md:hidden"
                            ></div>
                        </div>

                        <div
                            class="h-3 w-28 animate-pulse rounded bg-white/[0.05]"
                        ></div>

                        <div
                            class="h-3 w-24 animate-pulse rounded bg-white/[0.05]"
                        ></div>

                        <div
                            class="h-3 w-full max-w-sm animate-pulse rounded bg-white/[0.04]"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="!awards.length"
            class="flex min-h-[280px] flex-col items-center justify-center px-6 py-12 text-center"
        >
            <div
                class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.03]"
            >
                <i class="fas fa-trophy text-2xl text-gray-700"></i>
            </div>

            <div
                class="mb-2 text-[9px] font-black uppercase tracking-[0.25em] text-gray-600"
            >
                Season Awards
            </div>

            <h3 class="text-base font-black text-gray-400">
                No Awards Available
            </h3>

            <p
                class="mt-2 max-w-md text-xs leading-relaxed text-gray-600"
            >
                No season awards have been recorded yet.
            </p>
        </div>

        <!-- Awards -->
        <div v-else class="p-3 sm:p-4">
            <!--
                Internal horizontal scrolling keeps the table readable
                when the parent/container becomes narrow.
            -->
            <div
                class="w-full overflow-x-auto rounded-xl border border-white/[0.06]"
            >
                <table class="min-w-[1000px] w-full border-collapse">
                    <!-- Table Header -->
                    <thead>
                        <tr
                            class="border-b border-white/[0.07] bg-[#0d0d0d]"
                        >
                            <th
                                class="px-4 py-3 text-left text-[8px] font-black uppercase tracking-[0.18em] text-gray-500"
                            >
                                Award
                            </th>

                            <th
                                class="px-4 py-3 text-left text-[8px] font-black uppercase tracking-[0.18em] text-gray-500"
                            >
                                Player
                            </th>

                            <th
                                class="px-4 py-3 text-left text-[8px] font-black uppercase tracking-[0.18em] text-gray-500"
                            >
                                Team
                            </th>

                            <th
                                class="px-4 py-3 text-left text-[8px] font-black uppercase tracking-[0.18em] text-gray-500"
                            >
                                Description
                            </th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody class="divide-y divide-white/[0.05]">
                        <tr
                            v-for="award in awards"
                            :key="award.id"
                            @click.prevent="showPlayerInformation(award.player_id)"
                            class="group cursor-pointer bg-black transition duration-150 hover:bg-white/[0.025]"
                        >
                            <!-- Award -->
                            <td class="px-4 py-4 align-top">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-yellow-500/15 bg-yellow-500/[0.06] transition group-hover:border-yellow-500/30 group-hover:bg-yellow-500/[0.1]"
                                    >
                                        <i
                                            class="fas fa-award text-xs text-yellow-500"
                                        ></i>
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="text-sm font-black leading-tight text-white transition group-hover:text-yellow-400"
                                        >
                                            {{ award.award_name }}
                                        </div>

                                        <div
                                            class="mt-1 text-[8px] font-bold uppercase tracking-wider text-gray-700"
                                        >
                                            Season Honor
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Player -->
                            <td class="px-4 py-4 align-top">
                                <div
                                    class="text-sm font-bold text-gray-200 transition group-hover:text-white"
                                >
                                    {{ award.player_name }}
                                </div>

                                <div
                                    class="mt-1 text-[8px] font-bold uppercase tracking-wider text-gray-600"
                                >
                                    View Player Profile
                                </div>
                            </td>

                            <!-- Team -->
                            <td class="px-4 py-4 align-top">
                                <div
                                    class="inline-flex items-center rounded-lg border border-white/[0.07] bg-white/[0.025] px-2.5 py-1.5"
                                >
                                    <i
                                        class="fas fa-shield-alt mr-2 text-[9px] text-gray-600"
                                    ></i>

                                    <span
                                        class="text-xs font-bold text-gray-300"
                                    >
                                        {{ award.team_name }}
                                    </span>
                                </div>
                            </td>

                            <!-- Description -->
                            <td class="px-4 py-4 align-top">
                                <div
                                    class="max-w-xl text-xs leading-relaxed text-gray-500 transition group-hover:text-gray-400"
                                >
                                    {{ award.award_description }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile scroll hint -->
            <div
                class="mt-2 flex items-center justify-end gap-1.5 px-1 text-[8px] font-bold uppercase tracking-wider text-gray-700"
            >
                <i class="fas fa-arrows-left-right"></i>
                <span>Scroll table horizontally</span>
            </div>
        </div>
    </section>

    <!-- Player Profile Modal -->
    <Modal
        :show="showPlayerProfileModal"
        :maxWidth="'6xl'"
        title="Player Profile"
        @close="showPlayerProfileModal = false"
    >
        <div class="bg-black p-3 sm:p-5">
            <PlayerPerformance
                v-if="selectedPlayer"
                :key="selectedPlayer"
                :player_id="selectedPlayer"
            />
        </div>
    </Modal>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    team_ids: {
        type: Array,
        default: () => [],
    },
});

const awards = ref([]);
const isLoading = ref(false);
const showPlayerProfileModal = ref(false);

/*
|--------------------------------------------------------------------------
| Store the actual player ID
|--------------------------------------------------------------------------
*/
const selectedPlayer = ref(null);

/*
|--------------------------------------------------------------------------
| Progress
|--------------------------------------------------------------------------
|
| Kept because your updatePlayerStatus() workflow uses it.
|
*/
const progressPercentage = ref(0);

/*
|--------------------------------------------------------------------------
| Show Player
|--------------------------------------------------------------------------
*/
const showPlayerInformation = (player_id) => {
    if (!player_id) {
        return;
    }

    selectedPlayer.value = player_id;
    showPlayerProfileModal.value = true;
};

/*
|--------------------------------------------------------------------------
| Update Player Status
|--------------------------------------------------------------------------
|
| Existing functionality preserved.
| This is currently not called automatically on mount.
|
*/
const updatePlayerStatus = async () => {
    try {
        isLoading.value = true;

        const team_ids = props.team_ids ?? [];
        const team_count = team_ids.length;

        if (!team_count) {
            await showSeasonAwards();
            return;
        }

        for (let i = 0; i < team_count; i++) {
            const team_id = team_ids[i];

            await updatePlayerStatusPerTeam(
                i,
                team_id,
                team_count
            );

            progressPercentage.value = Math.round(
                ((i + 1) / team_count) * 100
            );
        }

        await showSeasonAwards();

        Swal.fire({
            icon: "success",
            title: "Success!",
            text: "All player stats updated successfully.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Update Failed",
            text: "Failed to update player status for some or all teams. Please try again later.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    } finally {
        isLoading.value = false;
        progressPercentage.value = 0;
    }
};

/*
|--------------------------------------------------------------------------
| Update Stats Per Team
|--------------------------------------------------------------------------
*/
const updatePlayerStatusPerTeam = async (
    index,
    team_id,
    team_count
) => {
    try {
        await axios.post(
            route("store.player.stats"),
            {
                team_id,
            }
        );
    } catch (error) {
        console.error(error);

        throw new Error(
            "Failed to update player stats for team " + team_id
        );
    }
};

/*
|--------------------------------------------------------------------------
| Load Season Awards
|--------------------------------------------------------------------------
*/
const showSeasonAwards = async () => {
    try {
        isLoading.value = true;
        progressPercentage.value = 50;

        const response = await axios.post(
            route("player.awards")
        );

        awards.value = Array.isArray(response.data?.awards)
            ? response.data.awards
            : [];

        progressPercentage.value = 100;
    } catch (error) {
        console.error(error);

        awards.value = [];

        Swal.fire({
            icon: "error",
            title: "Unable to Load Awards",
            text: "Failed to fetch season awards. Please try again later.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    } finally {
        isLoading.value = false;
        progressPercentage.value = 0;
    }
};

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/
onMounted(() => {
    showSeasonAwards();

    /*
     * Enable this if you want player stats updated automatically
     * before loading the awards:
     *
     * updatePlayerStatus();
     */
});
</script>

<style scoped>
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
    background: #444;
}

/* Selection */
::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}

/* Skeleton */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.4;
    }
}
</style>