

<template>
    <div
        class="w-full rounded-xl border border-gray-700 bg-gray-900 p-6 text-gray-100 shadow-xl"
    >
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-500/10 text-red-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h14"
                        />
                    </svg>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-white">
                        Reset League Data
                    </h2>

                    <p class="text-sm text-gray-400">
                        {{ seasonName
                            ? `Season ${seasonName}`
                            : `Season ${seasonId}` }}
                    </p>
                </div>
            </div>

            <p class="mt-4 text-sm leading-6 text-gray-400">
                Use these tools to recover from playoff generation or
                simulation errors. Resetting a round also removes all
                subsequent playoff rounds.
            </p>
        </div>

        <!-- Success -->
        <div
            v-if="successMessage"
            class="mb-5 rounded-lg border border-emerald-800 bg-emerald-950/50 px-4 py-3 text-sm text-emerald-300"
        >
            {{ successMessage }}
        </div>

        <!-- Error -->
        <div
            v-if="errorMessage"
            class="mb-5 rounded-lg border border-red-800 bg-red-950/50 px-4 py-3 text-sm text-red-300"
        >
            {{ errorMessage }}
        </div>

        <!-- Reset Round -->
        <div
            class="mb-5 rounded-xl border border-gray-700 bg-gray-800/60 p-5"
        >
            <div class="mb-4">
                <h3 class="font-semibold text-white">
                    Reset From Round
                </h3>

                <p class="mt-1 text-sm text-gray-400">
                    Keeps earlier rounds and removes the selected round
                    plus everything after it.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <select
                    v-model="selectedRound"
                    class="w-full rounded-lg border border-gray-600 bg-gray-900 px-4 py-2.5 text-sm text-gray-100 outline-none transition focus:border-red-500 focus:ring-1 focus:ring-red-500 sm:flex-1"
                    :disabled="loading"
                >
                    <option
                        v-for="round in rounds"
                        :key="round.value"
                        :value="round.value"
                    >
                        {{ round.label }}
                    </option>
                </select>

                <button
                    type="button"
                    @click="openConfirmation('round')"
                    :disabled="loading"
                    class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Reset Round
                </button>
            </div>
        </div>

        <!-- Reset Playoffs -->
        <div
            class="mb-5 rounded-xl border border-gray-700 bg-gray-800/60 p-5"
        >
            <div class="mb-4">
                <h3 class="font-semibold text-white">
                    Reset Entire Playoffs
                </h3>

                <p class="mt-1 text-sm leading-5 text-gray-400">
                    Deletes all playoff games, series, playoff statistics,
                    and related playoff data. Regular-season data is kept.
                </p>
            </div>

            <button
                type="button"
                @click="openConfirmation('playoffs')"
                :disabled="loading"
                class="w-full rounded-lg border border-red-700 bg-red-950/40 px-5 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Reset Entire Playoffs
            </button>
        </div>

        <!-- Reset Season -->
        <div
            class="rounded-xl border border-red-900/70 bg-red-950/20 p-5"
        >
            <div class="mb-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-red-300">
                        Reset Entire Season
                    </h3>

                    <span
                        class="rounded-full bg-red-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-400"
                    >
                        Dangerous
                    </span>
                </div>

                <p class="mt-1 text-sm leading-5 text-gray-400">
                    Deletes all regular-season and playoff game data
                    for this season.
                </p>
            </div>

            <button
                type="button"
                @click="openConfirmation('season')"
                :disabled="loading"
                class="w-full rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Reset Entire Season
            </button>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showConfirmModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                @click.self="closeConfirmation"
            >
                <div
                    class="w-full max-w-lg rounded-2xl border border-gray-700 bg-gray-900 p-6 shadow-2xl"
                >
                    <!-- Modal Header -->
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                                />
                            </svg>
                        </div>

                        <div>
                            <h3
                                class="text-lg font-semibold text-white"
                            >
                                {{ confirmationTitle }}
                            </h3>

                            <p
                                class="mt-2 text-sm leading-6 text-gray-400"
                            >
                                {{ confirmationMessage }}
                            </p>
                        </div>
                    </div>

                    <!-- Season Information -->
                    <div
                        class="mt-5 rounded-lg border border-gray-700 bg-gray-800 px-4 py-3"
                    >
                        <div
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-gray-400">
                                Season
                            </span>

                            <span class="font-semibold text-white">
                                {{ props.seasonId }}
                            </span>
                        </div>

                        <div
                            v-if="pendingAction === 'round'"
                            class="mt-2 flex items-center justify-between text-sm"
                        >
                            <span class="text-gray-400">
                                Starting round
                            </span>

                            <span class="font-semibold text-amber-400">
                                {{ selectedRoundLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div
                        class="mt-4 rounded-lg border border-red-900 bg-red-950/40 px-4 py-3"
                    >
                        <p
                            class="text-xs leading-5 text-red-300"
                        >
                            This action cannot be undone. Make sure you
                            have a database backup before continuing.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div
                        class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            @click="closeConfirmation"
                            :disabled="loading"
                            class="rounded-lg border border-gray-600 bg-gray-800 px-5 py-2.5 text-sm font-medium text-gray-200 transition hover:bg-gray-700 disabled:opacity-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            @click="executeReset"
                            :disabled="loading"
                            class="rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="loading">
                                Resetting...
                            </span>

                            <span v-else>
                                Yes, Reset
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    seasonId: {
        type: [Number, String],
        required: true,
    },

    // Optional:
    // If passed, this can be used as the default selected round.
    currentRound: {
        type: String,
        default: '',
    },

    // Optional:
    // If you only want to show the reset UI for the current season.
    seasonName: {
        type: String,
        default: '',
    },
});

