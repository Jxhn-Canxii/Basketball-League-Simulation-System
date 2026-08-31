<template>
  <div class="draft-board">
    <h2 class="text-xl font-semibold text-gray-800">
      {{ props.isOffSeason ? "Off-season" : "In-season" }} Trade Proposal
    </h2>

    <!-- Show 'Generate Proposal' button if proposals are empty -->
    <div
      v-if="!trade_season_end"
      class="flex text-2xl bg-gray-200 font-bold justify-center items-center p-4 mb-4 gap-3 mt-4 border-b"
    >
      <button
        @click="generateTradeProposal"
        v-if="!trade_season_end"
        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
      >
        Generate Proposal
      </button>
      <button
        @click="endTrade"
        v-if="!trade_season_end"
        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
      >
        End Trade Season
      </button>
    </div>
    <div
      v-if="current_season > 0 || (proposals.length > 0 && !trade_season_end)"
      class="text-right mb-4 mt-4"
    >
      <button
        @click="autoTrade"
        v-if="isTradeDone > 0"
        class="px-4 py-2 bg-green-500 mr-4 text-white rounded hover:bg-red-600"
      >
        Let AI Decide
      </button>
    </div>
    <!-- Display list of proposals -->
    <div v-if="proposals.length > 0 && current_season > 0">
      <!-- Show 'End Trade' button if there are proposals -->
      <div v-if="proposals.length > 0" class="grid grid-cols-3 xs:grid-cols-1 p-2 gap-3">
        <div
          class="bg-gray-200 p-4 rounded-2xl"
          v-for="(proposal, index) in proposals"
          :key="index"
        >
          <p v-if="isTradeDone > 0">
            A proposed {{ proposal.team_count }}-team trade involves the following
            <b v-for="(teams, index) in proposal?.team_name_involved">
                {{ teams }} {{ index == proposal.team_count - 1 ? "" : "," }}
            </b>.
          </p>
          <p v-else>
            Breaking news: {{ proposal.team_count }}-team trade approved involving
            <b v-for="(teams, index) in proposal?.team_name_involved">
                {{ teams }} {{ index == proposal.team_count - 1 ? "" : "," }}
            </b>.
          </p>
          <!-- {{ proposal }} -->
          <div :class="'grid-cols-'+((proposal.team_count % 2 == 0) ? 2 : 1)" class="grid gap-4  border p-4 rounded-lg shadow-md">
            <div
              class="p-2 rounded-lg"
              v-for="(player, index) in proposal.players"
              :key="player.iplayer_idd"
            >
                <div
                  :style="
                    gradientStyle(
                      player?.to_team_primary_color,
                      player?.to_team_primary_color,
                      player?.to_team_primary_color,
                      player?.to_team_primary_color
                    )
                  "
                  class="flex-1 p-4 bg-gray-50 rounded-lg shadow-md"
                >
                  <h4 class="text-center font-semibold text-lg">
                    {{ player.player_name }}
                  </h4>
                  <p class="text-center text-white">
                    {{ player.from_team }} to {{ player.to_team }}
                  </p>
                  <p class="text-center text-white">{{ player.role }}</p>
                  <div class="flex justify-center items-center mt-2">
                    <a
                      href="#"
                      @click.prevent="showProfile(player.player_id)"
                      class="text-blue-500 underline"
                      >View Profile</a
                    >
                  </div>
                </div>
              
            </div>
            <p class="text-black text-sm uppercase font-bold">{{ proposal.status }}</p>
            <!-- <div class="mt-2 flex justify-end space-x-4">
                        <button 
                            @click="approveProposal(proposal.id)" 
                            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                            Approve
                        </button>
                        <button 
                            @click="rejectProposal(proposal.id)" 
                            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                            Reject
                        </button>
                    </div> -->
          </div>
        </div>
      </div>

      <!-- Show if no proposals available for the selected category -->
      <div v-else>
        <p class="text-center text-white">
          No trade proposals available for this season.
        </p>
      </div>
    </div>
  </div>

  <!-- Modal for Player Profile -->
  <Modal
    :show="showPlayerProfileModal"
    :maxWidth="'6xl'"
    title="Player Profile"
    @close="showPlayerProfileModal = false"
  >
    <div class="p-6 block">
      <!-- Image Section -->
      <PlayerPerformance
        v-if="selectedPlayer"
        :key="selectedPlayer"
        :player_id="selectedPlayer"
      />
    </div>
  </Modal>
