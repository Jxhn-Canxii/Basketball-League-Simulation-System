<template>
    <section
        class="mt-2 w-full min-w-0 overflow-hidden rounded-2xl border border-white/[0.07] bg-black text-white shadow-2xl"
    >
        <!-- Header -->
        <div
            class="relative border-b border-white/[0.07] bg-gradient-to-r from-[#111111] via-[#0b0b0b] to-black px-4 py-4"
        >
            <!-- Accent -->
            <div
                class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-yellow-400 via-yellow-500 to-transparent"
            ></div>

            <div class="flex min-w-0 items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-yellow-500/20 bg-yellow-500/[0.08]"
                    >
                        <i class="fas fa-newspaper text-sm text-yellow-500"></i>
                    </div>

                    <div class="min-w-0">
                        <div
                            class="text-[8px] font-black uppercase tracking-[0.25em] text-yellow-500/70"
                        >
                            League Updates
                        </div>

                        <h2
                            class="truncate text-base font-black tracking-tight text-white sm:text-lg"
                        >
                            Latest News
                        </h2>
                    </div>
                </div>

                <!-- News Count -->
                <div
                    class="flex shrink-0 items-center gap-2 rounded-lg border border-white/[0.07] bg-white/[0.03] px-2.5 py-1.5"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.6)]"
                    ></span>

                    <span
                        class="text-[9px] font-black uppercase tracking-wider text-gray-400"
                    >
                        {{ data?.total_pages ?? 0 }}
                        <span class="hidden sm:inline">Stories</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-4 sm:p-5">
            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="n in 3"
                    :key="n"
                    class="overflow-hidden rounded-2xl border border-white/[0.06] bg-[#0d0d0d] p-4"
                >
                    <div class="animate-pulse space-y-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-8 w-8 shrink-0 rounded-lg bg-white/[0.06]"
                            ></div>

                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-2.5 w-1/3 rounded bg-white/[0.07]"
                                ></div>

                                <div
                                    class="h-4 w-3/4 rounded bg-white/[0.07]"
                                ></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                class="h-2.5 w-full rounded bg-white/[0.05]"
                            ></div>

                            <div
                                class="h-2.5 w-5/6 rounded bg-white/[0.05]"
                            ></div>

                            <div
                                class="h-2.5 w-2/3 rounded bg-white/[0.05]"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- News -->
        <div
            v-else-if="newsItems.length"
            class="p-3 sm:p-4"
        >
            <!--
                Responsive grid:
                mobile  = 1 column
                md      = 2 columns
                xl      = 3 columns

                min-w-0 prevents the cards from forcing the parent wider.
            -->
            <div
                class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="news in newsItems"
                    :key="news.id"
                    class="min-w-0 overflow-hidden rounded-2xl bg-[#080808] transition duration-200 hover:border-white/[0.11] hover:bg-[#0b0b0b]"
                >
                    <GameNews
                        :key="news.id"
                        :data="news"
                        :showNews="true"
                        class="!mt-0 !max-w-none"
                    />
                </article>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else
            class="flex min-h-[220px] flex-col items-center justify-center px-6 py-12 text-center"
        >
            <div
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.03]"
            >
                <i class="fas fa-newspaper text-xl text-gray-600"></i>
            </div>

            <h3 class="text-sm font-black uppercase tracking-wider text-gray-400">
                No News Available
            </h3>

            <p class="mt-2 max-w-sm text-xs leading-relaxed text-gray-600">
                There are no league news stories available for this season yet.
            </p>
        </div>

        <!-- Pagination -->
        <div
            v-if="data?.total_pages && data.total_pages > 1"
            class="border-t border-white/[0.07] bg-[#080808] px-3 py-3 sm:px-4"
        >
            <div class="flex min-w-0 justify-center overflow-x-auto">
                <Paginator
                    :page_number="search.page_num"
                    :total_rows="data.total_pages ?? 0"
                    :itemsperpage="search.itemsperpage"
                    @page_num="handlePagination"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import Paginator from "@/Components/Paginator.vue";
import GameNews from "@/Pages/Seasons/Module/GameNews.vue";

const props = defineProps({
    season_id: {
        type: [Number, String],
        default: 1,
    },
});

const data = ref({});
const loading = ref(true);

const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 9,
    season_id: Number(props.season_id) || 1,
});

/*
|--------------------------------------------------------------------------
| News Items
|--------------------------------------------------------------------------
|
| Keeps the component safe whether the API returns:
|
| {
|   data: [...]
| }
|
| or no data / null.
|
*/
const newsItems = computed(() => {
    return Array.isArray(data.value?.data)
        ? data.value.data
        : [];
});

/*
|--------------------------------------------------------------------------
| Fetch News
|--------------------------------------------------------------------------
*/
const getNewsList = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("game.news.list"),
            search.value
        );

        data.value = response.data ?? {};
    } catch (error) {
        console.error("Failed to fetch game news:", error);

        Swal.fire({
            icon: "error",
            title: "Unable to Load News",
            text: "Failed to fetch the latest news. Please try again later.",
            background: "#0b0b0b",
            color: "#ffffff",
            confirmButtonColor: "#eab308",
        });
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/
const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;

    getNewsList();
};

/*
|--------------------------------------------------------------------------
| Refresh when season changes
|--------------------------------------------------------------------------
*/
watch(
    () => props.season_id,
    (newSeasonId) => {
        const seasonId = Number(newSeasonId) || 1;

        if (search.value.season_id !== seasonId) {
            search.value.season_id = seasonId;
            search.value.page_num = 1;

            getNewsList();
        }
    }
);

onMounted(() => {
    getNewsList();
});
</script>

<style scoped>
/* Smooth dark scrollbar */
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

/* Skeleton animation */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.45;
    }
}

/* Selection */
::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>