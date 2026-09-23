<template>
    <article
        class="relative z-[9000] mt-4 w-full overflow-hidden rounded-2xl border border-white/[0.08] bg-gradient-to-br from-[#151515] via-[#0b0b0b] to-black text-white shadow-xl transition duration-200 hover:border-white/[0.12]"
        :class="textLarge ? 'max-w-lg' : 'max-w-sm'"
    >
        <!-- Subtle accent -->
        <div
            class="pointer-events-none absolute left-0 top-0 h-full w-1 bg-yellow-500/70"
        ></div>

        <div
            class="relative px-4 py-4"
            :class="textLarge ? 'md:px-5 md:py-5' : ''"
        >
            <!-- Header -->
            <div
                class="flex items-start justify-between gap-3 border-b border-white/[0.08] pb-3"
            >
                <div class="min-w-0 flex-1">
                    <div
                        class="mb-1 flex items-center gap-2 text-[8px] font-black uppercase tracking-[0.22em] text-yellow-500/80"
                    >
                        <span
                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-yellow-500"
                        ></span>

                        News
                    </div>

                    <h3
                        class="font-black leading-snug tracking-tight text-white"
                        :class="textLarge ? 'text-xl' : 'text-lg'"
                    >
                        {{ data?.title ?? "News Update" }}
                    </h3>
                </div>

                <!-- Expand button -->
                <button
                    type="button"
                    class="mt-1 flex h-8 shrink-0 items-center justify-center rounded-lg border border-white/[0.08] bg-white/[0.03] px-2.5 text-[9px] font-black uppercase tracking-wider text-gray-500 transition hover:border-yellow-500/20 hover:bg-yellow-500/[0.06] hover:text-yellow-400"
                    @click.prevent="show_news = !show_news"
                >
                    {{ show_news ? "Less" : "More" }}
                </button>
            </div>

            <!-- Content -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="max-h-0 opacity-0"
                enter-to-class="max-h-96 opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="max-h-96 opacity-100"
                leave-to-class="max-h-0 opacity-0"
            >
                <div
                    v-if="show_news"
                    class="overflow-hidden"
                >
                    <p
                        class="pt-4 leading-relaxed text-gray-300"
                        :class="
                            textLarge
                                ? 'text-base md:text-lg'
                                : 'text-sm'
                        "
                    >
                        {{ data?.content ?? data?.body ?? "" }}
                    </p>
                </div>
            </Transition>

            <!-- Bottom indicator -->
            <div
                v-if="!show_news"
                class="mt-3 flex items-center gap-2 text-[8px] font-bold uppercase tracking-wider text-gray-700"
            >
                <span
                    class="h-px flex-1 bg-white/[0.05]"
                ></span>

                <span>
                    Click more to read
                </span>

                <span
                    class="h-px flex-1 bg-white/[0.05]"
                ></span>
            </div>
        </div>
    </article>
</template>

<script setup>
import {
    ref,
    watch,
} from "vue";

const props = defineProps({
    data: {
        type: Object,
        required: true,
        default: () => ({
            title: "",
            content: "",
            body: "",
        }),
    },

    showNews: {
        type: Boolean,
        default: false,
    },

    textLarge: {
        type: Boolean,
        default: false,
    },
});

const show_news = ref(props.showNews);

/*
|--------------------------------------------------------------------------
| Keep local state synchronized with parent
|--------------------------------------------------------------------------
*/

watch(
    () => props.showNews,
    (value) => {
        show_news.value = value;
    }
);
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Scrollbar
|--------------------------------------------------------------------------
*/

::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

::-webkit-scrollbar-track {
    background: #050505;
}

::-webkit-scrollbar-thumb {
    background: #303030;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #4a4a4a;
}

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

::selection {
    background: rgba(234, 179, 8, 0.25);
    color: white;
}
</style>