</template>
<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";
import { gradientStyle } from "@/Utility/Formatter";
const emits = defineEmits(["newSeason"]);
const props = defineProps({
  isOffSeason: {
    type: Boolean,
    default: true,
  },
});
const showPlayerProfileModal = ref(false);
const selectedPlayer = ref(null);
const proposals = ref([]);
const current_season = ref(null);
const trade_season_end = ref(false);
const isTradeDone = ref(false);

onMounted(async () => {
  loadData();
});

const loadData = async () => {
  await fetchPendingTradeProposals();
  if (isTradeDone.value == 0) {
    await fetchApprovedTradeProposals();
  }
//   console.log(isTradeDone.value);
};
const showProfile = (playerId) => {
  selectedPlayer.value = playerId;
  showPlayerProfileModal.value = true;
};

// Fetch trade candidates
const fetchPendingTradeProposals = async () => {
  try {
    const response = await axios.post(route("trade.list.pending"), {
      is_off_season: props.isOffSeason,
    }); // Update with your API endpoint
    proposals.value = response.data.trade_proposals;
    isTradeDone.value = response.data.trade_proposals.length;
    current_season.value = response.data.current_season;
  } catch (error) {
    console.error("Error fetching available proposals:", error);
  }
};
const fetchApprovedTradeProposals = async () => {
  try {
    const response = await axios.post(route("trade.list.approved"), {
      is_off_season: props.isOffSeason,
    }); // Update with your API endpoint
    proposals.value = response.data.trade_proposals;
    current_season.value = response.data.current_season;
    trade_season_end.value = response.data.trade_season_end;
  } catch (error) {
    console.error("Error fetching available proposals:", error);
  }
};

// Handle pagination (if needed)
const handlePagination = (page_num) => {
  search.value.page_num = page_num;
  fetchPendingTradeProposals();
};

// End trade function (currently not used here)
const endTrade = async () => {
  try {
    // 1️⃣  fire‑and‑forget: no await here
    Swal.fire({
      title: "Processing...",
      text: "Ending trade...",
      icon: "info",
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // 2️⃣  do the API call
    await axios.post(
      route(props.isOffSeason ? "trade.end.offseason" : "trade.end.inseason")
    );

    // 3️⃣  close loader & show success
    await Swal.close();
    await Swal.fire({
      title: "Success!",
      text: "Trade successfully completed and season storyline summary created!",
      icon: "success",
      confirmButtonText: "OK",
    });

    // 4️⃣  follow‑up logic
    fetchApprovedTradeProposals();
    emits("newSeason", Math.random());
  } catch (error) {
    await Swal.close();
    await Swal.fire({
      title: "Error!",
      text:
        error.response?.data?.message ||
        "Failed to end trade or create storyline summary",
      icon: "error",
      confirmButtonText: "OK",
    });
    console.error("Error in endTrade:", error);
  }
};

const autoTrade = async () => {
  // Show the "Processing" Swal when the function is called
  const processingSwal = Swal.fire({
    title: "Processing...",
    text: "Please wait while the AI decides the trade proposals.",
    icon: "info",
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    },
  });
  try {
    const response = await axios.post(route("trade.decision.automated"), {
      is_off_season: props.isOffSeason,
    });

    if (response && response.data && response.data.decisions) {
      proposals.value = []; //clear pending proposals
      fetchApprovedTradeProposals();
      emits("newSeason", Math.random());
    }

    // Close the processing Swal once the response is processed
    processingSwal.close();
  } catch (error) {
    console.error("Error deciding trade:", error);

    // Close the processing Swal when error occurs
    processingSwal.close();

    // Show error Swal with the response error message
    await Swal.fire({
      title: "Error!",
      text: error.response ? error.response.data.message : "An error occurred.",
      icon: "error",
    });
  }
};

// Generate new trade proposal
const generateTradeProposal = async () => {
  try {
    // Show the processing Swal
    const swalProcessing = Swal.fire({
      title: "Processing...",
      text: "Please wait while we generate the trade proposal.",
      icon: "info",
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      },
    });

    // Make the API request
    const response = await axios.post(route("trade.generate"), {
      is_off_season: props.isOffSeason,
    }); // Update with your API endpoint

    // Close the processing Swal once the API call finishes
    swalProcessing.close();

    if (response) {
      await Swal.fire({
        title: "Proposal Generated!",
        text: "A new trade proposal has been generated.",
        icon: "success",
        confirmButtonText: "OK",
      });
      fetchPendingTradeProposals(); // Refresh proposals list
    }
  } catch (error) {
    console.error("Error generating trade proposal:", error);
    await Swal.fire({
      title: "Error!",
      text:
        error.response?.data?.message ||
        "An error occurred while generating the trade proposal.",
      icon: "error",
    });
  }
};
</script>
