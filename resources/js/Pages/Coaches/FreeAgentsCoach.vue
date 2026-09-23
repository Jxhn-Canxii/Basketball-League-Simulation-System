<template>
    <section
        class="w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-2xl shadow-black/40"
    >
        <!-- =========================================================
             HEADER
        ========================================================== -->
        <div
            class="border-b border-white/[0.07] bg-[#0b0b0b] px-4 py-4 sm:px-5"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="min-w-0">
                    <div
                        v-if="props.showControls"
                        class="mb-1 flex items-center gap-2"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-yellow-500/20 bg-yellow-500/[0.07]"
                        >
                            <i
                                class="fa fa-user-tie text-xs text-yellow-500"
                            ></i>
                        </span>

                        <span
                            class="text-[9px] font-black uppercase tracking-[0.25em] text-yellow-500/70"
                        >
                            Coaching Market
                        </span>
                    </div>

                    <h2
                        v-if="props.showControls"
                        class="text-lg font-black tracking-tight text-white sm:text-xl"
                    >
                        Coach Signings
                    </h2>

                    <div v-else>
                        <div
                            class="mb-1 text-[9px] font-black uppercase tracking-[0.25em] text-yellow-500/70"
                        >
                            Coaching Market
                        </div>

                        <h2
                            class="text-lg font-black tracking-tight text-white sm:text-xl"
                        >
                            Free Agents Coach List
                        </h2>
                    </div>

                    <p
                        v-if="props.showControls"
                        class="mt-1 text-xs text-gray-500"
                    >
                        Manage available coaches and team vacancies.
                    </p>
                </div>

                <!-- Summary -->
                <div class="flex shrink-0 items-center gap-2">
                    <div
                        class="rounded-xl border border-white/[0.07] bg-white/[0.025] px-3 py-2"
                    >
                        <div
                            class="text-[8px] font-bold uppercase tracking-widest text-gray-600"
                        >
                            Available
                        </div>
                        <div class="mt-0.5 text-sm font-black text-white">
                            {{ data?.total_count ?? data?.coaches?.length ?? 0 }}
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-rose-500/10 bg-rose-500/[0.04] px-3 py-2"
                    >
                        <div
                            class="text-[8px] font-bold uppercase tracking-widest text-rose-400/60"
                        >
                            Vacancies
                        </div>
                        <div class="mt-0.5 text-sm font-black text-rose-400">
                            {{ data?.teams_without_coach_count ?? data?.teams_without_coach?.length ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================
             CONTENT
        ========================================================== -->
        <div class="p-3 sm:p-4 lg:p-5">
            <!-- =====================================================
                 TEAM VACANCIES
            ====================================================== -->
            <div
                class="rounded-xl border border-white/[0.06] bg-[#090909] p-4"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div
                            class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600"
                        >
                            Team Availability
                        </div>

                        <div
                            class="mt-1 text-sm font-bold text-gray-200"
                        >
                            Teams without a coach
                        </div>
                    </div>

                    <div
                        class="self-start rounded-lg border border-white/[0.06] bg-white/[0.025] px-2.5 py-1.5 text-[10px] font-bold text-gray-400 sm:self-auto"
                    >
                        {{ data?.teams_without_coach?.length ?? 0 }}
                        vacancy<span
                            v-if="(data?.teams_without_coach?.length ?? 0) !== 1"
                        >ies</span>
                    </div>
                </div>

                <!-- Team badges -->
                <div
                    v-if="data?.teams_without_coach?.length > 0"
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <span
                        v-for="team in data.teams_without_coach"
                        :key="team.id"
                        class="inline-flex items-center gap-2 rounded-lg border border-rose-500/15 bg-rose-500/[0.05] px-3 py-2 text-xs font-semibold text-rose-300 transition hover:border-rose-500/30 hover:bg-rose-500/[0.09]"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-rose-500"
                        ></span>

                        {{ team.name }}
                    </span>
                </div>

                <!-- No vacancies -->
                <div
                    v-else
                    class="mt-4 flex items-center gap-2 rounded-lg border border-emerald-500/10 bg-emerald-500/[0.04] px-3 py-2.5 text-xs text-emerald-400"
                >
                    <i class="fa fa-check-circle"></i>
                    <span>All teams currently have a coach.</span>
                </div>
            </div>

            <!-- =====================================================
                 CONTROLS
            ====================================================== -->
            <div
                v-if="props.showControls"
                class="mt-4 rounded-xl border border-white/[0.06] bg-[#090909] p-3"
            >
                <div
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
                >
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <!-- Auto Sign -->
                        <button
                            v-if="data?.teams_without_coach_count > 0"
                            type="button"
                            @click="assignTeamsAuto"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-500/20 bg-rose-500/[0.08] px-4 py-2.5 text-xs font-bold text-rose-300 transition hover:border-rose-500/35 hover:bg-rose-500/[0.14] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <i class="fa fa-users"></i>
                            Auto Sign Coaches
                        </button>

                        <!-- Skip -->
                        <button
                            v-if="data?.teams_without_coach_count == 0"
                            type="button"
                            @click="endCoachSigning"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2.5 text-xs font-bold text-gray-300 transition hover:bg-white/[0.08]"
                        >
                            <i class="fa fa-arrow-right text-gray-500"></i>
                            Skip
                        </button>
                    </div>

                    <!-- Invite -->
                    <button
                        type="button"
                        @click.prevent="addMultiplePlayers(80)"
                        :disabled="inviting"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/[0.08] px-4 py-2.5 text-xs font-bold text-emerald-300 transition hover:border-emerald-500/35 hover:bg-emerald-500/[0.14] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i
                            v-if="!inviting"
                            class="fa fa-user-plus"
                        ></i>

                        <i
                            v-else
                            class="fa fa-spinner fa-spin"
                        ></i>

                        {{ inviting ? "Inviting Coaches..." : "Invite Coaches" }}
                    </button>
                </div>
            </div>

            <!-- =====================================================
                 SEARCH
            ====================================================== -->
            <div class="mt-4">
                <div
                    class="flex items-center gap-2 rounded-xl border border-white/[0.07] bg-[#090909] px-3"
                >
                    <i class="fa fa-search text-xs text-gray-600"></i>

                    <input
                        id="LeagueName"
                        v-model="search.search"
                        type="text"
                        placeholder="Search coach name..."
                        autocomplete="off"
                        class="w-full border-0 bg-transparent py-3 text-xs text-white outline-none placeholder:text-gray-700 focus:ring-0"
                        @input="fetchFreeAgent"
                    />

                    <button
                        v-if="search.search"
                        type="button"
                        @click="search.search = ''; fetchFreeAgent()"
                        class="text-gray-600 transition hover:text-gray-300"
                    >
                        <i class="fa fa-times text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- =====================================================
                 LOADING
            ====================================================== -->
            <div
                v-if="loading"
                class="mt-4 overflow-hidden rounded-xl border border-white/[0.06] bg-[#090909]"
            >
                <div class="space-y-2 p-4">
                    <div
                        v-for="n in 8"
                        :key="n"
                        class="h-9 animate-pulse rounded-lg bg-white/[0.035]"
                    ></div>
                </div>
            </div>

            <!-- =====================================================
                 EMPTY
            ====================================================== -->
            <div
                v-else-if="data?.coaches?.length === 0"
                class="mt-4 rounded-xl border border-white/[0.06] bg-[#090909] px-4 py-12 text-center"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.025]"
                >
                    <i class="fa fa-user-tie text-gray-700"></i>
                </div>

                <div class="mt-4 text-sm font-bold text-gray-300">
                    No coaches found
                </div>

                <div class="mt-1 text-xs text-gray-600">
                    No available coaches match your search.
                </div>
            </div>

            <!-- =====================================================
                 TABLE
            ====================================================== -->
            <div
                v-else
                class="mt-4 overflow-hidden rounded-xl border border-white/[0.07] bg-[#090909]"
            >
                <div
                    class="border-b border-white/[0.06] px-4 py-3"
                >
                    <div
                        class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div
                                class="text-[9px] font-black uppercase tracking-[0.2em] text-yellow-500/60"
                            >
                                Coach Directory
                            </div>

                            <div
                                class="mt-0.5 text-xs font-semibold text-gray-400"
                            >
                                Click a coach to view profile
                            </div>
                        </div>

                        <div
                            class="text-[10px] text-gray-600"
                        >
                            {{ data.coaches.length }} shown
                        </div>
                    </div>
                </div>

                <!--
                    Important:
                    Horizontal scrolling belongs here instead of on
                    the whole component. This keeps the page responsive
                    when the parent is not full width.
                -->
                <div class="w-full overflow-x-auto">
                    <table
                        class="w-full min-w-[1200px] border-collapse text-xs"
                    >
                        <thead>
                            <tr
                                class="border-b border-white/[0.07] bg-white/[0.025]"
                            >
                                <th
                                    v-for="header in tableHeaders"
                                    :key="header.key"
                                    :class="[
                                        'px-3 py-3 text-[9px] font-black uppercase tracking-wider text-gray-600',
                                        header.align === 'right'
                                            ? 'text-right'
                                            : header.align === 'center'
                                              ? 'text-center'
                                              : 'text-left'
                                    ]"
                                >
                                    {{ header.label }}
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="coach in data.coaches"
                                :key="coach.id"
                                @click.prevent="showCoachProfile(coach)"
                                class="group cursor-pointer border-b border-white/[0.045] transition last:border-b-0 hover:bg-white/[0.035]"
                            >
                                <!-- ID -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 font-mono text-[10px] text-gray-600"
                                >
                                    #{{ coach.id }}
                                </td>

                                <!-- Name -->
                                <td
                                    class="whitespace-nowrap px-3 py-3"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-white/[0.07] bg-white/[0.035] text-[10px] text-gray-500 transition group-hover:border-yellow-500/20 group-hover:text-yellow-500"
                                        >
                                            <i class="fa fa-user-tie"></i>
                                        </div>

                                        <div>
                                            <div
                                                class="font-bold text-gray-200 transition group-hover:text-white"
                                            >
                                                {{ coach.name }}
                                            </div>

                                            <div
                                                class="mt-0.5 text-[9px] text-gray-700"
                                            >
                                                Coach #{{ coach.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Team -->
                                <td
                                    class="whitespace-nowrap px-3 py-3"
                                >
                                    <span
                                        v-if="coach.team_name"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-white/[0.06] bg-white/[0.025] px-2 py-1 text-[10px] font-semibold text-gray-400"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>

                                        {{ coach.team_name }}
                                    </span>

                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-md border border-rose-500/10 bg-rose-500/[0.04] px-2 py-1 text-[10px] font-semibold text-rose-400"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-rose-500"
                                        ></span>

                                        Free Agent
                                    </span>
                                </td>

                                <!-- Coach IQ -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-right"
                                >
                                    <span
                                        :class="[
                                            'inline-flex min-w-[42px] items-center justify-center rounded-md border px-2 py-1 font-mono text-[10px] font-black',
                                            getCoachIqClass(coach.coach_iq)
                                        ]"
                                    >
                                        {{ coach.coach_iq }}
                                    </span>
                                </td>

                                <!-- Contract -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-gray-400"
                                >
                                    <span class="font-semibold text-gray-300">
                                        {{ coach.contract_years }}
                                    </span>
                                    <span class="ml-1 text-gray-700">
                                        yrs
                                    </span>
                                </td>

                                <!-- Experience -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-gray-400"
                                >
                                    <span class="font-semibold text-gray-300">
                                        {{ coach.experience_years }}
                                    </span>
                                    <span class="ml-1 text-gray-700">
                                        yrs
                                    </span>
                                </td>

                                <!-- Age -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-right font-mono text-gray-400"
                                >
                                    {{ coach.age }}
                                </td>

                                <!-- Retirement -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-right font-mono text-gray-400"
                                >
                                    {{ coach.retirement_age }}
                                </td>

                                <!-- Wins -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-right font-mono text-emerald-400/80"
                                >
                                    {{ coach.career_wins }}
                                </td>

                                <!-- Losses -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-right font-mono text-rose-400/80"
                                >
                                    {{ coach.career_losses }}
                                </td>

                                <!-- Winning Percentage -->
                                <td
                                    class="whitespace-nowrap px-3 py-3 text-center"
                                >
                                    <div
                                        class="inline-flex items-center gap-2"
                                    >
                                        <div
                                            class="h-1 w-10 overflow-hidden rounded-full bg-white/[0.06]"
                                        >
                                            <div
                                                class="h-full rounded-full bg-emerald-500/70"
                                                :style="{
                                                    width: `${winningPercentage(coach)}%`
                                                }"
                                            ></div>
                                        </div>

                                        <span
                                            class="font-mono text-[10px] font-bold text-gray-300"
                                        >
                                            {{
                                                parseFloat(
                                                    coach.winning_percentage || 0
                                                ).toFixed(1)
                                            }}%
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td
                                    class="whitespace-nowrap px-3 py-3"
                                >
                                    <span
                                        v-if="coach.is_active"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-emerald-500/15 bg-emerald-500/[0.06] px-2 py-1 text-[9px] font-black uppercase tracking-wide text-emerald-400"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        Active
                                    </span>

                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-md border border-rose-500/15 bg-rose-500/[0.06] px-2 py-1 text-[9px] font-black uppercase tracking-wide text-rose-400"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-rose-500"
                                        ></span>
                                        Retired
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table footer -->
                <div
                    class="border-t border-white/[0.06] bg-white/[0.015] px-3 py-2"
                >
                    <div class="overflow-x-auto">
                        <Paginator
                            v-if="data.total_count"
                            :page_number="search.page_num"
                            :total_rows="data.total_count ?? 0"
                            :itemsperpage="search.itemsperpage"
                            @page_num="handlePagination"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =============================================================
         COACH PROFILE MODAL
    ============================================================= -->
    <Modal
        :show="showCoachProfileModal"
        :maxWidth="'8xl'"
        title="Coach Information"
        @close="showCoachProfileModal = false"
    >
        <div
            v-if="selectedCoach?.id"
            class="overflow-hidden bg-black p-4 text-white sm:p-6"
        >
            <CoachProfile
                :key="selectedCoach.id"
                :coach_id="selectedCoach.id"
            />
        </div>
    </Modal>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";
