<template>
  <div class="trade-page bg-black rounded-lg min-h-screen">

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
            <h2 class="text-2xl font-bold text-gray-900">
              {{ props.isOffSeason ? "Off-Season" : "In-Season" }} Trades
            </h2>

            <p class="text-sm text-gray-500">
              AI-generated trade proposals and transactions
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="proposals.length > 0"
        class="trade-count"
      >
        {{ proposals.length }}
        {{ proposals.length === 1 ? "Proposal" : "Proposals" }}
      </div>
    </div>


    <!-- =========================================================
         ACTION BAR
    ========================================================== -->
    <div
      v-if="!trade_season_end"
      class="trade-actions"
    >
      <button
        @click="generateTradeProposal"
        class="trade-btn trade-btn-primary"
      >
        <span class="text-lg">＋</span>
        Generate Proposal
      </button>

      <button
        v-if="isTradeDone > 0"
        @click="autoTrade"
        class="trade-btn trade-btn-ai"
      >
        <span class="text-lg">✦</span>
        Let AI Decide
      </button>

      <button
        @click="endTrade"
        class="trade-btn trade-btn-danger"
      >
        End Trade Season
      </button>
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

      <h3>No Trade Proposals</h3>

      <p>
        There are currently no trade proposals available for this season.
      </p>

      <button
        v-if="!trade_season_end"
        @click="generateTradeProposal"
        class="trade-btn trade-btn-primary mt-5"
      >
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

      <div
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
              <span class="trade-number">
                TRADE #{{ proposal.id }}
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
    text:
      "The AI is evaluating every proposal...",
    icon: "info",
    showConfirmButton: false,
    allowOutsideClick: false,
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
  width: 100%;
  max-width: 1500px;
  margin: 0 auto;
  padding: 20px;

  color: #18181a;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.trade-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 22px;
}

.trade-header-icon {
  width: 48px;
  height: 48px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 14px;

  background: linear-gradient(
    135deg,
    #1f2937,
    #111827
  );

  color: #60a5fa;

  font-size: 27px;
  font-weight: 800;

  border: 1px solid #374151;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, .35);
}

.trade-header h2 {
  color: #f9fafb;
}

.trade-count {
  padding: 8px 14px;

  border-radius: 999px;

  background: #1f2937;

  color: #d1d5db;

  font-size: 13px;
  font-weight: 700;

  border: 1px solid #374151;
}


/*
|--------------------------------------------------------------------------
| ACTION BAR
|--------------------------------------------------------------------------
*/

.trade-actions {
  display: flex;
  justify-content: flex-end;

  gap: 10px;

  padding: 14px;

  margin-bottom: 22px;

  border: 1px solid #374151;

  border-radius: 14px;

  background:
    linear-gradient(
      135deg,
      #161b22,
      #111827
    );

  box-shadow:
    0 5px 20px rgba(0, 0, 0, .25);
}

.trade-btn {
  border: 1px solid transparent;

  border-radius: 9px;

  padding: 10px 16px;

  color: white;

  font-size: 14px;
  font-weight: 700;

  cursor: pointer;

  transition:
    transform .15s ease,
    opacity .15s ease,
    box-shadow .15s ease;
}

.trade-btn:hover {
  transform: translateY(-1px);
  opacity: .95;
}

.trade-btn-primary {
  background: #2563eb;

  box-shadow:
    0 3px 12px rgba(37, 99, 235, .25);
}

.trade-btn-primary:hover {
  box-shadow:
    0 5px 18px rgba(37, 99, 235, .4);
}

.trade-btn-ai {
  background: #059669;

  box-shadow:
    0 3px 12px rgba(5, 150, 105, .25);
}

.trade-btn-ai:hover {
  box-shadow:
    0 5px 18px rgba(5, 150, 105, .4);
}

.trade-btn-danger {
  background: #dc2626;

  box-shadow:
    0 3px 12px rgba(220, 38, 38, .2);
}


/*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

.empty-trades {
  text-align: center;

  padding: 80px 20px;

  border: 1px dashed #374151;

  border-radius: 18px;

  background:
    radial-gradient(
      circle at center,
      #1f2937 0%,
      #111827 65%
    );
}

.empty-trades-icon {
  width: 64px;
  height: 64px;

  margin: 0 auto 16px;

  border-radius: 18px;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #1f2937;

  color: #60a5fa;

  font-size: 32px;

  border: 1px solid #374151;
}

.empty-trades h3 {
  font-size: 20px;
  font-weight: 800;

  color: #f9fafb;
}

.empty-trades p {
  margin-top: 5px;

  color: #9ca3af;
}


/*
|--------------------------------------------------------------------------
| TRADE LIST
|--------------------------------------------------------------------------
*/

