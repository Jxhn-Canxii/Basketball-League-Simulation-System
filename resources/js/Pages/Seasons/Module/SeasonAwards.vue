<template>
    <div class="w-full min-w-0 bg-black p-2 text-white md:p-3">
        <!-- ========================================================= -->
        <!-- HEADER                                                    -->
        <!-- ========================================================= -->

        <section
            class="relative mb-3 overflow-hidden rounded-2xl border border-white/[0.08] bg-gradient-to-br from-[#151515] via-[#0b0b0b] to-black shadow-xl"
        >
            <!-- Background decoration -->
            <div
                class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-yellow-500/[0.035] blur-3xl"
            ></div>

            <div
                class="pointer-events-none absolute -bottom-20 left-1/3 h-48 w-48 rounded-full bg-white/[0.02] blur-3xl"
            ></div>

            <div class="relative px-4 py-5 sm:px-5 md:px-7 md:py-6">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div
                            class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.3em] text-yellow-500 sm:text-[10px]"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.7)]"
                            ></span>

                            Season Awards
                        </div>

                        <h2
                            class="text-2xl font-black tracking-tight text-white sm:text-3xl"
                        >
                            Season Awards
                        </h2>

                        <p
                            class="mt-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-gray-600 sm:text-[10px]"
                        >
                            Individual award winners and achievements
                        </p>
                    </div>

                    <!-- Award Count -->
                    <div
                        v-if="awards && awards.length > 0"
                        class="w-fit rounded-xl border border-yellow-500/[0.12] bg-yellow-500/[0.035] px-4 py-3"
                    >
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-600"
                        >
                            Total Awards
                        </div>

                        <div
                            class="mt-1 text-2xl font-black text-yellow-400"
                        >
                            {{ awards.length }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- AWARDS TABLE                                              -->
        <!-- ========================================================= -->

        <section
            v-if="awards && awards.length > 0"
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0a0a0a] shadow-xl"
        >
            <!-- Table Header -->
            <div
                class="flex flex-col gap-2 border-b border-white/[0.08] bg-white/[0.015] px-4 py-4 sm:flex-row sm:items-center sm:justify-between md:px-5"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-yellow-500"
                        ></span>

                        <h3
                            class="text-xs font-black uppercase tracking-wide text-white sm:text-sm"
                        >
                            Award Winners
                        </h3>
                    </div>

                    <p
                        class="mt-1 text-[8px] font-bold uppercase tracking-[0.2em] text-gray-600 sm:text-[9px]"
                    >
                        Complete season award recap
                    </p>
                </div>

                <div
                    class="w-fit rounded-lg border border-white/[0.08] bg-white/[0.025] px-3 py-2 text-[9px] font-black uppercase tracking-wider text-gray-500"
                >
                    {{ awards.length }} Awards
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- TABLE                                                   -->
            <!-- ===================================================== -->

            <div class="min-w-0 overflow-x-auto">
                <table
                    class="w-full min-w-[1050px] border-collapse text-xs"
                >
                    <thead>
                        <tr
                            class="border-b border-white/[0.08] bg-white/[0.02]"
                        >
                            <th
                                class="min-w-[220px] px-4 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Award Name
                            </th>

                            <th
                                class="min-w-[210px] px-4 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Player Name
                            </th>

                            <th
                                class="min-w-[190px] px-4 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Team Name
                            </th>

                            <th
                                class="min-w-[380px] px-4 py-3 text-left text-[8px] font-black uppercase tracking-wider text-gray-600"
                            >
                                Award Description
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(award, index) in awards"
                            :key="award.id ?? index"
                            class="group border-b border-white/[0.045] transition duration-150 hover:bg-white/[0.035]"
                        >
                            <!-- AWARD -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-yellow-500/[0.12] bg-yellow-500/[0.035]"
                                    >
                                        <i
                                            class="fa fa-trophy text-sm text-yellow-500/70"
                                        ></i>
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="font-black text-yellow-400 transition group-hover:text-yellow-300"
                                        >
                                            {{
                                                award.award_name ??
                                                "Unnamed Award"
                                            }}
                                        </div>

                                        <div
                                            class="mt-1 text-[8px] font-bold uppercase tracking-wider text-gray-700"
                                        >
                                            Award #{{ award.id ?? index + 1 }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- PLAYER -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/[0.06] bg-white/[0.035]"
                                    >
                                        <i
                                            class="fa fa-user text-xs text-gray-600"
                                        ></i>
                                    </div>

                                    <div
                                        class="font-black text-white group-hover:text-red-400"
                                    >
                                        {{
                                            award.player_name ??
                                            "Unknown Player"
                                        }}
                                    </div>
                                </div>
                            </td>

                            <!-- TEAM -->
                            <td class="px-4 py-4">
                                <div
                                    class="flex items-center gap-2"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-blue-500"
                                    ></span>

                                    <span
                                        class="font-black text-blue-400"
                                    >
                                        {{
                                            award.team_name ??
                                            "No Team"
                                        }}
                                    </span>
                                </div>
                            </td>

                            <!-- DESCRIPTION -->
                            <td class="px-4 py-4">
                                <div
                                    class="max-w-[600px] whitespace-normal leading-relaxed text-gray-400"
                                >
                                    {{
                                        award.award_description ??
                                        "No description available."
                                    }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ===================================================== -->
            <!-- FOOTER                                                  -->
            <!-- ===================================================== -->

            <div
                class="border-t border-white/[0.08] bg-white/[0.012] px-4 py-3 md:px-5"
            >
                <div
                    class="flex flex-col gap-2 text-[8px] font-bold uppercase tracking-wider text-gray-600 sm:flex-row sm:items-center sm:justify-between sm:text-[9px]"
                >
                    <div class="flex items-center gap-2">
                        <i
                            class="fa fa-award text-yellow-500/50"
                        ></i>

                        Season award recipients
                    </div>

                    <div>
                        {{ awards.length }} recognized
                        {{ awards.length === 1 ? "winner" : "winners" }}
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- EMPTY STATE                                               -->
        <!-- ========================================================= -->

        <section
            v-else
            class="flex min-h-[350px] items-center justify-center rounded-2xl border border-white/[0.08] bg-[#0a0a0a] px-5"
        >
            <div class="text-center">
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-yellow-500/[0.08] bg-yellow-500/[0.025]"
                >
                    <i
                        class="fa fa-trophy text-xl text-gray-700"
                    ></i>
                </div>

                <h2
                    class="text-lg font-black tracking-tight text-white sm:text-xl"
                >
                    NO AWARDS YET
                </h2>

                <p
                    class="mx-auto mt-2 max-w-md text-[10px] font-medium leading-relaxed text-gray-600 sm:text-xs"
                >
                    No awards have been given yet. Please wait
                    until the end of the season.
                </p>
            </div>
        </section>
    </div>
</template>

<script setup>
import {
    ref,
    onMounted,
} from "vue";

import axios from "axios";
import Swal from "sweetalert2";

const awards = ref([]);

const props = defineProps({
    season_id: {
        type: [Array, Number, String],
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| FETCH SEASON AWARDS
|--------------------------------------------------------------------------
*/

const showSeasonAwards = async () => {
    try {
        Swal.fire({
            title: "Fetching Awards...",
            text: "Please wait while we retrieve the season awards.",
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: "#0a0a0a",
            color: "#fff",
            confirmButtonColor: "#eab308",
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(
            route("player.season.awards"),
            {
                season_id: props.season_id,
            }
        );

        awards.value =
            response.data?.awards ?? [];

        Swal.close();
    } catch (error) {
        console.error(
            "Error fetching season awards:",
            error
        );

        awards.value = [];

        Swal.close();

        Swal.fire({
            icon: "error",
            title: "Unable to Load Awards",
            text: "Failed to fetch season awards. Please try again later.",
            background: "#0a0a0a",
            color: "#fff",
            confirmButtonColor: "#eab308",
        });
    }
};

onMounted(() => {
    showSeasonAwards();
});
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Scrollbar
|--------------------------------------------------------------------------
*/

::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: #2f2f2f;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #4a4a4a;
}

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

table {
    border-collapse: collapse;
}

/*
|--------------------------------------------------------------------------
| Horizontal scrolling
|--------------------------------------------------------------------------
*/

.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: #2f2f2f #050505;
}

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>