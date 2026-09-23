<template>
  <!-- =========================================================
       PLAYER PROFILE
  ========================================================== -->
  <div
    v-if="!isLoading && main_performance?.player_details"
    class="w-full min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-[#080808] text-white shadow-2xl"
  >
    <!-- =======================================================
         HERO
    ======================================================== -->
    <section
      class="relative overflow-hidden border-b border-white/10 px-5 py-6 sm:px-6"
      :style="heroStyle"
    >
      <!-- Background glow -->
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div
          class="absolute -right-24 -top-24 h-72 w-72 rounded-full opacity-20 blur-3xl"
          :style="{ backgroundColor: primaryColor }"
        ></div>

        <div
          class="absolute -bottom-32 -left-20 h-64 w-64 rounded-full opacity-10 blur-3xl"
          :style="{ backgroundColor: secondaryColor }"
        ></div>

        <div class="absolute inset-0 bg-black/40"></div>
        <div
            :style="{ color: primaryColor ?? '#ca1414', opacity: '50%' }"
            class="pointer-events-none absolute -right-4 -top-8 select-none text-[140px] font-black leading-none tracking-tighter text-white/[0.025] md:text-[190px]"
        >
            {{ player.team_name ?? "Free Agent" }}
        </div>
      </div>

      <div class="relative grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_auto]">
        <!-- Player identity -->
        <div class="min-w-0">
          <div class="mb-3 flex flex-wrap items-center gap-2">
            <span
              class="rounded-full border border-white/15 bg-black/40 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white/70"
            >
              Player Profile
            </span>

            <span
              v-if="player.injury_recovery_game_count > 0"
              class="inline-flex items-center gap-1.5 rounded-full border border-red-400/30 bg-red-500/20 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-red-200"
            >
              <i class="fa fa-medkit"></i>

              {{ roundedValue(player.injury_recovery_game_count) }}
              {{ roundedValue(player.injury_recovery_game_count) === 1 ? "Day" : "Days" }}
            </span>
          </div>

          <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h1
                  class="max-w-full truncate text-2xl font-black tracking-tight text-white sm:text-3xl lg:text-4xl"
                  :title="player.player_name"
                >
                  {{ player.player_name ?? "-" }}
                </h1>

                <span
                  class="rounded-md border px-2 py-1 text-xs font-black"
                  :class="
                    player.is_active == 0
                      ? 'border-red-400/20 bg-red-500/10 text-red-300'
                      : 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300'
                  "
                >
                  {{ player.is_active == 0 ? "RETIRED" : "ACTIVE" }}
                </span>
              </div>

              <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/65">
                <span>
                  <i class="fa fa-id-card mr-1.5 text-white/40"></i>
                  #{{ player.player_id ?? "-" }}
                </span>

                <span>
                  <i class="fa fa-calendar mr-1.5 text-white/40"></i>
                  Age {{ player.age ?? "N/A" }}
                </span>

                <span>
                  <i class="fa fa-map-marker-alt mr-1.5 text-white/40"></i>
                  {{ player.country ?? "-" }}
                </span>

                <span>
                  <i class="fa fa-users mr-1.5 text-white/40"></i>
                  {{ player.team_name ?? "-" }}
                </span>
              </div>
            </div>

            <!-- Rating -->
            <div
              class="flex shrink-0 items-center gap-4 rounded-2xl border border-white/10 bg-black/50 px-4 py-3 backdrop-blur-sm"
            >
              <div class="text-right">
                <div class="text-[9px] font-bold uppercase tracking-[0.18em] text-white/40">
                  Overall
                </div>

                <div class="text-4xl font-black leading-none text-yellow-400">
                  {{ player.overall_rating ?? "-" }}
                </div>

                <div class="mt-1 text-[9px] font-semibold uppercase tracking-wider text-white/40">
                  Player Rating
                </div>
              </div>

              <div class="h-12 w-px bg-white/10"></div>

              <div>
                <div class="text-[9px] font-bold uppercase tracking-[0.18em] text-white/40">
                  Potential
                </div>

                <div class="text-xl font-black text-white">
                  {{ player.potential_rating ?? "-" }}
                </div>
              </div>
            </div>
          </div>

          <!-- Quick tags -->
          <div class="mt-5 flex flex-wrap gap-2">
            <span
              v-if="player.position"
              class="rounded-lg border border-white/10 bg-black/40 px-3 py-1.5 text-xs font-bold text-white/80"
            >
              <i class="fa fa-running mr-1.5 text-blue-400"></i>
              {{ player.position }}
            </span>

            <span
              v-if="player.role"
              :class="[
                roleBadgeClass(player.role),
                'rounded-lg border border-white/10 px-3 py-1.5 text-xs font-bold'
              ]"
            >
              {{ player.role }}
            </span>

            <span
              v-if="player.draft_class"
              class="rounded-lg border border-white/10 bg-black/40 px-3 py-1.5 text-xs font-bold text-white/70"
            >
              <i class="fa fa-graduation-cap mr-1.5 text-purple-400"></i>
              Draft {{ player.draft_class }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- =======================================================
         MAIN CONTENT
    ======================================================== -->
    <div class="space-y-4 p-4 sm:p-5">

      <!-- =====================================================
           PLAYER / DRAFT / EXPERIENCE / CONTRACT
      ====================================================== -->
      <div class="grid min-w-0 grid-cols-1 gap-4 xl:grid-cols-4">

        <!-- Player Details -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-user"
            iconClass="text-blue-400"
            title="Player Details"
          />

          <div class="space-y-1.5">
            <InfoRow label="Player ID">
              #{{ player.player_id ?? "-" }}
            </InfoRow>

            <InfoRow label="Name">
              {{ player.player_name ?? "-" }}
            </InfoRow>

            <InfoRow label="Age">
              {{ player.age ?? "N/A" }}
            </InfoRow>

            <InfoRow label="Status">
              <span
                :class="
                  player.is_active == 0
                    ? 'text-red-400'
                    : 'text-emerald-400'
                "
              >
                {{ player.is_active == 0 ? "Retired" : "Active" }}
              </span>
            </InfoRow>

            <InfoRow label="Team">
              <span class="truncate" :title="player.team_name">
                {{ player.team_name ?? "-" }}
              </span>
            </InfoRow>

            <InfoRow label="Country">
              {{ player.country ?? "-" }}
            </InfoRow>
          </div>
        </section>

        <!-- Draft Details -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-bar-chart"
            iconClass="text-orange-400"
            title="Draft Details"
          />

          <div class="space-y-1.5">
            <InfoRow label="Draft">
              <span>
                {{ player.draft_status ?? "-" }}

                <span v-if="player.drafted_team" class="text-white/40">
                  ({{ player.drafted_team }})
                </span>
              </span>
            </InfoRow>

            <InfoRow label="Draft Class">
              {{ player.draft_class ?? "-" }}
            </InfoRow>

            <InfoRow label="Role">
              <span
                v-if="player.role"
                :class="[
                  roleBadgeClass(player.role),
                  'rounded-md px-2 py-0.5 text-[10px] font-bold'
                ]"
              >
                {{ player.role }}
              </span>

              <span v-else>-</span>
            </InfoRow>

            <InfoRow label="Position">
              {{ player.position ?? "-" }}
            </InfoRow>

            <InfoRow label="Overall">
              <span class="font-black text-yellow-400">
                {{ player.overall_rating ?? "-" }}
              </span>
            </InfoRow>

            <InfoRow label="Potential">
              <span class="font-bold text-purple-300">
                {{ player.potential_rating ?? "-" }}
              </span>
            </InfoRow>
          </div>
        </section>

        <!-- League Experience -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-list"
            iconClass="text-red-400"
            title="League Experience"
          />

          <div class="space-y-3">
            <div class="rounded-xl border border-white/5 bg-white/[0.025] p-3">
              <div class="mb-1 text-[9px] font-bold uppercase tracking-widest text-white/35">
                Regular Seasons
              </div>

              <div class="flex items-end justify-between gap-2">
                <span
                  class="text-sm font-bold"
                  :class="playerExpStatusClass(main_performance.season_count)"
                >
                  {{ playerExpStatusText(main_performance.season_count ?? 0) }}
                </span>

                <span class="text-2xl font-black text-white">
                  {{ main_performance.season_count ?? 0 }}
                </span>
              </div>
            </div>

            <div class="rounded-xl border border-white/5 bg-white/[0.025] p-3">
              <div class="mb-1 text-[9px] font-bold uppercase tracking-widest text-white/35">
                Playoff Seasons
              </div>

              <div class="flex items-end justify-between gap-2">
                <span
                  class="text-sm font-bold"
                  :class="playerExpStatusClass(main_performance.playoff_count)"
                >
                  {{ playerExpStatusText(main_performance.playoff_count ?? 0) }}
                </span>

                <span class="text-2xl font-black text-white">
                  {{ main_performance.playoff_count ?? 0 }}
                </span>
              </div>
            </div>
          </div>
        </section>

        <!-- Contract -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-file-contract"
            iconClass="text-emerald-400"
            title="Contract"
          />

          <div class="space-y-1.5">
            <InfoRow label="Type">
              <span class="font-bold uppercase text-white">
                {{ player.contract_type?.toUpperCase() ?? "UNSIGNED" }}
              </span>
            </InfoRow>

            <InfoRow label="Salary">
              <span class="font-bold text-emerald-400">
                {{ moneyFormatter(player.salary ?? 0) }}
              </span>
            </InfoRow>

            <InfoRow label="Contract Left">
              <span
                :class="
                  player.contract_years > 0
                    ? 'text-white'
                    : 'text-white/35'
                "
              >
                {{
                  player.contract_years > 0
                    ? player.contract_years + " years left"
                    : "Unsigned"
                }}
              </span>
            </InfoRow>

            <InfoRow label="Hardship Left">
              <span
                :class="
                  player.hardship_contract > 0
                    ? 'text-orange-300'
                    : 'text-white/35'
                "
              >
                {{
                  player.hardship_contract > 0
                    ? player.hardship_contract + " games left"
                    : "None"
                }}
              </span>
            </InfoRow>
          </div>
        </section>
      </div>

      <!-- =====================================================
           PLAYOFF + CAREER HIGHS
      ====================================================== -->
      <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">

        <!-- Playoff Performance -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-network-wired"
            iconClass="text-purple-400"
            title="Playoff Performance"
          />

          <div
            v-if="main_performance.playoff_performance"
            class="grid grid-cols-2 gap-2 sm:grid-cols-3"
          >
            <StatBox
              label="Conf. Play-Ins"
              :value="playoffPlayIns"
            />

            <StatBox
              label="Quarter Finals"
              :value="main_performance.playoff_performance.round_of_16_appearances ?? 0"
            />

            <StatBox
              label="Semi Finals"
              :value="main_performance.playoff_performance.quarter_finals_appearances ?? 0"
            />

            <StatBox
              label="Conf. Finals"
              :value="main_performance.playoff_performance.semi_finals_appearances ?? 0"
            />

            <StatBox
              label="The Big 4"
              :value="main_performance.playoff_performance.interconference_semi_finals_appearances ?? 0"
            />

            <StatBox
              label="The Finals"
              :value="main_performance.playoff_performance.finals_appearances ?? 0"
            />

            <StatBox
              label="Finals MVP"
              :value="main_performance.mvp_count ?? 0"
              highlight
            />
          </div>

          <EmptyState
            v-else
            text="No playoff performance data available."
          />
        </section>

        <!-- Career Highs -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-chart-line"
            iconClass="text-cyan-400"
            title="Career Highs"
          />

          <div
            v-if="main_performance.career_highs"
            class="grid grid-cols-2 gap-2 sm:grid-cols-4"
          >
            <StatBox
              label="Minutes"
              :value="main_performance.career_highs.minutes ?? 'N/A'"
            />

            <StatBox
              label="Points"
              :value="main_performance.career_highs.points ?? 'N/A'"
              highlight
            />

            <StatBox
              label="Rebounds"
              :value="main_performance.career_highs.rebounds ?? 'N/A'"
            />

            <StatBox
              label="Assists"
              :value="main_performance.career_highs.assists ?? 'N/A'"
            />

            <StatBox
              label="Steals"
              :value="main_performance.career_highs.steals ?? 'N/A'"
            />

            <StatBox
              label="Blocks"
              :value="main_performance.career_highs.blocks ?? 'N/A'"
            />

            <StatBox
              label="Turnovers"
              :value="main_performance.career_highs.turnovers ?? 'N/A'"
            />
          </div>

          <EmptyState
            v-else
            text="No career highs data available."
          />
        </section>
      </div>

      <!-- =====================================================
           ACHIEVEMENTS
      ====================================================== -->
      <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-3">

        <!-- Championships -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-trophy"
            iconClass="text-yellow-400"
            :title="`Championships (${championshipCount})`"
          />

          <div
            v-if="championshipCount > 0"
            class="max-h-[360px] space-y-4 overflow-y-auto pr-1 custom-scrollbar"
          >
            <!-- National -->
            <AchievementGroup
              v-if="main_performance.national_championships?.length > 0"
              title="National Championships"
              :count="main_performance.national_championships.length"
            >
              <AchievementItem
                v-for="(season, index) in main_performance.national_championships"
                :key="`national-${index}`"
                icon="fa-trophy"
                :title="season.season_name"
                :subtitle="season.championship_team"
              />
            </AchievementGroup>

            <!-- Conference -->
            <AchievementGroup
              v-if="main_performance.conference_championships?.length > 0"
              title="Conference Championships"
              :count="main_performance.conference_championships.length"
            >
              <AchievementItem
                v-for="(season, index) in main_performance.conference_championships"
                :key="`conference-${index}`"
                icon="fa-trophy"
                :title="season.season_name"
                :subtitle="season.championship_team"
              />
            </AchievementGroup>

            <!-- National Overall -->
            <AchievementGroup
              v-if="main_performance.national_overall_champions?.length > 0"
              title="Nationals Rank #1"
              :count="main_performance.national_overall_champions.length"
            >
              <AchievementItem
                v-for="(season, index) in main_performance.national_overall_champions"
                :key="`national-overall-${index}`"
                icon="fa-trophy"
                :title="season.season_name"
                :subtitle="season.team_name"
              />
            </AchievementGroup>

            <!-- Conference Overall -->
            <AchievementGroup
              v-if="main_performance.conference_overall_champions?.length > 0"
              title="Conference Rank #1"
              :count="main_performance.conference_overall_champions.length"
            >
              <AchievementItem
                v-for="(season, index) in main_performance.conference_overall_champions"
                :key="`conference-overall-${index}`"
                icon="fa-trophy"
                :title="season.season_name"
                :subtitle="season.team_name"
              />
            </AchievementGroup>
          </div>

          <EmptyState
            v-else
            text="No championship available."
          />
        </section>

        <!-- MVP -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-star"
            iconClass="text-yellow-400"
            :title="`MVP (${main_performance.mvp_seasons?.length ?? 0})`"
          />

          <div
            v-if="main_performance.mvp_seasons?.length > 0"
            class="max-h-[360px] space-y-2 overflow-y-auto pr-1 custom-scrollbar"
          >
            <div
              v-for="(season, index) in main_performance.mvp_seasons"
              :key="`mvp-${index}`"
              class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.025] p-3 transition hover:bg-white/[0.05]"
            >
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-yellow-500/10 text-yellow-400"
              >
                <i class="fa fa-star"></i>
              </div>

              <div class="min-w-0">
                <div class="text-sm font-bold text-white">
                  MVP Season
                </div>

                <div class="text-xs text-white/45">
                  {{ season }}
                </div>
              </div>
            </div>
          </div>

          <EmptyState
            v-else
            text="No MVP data available."
          />
        </section>

        <!-- Awards -->
        <section class="profile-card">
          <SectionTitle
            icon="fa-medal"
            iconClass="text-yellow-400"
            :title="`Awards (${main_performance.awards?.length ?? 0})`"
          />

          <div
            v-if="main_performance.awards?.length > 0"
            class="max-h-[360px] space-y-2 overflow-y-auto pr-1 custom-scrollbar"
          >
            <div
              v-for="(season, index) in main_performance.awards"
              :key="`award-${index}`"
              :title="season.team_name"
              class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.025] p-3 transition hover:bg-white/[0.05]"
            >
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-yellow-500/10 text-yellow-400"
              >
                <i class="fa fa-medal"></i>
              </div>

              <div class="min-w-0">
                <div class="truncate text-xs font-bold text-white">
                  {{ season.award_name }}
                </div>

                <div class="mt-0.5 truncate text-[10px] text-white/40">
                  {{ season.season_name }}

                  <span v-if="season.team_name">
                    · {{ season.team_name }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <EmptyState
            v-else
            text="No awards data available."
          />
        </section>
      </div>

      <!-- =====================================================
           SCOUTING REPORT
      ====================================================== -->
      <section
        class="overflow-hidden rounded-2xl border border-yellow-500/15 bg-gradient-to-r from-yellow-500/[0.08] to-transparent"
      >
        <div
          class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"
        >
          <div class="flex min-w-0 items-center gap-4">
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yellow-500/10 text-yellow-400"
            >
              <i class="fa fa-clipboard text-lg"></i>
            </div>

            <div class="min-w-0">
              <div class="text-sm font-black text-white">
                Scouting Report
              </div>

              <div class="mt-0.5 text-xs text-white/40">
                Internal player evaluation from Champs Narnia
              </div>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-2.5 text-xs font-bold text-yellow-300 transition hover:bg-yellow-500/20"
            @click="isPlayerScoutingReportOpen = true"
          >
            <i class="fa fa-envelope"></i>
            View Scouting Report
          </button>
        </div>
      </section>
    </div>
  </div>

  <!-- =========================================================
       LOADING
  ========================================================== -->
  <div
    v-else
    class="flex min-h-[320px] w-full items-center justify-center rounded-2xl border border-white/10 bg-[#080808]"
  >
    <div class="text-center">
      <div
        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-500/10"
      >
        <i class="fa fa-spinner fa-spin text-2xl text-blue-400"></i>
      </div>

      <p class="mt-4 text-sm font-semibold text-white/60">
        Loading player data...
      </p>

      <p class="mt-1 text-xs text-white/30">
        Fetching player profile and career information
      </p>
    </div>
  </div>

  <!-- =========================================================
       SCOUTING MODAL
  ========================================================== -->
  <Modal
    :show="isPlayerScoutingReportOpen"
    :maxWidth="'sm'"
    title="Player Scouting Report"
    @close="isPlayerScoutingReportOpen = false"
  >
    <div class="bg-[#111111] p-5 text-white">
      <div class="mb-4 flex items-center gap-3">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-500/10 text-yellow-400"
        >
          <i class="fa fa-clipboard"></i>
        </div>

        <div>
          <div class="text-sm font-black">
            Scouting Report
          </div>

          <div class="text-[10px] uppercase tracking-widest text-white/30">
            Champs Narnia
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-white/10 bg-black/30 p-4 text-sm leading-7 text-white/70"
      >
        {{ main_performance.scouting_report ?? "N/A" }}
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, onMounted, ref, watch, defineComponent, h } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

