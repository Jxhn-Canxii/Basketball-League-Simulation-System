<template>
    <div class="w-full space-y-4">
        <!-- =========================
             PLAYER STATISTICS
        ========================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3">
            <!-- Total Players -->
            <div class="stat-card">
                <div class="stat-icon bg-orange-500/10 text-orange-400 border-orange-500/20">
                    <i class="fa fa-users"></i>
                </div>

                <div class="min-w-0">
                    <p class="stat-label">Total Players</p>
                    <p class="stat-value">
                        {{ moneyFormatter(data.total_players ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Rookies -->
            <div class="stat-card">
                <div class="stat-icon bg-emerald-500/10 text-emerald-400 border-emerald-500/20">
                    <i class="fa fa-star"></i>
                </div>

                <div class="min-w-0">
                    <p class="stat-label">Rookies</p>
                    <p class="stat-value">
                        {{ moneyFormatter(data.rookie_players ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Retired -->
            <div class="stat-card">
                <div class="stat-icon bg-rose-500/10 text-rose-400 border-rose-500/20">
                    <i class="fa fa-user-slash"></i>
                </div>

                <div class="min-w-0">
                    <p class="stat-label">Retired</p>
                    <p class="stat-value">
                        {{ moneyFormatter(data.retired_players ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Active Players -->
            <div class="stat-card">
                <div class="stat-icon bg-sky-500/10 text-sky-400 border-sky-500/20">
                    <i class="fa fa-chess"></i>
                </div>

                <div class="min-w-0">
                    <p class="stat-label">Active Players</p>
                    <p class="stat-value">
                        {{ moneyFormatter(data.active_players_with_team ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Available Slots -->
            <div class="stat-card">
                <div class="stat-icon bg-violet-500/10 text-violet-400 border-violet-500/20">
                    <i class="fa fa-check"></i>
                </div>

                <div class="min-w-0">
                    <p class="stat-label">Available Slots</p>
                    <p class="stat-value">
                        {{ moneyFormatter(data.total_available_slots ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Free Agents -->
            <div class="stat-card">
                <div class="stat-icon bg-cyan-500/10 text-cyan-400 border-cyan-500/20">
                    <i class="fa fa-user-circle"></i>
                </div>

                <div class="min-w-0">
                    <p class="stat-label">Free Agents</p>
                    <p class="stat-value">
                        {{ moneyFormatter(data.free_agents ?? 0) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- =========================
             POSITION NEEDS
        ========================== -->
        <div
            v-if="hasNeededPositions || data?.position_summary?.warning"
            class="overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-xl"
        >
            <!-- Header -->
            <div
                class="flex flex-col gap-3 border-b border-gray-800 bg-gray-900/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-rose-500/20 bg-rose-500/10 text-rose-400"
                    >
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-100">
                            Position Needs
                        </h3>

                        <p class="text-xs text-gray-500">
                            Current roster requirements
                        </p>
                    </div>
                </div>

                <!-- Warning -->
                <div
                    v-if="data?.position_summary?.warning"
                    class="rounded-lg border border-amber-500/20 bg-amber-500/5 px-3 py-2 text-xs text-amber-300"
                >
                    <i class="fa fa-info-circle mr-1"></i>
                    {{ data.position_summary.warning }}
                </div>
            </div>

            <!-- Position Needs -->
            <div class="p-4">
                <div
                    v-if="filteredSummary.length"
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5"
                >
                    <div
                        v-for="[itemKey, itemValue] in filteredSummary"
                        :key="itemKey"
                        class="group rounded-lg border border-rose-500/15 bg-gray-900/70 p-3 transition-all duration-200 hover:border-rose-500/30 hover:bg-gray-900"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                {{ formatPosition(itemKey) }}
                            </span>

                            <span
                                class="flex h-7 min-w-7 items-center justify-center rounded-md border border-rose-500/20 bg-rose-500/10 px-2 text-sm font-bold text-rose-300"
                            >
                                {{ itemValue }}
                            </span>
                        </div>

                        <div class="mt-2 text-[11px] text-gray-600">
                            player{{ itemValue !== 1 ? "s" : "" }} needed
                        </div>
                    </div>
                </div>

                <!-- No needs -->
                <div
                    v-else
                    class="flex items-center gap-3 rounded-lg border border-emerald-500/10 bg-emerald-500/5 px-4 py-3"
                >
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400"
                    >
                        <i class="fa fa-check"></i>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-emerald-300">
                            Roster is balanced
                        </p>
                        <p class="text-xs text-gray-500">
                            No position shortages detected.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { moneyFormatter } from "@/Utility/Formatter";

const data = ref({
    total_players: 0,
    rookie_players: 0,
    retired_players: 0,
    active_players_with_team: 0,
    total_available_slots: 0,
    free_agents: 0,
    position_summary: {},
});

const fetchPlayerCount = async () => {
    try {
        const response = await axios.get(
            route("analytics.player.count")
        );

        data.value = {
            ...data.value,
            ...(response.data ?? {}),
        };
    } catch (error) {
        console.error("Error fetching player count:", error);
    }
};

/**
 * Position fields supported by the API.
 */
const isPosition = (key) => {
    return [
        "PG_needed",
        "SG_needed",
        "SF_needed",
        "PF_needed",
        "C_needed",
    ].includes(key);
};

/**
 * Positions that currently need players.
 */
const filteredSummary = computed(() => {
    return Object.entries(data.value?.position_summary ?? {}).filter(
        ([key, value]) =>
            isPosition(key) &&
            Number(value) > 0
    );
});

/**
 * Whether at least one position needs a player.
 */
const hasNeededPositions = computed(() => {
    return filteredSummary.value.length > 0;
});

/**
 * Format:
 * PG_needed -> PG
 * SG_needed -> SG
 */
const formatPosition = (key) => {
    return key.replace("_needed", "");
};

onMounted(() => {
    fetchPlayerCount();
});
</script>

<style scoped>
.stat-card {
    @apply flex min-w-0 items-center gap-3 rounded-xl border border-gray-800 bg-gray-950 p-4 shadow-lg transition-all duration-200;
}

.stat-card:hover {
    @apply border-gray-700 bg-gray-900;
}

.stat-icon {
    @apply flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border text-sm;
}

.stat-label {
    @apply mb-1 truncate text-xs font-medium uppercase tracking-wider text-gray-500;
}

.stat-value {
    @apply text-xl font-bold leading-none text-gray-100;
}
</style>