.trade-list {
  display: flex;
  flex-direction: column;

  gap: 24px;
}


/*
|--------------------------------------------------------------------------
| TRADE CARD
|--------------------------------------------------------------------------
*/

.trade-card {
  overflow: hidden;

  background: #111827;

  border: 1px solid #374151;

  border-radius: 18px;

  box-shadow:
    0 8px 30px rgba(0, 0, 0, .3);
}


/*
|--------------------------------------------------------------------------
| TRADE CARD HEADER
|--------------------------------------------------------------------------
*/

.trade-card-header {
  display: flex;

  justify-content: space-between;
  align-items: center;

  padding: 20px 22px;

  border-bottom: 1px solid #374151;

  background:
    linear-gradient(
      135deg,
      #1b2230,
      #111827
    );
}

.trade-number {
  font-size: 11px;

  font-weight: 800;

  letter-spacing: .08em;

  color: #6b7280;
}

.trade-title {
  margin-top: 3px;

  font-size: 20px;

  font-weight: 800;

  color: #f9fafb;
}

.trade-subtitle {
  margin-top: 3px;

  font-size: 13px;

  color: #9ca3af;
}

.trade-date {
  font-size: 12px;

  color: #6b7280;
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

.trade-status {
  padding: 4px 9px;

  border-radius: 999px;

  font-size: 10px;

  font-weight: 900;

  text-transform: uppercase;

  letter-spacing: .04em;
}

.status-pending {
  color: #fbbf24;

  background: rgba(245, 158, 11, .12);

  border: 1px solid rgba(245, 158, 11, .25);
}

.status-approved {
  color: #34d399;

  background: rgba(16, 185, 129, .12);

  border: 1px solid rgba(16, 185, 129, .25);
}

.status-rejected {
  color: #f87171;

  background: rgba(239, 68, 68, .12);

  border: 1px solid rgba(239, 68, 68, .25);
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
      minmax(280px, 1fr)
    );

  background: #0d1117;
}


/*
|--------------------------------------------------------------------------
| TEAM COLUMN
|--------------------------------------------------------------------------
*/

.team-trade-column {
  min-width: 0;

  padding: 18px;

  border-right: 1px solid #252b35;
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

  align-items: center;

  gap: 10px;

  padding: 13px;

  margin-bottom: 14px;

  border-top: 4px solid;

  border-radius: 10px;

  background:
    linear-gradient(
      135deg,
      #1a202c,
      #151a23
    );

  border-left: 1px solid #303744;
  border-right: 1px solid #303744;
  border-bottom: 1px solid #303744;

  box-shadow:
    0 4px 12px rgba(0, 0, 0, .25);
}

.team-logo-placeholder {
  width: 42px;
  height: 42px;

  flex-shrink: 0;

  display: flex;

  align-items: center;
  justify-content: center;

  border-radius: 10px;

  color: white;

  font-size: 13px;

  font-weight: 900;

  box-shadow:
    0 3px 10px rgba(0, 0, 0, .35);
}

.team-name {
  font-size: 16px;

  font-weight: 900;
}

.team-trade-label {
  margin-top: 1px;

  font-size: 10px;

  color: #6b7280;

  text-transform: uppercase;

  letter-spacing: .06em;

  font-weight: 800;
}


/*
|--------------------------------------------------------------------------
| ASSETS
|--------------------------------------------------------------------------
*/

.assets-container {
  display: flex;

  flex-direction: column;

  gap: 8px;
}

.asset-card {
  display: flex;

  align-items: center;

  gap: 10px;

  min-height: 58px;

  padding: 9px 10px;

  border-radius: 10px;

  background:
    linear-gradient(
      135deg,
      #1b2230,
      #151a23
    );

  border: 1px solid #303744;

  transition:
    transform .15s ease,
    border-color .15s ease,
    box-shadow .15s ease;
}

.asset-card:hover {
  transform: translateY(-2px);

  border-color: #4b5563;

  box-shadow:
    0 6px 16px rgba(0, 0, 0, .3);
}

.player-asset {
  cursor: pointer;
}

.received-player {
  cursor: pointer;
}


/*
|--------------------------------------------------------------------------
| PLAYER / PICK ICON
|--------------------------------------------------------------------------
*/

.asset-icon {
  width: 34px;
  height: 34px;

  flex-shrink: 0;

  display: flex;

  align-items: center;
  justify-content: center;

  border-radius: 9px;

  font-weight: 900;
}

.player-icon {
  background: rgba(37, 99, 235, .15);

  color: #60a5fa;

  border: 1px solid rgba(96, 165, 250, .2);
}

