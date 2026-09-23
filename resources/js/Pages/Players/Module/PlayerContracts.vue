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
                                    d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 7h6M9 11h6M9 15h3"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    class="truncate text-sm font-semibold tracking-wide text-white"
                                >
                                    Player Contracts
                                </h2>

                                <span
                                    v-if="transactions.length > 0"
                                    class="rounded-full border border-white/[0.08] bg-white/[0.05] px-2 py-0.5 text-[10px] font-semibold text-slate-300"
                                >
                                    {{ transactions.length }}
                                </span>
                            </div>

                            <p class="mt-0.5 text-[11px] text-slate-500">
                                Contract history, salary and option details
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
                            Active
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.06] bg-white/[0.025] px-2.5 py-1"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-amber-400"
                            ></span>
                            Option
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.06] bg-white/[0.025] px-2.5 py-1"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-sky-400"
                            ></span>
                            Protected
                        </span>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto scrollbar-thin">
                <table
                    class="w-full min-w-[1550px] border-collapse text-xs"
                >
                    <!-- Group Header -->
                    <thead>
                        <tr
                            class="border-b border-white/[0.05] bg-[#080a0d] text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-600"
                        >
                            <th
                                colspan="3"
                                class="sticky left-0 z-40 bg-[#080a0d] px-4 py-2 text-left"
                            >
                                Contract
                            </th>

                            <th
                                colspan="3"
                                class="px-4 py-2 text-left"
                            >
                                Financials
                            </th>

                            <th
                                colspan="1"
                                class="px-4 py-2 text-left"
                            >
                                Team
                            </th>

                            <th
                                colspan="3"
                                class="px-4 py-2 text-left"
                            >
                                Options & Protection
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
                                class="sticky left-0 z-30 w-[105px] bg-[#0d1014] px-4 py-3 text-left"
                            >
                                Season
                            </th>

                            <th
                                class="sticky left-[105px] z-30 w-[190px] bg-[#0d1014] px-4 py-3 text-left"
                            >
                                Name
                            </th>

                            <th
                                class="sticky left-[295px] z-30 w-[140px] bg-[#0d1014] px-4 py-3 text-left"
                            >
                                Type
                            </th>

                            <th
                                class="w-[155px] px-4 py-3 text-right"
                            >
                                Salary
                            </th>

                            <th
                                class="w-[135px] px-4 py-3 text-right"
                            >
                                Contract Yrs.
                            </th>

                            <th
                                class="w-[145px] px-4 py-3 text-right"
                            >
                                Yrs Remaining
                            </th>

                            <th
                                class="w-[210px] px-4 py-3 text-left"
                            >
                                Team
                            </th>

                            <th
                                class="w-[145px] px-4 py-3 text-left"
                            >
                                Player Option
                            </th>

                            <th
                                class="w-[135px] px-4 py-3 text-left"
                            >
                                Team Option
                            </th>

                            <th
                                class="w-[145px] px-4 py-3 text-left"
                            >
                                Trade Clause
                            </th>

                            <th
                                class="w-[140px] px-4 py-3 text-left"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- Loading -->
                        <template v-if="loading">
                            <tr
                                v-for="index in 6"
                                :key="`loading-${index}`"
                                class="border-b border-white/[0.04]"
                            >
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10] px-4 py-4"
                                >
                                    <SkeletonBlock width="w-14" />
                                </td>

                                <td
                                    class="sticky left-[105px] z-20 bg-[#0b0d10] px-4 py-4"
                                >
                                    <SkeletonBlock width="w-32" />
                                </td>

                                <td
                                    class="sticky left-[295px] z-20 bg-[#0b0d10] px-4 py-4"
                                >
                                    <SkeletonBlock width="w-20" />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock
                                        width="w-20"
                                        class="ml-auto"
                                    />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock
                                        width="w-16"
                                        class="ml-auto"
                                    />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock
                                        width="w-20"
                                        class="ml-auto"
                                    />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock width="w-28" />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock width="w-16" />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock width="w-16" />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock width="w-16" />
                                </td>

                                <td class="px-4 py-4">
                                    <SkeletonBlock width="w-20" />
                                </td>
                            </tr>
                        </template>

                        <!-- Data -->
                        <template
                            v-else-if="
                                transactions &&
                                transactions.length > 0
                            "
                        >
                            <tr
                                v-for="(transaction, index) in transactions"
                                :key="transaction.id ?? index"
                                class="group border-b border-white/[0.045] transition-colors duration-150"
                                :class="rowClass(transaction)"
                                :style="teamBackgroundStyle(transaction)"
                            >
                                <!-- Season -->
                                <td
                                    class="sticky left-0 z-20 bg-[#0b0d10]/95 px-4 py-3.5 backdrop-blur-sm transition-colors group-hover:bg-[#11151a]/95"
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

                                <!-- Name -->
                                <td
                                    class="sticky left-[105px] z-20 bg-[#0b0d10]/95 px-4 py-3.5 backdrop-blur-sm transition-colors group-hover:bg-[#11151a]/95"
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
                                                Contract #{{
                                                    transaction.id
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contract Type -->
                                <td
                                    class="sticky left-[295px] z-20 bg-[#0b0d10]/95 px-4 py-3.5 backdrop-blur-sm transition-colors group-hover:bg-[#11151a]/95"
                                >
                                    <span
                                        class="inline-flex items-center rounded-full border border-violet-400/20 bg-violet-400/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-violet-300"
                                    >
                                        {{
                                            transaction.contract_type ??
                                            "Unknown"
                                        }}
                                    </span>
                                </td>

                                <!-- Salary -->
                                <td class="px-4 py-3.5 text-right">
                                    <div
                                        class="font-semibold tabular-nums text-white"
                                    >
                                        {{
                                            moneyFormatter(
                                                transaction.salary ?? 0
                                            )
                                        }}
                                    </div>

                                    <div
                                        class="mt-0.5 text-[9px] uppercase tracking-wide text-slate-600"
                                    >
                                        Annual Salary
                                    </div>
                                </td>

                                <!-- Contract Years -->
                                <td class="px-4 py-3.5 text-right">
                                    <div
                                        class="font-semibold tabular-nums text-slate-200"
                                    >
                                        {{
                                            transaction.contract_years ??
                                            0
                                        }}
                                    </div>

                                    <div
                                        class="text-[9px] text-slate-600"
                                    >
                                        years
                                    </div>
                                </td>

                                <!-- Years Remaining -->
                                <td class="px-4 py-3.5 text-right">
                                    <div
                                        class="inline-flex min-w-[72px] flex-col items-end"
                                    >
                                        <span
                                            class="rounded-lg border px-2.5 py-1 font-semibold tabular-nums"
                                            :class="
                                                remainingYearsClass(
                                                    transaction
                                                )
                                            "
                                        >
                                            {{
                                                remainingYears(
                                                    transaction
                                                )
                                            }}
                                        </span>

                                        <span
                                            class="mt-1 text-[9px] text-slate-600"
                                        >
                                            years remaining
                                        </span>
                                    </div>
                                </td>

                                <!-- Team -->
                                <td class="px-4 py-3.5">
                                    <div
                                        class="flex items-center gap-2.5"
                                    >
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/[0.07] bg-white/[0.035]"
                                        >
                                            <svg
                                                class="h-4 w-4 text-slate-500"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.6"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M4 19h16M6 19V9l6-4 6 4v10M9 19v-6h6v6"
                                                />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <div
                                                class="max-w-[150px] truncate font-medium text-slate-300"
                                                :title="
                                                    transaction.team_name ??
                                                    'Free Agent'
                                                "
                                            >
                                                {{
                                                    transaction.team_name ??
                                                    "Free Agent"
                                                }}
                                            </div>

                                            <div
                                                v-if="
                                                    !transaction.team_name
                                                "
                                                class="text-[9px] text-slate-600"
                                            >
                                                Unassigned
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Player Option -->
                                <td class="px-4 py-3.5">
                                    <OptionBadge
                                        label="Player"
                                        :enabled="
                                            transaction.player_option !=
                                            0
                                        "
                                    />
                                </td>

                                <!-- Team Option -->
                                <td class="px-4 py-3.5">
                                    <OptionBadge
                                        label="Team"
                                        :enabled="
                                            transaction.team_option !=
                                            0
                                        "
                                    />
                                </td>

                                <!-- Trade Clause -->
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-semibold"
                                        :class="
                                            transaction.no_trade_clause !=
                                            0
                                                ? 'border-sky-400/20 bg-sky-400/10 text-sky-300'
                                                : 'border-white/[0.07] bg-white/[0.025] text-slate-500'
                                        "
                                    >
                                        <svg
                                            v-if="
                                                transaction.no_trade_clause !=
                                                0
                                            "
                                            class="h-3 w-3"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4Z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m9 12 2 2 4-4"
                                            />
                                        </svg>

                                        {{
                                            transaction.no_trade_clause !=
                                            0
                                                ? "Protected"
                                                : "None"
                                        }}
                                    </span>
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

                        <!-- Empty -->
                        <tr v-else>
                            <td
                                colspan="11"
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
                                                d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 7h6M9 11h6M9 15h3"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-sm font-medium text-slate-300"
                                    >
                                        No contract history
                                    </p>

                                    <p
                                        class="mt-1 text-xs text-slate-600"
                                    >
                                        No recorded contracts were found
                                        for this player.
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
import { moneyFormatter } from "@/Utility/Formatter.js";

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
| Small reusable UI components
|--------------------------------------------------------------------------
*/

