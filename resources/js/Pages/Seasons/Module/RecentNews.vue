<template>
    <section
        class="mt-2 w-full min-w-0 overflow-hidden rounded-xl border border-white/[0.06] bg-black text-white shadow-xl shadow-red-950/20"
    >
        <!-- Header -->
        <div
            class="relative border-b border-white/[0.06] bg-gradient-to-r from-[#111111] via-[#0c0c0c] to-black px-4 py-3"
        >
            <!-- Red accent -->
            <div
                class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-red-500 via-red-600 to-transparent"
            ></div>

            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-500/20 bg-red-500/10"
                    >
                        <i class="fas fa-newspaper text-sm text-red-400"></i>
                    </div>

                    <div class="min-w-0">
                        <h2
                            class="truncate text-sm font-black uppercase tracking-[0.12em] text-yellow-400 sm:text-base"
                        >
                            Latest News
                        </h2>

                        <p
                            class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-gray-600"
                        >
                            League updates & game stories
                        </p>
                    </div>
                </div>

                <div
                    v-if="data?.total_items"
                    class="flex shrink-0 items-center gap-1.5 rounded-full border border-red-500/15 bg-red-500/10 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-red-400"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    {{ data.total_items }} News
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-4">
            <div
                class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="n in 3"
                    :key="n"
                    class="overflow-hidden rounded-xl border border-white/[0.06] bg-[#0d0d0d] p-4"
                >
                    <div class="animate-pulse space-y-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-8 w-8 shrink-0 rounded-lg bg-white/[0.06]"
                            ></div>

                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-2.5 w-1/3 rounded bg-white/[0.07]"
                                ></div>

                                <div
                                    class="h-3 w-3/4 rounded bg-white/[0.06]"
                                ></div>
                            </div>
                        </div>

                        <div
                            class="h-2.5 w-full rounded bg-white/[0.05]"
                        ></div>

                        <div
                            class="h-2.5 w-5/6 rounded bg-white/[0.05]"
                        ></div>

                        <div
                            class="h-7 w-20 rounded-lg bg-white/[0.05]"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- News -->
        <div v-else>
            <!-- Empty State -->
            <div
                v-if="!data?.data?.length"
                class="flex min-h-[180px] flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div
                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.03]"
                >
                    <i class="fas fa-newspaper text-lg text-gray-700"></i>
                </div>

                <p
                    class="text-xs font-black uppercase tracking-wider text-gray-500"
                >
                    No News Available
                </p>

                <p class="mt-1 text-[10px] text-gray-700">
                    There are no recent league stories to display.
                </p>
            </div>

            <!-- News Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="news in data.data"
                    :key="news.id"
                    class="group min-w-0 overflow-hidden rounded-xl border border-white/[0.06] bg-[#0b0b0b] transition-all duration-200 hover:-translate-y-0.5 hover:border-red-500/20 hover:bg-[#101010] hover:shadow-lg hover:shadow-red-950/10"
                >
                    <div
                        class="relative min-w-0 p-2"
                    >
                        <GameNews
                            :key="news.id"
                            :data="news"
                            :showNews="true"
                        />
                    </div>
                </article>
            </div>

            <!-- Pagination -->
            <div
                v-if="data?.total_pages"
                class="border-t border-white/[0.06] bg-[#080808] px-3 py-2"
            >
                <div class="w-full overflow-x-auto">
                    <Paginator
                        :page_number="search.page_num"
                        :total_rows="data.total_pages ?? 0"
                        :itemsperpage="search.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import Paginator from "@/Components/Paginator.vue";
import GameNews from "@/Pages/Seasons/Module/GameNews.vue";

const props = defineProps({
    season_status: {
        type: Number,
        default: null,
    },

    season_id: {
        type: Number,
        default: 1,
        required: true,
    },
});

const data = ref({
    data: [],
    total_items: 0,
    total_pages: 0,
});

const loading = ref(true);

const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 9,
    season_id: props.season_id,
});

const getNewsList = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("game.news.list"),
            search.value
        );

        data.value = {
            data: response.data?.data ?? [],
            total_items: response.data?.total_items ?? 0,
            total_pages: response.data?.total_pages ?? 0,
            ...response.data,
        };
    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Unable to Load News",
            text: "Failed to fetch the latest news. Please try again later.",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });
    } finally {
        loading.value = false;
    }
};

const handlePagination = (page_num) => {
    search.value.page_num = page_num ?? 1;
    getNewsList();
};

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
    background: #404040;
}

/* Skeleton */
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
    background: rgba(239, 68, 68, 0.2);
    color: white;
}
</style>