<template>
    <Head title="Leaders" />

    <AuthenticatedLayout>
        <template #header>
            Stats Leaders
        </template>

        <div class="min-h-screen bg-[#05070a] p-3 sm:p-4 lg:p-6">
            <div class="mx-auto w-full max-w-[1800px]">
                <div
                    class="overflow-hidden rounded-2xl border border-slate-800 bg-[#080b10] shadow-xl"
                >
                    <!-- Top Accent -->
                    <div
                        class="h-px w-full bg-gradient-to-r from-slate-800 via-slate-500 to-slate-800"
                    ></div>

                    <!-- Page Header -->
                    <div
                        class="border-b border-slate-800 px-4 py-4 sm:px-6"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-700 bg-[#0d1117]"
                                >
                                    <i
                                        class="fas fa-chart-line text-sm text-slate-300"
                                    ></i>
                                </div>

                                <div>
                                    <h1
                                        class="text-sm font-bold uppercase tracking-wider text-white sm:text-base"
                                    >
                                        Stats Leaders
                                    </h1>

                                    <p class="mt-1 text-xs text-slate-500">
                                        League statistical leaders and
                                        record performances
                                    </p>
                                </div>
                            </div>

                            <!-- Reload -->
                            <button
                                type="button"
                                :disabled="loading"
                                class="inline-flex items-center justify-center gap-2 self-start rounded-lg border border-slate-700 bg-[#0d1117] px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest text-slate-300 transition hover:border-slate-600 hover:bg-[#11161d] hover:text-white disabled:cursor-not-allowed disabled:opacity-50 lg:self-auto"
                                @click="reloadData"
                            >
                                <i
                                    :class="[
                                        'fas',
                                        loading
                                            ? 'fa-spinner fa-spin'
                                            : 'fa-rotate'
                                    ]"
                                ></i>

                                {{ loading ? "Refreshing..." : "Reload Data" }}
                            </button>
                        </div>
                    </div>

                    <!-- Overview -->
                    <div
                        class="grid grid-cols-2 border-b border-slate-800 bg-[#0a0d12] sm:grid-cols-4"
                    >
                        <div
                            class="border-b border-slate-800 px-4 py-4 sm:border-b-0 sm:border-r"
                        >
                            <div
                                class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                            >
                                Categories
                            </div>

                            <div
                                class="mt-1 text-xl font-black text-white"
                            >
                                15
                            </div>
                        </div>

                        <div
                            class="border-b border-slate-800 px-4 py-4 sm:border-b-0 sm:border-r"
                        >
                            <div
                                class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                            >
                                Per Game
                            </div>

                            <div
                                class="mt-1 text-xl font-black text-white"
                            >
                                5
                            </div>
                        </div>

                        <div
                            class="border-r border-slate-800 px-4 py-4"
                        >
                            <div
                                class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                            >
                                Career Totals
                            </div>

                            <div
                                class="mt-1 text-xl font-black text-white"
                            >
                                5
                            </div>
                        </div>

                        <div class="px-4 py-4">
                            <div
                                class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                            >
                                Single Game
                            </div>

                            <div
                                class="mt-1 text-xl font-black text-white"
                            >
                                5
                            </div>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- AVERAGE LEADERS -->
                    <!-- ================================================= -->
                    <section class="p-4 sm:p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-gauge-high text-[11px] text-slate-400"
                                ></i>
                            </div>

                            <div>
                                <h2
                                    class="text-xs font-bold uppercase tracking-widest text-white"
                                >
                                    Per Game Leaders
                                </h2>

                                <p
                                    class="mt-0.5 text-[10px] text-slate-600"
                                >
                                    Highest average production
                                </p>
                            </div>

                            <div
                                class="ml-auto hidden h-px flex-1 bg-slate-800 sm:block"
                            ></div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
                        >
                            <LeaderCard
                                title="Average Points"
                                subtitle="PPG"
                                icon="fa-basketball"
                                :players="average.topPoints"
                                stat-key="avg_points_per_game"
                            />

                            <LeaderCard
                                title="Average Assists"
                                subtitle="APG"
                                icon="fa-hands"
                                :players="average.topAssists"
                                stat-key="avg_assists_per_game"
                            />

                            <LeaderCard
                                title="Average Rebounds"
                                subtitle="RPG"
                                icon="fa-arrows-up-down"
                                :players="average.topRebounds"
                                stat-key="avg_rebounds_per_game"
                            />

                            <LeaderCard
                                title="Average Steals"
                                subtitle="SPG"
                                icon="fa-hand"
                                :players="average.topSteals"
                                stat-key="avg_steals_per_game"
                            />

                            <LeaderCard
                                title="Average Blocks"
                                subtitle="BPG"
                                icon="fa-shield-halved"
                                :players="average.topBlocks"
                                stat-key="avg_blocks_per_game"
                            />
                        </div>
                    </section>

                    <!-- Section Divider -->
                    <div class="mx-4 h-px bg-slate-800 sm:mx-6"></div>

                    <!-- ================================================= -->
                    <!-- TOTAL LEADERS -->
                    <!-- ================================================= -->
                    <section class="p-4 sm:p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-layer-group text-[11px] text-slate-400"
                                ></i>
                            </div>

                            <div>
                                <h2
                                    class="text-xs font-bold uppercase tracking-widest text-white"
                                >
                                    Career Totals
                                </h2>

                                <p
                                    class="mt-0.5 text-[10px] text-slate-600"
                                >
                                    All-time statistical accumulation
                                </p>
                            </div>

                            <div
                                class="ml-auto hidden h-px flex-1 bg-slate-800 sm:block"
                            ></div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
                        >
                            <LeaderCard
                                title="Total Points"
                                subtitle="PTS"
                                icon="fa-basketball"
                                :players="total.topTotalPoints"
                                stat-key="total_points"
                            />

                            <LeaderCard
                                title="Total Assists"
                                subtitle="AST"
                                icon="fa-hands"
                                :players="total.topTotalAssists"
                                stat-key="total_assists"
                            />

                            <LeaderCard
                                title="Total Rebounds"
                                subtitle="REB"
                                icon="fa-arrows-up-down"
                                :players="total.topTotalRebounds"
                                stat-key="total_rebounds"
                            />

                            <LeaderCard
                                title="Total Steals"
                                subtitle="STL"
                                icon="fa-hand"
                                :players="total.topTotalSteals"
                                stat-key="total_steals"
                            />

                            <LeaderCard
                                title="Total Blocks"
                                subtitle="BLK"
                                icon="fa-shield-halved"
                                :players="total.topTotalBlocks"
                                stat-key="total_blocks"
                            />
                        </div>
                    </section>

                    <!-- Section Divider -->
                    <div class="mx-4 h-px bg-slate-800 sm:mx-6"></div>

                    <!-- ================================================= -->
                    <!-- SINGLE GAME LEADERS -->
                    <!-- ================================================= -->
                    <section class="p-4 sm:p-6">
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-800 bg-[#0d1117]"
                            >
                                <i
                                    class="fas fa-bolt text-[11px] text-slate-400"
                                ></i>
                            </div>

                            <div>
                                <h2
                                    class="text-xs font-bold uppercase tracking-widest text-white"
                                >
                                    Single-Game Records
                                </h2>

                                <p
                                    class="mt-0.5 text-[10px] text-slate-600"
                                >
                                    Highest individual game performances
                                </p>
                            </div>

                            <div
                                class="ml-auto hidden h-px flex-1 bg-slate-800 sm:block"
                            ></div>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
                        >
                            <SingleGameCard
                                title="Single-Game Points"
                                subtitle="PTS"
                                icon="fa-basketball"
                                :players="single.topSinglePoints"
                                stat-key="points"
                            />

                            <SingleGameCard
                                title="Single-Game Assists"
                                subtitle="AST"
                                icon="fa-hands"
                                :players="single.topSingleAssists"
                                stat-key="assists"
                            />

                            <SingleGameCard
                                title="Single-Game Rebounds"
                                subtitle="REB"
                                icon="fa-arrows-up-down"
                                :players="single.topSingleRebounds"
                                stat-key="rebounds"
                            />

                            <SingleGameCard
                                title="Single-Game Steals"
                                subtitle="STL"
                                icon="fa-hand"
                                :players="single.topSingleSteals"
                                stat-key="steals"
                            />

                            <SingleGameCard
                                title="Single-Game Blocks"
                                subtitle="BLK"
                                icon="fa-shield-halved"
                                :players="single.topSingleBlocks"
                                stat-key="blocks"
                            />
                        </div>
                    </section>

                    <!-- Footer -->
                    <div
                        class="border-t border-slate-800 bg-[#0a0d12] px-4 py-3 sm:px-6"
                    >
                        <div
                            class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <span
                                class="text-[9px] font-bold uppercase tracking-widest text-slate-600"
                            >
                                League Statistics Archive
                            </span>

                            <span
                                class="text-[9px] font-semibold uppercase tracking-widest text-slate-700"
                            >
                                Cached locally for faster loading
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, h, onMounted, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";
import Swal from "sweetalert2";

const average = ref({
    topPoints: [],
    topAssists: [],
    topRebounds: [],
    topSteals: [],
    topBlocks: [],
});

const total = ref({
    topTotalPoints: [],
    topTotalAssists: [],
    topTotalRebounds: [],
    topTotalSteals: [],
    topTotalBlocks: [],
});

const single = ref({
    topSinglePoints: [],
    topSingleAssists: [],
    topSingleRebounds: [],
    topSingleSteals: [],
    topSingleBlocks: [],
});

const loading = ref(false);

const CACHE_KEY_AVERAGE = "average_stat_leaders";
const CACHE_KEY_TOTAL = "total_stat_leaders";
const CACHE_KEY_SINGLE = "single_stat_leaders";

/*
|--------------------------------------------------------------------------
| Reusable Leader Card
|--------------------------------------------------------------------------
*/

const LeaderCard = (props) => {
    const players = Array.isArray(props.players)
        ? props.players
        : [];

    return h(
        "div",
        {
            class: "overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]",
        },
        [
            h(
                "div",
                {
                    class:
                        "flex items-center justify-between border-b border-slate-800 px-3 py-3",
                },
                [
                    h("div", { class: "flex items-center gap-2" }, [
                        h(
                            "div",
                            {
                                class:
                                    "flex h-7 w-7 items-center justify-center rounded-md bg-[#080b10] text-[10px] text-slate-500",
                            },
                            [h("i", { class: `fas ${props.icon}` })]
                        ),

                        h("div", [
                            h(
                                "div",
                                {
                                    class:
                                        "text-[10px] font-bold uppercase tracking-wider text-white",
                                },
                                props.title
                            ),
                            h(
                                "div",
                                {
                                    class:
                                        "mt-0.5 text-[8px] font-bold uppercase tracking-widest text-slate-600",
                                },
                                props.subtitle
                            ),
                        ]),
                    ]),
                ]
            ),

            h(
                "div",
                {
                    class: "divide-y divide-slate-800/70",
                },
                players.length
                    ? players.map((player, index) =>
                          h(
                              "div",
                              {
                                  class: [
                                      "flex items-center justify-between gap-3 px-3 py-2.5 transition-colors",
                                      index < 3
                                          ? "bg-white/[0.02]"
                                          : "hover:bg-white/[0.015]",
                                  ],
                              },
                              [
                                  h(
                                      "div",
                                      {
                                          class:
                                              "flex min-w-0 items-center gap-2.5",
                                      },
                                      [
                                          h(
                                              "span",
                                              {
                                                  class: [
                                                      "flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[9px] font-black tabular-nums",
                                                      getRankClass(index),
                                                  ],
                                              },
                                              String(index + 1)
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
                                                              "truncate text-[10px] font-bold uppercase tracking-wide text-slate-200",
                                                      },
                                                      `${playerFormatter(player.player_name)}`
                                                  ),

                                                  h(
                                                      "div",
                                                      {
                                                          class:
                                                              "mt-0.5 truncate text-[8px] text-slate-600",
                                                      },
                                                      `${player.team_name ?? "Free Agent"} • Season ${player.season_id ?? "—"}`
                                                  ),
                                              ]
                                          ),
                                      ]
                                  ),

                                  h(
                                      "div",
                                      {
                                          class:
                                              "shrink-0 rounded-lg border border-slate-800 bg-[#080b10] px-2.5 py-2 text-right",
                                      },
                                      [
                                          h(
                                              "div",
                                              {
                                                  class:
                                                      "text-sm font-black tabular-nums text-white",
                                              },
                                              formatStat(
                                                  player[props.statKey]
                                              )
                                          ),
                                          h(
                                              "div",
                                              {
                                                  class:
                                                      "mt-0.5 text-[7px] font-bold uppercase tracking-widest text-slate-600",
                                              },
                                              props.subtitle
                                          ),
                                      ]
                                  ),
                              ]
                          )
                      )
                    : [
                          h(
                              "div",
                              {
                                  class:
                                      "px-3 py-8 text-center text-[9px] font-bold uppercase tracking-widest text-slate-700",
                              },
                              "No Data"
                          ),
                      ]
            ),
        ]
    );
};