import {
  roleBadgeClass,
  playerExpStatusClass,
  playerExpStatusText,
  moneyFormatter,
} from "@/Utility/Formatter";

const props = defineProps({
  player_id: {
    type: Number,
    required: true,
  },
  season_logs: {
    type: Number,
    required: true,
  },
  playoff_logs: {
    type: Number,
    required: true,
  },
});

const main_performance = ref(null);
const isPlayerScoutingReportOpen = ref(false);
const isLoading = ref(false);
const player_id = ref(props.player_id);

const player = computed(() => main_performance.value?.player_details ?? {});

const primaryColor = computed(() => {
  const color = player.value.primary_color;

  if (!color) {
    return "#111111";
  }

  return color.startsWith("#") ? color : `#${color}`;
});

const secondaryColor = computed(() => {
  const color = player.value.secondary_color;

  if (!color) {
    return "#111111";
  }

  return color.startsWith("#") ? color : `#${color}`;
});

const heroStyle = computed(() => ({
  background: `
    linear-gradient(
      120deg,
      ${primaryColor.value} 0%,
      ${secondaryColor.value} 75%,
      #080808 100%
    )
  `,
}));

/*
|--------------------------------------------------------------------------
| Playoff Play-Ins
|--------------------------------------------------------------------------
|
| The original expression could produce NaN if one of the values was
| undefined. Convert every value individually before adding them.
|
*/
const playoffPlayIns = computed(() => {
  const playoff = main_performance.value?.playoff_performance;

  if (!playoff) {
    return 0;
  }

  return (
    Number(playoff.play_ins_elims_round_1_appearances ?? 0) +
    Number(playoff.play_ins_elims_round_2_appearances ?? 0) +
    Number(playoff.play_ins_finals_appearances ?? 0)
  );
});

