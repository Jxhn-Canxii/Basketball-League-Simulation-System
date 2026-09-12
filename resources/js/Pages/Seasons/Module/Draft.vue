<template>
  <div class="min-h-screen bg-[#050505] text-white">
    <!-- ========================================================= -->
    <!-- TOP BAR -->
    <!-- ========================================================= -->
    <div
      class="sticky top-0 z-30 border-b border-white/10 bg-black/95 backdrop-blur"
    >
      <div
        class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-3"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-900/30"
          >
            <i class="fa fa-basketball-ball text-lg"></i>
          </div>

          <div>
            <h1 class="text-xl font-black tracking-tight">
              DRAFT NIGHT
            </h1>

            <p
              class="text-[10px] uppercase tracking-[0.25em] text-gray-500"
            >
              Rookie Draft
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-if="!isHide && !draftRunning && !draftFinished"
            @click.prevent="startDraft"
            class="rounded-lg bg-blue-600 px-5 py-2.5 text-xs font-black uppercase tracking-wide shadow-lg shadow-blue-900/20 transition hover:bg-blue-500"
          >
            <i class="fa fa-bolt mr-1"></i>
            Start Draft
          </button>

          <button
            v-if="!isHide && !draftFinished"
            @click.prevent="addMultiplePlayers(160)"
            class="rounded-lg bg-green-600 px-3 py-2.5 text-xs font-bold shadow-lg transition hover:bg-green-500 disabled:opacity-50"
            :disabled="draftRunning"
          >
            <i class="fa fa-user mr-1"></i>
            Add Rookies
          </button>

          <div
            v-if="draftRunning"
            class="flex items-center gap-2 rounded-lg border border-blue-500/20 bg-blue-500/10 px-3 py-2"
          >
            <span
              class="h-2 w-2 animate-pulse rounded-full bg-blue-400"
            ></span>

            <span
              class="text-[10px] font-black uppercase tracking-wider text-blue-400"
            >
              Live Draft
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-[1600px] px-3 py-4 md:px-6">
      <!-- ========================================================= -->
      <!-- TOP STATISTICS -->
      <!-- ========================================================= -->

      <div
        class="mb-4 rounded-xl border border-white/10 bg-[#0b0b0b] p-2"
      >
        <TopStatistics :key="key" />
      </div>

      <!-- ========================================================= -->
      <!-- DRAFT PROGRESS -->
      <!-- ========================================================= -->

      <div
        v-if="draftOrder.length"
        class="mb-4 overflow-hidden rounded-xl border border-white/10 bg-[#0b0b0b]"
      >
        <div class="flex items-center justify-between px-4 py-3">
          <div>
            <div
              class="text-[9px] font-black uppercase tracking-[0.25em] text-gray-600"
            >
              Draft Progress
            </div>

            <div class="mt-1 text-sm font-black">
              {{ draftProgress.current }}
              <span class="text-gray-600">
                /
              </span>
              {{ draftProgress.total }}
            </div>
          </div>

          <div class="text-right">
            <div
              class="text-[9px] font-black uppercase tracking-[0.25em] text-gray-600"
            >
              Current Round
            </div>

            <div class="mt-1 text-sm font-black text-blue-400">
              Round {{ currentPick?.round ?? 2 }}
            </div>
          </div>
        </div>

        <div class="h-1 bg-white/5">
          <div
            class="h-full bg-blue-600 transition-all duration-700"
            :style="{ width: `${draftProgress.percent}%` }"
          ></div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- MAIN LAYOUT -->
      <!-- ========================================================= -->

      <div
        class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]"
      >
        <!-- ======================================================= -->
        <!-- MAIN STAGE -->
        <!-- ======================================================= -->

        <main>
          <!-- ===================================================== -->
          <!-- EMPTY / PRE-DRAFT -->
          <!-- ===================================================== -->

          <div
            v-if="!currentPick && !currentDecision"
            class="flex min-h-[650px] items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-[#111] via-[#080808] to-[#050505]"
          >
            <div class="relative z-10 px-6 text-center">
              <div
                class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-white/5"
              >
                <i
                  class="fa fa-basketball-ball text-4xl text-gray-600"
                ></i>
              </div>

              <div
                v-if="draftFinished"
                class="mb-3 text-[10px] font-black uppercase tracking-[0.3em] text-green-400"
              >
                Draft Complete
              </div>

              <div
                v-else
                class="mb-3 text-[10px] font-black uppercase tracking-[0.3em] text-blue-400"
              >
                Rookie Draft
              </div>

              <h2 class="text-4xl font-black md:text-6xl">
                {{ draftFinished ? "DRAFT COMPLETE" : "DRAFT NIGHT" }}
              </h2>

              <p
                class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-500"
              >
                {{
                  draftFinished
                    ? "All scheduled selections have been completed."
                    : "The league is ready for the next selection."
                }}
              </p>

              <button
                v-if="!draftFinished && draftOrder.length"
                @click.prevent="startDraft"
                :disabled="draftRunning"
                class="mt-8 rounded-xl bg-blue-600 px-10 py-4 text-sm font-black uppercase tracking-widest shadow-lg shadow-blue-900/30 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <i class="fa fa-bolt mr-2"></i>
                Start Draft
              </button>
            </div>
          </div>

          <!-- ===================================================== -->
          <!-- DRAFT STAGE -->
          <!-- ===================================================== -->

          <div
            v-else
            class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b0b0b] shadow-2xl"
          >
            <!-- =================================================== -->
            <!-- DRAFT HEADER -->
            <!-- =================================================== -->

            <div
              class="border-b border-white/10 bg-gradient-to-r from-[#101010] to-[#080808] px-5 py-5"
            >
              <div
                class="flex flex-wrap items-center justify-between gap-5"
              >
                <div>
                  <div
                    class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-400"
                  >
                    Round {{ currentPick?.round ?? currentDecision?.round }}
                  </div>

                  <div class="mt-1 flex items-end gap-3">
                    <span
                      class="text-5xl font-black leading-none md:text-6xl"
                    >
                      #{{
                        currentPick?.pick ??
                        currentDecision?.pick_number
                      }}
                    </span>

                    <span
                      class="pb-1 text-sm font-bold uppercase text-gray-600"
                    >
                      Overall Pick
                    </span>
                  </div>
                </div>

                <div class="text-right">
                  <div
                    class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-600"
                  >
                    Selection
                  </div>

                  <div class="mt-1 text-xl font-black">
                    {{ draftProgress.current }}
                    <span class="text-gray-700">
                      /
                    </span>
                    {{ draftProgress.total }}
                  </div>
                </div>
              </div>
            </div>

            <!-- =================================================== -->
            <!-- ON THE CLOCK -->
            <!-- =================================================== -->

            <div
              v-if="!currentDecision"
              class="relative flex min-h-[590px] items-center justify-center overflow-hidden"
            >
              <div
                class="pointer-events-none absolute -right-10 -top-24 select-none text-[280px] font-black leading-none text-white/[0.02]"
              >
                {{ currentPick?.pick }}
              </div>

              <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-blue-950/20 via-transparent to-transparent"
              ></div>

              <div
                class="relative z-10 w-full max-w-3xl px-6 py-16 text-center"
              >
                <!-- STATUS -->

                <div
                  class="mx-auto mb-7 inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.3em] text-red-400"
                >
                  <span
                    class="h-2 w-2 animate-pulse rounded-full bg-red-500"
                  ></span>

                  {{
                    draftRunning
                      ? "On The Clock"
                      : "Ready To Draft"
                  }}
                </div>

                <!-- TEAM ICON -->

                <div
                  class="mx-auto mb-7 flex h-28 w-28 items-center justify-center rounded-3xl border border-blue-500/20 bg-blue-500/10 shadow-2xl shadow-blue-900/10"
                >
                  <i
                    class="fa fa-shield-alt text-5xl text-blue-400"
                  ></i>
                </div>

                <div
                  class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-600"
                >
                  The next team selecting is
                </div>

                <h2
                  class="mt-3 text-4xl font-black uppercase tracking-tight md:text-6xl"
                >
                  {{ currentPick?.team_name }}
                </h2>

                <div class="mx-auto mt-6 h-px w-20 bg-blue-500"></div>

                <p class="mt-5 text-sm text-gray-500">
                  {{
                    draftRunning
                      ? "The front office is making its selection..."
                      : "The next selection is ready."
                  }}
                </p>

                <!-- MANUAL START / CONTINUE -->

                <button
                  v-if="!draftRunning"
                  @click.prevent="runNextPick"
                  class="mt-8 rounded-xl bg-blue-600 px-10 py-4 text-sm font-black uppercase tracking-widest shadow-lg shadow-blue-900/30 transition hover:scale-[1.02] hover:bg-blue-500"
                >
                  <i class="fa fa-check mr-2"></i>
                  Make Pick
                </button>

                <!-- PROCESSING -->

                <div
                  v-else
                  class="mx-auto mt-8 flex w-fit items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-6 py-4"
                >
                  <span
                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/20 border-t-blue-400"
                  ></span>

                  <span
                    class="text-xs font-black uppercase tracking-widest text-gray-400"
                  >
                    Processing Selection
                  </span>
                </div>
              </div>
            </div>

            <!-- =================================================== -->
            <!-- PLAYER REVEAL -->
            <!-- =================================================== -->

            <div
              v-else
              class="relative min-h-[590px] overflow-hidden"
            >
              <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-blue-950/30 via-transparent to-transparent"
              ></div>

              <div
                class="pointer-events-none absolute -right-16 top-0 text-[300px] font-black leading-none text-white/[0.025]"
              >
                {{ currentDecision.pick_number }}
              </div>

              <div
                class="relative z-10 px-5 py-10 md:px-10"
              >
                <!-- PICK IS IN -->

                <div class="mb-8 text-center">
                  <div
                    class="inline-flex items-center gap-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.3em] text-blue-400"
                  >
                    <i class="fa fa-check-circle"></i>
                    Pick Is In
                  </div>

                  <div
                    class="mt-3 text-xs font-bold uppercase tracking-[0.2em] text-gray-600"
                  >
                    With the #{{ currentDecision.pick_number }} pick
                  </div>
                </div>

                <!-- ================================================= -->
                <!-- PLAYER + DECISION -->
                <!-- ================================================= -->

                <div
                  class="grid gap-8 lg:grid-cols-[260px_minmax(0,1fr)]"
                >
                  <!-- PLAYER CARD -->

                  <div class="relative">
                    <div
                      class="absolute inset-0 scale-90 rounded-full bg-blue-500/10 blur-3xl"
                    ></div>

                    <div
                      class="relative mx-auto flex aspect-square max-w-[260px] items-center justify-center rounded-3xl border border-white/10 bg-gradient-to-br from-[#191919] to-[#080808] shadow-2xl"
                    >
                      <div class="text-center">
                        <div
                          class="mx-auto mb-4 flex h-32 w-32 items-center justify-center rounded-full border border-white/5 bg-white/5"
                        >
                          <i
                            class="fa fa-user text-6xl text-gray-600"
                          ></i>
                        </div>

                        <div
                          class="text-[10px] font-black uppercase tracking-[0.25em] text-blue-400"
                        >
                          Rookie
                        </div>

                        <div
                          class="mt-1 text-[9px] uppercase tracking-widest text-gray-700"
                        >
                          {{ currentDecision.player?.position ?? "-" }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- PLAYER INFORMATION -->

                  <div>
                    <div
                      class="text-xs font-black uppercase tracking-[0.25em] text-blue-400"
                    >
                      {{ currentDecision.team?.name }}
                      selects
                    </div>

                    <h2
                      class="mt-2 text-4xl font-black leading-none tracking-tight md:text-6xl"
                    >
                      {{ currentDecision.player?.name }}
                    </h2>

                    <div class="mt-3 text-sm text-gray-500">
                      Age {{ currentDecision.player?.age ?? "-" }}
                    </div>

                    <!-- PLAYER STATS -->

                    <div
                      class="mt-7 grid grid-cols-2 gap-2 sm:grid-cols-4"
                    >
                      <div
                        class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                      >
                        <div
                          class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                        >
                          Position
                        </div>

                        <div class="mt-1 text-xl font-black">
                          {{ currentDecision.player?.position ?? "-" }}
                        </div>
                      </div>

                      <div
                        class="rounded-xl border border-white/10 bg-white/[0.03] p-4"
                      >
                        <div
                          class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                        >
                          Overall
                        </div>

                        <div
                          class="mt-1 text-xl font-black text-blue-400"
                        >
                          {{
                            currentDecision.player?.overall_rating ?? 0
                          }}
                        </div>
                      </div>

                      <div
                        class="rounded-xl border border-white/10 bg-white/[0.03] p-4 sm:col-span-2"
                      >
                        <div
                          class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                        >
                          Archetype
                        </div>

                        <div
                          class="mt-1 text-xl font-black capitalize"
                        >
                          {{
                            formatArchetype(
                              currentDecision.player?.archetype
                            )
                          }}
                        </div>
                      </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- DECISION MAKER -->
                    <!-- ================================================= -->

                    <div
                      class="mt-5 rounded-xl border border-white/10 bg-white/[0.025] p-4"
                    >
                      <div
                        class="flex items-center gap-3"
                      >
                        <div
                          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600"
                        >
                          <i class="fa fa-user-tie"></i>
                        </div>

                        <div class="min-w-0">
                          <div
                            class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                          >
                            Draft Decision
                          </div>

                          <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span class="font-black">
                              {{
                                currentDecision.decision?.maker_name ??
                                "Head Coach"
                              }}
                            </span>

                            <span
                              class="rounded bg-white/5 px-2 py-0.5 text-[8px] font-black uppercase tracking-wider text-gray-500"
                            >
                              {{
                                currentDecision.decision?.maker_type ??
                                "coach"
                              }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ================================================= -->
                <!-- DECISION REASON -->
                <!-- ================================================= -->

                <div
                  class="mt-8 rounded-2xl border border-blue-500/10 bg-blue-500/[0.035] p-5"
                >
                  <div
                    class="flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.25em] text-blue-400"
                  >
                    <i class="fa fa-comment-alt"></i>
                    Why This Pick?
                  </div>

                  <p
                    class="mt-3 text-sm font-medium leading-7 text-gray-300"
                  >
                    {{
                      currentDecision.decision?.reason ??
                      "The coaching staff selected the best available prospect."
                    }}
                  </p>

                  <!-- FACTORS -->

                  <div
                    v-if="
                      currentDecision.decision?.factors?.length
                    "
                    class="mt-4 flex flex-wrap gap-2"
                  >
                    <span
                      v-for="factor in currentDecision.decision.factors"
                      :key="factor"
                      class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-[9px] font-black uppercase tracking-wider text-gray-400"
                    >
                      {{ formatArchetype(factor) }}
                    </span>
                  </div>

                  <!-- SCORE -->

                  <div
                    v-if="
                      currentDecision.decision?.draft_score !== null &&
                      currentDecision.decision?.draft_score !== undefined
                    "
                    class="mt-4 flex items-center justify-between border-t border-white/10 pt-4"
                  >
                    <span
                      class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                    >
                      Coach Draft Score
                    </span>

                    <span
                      class="text-lg font-black text-blue-400"
                    >
                      {{
                        Number(
                          currentDecision.decision.draft_score
                        ).toFixed(1)
                      }}
                    </span>
                  </div>
                </div>

                <!-- ================================================= -->
                <!-- CONTRACT / WAIVER -->
                <!-- ================================================= -->

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                  <!-- SIGNING -->

                  <div
                    class="rounded-xl border border-white/10 bg-white/[0.025] p-4"
                  >
                    <div
                      class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                    >
                      Draft Result
                    </div>

                    <div class="mt-2 flex items-center gap-3">
                      <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg"
                        :class="
                          currentDecision.signing?.signed
                            ? 'bg-green-500/10 text-green-400'
                            : 'bg-yellow-500/10 text-yellow-400'
                        "
                      >
                        <i
                          :class="
                            currentDecision.signing?.signed
                              ? 'fa fa-file-signature'
                              : 'fa fa-user-clock'
                          "
                        ></i>
                      </div>

                      <div>
                        <div class="font-black">
                          {{
                            currentDecision.signing?.signed
                              ? "Signed to Roster"
                              : "Draft Rights Only"
                          }}
                        </div>

                        <div
                          v-if="currentDecision.signing?.signed"
                          class="mt-1 text-[10px] text-gray-600"
                        >
                          {{
                            currentDecision.signing.contract_years
                          }}
                          years ·
                          {{
                            currentDecision.signing.contract_type ??
                            "Contract"
                          }}
                        </div>

                        <div
                          v-else
                          class="mt-1 text-[10px] text-gray-600"
                        >
                          Player remains in the free-agent pool.
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- WAIVER -->

                  <div
                    class="rounded-xl border border-white/10 bg-white/[0.025] p-4"
                  >
                    <div
                      class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                    >
                      Roster Move
                    </div>

                    <div
                      v-if="currentDecision.waiver?.waived"
                      class="mt-2 flex items-center gap-3"
                    >
                      <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-500/10 text-red-400"
                      >
                        <i class="fa fa-user-minus"></i>
                      </div>

                      <div>
                        <div class="font-black">
                          {{
                            currentDecision.waiver.player_name
                          }}
                        </div>

                        <div
                          class="mt-1 text-[10px] text-red-400"
                        >
                          Waived to create roster space
                        </div>
                      </div>
                    </div>

                    <div
                      v-else
                      class="mt-2 flex items-center gap-3"
                    >
                      <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-gray-500"
                      >
                        <i class="fa fa-minus"></i>
                      </div>

                      <div>
                        <div class="font-black text-gray-500">
                          No Roster Move
                        </div>

                        <div
                          class="mt-1 text-[10px] text-gray-700"
                        >
                          No player was waived.
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ================================================= -->
                <!-- NEXT PICK -->
                <!-- ================================================= -->

                <div
                  class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-5"
                >
                  <div>
                    <div
                      class="text-[9px] font-black uppercase tracking-widest text-gray-600"
                    >
                      Next Pick
                    </div>

                    <div
                      v-if="currentDecision.next_pick"
                      class="mt-1 text-sm font-black"
                    >
                      <span class="text-blue-400">
                        R{{ currentDecision.next_pick.round }}
                        #{{ currentDecision.next_pick.pick_number }}
                      </span>

                      <span class="mx-2 text-gray-700">
                        —
                      </span>

                      <span>
                        {{ currentDecision.next_pick.team_name }}
                      </span>
                    </div>

                    <div
                      v-else
                      class="mt-1 text-sm font-black text-green-400"
                    >
                      Draft Complete
                    </div>
                  </div>

                  <div
                    v-if="draftRunning"
                    class="flex items-center gap-2 rounded-lg bg-blue-500/10 px-4 py-2.5 text-[10px] font-black uppercase tracking-wider text-blue-400"
                  >
                    <span
                      class="h-2 w-2 animate-pulse rounded-full bg-blue-400"
                    ></span>

                    Next selection coming up
                  </div>

                  <button
                    v-if="currentDecision.next_pick && !draftRunning"
                    @click.prevent="runNextPick"
                    class="rounded-lg bg-blue-600 px-5 py-3 text-xs font-black uppercase tracking-wider transition hover:bg-blue-500"
                  >
                    Next Pick
                    <i class="fa fa-arrow-right ml-2"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </main>

        <!-- ======================================================= -->
        <!-- DRAFT BOARD -->
        <!-- ======================================================= -->

        <aside
          class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b0b0b]"
        >
          <div
            class="border-b border-white/10 px-4 py-4"
          >
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-black uppercase">
                  Draft Board
                </h3>

                <p
                  class="mt-1 text-[9px] uppercase tracking-[0.2em] text-gray-600"
                >
                  Latest selections
                </p>
              </div>

              <div
                class="rounded-md bg-white/5 px-2 py-1 text-[9px] font-black text-gray-500"
              >
                {{ draftHistory.length }} PICKS
              </div>
            </div>
          </div>

          <div class="max-h-[700px] overflow-y-auto">
            <div
              v-if="draftHistory.length === 0"
              class="px-4 py-12 text-center text-xs text-gray-600"
            >
              No selections yet.
            </div>

            <div
              v-for="(pick, index) in draftHistory"
              :key="pick.id ?? `${pick.round}-${pick.pick_number}-${index}`"
              class="border-b border-white/5 px-4 py-3 transition hover:bg-white/[0.03]"
            >
              <div class="flex items-center gap-3">
                <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/5 text-xs font-black"
                >
                  {{ pick.pick_number ?? pick.pick }}
                </div>

                <div class="min-w-0 flex-1">
                  <div
                    class="truncate text-[9px] font-black uppercase tracking-wider text-gray-600"
                  >
                    R{{ pick.round }}
                    ·
                    {{
                      pick.team?.name ??
                      pick.team_name ??
                      "Unknown Team"
                    }}
                  </div>

                  <div class="mt-1 truncate text-sm font-black">
                    {{
                      pick.player?.name ??
                      pick.player_name ??
                      "-"
                    }}
                  </div>

                  <div
                    class="mt-1 flex gap-2 text-[9px] font-bold uppercase text-gray-600"
                  >
                    <span>
                      {{
                        pick.player?.position ??
                        pick.position ??
                        "-"
                      }}
                    </span>

                    <span>
                      OVR
                      {{
                        pick.player?.overall_rating ??
                        pick.overall_rating ??
                        0
                      }}
                    </span>
                  </div>
                </div>

                <div
                  v-if="pick.decision"
                  class="text-right"
                >
                  <div
                    class="text-[8px] font-black uppercase text-blue-400"
                  >
                    {{
                      pick.decision.maker_name ??
                      "Coach"
                    }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>

      <!-- ========================================================= -->
      <!-- AVAILABLE ROOKIES -->
      <!-- ========================================================= -->

      <section class="mt-5">
        <div
          class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b0b0b]"
        >
          <div
            class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-4 py-4"
          >
            <div>
              <h3 class="text-sm font-black uppercase">
                Available Prospects
              </h3>

              <p
                class="mt-1 text-[9px] uppercase tracking-[0.2em] text-gray-600"
              >
                Rookie pool
              </p>
            </div>

            <div
              class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold"
            >
              {{ availablePlayers.total ?? 0 }}
              Available
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
              <thead>
                <tr
                  class="border-b border-white/10 bg-white/[0.02]"
                >
                  <th
                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-500"
                  >
                    Prospect
                  </th>

                  <th
                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-500"
                  >
                    Position
                  </th>

                  <th
                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-500"
                  >
                    Age
                  </th>

                  <th
                    class="px-4 py-3 text-left text-[9px] font-black uppercase tracking-wider text-gray-500"
                  >
                    Overall
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="player in availablePlayers.rookies"
                  :key="player.id"
                  class="border-b border-white/5 transition hover:bg-white/[0.03]"
                >
                  <td class="px-4 py-3">
                    <div class="font-bold">
                      {{ player.name }}
                    </div>
                  </td>

                  <td class="px-4 py-3 text-gray-500">
                    {{ player.position ?? "-" }}
                  </td>

                  <td class="px-4 py-3 text-gray-500">
                    {{ player.age ?? "-" }}
                  </td>

                  <td class="px-4 py-3">
                    <span class="font-black text-blue-400">
                      {{ player.overall_rating }}
                    </span>
                  </td>
                </tr>

                <tr
                  v-if="
                    !availablePlayers.rookies ||
                    availablePlayers.rookies.length === 0
                  "
                >
                  <td
                    colspan="4"
                    class="px-4 py-10 text-center text-xs text-gray-600"
                  >
                    No available rookies.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="availablePlayers.total"
            class="border-t border-white/10 p-4"
          >
            <Paginator
              :page_number="search.page_num"
              :total_rows="availablePlayers.total ?? 0"
              :itemsperpage="search.itemsperpage"
              @page_num="handlePagination"
            />
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Paginator from "@/Components/Paginator.vue";
import TopStatistics from "@/Pages/Analytics/Module/TopStatistics.vue";

const props = defineProps({
  season_id: {
    type: [Number, String],
    required: true,
  },
});

const emits = defineEmits(["newSeason"]);

/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

const availablePlayers = ref({
  rookies: [],
  total: 0,
});

const draftOrder = ref([]);
const draftResults = ref([]);

const currentDecision = ref(null);

const isHide = ref(true);
const key = ref(0);

const draftRunning = ref(false);
const isProcessingPick = ref(false);

const nextPickToDraft = ref(null);

const search = ref({
  page_num: 1,
  total_pages: 0,
  total: 0,
  search: "",
  itemsperpage: 10,
});

let nextPickTimer = null;

/*
|--------------------------------------------------------------------------
| COMPUTED
|--------------------------------------------------------------------------
*/

const currentPick = computed(() => {
  if (nextPickToDraft.value) {
    return nextPickToDraft.value;
  }

  if (currentDecision.value?.next_pick) {
    return currentDecision.value.next_pick;
  }

  return null;
});

const draftHistory = computed(() => {
  return draftResults.value
    .slice()
    .sort((a, b) => {
      if (Number(a.round) !== Number(b.round)) {
        return Number(a.round) - Number(b.round);
      }

      return Number(
        a.pick_number ?? a.pick ?? 0
      ) - Number(
        b.pick_number ?? b.pick ?? 0
      );
    });
});

const draftFinished = computed(() => {
  return (
    draftOrder.value.length > 0 &&
    draftHistory.value.length >= draftOrder.value.length &&
    !currentDecision.value?.next_pick
  );
});

const draftProgress = computed(() => {
  const total = draftOrder.value.length;

  const completed = draftHistory.value.length;

  const current = Math.min(
    completed + (currentDecision.value ? 0 : 1),
    total
  );

  return {
    current,
    total,
    percent:
      total > 0
        ? Math.min((completed / total) * 100, 100)
        : 0,
  };
});

/*
|--------------------------------------------------------------------------
| LIFECYCLE
|--------------------------------------------------------------------------
*/

onMounted(async () => {
  await fetchDraftOrder();
  await fetchDraftResults();
  await fetchAvailablePlayers();

  syncNextPick();
});

onBeforeUnmount(() => {
  clearNextPickTimer();
});

/*
|--------------------------------------------------------------------------
| DRAFT DATA
|--------------------------------------------------------------------------
*/

const fetchDraftOrder = async () => {
  try {
    const response = await axios.get(
      route("draft.orders")
    );

    draftOrder.value =
      response.data.draft_order ?? [];

    isHide.value = false;
  } catch (error) {
    console.error(
      "Error fetching draft order:",
      error
    );

    draftOrder.value = [];
  }
};

const fetchDraftResults = async () => {
  try {
    const response = await axios.get(
      route("draft.results")
    );

    draftResults.value =
      response.data.draft_results ?? [];
  } catch (error) {
    console.error(
      "Error fetching draft results:",
      error
    );

    draftResults.value = [];
  }
};

const fetchAvailablePlayers = async () => {
  try {
    const response = await axios.post(
      route("draft.list"),
      search.value
    );

    availablePlayers.value =
      response.data ?? {
        rookies: [],
        total: 0,
      };
  } catch (error) {
    console.error(
      "Error fetching available players:",
      error
    );
  }
};

/*
|--------------------------------------------------------------------------
| SYNC NEXT PICK
|--------------------------------------------------------------------------
|
| The backend is authoritative.
|
| We only use the draft results to determine the first
| unprocessed pick when the page initially loads.
|
*/

const syncNextPick = () => {
  if (!draftOrder.value.length) {
    nextPickToDraft.value = null;
    return;
  }

  const completedKeys = new Set(
    draftResults.value.map((pick) => {
      return `${Number(pick.round)}-${Number(
        pick.pick_number ?? pick.pick
      )}`;
    })
  );

  const next = draftOrder.value.find((pick) => {
    return !completedKeys.has(
      `${Number(pick.round)}-${Number(
        pick.pick_number ?? pick.pick
      )}`
    );
  });

  nextPickToDraft.value = next
    ? normalizePick(next)
    : null;
};

/*
|--------------------------------------------------------------------------
| NORMALIZE PICK
|--------------------------------------------------------------------------
*/

const normalizePick = (pick) => {
  if (!pick) {
    return null;
  }

  return {
    ...pick,

    round: Number(
      pick.round ?? 1
    ),

    pick_number: Number(
      pick.pick_number ??
      pick.pick ??
      0
    ),

    pick: Number(
      pick.pick ??
      pick.pick_number ??
      0
    ),

    team_id: Number(
      pick.team_id ?? 0
    ),

    team_name:
      pick.team_name ??
      pick.team?.name ??
      "Unknown Team",
  };
};

/*
|--------------------------------------------------------------------------
| START DRAFT
|--------------------------------------------------------------------------
*/

const startDraft = async () => {
  if (draftRunning.value || draftFinished.value) {
    return;
  }

  clearNextPickTimer();

  currentDecision.value = null;

  await fetchDraftResults();

  syncNextPick();

  if (!nextPickToDraft.value) {
    await fetchDraftOrder();
    syncNextPick();
  }

  if (!nextPickToDraft.value) {
    await Swal.fire({
      title: "Draft Complete",
      text: "There are no remaining draft picks.",
      icon: "info",
      confirmButtonText: "OK",
    });

    return;
  }

  /*
   * Start the live NBA-style sequence.
   */
  await runNextPick();
};

/*
|--------------------------------------------------------------------------
| RUN ONE PICK
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| This calls the backend ONCE.
|
| The backend processes exactly ONE pick.
|
| The frontend waits for the response and then decides
| whether to continue to next_pick.
|
*/

const runNextPick = async () => {
  if (
    draftRunning.value &&
    isProcessingPick.value
  ) {
    return;
  }

  const pick = nextPickToDraft.value;

  if (!pick) {
    return;
  }

  draftRunning.value = true;
  isProcessingPick.value = true;

  clearNextPickTimer();

  try {
    const response = await axios.post(
      route("draft.decision", {
        seasonId: props.season_id,
        round: pick.round,
        pickNumber: pick.pick_number,
      })
    );

    const result =
      response.data?.data ?? null;

    if (!result) {
      throw new Error(
        "Draft response did not contain a result."
      );
    }

    /*
     * Show the completed selection.
     */
    currentDecision.value = result;

    /*
     * Add the result to the board immediately.
     */
    addResultToHistory(result);

    /*
     * Refresh available players.
     */
    await fetchAvailablePlayers();

    /*
     * Refresh top statistics.
     */
    key.value = Math.random();

    emits("newSeason",Math.random());

    /*
     * Pick is finished processing.
     */
    isProcessingPick.value = false;

    /*
     * Draft is complete.
     */
    if (
      result.is_complete ||
      !result.next_pick
    ) {
      nextPickToDraft.value = null;

      draftRunning.value = false;

      await fetchDraftResults();

      return;
    }

    /*
     * IMPORTANT:
     *
     * The backend tells us exactly who is next.
     *
     * This also handles:
     *
     * Round 1 Pick 30
     *       ↓
     * Round 2 Pick 1
     */
    nextPickToDraft.value =
      normalizePick(
        result.next_pick
      );

    console.log(nextPickToDraft);

    /*
     * Refresh board data in background.
     */
    fetchDraftResults();

    /*
     * Give the user time to read the selection.
     */
    scheduleNextPick(3500);

  } catch (error) {
    console.error(
      "Draft pick failed:",
      error
    );

    isProcessingPick.value = false;
    draftRunning.value = false;

    await Swal.fire({
      title: "Draft Error",
      text:
        error?.response?.data?.error_message ??
        error?.response?.data?.message ??
        error?.message ??
        "Unable to complete the draft pick.",
      icon: "error",
      confirmButtonText: "OK",
    });
  }
};

/*
|--------------------------------------------------------------------------
| ADD RESULT TO HISTORY
|--------------------------------------------------------------------------
*/

const addResultToHistory = (result) => {
  const resultRound =
    Number(result.round);

  const resultPick =
    Number(result.pick_number);

  const existingIndex =
    draftResults.value.findIndex(
      (pick) =>
        Number(pick.round) === resultRound &&
        Number(
          pick.pick_number ??
          pick.pick
        ) === resultPick
    );

  /*
   * Convert the new backend response into the same
   * structure used by the draft board.
   */
  const boardResult = {
    ...result,

    pick:
      result.pick_number,

    pick_number:
      result.pick_number,

    team_name:
      result.team?.name ??
      result.team_name ??
      "Unknown Team",

    player_name:
      result.player?.name ??
      result.player_name ??
      "-",

    position:
      result.player?.position ??
      result.position ??
      "-",

    overall_rating:
      result.player?.overall_rating ??
      result.overall_rating ??
      0,

    age:
      result.player?.age ??
      result.age ??
      null,

    archetype:
      result.player?.archetype ??
      result.archetype ??
      null,
  };

  if (existingIndex >= 0) {
    draftResults.value[
      existingIndex
    ] = boardResult;
  } else {
    draftResults.value.push(
      boardResult
    );
  }
};

/*
|--------------------------------------------------------------------------
| SCHEDULE NEXT PICK
|--------------------------------------------------------------------------
*/

const scheduleNextPick = (delay = 3500) => {
  clearNextPickTimer();

  nextPickTimer = setTimeout(() => {
    /*
     * Hide the previous player's information.
     *
     * This causes the UI to return to
     * "ON THE CLOCK".
     */
    currentDecision.value = null;

    /*
     * Wait a small amount so the transition
     * feels intentional.
     */
    setTimeout(() => {
      if (
        nextPickToDraft.value &&
        draftRunning.value
      ) {
        runNextPick();
      }
    }, 500);

  }, delay);
};

/*
|--------------------------------------------------------------------------
| CLEAR TIMER
|--------------------------------------------------------------------------
*/

const clearNextPickTimer = () => {
  if (nextPickTimer) {
    clearTimeout(nextPickTimer);
    nextPickTimer = null;
  }
};

/*
|--------------------------------------------------------------------------
| MANUAL NEXT PICK
|--------------------------------------------------------------------------
|
| Useful if you want the user to control the pace.
|
*/

const prepareNextPick = () => {
  clearNextPickTimer();

  if (!nextPickToDraft.value) {
    currentDecision.value = null;
    draftRunning.value = false;
    return;
  }

  currentDecision.value = null;

  runNextPick();
};

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

const handlePagination = (page_num) => {
  search.value.page_num = page_num;

  fetchAvailablePlayers();
};

/*
|--------------------------------------------------------------------------
| FORMAT
|--------------------------------------------------------------------------
*/

const formatArchetype = (archetype) => {
  if (!archetype) {
    return "-";
  }

  return String(archetype)
    .replaceAll("_", " ")
    .replace(/\b\w/g, (letter) =>
      letter.toUpperCase()
    );
};

/*
|--------------------------------------------------------------------------
| PLAYER GENERATION
|--------------------------------------------------------------------------
*/

const fetchRandomFullName3 = async () => {
  try {
    const response = await axios.get(
      route("generate.new.player")
    );

    return {
      name: response.data.name,
      country: response.data.country,
      address: response.data.address,
    };
  } catch (error) {
    console.error(
      "Error fetching random player name:",
      error
    );

    return null;
  }
};

const addMultiplePlayers = async (count) => {
  if (draftRunning.value) {
    return;
  }

  try {
    Swal.fire({
      title: "Generating Rookies",
      text:
        `Adding ${count} players to the draft pool...`,
      icon: "info",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    let successCount = 0;

    /*
     * Keep the existing generation behavior.
     */
    for (let i = 0; i < count; i++) {
      const randomFullName =
        await fetchRandomFullName3();

      if (randomFullName) {
        await addPlayer(
          randomFullName,
          false
        );

        successCount++;
      }
    }

    Swal.close();

    await fetchAvailablePlayers();

    key.value = Math.random();

    await Swal.fire({
      icon: "success",
      title: "Draft Pool Updated",
      text:
        `Successfully added ${successCount} players.`,
    });

  } catch (error) {
    console.error(
      "Error adding multiple players:",
      error
    );

    Swal.close();

    await Swal.fire({
      icon: "error",
      title: "Error",
      text:
        error?.message ??
        "Unable to add rookie players.",
    });
  }
};

const addPlayer = async (
  info,
  showAlert = true
) => {
  try {
    const response = await axios.post(
      route("players.add.free.agent"),
      {
        name: info.name,
        address: info.address,
        country: info.country,
      }
    );

    if (showAlert) {
      await Swal.fire({
        icon: "success",
        title:
          `${response.data.player.name} added`,
        text:
          response.data.message ??
          "Player added to Draft Pool.",
      });
    }

    key.value = Math.random();

    return response.data;

  } catch (error) {
    console.error(
      "Error adding player:",
      error?.response?.data?.message
    );

    throw new Error(
      error?.response?.data?.message ??
      "Unable to add player."
    );
  }
};
</script>
<style scoped>
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #050505;
}

::-webkit-scrollbar-thumb {
  background: #333;
  border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>