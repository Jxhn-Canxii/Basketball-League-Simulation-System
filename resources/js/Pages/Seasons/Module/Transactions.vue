<template>
    <section
        class="mt-2 w-full min-w-0 overflow-hidden rounded-xl border border-white/[0.06] bg-black text-white shadow-xl shadow-red-950/20"
    >
        <!-- Header -->
        <div
            v-if="props.showTitle"
            class="relative border-b border-white/[0.06] bg-gradient-to-r from-[#111111] via-[#0c0c0c] to-black px-4 py-3"
        >
            <div
                class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-red-500 via-red-600 to-transparent"
            ></div>

            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-500/20 bg-red-500/10"
                    >
                        <i
                            class="fas fa-exchange-alt text-sm text-red-400"
                        ></i>
                    </div>

                    <div class="min-w-0">
                        <h2
                            class="truncate text-sm font-black uppercase tracking-[0.12em] text-yellow-400 sm:text-base"
                        >
                            Season Transactions
                        </h2>

                        <p
                            class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-gray-600"
                        >
                            Player movement & league activity
                        </p>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center gap-1.5 rounded-full border border-red-500/15 bg-red-500/10 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-red-400"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-red-500"
                    ></span>

                    {{ data?.total ?? 0 }} Transactions
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-3 sm:p-4">
            <div class="space-y-2">
                <div
                    v-for="n in 5"
                    :key="n"
                    class="overflow-hidden rounded-xl border border-white/[0.05] bg-[#0b0b0b] p-4"
                >
                    <div class="animate-pulse">
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-1 h-9 w-9 shrink-0 rounded-lg bg-white/[0.06]"
                            ></div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex gap-2">
                                    <div
                                        class="h-3 w-40 rounded bg-white/[0.07]"
                                    ></div>

                                    <div
                                        class="h-5 w-16 rounded-full bg-white/[0.05]"
                                    ></div>

                                    <div
                                        class="h-5 w-20 rounded-full bg-white/[0.05]"
                                    ></div>
                                </div>

                                <div
                                    class="h-2.5 w-3/4 rounded bg-white/[0.05]"
                                ></div>

                                <div
                                    class="h-2.5 w-1/2 rounded bg-white/[0.04]"
                                ></div>

                                <div
                                    class="h-2 w-1/3 rounded bg-white/[0.04]"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div v-else>
            <!-- Empty State -->
            <div
                v-if="!sortedTransactions.length"
                class="flex min-h-[220px] flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div
                    class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-white/[0.06] bg-white/[0.03]"
                >
                    <i
                        class="fas fa-exchange-alt text-lg text-gray-700"
                    ></i>
                </div>

                <p
                    class="text-xs font-black uppercase tracking-wider text-gray-500"
                >
                    No Transactions
                </p>

                <p class="mt-1 text-[10px] text-gray-700">
                    No season transactions are available.
                </p>
            </div>

            <!-- Transaction List -->
            <div v-else class="divide-y divide-white/[0.04]">
                <div
                    v-for="transaction in sortedTransactions"
                    :key="transaction.id"
                    class="group min-w-0 bg-black p-3 transition-colors duration-200 hover:bg-[#0b0b0b] sm:p-4"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <!-- Transaction Icon -->
                        <div class="shrink-0 pt-1">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.03] transition group-hover:border-red-500/20 group-hover:bg-red-500/[0.05]"
                            >
                                <i
                                    :class="
                                        getTransactionIcon(
                                            transaction.status
                                        )
                                    "
                                    class="text-sm text-gray-500 group-hover:text-red-400"
                                ></i>
                            </div>
                        </div>

                        <!-- Transaction -->
                        <div class="min-w-0 flex-1">
                            <div
                                class="flip-card-container"
                                :class="{
                                    'is-flipped':
                                        flippedCards[transaction.id],
                                }"
                                @click="togglePlayerCard(transaction.id)"
                            >
                                <!-- FRONT -->
                                <div
                                    class="flip-card-front cursor-pointer rounded-lg border border-white/[0.05] bg-[#0d0d0d] p-3 transition-colors hover:border-white/[0.09]"
                                >
                                    <!-- Player Header -->
                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
                                        <!-- Player -->
                                        <span
                                            class="mr-1 min-w-0 max-w-full text-sm font-black leading-tight text-white"
                                        >
                                            {{ transaction.player_name }}

                                            <sup
                                                class="ml-0.5 text-[9px] font-black text-yellow-500"
                                            >
                                                {{
                                                    Math.round(
                                                        transaction.overall_rating ??
                                                            0
                                                    )
                                                }}
                                            </sup>
                                        </span>

                                        <!-- Age -->
                                        <span
                                            class="text-[10px] font-semibold text-gray-600"
                                        >
                                            {{ transaction.age }}
                                        </span>

                                        <!-- Position -->
                                        <span
                                            class="rounded-md border border-white/[0.06] bg-white/[0.03] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gray-500"
                                        >
                                            {{ transaction.position }}
                                        </span>

                                        <!-- Role -->
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase"
                                            :class="
                                                roleBadgeClass(
                                                    transaction.player_role
                                                )
                                            "
                                        >
                                            {{ transaction.player_role }}
                                        </span>

                                        <!-- Status -->
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase"
                                            :class="
                                                getStatusBadgeClass(
                                                    transaction.status
                                                )
                                            "
                                        >
                                            {{
                                                formatStatus(
                                                    transaction.status
                                                )
                                            }}
                                        </span>

                                        <!-- Draft Status -->
                                        <span
                                            class="rounded-full border border-white/[0.06] bg-white/[0.03] px-2 py-0.5 text-[9px] font-bold text-gray-500"
                                        >
                                            <template
                                                v-if="
                                                    transaction.draft_status ===
                                                    'Undrafted'
                                                "
                                            >
                                                S{{
                                                    transaction.draft_season_id
                                                }}
                                                Undrafted
                                            </template>

                                            <template v-else>
                                                {{
                                                    transaction.draft_status
                                                }}

                                                <span
                                                    v-if="
                                                        transaction.drafted_team_abbre
                                                    "
                                                    class="text-gray-700"
                                                >
                                                    ({{
                                                        transaction.drafted_team_abbre
                                                    }})
                                                </span>
                                            </template>
                                        </span>

                                        <!-- Awards -->
                                        <button
                                            v-if="transaction.awards_info"
                                            type="button"
                                            class="flex h-6 w-6 items-center justify-center rounded-md border border-yellow-500/10 bg-yellow-500/[0.05] text-yellow-500 transition hover:border-yellow-500/30 hover:bg-yellow-500/10"
                                            :title="`${transaction.player_name} has ${parseAwards(transaction.awards_info).length} awards`"
                                            @click.stop="
                                                togglePlayerCard(
                                                    transaction.id
                                                )
                                            "
                                        >
                                            <i
                                                class="fas fa-award text-[10px]"
                                            ></i>
                                        </button>
                                    </div>

                                    <!-- Details -->
                                    <p
                                        v-if="transaction.details"
                                        class="mt-3 text-xs leading-relaxed text-gray-400"
                                    >
                                        {{ transaction.details }}
                                    </p>

                                    <!-- Team Movement -->
                                    <template
                                        v-if="
                                            transaction.status !==
                                                'star player change' &&
                                            transaction.status !==
                                                'role change'
                                        "
                                    >
                                        <div
                                            class="mt-3 flex flex-col gap-1.5 border-t border-white/[0.04] pt-2.5 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div
                                                class="flex min-w-0 flex-wrap items-center gap-1.5 text-[10px]"
                                            >
                                                <span
                                                    class="truncate text-gray-600"
                                                >
                                                    {{
                                                        transaction.from_team_name
                                                    }}
                                                </span>

                                                <i
                                                    class="fas fa-arrow-right text-[8px] text-red-500/60"
                                                ></i>

                                                <b
                                                    class="truncate text-gray-300"
                                                >
                                                    {{
                                                        transaction.to_team_name
                                                    }}
                                                </b>
                                            </div>

                                            <p
                                                v-if="
                                                    transaction.status ===
                                                        'waived' &&
                                                    transaction.current_team_name !==
                                                        'Free Agent'
                                                "
                                                class="text-[9px] font-semibold text-gray-600"
                                            >
                                                Current:
                                                <span class="text-gray-400">
                                                    {{
                                                        transaction.current_team_city
                                                    }}
                                                    {{
                                                        transaction.current_team_name
                                                    }}
                                                </span>
                                            </p>
                                        </div>
                                    </template>

                                    <!-- Source -->
                                    <div
                                        class="mt-2 flex min-w-0 items-center gap-1 text-[9px] text-gray-700"
                                    >
                                        <span
                                            class="font-bold uppercase tracking-wider"
                                        >
                                            Source
                                        </span>

                                        <span class="text-gray-800">•</span>

                                        <span
                                            class="truncate text-blue-500/70"
                                        >
                                            {{ getSourceTeam(transaction) }}
                                        </span>
                                    </div>

                                    <!-- Flip Hint -->
                                    <div
                                        class="mt-2 flex items-center gap-1 text-[8px] font-bold uppercase tracking-wider text-gray-800"
                                    >
                                        <i class="fas fa-sync-alt text-[7px]"></i>
                                        Click for career achievements
                                    </div>
                                </div>

                                <!-- BACK -->
                                <div
                                    class="flip-card-back cursor-pointer rounded-lg border border-yellow-500/10 bg-gradient-to-br from-[#151515] to-[#090909] p-3"
                                >
                                    <div
                                        class="flex min-h-full flex-col justify-between gap-4"
                                    >
                                        <div>
                                            <div
                                                class="mb-3 flex items-center justify-between gap-3 border-b border-white/[0.06] pb-2"
                                            >
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-[8px] font-black uppercase tracking-[0.2em] text-yellow-500/70"
                                                    >
                                                        Career
                                                    </p>

                                                    <h3
                                                        class="truncate text-sm font-black text-white"
                                                    >
                                                        {{
                                                            transaction.player_name
                                                        }}
                                                    </h3>
                                                </div>

                                                <i
                                                    class="fas fa-award shrink-0 text-yellow-500"
                                                ></i>
                                            </div>

                                            <div
                                                class="flex flex-wrap gap-1.5"
                                            >
                                                <template
                                                    v-if="
                                                        transaction.awards_info
                                                    "
                                                >
                                                    <span
                                                        v-for="(
                                                            award, index
                                                        ) in parseAwards(
                                                            transaction.awards_info
                                                        )"
                                                        :key="index"
                                                        class="inline-flex items-center rounded-full border border-white/[0.06] px-2 py-1 text-[9px] font-bold"
                                                        :class="
                                                            getAwardBadgeClass(
                                                                award
                                                            )
                                                        "
                                                    >
                                                        <i
                                                            :class="
                                                                getAwardIcon(
                                                                    award
                                                                )
                                                            "
                                                            class="mr-1"
                                                        ></i>

                                                        {{
                                                            formatAwardText(
                                                                award
                                                            )
                                                        }}
                                                    </span>
                                                </template>

                                                <span
                                                    v-else
                                                    class="text-[10px] italic text-gray-600"
                                                >
                                                    No awards yet in career
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="flex items-center gap-1 text-[8px] font-bold uppercase tracking-wider text-gray-700"
                                        >
                                            <i
                                                class="fas fa-arrow-left text-[7px]"
                                            ></i>
                                            Click to return
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="data?.total"
                class="border-t border-white/[0.06] bg-[#080808] px-3 py-2"
            >
                <div class="w-full overflow-x-auto">
                    <Paginator
                        :page_number="search.page_num"
                        :total_rows="data.total ?? 0"
                        :itemsperpage="search.itemsperpage"
                        @page_num="handlePagination"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import Paginator from "@/Components/Paginator.vue";