/*
|--------------------------------------------------------------------------
| Championship Count
|--------------------------------------------------------------------------
*/
const championshipCount = computed(() => {
  const performance = main_performance.value;

  if (!performance) {
    return 0;
  }

  return (
    (performance.national_championships?.length ?? 0) +
    (performance.conference_championships?.length ?? 0) +
    (performance.national_overall_champions?.length ?? 0) +
    (performance.conference_overall_champions?.length ?? 0)
  );
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
const roundedValue = (value) => {
  if (value === null || value === undefined || value === "") {
    return 0;
  }

  const result = Math.round(Number(value));

  return Number.isNaN(result) ? 0 : result;
};

/*
|--------------------------------------------------------------------------
| Fetch
|--------------------------------------------------------------------------
*/
const fetchPlayerMainPerformance = async () => {
  try {
    isLoading.value = true;

    const response = await axios.post(
      route("players.main.performance"),
      {
        player_id: player_id.value,
      }
    );

    main_performance.value = response.data;
  } catch (error) {
    console.error(
      "Error fetching player main performance:",
      error
    );

    main_performance.value = null;
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchPlayerMainPerformance();
});

/*
|--------------------------------------------------------------------------
| Keep player data synchronized if parent changes player_id
|--------------------------------------------------------------------------
*/
watch(
  () => props.player_id,
  (newPlayerId) => {
    if (newPlayerId === player_id.value) {
      return;
    }

    player_id.value = newPlayerId;
    fetchPlayerMainPerformance();
  }
);

/*
|--------------------------------------------------------------------------
| Local Components
|--------------------------------------------------------------------------
*/

const SectionTitle = defineComponent({
  props: {
    icon: {
      type: String,
      required: true,
    },
    iconClass: {
      type: String,
      default: "text-white",
    },
    title: {
      type: String,
      required: true,
    },
  },

  setup(props) {
    return () =>
      h(
        "div",
        {
          class:
            "mb-4 flex items-center gap-2.5 border-b border-white/5 pb-3",
        },
        [
          h(
            "div",
            {
              class:
                "flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/[0.04]",
            },
            [
              h("i", {
                class: `fa ${props.icon} ${props.iconClass}`,
              }),
            ]
          ),

          h(
            "h3",
            {
              class:
                "min-w-0 truncate text-xs font-black uppercase tracking-[0.12em] text-white/80",
            },
            props.title
          ),
        ]
      );
  },
});

const InfoRow = defineComponent({
  props: {
    label: {
      type: String,
      required: true,
    },
  },

  setup(props, { slots }) {
    return () =>
      h(
        "div",
        {
          class:
            "flex min-w-0 items-center justify-between gap-4 border-b border-white/[0.035] py-2 last:border-0",
        },
        [
          h(
            "span",
            {
              class:
                "shrink-0 text-[10px] font-bold uppercase tracking-wider text-white/30",
            },
            props.label
          ),

          h(
            "span",
            {
              class:
                "min-w-0 text-right text-xs font-semibold text-white/70",
            },
            slots.default?.()
          ),
        ]
      );
  },
});

const StatBox = defineComponent({
  props: {
    label: {
      type: String,
      required: true,
    },
    value: {
      default: "-",
    },
    highlight: {
      type: Boolean,
      default: false,
    },
  },

  setup(props) {
    return () =>
      h(
        "div",
        {
          class: [
            "rounded-xl border p-3 transition",
            props.highlight
              ? "border-yellow-500/15 bg-yellow-500/[0.06]"
              : "border-white/5 bg-white/[0.025] hover:bg-white/[0.045]",
          ],
        },
        [
          h(
            "div",
            {
              class:
                "truncate text-[9px] font-bold uppercase tracking-wider text-white/30",
            },
            props.label
          ),

          h(
            "div",
            {
              class: [
                "mt-1 text-xl font-black",
                props.highlight
                  ? "text-yellow-400"
                  : "text-white",
              ],
            },
            String(props.value ?? "-")
          ),
        ]
      );
  },
});

const AchievementGroup = defineComponent({
  props: {
    title: {
      type: String,
      required: true,
    },
    count: {
      type: Number,
      default: 0,
    },
  },

  setup(props, { slots }) {
    return () =>
      h("div", {}, [
        h(
          "div",
          {
            class:
              "mb-2 flex items-center justify-between gap-2",
          },
          [
            h(
              "span",
              {
                class:
                  "text-[10px] font-black uppercase tracking-wider text-white/45",
              },
              props.title
            ),

            h(
              "span",
              {
                class:
                  "rounded-md bg-white/5 px-1.5 py-0.5 text-[9px] font-bold text-white/35",
              },
              String(props.count)
            ),
          ]
        ),

        h(
          "div",
          {
            class: "space-y-1.5",
          },
          slots.default?.()
        ),
      ]);
  },
});

const AchievementItem = defineComponent({
  props: {
    icon: {
      type: String,
      default: "fa-trophy",
    },
    title: {
      type: String,
      default: "-",
    },
    subtitle: {
      type: String,
      default: "",
    },
  },

  setup(props) {
    return () =>
      h(
        "div",
        {
          class:
            "flex items-center gap-3 rounded-lg border border-white/5 bg-white/[0.02] px-3 py-2",
        },
        [
          h(
            "div",
            {
              class:
                "flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-yellow-500/10 text-yellow-400",
            },
            [
              h("i", {
                class: `fa ${props.icon} text-[10px]`,
              }),
            ]
          ),

          h(
            "div",
            {
              class: "min-w-0",
            },
            [
              h(
                "div",
                {
                  class:
                    "truncate text-xs font-bold text-white/80",
                },
                props.title
              ),

              props.subtitle
                ? h(
                    "div",
                    {
                      class:
                        "truncate text-[9px] text-white/35",
                    },
                    props.subtitle
                  )
                : null,
            ]
          ),
        ]
      );
  },
});

const EmptyState = defineComponent({
  props: {
    text: {
      type: String,
      default: "No data available.",
    },
  },

  setup(props) {
    return () =>
      h(
        "div",
        {
          class:
            "flex min-h-[120px] items-center justify-center rounded-xl border border-dashed border-white/5 bg-white/[0.015] px-4 text-center",
        },
        [
          h(
            "span",
            {
              class: "text-xs text-white/25",
            },
            props.text
          ),
        ]
      );
  },
});
</script>

<style scoped>
.profile-card {
  @apply min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-[#101010] p-4 shadow-lg;
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.12);
  border-radius: 999px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>