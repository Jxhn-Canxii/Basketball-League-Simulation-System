<template>
  <div class="trade-page bg-black rounded-lg" v-if="proposals.length > 0">

    <!-- =========================================================
        HEADER
    ========================================================== -->
    <div class="trade-header">
      <div>
        <div class="flex items-center gap-3">
          <div class="trade-header-icon">
            ⇄
          </div>

          <div>
            <h2 class="text-sm font-bold text-gray-900">
              Recent {{ props.isOffSeason ? "Off-Season" : "In-Season" }} Proposed Trades
            </h2>

            <p class="text-sm text-gray-500">
              AI-generated trade proposals and transactions
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="proposal_count > 0"
        class="trade-count"
      >
        {{ proposal_count }}
        Approved {{ proposal_count > 1 ? "Proposals" : "Proposal" }}
      </div>
    </div>
    <!-- =========================================================
      TRADE PROPOSALS
    ========================================================== -->
    <div

      class="trade-list"
    >

      <div
        v-if="proposals.length > 0"
        v-for="proposal in proposals"
        :key="proposal.id"
        class="trade-card"
      >

        <!-- =====================================================
          TRADE CARD HEADER
        ====================================================== -->
        <div class="trade-card-header">

          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="trade-number ">
                {{ proposal.type }} TRADE #{{ proposal.id }}
              </span>

              <span
                class="trade-status"
                :class="statusClass(proposal.status)"
              >
                {{ proposal.status }}
              </span>
            </div>

            <h3 class="trade-title">
              {{ proposal.team_count }}-Team Trade
            </h3>

            <p class="trade-subtitle">
              {{ proposal.team_name_involved?.join(" • ") }}
            </p>
          </div>

          <div class="trade-date">
            {{ formatTradeDate(proposal.created_at) }}
          </div>

        </div>


        <!-- =====================================================
            TRADE FLOW
        ====================================================== -->
        <div class="trade-flow">

          <!-- TEAM COLUMNS -->
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
                  background: '#' + team.primary
                }"
              >
                {{ team.name.substring(0, 2).toUpperCase() }}
              </div>

              <div class="min-w-0">
                <div
                  class="team-name"
                  :style="{ color: '#' + team.primary }"
                >
                  {{ team.name }}
                </div>

                <div class="team-trade-label">
                  Sends
                </div>
              </div>
            </div>


            <!-- ASSETS SENT BY TEAM -->
            <div class="assets-container">

              <template
                v-for="asset in assetsSentByTeam(proposal, team.id)"
                :key="asset.id"
              >

                <!-- PLAYER -->
                <div
                  v-if="asset.asset_type === 'player'"
                  class="asset-card player-asset"
                  @click="showProfile(asset.player_id)"
                >
                  <div class="asset-icon player-icon">
                    ●
                  </div>

                  <div class="asset-info">
                    <div class="asset-name">
                      {{ asset.player_name }}
                    </div>

                    <div class="asset-meta">
                      {{ asset.role }}
                    </div>
                  </div>

                  <div class="asset-arrow">
                    →
                  </div>
                </div>


                <!-- DRAFT PICK -->
                <div
                  v-else
                  class="asset-card pick-asset"
                >
                  <div class="asset-icon pick-icon">
                    #
                  </div>

                  <div class="asset-info">

                    <div class="asset-name">
                      {{ formatPick(asset) }}
                    </div>

                    <div class="asset-meta">
                      Round {{ asset.pick_round }}
                      · Season {{ asset.pick_season_id }}
                    </div>

                    <div
                      v-if="asset.pick_protections"
                      class="asset-protection"
                    >
                      {{ asset.pick_protections }}
                    </div>

                  </div>

                  <div class="asset-arrow">
                    →
                  </div>
                </div>

              </template>


              <!-- NOTHING SENT -->
              <div
                v-if="assetsSentByTeam(proposal, team.id).length === 0"
                class="no-assets"
              >
                No assets
              </div>

            </div>


            <!-- RECEIVES SECTION -->
            <div class="receives-divider">
              <span>RECEIVES</span>
            </div>


            <div class="assets-container receives">

              <template
                v-for="asset in assetsReceivedByTeam(proposal, team.id)"
                :key="'receive-' + asset.id"
              >

                <!-- PLAYER -->
                <div
                  v-if="asset.asset_type === 'player'"
                  class="asset-card received-player"
                  @click="showProfile(asset.player_id)"
                >
                  <div class="asset-arrow receive-arrow">
                    ←
                  </div>

                  <div class="asset-icon player-icon">
                    ●
                  </div>

                  <div class="asset-info">
                    <div class="asset-name">
                      {{ asset.player_name }}
                    </div>

                    <div class="asset-meta">
                      {{ asset.role }}
                    </div>

                    <div class="asset-from">
                      From {{ asset.from_team }}
                    </div>
                  </div>
                </div>


                <!-- PICK -->
                <div
                  v-else
                  class="asset-card received-pick"
                >
                  <div class="asset-arrow receive-arrow">
                    ←
                  </div>

                  <div class="asset-icon pick-icon">
                    #
                  </div>

                  <div class="asset-info">

                    <div class="asset-name">
                      {{ formatPick(asset) }}
                    </div>

                    <div class="asset-meta">
                      Round {{ asset.pick_round }}
                      · Season {{ asset.pick_season_id }}
                    </div>

                    <div class="asset-from">
                      From {{ asset.from_team }}
                    </div>

                  </div>
                </div>

              </template>


              <div
                v-if="assetsReceivedByTeam(proposal, team.id).length === 0"
                class="no-assets"
              >
                Nothing received
              </div>

            </div>
          </div>
        </div>


        <!-- =====================================================
            TRADE SUMMARY
        ====================================================== -->
        <div class="trade-summary">

          <div class="summary-left">

            <span class="summary-icon">
              ⇄
            </span>

            <div>
              <div class="summary-title">
                {{ proposal.team_count }} teams involved
              </div>

              <div class="summary-text">
                {{ totalPlayers(proposal) }} players
                ·
                {{ totalPicks(proposal) }} draft picks
              </div>
            </div>

          </div>


          <!-- ORIGINAL TEAMS -->
          <div class="summary-teams">

            <div
              v-for="team in getTradeTeams(proposal)"
              :key="'summary-' + team.id"
              class="summary-team"
            >
              <span
                class="summary-dot"
                :style="{ background: '#' + team.primary }"
              ></span>

              {{ team.name }}

            </div>

          </div>

        </div>


        <!-- =====================================================
            PENDING / APPROVED MESSAGE
        ====================================================== -->
        <div
          v-if="proposal.status === 'pending'"
          class="trade-pending-message"
        >
          <span>⏳</span>

          <span>
            Waiting for AI decision
          </span>
        </div>

        <div
          v-if="proposal.status === 'approved'"
          class="trade-approved-message"
        >
          <span>✓</span>

          <span>
            Trade approved and completed
          </span>
        </div>

      </div>
      <div v-else>
          <h2 class="text-2xl text-left font-black text-nowrap">
            NO TRADE PROPOSAL
          </h2>
      </div>
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
      <div class="p-6 block">

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
  seasonId: {
    type: Number,
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
const proposal_count = ref([]);
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

  await fetchTradeProposals();

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

const fetchTradeProposals = async () => {

  try {

    const response = await axios.post(
      route("trade.list.recent"),
      {
        is_off_season: props.isOffSeason,
        season_id: props.seasonId,
        limit: 1
      }
    );

    proposals.value = response.data.trade_proposals || [];
    proposal_count.value = response.data.proposal_count || 0;

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
| GET ALL TEAMS INVOLVED
|--------------------------------------------------------------------------
|
| We derive the team information directly from the trade assets.
|
*/

const getTradeTeams = (proposal) => {

  const teams = new Map();

  const assets =
    proposal.tradePlayers ||
    proposal.players ||
    [];

  assets.forEach((asset) => {

    if (!teams.has(asset.from_team_id)) {

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

    if (!teams.has(asset.to_team_id)) {

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
| ASSETS SENT BY TEAM
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
| ASSETS RECEIVED BY TEAM
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
| PLAYER / PICK COUNTS
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
| FORMAT DRAFT PICK
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

  return `${season} ${round}`.trim();

};


/*
|--------------------------------------------------------------------------
| TEAM HEADER STYLE
|--------------------------------------------------------------------------
*/

const teamHeaderStyle = (team) => {

  return {
    borderTopColor: `#${team.primary}`,
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

    return new Date(date.replace(" ", "T"))
      .toLocaleString([], {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      });

  } catch {

    return date;

  }

};

</script>

<style scoped>

/* =========================================================
   COMPACT TRADE PANEL
========================================================= */

.trade-page {
    width: 100%;
    margin: 0;
    padding: 0;

    color: #e5e7eb;
    background: #08090b;
}


/* =========================================================
   HEADER
========================================================= */

.trade-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 10px;

    margin-bottom: 8px;
    padding: 8px 10px;

    border: 1px solid #1d2026;
    border-radius: 8px;

    background: #0d0f12;
}

.trade-header > div:first-child {
    min-width: 0;
}

.trade-header .flex {
    gap: 8px;
}

.trade-header-icon {
    width: 26px;
    height: 26px;

    flex: 0 0 26px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 7px;

    background: #171a20;
    border: 1px solid #292e37;

    color: #60a5fa;

    font-size: 16px;
    font-weight: 900;
}

.trade-header h2 {
    margin: 0;

    color: #e5e7eb !important;

    font-size: 11px !important;
    line-height: 1.2;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trade-header p {
    margin-top: 2px;

    color: #59606c !important;

    font-size: 8px !important;
    line-height: 1.2;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trade-count {
    flex-shrink: 0;

    padding: 4px 7px;

    border-radius: 5px;

    background: #171a20;
    border: 1px solid #292e37;

    color: #9ca3af;

    font-size: 7px;
    font-weight: 900;

    white-space: nowrap;
}


/* =========================================================
   TRADE LIST
========================================================= */

.trade-list {
    display: flex;
    flex-direction: column;

    gap: 8px;
}


/* =========================================================
   TRADE CARD
========================================================= */

.trade-card {
    overflow: hidden;

    background: #0d0f12;

    border: 1px solid #20242b;
    border-radius: 9px;

    box-shadow: none;
}


/* =========================================================
   CARD HEADER
========================================================= */

.trade-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 8px;

    padding: 8px 10px;

    border-bottom: 1px solid #1d2026;

    background: #111318;
}

.trade-card-header > div:first-child {
    min-width: 0;
}

.trade-number {
    color: #555b66;

    font-size: 7px;
    font-weight: 900;

    letter-spacing: .07em;
}

.trade-title {
    margin-top: 2px;

    color: #d1d5db;

    font-size: 11px;
    line-height: 1.15;
    font-weight: 900;
}

.trade-subtitle {
    margin-top: 2px;

    max-width: 100%;

    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;

    color: #555b66;

    font-size: 7px;
}

.trade-date {
    flex-shrink: 0;

    color: #4b515c;

    font-size: 7px;
}


/* =========================================================
   STATUS
========================================================= */

.trade-status {
    padding: 2px 5px;

    border-radius: 4px;

    font-size: 6px;
    line-height: 1;

    font-weight: 900;

    text-transform: uppercase;
    letter-spacing: .04em;
}

.status-pending {
    color: #fbbf24;

    background: rgba(245, 158, 11, .08);

    border: 1px solid rgba(245, 158, 11, .18);
}

.status-approved {
    color: #34d399;

    background: rgba(16, 185, 129, .08);

    border: 1px solid rgba(16, 185, 129, .18);
}

.status-rejected {
    color: #f87171;

    background: rgba(239, 68, 68, .08);

    border: 1px solid rgba(239, 68, 68, .18);
}


/* =========================================================
   TRADE FLOW
========================================================= */

.trade-flow {
    display: grid;

    /*
     * Smaller columns so the entire trade can live
     * comfortably inside a side panel.
     */
    grid-template-columns:
        repeat(
            auto-fit,
            minmax(150px, 1fr)
        );

    background: #090a0c;
}


/* =========================================================
   TEAM COLUMN
========================================================= */

.team-trade-column {
    min-width: 0;

    padding: 7px;

    border-right: 1px solid #1d2026;
}

.team-trade-column:last-child {
    border-right: 0;
}


/* =========================================================
   TEAM HEADER
========================================================= */

.team-column-header {
    display: flex;
    align-items: center;

    gap: 7px;

    padding: 7px 8px;

    margin-bottom: 6px;

    border-top: 2px solid;

    border-radius: 6px;

    background: #111318;

    border-left: 1px solid #20242b;
    border-right: 1px solid #20242b;
    border-bottom: 1px solid #20242b;

    box-shadow: none;
}

.team-logo-placeholder {
    width: 25px;
    height: 25px;

    flex: 0 0 25px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;

    color: white;

    font-size: 8px;
    font-weight: 900;
}

.team-name {
    max-width: 100%;

    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;

    font-size: 9px;
    line-height: 1.1;

    font-weight: 900;
}

.team-trade-label {
    margin-top: 2px;

    color: #4f5560;

    font-size: 6px;
    line-height: 1;

    text-transform: uppercase;
    letter-spacing: .08em;

    font-weight: 900;
}


/* =========================================================
   ASSETS
========================================================= */

.assets-container {
    display: flex;
    flex-direction: column;

    gap: 4px;
}

.asset-card {
    display: flex;
    align-items: center;

    gap: 6px;

    min-height: 38px;

    padding: 5px 6px;

    border-radius: 6px;

    background: #111318;

    border: 1px solid #20242b;

    transition:
        border-color .12s ease,
        background .12s ease;
}

.asset-card:hover {
    transform: none;

    background: #15181e;

    border-color: #303641;

    box-shadow: none;
}

.player-asset,
.received-player {
    cursor: pointer;
}


/* =========================================================
   ASSET ICON
========================================================= */

.asset-icon {
    width: 22px;
    height: 22px;

    flex: 0 0 22px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 5px;

    font-size: 8px;

    font-weight: 900;
}

.player-icon {
    background: rgba(37, 99, 235, .08);

    color: #60a5fa;

    border: 1px solid rgba(96, 165, 250, .14);
}

.pick-icon {
    background: rgba(245, 158, 11, .07);

    color: #fbbf24;

    border: 1px solid rgba(251, 191, 36, .14);
}


/* =========================================================
   ASSET TEXT
========================================================= */

.asset-info {
    flex: 1;
    min-width: 0;
}

.asset-name {
    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #d1d5db;

    font-size: 8px;
    line-height: 1.15;

    font-weight: 800;
}

.asset-meta {
    margin-top: 2px;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #555b66;

    font-size: 6px;
    line-height: 1.1;

    text-transform: capitalize;
}

.asset-from {
    margin-top: 2px;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #4b515b;

    font-size: 6px;
}

.asset-protection {
    margin-top: 2px;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #d9a72e;

    font-size: 6px;

    font-weight: 800;
}

.asset-arrow {
    flex: 0 0 auto;

    color: #454b55;

    font-size: 11px;
    line-height: 1;

    font-weight: 900;
}

.receive-arrow {
    color: #34a879;
}


/* =========================================================
   RECEIVES
========================================================= */

.receives-divider {
    position: relative;

    margin: 7px 0 5px;

    text-align: center;
}

.receives-divider::before {
    content: "";

    position: absolute;

    left: 0;
    right: 0;

    top: 50%;

    height: 1px;

    background: #1d2026;
}

.receives-divider span {
    position: relative;

    padding: 0 5px;

    background: #090a0c;

    color: #4d535e;

    font-size: 6px;
    line-height: 1;

    font-weight: 900;

    letter-spacing: .1em;
}

.receives .asset-card {
    border-left: 2px solid #159669;

    background: rgba(16, 185, 129, .035);
}


/* =========================================================
   NO ASSETS
========================================================= */

.no-assets {
    padding: 7px 5px;

    border-radius: 5px;

    text-align: center;

    background: #0e1014;

    border: 1px dashed #252a32;

    color: #414752;

    font-size: 7px;

    font-weight: 700;
}


/* =========================================================
   SUMMARY
========================================================= */

.trade-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 8px;

    padding: 7px 9px;

    border-top: 1px solid #1d2026;

    background: #101216;
}

.summary-left {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 6px;
}

.summary-icon {
    width: 23px;
    height: 23px;

    flex: 0 0 23px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 5px;

    background: #171a20;

    color: #60a5fa;

    border: 1px solid #292e37;

    font-size: 10px;

    font-weight: 900;
}

.summary-title {
    color: #9ca3af;

    font-size: 7px;
    line-height: 1.1;

    font-weight: 900;
}

.summary-text {
    margin-top: 2px;

    color: #4f5560;

    font-size: 6px;
}

.summary-teams {
    min-width: 0;

    display: flex;
    flex-wrap: wrap;

    justify-content: flex-end;

    gap: 4px;
}

.summary-team {
    display: flex;
    align-items: center;

    gap: 3px;

    max-width: 85px;

    overflow: hidden;

    color: #666d78;

    font-size: 6px;
    font-weight: 700;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.summary-dot {
    width: 5px;
    height: 5px;

    flex: 0 0 5px;

    border-radius: 50%;
}


/* =========================================================
   RESULT MESSAGE
========================================================= */

.trade-pending-message,
.trade-approved-message {
    display: flex;
    align-items: center;

    gap: 5px;

    padding: 6px 9px;

    font-size: 7px;

    font-weight: 800;
}

.trade-pending-message {
    color: #d9a72e;

    background: rgba(245, 158, 11, .035);

    border-top: 1px solid rgba(245, 158, 11, .12);
}

.trade-approved-message {
    color: #34a879;

    background: rgba(16, 185, 129, .035);

    border-top: 1px solid rgba(16, 185, 129, .12);
}


/* =========================================================
   PLAYER MODAL
========================================================= */

:deep(.modal-content) {
    border-radius: 10px;
}


/* =========================================================
   TABLET / NARROW SIDE PANEL
========================================================= */

@media (max-width: 900px) {

    .trade-flow {
        grid-template-columns:
            repeat(
                auto-fit,
                minmax(135px, 1fr)
            );
    }

    .team-trade-column {
        padding: 6px;
    }

    .trade-card-header {
        padding: 7px 8px;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 640px) {

    .trade-header {
        padding: 7px 8px;
    }

    .trade-header p {
        display: none;
    }

    .trade-count {
        font-size: 6px;
    }

    .trade-card-header {
        align-items: flex-start;
        flex-direction: column;

        gap: 4px;
    }

    .trade-date {
        display: none;
    }

    .trade-flow {
        grid-template-columns: 1fr;
    }

    .team-trade-column {
        border-right: 0;
        border-bottom: 1px solid #1d2026;
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
}


/* =========================================================
   VERY SMALL SIDE PANEL
========================================================= */

@media (max-width: 400px) {

    .trade-header-icon {
        width: 22px;
        height: 22px;
        flex-basis: 22px;

        font-size: 13px;
    }

    .trade-header h2 {
        font-size: 9px !important;
    }

    .trade-count {
        padding: 3px 5px;
    }

    .team-column-header {
        padding: 5px 6px;
    }

    .team-logo-placeholder {
        width: 21px;
        height: 21px;
        flex-basis: 21px;
    }

    .team-name {
        font-size: 8px;
    }

    .asset-card {
        min-height: 34px;
        padding: 4px 5px;
    }

    .asset-icon {
        width: 20px;
        height: 20px;
        flex-basis: 20px;
    }

    .asset-name {
        font-size: 7px;
    }

    .asset-meta,
    .asset-from,
    .asset-protection {
        font-size: 5px;
    }
}

</style>