const emit = defineEmits([
    'success',
    'error',
    'reset',
]);

const selectedRound = ref(props.currentRound || 'quarter_finals');

const loading = ref(false);
const showConfirmModal = ref(false);

const pendingAction = ref(null);

const successMessage = ref('');
const errorMessage = ref('');

const rounds = [
    {
        value: 'play_ins_elims_round_1',
        label: 'Play-In Elimination Round 1',
        status: 4,
    },
    {
        value: 'play_ins_elims_round_2',
        label: 'Play-In Elimination Round 2',
        status: 5,
    },
    {
        value: 'play_ins_finals',
        label: 'Play-In Finals',
        status: 6,
    },
    {
        value: 'round_of_16',
        label: 'Round of 16',
        status: 7,
    },
    {
        value: 'quarter_finals',
        label: 'Quarter Finals',
        status: 8,
    },
    {
        value: 'semi_finals',
        label: 'Semi Finals',
        status: 9,
    },
    {
        value: 'interconference_semi_finals',
        label: 'Interconference Semi Finals',
        status: 10,
    },
    {
        value: 'finals',
        label: 'Finals',
        status: 11,
    },
];

const selectedRoundLabel = computed(() => {
    return rounds.find(
        round => round.value === selectedRound.value
    )?.label || selectedRound.value;
});

const confirmationTitle = computed(() => {
    if (pendingAction.value === 'round') {
        return `Reset ${selectedRoundLabel.value}?`;
    }

    if (pendingAction.value === 'playoffs') {
        return 'Reset Entire Playoffs?';
    }

    if (pendingAction.value === 'season') {
        return 'Reset Entire Season?';
    }

    return 'Confirm Reset';
});

const confirmationMessage = computed(() => {
    if (pendingAction.value === 'round') {
        return `This will delete ${selectedRoundLabel.value} and every playoff round after it. Earlier rounds will remain untouched.`;
    }

    if (pendingAction.value === 'playoffs') {
        return 'This will delete all playoff schedules, series, playoff statistics, and playoff game data. Regular-season data will remain untouched.';
    }

    if (pendingAction.value === 'season') {
        return 'This is the most destructive option. It will delete the entire season game data, including regular-season and playoff data.';
    }

    return '';
});

function clearMessages() {
    successMessage.value = '';
    errorMessage.value = '';
}

function openConfirmation(action) {
    clearMessages();

    pendingAction.value = action;
    showConfirmModal.value = true;
}

function closeConfirmation() {
    if (loading.value) {
        return;
    }

    showConfirmModal.value = false;
    pendingAction.value = null;
}

async function executeReset() {
    if (!pendingAction.value) {
        return;
    }

    loading.value = true;
    clearMessages();

    try {
        let url;
        let payload;

        switch (pendingAction.value) {
            case 'round':
                url = route('playoff.reset.round');

                payload = {
                    season_id: props.seasonId,
                    round: selectedRound.value,
                };

                break;

            case 'playoffs':
                url = route('playoff.reset.all');

                payload = {
                    season_id: props.seasonId,
                };

                break;

            case 'season':
                url = route('season.reset.all');

                payload = {
                    season_id: props.seasonId,
                };

                break;

            default:
                throw new Error('Invalid reset action.');
        }

        const response = await axios.post(
            url,
            payload
        );

        successMessage.value =
            response.data?.message ||
            'Reset completed successfully.';

        emit('success', response.data);
        emit('reset', {
            type: pendingAction.value,
            response: response.data,
        });

        showConfirmModal.value = false;
        pendingAction.value = null;

    } catch (error) {

        console.error('Playoff reset error:', error);

        errorMessage.value =
            error.response?.data?.message ||
            error.message ||
            'Failed to reset data.';

        emit('error', error);
    } finally {
        loading.value = false;
    }
}
</script>
