<template>
    <div class="flex w-full flex-col items-center gap-3">
        <!-- Pagination -->
        <nav
            v-if="totalPages > 0"
            aria-label="Page navigation"
            class="w-full overflow-x-auto"
        >
            <ul
                class="mx-auto flex w-max items-center gap-1 rounded-xl border border-gray-800 bg-gray-950 p-1.5 shadow-lg"
            >
                <!-- First -->
                <li>
                    <button
                        type="button"
                        title="First page"
                        aria-label="First page"
                        :disabled="props.page_number <= 1"
                        :class="buttonClass(props.page_number <= 1)"
                        @click.prevent="paginateToPage(1)"
                    >
                        <i class="fa fa-angle-double-left"></i>
                    </button>
                </li>

                <!-- Previous -->
                <li>
                    <button
                        type="button"
                        title="Previous page"
                        aria-label="Previous page"
                        :disabled="props.page_number <= 1"
                        :class="buttonClass(props.page_number <= 1)"
                        @click.prevent="paginate(false)"
                    >
                        <i class="fa fa-angle-left"></i>
                    </button>
                </li>

                <!-- Page Numbers -->
                <template
                    v-for="(pn, index) in visiblePageNumbers"
                    :key="`${pn}-${index}`"
                >
                    <!-- Ellipsis -->
                    <li v-if="pn === '...'">
                        <span
                            class="flex h-9 min-w-9 items-center justify-center px-1 text-xs font-semibold text-gray-600"
                        >
                            ...
                        </span>
                    </li>

                    <!-- Page -->
                    <li v-else>
                        <button
                            type="button"
                            :aria-label="`Page ${pn}`"
                            :aria-current="
                                props.page_number === pn
                                    ? 'page'
                                    : undefined
                            "
                            :disabled="props.page_number === pn"
                            :class="
                                pageButtonClass(
                                    props.page_number === pn
                                )
                            "
                            @click.prevent="paginateToPage(pn)"
                        >
                            {{ pn }}
                        </button>
                    </li>
                </template>

                <!-- Next -->
                <li>
                    <button
                        type="button"
                        title="Next page"
                        aria-label="Next page"
                        :disabled="
                            props.page_number >= totalPages
                        "
                        :class="
                            buttonClass(
                                props.page_number >= totalPages
                            )
                        "
                        @click.prevent="paginate(true)"
                    >
                        <i class="fa fa-angle-right"></i>
                    </button>
                </li>

                <!-- Last -->
                <li>
                    <button
                        type="button"
                        title="Last page"
                        :aria-label="`Last page, page ${totalPages}`"
                        :disabled="
                            props.page_number >= totalPages
                        "
                        :class="
                            buttonClass(
                                props.page_number >= totalPages
                            )
                        "
                        @click.prevent="
                            paginateToPage(totalPages)
                        "
                    >
                        <i class="fa fa-angle-double-right"></i>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Pagination Information -->
        <div
            v-if="props.total_rows > 0"
            class="flex flex-col items-center gap-1 text-center"
        >
            <p class="text-xs font-medium text-gray-500">
                {{
                    pageInfo(
                        props.page_number,
                        props.total_rows,
                        props.itemsperpage
                    )
                }}
            </p>

            <p
                v-if="totalPages > 1"
                class="text-[10px] font-semibold uppercase tracking-wider text-gray-700"
            >
                Page
                <span class="text-gray-400">
                    {{ props.page_number }}
                </span>
                of
                <span class="text-gray-400">
                    {{ totalPages }}
                </span>
            </p>
        </div>

        <!-- Empty -->
        <div
            v-else
            class="py-2 text-center text-xs text-gray-600"
        >
            No records
        </div>
    </div>
</template>

<script setup>
import {
    ref,
    computed,
    watch,
    onMounted,
} from "vue";

import {
    pageInfo,
    page_number,
    generate_page_number,
} from "@/Utility/Pagination";

const emits = defineEmits(["page_num"]);

const props = defineProps({
    page_number: {
        type: Number,
        default: 1,
    },

    total_rows: {
        type: Number,
        default: 0,
    },

    itemsperpage: {
        type: Number,
        default: 10,
    },
});

