<template>
    <div
        v-if="!isLoading && main_performance?.coach_details"
        class="w-full min-w-0 overflow-hidden rounded-xl border border-white/[0.08] bg-[#050505] text-white"
    >
        <!-- =========================================================
             COACH PROFILE HEADER
        ========================================================== -->
        <div
            class="relative overflow-hidden border-b border-white/[0.08]"
            :style="{ background: profileBackground }"
        >
            <!-- Decorative glow -->
            <div
                class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full blur-3xl"
                :style="{
                    backgroundColor: `#${normalizeColor(
                        main_performance.coach_details.primary_color
                    )}30`,
                }"
            ></div>

            <div
                class="pointer-events-none absolute -bottom-16 left-1/3 h-64 w-64 rounded-full blur-3xl"
                :style="{
                    backgroundColor: `#${normalizeColor(
                        main_performance.coach_details.secondary_color
                    )}20`,
                }"
            ></div>
            <div class="absolute inset-0 bg-black/40"></div>
            <div
                :style="{ color: `#${normalizeColor(main_performance.coach_details.secondary_color)}20` }"
                class="pointer-events-none absolute -right-4 -top-8 select-none text-[140px] font-black leading-none tracking-tighter text-white/[0.025] md:text-[190px]"
            >
                {{ main_performance.coach_details.team_name ?? "Free Agent" }}
            </div>

            <div class="relative p-4 sm:p-5">
                <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                    <!-- Coach Icon -->
                    <div
                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-black/40 shadow-lg"
                    >
                        <i class="fa fa-user-tie text-2xl text-yellow-400"></i>
                    </div>

                    <!-- Identity -->
                    <div class="min-w-0 flex-1">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <span
                                class="text-[9px] font-black uppercase tracking-[0.2em] text-yellow-400"
                            >
                                Coach Profile
                            </span>

                            <span
                                class="rounded-full border px-2 py-0.5 text-[8px] font-black uppercase tracking-wider"
                                :class="
                                    Number(
                                        main_performance.coach_details.is_active
                                    ) === 0
                                        ? 'border-red-500/20 bg-red-500/10 text-red-400'
                                        : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                "
                            >
                                {{
                                    Number(
                                        main_performance.coach_details.is_active
                                    ) === 0
                                        ? "Retired"
                                        : "Active"
                                }}
                            </span>
                        </div>

                        <h2
                            class="truncate text-xl font-black tracking-tight text-white sm:text-2xl"
                        >
                            {{
                                main_performance.coach_details.name ??
                                "Unknown Coach"
                            }}
                        </h2>

                        <div
                            class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-gray-400"
                        >
                            <span>
                                #{{ main_performance.coach_details.id ?? "-" }}
                            </span>

                            <span class="text-gray-700">•</span>

                            <span>
                                Age
                                {{
                                    main_performance.coach_details.age ?? "N/A"
                                }}
                            </span>

                            <span class="text-gray-700">•</span>

                            <span class="capitalize">
                                {{
                                    main_performance.coach_details
                                        .coaching_style ?? "-"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Win Percentage -->
                    <div
                        class="shrink-0 rounded-xl border border-yellow-500/15 bg-black/40 px-5 py-3 text-center backdrop-blur-sm"
                    >
                        <div
                            class="text-2xl font-black leading-none text-yellow-400"
                        >
                            {{
                                main_performance.coach_details
                                    .winning_percentage ?? 0
                            }}%
                        </div>

                        <div
                            class="mt-1 text-[8px] font-black uppercase tracking-[0.18em] text-gray-500"
                        >
                            Career Win Rate
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================
             PROFILE CONTENT
        ========================================================== -->
        <div class="min-w-0 space-y-3 p-3 sm:p-4">
            <!-- =====================================================
                 BASIC INFORMATION
            ====================================================== -->
            <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-3">
                <!-- Coach Details -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="section-icon bg-blue-500/10 text-blue-400">
                            <i class="fa fa-user"></i>
                        </div>

                        <div>
                            <h3>Coach Details</h3>
                            <p>Coaching information</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="detail-row">
                            <span>Coach ID</span>
                            <strong>
                                #{{ main_performance.coach_details.id ?? "-" }}
                            </strong>
                        </div>

                        <div class="detail-row">
                            <span>Name</span>
                            <strong class="truncate">
                                {{
                                    main_performance.coach_details.name ?? "-"
                                }}
                            </strong>
                        </div>

                        <div class="detail-row">
                            <span>Age</span>
                            <strong>
                                {{
                                    main_performance.coach_details.age ?? "N/A"
                                }}
                            </strong>
                        </div>

                        <div class="detail-row">
                            <span>Team</span>
                            <strong class="truncate">
                                {{
                                    main_performance.coach_details.team_name ??
                                    "-"
                                }}
                            </strong>
                        </div>

                        <div class="detail-row">
                            <span>Style</span>
                            <strong
                                class="max-w-[55%] truncate capitalize text-purple-300"
                            >
                                {{
                                    main_performance.coach_details
                                        .coaching_style ?? "-"
                                }}
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Career -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div
                            class="section-icon bg-emerald-500/10 text-emerald-400"
                        >
                            <i class="fa fa-chart-line"></i>
                        </div>

                        <div>
                            <h3>Career</h3>
                            <p>Overall coaching record</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <div class="mini-stat">
                            <div
                                class="mb-2 text-emerald-400"
                            >
                                <i class="fa fa-check"></i>
                            </div>

                            <strong>
                                {{
                                    main_performance.coach_details
                                        .career_wins ?? 0
                                }}
                            </strong>

                            <span>Wins</span>
                        </div>

                        <div class="mini-stat">
                            <div class="mb-2 text-red-400">
                                <i class="fa fa-xmark"></i>
                            </div>

                            <strong>
                                {{
                                    main_performance.coach_details
                                        .career_losses ?? 0
                                }}
                            </strong>

                            <span>Losses</span>
                        </div>

                        <div class="mini-stat">
                            <div class="mb-2 text-yellow-400">
                                <i class="fa fa-percent"></i>
                            </div>

                            <strong>
                                {{
                                    main_performance.coach_details
                                        .winning_percentage ?? 0
                                }}%
                            </strong>

                            <span>Win Rate</span>
                        </div>
                    </div>

                    <!-- Contract -->
                    <div class="mt-4 border-t border-white/[0.05] pt-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div
                                    class="text-[8px] font-black uppercase tracking-widest text-gray-600"
                                >
                                    Contract
                                </div>

                                <div class="mt-1 text-xs font-bold text-gray-300">
                                    {{
                                        main_performance.coach_details
                                            .contract_years > 0
                                            ? `${main_performance.coach_details.contract_years} ${
                                                  main_performance.coach_details
                                                      .contract_years == 1
                                                      ? "year"
                                                      : "years"
                                              } left`
                                            : "Unsigned"
                                    }}
                                </div>
                            </div>

                            <i
                                class="fa fa-file-contract"
                                :class="
                                    main_performance.coach_details
                                        .contract_years > 0
                                        ? 'text-emerald-400'
                                        : 'text-red-400'
                                "
                            ></i>
                        </div>
                    </div>
                </div>

                <!-- Experience -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div
                            class="section-icon bg-purple-500/10 text-purple-400"
                        >
                            <i class="fa fa-medal"></i>
                        </div>

                        <div>
                            <h3>League Experience</h3>
                            <p>Coaching experience</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="experience-row">
                            <div>
                                <span>Regular Seasons</span>

                                <small
                                    :class="
                                        playerExpStatusClass(
                                            main_performance.season_count ?? 0
                                        )
                                    "
                                >
                                    {{
                                        playerExpStatusText(
                                            main_performance.season_count ?? 0
                                        )
                                    }}
                                </small>
                            </div>

                            <strong>
                                {{ main_performance.season_count ?? 0 }}
                            </strong>
                        </div>

                        <div class="experience-row">
                            <div>
                                <span>Playoff Seasons</span>

                                <small
                                    :class="
                                        playerExpStatusClass(
                                            main_performance.playoff_count ?? 0
                                        )
                                    "
                                >
                                    {{
                                        playerExpStatusText(
                                            main_performance.playoff_count ?? 0
                                        )
                                    }}
                                </small>
                            </div>

                            <strong>
                                {{ main_performance.playoff_count ?? 0 }}
                            </strong>
                        </div>

                        <!-- Playoff -->
                        <div
                            class="border-t border-white/[0.05] pt-3"
                        >
                            <div
                                class="mb-2 flex items-center gap-2"
                            >
                                <i
                                    class="fa fa-basketball text-purple-400"
                                ></i>

                                <span
                                    class="text-[9px] font-black uppercase tracking-widest text-gray-500"
                                >
                                    Playoff Performance
                                </span>
                            </div>

                            <div
                                v-if="
                                    main_performance.playoff_performance
                                "
                                class="grid grid-cols-2 gap-2"
                            >
                                <div class="playoff-stat">
                                    <strong>
                                        {{
                                            main_performance
                                                .playoff_performance
                                                .champion_count ?? 0
                                        }}
                                    </strong>

                                    <span>Champions</span>
                                </div>

                                <div class="playoff-stat">
                                    <strong>
                                        {{
                                            main_performance
                                                .playoff_performance
                                                .playoff_count ?? 0
                                        }}
                                    </strong>

                                    <span>Playoffs</span>
                                </div>
                            </div>

                            <div
                                v-else
                                class="text-[10px] text-gray-600"
                            >
                                No playoff performance available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 CHAMPIONSHIPS + RADAR
            ====================================================== -->
            <div
                class="grid min-w-0 grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.75fr)]"
            >
                <!-- Championships -->
                <div class="profile-card min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <div class="profile-card-header">
                            <div
                                class="section-icon bg-yellow-500/10 text-yellow-400"
                            >
                                <i class="fa fa-trophy"></i>
                            </div>

                            <div>
                                <h3>Championships</h3>
                                <p>Career achievements</p>
                            </div>
                        </div>

                        <div
                            class="flex h-8 min-w-8 items-center justify-center rounded-lg border border-yellow-500/15 bg-yellow-500/[0.05] px-2 text-xs font-black text-yellow-400"
                        >
                            {{ championshipCount }}
                        </div>
                    </div>

                    <div
                        v-if="championshipCount"
                        class="mt-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <!-- National -->
                        <div
                            v-if="
                                main_performance.national_championships
                                    ?.length
                            "
                        >
                            <div class="achievement-heading">
                                <span class="bg-yellow-400"></span>
                                National Championships
                                <b>
                                    {{
                                        main_performance
                                            .national_championships.length
                                    }}
                                </b>
                            </div>

                            <div class="mt-2 space-y-1.5">
                                <div
                                    v-for="(
                                        season, index
                                    ) in main_performance.national_championships"
                                    :key="`national-${index}`"
                                    class="achievement"
                                >
                                    <div
                                        class="achievement-icon bg-yellow-500/10 text-yellow-400"
                                    >
                                        <i class="fa fa-trophy"></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="truncate text-[11px] font-bold text-gray-200"
                                        >
                                            {{
                                                season.season_name ?? "-"
                                            }}
                                        </div>

                                        <div
                                            class="truncate text-[9px] text-gray-600"
                                        >
                                            {{
                                                season.championship_team ?? "-"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conference -->
                        <div
                            v-if="
                                main_performance.conference_championships
                                    ?.length
                            "
                        >
                            <div class="achievement-heading">
                                <span class="bg-blue-400"></span>
                                Conference Championships
                                <b>
                                    {{
                                        main_performance
                                            .conference_championships.length
                                    }}
                                </b>
                            </div>

                            <div class="mt-2 space-y-1.5">
                                <div
                                    v-for="(
                                        season, index
                                    ) in main_performance.conference_championships"
                                    :key="`conference-${index}`"
                                    class="achievement"
                                >
                                    <div
                                        class="achievement-icon bg-blue-500/10 text-blue-400"
                                    >
                                        <i class="fa fa-trophy"></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="truncate text-[11px] font-bold text-gray-200"
                                        >
                                            {{
                                                season.season_name ?? "-"
                                            }}
                                        </div>

                                        <div
                                            class="truncate text-[9px] text-gray-600"
                                        >
                                            {{
                                                season.championship_team ?? "-"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- National Rank -->
                        <div
                            v-if="
                                main_performance.national_overall_champions
                                    ?.length
                            "
                        >
                            <div class="achievement-heading">
                                <span class="bg-emerald-400"></span>
                                National Rank #1
                                <b>
                                    {{
                                        main_performance
                                            .national_overall_champions
                                            .length
                                    }}
                                </b>
                            </div>

                            <div class="mt-2 space-y-1.5">
                                <div
                                    v-for="(
                                        season, index
                                    ) in main_performance.national_overall_champions"
                                    :key="`national-overall-${index}`"
                                    class="achievement"
                                >
                                    <div
                                        class="achievement-icon bg-emerald-500/10 text-emerald-400"
                                    >
                                        <i class="fa fa-medal"></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="truncate text-[11px] font-bold text-gray-200"
                                        >
                                            {{
                                                season.season_name ?? "-"
                                            }}
                                        </div>

                                        <div
                                            class="truncate text-[9px] text-gray-600"
                                        >
                                            {{
                                                season.team_name ?? "-"
                                            }}
                                        </div>
                                    </div>

                                    <span class="rank-badge text-emerald-400">
                                        #1
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Conference Rank -->
                        <div
                            v-if="
                                main_performance
                                    .conference_overall_champions?.length
                            "
                        >
                            <div class="achievement-heading">
                                <span class="bg-purple-400"></span>
                                Conference Rank #1
                                <b>
                                    {{
                                        main_performance
                                            .conference_overall_champions
                                            .length
                                    }}
                                </b>
                            </div>

                            <div class="mt-2 space-y-1.5">
                                <div
                                    v-for="(
                                        season, index
                                    ) in main_performance.conference_overall_champions"
                                    :key="`conference-overall-${index}`"
                                    class="achievement"
                                >
                                    <div
                                        class="achievement-icon bg-purple-500/10 text-purple-400"
                                    >
                                        <i class="fa fa-medal"></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="truncate text-[11px] font-bold text-gray-200"
                                        >
                                            {{
                                                season.season_name ?? "-"
                                            }}
                                        </div>

                                        <div
                                            class="truncate text-[9px] text-gray-600"
                                        >
                                            {{
                                                season.team_name ?? "-"
                                            }}
                                        </div>
                                    </div>

                                    <span class="rank-badge text-purple-400">
                                        #1
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mt-4 flex min-h-[180px] flex-col items-center justify-center rounded-xl border border-dashed border-white/[0.07] bg-white/[0.015] text-center"
                    >
                        <i
                            class="fa fa-trophy text-2xl text-gray-700"
                        ></i>

                        <p class="mt-2 text-[10px] text-gray-600">
                            No championship achievements available.
                        </p>
                    </div>
                </div>

                <!-- =================================================
                     RADAR CHART
                ================================================== -->
                <div class="profile-card min-w-0">
                    <div class="profile-card-header">
                        <div
                            class="section-icon bg-rose-500/10 text-rose-400"
                        >
                            <i class="fa fa-chart-pie"></i>
                        </div>

                        <div>
                            <h3>Coach Attributes</h3>
                            <p>Coaching profile</p>
                        </div>
                    </div>

                    <div
                        class="mt-3 flex min-h-[300px] min-w-0 items-center justify-center overflow-hidden rounded-xl border border-white/[0.05] bg-black/30 p-2 sm:min-h-[340px] sm:p-3"
                    >
                        <div class="w-full min-w-0">
                            <CoachRadarChart
                                v-if="main_performance.coach_details"
                                :key="main_performance.coach_details.id"
                                :coachDetails="
                                    main_performance.coach_details
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================================================
         LOADING
    ============================================================== -->
    <div
        v-else
        class="w-full overflow-hidden rounded-xl border border-white/[0.08] bg-[#050505] text-white"
    >
        <div class="animate-pulse p-4 sm:p-5">
            <div class="flex items-center gap-4">
                <div
                    class="h-16 w-16 shrink-0 rounded-xl bg-white/[0.05]"
                ></div>

                <div class="min-w-0 flex-1">
                    <div
                        class="h-2 w-24 rounded bg-white/[0.05]"
                    ></div>

                    <div
                        class="mt-2 h-6 w-48 max-w-full rounded bg-white/[0.06]"
                    ></div>

                    <div
                        class="mt-2 h-2 w-64 max-w-full rounded bg-white/[0.04]"
                    ></div>
                </div>
            </div>
        </div>

        <div
            class="flex items-center justify-center gap-2 border-t border-white/[0.06] py-5"
        >
            <i
                class="fa fa-spinner fa-spin text-sm text-yellow-500"
            ></i>

            <span class="text-[10px] font-medium text-gray-600">
                Loading coach data...
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";

import {
    playerExpStatusClass,
    playerExpStatusText,
} from "@/Utility/Formatter";

import CoachRadarChart from "./CoachRadarChart.vue";

const props = defineProps({
    coach_id: {
        type: Number,
        required: true,
    },
});

const main_performance = ref(null);
const isLoading = ref(false);

const normalizeColor = (color) => {
    if (!color) return "111111";

    const normalized = String(color)
        .replace("#", "")
        .replace(/[^a-fA-F0-9]/g, "")
        .slice(0, 6);

    return normalized || "111111";
};

const profileBackground = computed(() => {
    const primary = normalizeColor(
        main_performance.value?.coach_details?.primary_color
    );

    const secondary = normalizeColor(
        main_performance.value?.coach_details?.secondary_color
    );

    return `linear-gradient(
        120deg,
        #${primary} 0%,
        #${secondary}cc 42%,
        #090909 100%
    )`;
});

const championshipCount = computed(() => {
    const data = main_performance.value;

    return (
        (data?.national_championships?.length ?? 0) +
        (data?.conference_championships?.length ?? 0) +
        (data?.national_overall_champions?.length ?? 0) +
        (data?.conference_overall_champions?.length ?? 0)
    );
});

const fetchPlayerMainPerformance = async () => {
    try {
        isLoading.value = true;

        const response = await axios.post(
            route("coach.information"),
            {
                coach_id: props.coach_id,
            }
        );

        main_performance.value = response.data;
    } catch (error) {
        console.error(
            "Error fetching coach performance:",
            error
        );

        main_performance.value = null;
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchPlayerMainPerformance();
});
</script>

<style scoped>
.profile-card {
    min-width: 0;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.065);
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.018);
    padding: 0.9rem;
}