const SkeletonBlock = {
    props: {
        width: {
            type: String,
            default: "w-20",
        },
    },

    template: `
        <div
            :class="[
                'h-3 animate-pulse rounded bg-white/[0.07]',
                width
            ]"
        ></div>
    `,
};

const OptionBadge = {
    props: {
        label: String,
        enabled: Boolean,
    },

    template: `
        <span
            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-semibold"
            :class="
                enabled
                    ? 'border-amber-400/20 bg-amber-400/10 text-amber-300'
                    : 'border-white/[0.07] bg-white/[0.025] text-slate-500'
            "
        >
            <span
                class="h-1.5 w-1.5 rounded-full"
                :class="enabled ? 'bg-amber-400' : 'bg-slate-600'"
            ></span>

            {{ label }} {{ enabled ? 'Option' : '—' }}
        </span>
    `,
};

/*
|--------------------------------------------------------------------------
| Player
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

/*
|--------------------------------------------------------------------------
| Contract
|--------------------------------------------------------------------------
*/

const remainingYears = (transaction) => {
    if (
        String(transaction?.status ?? "").toLowerCase() !==
        "signed"
    ) {
        return "0 yrs.";
    }

    return `${Number(transaction?.years_remaining ?? 0)} yrs.`;
};

const remainingYearsClass = (transaction) => {
    const status = String(
        transaction?.status ?? ""
    ).toLowerCase();

    if (status !== "signed") {
        return "border-white/[0.07] bg-white/[0.025] text-slate-500";
    }

    const years = Number(
        transaction?.years_remaining ?? 0
    );

    if (years <= 0) {
        return "border-rose-400/20 bg-rose-400/10 text-rose-300";
    }

    if (years === 1) {
        return "border-amber-400/20 bg-amber-400/10 text-amber-300";
    }

    return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";
};