import CoachProfile from "./Module/CoachProfile.vue";

const props = defineProps({
    showControls: {
        type: Boolean,
        default: true,
    },
});

const emits = defineEmits(["newSeason"]);

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const showCoachProfileModal = ref(false);

const selectedCoach = ref(null);

const loading = ref(false);

const inviting = ref(false);

const data = ref({
    coaches: [],
    teams_without_coach: [],
    teams_without_coach_count: 0,
    total_count: 0,
});

const search = ref({
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    itemsperpage: 10,
});

/*
|--------------------------------------------------------------------------
| Table Headers
|--------------------------------------------------------------------------
*/

const tableHeaders = computed(() => [
    {
        key: "id",
        label: "ID",
        align: "left",
    },
    {
        key: "name",
        label: "Coach",
        align: "left",
    },
    {
        key: "team",
        label: "Team",
        align: "left",
    },
    {
        key: "iq",
        label: "Coach IQ",
        align: "right",
    },
    {
        key: "contract",
        label: "Contract",
        align: "left",
    },
    {
        key: "experience",
        label: "Experience",
        align: "left",
    },
    {
        key: "age",
        label: "Age",
        align: "right",
    },
    {
        key: "retirement",
        label: "Retirement",
        align: "right",
    },
    {
        key: "wins",
        label: "Career Wins",
        align: "right",
    },
    {
        key: "losses",
        label: "Career Losses",
        align: "right",
    },
    {
        key: "winning_percentage",
        label: "Winning %",
        align: "center",
    },
    {
        key: "status",
        label: "Status",
        align: "left",
    },
]);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getCoachIqClass = (iq) => {
    const value = Number(iq) || 0;

    if (value >= 90) {
        return "border-yellow-500/20 bg-yellow-500/[0.08] text-yellow-400";
    }

    if (value >= 80) {
        return "border-emerald-500/20 bg-emerald-500/[0.08] text-emerald-400";
    }

    if (value >= 70) {
        return "border-blue-500/20 bg-blue-500/[0.08] text-blue-400";
    }

    if (value >= 60) {
        return "border-orange-500/20 bg-orange-500/[0.08] text-orange-400";
    }

    return "border-white/[0.08] bg-white/[0.035] text-gray-400";
};