.pick-icon {
  background: rgba(245, 158, 11, .12);

  color: #fbbf24;

  border: 1px solid rgba(251, 191, 36, .2);
}


/*
|--------------------------------------------------------------------------
| ASSET TEXT
|--------------------------------------------------------------------------
*/

.asset-info {
  flex: 1;

  min-width: 0;
}

.asset-name {
  overflow: hidden;

  white-space: nowrap;

  text-overflow: ellipsis;

  font-size: 13px;

  font-weight: 800;

  color: #f3f4f6;
}

.asset-meta {
  margin-top: 2px;

  font-size: 10px;

  color: #9ca3af;

  text-transform: capitalize;
}

.asset-from {
  margin-top: 2px;

  font-size: 10px;

  color: #6b7280;
}

.asset-protection {
  margin-top: 4px;

  font-size: 9px;

  color: #fbbf24;

  font-weight: 700;
}

.asset-arrow {
  color: #6b7280;

  font-size: 18px;

  font-weight: 800;
}

.receive-arrow {
  color: #34d399;
}


/*
|--------------------------------------------------------------------------
| RECEIVES
|--------------------------------------------------------------------------
*/

.receives-divider {
  position: relative;

  margin: 18px 0 10px;

  text-align: center;
}

.receives-divider::before {
  content: "";

  position: absolute;

  left: 0;
  right: 0;

  top: 50%;

  height: 1px;

  background: #303744;
}

.receives-divider span {
  position: relative;

  padding: 0 8px;

  background: #0d1117;

  font-size: 9px;

  font-weight: 900;

  color: #6b7280;

  letter-spacing: .1em;
}

.receives .asset-card {
  border-left: 3px solid #10b981;

  background:
    linear-gradient(
      135deg,
      rgba(16, 185, 129, .08),
      #151a23
    );
}


/*
|--------------------------------------------------------------------------
| EMPTY ASSET
|--------------------------------------------------------------------------
*/

.no-assets {
  padding: 12px;

  border-radius: 9px;

  text-align: center;

  background: #151a23;

  border: 1px dashed #303744;

  color: #4b5563;

  font-size: 11px;

  font-weight: 600;
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

  gap: 20px;

  padding: 16px 20px;

  border-top: 1px solid #374151;

  background:
    linear-gradient(
      135deg,
      #151a23,
      #111827
    );
}

.summary-left {
  display: flex;

  align-items: center;

  gap: 10px;
}

.summary-icon {
  width: 36px;
  height: 36px;

  display: flex;

  align-items: center;
  justify-content: center;

  border-radius: 9px;

  background: #1f2937;

  color: #60a5fa;

  border: 1px solid #374151;

  font-weight: 900;
}

.summary-title {
  font-size: 12px;

  font-weight: 800;

  color: #d1d5db;
}

.summary-text {
  margin-top: 2px;

  font-size: 11px;

  color: #6b7280;
}

.summary-teams {
  display: flex;

  flex-wrap: wrap;

  justify-content: flex-end;

  gap: 10px;
}

.summary-team {
  display: flex;

  align-items: center;

  gap: 5px;

  font-size: 11px;

  font-weight: 700;

  color: #9ca3af;
}

.summary-dot {
  width: 7px;
  height: 7px;

  border-radius: 50%;

  box-shadow:
    0 0 6px rgba(255, 255, 255, .15);
}


/*
|--------------------------------------------------------------------------
| PENDING / APPROVED
|--------------------------------------------------------------------------
*/

.trade-pending-message,
.trade-approved-message {
  display: flex;

  align-items: center;

  gap: 8px;

  padding: 11px 20px;

  font-size: 12px;

  font-weight: 700;
}

.trade-pending-message {
  color: #fbbf24;

  background:
    rgba(245, 158, 11, .07);

  border-top: 1px solid rgba(245, 158, 11, .2);
}

.trade-approved-message {
  color: #34d399;

  background:
    rgba(16, 185, 129, .07);

  border-top: 1px solid rgba(16, 185, 129, .2);
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

  .trade-page {
    padding: 10px;
  }

  .trade-header {
    align-items: flex-start;
  }

  .trade-actions {
    flex-direction: column;
  }

  .trade-btn {
    width: 100%;
  }

  .trade-card-header {
    align-items: flex-start;

    flex-direction: column;

    gap: 8px;
  }

  .trade-flow {
    grid-template-columns: 1fr;
  }

  .team-trade-column {
    border-right: 0;

    border-bottom: 1px solid #252b35;
  }

  .team-trade-column:last-child {
    border-bottom: 0;
  }

  .trade-summary {
    flex-direction: column;

    align-items: flex-start;
  }

  .summary-teams {
    justify-content: flex-start;
  }

}
</style>