/*
|--------------------------------------------------------------------------
| Status Styling
|--------------------------------------------------------------------------
*/

const statusClasses = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("signed") ||
        value.includes("active") ||
        value.includes("extended")
    ) {
        return "border-emerald-400/20 bg-emerald-400/10 text-emerald-300";
    }

    if (
        value.includes("expired") ||
        value.includes("released") ||
        value.includes("waived")
    ) {
        return "border-rose-400/20 bg-rose-400/10 text-rose-300";
    }

    if (
        value.includes("option") ||
        value.includes("pending")
    ) {
        return "border-amber-400/20 bg-amber-400/10 text-amber-300";
    }

    if (
        value.includes("trade") ||
        value.includes("traded")
    ) {
        return "border-sky-400/20 bg-sky-400/10 text-sky-300";
    }

    return "border-white/[0.08] bg-white/[0.04] text-slate-400";
};

const statusDotClass = (status) => {
    const value = String(status ?? "").toLowerCase();

    if (
        value.includes("signed") ||
        value.includes("active") ||
        value.includes("extended")
    ) {
        return "bg-emerald-400";
    }

    if (
        value.includes("expired") ||
        value.includes("released") ||
        value.includes("waived")
    ) {
        return "bg-rose-400";
    }

    if (
        value.includes("option") ||
        value.includes("pending")
    ) {
        return "bg-amber-400";
    }

    if (
        value.includes("trade") ||
        value.includes("traded")
    ) {
        return "bg-sky-400";
    }

    return "bg-slate-500";
};

/*
|--------------------------------------------------------------------------
| Row Styling
|--------------------------------------------------------------------------
*/

const transactionDotClass = (transaction) => {
    const status = String(
        transaction?.status ?? ""
    ).toLowerCase();

    if (
        status.includes("signed") ||
        status.includes("active") ||
        status.includes("extended")
    ) {
        return "bg-emerald-400";
    }

    if (
        status.includes("expired") ||
        status.includes("released") ||
        status.includes("waived")
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
        status.includes("expired") ||
        status.includes("released") ||
        status.includes("waived")
    ) {
        return "hover:bg-rose-400/[0.025]";
    }

    if (
        status.includes("signed") ||
        status.includes("active") ||
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
| Team Colors
|--------------------------------------------------------------------------
*/

const teamBackgroundStyle = (transaction) => {
    const primary = transaction?.team_primary_colors;
    const secondary = transaction?.team_secondary_colors;

    if (!primary && !secondary) {
        return {};
    }

    const primaryColors = String(primary ?? "")
        .split(",")
        .map((color) => color.trim())
        .filter(Boolean);

    const secondaryColors = String(
        secondary ?? ""
    )
        .split(",")
        .map((color) => color.trim())
        .filter(Boolean);

    const colors = [];

    primaryColors.forEach((color, index) => {
        colors.push(`#${color}`);

        if (secondaryColors[index]) {
            colors.push(`#${secondaryColors[index]}`);
        }
    });

    if (!colors.length) {
        return {};
    }

    return {
        "--team-gradient": `linear-gradient(90deg, ${colors.join(
            ", "
        )})`,
    };
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchPlayerTransactions = async () => {
    loading.value = true;

    try {
        const response = await axios.post(
            route("players.contracts.history"),
            {
                player_id: player_id.value,
            }
        );

        transactions.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching player contract history:",
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
| Player Change
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