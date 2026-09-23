<template>
    <section
        ref="carouselContainer"
        class="relative w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-xl"
    >
        <!-- Header -->
        <div
            class="border-b border-white/[0.07] bg-gradient-to-r from-[#111111] via-[#0b0b0b] to-black px-4 py-4"
        >
            <div class="flex min-w-0 items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/[0.07]"
                    >
                        <i class="fas fa-chart-bar text-sm text-blue-400"></i>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-blue-400/70"
                        >
                            Draft Analytics
                        </div>

                        <h2
                            class="truncate text-base font-black tracking-tight text-white sm:text-lg"
                        >
                            Draft Statistics
                        </h2>
                    </div>
                </div>

                <div
                    v-if="!loading && data.length"
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-white/[0.07] bg-white/[0.03] px-3 py-2"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.6)]"
                    ></span>

                    <span
                        class="text-[9px] font-black uppercase tracking-wider text-gray-500"
                    >
                        {{ data.length }}
                        <span class="hidden sm:inline">
                            Draft Batches
                        </span>
                    </span>
                </div>

                <div
                    v-else-if="loading"
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-white/[0.07] bg-white/[0.03] px-3 py-2"
                >
                    <i
                        class="fas fa-circle-notch animate-spin text-[10px] text-blue-400"
                    ></i>

                    <span
                        class="hidden text-[9px] font-black uppercase tracking-wider text-gray-600 sm:inline"
                    >
                        Loading
                    </span>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="flex gap-3 overflow-hidden p-4"
        >
            <div
                v-for="n in 4"
                :key="n"
                class="h-44 min-w-[220px] flex-1 animate-pulse rounded-xl border border-white/[0.06] bg-white/[0.025]"
            ></div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="!data.length"
            class="flex min-h-[230px] flex-col items-center justify-center px-6 py-10 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.03]"
            >
                <i class="fas fa-chart-bar text-xl text-gray-700"></i>
            </div>

            <div
                class="text-[8px] font-black uppercase tracking-[0.25em] text-gray-700"
            >
                Draft Analytics
            </div>

            <h3 class="mt-1 text-sm font-black text-gray-500">
                No Statistics Available
            </h3>

            <p
                class="mt-2 max-w-sm text-xs leading-relaxed text-gray-700"
            >
                Draft statistics will appear here once draft data has
                been recorded.
            </p>
        </div>

        <!-- Carousel -->
        <div
            v-else
            class="relative p-3 sm:p-4"
        >
            <!-- Viewport -->
            <div
                ref="viewport"
                class="overflow-hidden"
            >
                <!-- Track -->
                <div
                    class="flex"
                    :style="{
                        transform: `translateX(-${currentOffset}px)`,
                        gap: `${gap}px`,
                        transition: 'transform 400ms ease',
                    }"
                >
                    <div
                        v-for="(item, index) in data"
                        :key="item.draft_id ?? index"
                        class="group relative min-w-0 shrink-0 overflow-hidden rounded-xl border border-white/[0.07] bg-[#0b0b0b] transition-all duration-200 hover:border-blue-500/20 hover:bg-[#0e0e0e]"
                        :style="{
                            width: `${cardWidth}px`,
                        }"
                    >
                        <!-- Accent -->
                        <div
                            class="absolute left-0 top-0 h-full w-0.5 bg-gradient-to-b from-blue-500/80 via-blue-500/30 to-transparent"
                        ></div>

                        <!-- Card -->
                        <div
                            class="relative flex h-full min-h-[185px] flex-col p-4"
                        >
                            <!-- Draft Header -->
                            <div
                                class="flex items-start justify-between gap-3 border-b border-white/[0.06] pb-3"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="text-[8px] font-black uppercase tracking-[0.2em] text-blue-400/70"
                                    >
                                        Draft Batch
                                    </div>

                                    <div
                                        class="mt-1 truncate text-lg font-black text-white"
                                    >
                                        {{ item.draft_id }}
                                    </div>
                                </div>

                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025]"
                                >
                                    <i
                                        class="fas fa-layer-group text-[10px] text-gray-600"
                                    ></i>
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div class="mt-4 space-y-2.5">
                                <!-- Active With Team -->
                                <div
                                    class="flex items-center justify-between rounded-lg border border-white/[0.05] bg-white/[0.02] px-3 py-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-500/[0.08]"
                                        >
                                            <i
                                                class="fas fa-users text-[9px] text-emerald-400"
                                            ></i>
                                        </div>

                                        <span
                                            class="text-[9px] font-bold uppercase tracking-wider text-gray-500"
                                        >
                                            With Team
                                        </span>
                                    </div>

                                    <span
                                        class="text-sm font-black text-emerald-400"
                                    >
                                        {{
                                            item.active_players_with_team ??
                                            0
                                        }}
                                    </span>
                                </div>

                                <!-- Active -->
                                <div
                                    class="flex items-center justify-between rounded-lg border border-white/[0.05] bg-white/[0.02] px-3 py-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-6 w-6 items-center justify-center rounded-md bg-blue-500/[0.08]"
                                        >
                                            <i
                                                class="fas fa-user-check text-[9px] text-blue-400"
                                            ></i>
                                        </div>

                                        <span
                                            class="text-[9px] font-bold uppercase tracking-wider text-gray-500"
                                        >
                                            Active
                                        </span>
                                    </div>

                                    <span
                                        class="text-sm font-black text-blue-400"
                                    >
                                        {{ item.active_players ?? 0 }}
                                    </span>
                                </div>

                                <!-- Inactive -->
                                <div
                                    class="flex items-center justify-between rounded-lg border border-white/[0.05] bg-white/[0.02] px-3 py-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-md bg-rose-500/[0.08]">
                                            <i
                                                class="fas fa-user-times text-[9px] text-rose-400"
                                            ></i>
                                        </div>

                                        <span
                                            class="text-[9px] font-bold uppercase tracking-wider text-gray-500"
                                        >
                                            Inactive
                                        </span>
                                    </div>

                                    <span
                                        class="text-sm font-black text-rose-400"
                                    >
                                        {{
                                            Math.max(
                                                0,
                                                (Number(item.total_players) || 0) -
                                                    (Number(item.active_players) || 0)
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Hover Details -->
                            <div
                                v-if="Number(item.active_players_with_team) > 0"
                                class="absolute inset-0 z-10 flex flex-col justify-center bg-black/95 p-5 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                            >
                                <!-- Overlay Header -->
                                <div
                                    class="mb-4 flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-blue-500/20 bg-blue-500/[0.08]"
                                    >
                                        <i
                                            class="fas fa-percentage text-[10px] text-blue-400"
                                        ></i>
                                    </div>

                                    <div>
                                        <div
                                            class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-600"
                                        >
                                            Draft Batch
                                        </div>

                                        <div
                                            class="text-sm font-black text-white"
                                        >
                                            Success Rate
                                        </div>
                                    </div>
                                </div>

                                <!-- With Team Percentage -->
                                <div>
                                    <div
                                        class="mb-1.5 flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[9px] font-bold uppercase tracking-wider text-gray-500"
                                        >
                                            With Team
                                        </span>

                                        <span
                                            class="text-xs font-black text-emerald-400"
                                        >
                                            {{
                                                item.active_percentage_with_team ??
                                                0
                                            }}%
                                        </span>
                                    </div>

                                    <div
                                        class="h-1.5 overflow-hidden rounded-full bg-white/[0.06]"
                                    >
                                        <div
                                            class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                            :style="{
                                                width: `${percentage(
                                                    item.active_percentage_with_team
                                                )}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Active Percentage -->
                                <div class="mt-4">
                                    <div
                                        class="mb-1.5 flex items-center justify-between"
                                    >
                                        <span
                                            class="text-[9px] font-bold uppercase tracking-wider text-gray-500"
                                        >
                                            Active
                                        </span>

                                        <span
                                            class="text-xs font-black text-blue-400"
                                        >
                                            {{
                                                item.active_percentage ?? 0
                                            }}%
                                        </span>
                                    </div>

                                    <div
                                        class="h-1.5 overflow-hidden rounded-full bg-white/[0.06]"
                                    >
                                        <div
                                            class="h-full rounded-full bg-blue-500 transition-all duration-500"
                                            :style="{
                                                width: `${percentage(
                                                    item.active_percentage
                                                )}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>

                                <div
                                    class="mt-5 text-center text-[8px] font-bold uppercase tracking-wider text-gray-700"
                                >
                                    Hover away to return
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previous -->
            <button
                v-if="canNavigate"
                type="button"
                @click="prevSlide"
                :disabled="currentIndex === 0"
                aria-label="Previous draft batch"
                class="absolute left-1 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl border border-white/[0.08] bg-black/90 text-gray-500 shadow-xl backdrop-blur transition hover:border-blue-500/30 hover:bg-[#111111] hover:text-blue-400 disabled:cursor-not-allowed disabled:opacity-20"
            >
                <i class="fas fa-chevron-left text-[10px]"></i>
            </button>

            <!-- Next -->
            <button
                v-if="canNavigate"
                type="button"
                @click="nextSlide"
                :disabled="currentIndex >= maxIndex"
                aria-label="Next draft batch"
                class="absolute right-1 top-1/2 z-20 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl border border-white/[0.08] bg-black/90 text-gray-500 shadow-xl backdrop-blur transition hover:border-blue-500/30 hover:bg-[#111111] hover:text-blue-400 disabled:cursor-not-allowed disabled:opacity-20"
            >
                <i class="fas fa-chevron-right text-[10px]"></i>
            </button>

            <!-- Indicators -->
            <div
                v-if="canNavigate"
                class="mt-3 flex items-center justify-center gap-1.5"
            >
                <button
                    v-for="index in Math.min(maxIndex + 1, 8)"
                    :key="index"
                    type="button"
                    @click="goToSlide(index - 1)"
                    :aria-label="`Go to slide ${index}`"
                    class="h-1 rounded-full transition-all duration-200"
                    :class="
                        currentIndex === index - 1
                            ? 'w-5 bg-blue-500'
                            : 'w-1.5 bg-white/[0.12] hover:bg-white/[0.25]'
                    "
                ></button>
            </div>

            <!-- Scroll Hint -->
            <div
                v-if="canNavigate"
                class="mt-2 flex items-center justify-center gap-1.5 text-[8px] font-bold uppercase tracking-wider text-gray-700"
            >
                <i class="fas fa-arrows-left-right"></i>
                <span>Browse draft batches</span>
            </div>
        </div>
    </section>
</template>

<script setup>
import {
    ref,
    computed,
    onMounted,
    onBeforeUnmount,
    nextTick,
} from "vue";
import axios from "axios";

const data = ref([]);
const loading = ref(true);

const carouselContainer = ref(null);
const viewport = ref(null);

const currentIndex = ref(0);
const containerWidth = ref(0);

const gap = 12;
const minCardWidth = 220;
const maxCardWidth = 280;

/*
|--------------------------------------------------------------------------
| Safe Percentage
|--------------------------------------------------------------------------
*/
const percentage = (value) => {
    const number = parseFloat(value);

    if (Number.isNaN(number)) {
        return 0;
    }

    return Math.min(100, Math.max(0, number));
};

/*
|--------------------------------------------------------------------------
| Responsive Visible Cards
|--------------------------------------------------------------------------
*/
const visibleCards = computed(() => {
    if (!containerWidth.value) {
        return 1;
    }

    if (containerWidth.value >= 1100) {
        return 4;
    }

    if (containerWidth.value >= 800) {
        return 3;
    }

    if (containerWidth.value >= 520) {
        return 2;
    }

    return 1;
});

/*
|--------------------------------------------------------------------------
| Responsive Card Width
|--------------------------------------------------------------------------
*/
const cardWidth = computed(() => {
    if (!containerWidth.value) {
        return minCardWidth;
    }

    const availableWidth =
        containerWidth.value -
        gap * (visibleCards.value - 1);

    const calculatedWidth =
        availableWidth / visibleCards.value;

    return Math.min(
        maxCardWidth,
        Math.max(minCardWidth, calculatedWidth)
    );
});

/*
|--------------------------------------------------------------------------
| Maximum Slide
|--------------------------------------------------------------------------
*/
const maxIndex = computed(() => {
    return Math.max(
        0,
        data.value.length - visibleCards.value
    );
});

/*
|--------------------------------------------------------------------------
| Navigation Availability
|--------------------------------------------------------------------------
*/
const canNavigate = computed(() => {
    return data.value.length > visibleCards.value;
});

/*
|--------------------------------------------------------------------------
| Current Track Offset
|--------------------------------------------------------------------------
*/
const currentOffset = computed(() => {
    return currentIndex.value * (cardWidth.value + gap);
});

/*
|--------------------------------------------------------------------------
| Fetch Draft Statistics
|--------------------------------------------------------------------------
*/
const fetchGameRecords = async () => {
    loading.value = true;

    try {
        const response = await axios.get(
            route("draft.statistics")
        );

        data.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching draft statistics:",
            error
        );

        data.value = [];
    } finally {
        loading.value = false;

        await nextTick();

        updateContainerWidth();
    }
};

/*
|--------------------------------------------------------------------------
| Carousel Navigation
|--------------------------------------------------------------------------
*/
const nextSlide = () => {
    if (currentIndex.value < maxIndex.value) {
        currentIndex.value++;
    }
};

const prevSlide = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
};

const goToSlide = (index) => {
    currentIndex.value = Math.min(
        Math.max(0, index),
        maxIndex.value
    );
};

/*
|--------------------------------------------------------------------------
| Measure Container
|--------------------------------------------------------------------------
*/
const updateContainerWidth = () => {
    if (!viewport.value) {
        return;
    }

    containerWidth.value =
        viewport.value.clientWidth;

    if (currentIndex.value > maxIndex.value) {
        currentIndex.value = maxIndex.value;
    }
};

/*
|--------------------------------------------------------------------------
| Resize Observer
|--------------------------------------------------------------------------
*/
let resizeObserver = null;

onMounted(async () => {
    await fetchGameRecords();

    await nextTick();

    updateContainerWidth();

    if (
        viewport.value &&
        typeof ResizeObserver !== "undefined"
    ) {
        resizeObserver = new ResizeObserver(() => {
            updateContainerWidth();
        });

        resizeObserver.observe(viewport.value);
    } else {
        window.addEventListener(
            "resize",
            updateContainerWidth
        );
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }

    window.removeEventListener(
        "resize",
        updateContainerWidth
    );
});
</script>

<style scoped>
::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: #292929;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #444;
}

::selection {
    background: rgba(59, 130, 246, 0.25);
    color: white;
}
</style>