const winningPercentage = (coach) => {
    const value = parseFloat(coach?.winning_percentage || 0);

    return Math.min(100, Math.max(0, value));
};

/*
|--------------------------------------------------------------------------
| Coach Profile
|--------------------------------------------------------------------------
*/

const showCoachProfile = (coach) => {
    selectedCoach.value = coach;
    showCoachProfileModal.value = true;
};

/*
|--------------------------------------------------------------------------
| Generate Random Coach Information
|--------------------------------------------------------------------------
*/

const fetchRandomFullName = async () => {
    try {
        const response = await axios.get(
            route("generate.new.player")
        );

        const {
            name,
            country,
            address,
        } = response.data;

        return {
            name,
            country,
            address,
        };
    } catch (error) {
        console.error(
            "Error fetching random coach name:",
            error
        );

        return null;
    }
};

/*
|--------------------------------------------------------------------------
| Invite Single Coach
|--------------------------------------------------------------------------
*/

const inviteCoach = async (info) => {
    try {
        const response = await axios.post(
            route("coaches.add.free.agent"),
            {
                name: info.name,
                nationality: info.country,
            }
        );

        return {
            success: true,
            message: response.data?.message || "Coach added.",
        };
    } catch (error) {
        console.error(
            "Error adding coach:",
            error.response?.data?.message || error.message
        );

        return {
            success: false,
            message:
                error.response?.data?.message ||
                "Failed to add coach.",
        };
    }
};