.profile-card-header {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.65rem;
}

.profile-card-header h3 {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 900;
    color: #f3f4f6;
}

.profile-card-header p {
    margin-top: 0.1rem;
    font-size: 0.58rem;
    color: #5f636b;
}

.section-icon {
    display: flex;
    height: 2rem;
    width: 2rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 0.6rem;
    font-size: 0.7rem;
}

.detail-row {
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.045);
    padding-bottom: 0.55rem;
}

.detail-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.detail-row span {
    flex-shrink: 0;
    font-size: 0.6rem;
    font-weight: 700;
    color: #626771;
}

.detail-row strong {
    min-width: 0;
    text-align: right;
    font-size: 0.65rem;
    font-weight: 800;
    color: #d1d5db;
}

.mini-stat {
    min-width: 0;
    border-radius: 0.7rem;
    background: rgba(255, 255, 255, 0.025);
    padding: 0.65rem 0.35rem;
    text-align: center;
}

.mini-stat strong {
    display: block;
    font-size: 1rem;
    font-weight: 900;
    color: #f3f4f6;
}

.mini-stat span {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.5rem;
    font-weight: 700;
    color: #5f636b;
}

.experience-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 0.7rem;
    background: rgba(255, 255, 255, 0.02);
    padding: 0.65rem 0.75rem;
}

