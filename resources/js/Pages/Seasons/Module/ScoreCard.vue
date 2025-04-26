<template>
    <div
        :style="{
            background: `
                linear-gradient(45deg, 
                    #${game.home_secondary_color} 0%, 
                    #${game.home_secondary_color} 50%, 
                    #${game.home_primary_color} 50%, 
                    #${game.home_primary_color} 100%
                ),
                linear-gradient(-45deg, 
                    #${game.away_primary_color} 0%, 
                    #${game.away_primary_color} 50%, 
                    #${game.away_secondary_color} 50%, 
                    #${game.away_secondary_color} 100%
                )`,
            backgroundSize: '50% 100%',
            backgroundPosition: 'left, right',
            backgroundRepeat: 'no-repeat'
        }"
        class="rounded"
    >
        <div class="px-5 text-6xl font-bold text-white py-0 flex justify-between items-center">
            <div>
                <h3>{{ game.home_score }}</h3>
            </div>
            <div>
                <h3>{{ game.away_score }}</h3>
            </div>
        </div>
        <div class="px-1 py-2 flex justify-between items-center">
            
            <h3>
                <TeamDetails
                    :team_id="game.home_id"
                    :key="game.home_id"
                    class="text-white text-xl uppercase text-wrap text-left"
                    :showButton="0"
                    :text="game.home_team_name"
                />
            </h3>
            <h3>
                <TeamDetails
                    :team_id="game.away_id"
                    :key="game.away_id"
                    class="text-white text-xl uppercase text-wrap text-right"
                    :showButton="0"
                    :text="game.away_team_name"
                />
            </h3>
        </div>
        <div class="px-4 text-nowrap text-xs py-0">
            <span class="px-2 text-lg text-white py-1 rounded flex justify-center">
                {{ "Round #" + parseFloat(game.round) }}
            </span>
            <small class="flex justify-center text-white">#R{{ game.round }}-{{ game.game_id }}</small>
        </div>
        <div class="p-0 mt-2 flex justify-center items-center">
            <div v-if="!isSimulating">
                <a
                    href="#"
                    class="text-xs text-blue-500 underline font-bold rounded-t bg-white p-2"
                    @click.prevent="$emit('view-result', game.game_id)"
                >View Result</a>
            </div>
            <div v-else class="text-center">
                <p class="text-sm text-blue-500 underline font-bold rounded bg-white p-2">
                    Getting results
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";

defineProps({
    game: {
        type: Object,
        required: true
    },
    isSimulating: {
        type: Boolean,
        default: false
    }
});

defineEmits(['view-result']);
</script>