/*
|--------------------------------------------------------------------------
| Invite Multiple Coaches
|--------------------------------------------------------------------------
*/

const addMultiplePlayers = async (count) => {
    if (inviting.value) {
        return;
    }

    inviting.value = true;

    try {
        Swal.fire({
            title: "Inviting Coaches",
            text: `Generating ${count} coach candidates. Please wait...`,
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        let successCount = 0;
        let failedCount = 0;

        /*
         * Keep requests sequential here because the random-name
         * generator and coach creation endpoints may otherwise
         * receive a large burst of requests.
         */
        for (let i = 0; i < count; i++) {
            const randomFullName =
                await fetchRandomFullName();

            if (!randomFullName) {
                failedCount++;
                continue;
            }

            const result =
                await inviteCoach(randomFullName);

            if (result.success) {
                successCount++;
            } else {
                failedCount++;
            }
        }

        Swal.close();

        await Swal.fire({
            icon:
                failedCount > 0
                    ? "warning"
                    : "success",
            title:
                failedCount > 0
                    ? "Completed with warnings"
                    : "Success!",
            html: `
                <div style="font-size:14px;line-height:1.8">
                    <div>
                        <strong>${successCount}</strong>
                        coaches added successfully.
                    </div>

                    ${
                        failedCount > 0
                            ? `
                                <div style="color:#fb7185">
                                    ${failedCount}
                                    coaches failed.
                                </div>
                            `
                            : ""
                    }
                </div>
            `,
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#16a34a",
        });

        await fetchFreeAgent();
    } catch (error) {
        console.error(
            "Error adding multiple coaches:",
            error
        );

        Swal.close();

        await Swal.fire({
            icon: "error",
            title: "Unable to add coaches",
            text:
                error.response?.data?.message ||
                error.message ||
                "An unexpected error occurred.",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });
    } finally {
        inviting.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Fetch Coaches
|--------------------------------------------------------------------------
*/

const fetchFreeAgent = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("coaches.list"),
            search.value
        );

        data.value = {
            coaches: Array.isArray(
                response.data?.coaches
            )
                ? response.data.coaches
                : [],

            teams_without_coach:
                Array.isArray(
                    response.data?.teams_without_coach
                )
                    ? response.data.teams_without_coach
                    : [],

            teams_without_coach_count:
                Number(
                    response.data
                        ?.teams_without_coach_count
                ) || 0,

            total_count:
                Number(
                    response.data?.total_count
                ) || 0,
        };
    } catch (error) {
        console.error(
            "Error fetching free agent coaches:",
            error
        );

        data.value = {
            coaches: [],
            teams_without_coach: [],
            teams_without_coach_count: 0,
            total_count: 0,
        };
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;

    fetchFreeAgent();
};

/*
|--------------------------------------------------------------------------
| Auto Assign Coaches
|--------------------------------------------------------------------------
*/

const assignTeamsAuto = async () => {
    try {
        const result = await Swal.fire({
            title: "Assign Coaches?",
            text: "Free-agent coaches will be assigned automatically to teams without a coach.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, assign them",
            cancelButtonText: "Cancel",
            reverseButtons: true,
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#e11d48",
            cancelButtonColor: "#27272a",
        });

        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({
            title: "Processing...",
            text: "Assigning free-agent coaches to teams.",
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: "#0b0b0b",
            color: "#fff",
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.get(
            route("assign.coach.teams")
        );

        Swal.close();

        await Swal.fire({
            icon: "success",
            title: "Coaches Assigned",
            html: response.data.message,
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#16a34a",
        });

        emits("newSeason", Math.random());

        await fetchFreeAgent();
    } catch (error) {
        console.error(
            "Error assigning teams:",
            error
        );

        Swal.close();

        await Swal.fire({
            icon: "error",
            title: "Assignment Failed",
            text:
                error.response?.data?.message ||
                "An unexpected error occurred.",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });

        emits("newSeason", true);
    }
};

/*
|--------------------------------------------------------------------------
| End Coach Signing
|--------------------------------------------------------------------------
*/

const endCoachSigning = async () => {
    try {
        const storylineSuccess =
            await generateSeasonStoryLine();

        /*
         * Do not continue if storyline generation failed.
         */
        if (!storylineSuccess) {
            return;
        }

        const response = await axios.get(
            route("end.coach.signings")
        );

        await Swal.fire({
            title: "Coach Signing Complete",
            text: response.data.message,
            icon: "success",
            confirmButtonText: "OK",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#eab308",
        });

        await fetchFreeAgent();

        emits("newSeason", Math.random());
    } catch (error) {
        console.error(
            "Error ending coach signing:",
            error
        );

        Swal.close();

        await Swal.fire({
            title: "Unable to Finish",
            text:
                error.response?.data?.message ||
                "An unexpected error occurred.",
            icon: "error",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Generate Season Storyline
|--------------------------------------------------------------------------
*/

const generateSeasonStoryLine = async () => {
    try {
        Swal.fire({
            title: "Summarizing Season",
            text: "Generating storyline for this season...",
            icon: "info",
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: "#0b0b0b",
            color: "#fff",
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.get(
            route("storyline.season")
        );

        Swal.close();

        return true;
    } catch (error) {
        console.error(
            "Error generating storyline:",
            error
        );

        Swal.close();

        await Swal.fire({
            icon: "warning",
            title: "Storyline Warning",
            text:
                error.response?.data?.message ||
                "Unable to generate the season storyline.",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#eab308",
        });

        return false;
    }
};

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchFreeAgent();
});
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Horizontal scrollbar
|--------------------------------------------------------------------------
*/

.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 999px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
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

/*
|--------------------------------------------------------------------------
| Input autofill
|--------------------------------------------------------------------------
*/

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
    -webkit-text-fill-color: white;
    transition: background-color 9999s ease-in-out 0s;
}
</style>