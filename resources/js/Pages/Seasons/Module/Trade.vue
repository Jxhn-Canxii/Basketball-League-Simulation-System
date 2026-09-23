<template>
    <div
        class="trade-page w-full min-w-0 overflow-hidden rounded-xl border border-white/[0.06] bg-black text-white"
    >
        <!-- =========================================================
             HEADER
        ========================================================== -->
        <header
            class="relative border-b border-white/[0.06] bg-gradient-to-r from-[#111111] via-[#0b0b0b] to-black px-4 py-4 sm:px-5"
        >
            <div
                class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-blue-500 via-indigo-500 to-transparent"
            ></div>

            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10"
                    >
                        <span
                            class="text-xl font-black text-blue-400"
                        >
                            ⇄
                        </span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="text-base font-black uppercase tracking-[0.12em] text-white sm:text-lg"
                            >
                                {{ props.isOffSeason ? "Off-Season" : "In-Season" }}
                                Trades
                            </h2>

                            <span
                                class="rounded-full border border-blue-500/15 bg-blue-500/10 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-blue-400"
                            >
                                {{ props.isOffSeason ? "OFF-SEASON" : "IN-SEASON" }}
                            </span>
                        </div>

                        <p
                            class="mt-1 text-[10px] font-semibold text-gray-600 sm:text-xs"
                        >
                            AI-generated trade proposals and transactions
                        </p>
                    </div>
                </div>

                <div
                    v-if="proposals.length > 0"
                    class="flex w-fit shrink-0 items-center gap-2 rounded-full border border-white/[0.07] bg-white/[0.03] px-3 py-1.5"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-blue-500"
                    ></span>

                    <span
                        class="text-[9px] font-black uppercase tracking-wider text-gray-400"
                    >
                        {{ proposals.length }}
                        {{ proposals.length === 1 ? "Proposal" : "Proposals" }}
                    </span>
                </div>
            </div>
        </header>

        <!-- =========================================================
             ACTION BAR
        ========================================================== -->
        <div
            v-if="!trade_season_end"
            class="border-b border-white/[0.05] bg-[#080808] p-3"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:justify-end"
            >
                <button
                    type="button"
                    class="trade-btn trade-btn-primary"
                    @click="generateTradeProposal"
                >
                    <span class="text-base leading-none">＋</span>
                    Generate Proposal
                </button>

                <button
                    v-if="isTradeDone > 0"
                    type="button"
                    class="trade-btn trade-btn-ai"
                    @click="autoTrade"
                >
                    <span class="text-base leading-none">✦</span>
                    Let AI Decide
                </button>

                <button
                    type="button"
                    class="trade-btn trade-btn-danger"
                    @click="endTrade"
                >
                    <i class="fas fa-stop-circle text-[11px]"></i>
                    End Trade Season
                </button>
            </div>
        </div>

        <!-- =========================================================
             NO PROPOSALS
        ========================================================== -->
        <div
            v-if="proposals.length === 0"
            class="empty-trades"
        >
            <div class="empty-trades-icon">
                ⇄
            </div>

            <div
                class="mb-2 text-[9px] font-black uppercase tracking-[0.2em] text-blue-500/70"
            >
                Trade Office
            </div>

            <h3>No Trade Proposals</h3>

            <p>
                There are currently no trade proposals available for this
                season.
            </p>

            <button
                v-if="!trade_season_end"
                type="button"
                class="trade-btn trade-btn-primary mt-5"
                @click="generateTradeProposal"
            >
                <span class="text-base">＋</span>
                Generate Trade Proposal
            </button>
        </div>

        <!-- =========================================================
             TRADE PROPOSALS
        ========================================================== -->
        <div
            v-else
            class="trade-list"
        >
            <article
                v-for="proposal in proposals"
                :key="proposal.id"
                class="trade-card"
            >
                <!-- =================================================
                     TRADE CARD HEADER
                ================================================== -->
                <div class="trade-card-header">
                    <div class="min-w-0">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <span class="trade-number">
                                TRADE #{{ proposal.id }}
                            </span>

                            <span
                                class="trade-status"
                                :class="statusClass(proposal.status)"
                            >
                                <span
                                    class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full"
                                ></span>

                                {{ proposal.status }}
                            </span>
                        </div>

                        <h3 class="trade-title">
                            {{ proposal.team_count }}-Team Trade
                        </h3>

                        <p
                            v-if="proposal.team_name_involved?.length"
                            class="trade-subtitle"
                        >
                            {{ proposal.team_name_involved.join(" • ") }}
                        </p>
                    </div>

                    <div
                        v-if="proposal.created_at"
                        class="trade-date"
                    >
                        <i class="far fa-clock mr-1"></i>
                        {{ formatTradeDate(proposal.created_at) }}
                    </div>
                </div>

                <!-- =================================================
                     TRADE FLOW
                ================================================== -->
                <div class="trade-flow">
                    <div
                        v-for="team in getTradeTeams(proposal)"
                        :key="team.id"
                        class="team-trade-column"
                    >
                        <!-- TEAM HEADER -->
                        <div
                            class="team-column-header"
                            :style="teamHeaderStyle(team)"
                        >
                            <div
                                class="team-logo-placeholder"
                                :style="{
                                    background: team.primary
                                        ? '#' + team.primary
                                        : '#374151',
                                }"
                            >
                                {{ getTeamInitials(team.name) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="team-name"
                                    :style="{
                                        color: team.primary
                                            ? '#' + team.primary
                                            : '#d1d5db',
                                    }"
                                >
                                    {{ team.name }}
                                </div>

                                <div class="team-trade-label">
                                    <span
                                        class="inline-block h-1 w-1 rounded-full bg-red-500"
                                    ></span>

                                    Sends
                                </div>
                            </div>
                        </div>

                        <!-- SENDS -->
                        <div class="section-label sends-label">
                            <span>Outgoing Assets</span>
                            <span class="section-count">
                                {{ assetsSentByTeam(proposal, team.id).length }}
                            </span>
                        </div>

                        <div class="assets-container">
                            <template
                                v-for="asset in assetsSentByTeam(
                                    proposal,
                                    team.id
                                )"
                                :key="asset.id"
                            >
                                <!-- PLAYER -->
                                <button
                                    v-if="asset.asset_type === 'player'"
                                    type="button"
                                    class="asset-card player-asset"
                                    @click="showProfile(asset.player_id)"
                                >
                                    <div
                                        class="asset-icon player-icon"
                                    >
                                        <i class="fas fa-user text-[11px]"></i>
                                    </div>

                                    <div class="asset-info text-left">
                                        <div class="asset-name">
                                            {{ asset.player_name }}
                                        </div>

                                        <div class="asset-meta">
                                            {{ asset.role || "Player" }}
                                        </div>
                                    </div>

                                    <div class="asset-arrow">
                                        →
                                    </div>
                                </button>

                                <!-- DRAFT PICK -->
                                <div
                                    v-else
                                    class="asset-card pick-asset"
                                >
                                    <div class="asset-icon pick-icon">
                                        <i
                                            class="fas fa-ticket-alt text-[10px]"
                                        ></i>
                                    </div>

                                    <div class="asset-info">
                                        <div class="asset-name">
                                            {{ formatPick(asset) }}
                                        </div>

                                        <div class="asset-meta">
                                            Round {{ asset.pick_round }}
                                            <span class="mx-1 text-gray-700">
                                                ·
                                            </span>
                                            Season {{ asset.pick_season_id }}
                                        </div>

                                        <div
                                            v-if="asset.pick_protections"
                                            class="asset-protection"
                                        >
                                            <i
                                                class="fas fa-shield-alt mr-1 text-[8px]"
                                            ></i>

                                            {{ asset.pick_protections }}
                                        </div>
                                    </div>

                                    <div class="asset-arrow">
                                        →
                                    </div>
                                </div>
                            </template>

                            <div
                                v-if="
                                    assetsSentByTeam(
                                        proposal,
                                        team.id
                                    ).length === 0
                                "
                                class="no-assets"
                            >
                                <i
                                    class="fas fa-minus-circle mr-1 text-[9px]"
                                ></i>
                                No outgoing assets
                            </div>
                        </div>

                        <!-- RECEIVES DIVIDER -->
                        <div class="receives-divider">
                            <span>
                                <i
                                    class="fas fa-arrow-down mr-1 text-[8px]"
                                ></i>
                                RECEIVES
                            </span>
                        </div>

                        <!-- RECEIVES -->
                        <div class="section-label receives-label">
                            <span>Incoming Assets</span>
                            <span class="section-count">
                                {{
                                    assetsReceivedByTeam(
                                        proposal,
                                        team.id
                                    ).length
                                }}
                            </span>
                        </div>

                        <div class="assets-container receives">
                            <template
                                v-for="asset in assetsReceivedByTeam(
                                    proposal,
                                    team.id
                                )"
                                :key="'receive-' + asset.id"
                            >
                                <!-- PLAYER -->
                                <button
                                    v-if="asset.asset_type === 'player'"
                                    type="button"
                                    class="asset-card received-player"
                                    @click="showProfile(asset.player_id)"
                                >
                                    <div
                                        class="asset-arrow receive-arrow"
                                    >
                                        ←
                                    </div>

                                    <div
                                        class="asset-icon player-icon received-icon"
                                    >
                                        <i
                                            class="fas fa-user text-[11px]"
                                        ></i>
                                    </div>

                                    <div class="asset-info text-left">
                                        <div class="asset-name">
                                            {{ asset.player_name }}
                                        </div>

                                        <div class="asset-meta">
                                            {{ asset.role || "Player" }}
                                        </div>

                                        <div
                                            v-if="asset.from_team"
                                            class="asset-from"
                                        >
                                            From {{ asset.from_team }}
                                        </div>
                                    </div>
                                </button>

                                <!-- PICK -->
                                <div
                                    v-else
                                    class="asset-card received-pick"
                                >
                                    <div
                                        class="asset-arrow receive-arrow"
                                    >
                                        ←
                                    </div>

                                    <div class="asset-icon pick-icon">
                                        <i
                                            class="fas fa-ticket-alt text-[10px]"
                                        ></i>
                                    </div>

                                    <div class="asset-info">
                                        <div class="asset-name">
                                            {{ formatPick(asset) }}
                                        </div>

                                        <div class="asset-meta">
                                            Round {{ asset.pick_round }}
                                            <span class="mx-1 text-gray-700">
                                                ·
                                            </span>
                                            Season {{ asset.pick_season_id }}
                                        </div>

                                        <div
                                            v-if="asset.from_team"
                                            class="asset-from"
                                        >
                                            From {{ asset.from_team }}
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div
                                v-if="
                                    assetsReceivedByTeam(
                                        proposal,
                                        team.id
                                    ).length === 0
                                "
                                class="no-assets"
                            >
                                <i
                                    class="fas fa-minus-circle mr-1 text-[9px]"
                                ></i>
                                Nothing received
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =================================================
                     TRADE SUMMARY
                ================================================== -->
                <div class="trade-summary">
                    <div class="summary-left">
                        <div class="summary-icon">
                            ⇄
                        </div>

                        <div class="min-w-0">
                            <div class="summary-title">
                                {{ proposal.team_count }} teams involved
                            </div>

                            <div class="summary-text">
                                {{ totalPlayers(proposal) }} players
                                <span class="mx-1 text-gray-700">·</span>
                                {{ totalPicks(proposal) }} draft picks
                            </div>
                        </div>
                    </div>

                    <div class="summary-teams">
                        <div
                            v-for="team in getTradeTeams(proposal)"
                            :key="'summary-' + team.id"
                            class="summary-team"
                        >
                            <span
                                class="summary-dot"
                                :style="{
                                    background: team.primary
                                        ? '#' + team.primary
                                        : '#6b7280',
                                }"
                            ></span>

                            <span class="truncate">
                                {{ team.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- =================================================
                     STATUS MESSAGE
                ================================================== -->
                <div
                    v-if="proposal.status === 'pending'"
                    class="trade-pending-message"
                >
                    <div class="status-message-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>

                    <div>
                        <div class="status-message-title">
                            Awaiting Decision
                        </div>

                        <div class="status-message-text">
                            Waiting for AI decision
                        </div>
                    </div>
                </div>

                <div
                    v-if="proposal.status === 'approved'"
                    class="trade-approved-message"
                >
                    <div class="status-message-icon">
                        <i class="fas fa-check"></i>
                    </div>

                    <div>
                        <div class="status-message-title">
                            Trade Completed
                        </div>

                        <div class="status-message-text">
                            Trade approved and completed
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- =========================================================
             PLAYER PROFILE MODAL
        ========================================================== -->
        <Modal
            :show="showPlayerProfileModal"
            :maxWidth="'6xl'"
            title="Player Profile"
            @close="showPlayerProfileModal = false"
        >
            <div class="block bg-black p-3 sm:p-5">
                <PlayerPerformance
                    v-if="selectedPlayer"
                    :key="selectedPlayer"
                    :player_id="selectedPlayer"
                />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";

const emits = defineEmits(["newSeason"]);

const props = defineProps({
    isOffSeason: {
        type: Boolean,
        default: true,
    },
});

/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

const showPlayerProfileModal = ref(false);
const selectedPlayer = ref(null);

const proposals = ref([]);
const current_season = ref(null);
const trade_season_end = ref(false);
const isTradeDone = ref(false);

/*
|--------------------------------------------------------------------------
| LIFECYCLE
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await loadData();
});

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

const loadData = async () => {
    await fetchPendingTradeProposals();

    if (isTradeDone.value == 0) {
        await fetchApprovedTradeProposals();
    }
};

/*
|--------------------------------------------------------------------------
| PLAYER PROFILE
|--------------------------------------------------------------------------
*/

const showProfile = (playerId) => {
    if (!playerId || playerId === 0) {
        return;
    }

    selectedPlayer.value = playerId;
    showPlayerProfileModal.value = true;
};

/*
|--------------------------------------------------------------------------
| FETCH PENDING
|--------------------------------------------------------------------------
*/

const fetchPendingTradeProposals = async () => {
    try {
        const response = await axios.post(
            route("trade.list.pending"),
            {
                is_off_season: props.isOffSeason,
            }
        );

        proposals.value = response.data.trade_proposals || [];

        isTradeDone.value = proposals.value.length;

        current_season.value = response.data.current_season;
    } catch (error) {
        console.error(
            "Error fetching pending trade proposals:",
            error
        );
    }
};

/*
|--------------------------------------------------------------------------
| FETCH APPROVED
|--------------------------------------------------------------------------
*/

const fetchApprovedTradeProposals = async () => {
    try {
        const response = await axios.post(
            route("trade.list.approved"),
            {
                is_off_season: props.isOffSeason,
            }
        );

        proposals.value = response.data.trade_proposals || [];

        current_season.value = response.data.current_season;

        trade_season_end.value =
            response.data.trade_season_end;
    } catch (error) {
        console.error(
            "Error fetching approved trade proposals:",
            error
        );
    }
};

/*
|--------------------------------------------------------------------------
| GET ALL TEAMS INVOLVED
|--------------------------------------------------------------------------
*/

const getTradeTeams = (proposal) => {
    const teams = new Map();

    const assets =
        proposal.tradePlayers ||
        proposal.players ||
        [];

    assets.forEach((asset) => {
        if (
            asset.from_team_id &&
            !teams.has(asset.from_team_id)
        ) {
            teams.set(
                asset.from_team_id,
                {
                    id: asset.from_team_id,
                    name: asset.from_team,
                    primary: asset.from_team_primary_color,
                    secondary: asset.from_team_secondary_color,
                }
            );
        }

        if (
            asset.to_team_id &&
            !teams.has(asset.to_team_id)
        ) {
            teams.set(
                asset.to_team_id,
                {
                    id: asset.to_team_id,
                    name: asset.to_team,
                    primary: asset.to_team_primary_color,
                    secondary: asset.to_team_secondary_color,
                }
            );
        }
    });

    return Array.from(teams.values());
};

/*
|--------------------------------------------------------------------------
| TEAM INITIALS
|--------------------------------------------------------------------------
*/

const getTeamInitials = (name) => {
    if (!name) {
        return "??";
    }

    const words = name.trim().split(/\s+/);

    if (words.length === 1) {
        return words[0].substring(0, 2).toUpperCase();
    }

    return (
        words[0].charAt(0) +
        words[words.length - 1].charAt(0)
    ).toUpperCase();
};

/*
|--------------------------------------------------------------------------
| ASSETS SENT
|--------------------------------------------------------------------------
*/

const assetsSentByTeam = (proposal, teamId) => {
    const assets =
        proposal.tradePlayers ||
        proposal.players ||
        [];

    return assets.filter(
        (asset) =>
            Number(asset.from_team_id) === Number(teamId)
    );
};

/*
|--------------------------------------------------------------------------
| ASSETS RECEIVED
|--------------------------------------------------------------------------
*/

const assetsReceivedByTeam = (proposal, teamId) => {
    const assets =
        proposal.tradePlayers ||
        proposal.players ||
        [];

    return assets.filter(
        (asset) =>
            Number(asset.to_team_id) === Number(teamId)
    );
};

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

const totalPlayers = (proposal) => {
    const assets =
        proposal.tradePlayers ||
        proposal.players ||
        [];

    return assets.filter(
        (asset) => asset.asset_type === "player"
    ).length;
};

const totalPicks = (proposal) => {
    const assets =
        proposal.tradePlayers ||
        proposal.players ||
        [];

    return assets.filter(
        (asset) => asset.asset_type === "draft_pick"
    ).length;
};

/*
|--------------------------------------------------------------------------
| FORMAT PICK
|--------------------------------------------------------------------------
*/

const formatPick = (asset) => {
    if (asset.asset_type !== "draft_pick") {
        return asset.player_name;
    }

    const season =
        asset.pick_season_id
            ? `S${asset.pick_season_id}`
            : "";

    const round =
        asset.pick_round
            ? `Round ${asset.pick_round}`
            : "";

    return `${season} ${round}`.trim() || "Draft Pick";
};

/*
|--------------------------------------------------------------------------
| TEAM HEADER
|--------------------------------------------------------------------------
*/

const teamHeaderStyle = (team) => {
    return {
        borderTopColor: team.primary
            ? `#${team.primary}`
            : "#374151",
    };
};

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

const statusClass = (status) => {
    switch (String(status).toLowerCase()) {
        case "approved":
            return "status-approved";

        case "rejected":
            return "status-rejected";

        case "pending":
        default:
            return "status-pending";
    }
};

/*
|--------------------------------------------------------------------------
| DATE
|--------------------------------------------------------------------------
*/

const formatTradeDate = (date) => {
    if (!date) {
        return "";
    }

    try {
        return new Date(
            String(date).replace(" ", "T")
        ).toLocaleString([], {
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit",
        });
    } catch {
        return date;
    }
};

/*
|--------------------------------------------------------------------------
| END TRADE
|--------------------------------------------------------------------------
*/

const endTrade = async () => {
    try {
        Swal.fire({
            title: "Processing...",
            text: "Ending trade season...",
            icon: "info",
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            background: "#0b0b0b",
            color: "#fff",
            didOpen: () => {
                Swal.showLoading();
            },
        });

        await axios.post(
            route(
                props.isOffSeason
                    ? "trade.end.offseason"
                    : "trade.end.inseason"
            )
        );

        Swal.close();

        await Swal.fire({
            title: "Trade Season Complete",
            text:
                "Trades were completed and the season storyline was created.",
            icon: "success",
            confirmButtonText: "OK",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#2563eb",
        });

        await fetchApprovedTradeProposals();

        emits("newSeason", Math.random());
    } catch (error) {
        Swal.close();

        await Swal.fire({
            title: "Error",
            text:
                error.response?.data?.message ||
                "Failed to end trade season.",
            icon: "error",
            confirmButtonText: "OK",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });

        console.error(
            "Error in endTrade:",
            error
        );
    }
};

/*
|--------------------------------------------------------------------------
| AI TRADE
|--------------------------------------------------------------------------
*/

const autoTrade = async () => {
    Swal.fire({
        title: "AI Trade Office",
        text: "The AI is evaluating every proposal...",
        icon: "info",
        showConfirmButton: false,
        allowOutsideClick: false,
        background: "#0b0b0b",
        color: "#fff",
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const response = await axios.post(
            route("trade.decision.automated"),
            {
                is_off_season: props.isOffSeason,
            }
        );

        if (
            response &&
            response.data &&
            response.data.decisions
        ) {
            proposals.value = [];

            await fetchApprovedTradeProposals();

            emits(
                "newSeason",
                Math.random()
            );
        }

        Swal.close();
    } catch (error) {
        Swal.close();

        console.error(
            "Error deciding trade:",
            error
        );

        await Swal.fire({
            title: "Error",
            text:
                error.response?.data?.message ||
                "An error occurred while deciding trades.",
            icon: "error",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });
    }
};

/*
|--------------------------------------------------------------------------
| GENERATE PROPOSAL
|--------------------------------------------------------------------------
*/

const generateTradeProposal = async () => {
    Swal.fire({
        title: "Trade Machine",
        text:
            "Finding teams, players and draft picks that make sense together...",
        icon: "info",
        showConfirmButton: false,
        allowOutsideClick: false,
        background: "#0b0b0b",
        color: "#fff",
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        await axios.post(
            route("trade.generate"),
            {
                is_off_season: props.isOffSeason,
            }
        );

        Swal.close();

        await Swal.fire({
            title: "Proposal Generated",
            text:
                "A new trade proposal has been generated.",
            icon: "success",
            confirmButtonText: "View Trade",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#2563eb",
        });

        await fetchPendingTradeProposals();
    } catch (error) {
        Swal.close();

        console.error(
            "Error generating trade proposal:",
            error
        );

        await Swal.fire({
            title: "Error",
            text:
                error.response?.data?.message ||
                "An error occurred while generating the trade proposal.",
            icon: "error",
            background: "#0b0b0b",
            color: "#fff",
            confirmButtonColor: "#dc2626",
        });
    }
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

.trade-page {
    max-width: 1600px;
    margin: 0 auto;
}

/*
|--------------------------------------------------------------------------
| BUTTONS
|--------------------------------------------------------------------------
*/

.trade-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    min-height: 38px;
    padding: 8px 14px;

    border-radius: 9px;
    border: 1px solid transparent;

    color: white;

    font-size: 10px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 0.04em;

    cursor: pointer;

    transition:
        transform 0.15s ease,
        background 0.15s ease,
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.trade-btn:hover {
    transform: translateY(-1px);
}

.trade-btn:active {
    transform: translateY(0);
}

.trade-btn-primary {
    background: #2563eb;
    border-color: #3b82f6;

    box-shadow:
        0 4px 14px rgba(37, 99, 235, 0.2);
}

.trade-btn-primary:hover {
    background: #1d4ed8;

    box-shadow:
        0 6px 20px rgba(37, 99, 235, 0.3);
}

.trade-btn-ai {
    background: #047857;
    border-color: #059669;

    box-shadow:
        0 4px 14px rgba(5, 150, 105, 0.18);
}

.trade-btn-ai:hover {
    background: #065f46;

    box-shadow:
        0 6px 20px rgba(5, 150, 105, 0.25);
}

.trade-btn-danger {
    background: #991b1b;
    border-color: #b91c1c;

    box-shadow:
        0 4px 14px rgba(220, 38, 38, 0.15);
}

.trade-btn-danger:hover {
    background: #7f1d1d;
}

/*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

.empty-trades {
    display: flex;
    min-height: 280px;

    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding: 40px 20px;

    text-align: center;

    background:
        radial-gradient(
            circle at center,
            rgba(30, 64, 175, 0.08),
            transparent 55%
        );
}

.empty-trades-icon {
    display: flex;

    width: 58px;
    height: 58px;

    align-items: center;
    justify-content: center;

    margin-bottom: 12px;

    border-radius: 16px;

    border: 1px solid rgba(96, 165, 250, 0.12);

    background: rgba(59, 130, 246, 0.06);

    color: #3b82f6;

    font-size: 28px;
    font-weight: 900;
}

.empty-trades h3 {
    color: #f3f4f6;

    font-size: 15px;
    font-weight: 900;
}

.empty-trades p {
    max-width: 420px;

    margin-top: 5px;

    color: #4b5563;

    font-size: 11px;
    line-height: 1.6;
}

/*
|--------------------------------------------------------------------------
| TRADE LIST
|--------------------------------------------------------------------------
*/

.trade-list {
    display: flex;
    flex-direction: column;
    gap: 12px;

    padding: 12px;
}

/*
|--------------------------------------------------------------------------
| TRADE CARD
|--------------------------------------------------------------------------
*/

.trade-card {
    min-width: 0;

    overflow: hidden;

    border-radius: 13px;

    border: 1px solid rgba(255, 255, 255, 0.06);

    background: #0a0a0a;

    box-shadow:
        0 8px 30px rgba(0, 0, 0, 0.3);
}

/*
|--------------------------------------------------------------------------
| CARD HEADER
|--------------------------------------------------------------------------
*/

.trade-card-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 14px 16px;

    border-bottom: 1px solid rgba(255, 255, 255, 0.05);

    background:
        linear-gradient(
            135deg,
            #111111,
            #090909
        );
}

.trade-number {
    color: #4b5563;

    font-size: 8px;
    font-weight: 900;

    letter-spacing: 0.16em;
}

.trade-title {
    color: #f3f4f6;

    font-size: 14px;
    font-weight: 900;

    line-height: 1.2;
}

.trade-subtitle {
    max-width: 700px;

    overflow: hidden;

    margin-top: 3px;

    color: #52525b;

    font-size: 9px;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.trade-date {
    flex-shrink: 0;

    color: #52525b;

    font-size: 9px;
    font-weight: 700;
}

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

.trade-status {
    display: inline-flex;
    align-items: center;

    padding: 3px 7px;

    border-radius: 999px;

    font-size: 7px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.status-pending {
    color: #fbbf24;

    background: rgba(245, 158, 11, 0.08);

    border: 1px solid rgba(245, 158, 11, 0.16);
}

.status-pending > span {
    background: #f59e0b;
}

.status-approved {
    color: #34d399;

    background: rgba(16, 185, 129, 0.08);

    border: 1px solid rgba(16, 185, 129, 0.16);
}

.status-approved > span {
    background: #10b981;
}

.status-rejected {
    color: #f87171;

    background: rgba(239, 68, 68, 0.08);

    border: 1px solid rgba(239, 68, 68, 0.16);
}

.status-rejected > span {
    background: #ef4444;
}

/*
|--------------------------------------------------------------------------
| TRADE FLOW
|--------------------------------------------------------------------------
*/

.trade-flow {
    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(250px, 1fr)
        );

    min-width: 0;

    background: #050505;
}

/*
|--------------------------------------------------------------------------
| TEAM COLUMN
|--------------------------------------------------------------------------
*/

.team-trade-column {
    min-width: 0;

    padding: 12px;

    border-right: 1px solid rgba(255, 255, 255, 0.04);
}

.team-trade-column:last-child {
    border-right: 0;
}

/*
|--------------------------------------------------------------------------
| TEAM HEADER
|--------------------------------------------------------------------------
*/

.team-column-header {
    display: flex;

    min-width: 0;

    align-items: center;

    gap: 9px;

    margin-bottom: 11px;

    padding: 9px;

    border-top: 3px solid;

    border-right: 1px solid rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    border-left: 1px solid rgba(255, 255, 255, 0.05);

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #111111,
            #0b0b0b
        );
}

.team-logo-placeholder {
    display: flex;

    width: 36px;
    height: 36px;

    flex-shrink: 0;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    color: white;

    font-size: 10px;
    font-weight: 900;

    box-shadow:
        0 3px 10px rgba(0, 0, 0, 0.4);
}

.team-name {
    overflow: hidden;

    font-size: 12px;
    font-weight: 900;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.team-trade-label {
    display: flex;

    align-items: center;

    gap: 5px;

    margin-top: 2px;

    color: #4b5563;

    font-size: 7px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 0.12em;
}

/*
|--------------------------------------------------------------------------
| SECTION LABEL
|--------------------------------------------------------------------------
*/

.section-label {
    display: flex;

    align-items: center;
    justify-content: space-between;

    margin: 8px 2px 6px;

    color: #3f3f46;

    font-size: 7px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 0.13em;
}

.sends-label {
    color: #52525b;
}

.receives-label {
    color: #10b981;
}

.section-count {
    display: inline-flex;

    min-width: 17px;
    height: 17px;

    align-items: center;
    justify-content: center;

    padding: 0 5px;

    border-radius: 999px;

    background: rgba(255, 255, 255, 0.04);

    color: #71717a;

    font-size: 7px;
}

/*
|--------------------------------------------------------------------------
| ASSETS
|--------------------------------------------------------------------------
*/

.assets-container {
    display: flex;

    min-width: 0;

    flex-direction: column;

    gap: 5px;
}

.asset-card {
    display: flex;

    width: 100%;
    min-width: 0;
    min-height: 49px;

    align-items: center;

    gap: 8px;

    padding: 7px 8px;

    border-radius: 8px;

    border: 1px solid rgba(255, 255, 255, 0.05);

    background:
        linear-gradient(
            135deg,
            #101010,
            #0b0b0b
        );

    transition:
        transform 0.15s ease,
        border-color 0.15s ease,
        background 0.15s ease,
        box-shadow 0.15s ease;
}

button.asset-card {
    font-family: inherit;

    text-align: left;
}

.player-asset,
.received-player {
    cursor: pointer;
}

.asset-card:hover {
    transform: translateY(-1px);

    border-color: rgba(255, 255, 255, 0.1);

    background: #121212;

    box-shadow:
        0 5px 14px rgba(0, 0, 0, 0.25);
}

.player-asset:hover {
    border-color: rgba(96, 165, 250, 0.2);
}

.received-player:hover {
    border-color: rgba(52, 211, 153, 0.2);
}

/*
|--------------------------------------------------------------------------
| ASSET ICON
|--------------------------------------------------------------------------
*/

.asset-icon {
    display: flex;

    width: 30px;
    height: 30px;

    flex-shrink: 0;

    align-items: center;
    justify-content: center;

    border-radius: 7px;
}

.player-icon {
    background: rgba(59, 130, 246, 0.08);

    border: 1px solid rgba(59, 130, 246, 0.12);

    color: #60a5fa;
}

.pick-icon {
    background: rgba(245, 158, 11, 0.08);

    border: 1px solid rgba(245, 158, 11, 0.12);

    color: #fbbf24;
}

.received-icon {
    background: rgba(16, 185, 129, 0.08);

    border-color: rgba(16, 185, 129, 0.12);

    color: #34d399;
}

/*
|--------------------------------------------------------------------------
| ASSET TEXT
|--------------------------------------------------------------------------
*/

.asset-info {
    min-width: 0;

    flex: 1;
}

.asset-name {
    overflow: hidden;

    color: #d4d4d8;

    font-size: 10px;
    font-weight: 900;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.asset-meta {
    overflow: hidden;

    margin-top: 2px;

    color: #52525b;

    font-size: 8px;
    font-weight: 700;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.asset-from {
    overflow: hidden;

    margin-top: 2px;

    color: #3f3f46;

    font-size: 7px;
    font-weight: 700;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.asset-protection {
    overflow: hidden;

    margin-top: 3px;

    color: #a16207;

    font-size: 7px;
    font-weight: 800;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.asset-arrow {
    flex-shrink: 0;

    color: #3f3f46;

    font-size: 14px;
    font-weight: 900;
}

.receive-arrow {
    color: #10b981;
}

/*
|--------------------------------------------------------------------------
| RECEIVES
|--------------------------------------------------------------------------
*/

.receives-divider {
    position: relative;

    margin: 15px 0 4px;

    text-align: center;
}

.receives-divider::before {
    content: "";

    position: absolute;

    left: 0;
    right: 0;

    top: 50%;

    height: 1px;

    background: rgba(255, 255, 255, 0.05);
}

.receives-divider span {
    position: relative;

    padding: 0 7px;

    background: #050505;

    color: #10b981;

    font-size: 7px;
    font-weight: 900;

    letter-spacing: 0.13em;
}

/*
|--------------------------------------------------------------------------
| RECEIVED ASSETS
|--------------------------------------------------------------------------
*/

.receives .asset-card {
    border-left: 2px solid rgba(16, 185, 129, 0.6);

    background:
        linear-gradient(
            135deg,
            rgba(16, 185, 129, 0.045),
            #0b0b0b
        );
}

/*
|--------------------------------------------------------------------------
| NO ASSETS
|--------------------------------------------------------------------------
*/

.no-assets {
    padding: 9px;

    border-radius: 7px;

    border: 1px dashed rgba(255, 255, 255, 0.06);

    background: rgba(255, 255, 255, 0.015);

    color: #3f3f46;

    font-size: 8px;
    font-weight: 700;

    text-align: center;
}

/*
|--------------------------------------------------------------------------
| SUMMARY
|--------------------------------------------------------------------------
*/

.trade-summary {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 11px 14px;

    border-top: 1px solid rgba(255, 255, 255, 0.05);

    background:
        linear-gradient(
            135deg,
            #0d0d0d,
            #090909
        );
}

.summary-left {
    display: flex;

    min-width: 0;

    align-items: center;

    gap: 8px;
}

.summary-icon {
    display: flex;

    width: 30px;
    height: 30px;

    flex-shrink: 0;

    align-items: center;
    justify-content: center;

    border-radius: 7px;

    border: 1px solid rgba(59, 130, 246, 0.12);

    background: rgba(59, 130, 246, 0.06);

    color: #60a5fa;

    font-size: 13px;
    font-weight: 900;
}

.summary-title {
    color: #a1a1aa;

    font-size: 9px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.summary-text {
    margin-top: 2px;

    color: #3f3f46;

    font-size: 8px;
    font-weight: 700;
}

.summary-teams {
    display: flex;

    min-width: 0;

    flex-wrap: wrap;

    justify-content: flex-end;

    gap: 7px;
}

.summary-team {
    display: flex;

    max-width: 160px;

    min-width: 0;

    align-items: center;

    gap: 4px;

    color: #52525b;

    font-size: 8px;
    font-weight: 800;
}

.summary-dot {
    width: 5px;
    height: 5px;

    flex-shrink: 0;

    border-radius: 50%;
}

/*
|--------------------------------------------------------------------------
| STATUS MESSAGE
|--------------------------------------------------------------------------
*/

.trade-pending-message,
.trade-approved-message {
    display: flex;

    align-items: center;

    gap: 9px;

    padding: 9px 14px;
}

.trade-pending-message {
    border-top: 1px solid rgba(245, 158, 11, 0.1);

    background: rgba(245, 158, 11, 0.025);

    color: #fbbf24;
}

.trade-approved-message {
    border-top: 1px solid rgba(16, 185, 129, 0.1);

    background: rgba(16, 185, 129, 0.025);

    color: #34d399;
}

.status-message-icon {
    display: flex;

    width: 27px;
    height: 27px;

    flex-shrink: 0;

    align-items: center;
    justify-content: center;

    border-radius: 7px;

    background: rgba(255, 255, 255, 0.04);

    font-size: 9px;
}

.status-message-title {
    font-size: 9px;
    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-message-text {
    margin-top: 1px;

    color: #52525b;

    font-size: 8px;
    font-weight: 600;
}

/*
|--------------------------------------------------------------------------
| SCROLLBAR
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

::selection {
    background: rgba(59, 130, 246, 0.2);
    color: white;
}

/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {
    .trade-card-header {
        align-items: flex-start;

        flex-direction: column;

        gap: 8px;
    }

    .trade-date {
        align-self: flex-start;
    }

    .trade-flow {
        grid-template-columns: 1fr;
    }

    .team-trade-column {
        border-right: 0;

        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .team-trade-column:last-child {
        border-bottom: 0;
    }

    .trade-summary {
        align-items: flex-start;

        flex-direction: column;
    }

    .summary-teams {
        justify-content: flex-start;
    }

    .summary-team {
        max-width: 140px;
    }
}

/*
|--------------------------------------------------------------------------
| VERY NARROW
|--------------------------------------------------------------------------
*/

@media (max-width: 420px) {
    .trade-list {
        padding: 7px;
    }

    .trade-card-header {
        padding: 12px;
    }

    .team-trade-column {
        padding: 9px;
    }

    .trade-summary {
        padding: 10px;
    }

    .trade-btn {
        width: 100%;
    }

    .team-name {
        font-size: 11px;
    }
}
</style>