<template>
    <!-- Player Profile and Playoff Performance in One Row -->
    <div class="flex flex-col md:flex-row gap-6 p-4 rounded" 
    :style="{
    background:
        main_performance.player_details.primary_color && main_performance.player_details.secondary_color
        ? `linear-gradient(180deg, #${main_performance.player_details.primary_color}, #${main_performance.player_details.secondary_color})`
        : '#f9fafb',
    }"
    v-if="!isLoading && main_performance.player_details">
        <!-- Player Details Section -->
        <div
            class="player-details mb-6 flex-2"
            v-if="main_performance.player_details"
        >
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 flex items-center"
            >
                <i class="fa fa-user text-blue-500 mr-2"></i>
                Player Details
                <small v-if="main_performance.player_details.injury_recovery_game_count > 0">
                    <span class="inline-flex items-center text-nowrap justify-center animate-pulse ml-3 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-400 text-white">
                        <i class="fa fa-medkit mr-1"></i> 
                         {{ Math.round(main_performance.player_details.injury_recovery_game_count) ?? '-' }} {{ Math.round(main_performance.player_details.injury_recovery_game_count) === 1 ? 'Day' : 'Days' }}
                    </span>
                </small>
            </h3>
            <div class="ml-4">
                <p class="first-letter:uppercase">
                    <strong>Player ID:</strong> #{{
                        main_performance.player_details.player_id ?? "-"
                    }}
                </p>
                <p class="text-nowrap">
                    <strong>Name:</strong>
                    <span>
                        {{ main_performance.player_details.player_name ?? "-" }} ,{{
                        main_performance.player_details.age ?? "N/A"
                        }}
                        <sup
                            :class="
                                main_performance.player_details.age >=
                                main_performance.player_details.retirement_age
                                    ? 'p-1 rounded-full text-red-500'
                                    :  'p-1 rounded-full text-yellow-500'
                            "
                        >
                            {{
                                main_performance.player_details.age >=
                                main_performance.player_details.retirement_age
                                    ? "R"
                                    : "A"
                            }}
                        </sup>
                    </span>
                </p>
                <p>
                    <strong>Team:</strong>
                    {{ main_performance.player_details.team_name ?? "-" }}
                </p>
                <p class="text-wrap">
                    <strong>Country:</strong>
                    {{ main_performance.player_details.country ?? "-" }}
                </p>
            </div>
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-bar-chart text-orange-500 mr-2"></i>
                Draft Details
            </h3>
            <div class="ml-4">
                <p>
                    <strong>Draft:</strong>
                    {{ main_performance.player_details.draft_status ?? "-" }}
                    {{
                        main_performance.player_details.drafted_team
                            ? "(" +
                            main_performance.player_details.drafted_team +
                            ")"
                            : ""
                    }}
                </p>
                <p>
                    <strong>Draft Class:</strong>
                    {{ main_performance.player_details.draft_class ?? "-" }}
                </p>
                <p>
                    <strong>Role:</strong>
                    <label
                        :class="roleBadgeClass(main_performance.player_details.role)"
                    >
                        {{ main_performance.player_details.role }}
                    </label>
                </p>
                <p>
                    <strong>Position:</strong>
                    {{ main_performance.player_details.position ?? "-" }}
                </p>
                <p>
                    <strong>Archetype:</strong>
                    <a class="uppercase">&nbsp;{{ main_performance.player_details.archetype.replaceAll('_',' ') ?? "-" }}</a>
                </p>
            </div>
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-list text-red-500 mr-2"></i>
                League Experience
            </h3>
            <div class="ml-4">
                <p>
                    <strong>Season Exp:</strong>
                    <span
                        class="text-xs"
                        :class="playerExpStatusClass(main_performance.season_count)"
                    >
                        {{
                            playerExpStatusText(
                                main_performance.season_count ??
                                    main_performance.season_count
                            )
                        }}
                        ({{ main_performance.season_count ?? 0 }})
                    </span>
                </p>
                <p>
                    <strong>Playoff Exp:</strong>
                    <span
                        class="text-xs"
                        :class="
                            playerExpStatusClass(main_performance.playoff_count)
                        "
                    >
                        {{
                            playerExpStatusText(
                                main_performance.playoff_count ??
                                    main_performance.playoff_count
                            )
                        }}
                        ({{ main_performance.playoff_count ?? 0 }})
                    </span>
                </p>
            </div>
           
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-file-contract text-green-500 mr-2"></i>
                Contracts
            </h3>
            <div class="ml-4">
                <p>
                    <strong>Contract Left:</strong>
                    {{
                        main_performance.player_details.contract_years > 0 ? main_performance.player_details.contract_years +
                            " years left" : "unsigned"
                    }}
                </p>
            </div>
             <div class="ml-4">
                <p>
                    <strong>Hardship Contract Left:</strong>
                    {{
                        main_performance.player_details.hardship_contract > 0 ? main_performance.player_details.hardship_contract +
                            " games left" : "none"
                    }}
                </p>
            </div> 
        </div>

        <!-- Playoff Performance Section -->
        <div class="playoff-performance mb-6 flex-2">
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 flex items-center"
            >
                <i class="fa fa-network-wired text-purple-500 mr-2"></i>
                Playoff
            </h3>
            <div v-if="main_performance.playoff_performance" class="ml-4 text-sm text-nowrap">
                <p>
                    <strong>Conf. Playins:</strong>
                    {{ main_performance.playoff_performance.play_ins_elims_round_1_appearances + main_performance.playoff_performance.play_ins_elims_round_2_appearances + main_performance.playoff_performance.play_ins_finals_appearances  ?? 0 }}
                </p>
                <p>
                    <strong>Conf. Quarter Finals:</strong>
                    {{ main_performance.playoff_performance.round_of_16_appearances ?? 0 }}
                </p>
                <p>
                    <strong>Conf. Semi Finals:</strong>
                    {{
                        main_performance.playoff_performance.quarter_finals_appearances ?? 0
                    }}
                </p>
                <p>
                    <strong>Conf. Finals:</strong>
                    {{ main_performance.playoff_performance.semi_finals_appearances ?? 0 }}
                </p>
                <p>
                    <strong>The Big 4:</strong>
                    {{
                        main_performance.playoff_performance
                            .interconference_semi_finals_appearances ?? 0
                    }}
                </p>
                <p>
                    <strong>The Finals:</strong>
                    {{ main_performance.playoff_performance.finals_appearances ?? 0 }}
                </p>
                <p>
                    <strong>Finals MVP:</strong>
                    {{ main_performance.mvp_count ?? 0 }}
                </p>
            </div>
            <div v-else class="ml-4">
                <p>No playoff performance data available.</p>
            </div>
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-chart-line text-purple-500 mr-2"></i>
                Career Highs
            </h3>
            <div v-if="main_performance.career_highs" class="ml-4">
                <p>
                    <strong>Points:</strong>
                    {{
                        main_performance.career_highs.career_high_points ??
                        "N/A"
                    }}
                </p>
                <p>
                    <strong>Rebounds:</strong>
                    {{
                        main_performance.career_highs.career_high_rebounds ??
                        "N/A"
                    }}
                </p>
                <p>
                    <strong>Assists:</strong>
                    {{
                        main_performance.career_highs.career_high_assists ??
                        "N/A"
                    }}
                </p>
                <p>
                    <strong>Steals:</strong>
                    {{
                        main_performance.career_highs.career_high_steals ??
                        "N/A"
                    }}
                </p>
                <p>
                    <strong>Blocks:</strong>
                    {{
                        main_performance.career_highs.career_high_blocks ??
                        "N/A"
                    }}
                </p>
                <p>
                    <strong>Turnovers:</strong>
                    {{
                        main_performance.career_highs.career_high_turnovers ??
                        "N/A"
                    }}
                </p>
                <p>
                    <strong>Fouls:</strong>
                    {{
                        main_performance.career_highs.career_high_fouls ?? "N/A"
                    }}
                </p>
            </div>
            <div v-else class="ml-4">
                <p>No career highs data available.</p>
            </div>
             <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-users text-red-500 mr-2"></i>
                Player Projection
            </h3>
            <div class="ml-4 text-wrap" v-if="main_performance.player_comparison && main_performance.player_comparison.length > 0">
                <p>
                    <strong  class="text-blue-500">Best Projection</strong>
                    {{
                        main_performance.player_comparison[0]?.best_projection_player_name ?? "N/A"
                    }}
                </p>
                <p>
                    <strong>Rating:</strong>
                    {{
                        main_performance.player_comparison[0]?.best_projection_rating ?? "N/A"
                    }}
                </p>
            </div>
             <div class="ml-4 mt-4 text-wrap" v-if="main_performance.player_comparison && main_performance.player_comparison.length > 0">
                <p>
                    <strong class="text-red-500">Worst Projection</strong>
                    {{
                        main_performance.player_comparison[0]?.worst_projection_player_name ?? "N/A"
                    }}
                </p>
                <p>
                    <strong>Rating:</strong>
                    {{
                        main_performance.player_comparison[0]?.worst_projection_rating ?? "N/A"
                    }}
                </p>
            </div>
            <div v-else class="ml-4">
                <p>No player comparison data available.</p>
                
            </div>
        </div>

        <div class="awards mb-6 flex-1 text-nowrap">
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 flex items-center"
            >
                <i class="fa fa-trophy text-yellow-500 mr-2"></i>
                Championships ({{  main_performance.national_championships?.length + main_performance.conference_championships?.length + main_performance.national_overall_champions?.length + main_performance.conference_overall_champions?.length }})
            </h3>
            <div v-if="main_performance.national_championships?.length > 0" class="ml-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">
                    National Championships
                    {{
                        main_performance.national_championships?.length > 0
                            ? "(" + main_performance.national_championships?.length + ")"
                            : ""
                    }}
                </h4>
                <div
                    v-for="(season, index) in main_performance.national_championships"
                    :key="index"
                    class="flex items-center mb-2"
                >
                    <i class="fa fa-trophy text-yellow-500 mr-2"></i>
                    <p class="text-xs">
                        {{ season.season_name }} ({{
                            season.championship_team
                        }})
                    </p>
                </div>
            </div>
            <div v-if="main_performance.conference_championships?.length > 0" class="ml-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">
                    Conf. Championships
                    {{
                        main_performance.conference_championships?.length > 0
                            ? "(" +
                            main_performance.conference_championships
                                ?.length +
                            ")"
                            : ""
                    }}
                </h4>
                <div
                    v-for="(
                        season, index
                    ) in main_performance.conference_championships"
                    :key="index"
                    class="flex items-center mb-2"
                >
                    <i class="fa fa-trophy text-yellow-500 mr-2"></i>
                    <p class="text-xs">
                        {{ season.season_name }} ({{
                            season.championship_team
                        }})
                    </p>
                </div>
            </div>
            <div v-if="main_performance.national_overall_champions?.length > 0" class="ml-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">
                    Nationals Rank #1
                    {{
                        main_performance.national_overall_champions?.length > 0
                            ? "(" + main_performance.national_overall_champions?.length + ")"
                            : ""
                    }}
                </h4>
                <div
                    v-for="(season, index) in main_performance.national_overall_champions"
                    :key="index"
                    class="flex items-center mb-2"
                >
                    <i class="fa fa-trophy text-yellow-500 mr-2"></i>
                    <p class="text-xs">
                        {{ season.season_name }} ({{
                            season.team_name
                        }})
                    </p>
                </div>
            </div>
            <div v-if="main_performance.conference_overall_champions?.length > 0" class="ml-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">
                    Conference Rank #1
                    {{
                        main_performance.conference_overall_champions?.length > 0
                            ? "(" + main_performance.conference_overall_champions?.length + ")"
                            : ""
                    }}
                </h4>
                <div
                    v-for="(season, index) in main_performance.conference_overall_champions"
                    :key="index"
                    class="flex items-center mb-2"
                >
                    <i class="fa fa-trophy text-yellow-500 mr-2"></i>
                    <p class="text-xs">
                        {{ season.season_name }} ({{
                            season.team_name
                        }})
                    </p>
                </div>
            </div>
            <div v-else class="text-sm text-red-500 ml-4">
                <p>No championship available.</p>
            </div>
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-star text-yellow-500 mr-2"></i>
                MVP
            </h3>
            <div v-if="main_performance.mvp_seasons?.length > 0" class="ml-4">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">
                    MVP Seasons
                    {{
                        main_performance.mvp_seasons?.length > 0
                            ? "(" + main_performance.mvp_seasons?.length + ")"
                            : ""
                    }}
                </h4>
                <div
                    v-for="(season, index) in main_performance.mvp_seasons"
                    :key="index"
                    class="flex items-center mb-2"
                >
                    <i class="fa fa-star text-yellow-500 mr-2"></i>
                    <p class="text-sm">{{ season }}</p>
                </div>
            </div>
            <div v-else class="text-sm text-red-500 ml-4">
                <p>No MVP data available.</p>
            </div>
            <h3
                class="text-md font-semibold text-yellow-500 mb-2 mt-4 flex items-center"
            >
                <i class="fa fa-medal text-yellow-500 mr-2"></i>
                Awards
            </h3>
            <div v-if="main_performance.awards?.length > 0" class="ml-4 max-h-[35vh] overflow-y-scroll">
                <h4 class="text-sm font-semibold text-gray-600 mb-2">
                    Awards
                    {{
                        main_performance.awards?.length > 0
                            ? "(" + main_performance.awards?.length + ")"
                            : ""
                    }}
                </h4>
                <div
                    v-for="(season, index) in main_performance.awards"
                    :title="season.team_name"
                    :key="index"
                    class="flex text-nowrap items-center mb-2"
                >
                    <i class="fa fa-medal text-yellow-500 mr-2"></i>
                    <p class="text-xs">
                        {{ season.award_name }} ({{ season.season_name }})
                    </p>
                </div>
            </div>
            <div v-else class="text-sm text-red-500 ml-4">
                <p>No awards data available.</p>
            </div>
        </div>
        <div class="career-highs flex-1" v-if="main_performance.player_details">
            <PlayerRadarChart v-if="main_performance.player_details" :key="main_performance.player_details.player_id" :playerRatings="main_performance.player_details" />
            <LatestPlayerGameLogs
                :key="main_performance.player_details.player_id"
                :player_id="main_performance.player_details.player_id"
                :season_id="main_performance.current_season_id"
                :full="false"
            />
        </div>
    </div>
    <div class="flex" v-else>
        <div class="flex items-center justify-center w-full h-32">
            <div class="block text-center">
                <i class="fa fa-spinner fa-spin text-blue-500 text-4xl"></i>
                <p>Loading player data...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import {
    roleBadgeClass,
    playerExpStatusClass,
    playerExpStatusText,
} from "@/Utility/Formatter";
import PlayerRadarChart from './PlayerRadarChart.vue';
import LatestPlayerGameLogs from "./LatestPlayerGameLogs.vue";
const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
    season_logs: {
        type: Number,
        required: true,
    },
    playoff_logs: {
        type: Number,
        required: true,
    },
});
const main_performance = ref([]);
const isLoading = ref(false);
const player_id = ref(props.player_id);

// Watch for changes in player_id
// Fetch data on component mount
onMounted(() => {
    fetchPlayerMainPerformance();
});

const fetchPlayerMainPerformance = async () => {
    try {
        isLoading.value = true;
        const response = await axios.post(route("players.main.performance"), {
            player_id: player_id.value,
        });
        main_performance.value = response.data;
        isLoading.value = false;
    } catch (error) {
        isLoading.value = false;
        console.error("Error fetching player playoff performance:", error);
    }
};
</script>

<style scoped>
.table {
    font-size: 0.75rem; /* Smaller text size */
}

.table th,
.table td {
    padding: 0.5rem; /* Smaller padding */
}
</style>
