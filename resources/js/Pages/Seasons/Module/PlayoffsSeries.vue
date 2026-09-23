<template>
    <!-- ============================================================= -->
    <!-- PLAYOFFS -->
    <!-- ============================================================= -->

    <div
        v-if="
            season_info?.seasons &&
            season_info.seasons[0]?.status >= 2 &&
            !loading
        "
        class="w-full min-w-0 overflow-hidden rounded-xl border border-gray-800 bg-gray-950 text-white shadow-2xl"
    >
        <div class="w-full min-w-0 overflow-y-auto">
            <!-- ===================================================== -->
            <!-- HEADER -->
            <!-- ===================================================== -->

            <div
                v-if="!isHide"
                class="flex flex-col gap-4 border-b border-gray-800 bg-gray-950 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-orange-500/20 bg-orange-500/10"
                        >
                            <svg
                                class="h-5 w-5 text-orange-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M6 3v18" />
                                <path d="M6 5h9a4 4 0 0 1 0 8H6" />
                                <path d="M18 5v16" />
                                <path d="M18 9h-4" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h2
                                class="truncate text-base font-black uppercase tracking-wider text-white sm:text-lg"
                            >
                                Playoffs Series
                            </h2>

                            <p class="mt-0.5 text-[10px] uppercase tracking-widest text-gray-600">
                                Championship bracket
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SIMULATE FULL PLAYOFFS -->

                <button
                    :disabled="
                        season_info?.seasons &&
                        (
                            season_info.seasons[0].status < 2 ||
                            season_info.seasons[0].status > 11
                        )
                    "
                    :class="
                        season_info?.seasons &&
                        (
                            season_info.seasons[0].status < 2 ||
                            season_info.seasons[0].status > 11
                        )
                            ? 'cursor-not-allowed border-gray-800 bg-gray-800 text-gray-600'
                            : 'border-orange-500/30 bg-orange-500/10 text-orange-400 hover:border-orange-400/50 hover:bg-orange-500/20'
                    "
                    @click="simulateFullPlayoffs"
                    type="button"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-xs font-black uppercase tracking-wider transition duration-200 disabled:opacity-50"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                    </svg>

                    Simulate Full Playoffs
                </button>
            </div>

            <!-- ===================================================== -->
            <!-- SERIES RESULT -->
            <!-- ===================================================== -->

            <div
                v-if="showSeriesResult && active_series_id !== 0"
                class="border-b border-gray-800 bg-gray-950 p-4"
            >
                <SeriesResult
                    :key="active_series_id"
                    :series_id="active_series_id"
                    :season_id="props.season_id"
                    @finish="onSeriesResultFinish"
                />
            </div>

            <!-- ===================================================== -->
            <!-- PLAYOFF SERIES -->
            <!-- ===================================================== -->

            <div
                v-if="season_playoffs?.playoffs"
                class="w-full min-w-0 px-3 py-4 sm:px-4 sm:py-5"
            >
                <div class="space-y-8">
                    <template
                        v-for="(r, rr) in roundOrder"
                        :key="rr"
                    >
                        <!-- Safe round reference -->
                        <div
                            v-if="getRoundSeries(r).length > 0"
                            class="min-w-0"
                        >
                            <!-- ================================================= -->
                            <!-- ROUND HEADER -->
                            <!-- ================================================= -->

                            <div class="mb-4 flex items-center gap-3">
                                <div
                                    class="h-2 w-2 shrink-0 rounded-full"
                                    :class="
                                        r === 'finals'
                                            ? 'bg-orange-400 shadow-[0_0_10px_rgba(251,146,60,0.6)]'
                                            : r.includes('play_ins')
                                                ? 'bg-cyan-400'
                                                : 'bg-gray-500'
                                    "
                                ></div>

                                <h3
                                    class="text-sm font-black uppercase tracking-[0.16em]"
                                    :class="
                                        r === 'finals'
                                            ? 'text-orange-400'
                                            : r.includes('play_ins')
                                                ? 'text-cyan-400'
                                                : 'text-gray-300'
                                    "
                                >
                                    {{ roundNameFormatter(r) }}
                                </h3>

                                <div class="h-px flex-1 bg-gray-800"></div>

                                <span
                                    class="shrink-0 rounded-md border border-gray-800 bg-gray-900 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-gray-500"
                                >
                                    {{ getRoundSeries(r).length }}
                                    {{ getRoundSeries(r).length === 1 ? 'Series' : 'Series' }}
                                </span>
                            </div>

                            <!-- ================================================= -->
                            <!-- SERIES GRID -->
                            <!-- ================================================= -->

                            <div
                                class="w-full min-w-0"
                                :class="
                                    getRoundSeries(r).length >= 4
                                        ? 'overflow-x-auto pb-2'
                                        : ''
                                "
                            >
                                <div
                                    :class="
                                        getRoundSeries(r).length >= 4
                                            ? 'grid min-w-[900px] grid-cols-4 gap-3 xl:gap-4'
                                            : getRoundSeries(r).length === 2
                                                ? 'flex flex-wrap justify-center gap-4'
                                                : 'flex justify-center'
                                    "
                                >
                                    <div
                                        v-for="(ser, serr) in getRoundSeries(r)"
                                        :key="ser.id ?? serr"
                                        class="min-w-0"
                                        :class="
                                            getRoundSeries(r).length === 2
                                                ? 'w-full sm:w-[calc(50%-0.5rem)] lg:max-w-[420px]'
                                                : getRoundSeries(r).length === 1
                                                    ? 'w-full max-w-[460px]'
                                                    : ''
                                        "
                                    >
                                        <div
                                            class="group relative h-full overflow-hidden rounded-xl border border-gray-800 bg-gray-900/80 p-1.5 transition duration-200 hover:border-gray-700 hover:bg-gray-900"
                                            :class="
                                                r === 'finals'
                                                    ? 'border-orange-500/30 bg-orange-500/[0.03] shadow-[0_0_30px_rgba(251,146,60,0.05)]'
                                                    : ''
                                            "
                                        >
                                            <!-- Top accent -->
                                            <div
                                                class="absolute left-0 right-0 top-0 h-px opacity-0 transition group-hover:opacity-100"
                                                :class="
                                                    r === 'finals'
                                                        ? 'bg-orange-400'
                                                        : 'bg-gray-500'
                                                "
                                            ></div>

                                            <SeriesCard
                                                :series="ser"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- EMPTY PLAYOFF STATE -->
            <!-- ===================================================== -->

            <div
                v-else
                class="flex min-h-[300px] items-center justify-center px-4"
            >
                <div class="text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-gray-800 bg-gray-900"
                    >
                        <svg
                            class="h-6 w-6 text-gray-600"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 2" />
                        </svg>
                    </div>

                    <p
                        class="text-xs font-black uppercase tracking-[0.2em] text-gray-600"
                    >
                        No Playoff Series
                    </p>

                    <p class="mt-2 text-xs text-gray-700">
                        Playoff schedules have not been generated yet.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- REGULAR SEASON NOT FINISHED -->
    <!-- ============================================================= -->

    <div
        v-if="
            season_info?.seasons &&
            season_info.seasons[0]?.status < 3 &&
            !loading
        "
        class="flex min-h-[500px] w-full items-center justify-center border-b border-gray-800 bg-gray-950 p-4"
    >
        <div
            class="w-full max-w-xl rounded-2xl border border-gray-800 bg-gray-900 p-6 text-center shadow-2xl sm:p-8"
        >
            <div
                class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-orange-500/20 bg-orange-500/10"
            >
                <svg
                    class="h-7 w-7 text-orange-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 2" />
                </svg>
            </div>

            <h2
                class="text-xl font-black uppercase tracking-wide text-orange-400 sm:text-2xl"
            >
                Playoffs Not Available
            </h2>

            <p class="mt-3 text-sm leading-6 text-gray-500">
                Finish the regular season before proceeding to the playoffs.
            </p>

            <a
                :href="
                    route('seasons.details', {
                        season_id: props.season_id,
                        playoff_type: 2,
                    })
                "
                class="mt-6 inline-flex items-center justify-center rounded-lg border border-orange-500/30 bg-orange-500/10 px-5 py-2.5 text-xs font-black uppercase tracking-wider text-orange-400 transition hover:border-orange-400/50 hover:bg-orange-500/20"
            >
                Go to Regular Season
            </a>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- LOADING -->
    <!-- ============================================================= -->

    <div
        v-if="loading"
        class="flex min-h-[300px] w-full items-center justify-center bg-gray-950"
    >
        <div class="flex flex-col items-center gap-4">
            <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-gray-800 border-t-orange-400"
            ></div>

            <p
                class="text-xs font-black uppercase tracking-[0.2em] text-gray-500"
            >
                Loading Playoffs
            </p>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- GAME NEWS MODAL -->
    <!-- ============================================================= -->

    <div
        v-if="isGameNewsModalOpen && series?.news"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/80 p-3 backdrop-blur-sm sm:p-6 lg:p-10"
        @click.self="isGameNewsModalOpen = false"
    >
        <div
            class="my-4 w-full max-w-6xl overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 shadow-2xl sm:my-8"
        >
            <!-- Modal header -->

            <div
                class="flex items-center justify-between border-b border-gray-800 bg-gray-900 px-4 py-4 sm:px-5"
            >
                <div>
                    <p
                        class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-600"
                    >
                        Game Result
                    </p>

                    <h3 class="mt-1 text-base font-black text-white sm:text-lg">
                        Series Lead:
                        <span class="text-orange-400">
                            {{ series?.series_lead ?? '-' }}
                        </span>
                    </h3>
                </div>

                <button
                    type="button"
                    @click="isGameNewsModalOpen = false"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-800 bg-gray-950 text-gray-500 transition hover:border-gray-700 hover:text-white"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M18 6L6 18" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal content -->

            <div
                class="grid grid-cols-1 gap-0 lg:grid-cols-3"
            >
                <!-- ================================================= -->
                <!-- GAME SUMMARY -->
                <!-- ================================================= -->

                <div
                    class="min-w-0 border-b border-gray-800 p-4 lg:col-span-2 lg:border-b-0 lg:border-r sm:p-5"
                >
                    <!-- Breakdown -->

                    <div
                        v-if="series?.break_down"
                        class="mb-5 overflow-x-auto rounded-xl border border-gray-800"
                    >
                        <table
                            class="min-w-[800px] w-full overflow-hidden text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-gray-800 bg-black text-gray-500"
                                >
                                    <th
                                        class="px-3 py-3 text-left text-[9px] font-black uppercase tracking-wider"
                                    >
                                        Team
                                    </th>

                                    <th
                                        class="px-3 py-3 text-center text-[9px] font-black uppercase"
                                    >
                                        Q1
                                    </th>

                                    <th
                                        class="px-3 py-3 text-center text-[9px] font-black uppercase"
                                    >
                                        Q2
                                    </th>

                                    <th
                                        class="px-3 py-3 text-center text-[9px] font-black uppercase text-gray-700"
                                    >
                                        1H
                                    </th>

                                    <th
                                        class="px-3 py-3 text-center text-[9px] font-black uppercase"
                                    >
                                        Q3
                                    </th>

                                    <th
                                        class="px-3 py-3 text-center text-[9px] font-black uppercase"
                                    >
                                        Q4
                                    </th>

                                    <th
                                        class="px-3 py-3 text-center text-[9px] font-black uppercase text-gray-700"
                                    >
                                        2H
                                    </th>

                                    <template v-if="series?.is_overtime">
                                        <th
                                            v-for="ot in series.is_overtime"
                                            :key="`ot-head-${ot}`"
                                            class="px-3 py-3 text-center text-[9px] font-black uppercase text-orange-400"
                                        >
                                            OT{{ ot }}
                                        </th>
                                    </template>

                                    <th
                                        class="px-4 py-3 text-center text-[9px] font-black uppercase tracking-wider text-orange-400"
                                    >
                                        Total
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="b in series.break_down"
                                    :key="b.id"
                                    :style="{
                                        backgroundColor:
                                            '#' +
                                            (
                                                series?.home_team?.id == b?.team_id
                                                    ? series?.home_team?.primary_color
                                                    : series?.away_team?.primary_color
                                            ),
                                    }"
                                    class="border-b border-black/20 last:border-0"
                                >
                                    <td class="px-3 py-3 text-left font-bold text-white">
                                        {{ b.team_name }}
                                    </td>

                                    <td class="px-3 py-3 text-center font-semibold text-white">
                                        {{ b.Q1 }}
                                    </td>

                                    <td class="px-3 py-3 text-center font-semibold text-white">
                                        {{ b.Q2 }}
                                    </td>

                                    <td class="px-3 py-3 text-center text-xs text-white/50">
                                        {{ b.Q1 + b.Q2 }}
                                    </td>

                                    <td class="px-3 py-3 text-center font-semibold text-white">
                                        {{ b.Q3 }}
                                    </td>

                                    <td class="px-3 py-3 text-center font-semibold text-white">
                                        {{ b.Q4 }}
                                    </td>

                                    <td class="px-3 py-3 text-center text-xs text-white/50">
                                        {{ b.Q3 + b.Q4 }}
                                    </td>

                                    <td
                                        v-if="series?.is_overtime > 0"
                                        class="px-3 py-3 text-center font-semibold text-orange-300"
                                    >
                                        {{ b.OT1 }}
                                    </td>

                                    <td
                                        v-if="series?.is_overtime > 1"
                                        class="px-3 py-3 text-center font-semibold text-orange-300"
                                    >
                                        {{ b.OT2 }}
                                    </td>

                                    <td
                                        v-if="series?.is_overtime > 2"
                                        class="px-3 py-3 text-center font-semibold text-orange-300"
                                    >
                                        {{ b.OT3 }}
                                    </td>

                                    <td
                                        class="px-4 py-3 text-center text-lg font-black text-white"
                                    >
                                        {{ b.total }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Game News -->

                    <div>
                        <div class="mb-3 flex items-center gap-3">
                            <h3
                                class="text-xs font-black uppercase tracking-widest text-gray-300"
                            >
                                Game Summary
                            </h3>

                            <div class="h-px flex-1 bg-gray-800"></div>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900">
                            <GameNews
                                :key="series?.news?.id"
                                :data="series?.news"
                                :showNews="true"
                                :textLarge="true"
                            />
                        </div>
                    </div>
                </div>

                <!-- ================================================= -->
                <!-- PAST RESULTS -->
                <!-- ================================================= -->

                <div class="min-w-0 p-4 sm:p-5">
                    <div class="mb-3 flex items-center gap-3">
                        <h3
                            class="text-xs font-black uppercase tracking-widest text-gray-300"
                        >
                            Past Results
                        </h3>

                        <span
                            v-if="series?.past_results?.length"
                            class="rounded bg-gray-900 px-2 py-1 text-[9px] font-bold text-gray-600"
                        >
                            {{ series.past_results.length }}
                        </span>

                        <div class="h-px flex-1 bg-gray-800"></div>
                    </div>

                    <div
                        v-if="series?.past_results?.length > 0"
                        class="flex flex-col gap-2"
                    >
                        <div
                            v-for="ps in series.past_results"
                            :key="ps.id"
                            :style="{
                                backgroundColor:
                                    '#' + (ps?.primary_color ?? '00000f'),
                            }"
                            class="rounded-xl border border-white/5 p-3 transition hover:border-white/10"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p
                                        class="text-[9px] font-black uppercase tracking-wider text-white/50"
                                    >
                                        Game {{ ps.game_number }}
                                    </p>

                                    <p class="mt-1 text-lg font-black text-white">
                                        {{ ps.home_score }}
                                        <span class="mx-1 text-white/40">-</span>
                                        {{ ps.away_score }}
                                    </p>
                                </div>

                                <div class="text-right">
                                    <p
                                        class="text-[9px] font-black uppercase tracking-wider text-yellow-400"
                                    >
                                        Winner
                                    </p>

                                    <p class="mt-1 max-w-[130px] truncate text-xs font-bold text-white">
                                        {{ ps.winner_team_name ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-3 border-t border-white/10 pt-2 text-[9px] text-white/50"
                            >
                                <span class="font-bold text-yellow-400">
                                    G{{ ps.game_number }}
                                </span>

                                <span class="mx-1">•</span>

                                {{ ps.home_team_name }}
                                vs
                                {{ ps.away_team_name }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-gray-800 bg-gray-900/50 p-8 text-center"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-widest text-gray-700"
                        >
                            No Previous Results
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import axios from "axios";

import {
    roundNameFormatter,
    roundGridFormatter,
    roundStatusFormatter,
} from "@/Utility/Formatter.js";

import GameNews from "@/Pages/Seasons/Module/GameNews.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";

import GameResults from "@/Pages/Seasons/Module/GameResults.vue";
import ScoreCard from "@/Pages/Seasons/Module/ScoreCard.vue";
import SeriesCard from "@/Pages/Seasons/Module/SeriesCard.vue";

const isAddModalOpen = ref(false);
const isTeamModalOpen = ref(false);
const isTeamComparisonModalOpen = ref(false);
const isGameNewsModalOpen = ref(false);
const showSeriesResult = ref(false);

const loading = ref(false);

const change_key = ref(localStorage.getItem("season-key"));

const isHide = ref(false);
const activeIndex = ref(0);

const season_info = ref(false);
const season_playoffs = ref(false);

const is_play_ins = ref(false);
const active_series_id = ref(false);

const series = ref(0);

const form = useForm({
    seasons_id: 0,
});

const comparison = useForm({
    season_id: 0,
    home_id: 0,
    away_id: 0,
});

const props = defineProps({
    season_id: {
        type: [Number, String],
        required: true,
    },
});

const roundOrder = [
    "play_ins_elims_round_1",
    "play_ins_elims_round_2",
    "play_ins_finals",
    "round_of_16",
    "quarter_finals",
    "semi_finals",
    "interconference_semi_finals",
    "finals",
];

/*
|--------------------------------------------------------------------------
| Safe round helpers
|--------------------------------------------------------------------------
*/

const getRoundSeries = (round) => {
    return season_playoffs.value?.playoffs?.[round]?.series ?? [];
};

const hasRoundSeries = (round) => {
    return getRoundSeries(round).length > 0;
};

/*
|--------------------------------------------------------------------------
| Create Playoff Schedule
|--------------------------------------------------------------------------
*/

const createPlayOffSchedule = async (round) => {
    try {
        let prev_round = round;

        let start_playoffs =
            season_info.value.seasons[0].start_playoffs;

        round = roundStatusFormatter(
            round,
            start_playoffs,
            is_play_ins.value
        );

        Swal.fire({
            title: "Simulating...",
            text:
                "Please wait while creating the schedule for " +
                roundNameFormatter(round),
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(
            route("create.schedule.playoff"),
            {
                season_id: form.seasons_id,
                round: round,
                prev_round: prev_round,
                start: start_playoffs,
            }
        );

        isHide.value = true;

        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);

        isHide.value = false;
        isAddModalOpen.value = false;

        Swal.close();

        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });
    } catch (error) {
        console.error(
            "Error creating playoff schedule:",
            error
        );

        Swal.close();

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "Failed to create playoff schedule.",
        });

        throw error;
    }
};