.experience-row > div {
    min-width: 0;
}

.experience-row span {
    display: block;
    font-size: 0.62rem;
    font-weight: 700;
    color: #9ca3af;
}

.experience-row small {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.5rem;
    font-weight: 800;
}

.experience-row > strong {
    flex-shrink: 0;
    font-size: 1.1rem;
    font-weight: 900;
    color: #f3f4f6;
}

.playoff-stat {
    border-radius: 0.65rem;
    background: rgba(168, 85, 247, 0.05);
    padding: 0.55rem;
}

.playoff-stat strong {
    display: block;
    font-size: 1rem;
    font-weight: 900;
    color: #e9d5ff;
}

.playoff-stat span {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.5rem;
    font-weight: 700;
    color: #6b7280;
}

.achievement-heading {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.55rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #737780;
}

.achievement-heading > span {
    height: 5px;
    width: 5px;
    flex-shrink: 0;
    border-radius: 999px;
}

.achievement-heading b {
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    padding: 0.15rem 0.4rem;
    font-size: 0.45rem;
    color: #626771;
}

.achievement {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.55rem;
    border: 1px solid rgba(255, 255, 255, 0.045);
    border-radius: 0.65rem;
    background: rgba(255, 255, 255, 0.018);
    padding: 0.45rem 0.55rem;
}

.achievement-icon {
    display: flex;
    height: 1.8rem;
    width: 1.8rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    font-size: 0.6rem;
}

.rank-badge {
    flex-shrink: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.035);
    padding: 0.2rem 0.4rem;
    font-size: 0.5rem;
    font-weight: 900;
}

/* Dark scrollbars */
::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}

::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>