/* =========================================================
   STATE
========================================================= */

const totalPages = ref(0);

/* =========================================================
   TOTAL PAGES
========================================================= */

const getTotalPageNumber = () => {
    const totalRows = Number(props.total_rows) || 0;
    const itemsPerPage =
        Number(props.itemsperpage) || 10;

    totalPages.value = Math.max(
        0,
        generate_page_number(
            totalRows,
            itemsPerPage
        )
    );
};

/* =========================================================
   PAGINATION
========================================================= */

const paginate = (isIncrement) => {
    const currentPage =
        Number(props.page_number) || 1;

    const nextPage = isIncrement
        ? currentPage + 1
        : currentPage - 1;

    if (
        nextPage < 1 ||
        nextPage > totalPages.value ||
        nextPage === currentPage
    ) {
        return;
    }

    emits("page_num", nextPage);
};

const paginateToPage = (pageNum) => {
    const page = Number(pageNum);

    if (
        !Number.isInteger(page) ||
        page < 1 ||
        page > totalPages.value ||
        page === props.page_number
    ) {
        return;
    }

    emits("page_num", page);
};

/* =========================================================
   VISIBLE PAGE NUMBERS
========================================================= */

const visiblePageNumbers = computed(() => {
    const currentPage =
        Number(props.page_number) || 1;

    if (totalPages.value <= 1) {
        return totalPages.value === 1
            ? [1]
            : [];
    }

    /*
     * Small number of pages:
     * Show everything.
     */
    if (totalPages.value <= 7) {
        return Array.from(
            { length: totalPages.value },
            (_, index) => index + 1
        );
    }

    /*
     * Near beginning.
     */
    if (currentPage <= 4) {
        return [
            1,
            2,
            3,
            4,
            5,
            "...",
            totalPages.value,
        ];
    }

    /*
     * Near end.
     */
    if (currentPage >= totalPages.value - 3) {
        return [
            1,
            "...",
            totalPages.value - 4,
            totalPages.value - 3,
            totalPages.value - 2,
            totalPages.value - 1,
            totalPages.value,
        ];
    }

    /*
     * Middle.
     */
    return [
        1,
        "...",
        currentPage - 1,
        currentPage,
        currentPage + 1,
        "...",
        totalPages.value,
    ];
});

/* =========================================================
   BUTTON STYLES
========================================================= */

const buttonClass = (disabled = false) => {
    return [
        "flex h-9 min-w-9 items-center justify-center rounded-lg border px-2 text-xs font-semibold transition-all duration-150",

        disabled
            ? "cursor-not-allowed border-gray-900 bg-gray-950 text-gray-700"
            : [
                  "border-gray-800",
                  "bg-gray-900",
                  "text-gray-500",
                  "hover:border-gray-700",
                  "hover:bg-gray-800",
                  "hover:text-gray-200",
                  "active:bg-gray-700",
              ],
    ];
};

const pageButtonClass = (active = false) => {
    return [
        "flex h-9 min-w-9 items-center justify-center rounded-lg border px-2 text-xs font-semibold transition-all duration-150",

        active
            ? [
                  "cursor-default",
                  "border-rose-500/30",
                  "bg-rose-500/15",
                  "text-rose-300",
                  "shadow-sm",
              ]
            : [
                  "border-transparent",
                  "bg-transparent",
                  "text-gray-500",
                  "hover:border-gray-800",
                  "hover:bg-gray-800",
                  "hover:text-gray-200",
              ],
    ];
};

/* =========================================================
   WATCHERS
========================================================= */

watch(
    () => props.total_rows,
    () => {
        getTotalPageNumber();
    }
);

watch(
    () => props.itemsperpage,
    () => {
        getTotalPageNumber();
    }
);

watch(
    () => props.page_number,
    (newPage) => {
        /*
         * Protect the paginator if the parent sends
         * a page greater than the newly calculated total.
         */
        if (
            totalPages.value > 0 &&
            newPage > totalPages.value
        ) {
            emits(
                "page_num",
                totalPages.value
            );
        }
    }
);

/* =========================================================
   INIT
========================================================= */

onMounted(() => {
    getTotalPageNumber();
});
</script>