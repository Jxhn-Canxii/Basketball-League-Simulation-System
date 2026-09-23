<template>
    <div class="w-full">
        <!-- =========================================================
             HEADER
        ========================================================== -->
        <div
            class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-800 bg-gray-900"
                >
                    <i class="fa fa-id-badge text-[11px] text-gray-500"></i>
                </div>

                <div>
                    <h2 class="text-xs font-semibold text-gray-200">
                        Role History
                    </h2>

                    <p class="text-[9px] text-gray-600">
                        Player role changes throughout their career
                    </p>
                </div>
            </div>

            <div
                v-if="!loading && transactions.length"
                class="self-start sm:self-auto inline-flex items-center gap-1.5 rounded-md border border-gray-800 bg-gray-900 px-2.5 py-1.5"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>

                <span class="text-[9px] font-semibold text-gray-400">
                    {{ transactions.length }}
                    {{ transactions.length === 1 ? "record" : "records" }}
                </span>
            </div>
        </div>

        <!-- =========================================================
             LOADING
        ========================================================== -->
        <div
            v-if="loading"
            class="border-t border-gray-800"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-[10px]">
                    <thead>
                        <tr class="bg-gray-900">
                            <th
                                v-for="i in 6"
                                :key="`head-${i}`"
                                class="px-3 py-3"
                            >
                                <div
                                    class="h-2.5 animate-pulse rounded bg-gray-800"
                                ></div>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-800">
                        <tr
                            v-for="row in 5"
                            :key="`row-${row}`"
                        >
                            <td
                                v-for="column in 6"
                                :key="`cell-${row}-${column}`"
                                class="px-3 py-4"
                            >
                                <div
                                    class="h-3 animate-pulse rounded bg-gray-900"
                                ></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =========================================================
             EMPTY STATE
        ========================================================== -->
        <div
            v-else-if="!transactions.length"
            class="border-t border-gray-800 px-4 py-9 text-center"
        >
            <div
                class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border border-gray-800 bg-gray-900"
            >
                <i class="fa fa-history text-sm text-gray-600"></i>
            </div>

            <p class="text-xs font-semibold text-gray-400">
                No Role History
            </p>

            <p class="mt-1 text-[10px] text-gray-600">
                No recorded role changes are available for this player.
            </p>
        </div>

        <!-- =========================================================
             TABLE
        ========================================================== -->
        <div
            v-else
            class="border-t border-gray-800"
        >
            <div class="overflow-x-auto">
                <!--
                    Keep the table wide enough for all information.
                    On smaller containers the table scrolls horizontally
                    instead of compressing the Details column.
                -->
                <table
                    class="w-full min-w-[1150px] border-collapse text-[10px]"
                >
                    <!-- =================================================
                         GROUP HEADER
                    ================================================== -->
                    <thead>
                        <tr
                            class="border-b border-gray-800 bg-gray-950 text-[8px] uppercase tracking-[0.15em] text-gray-700"
                        >
                            <th
                                colspan="3"
                                class="border-r border-gray-800 px-4 py-2 text-left"
                            >
                                Player
                            </th>

                            <th
                                class="border-r border-gray-800 px-4 py-2 text-left"
                            >
                                Role Change
                            </th>

                            <th
                                class="border-r border-gray-800 px-4 py-2 text-left"
                            >
                                Team
                            </th>

                            <th class="px-4 py-2 text-left">
                                Status
                            </th>
                        </tr>

                        <!-- Column headers -->
                        <tr
                            class="bg-gray-900 text-[9px] uppercase tracking-wider text-gray-500"
                        >
                            <th
                                class="sticky left-0 z-20 bg-gray-900 px-4 py-3 text-left font-semibold whitespace-nowrap"
                            >
                                Season
                            </th>

                            <th
                                class="px-4 py-3 text-left font-semibold whitespace-nowrap"
                            >
                                Player Name
                            </th>

                            <th
                                class="px-4 py-3 text-left font-semibold whitespace-nowrap border-r border-gray-800"
                            >
                                Role
                            </th>

                            <th
                                class="px-4 py-3 text-left font-semibold min-w-[400px] border-r border-gray-800"
                            >
                                Details
                            </th>

                            <th
                                class="px-4 py-3 text-left font-semibold whitespace-nowrap border-r border-gray-800"
                            >
                                Team
                            </th>

                            <th
                                class="px-4 py-3 text-left font-semibold whitespace-nowrap"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>

                    <!-- =================================================
                         BODY
                    ================================================== -->
                    <tbody class="divide-y divide-gray-800/80">
                        <tr
                            v-for="transaction in transactions"
                            :key="transaction.id"
                            class="group cursor-pointer bg-gray-950 transition-colors duration-150 hover:bg-gray-900"
                            @click.prevent="
                                isViewModalOpen = transaction.season_id
                            "
                        >
                            <!-- Season -->
                            <td
                                class="sticky left-0 z-10 bg-gray-950 group-hover:bg-gray-900 px-4 py-3 whitespace-nowrap border-r border-gray-800"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-7 w-1 rounded-full"
                                        :style="teamAccentStyle(transaction)"
                                    ></span>

                                    <div>
                                        <div
                                            class="font-semibold text-gray-300"
                                        >
                                            Season
                                            {{ transaction.season_id }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-[8px] uppercase tracking-wider text-gray-700"
                                        >
                                            Role Record
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Player -->
                            <td
                                class="px-4 py-3 whitespace-nowrap"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-800 bg-gray-900"
                                    >
                                        <span
                                            class="text-[9px] font-bold text-gray-500"
                                        >
                                            {{ playerInitials(transaction.player_name) }}
                                        </span>
                                    </div>

                                    <span
                                        class="font-semibold text-gray-300"
                                    >
                                        {{ transaction.player_name || "—" }}
                                    </span>
                                </div>
                            </td>

                            <!-- Role -->
                            <td
                                class="px-4 py-3 whitespace-nowrap border-r border-gray-800"
                            >
                                <span
                                    class="inline-flex items-center rounded-md border px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide"
                                    :class="
                                        roleBadge(
                                            transaction.latest_role
                                        )
                                    "
                                >
                                    {{
                                        formatText(
                                            transaction.latest_role
                                        )
                                    }}
                                </span>
                            </td>

                            <!-- Details -->
                            <td
                                class="px-4 py-3 border-r border-gray-800"
                            >
                                <div class="max-w-[550px]">
                                    <p
                                        class="whitespace-normal break-words leading-relaxed text-gray-400"
                                    >
                                        {{
                                            transaction.merged_details ||
                                            "No additional details available."
                                        }}
                                    </p>
                                </div>
                            </td>

                            <!-- Team -->
                            <td
                                class="px-4 py-3 whitespace-nowrap border-r border-gray-800"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-white/10"
                                        :style="
                                            teamBadgeStyle(
                                                transaction
                                            )
                                        "
                                    >
                                        <i
                                            class="fa fa-users text-[9px] text-white/80"
                                        ></i>
                                    </span>

                                    <span
                                        class="font-medium text-gray-300"
                                    >
                                        {{
                                            transaction.team_name ??
                                            "Free Agent"
                                        }}
                                    </span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td
                                class="px-4 py-3 whitespace-nowrap"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wide"
                                    :class="
                                        statusBadge(
                                            transaction.status
                                        )
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            statusDot(
                                                transaction.status
                                            )
                                        "
                                    ></span>

                                    {{
                                        formatText(
                                            transaction.status
                                        )
                                    }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- =========================================================
                 FOOTER
            ========================================================== -->
            <div
                class="flex flex-col gap-1 border-t border-gray-800 bg-gray-900/30 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between"
            >
                <span class="text-[9px] text-gray-600">
                    {{ transactions.length }}
                    {{
                        transactions.length === 1
                            ? "role record"
                            : "role records"
                    }}
                </span>

                <span class="text-[9px] text-gray-700">
                    Click a record to view the associated season
                </span>
            </div>
        </div>
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
const loading = ref(false);

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

const formatText = (value) => {
    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "—";
    }

    return String(value)
        .replaceAll("_", " ")
        .replace(/\s+/g, " ")
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

/*
|--------------------------------------------------------------------------
| Player Initials
|--------------------------------------------------------------------------
*/

const playerInitials = (name) => {
    if (!name) {
        return "??";
    }

    const parts = String(name)
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 1) {
        return parts[0]
            .substring(0, 2)
            .toUpperCase();
    }

    return (
        parts[0].charAt(0) +
        parts[parts.length - 1].charAt(0)
    ).toUpperCase();
};

/*
|--------------------------------------------------------------------------
| Team Colors
|--------------------------------------------------------------------------
*/

const normalizeColor = (
    color,
    fallback = "374151"
) => {
    if (!color) {
        return `#${fallback}`;
    }

    const cleaned = String(color)
        .replace("#", "")
        .trim();

    return `#${cleaned}`;
};

const teamAccentStyle = (transaction) => {
    const primary = normalizeColor(
        transaction.primary_color,
        "3b82f6"
    );

    return {
        backgroundColor: primary,
        boxShadow: `0 0 10px ${primary}55`,
    };
};

const teamBadgeStyle = (transaction) => {
    const primary = normalizeColor(
        transaction.primary_color,
        "374151"
    );

    const secondary = normalizeColor(
        transaction.secondary_color,
        transaction.primary_color || "1f2937"
    );

    return {
        background: `linear-gradient(135deg, ${primary}, ${secondary})`,
    };
};

/*
|--------------------------------------------------------------------------
| Role Badge
|--------------------------------------------------------------------------
*/

const roleBadge = (role) => {
    const value = String(role || "").toLowerCase();

    if (
        value.includes("franchise") ||
        value.includes("star") ||
        value.includes("all star") ||
        value.includes("all-star")
    ) {
        return "bg-purple-500/10 border-purple-500/20 text-purple-300";
    }

    if (value.includes("starter")) {
        return "bg-blue-500/10 border-blue-500/20 text-blue-300";
    }

    if (value.includes("role")) {
        return "bg-green-500/10 border-green-500/20 text-green-300";
    }

    if (value.includes("bench")) {
        return "bg-yellow-500/10 border-yellow-500/20 text-yellow-300";
    }

    if (value.includes("reserve")) {
        return "bg-gray-500/10 border-gray-600/30 text-gray-400";
    }

    return "bg-cyan-500/10 border-cyan-500/20 text-cyan-300";
};

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

const statusBadge = (status) => {
    const value = String(status || "").toLowerCase();

    if (
        value.includes("active") ||
        value.includes("current") ||
        value.includes("assigned")
    ) {
        return "bg-green-500/10 border-green-500/20 text-green-300";
    }

    if (
        value.includes("inactive") ||
        value.includes("removed") ||
        value.includes("ended")
    ) {
        return "bg-red-500/10 border-red-500/20 text-red-300";
    }

    if (
        value.includes("changed") ||
        value.includes("updated")
    ) {
        return "bg-blue-500/10 border-blue-500/20 text-blue-300";
    }

    return "bg-gray-500/10 border-gray-600/30 text-gray-400";
};

const statusDot = (status) => {
    const value = String(status || "").toLowerCase();

    if (
        value.includes("active") ||
        value.includes("current") ||
        value.includes("assigned")
    ) {
        return "bg-green-400 shadow-[0_0_7px_rgba(74,222,128,0.6)]";
    }

    if (
        value.includes("inactive") ||
        value.includes("removed") ||
        value.includes("ended")
    ) {
        return "bg-red-400 shadow-[0_0_7px_rgba(248,113,113,0.6)]";
    }

    if (
        value.includes("changed") ||
        value.includes("updated")
    ) {
        return "bg-blue-400 shadow-[0_0_7px_rgba(96,165,250,0.6)]";
    }

    return "bg-gray-500";
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchPlayerRoleHistory = async () => {
    if (!player_id.value) {
        transactions.value = [];
        return;
    }

    try {
        loading.value = true;

        const response = await axios.post(
            route("players.role.history"),
            {
                player_id: player_id.value,
            }
        );

        transactions.value = Array.isArray(response.data)
            ? response.data
            : [];
    } catch (error) {
        console.error(
            "Error fetching player role history:",
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
    fetchPlayerRoleHistory();
});

watch(
    () => props.player_id,
    (newPlayerId) => {
        player_id.value = newPlayerId;
        fetchPlayerRoleHistory();
    }
);
</script>