import {
    roleBadgeClass,
    getTransactionIcon,
    getStatusBadgeClass,
    getAwardBadgeClass,
    getAwardIcon,
    formatStatus,
    formatAwardText,
} from "@/Utility/Formatter";

const props = defineProps({
    showTitle: {
        type: Boolean,
        default: true,
    },
});

const data = ref({
    transactions: [],
    total: 0,
    total_pages: 0,
});

const loading = ref(true);

const flippedCards = ref({});

const search = ref({
    page_num: 1,
    search: "",
    itemsperpage: 10,
});

const getSeasonTransactions = async () => {
    try {
        loading.value = true;

        const response = await axios.post(
            route("season.transactions"),
            search.value
        );

        data.value = {
            transactions: response.data?.transactions ?? [],
            total: response.data?.total ?? 0,
            total_pages: response.data?.total_pages ?? 0,
            ...response.data,
        };
    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Unable to Load Transactions",
            text: "Failed to fetch season transactions. Please try again later.",
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
    getSeasonTransactions();
};

const sortedTransactions = computed(() => {
    const transactions = data.value?.transactions ?? [];

    return [...transactions].sort(
        (a, b) => new Date(b.date) - new Date(a.date)
    );
});

const getSourceTeam = (transaction) => {
    const playerSlug = (transaction.player_name ?? "player")
        .replace(/\s+/g, "-")
        .toLowerCase();

    const teamCity =
        transaction.to_team_city === "None"
            ? "free-agent"
            : transaction.to_team_city ?? "free-agent";

    const teamName =
        transaction.to_team_name === "Free Agent"
            ? ""
            : transaction.to_team_name ?? "";

    const teamSlug = `${teamCity} ${teamName}`
        .trim()
        .replace(/\s+/g, "-")
        .toLowerCase();

    const statusSlug = (transaction.status ?? "transaction")
        .replace(/\s+/g, "-")
        .toLowerCase();

    const domain = `${teamSlug}.com`;

    return `https://${domain}/news/${transaction.season_id ?? 0}/${playerSlug}-${teamSlug}-${statusSlug}`;
};

const togglePlayerCard = (id) => {
    flippedCards.value[id] = !flippedCards.value[id];
};

const parseAwards = (awardsInfo) => {
    if (!awardsInfo) {
        return [];
    }

    return awardsInfo
        .split(",")
        .map((award) => award.trim())
        .filter(Boolean);
};

onMounted(() => {
    getSeasonTransactions();
});
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Flip Card
|--------------------------------------------------------------------------
*/

.flip-card-container {
    position: relative;
    min-height: 150px;
    perspective: 1200px;
}

.flip-card-front,
.flip-card-back {
    width: 100%;
    min-height: 150px;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    transition:
        transform 0.45s cubic-bezier(0.2, 0.7, 0.2, 1),
        opacity 0.45s ease;
}

.flip-card-front {
    position: relative;
    transform: rotateY(0deg);
}

.flip-card-back {
    position: absolute;
    inset: 0;
    transform: rotateY(180deg);
}

.is-flipped .flip-card-front {
    transform: rotateY(-180deg);
}

.is-flipped .flip-card-back {
    transform: rotateY(0deg);
}

/*
|--------------------------------------------------------------------------
| Skeleton
|--------------------------------------------------------------------------
*/

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
    background: #292929;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #404040;
}

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

::selection {
    background: rgba(239, 68, 68, 0.2);
    color: white;
}
</style>