<template>
    <div class="w-full">
        <section
            class="overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0b0d10] shadow-[0_20px_70px_rgba(0,0,0,0.4)]"
        >
            <!-- Header -->
            <div
                class="border-b border-white/[0.07] bg-gradient-to-r from-white/[0.04] via-transparent to-transparent"
            >
                <div
                    class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/[0.08] bg-white/[0.04]"
                        >
                            <svg
                                class="h-5 w-5 text-slate-300"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7h8M8 11h8M8 15h5"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 3v4h4"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    class="truncate text-sm font-semibold tracking-wide text-white"
                                >
                                    Player Transactions
                                </h2>

                                <span
                                    v-if="transactions.length > 0"
                                    class="rounded-full border border-white/[0.08] bg-white/[0.05] px-2 py-0.5 text-[10px] font-semibold text-slate-300"
                                >
                                    {{ transactions.length }}
                                </span>
                            </div>

                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Complete player transaction history
                            </p>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div
                        class="flex flex-wrap items-center gap-2 text-[10px] text-slate-500"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.06] bg-white/[0.025] px-2.5 py-1"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-400"
                            ></span>
                            Incoming
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.06] bg-white/[0.025] px-2.5 py-1"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-rose-400"
                            ></span>
                            Outgoing
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.06] bg-white/[0.025] px-2.5 py-1"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-slate-500"
                            ></span>
                            Transaction
                        </span>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto scrollbar-thin">
                <table
                    class="w-full min-w-[1250px] border-collapse text-xs"
                >
                    <!-- Group Header -->
                    <thead>
                        <tr
                            class="border-b border-white/[0.05] bg-[#080a0d] text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-600"
                        >
                            <th
                                colspan="3"
                                class="sticky left-0 z-30 bg-[#080a0d] px-4 py-2 text-left"
                            >
                                Player
                            </th>

                            <th
                                colspan="1"
                                class="px-4 py-2 text-left"
                            >
                                Transaction
                            </th>

                            <th
                                colspan="2"
                                class="px-4 py-2 text-left"
                            >
                                Team Movement
                            </th>

                            <th
                                colspan="1"
                                class="px-4 py-2 text-left"
                            >
                                Status
                            </th>
                        </tr>

                        <!-- Column Header -->
                        <tr
                            class="border-b border-white/[0.07] bg-[#0d1014] text-[10px] font-semibold uppercase tracking-wider text-slate-500"
                        >
                            <th
                                class="sticky left-0 z-30 w-[110px] bg-[#0d1014] px-4 py-3 text-left"
                            >
                                Season
                            </th>

                            <th
                                class="sticky left-[110px] z-30 w-[190px] bg-[#0d1014] px-4 py-3 text-left"
                            >
                                Player Name
                            </th>

                            <th
                                class="sticky left-[300px] z-30 w-[130px] bg-[#0d1014] px-4 py-3 text-left"
                            >
                                Role
                            </th>

                            <th
                                class="w-[430px] px-4 py-3 text-left"
                            >
                                Transfer Details
                            </th>

                            <th
                                class="w-[180px] px-4 py-3 text-left"
                            >
                                Old Team
                            </th>

                            <th
                                class="w-[180px] px-4 py-3 text-left"
                            >
                                New Team
                            </th>

                            <th
                                class="w-[150px] px-4 py-3 text-left"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- Loading Skeleton -->
                        <template v-if="loading">
                            <tr
                                v-for="index in 6"
                                :key="`loading-${index}`"
                                class="border-b border-white/[0.04]"
                            >
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10] px-4 py-4"
                                >
                                    <div
                                        class="h-3 w-16 animate-pulse rounded bg-white/[0.07]"
                                    ></div>
                                </td>

                                <td
                                    class="sticky left-[110px] z-20 bg-[#0b0d10] px-4 py-4"
                                >
                                    <div
                                        class="h-3 w-32 animate-pulse rounded bg-white/[0.07]"
                                    ></div>
                                </td>

                                <td
                                    class="sticky left-[300px] z-20 bg-[#0b0d10] px-4 py-4"
                                >
                                    <div
                                        class="h-5 w-20 animate-pulse rounded-full bg-white/[0.07]"
                                    ></div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="h-3 w-72 animate-pulse rounded bg-white/[0.07]"
                                    ></div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="h-3 w-28 animate-pulse rounded bg-white/[0.07]"
                                    ></div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="h-3 w-28 animate-pulse rounded bg-white/[0.07]"
                                    ></div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="h-5 w-20 animate-pulse rounded-full bg-white/[0.07]"
                                    ></div>
                                </td>
                            </tr>
                        </template>

                        <!-- Transactions -->
                        <template
                            v-else-if="transactions && transactions.length"
                        >
                            <tr
                                v-for="(transaction, index) in transactions"
                                :key="transaction.id ?? index"
                                class="group border-b border-white/[0.045] transition-colors duration-150"
                                :class="rowClass(transaction)"
                            >
                                <!-- Season -->
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10] px-4 py-3.5 transition-colors group-hover:bg-[#11151a]"
                                >
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                transactionDotClass(
                                                    transaction
                                                )
                                            "
                                        ></span>

                                        <span
                                            class="font-medium text-slate-300"
                                        >
                                            Season
                                            {{ transaction.season_id }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Player -->
                                <td
                                    class="sticky left-[110px] z-20 bg-[#0b0d10] px-4 py-3.5 transition-colors group-hover:bg-[#11151a]"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/[0.08] bg-white/[0.04] text-[10px] font-bold text-slate-400"
                                        >
                                            {{
                                                playerInitials(
                                                    transaction.player_name
                                                )
                                            }}
                                        </div>

                                        <div class="min-w-0">
                                            <div
                                                class="truncate font-semibold text-white"
                                            >
                                                {{
                                                    transaction.player_name ??
                                                    "Unknown Player"
                                                }}
                                            </div>

                                            <div
                                                v-if="transaction.id"
                                                class="mt-0.5 text-[9px] text-slate-600"
                                            >
                                                Transaction #{{
                                                    transaction.id
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td
                                    class="sticky left-[300px] z-20 bg-[#0b0d10] px-4 py-3.5 transition-colors group-hover:bg-[#11151a]"
                                >
                                    <span
                                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold capitalize"
                                        :class="
                                            roleClasses(
                                                transaction.latest_role
                                            )
                                        "
                                    >
                                        {{
                                            transaction.latest_role ??
                                            "Unknown"
                                        }}
                                    </span>
                                </td>

                                <!-- Transfer Details -->
                                <td class="px-4 py-3.5 align-top">
                                    <div
                                        class="max-w-[430px] whitespace-normal break-words leading-5 text-slate-300"
                                    >
                                        <div
                                            class="flex items-start gap-2"
                                        >
                                            <span
                                                class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-600"
                                            ></span>

                                            <span>
                                                {{
                                                    transaction.merged_details ??
                                                    "No transaction details available."
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Old Team -->
                                <td class="px-4 py-3.5">
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025]"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5 text-slate-500"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4 19h16M6 19V9l6-4 6 4v10M9 19v-6h6v6"
                                                />
                                            </svg>
                                        </div>

                                        <span
                                            class="max-w-[130px] truncate text-slate-400"
                                            :title="
                                                transaction.from_team_name ??
                                                'Free Agent'
                                            "
                                        >
                                            {{
                                                transaction.from_team_name ??
                                                "Free Agent"
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- New Team -->
                                <td class="px-4 py-3.5">
                                    <div
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-white/[0.06] bg-white/[0.025]"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5 text-slate-500"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4 19h16M6 19V9l6-4 6 4v10M9 19v-6h6v6"
                                                />
                                            </svg>
                                        </div>

                                        <span
                                            class="max-w-[130px] truncate font-medium text-slate-300"
                                            :title="
                                                transaction.to_team_name ??
                                                'Free Agent'
                                            "
                                        >
                                            {{
                                                transaction.to_team_name ??
                                                "Free Agent"
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-semibold capitalize"
                                        :class="
                                            statusClasses(
                                                transaction.status
                                            )
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                statusDotClass(
                                                    transaction.status
                                                )
                                            "
                                        ></span>

                                        {{
                                            transaction.status ??
                                            "Unknown"
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr v-else>
                            <td
                                colspan="7"
                                class="px-6 py-16 text-center"
                            >
                                <div
                                    class="mx-auto flex max-w-sm flex-col items-center"
                                >
                                    <div
                                        class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-white/[0.07] bg-white/[0.025]"
                                    >
                                        <svg
                                            class="h-6 w-6 text-slate-600"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M8 7h8M8 11h8M8 15h5"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-sm font-medium text-slate-300"
                                    >
                                        No transactions found
                                    </p>

                                    <p
                                        class="mt-1 text-xs text-slate-600"
                                    >
                                        This player has no recorded
                                        transactions.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";

const props = defineProps({
    player_id: {
        type: Number,
        required: true,
    },
});

const transactions = ref([]);
const player_id = ref(props.player_id);
const loading = ref(true);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const playerInitials = (name) => {
    if (!name) return "?";

    const parts = String(name)
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }

    return (
        parts[0].charAt(0) +
        parts[parts.length - 1].charAt(0)
    ).toUpperCase();
};

const roleClasses = (role) => {
    switch (String(role ?? "").toLowerCase()) {
        case "starter":
            return "border-blue-400/20 bg-blue-400/10 text-blue-300";

        case "star player":
            return "border-amber-400/20 bg-amber-400/10 text-amber-300";

        case "all star":
        case "all-star":
            return "border-yellow-400/20 bg-yellow-400/10 text-yellow-300";

        case "role player":
            return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";

        case "bench":
            return "border-slate-400/15 bg-slate-400/10 text-slate-300";

        case "reserved bench":
            return "border-violet-400/20 bg-violet-400/10 text-violet-300";

        default:
            return "border-white/[0.08] bg-white/[0.04] text-slate-400";
    }
};

const statusClasses = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("signed") ||
        value.includes("activated") ||
        value.includes("extended") ||
        value.includes("acquired")
    ) {
        return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";
    }

    if (
        value.includes("waived") ||
        value.includes("released") ||
        value.includes("retired")
    ) {
        return "border-rose-400/20 bg-rose-400/10 text-rose-300";
    }

    if (
        value.includes("trade") ||
        value.includes("traded")
    ) {
        return "border-sky-400/20 bg-sky-400/10 text-sky-300";
    }

    if (
        value.includes("injured") ||
        value.includes("hardship")
    ) {
        return "border-amber-400/20 bg-amber-400/10 text-amber-300";
    }

    return "border-white/[0.08] bg-white/[0.04] text-slate-400";
};

const statusDotClass = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("signed") ||
        value.includes("activated") ||
        value.includes("extended") ||
        value.includes("acquired")
    ) {
        return "bg-emerald-400";
    }

    if (
        value.includes("waived") ||
        value.includes("released") ||
        value.includes("retired")
    ) {
        return "bg-rose-400";
    }

    if (
        value.includes("trade") ||
        value.includes("traded")
    ) {
        return "bg-sky-400";
    }

    if (
        value.includes("injured") ||
        value.includes("hardship")
    ) {
        return "bg-amber-400";
    }

    return "bg-slate-500";
};

const transactionDotClass = (transaction) => {
    const status = String(
        transaction?.status ?? ""
    ).toLowerCase();

    if (
        status.includes("signed") ||
        status.includes("activated") ||
        status.includes("acquired") ||
        status.includes("extended")
    ) {
        return "bg-emerald-400";
    }

    if (
        status.includes("waived") ||
        status.includes("released") ||
        status.includes("retired")
    ) {
        return "bg-rose-400";
    }

    if (
        status.includes("trade") ||
        status.includes("traded")
    ) {
        return "bg-sky-400";
    }

    return "bg-slate-500";
};

const rowClass = (transaction) => {
    const status = String(
        transaction?.status ?? ""
    ).toLowerCase();

    if (
        status.includes("waived") ||
        status.includes("released") ||
        status.includes("retired")
    ) {
        return "hover:bg-rose-400/[0.025]";
    }

    if (
        status.includes("signed") ||
        status.includes("activated") ||
        status.includes("acquired") ||
        status.includes("extended")
    ) {
        return "hover:bg-emerald-400/[0.025]";
    }

    if (
        status.includes("trade") ||
        status.includes("traded")
    ) {
        return "hover:bg-sky-400/[0.025]";
    }

    return "hover:bg-white/[0.02]";
};

/*
|--------------------------------------------------------------------------
| Fetch Transactions
|--------------------------------------------------------------------------
*/

const fetchPlayerTransactions = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("players.season.transactions"),
            {
                player_id: player_id.value,
            }
        );

        transactions.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching player transactions:",
            error
        );

        transactions.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchPlayerTransactions();
});

/*
|--------------------------------------------------------------------------
| Player Changes
|--------------------------------------------------------------------------
*/

watch(
    () => props.player_id,
    (newPlayerId) => {
        if (!newPlayerId) return;

        player_id.value = newPlayerId;
        fetchPlayerTransactions();
    }
);
</script>

<style scoped>
.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.18) transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    height: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.18);
    border-radius: 999px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.3);
}
</style>