<template>
    <div class="box-card p-4">
        <table class="player-table">
            <thead>
                <tr>
                    <th>Quarter</th>
                    <th>Team</th>
                    <th>Time</th>
                    <th class="text-left">Play By Play</th>
                    <th class="text-left">Score</th>
                </tr>
            </thead>

            <tbody>
                <tr
                v-for="(event, index) in props.play_by_play"
                :key="event.id"
                class="border-b-1 px-2"
                @click.prevent="showPlayerProfileModal = event.player_id"
                >
                    <td class="p-1">
                        <span class="text-md">
                            {{ event.quarter }}
                        </span>
                    </td>
                    <td class="p-1">
                        <span class="text-md">
                            {{ event.team_name }}
                        </span>
                    </td>
                    <td class="p-1">
                        <span class="text-md" :title="event.possession_number">
                            {{ formatTime(event.clock) }}
                        </span>
                    </td>
                    <td class="p-1 text-left">
                        {{ event?.description }}
                    </td>
                    <td class="p-1 text-left">
                        {{ event?.score_update }}
                    </td>
                </tr>
                <tr v-if="props.players?.length === 0">
                    <td class="empty-row">No play by play available.</td>
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
    formatTime,
    streakStatus
} from "@/Utility/Formatter";
import Modal from "@/Components/Modal.vue";

import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const props = defineProps({
    play_by_play: {
        type: Array,
        required: true,
        default: [],
    },
});

const showPlayerProfileModal = ref(false);

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

    .box-card {
        min-width: 0;
        background: #101217;
        border: 1px solid #24272e;
        border-radius: 14px;
        overflow: hidden;
    }
</style>
