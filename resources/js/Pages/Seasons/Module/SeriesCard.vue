<template>
    <div 
        :style="{
            background: `
                linear-gradient(45deg, 
                    #${series?.home_team?.secondary_color} 0%, 
                    #${series?.home_team?.secondary_color} 50%, 
                    #${series?.home_team?.primary_color} 50%, 
                    #${series?.home_team?.primary_color} 100%
                ),
                linear-gradient(-45deg, 
                    #${series?.away_team?.primary_color} 0%, 
                    #${series?.away_team?.primary_color} 50%, 
                    #${series?.away_team?.secondary_color} 50%, 
                    #${series?.away_team?.secondary_color} 100%
                )`,
            backgroundSize: '50% 100%',
            backgroundPosition: 'left, right',
            backgroundRepeat: 'no-repeat'
        }"
        class="shadow-md rounded-md overflow-hidden"
    >
        <!-- Series score header -->
        <div class="px-5 py-2 bg-black bg-opacity-50 flex justify-between items-center">
            <span class="text-white font-bold">Best of {{ series.best_of }}</span>
            <span class="text-white font-bold">
               {{ series.series_lead }}
            </span>
        </div>
        
        <!-- Team scores -->
        <div class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center">
            <div>
                <h3>{{ series.home_team.wins }}</h3>
            </div>
            <div>
                <h3>{{ series.away_team.wins }}</h3>
            </div>
        </div>
        
        <!-- Team names -->
        <div class="px-1 py-1 flex justify-between items-center">
            <h3>
                <TeamDetails
                    :team_id="series.home_team.id" 
                    :key="series.home_team.id" 
                    :showButton="0"
                    :showInfo="false"
                    class="text-white text-md uppercase text-wrap text-left"
                    :current_conference_rank="series.home_team.conference_rank"
                    :text="`#${series.home_team.overall_rank ?? 'TBD'} ${series.home_team.name ?? 'TBD'}`"
                />
            </h3>
            <h3>
                <TeamDetails
                    :team_id="series.away_team.id" 
                    :key="series.away_team.id" 
                    :showButton="0"
                    :showInfo="false"
                    class="text-white text-md uppercase text-wrap text-right"
                    :current_conference_rank="series.away_team.conference_rank"
                    :text="`#${series.away_team.overall_rank ?? 'TBD'} ${series.away_team.name ?? 'TBD'}`" 
                />
            </h3>
        </div>
        
        <!-- Round name -->
        <div class="px-4 text-nowrap text-xs py-0 flex justify-center">
            <span class="px-2 text-xs text-white py-1 rounded bg-black bg-opacity-50">
                {{ roundNameFormatter(roundName) }}
            </span>
        </div>
        
        <!-- Conference info -->
        <div class="border-gray-200 flex justify-between mt-4 mb-4 bg-white">
            <div class="px-2 text-nowrap text-xs py-3">
                <span :class="getConferenceClass(series.home_team.conference, series.away_team.conference)"
                      class="px-2 shadow py-1 rounded">
                    {{ series.home_team.conference }} #{{ series.home_team.conference_rank }}
                    vs {{ series.away_team.conference }} #{{ series.away_team.conference_rank }}
                </span>
            </div>
            <div class="px-2 text-nowrap text-red-600 text-xs py-2 flex items-center">
                <button
                    class="text-white bg-orange-500 rounded-full px-2 py-1"
                    @click.prevent="$emit('compare', series.home_team.id, series.away_team.id)"
                >
                    Compare
                    <i class="fa fa-exchange-alt ml-1"></i>
                </button>
            </div>
        </div>
        
        <!-- Action buttons -->
        <div class="border-gray-200 flex justify-center">
            <button
                v-if="!isHide && !series.completed"
                @click.prevent="$emit('simulate', series.id, series.series_id, index, roundName)"
                class="bg-slate-900 rounded-t text-orange-500 px-2 hover:bg-slate-300 text-sm font-bold"
            >
                Simulate Game
            </button>
            <a
                href="#"
                v-if="!isHide && series.completed"
                class="bg-slate-900 rounded-t text-blue-500 underlined px-2 hover:bg-slate-300 text-sm font-bold"
                @click.prevent="isGameResultModalOpen = series.series_id"
            >
                View Results
            </a>
            <p v-if="isHide && index == activeIndex" class="bg-slate-900 rounded-t text-red-500 px-2 hover:bg-slate-300 text-sm font-bold">
                Simulating...
            </p>
        </div>
    </div>
</template>

<script setup>
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import {
    roundNameFormatter,
    roundGridFormatter,
    roundStatusFormatter,
} from "@/Utility/Formatter.js";

const props = defineProps({
    series: {
        type: Object,
        required: true
    },
    roundName: {
        type: String,
        required: true
    },
    index: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['simulate', 'compare']);

const getConferenceClass = (homeConference, awayConference) => {
    const conferenceClasses = {
        NCR: "bg-blue-100 text-blue-500",
        Luzon: "bg-green-100 text-green-500",
        Visayas: "bg-yellow-100 text-yellow-500",
        Mindanao: "bg-red-100 text-red-500",
    };

    if (homeConference !== awayConference) {
        return "bg-orange-100 text-orange-500";
    }

    return conferenceClasses[homeConference] || "bg-gray-100 text-gray-500";
};
</script>