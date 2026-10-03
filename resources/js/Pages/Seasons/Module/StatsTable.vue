<template>
    <div>
        <table class="player-table">
        <thead>
            <tr>
            <th>PLAYER</th>
            <th>POS</th>
            <th>MIN</th>
            <th>PTS</th>
            <th>REB</th>
            <th>AST</th>
            <th>STL</th>
            <th>BLK</th>
            <th>TO</th>
            <th>FG</th>
            <th>2P</th>
            <th>3P</th>
            <th>FT</th>
            <th>PER</th>
            <th>EFF</th>
            </tr>
        </thead>

        <tbody>
            <tr
            v-for="(player, index) in sortedPlayers"
            :key="player.name"
            :class="{
                'top-player': topPlayers(index),
            }"
            @click.prevent="showPlayerProfileModal = player"
            >
            <td
                class="player-name-cell"
                :class="boxScoreRoleBadgeClass(player.role, player.minutes)"
            >
                <div>
                <span class="text-md">
                    {{ player.name }}
                </span>

                <small>
                    {{ player.is_rookie ? "R" : "V" }}

                    <i v-if="player.is_injured" class="fas fa-plus-square injury-icon"></i>

                    <i v-if="player.is_fouled_out" class="fas fa-square fouled-icon"></i>
                    <i v-if="player.is_starter" class="fas fa-crown text-yellow-500"></i>
                </small>
                </div>
            </td>

            <td>{{ player.position }}</td>

            <td class="number">
                {{ player.minutes === 0 ? "DNP" : player.minutes.toFixed(1) }}
            </td>

            <td class="number points">
                {{ player.points.toFixed(1) }}
            </td>

            <td class="number">
                {{ player.rebounds.toFixed(1) }}
            </td>

            <td class="number">
                {{ player.assists.toFixed(1) }}
            </td>

            <td class="number">
                {{ player.steals.toFixed(1) }}
            </td>

            <td class="number">
                {{ player.blocks.toFixed(1) }}
            </td>

            <td class="number">
                {{ player.minutes === 0 ? "0.0" : player.turnovers.toFixed(1) }}
            </td>

            <td class="number">
                {{ player.field_goals_made }}
                /
                {{ player.field_goal_attempts }}
            </td>

            <td class="number">
                {{ player.two_pointers_made }}
                /
                {{ player.two_point_attempts }}
            </td>

            <td class="number">
                {{ player.three_pointers_made }}
                /
                {{ player.three_point_attempts }}
            </td>

            <td class="number">
                {{ player.free_throws_made }}
                /
                {{ player.free_throw_attempts }}
            </td>

            <td class="number">
                {{
                isNaN(parseFloat(player.per)) ? "0.00" : parseFloat(player.per).toFixed(2)
                }}
            </td>

            <td class="number">
                <strong :class="player.efficiency <= 0 ? 'negative-stat' : 'positive-stat'">
                {{ player.efficiency ?? 0 }}
                </strong>
            </td>
            </tr>

            <tr v-if="props.players?.length === 0">
            <td colspan="12" class="empty-row">No player statistics available.</td>
            </tr>
        </tbody>
        </table>
    </div>
    <!-- =========================================================
            PLAYER MODAL
        ========================================================== -->
    <Modal
        :show="showPlayerProfileModal"
        maxWidth="6xl"
        title="Player Profile"
        @close="showPlayerProfileModal = false"
    >
        <div class="p-6">
        <PlayerPerformance
            :key="showPlayerProfileModal.player_id"
            :player_id="showPlayerProfileModal.player_id"
        />
        </div>
    </Modal>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import {
    roundNameFormatter,
    roleBadgeClass,
    playerFormatter,
    boxScoreRoleBadgeClass,
    streakStatus
} from "@/Utility/Formatter";
import Modal from "@/Components/Modal.vue";

import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    players: {
        type: Array,
        required: true,
        default: [],
    },
});

const showPlayerProfileModal = ref(false);

/*
|--------------------------------------------------------------------------
| PLAYER SORTING
|--------------------------------------------------------------------------
*/

const sortedPlayers = computed(() => {
    return props.players
        .slice()
        .sort((a, b) => b.points - a.points);
});

const topPlayers = (index) => {
    return index <= 4;
};

</script>
<style scoped>
    .player-table {
    min-width: 700px;
    }

    .injury-icon {
    color: #ef4444;
    margin-left: 3px;
    }

    .fouled-icon {
    color: #f59e0b;
    margin-left: 3px;
    }

    .empty-row {
    padding: 30px !important;
    color: #4b5563 !important;
    }

    .number {
    font-variant-numeric: tabular-nums;
    }

    /* =========================================================
    TABLE
    ========================================================= */

    .table-scroll {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    }

    .player-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
    }

    .player-table th {
    padding: 8px 7px;
    background: #0c0e12;
    color: #4b5563;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: 0.05em;
    text-align: center;
    white-space: nowrap;
    }

    .player-table th:first-child {
    text-align: left;
    padding-left: 13px;
    }

    .player-table td {
    padding: 8px 7px;
    border-top: 1px solid #1c1f25;
    color: #9ca3af;
    font-size: 9px;
    text-align: center;
    white-space: nowrap;
    }

    .player-table tbody tr {
    cursor: pointer;
    transition: background 0.12s ease;
    }

    .player-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.025);
    }

    .player-table tbody tr.top-player {
    background: rgba(255, 255, 255, 0.018);
    }

    .player-name-cell {
    min-width: 100%;
    text-align: left !important;
    padding-left: 13px !important;
    }

    .player-name-cell > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2px;
    }

    .player-name-cell span {
    overflow: hidden;
    text-overflow: ellipsis;
    color: #e5e7eb;
    font-weight: 600;
    }

    .player-name-cell small {
    color: #4b5563;
    font-size: 7px;
    }

    .number {
    font-variant-numeric: tabular-nums;
    }

    .points {
    color: #f3f4f6 !important;
    font-weight: 800;
    }

    .positive-stat {
    color: #86efac;
    }

    .negative-stat {
    color: #f87171;
    }
</style>