/*
|--------------------------------------------------------------------------
| Single Game Card
|--------------------------------------------------------------------------
*/

const SingleGameCard = (props) => {
    const players = Array.isArray(props.players)
        ? props.players
        : [];

    return h(
        "div",
        {
            class: "overflow-hidden rounded-xl border border-slate-800 bg-[#0d1117]",
        },
        [
            h(
                "div",
                {
                    class:
                        "flex items-center justify-between border-b border-slate-800 px-3 py-3",
                },
                [
                    h("div", { class: "flex items-center gap-2" }, [
                        h(
                            "div",
                            {
                                class:
                                    "flex h-7 w-7 items-center justify-center rounded-md bg-[#080b10] text-[10px] text-slate-500",
                            },
                            [h("i", { class: `fas ${props.icon}` })]
                        ),

                        h("div", [
                            h(
                                "div",
                                {
                                    class:
                                        "text-[10px] font-bold uppercase tracking-wider text-white",
                                },
                                props.title
                            ),

                            h(
                                "div",
                                {
                                    class:
                                        "mt-0.5 text-[8px] font-bold uppercase tracking-widest text-slate-600",
                                },
                                props.subtitle
                            ),
                        ]),
                    ]),

                    h(
                        "span",
                        {
                            class:
                                "rounded-md border border-slate-800 bg-[#080b10] px-2 py-1 text-[7px] font-bold uppercase tracking-widest text-slate-600",
                        },
                        "Record"
                    ),
                ]
            ),

            h(
                "div",
                {
                    class: "divide-y divide-slate-800/70",
                },
                players.length
                    ? players.map((player, index) =>
                          h(
                              "div",
                              {
                                  class: [
                                      "px-3 py-2.5 transition-colors hover:bg-white/[0.015]",
                                      index < 3
                                          ? "bg-white/[0.02]"
                                          : "",
                                  ],
                              },
                              [
                                  h(
                                      "div",
                                      {
                                          class:
                                              "flex items-center justify-between gap-3",
                                      },
                                      [
                                          h(
                                              "div",
                                              {
                                                  class:
                                                      "flex min-w-0 items-center gap-2.5",
                                              },
                                              [
                                                  h(
                                                      "span",
                                                      {
                                                          class: [
                                                              "flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-[9px] font-black tabular-nums",
                                                              getRankClass(index),
                                                          ],
                                                      },
                                                      String(index + 1)
                                                  ),

                                                  h("div", { class: "min-w-0" }, [
                                                      h(
                                                          "div",
                                                          {
                                                              class:
                                                                  "truncate text-[10px] font-bold uppercase tracking-wide text-slate-200",
                                                          },
                                                          `${playerFormatter(player.player_name)}`
                                                      ),

                                                      h(
                                                          "div",
                                                          {
                                                              class:
                                                                  "mt-0.5 truncate text-[8px] text-slate-600",
                                                          },
                                                          `${player.player_team ?? "—"} vs ${player.opponent_team ?? "—"}`
                                                      ),
                                                  ]),
                                              ]
                                          ),

                                          h(
                                              "div",
                                              {
                                                  class:
                                                      "shrink-0 text-right",
                                              },
                                              [
                                                  h(
                                                      "div",
                                                      {
                                                          class:
                                                              "text-lg font-black tabular-nums text-white",
                                                      },
                                                      formatStat(
                                                          player[props.statKey]
                                                      )
                                                  ),

                                                  h(
                                                      "div",
                                                      {
                                                          class:
                                                              "text-[7px] font-bold uppercase tracking-widest text-slate-600",
                                                      },
                                                      props.subtitle
                                                  ),
                                              ]
                                          ),
                                      ]
                                  ),

                                  h(
                                      "div",
                                      {
                                          class:
                                              "mt-2 flex items-center justify-between gap-2",
                                      },
                                      [
                                          h(
                                              "span",
                                              {
                                                  class:
                                                      "truncate text-[8px] text-slate-700",
                                              },
                                              player.season_name ??
                                                  "Season —"
                                          ),

                                          h(
                                              "span",
                                              {
                                                  class:
                                                      "shrink-0 text-[8px] font-bold uppercase tracking-wider text-slate-700",
                                              },
                                              player.draft_id
                                                  ? `Draft ${player.draft_id}`
                                                  : ""
                                          ),
                                      ]
                                  ),
                              ]
                          )
                      )
                    : [
                          h(
                              "div",
                              {
                                  class:
                                      "px-3 py-8 text-center text-[9px] font-bold uppercase tracking-widest text-slate-700",
                              },
                              "No Data"
                          ),
                      ]
            ),
        ]
    );
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const playerFormatter = (name) => {
    return name ?? "Unknown Player";
};

const formatStat = (value) => {
    if (value === null || value === undefined) {
        return "0";
    }

    const number = Number(value);

    if (Number.isNaN(number)) {
        return String(value);
    }

    return Number.isInteger(number)
        ? number.toLocaleString()
        : number.toFixed(1);
};

const getRankClass = (index) => {
    if (index === 0) {
        return "border border-slate-600 bg-slate-700 text-white";
    }

    if (index === 1) {
        return "border border-slate-700 bg-slate-800 text-slate-300";
    }

    if (index === 2) {
        return "border border-slate-800 bg-slate-900 text-slate-400";
    }

    return "border border-slate-800 bg-[#080b10] text-slate-600";
};

/*
|--------------------------------------------------------------------------
| Cache / API
|--------------------------------------------------------------------------
*/

const fetchStatLeaders = async (
    endpoint,
    cacheKey,
    refVariable
) => {
    const cachedData = localStorage.getItem(cacheKey);

    if (cachedData) {
        try {
            refVariable.value = JSON.parse(cachedData);

            console.log("Loaded from cache:", cacheKey);

            return;
        } catch (error) {
            console.warn(
                `Invalid cache for ${cacheKey}. Fetching fresh data.`,
                error
            );

            localStorage.removeItem(cacheKey);
        }
    }

    try {
        const response = await axios.get(route(endpoint));

        refVariable.value = response.data;

        localStorage.setItem(
            cacheKey,
            JSON.stringify(response.data)
        );

        console.log(
            "Fetched from API and cached:",
            cacheKey
        );
    } catch (error) {
        console.error(
            `Error fetching ${cacheKey}:`,
            error
        );
    }
};

const fetchAllStatLeaders = async () => {
    await Promise.all([
        fetchStatLeaders(
            "average.stats.leaders",
            CACHE_KEY_AVERAGE,
            average
        ),

        fetchStatLeaders(
            "total.stats.leaders",
            CACHE_KEY_TOTAL,
            total
        ),

        fetchStatLeaders(
            "single.stats.leaders",
            CACHE_KEY_SINGLE,
            single
        ),
    ]);
};

const reloadData = async () => {
    try {
        loading.value = true;

        localStorage.removeItem(CACHE_KEY_AVERAGE);
        localStorage.removeItem(CACHE_KEY_TOTAL);
        localStorage.removeItem(CACHE_KEY_SINGLE);

        await fetchAllStatLeaders();

        await Swal.fire({
            icon: "success",
            title: "Statistics Refreshed",
            text: "The cached leader data has been cleared and refreshed.",
            background: "#0d1117",
            color: "#e2e8f0",
            confirmButtonColor: "#334155",
        });
    } catch (error) {
        console.error("Error refreshing statistics:", error);

        await Swal.fire({
            icon: "error",
            title: "Refresh Failed",
            text: "Unable to refresh the statistical leader data.",
            background: "#0d1117",
            color: "#e2e8f0",
            confirmButtonColor: "#334155",
        });
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAllStatLeaders();
});
</script>