/*
|--------------------------------------------------------------------------
| Auto Create Playoff Schedule
|--------------------------------------------------------------------------
*/

const createPlayOffScheduleAuto = async (round) => {
    try {
        let prev_round = round;

        let start_playoffs =
            season_info.value.seasons[0].start_playoffs;

        round = roundStatusFormatter(
            round,
            start_playoffs,
            is_play_ins.value
        );

        Swal.fire({
            title: "Simulating...",
            text:
                "Please wait while creating the schedule for " +
                roundNameFormatter(round),
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(
            route("create.schedule.playoff"),
            {
                season_id: form.seasons_id,
                round: round,
                prev_round: prev_round,
                start: start_playoffs,
            }
        );

        isHide.value = true;

        await fetchSeasonInfo(form.seasons_id);
        await fetchSeasonPlayoffs(2);

        isHide.value = false;
        isAddModalOpen.value = false;

        Swal.close();

        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
        });

        return response.data.message;
    } catch (error) {
        console.error(
            "Error creating playoff schedule:",
            error
        );

        Swal.close();

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "Failed to create playoff schedule.",
        });

        throw error;
    }
};

/*
|--------------------------------------------------------------------------
| Fetch Season Info
|--------------------------------------------------------------------------
*/

const fetchSeasonInfo = async (id) => {
    try {
        form.seasons_id = id;

        const response = await axios.post(
            route("seasons.info"),
            {
                season_id: form.seasons_id,
            }
        );

        season_info.value = response.data;

        is_play_ins.value =
            response.data.is_play_ins ? 1 : 2;

        await fetchSeasonPlayoffs(is_play_ins.value);
    } catch (error) {
        console.error(
            "Error fetching season information:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "Failed to fetch season information.",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Fetch Playoffs
|--------------------------------------------------------------------------
*/

const fetchSeasonPlayoffs = async (type) => {
    try {
        let status =
            season_info.value.seasons[0].status;

        let start_playoffs =
            season_info.value.seasons[0].start_playoffs;

        const response = await axios.post(
            route("seasons.playoffs.series"),
            {
                season_id: form.seasons_id,
                type: type,
                status: status,
                start: start_playoffs,
            }
        );

        if (type === 2) {
            if (
                typeof season_playoffs.value?.playoffs !==
                    "object" ||
                season_playoffs.value.playoffs === null
            ) {
                season_playoffs.value.playoffs = {};
            }

            season_playoffs.value.playoffs = {
                ...season_playoffs.value.playoffs,
                ...response.data.playoffs,
            };
        } else {
            loading.value = true;

            season_playoffs.value = response.data;

            loading.value = false;
        }
    } catch (error) {
        loading.value = false;

        console.error(
            "Error fetching season playoffs:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "Failed to fetch playoff data.",
        });
    }
};

/*
|--------------------------------------------------------------------------
| Simulate Individual Game
|--------------------------------------------------------------------------
*/

const simulateGame = async (
    id,
    game_id,
    type,
    index,
    round
) => {
    try {
        isHide.value = true;
        isGameNewsModalOpen.value = true;

        activeIndex.value = index;

        Swal.fire({
            title: "Simulating...",
            text:
                "Please wait while the game is being simulated.",
            icon: "info",
            toast: true,
            position: "top",
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        const response = await axios.post(
            route("game.simulate.playoff.series"),
            {
                schedule_id: id,
            }
        );

        series.value = response.data.series;

        Swal.close();

        Swal.fire({
            icon: "success",
            title: "Success!",
            text: response.data.message,
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true,
        });

        isGameNewsModalOpen.value = false;
        isHide.value = false;
    } catch (error) {
        console.error(
            "Error simulating the game:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "Failed to simulate game.",
        });

        throw error;
    } finally {
        isHide.value = false;
        isGameNewsModalOpen.value = false;

        Swal.close();
    }
};

/*
|--------------------------------------------------------------------------
| Conference Styling
|--------------------------------------------------------------------------
*/

const getConferenceClass = (
    home_conference,
    away_conference
) => {
    const conferenceClasses = {
        NCR: "bg-blue-100 text-blue-500",
        Luzon: "bg-green-100 text-green-500",
        Visayas: "bg-yellow-100 text-yellow-500",
        Mindanao: "bg-red-100 text-red-500",
    };

    if (home_conference !== away_conference) {
        return "bg-orange-100 text-orange-500";
    }

    return (
        conferenceClasses[home_conference] ||
        "bg-gray-100 text-gray-500"
    );
};

/*
|--------------------------------------------------------------------------
| Simulate Full Playoffs
|--------------------------------------------------------------------------
*/

const simulateFullPlayoffs = async () => {
    try {
        isHide.value = true;
        loading.value = true;

        Swal.fire({
            title: "Simulating Playoffs...",
            text:
                "Please wait while the entire playoff is being simulated.",
            icon: "info",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        let currentRoundIndex = 0;
        let initialRound = "start";

        /*
        |--------------------------------------------------------------------------
        | Initialize Playoffs
        |--------------------------------------------------------------------------
        */

        if (
            season_info.value.seasons[0].status == 2
        ) {
            await createPlayOffScheduleAuto(
                initialRound
            );
        }

        while (
            currentRoundIndex <
            roundOrder.length
        ) {
            let roundName =
                roundOrder[currentRoundIndex];

            let start_playoffs =
                season_info.value.seasons[0]
                    .start_playoffs;

            let prevRound =
                roundStatusFormatter(
                    roundName,
                    start_playoffs,
                    is_play_ins.value
                );

            const isPrevRoundCompleted =
                season_playoffs.value.playoffs[
                    prevRound
                ]?.completed;

            const isCompleted =
                season_playoffs.value.playoffs[
                    roundName
                ]?.completed;

            if (isPrevRoundCompleted) {
                if (isCompleted) {
                    currentRoundIndex++;
                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Refresh latest data
            |--------------------------------------------------------------------------
            */

            await fetchSeasonInfo(
                form.seasons_id
            );

            await fetchSeasonPlayoffs(
                is_play_ins.value
            );

            /*
            |--------------------------------------------------------------------------
            | Create round if missing
            |--------------------------------------------------------------------------
            */

            if (
                !season_playoffs.value.playoffs[
                    roundName
                ] ||
                season_playoffs.value.playoffs[
                    roundName
                ]?.series?.length === 0
            ) {
                try {
                    const scheduleResponse =
                        await createPlayOffScheduleAuto(
                            roundName
                        );

                    if (
                        typeof scheduleResponse ===
                            "string" &&
                        scheduleResponse
                            .toLowerCase()
                            .includes(
                                "already created"
                            )
                    ) {
                        if (!isCompleted) {
                            await fetchSeasonPlayoffs(
                                is_play_ins.value
                            );
                        }
                    } else {
                        await fetchSeasonPlayoffs(
                            is_play_ins.value
                        );
                    }
                } catch (scheduleError) {
                    console.warn(
                        `Failed to create schedule for ${roundName}:`,
                        scheduleError
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Get pending games
            |--------------------------------------------------------------------------
            */

            const matches =
                season_playoffs.value.pending ||
                [];

            if (matches.length === 0) {
                const nextRoundResponse =
                    await createPlayOffScheduleAuto(
                        roundName
                    );

                if (
                    typeof nextRoundResponse ===
                        "string" &&
                    nextRoundResponse
                        .toLowerCase()
                        .includes(
                            "already created"
                        )
                ) {
                    console.log(
                        `Next round after ${roundName} already scheduled.`
                    );
                } else {
                    currentRoundIndex++;
                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Simulate games
            |--------------------------------------------------------------------------
            */

            for (
                let index = 0;
                index < matches.length;
                index++
            ) {
                const match = matches[index];

                try {
                    await simulateGame(
                        match.id,
                        match.game_id,
                        2,
                        index,
                        roundName
                    );

                    await fetchSeasonPlayoffs(
                        is_play_ins.value
                    );
                } catch (error) {
                    console.error(
                        `Error simulating game ${match.id} in ${roundName}:`,
                        error
                    );

                    await fetchSeasonPlayoffs(
                        is_play_ins.value
                    );

                    Swal.fire({
                        icon: "error",
                        title: "Game Simulation Error",
                        text:
                            error.response?.data
                                ?.message ||
                            `Failed to simulate game ${match.id}. Retrying...`,
                    });

                    index = matches.length;

                    currentRoundIndex--;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Advance round
            |--------------------------------------------------------------------------
            */

            if (roundName !== "finals") {
                try {
                    const nextRoundResponse =
                        await createPlayOffScheduleAuto(
                            roundName
                        );

                    if (
                        typeof nextRoundResponse ===
                            "string" &&
                        nextRoundResponse
                            .toLowerCase()
                            .includes(
                                "already created"
                            )
                    ) {
                        console.log(
                            `Next round after ${roundName} already scheduled.`
                        );
                    }
                } catch (advanceError) {
                    console.warn(
                        `Could not advance from ${roundName}:`,
                        advanceError
                    );
                }
            }

            currentRoundIndex++;
        }

        /*
        |--------------------------------------------------------------------------
        | Final Refresh
        |--------------------------------------------------------------------------
        */

        await fetchSeasonInfo(
            form.seasons_id
        );

        await fetchSeasonPlayoffs(2);

        Swal.close();

        Swal.fire({
            icon: "success",
            title: "Playoffs Completed!",
            text:
                "The entire playoff simulation has finished successfully.",
        });
    } catch (error) {
        console.error(
            "Error simulating full playoffs:",
            error
        );

        Swal.close();

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error.response?.data?.message ||
                "Failed to simulate playoffs.",
        });
    } finally {
        isHide.value = false;
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Watch Season
|--------------------------------------------------------------------------
*/

watch(
    () => props.season_id,
    async (n, o) => {
        if (n !== o) {
            await fetchSeasonInfo(n);
        }
    }
);

/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchSeasonInfo(props.season_id);
});
</script>