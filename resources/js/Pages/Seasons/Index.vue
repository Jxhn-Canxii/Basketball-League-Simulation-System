<template>
    <div class="min-w-0 w-full">
        <Head title="Seasons" />

        <AuthenticatedLayout>
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="fa fa-calendar-alt text-rose-400"></i>
                    <span>Seasons</span>
                </div>
            </template>

            <div class="w-full min-w-0 space-y-4">
                <!-- =====================================================
                     PAGE HEADER / CONTROLS
                ====================================================== -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-xl"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-gray-800 bg-gray-900/80 p-4 xl:flex-row xl:items-center xl:justify-between"
                    >
                        <!-- Title -->
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-500/20 bg-rose-500/10 text-rose-400"
                            >
                                <i class="fa fa-calendar-alt"></i>
                            </div>

                            <div>
                                <h1
                                    class="text-base font-semibold text-gray-100"
                                >
                                    Season History
                                </h1>

                                <p class="text-xs text-gray-500">
                                    League seasons, champions and historical
                                    results
                                </p>
                            </div>
                        </div>

                        <!-- Search + Current Season Actions -->
                        <div
                            class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center xl:max-w-5xl"
                        >
                            <!-- Search -->
                            <div class="relative min-w-0 flex-1 sm:min-w-[240px]">
                                <i
                                    class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-600"
                                ></i>

                                <input
                                    id="LeagueName"
                                    v-model="search_seasons.search"
                                    type="text"
                                    placeholder="Search seasons..."
                                    class="w-full rounded-lg border border-gray-800 bg-gray-950 py-2.5 pl-9 pr-3 text-sm text-gray-200 outline-none transition placeholder:text-gray-600 focus:border-rose-500/50 focus:ring-1 focus:ring-rose-500/20"
                                    @input.prevent="fetchSeasons(1)"
                                />
                            </div>

                            <!-- Season Workflow -->
                            <div
                                v-if="hasCurrentSeasonActions"
                                class="flex flex-wrap items-center gap-2"
                            >
                                <!-- Awards -->
                                <button
                                    v-if="seasons.is_new_season == 1"
                                    type="button"
                                    :disabled="isPlayerAwardsModalOpen"
                                    class="season-action border-amber-500/20 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click.prevent="
                                        isPlayerAwardsModalOpen = true
                                    "
                                >
                                    <i class="fa fa-trophy"></i>
                                    <span>Awards</span>
                                </button>

                                <!-- Player Status -->
                                <button
                                    v-if="seasons.is_new_season == 2"
                                    type="button"
                                    class="season-action border-sky-500/20 bg-sky-500/10 text-sky-300 hover:bg-sky-500/20"
                                    @click.prevent="updatePlayerStatus"
                                >
                                    <i class="fa fa-users"></i>
                                    <span>Update Players</span>
                                </button>

                                <!-- Coach Signings -->
                                <button
                                    v-if="
                                        seasons.is_new_season == 3 ||
                                        seasons.is_new_season == 8
                                    "
                                    type="button"
                                    :disabled="isCoachSigningModalOpen"
                                    class="season-action border-rose-500/20 bg-rose-500/10 text-rose-300 hover:bg-rose-500/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click.prevent="
                                        isCoachSigningModalOpen = true
                                    "
                                >
                                    <i
                                        class="fa fa-chalkboard-teacher"
                                    ></i>
                                    <span>Coach Signings</span>
                                </button>

                                <!-- Rookie Draft -->
                                <button
                                    v-if="
                                        seasons.is_new_season == 4 ||
                                        seasons.is_new_season == 8
                                    "
                                    type="button"
                                    :disabled="isDraftModalOpen"
                                    class="season-action border-yellow-500/20 bg-yellow-500/10 text-yellow-300 hover:bg-yellow-500/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click.prevent="isDraftModalOpen = true"
                                >
                                    <i class="fa fa-user-plus"></i>
                                    <span>Rookie Draft</span>
                                </button>

                                <!-- Trade Season -->
                                <button
                                    v-if="seasons.is_new_season == 5"
                                    type="button"
                                    :disabled="isTradeModalOpen"
                                    class="season-action border-orange-500/20 bg-orange-500/10 text-orange-300 hover:bg-orange-500/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click.prevent="isTradeModalOpen = true"
                                >
                                    <i class="fa fa-sync"></i>
                                    <span>Trade Season</span>
                                </button>

                                <!-- Player Signings -->
                                <button
                                    v-if="
                                        seasons.is_new_season == 6 ||
                                        (
                                            seasons.is_new_season == 8 &&
                                            seasons.is_draft_finished
                                        )
                                    "
                                    type="button"
                                    :disabled="isPlayerSigningModalOpen"
                                    class="season-action border-red-500/20 bg-red-500/10 text-red-300 hover:bg-red-500/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click.prevent="
                                        isPlayerSigningModalOpen = true
                                    "
                                >
                                    <i class="fa fa-users"></i>
                                    <span>Player Signings</span>
                                </button>

                                <!-- Create New Season -->
                                <Add
                                    v-if="
                                        seasons.is_new_season == 7 ||
                                        seasons.is_new_season == 8
                                    "
                                    @transaction_id="handleCreateSeason"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Current Season Status -->
                    <div
                        v-if="seasons.current_season"
                        class="flex flex-wrap items-center gap-2 border-b border-gray-800 bg-gray-950 px-4 py-2.5"
                    >
                        <span class="text-[10px] font-semibold uppercase tracking-widest text-gray-600">
                            Current Season
                        </span>

                        <span
                            class="rounded-md border border-rose-500/20 bg-rose-500/10 px-2 py-1 text-xs font-semibold text-rose-300"
                        >
                            Season {{ seasons.current_season }}
                        </span>

                        <span
                            v-if="seasons.is_new_season"
                            class="rounded-md border border-gray-700 bg-gray-900 px-2 py-1 text-[10px] font-medium uppercase tracking-wide text-gray-400"
                        >
                            Phase {{ seasons.is_new_season }}
                        </span>

                        <span
                            v-if="seasons.total_count"
                            class="ml-auto text-xs text-gray-600"
                        >
                            {{ seasons.total_count }} seasons
                        </span>
                    </div>

                    <!-- =====================================================
                         TABLE
                    ====================================================== -->
                    <div class="w-full min-w-0 overflow-x-auto">
                        <table
                            class="w-full min-w-[1850px] border-collapse text-left"
                        >
                            <!-- GROUP HEADER -->
                            <thead>
                                <tr
                                    class="border-b border-gray-800 bg-gray-950 text-[9px] font-bold uppercase tracking-[0.16em] text-gray-600"
                                >
                                    <th
                                        colspan="1"
                                        class="border-r border-gray-800 px-3 py-2"
                                    >
                                        Season
                                    </th>

                                    <th
                                        colspan="3"
                                        class="border-r border-gray-800 px-3 py-2 text-center"
                                    >
                                        Finals
                                    </th>

                                    <th
                                        colspan="1"
                                        class="border-r border-gray-800 px-3 py-2 text-center"
                                    >
                                        Regular Season
                                    </th>

                                    <th
                                        colspan="5"
                                        class="border-r border-gray-800 px-3 py-2 text-center"
                                    >
                                        Conference Champions
                                    </th>

                                    <th
                                        colspan="1"
                                        class="px-3 py-2 text-center"
                                    >
                                        Actions
                                    </th>
                                </tr>

                                <!-- COLUMN HEADER -->
                                <tr
                                    class="border-b border-gray-800 bg-gray-900 text-[10px] font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    <th
                                        class="sticky left-0 z-20 border-r border-gray-800 bg-gray-900 px-3 py-3"
                                    >
                                        Season
                                    </th>

                                    <th class="px-3 py-3">
                                        Finals MVP
                                    </th>

                                    <th class="px-3 py-3">
                                        Champion
                                    </th>

                                    <th class="px-3 py-3">
                                        Runner Up
                                    </th>

                                    <th class="px-3 py-3">
                                        Champion
                                    </th>

                                    <th class="px-3 py-3">
                                        NCR
                                    </th>

                                    <th class="px-3 py-3">
                                        Luzon
                                    </th>

                                    <th class="px-3 py-3">
                                        Visayas
                                    </th>

                                    <th class="px-3 py-3">
                                        Mindanao
                                    </th>

                                    <th class="border-r border-gray-800 px-3 py-3">
                                        Worst
                                    </th>

                                    <th class="px-3 py-3 text-center">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                v-if="
                                    seasons.seasons?.length &&
                                    seasons.total_pages
                                "
                            >
                                <tr
                                    v-for="(season, index) in seasons.seasons"
                                    :key="season.id"
                                    class="group border-b border-gray-800/80 transition-colors duration-150 hover:bg-gray-800/40"
                                    :class="seasonRowClass(season, index)"
                                >
                                    <!-- Season -->
                                    <td
                                        class="sticky left-0 z-10 border-r border-gray-800 bg-gray-950 px-3 py-3 align-middle group-hover:bg-gray-900"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex h-7 min-w-7 items-center justify-center rounded-md border border-gray-800 bg-gray-900 px-1.5 text-[10px] font-bold text-gray-500"
                                            >
                                                {{ season.id }}
                                            </div>

                                            <span
                                                class="whitespace-nowrap text-xs font-semibold uppercase text-gray-200"
                                            >
                                                {{ season.name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Finals MVP -->
                                    <td class="px-3 py-3 align-middle">
                                        <div
                                            v-if="season.finals_mvp"
                                            class="flex items-center gap-2 whitespace-nowrap"
                                        >
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500/10 text-[10px] text-amber-400"
                                            >
                                                <i class="fa fa-trophy"></i>
                                            </span>

                                            <span
                                                class="text-xs font-medium text-gray-200"
                                            >
                                                {{ season.finals_mvp }}
                                            </span>
                                        </div>

                                        <span
                                            v-else
                                            class="text-xs italic text-gray-600"
                                        >
                                            TBD
                                        </span>
                                    </td>

                                    <!-- Finals Champion -->
                                    <td class="px-3 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-bold text-amber-300"
                                            >
                                                {{
                                                    season.finals_winner_name ??
                                                    "TBD"
                                                }}
                                            </span>

                                            <span
                                                v-if="
                                                    season.finals_winner_name
                                                "
                                                class="rounded-md border border-amber-500/20 bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-300"
                                            >
                                                {{
                                                    finalsWinnerScore(season)
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Finals Runner Up -->
                                    <td class="px-3 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs text-gray-300"
                                            >
                                                {{
                                                    season.finals_loser_name ??
                                                    "TBD"
                                                }}
                                            </span>

                                            <span
                                                v-if="season.finals_loser_name"
                                                class="rounded-md border border-gray-800 bg-gray-900 px-1.5 py-0.5 text-[10px] font-semibold text-gray-500"
                                            >
                                                {{
                                                    finalsLoserScore(season)
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Regular Champion -->
                                    <td class="px-3 py-3 align-middle">
                                        <span
                                            v-if="season.type == 1"
                                            class="text-xs italic text-gray-600"
                                        >
                                            n/a
                                        </span>

                                        <span
                                            v-else
                                            class="text-xs font-semibold"
                                            :class="
                                                season.finals_winner_id ==
                                                season.champion_id
                                                    ? 'text-amber-300'
                                                    : 'text-gray-300'
                                            "
                                        >
                                            {{
                                                season.champion_name ?? "TBD"
                                            }}
                                        </span>
                                    </td>

                                    <!-- NCR -->
                                    <td
                                        class="px-3 py-3 align-middle"
                                        :class="
                                            conferenceCellClass(
                                                season,
                                                season.west_champion_id,
                                                'red'
                                            )
                                        "
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="conference-dot bg-red-500"
                                            ></span>

                                            <span
                                                class="whitespace-nowrap text-xs"
                                                :class="
                                                    season.finals_winner_id ==
                                                    season.west_champion_id
                                                        ? 'font-bold text-red-300'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{
                                                    season.west_champion_name ??
                                                    "TBD"
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Luzon -->
                                    <td
                                        class="px-3 py-3 align-middle"
                                        :class="
                                            conferenceCellClass(
                                                season,
                                                season.east_champion_id,
                                                'blue'
                                            )
                                        "
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="conference-dot bg-blue-500"></span>

                                            <span
                                                class="whitespace-nowrap text-xs"
                                                :class="
                                                    season.finals_winner_id ==
                                                    season.east_champion_id
                                                        ? 'font-bold text-blue-300'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{
                                                    season.east_champion_name ??
                                                    "TBD"
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Visayas -->
                                    <td
                                        class="px-3 py-3 align-middle"
                                        :class="
                                            conferenceCellClass(
                                                season,
                                                season.north_champion_id,
                                                'green'
                                            )
                                        "
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="conference-dot bg-emerald-500"></span>

                                            <span
                                                class="whitespace-nowrap text-xs"
                                                :class="
                                                    season.finals_winner_id ==
                                                    season.north_champion_id
                                                        ? 'font-bold text-emerald-300'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{
                                                    season.north_champion_name ??
                                                    "TBD"
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Mindanao -->
                                    <td
                                        class="px-3 py-3 align-middle"
                                        :class="
                                            conferenceCellClass(
                                                season,
                                                season.south_champion_id,
                                                'yellow'
                                            )
                                        "
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="conference-dot bg-yellow-500"></span>

                                            <span
                                                class="whitespace-nowrap text-xs"
                                                :class="
                                                    season.finals_winner_id ==
                                                    season.south_champion_id
                                                        ? 'font-bold text-yellow-300'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{
                                                    season.south_champion_name ??
                                                    "TBD"
                                                }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Worst -->
                                    <td
                                        class="border-r border-gray-800 px-3 py-3 align-middle"
                                    >
                                        <span
                                            v-if="season.type == 1"
                                            class="text-xs italic text-gray-600"
                                        >
                                            n/a
                                        </span>

                                        <span
                                            v-else
                                            class="whitespace-nowrap text-xs text-gray-500"
                                        >
                                            {{
                                                season.weakest_name ?? "TBD"
                                            }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-3 py-3 align-middle">
                                        <div
                                            class="flex min-w-max items-center justify-center gap-1"
                                        >
                                            <!-- Season -->
                                            <a
                                                :href="
                                                    route('seasons.details', {
                                                        season_id: season.id,
                                                        playoff_type:
                                                            season.playoff_type,
                                                    })
                                                "
                                                class="row-action border-sky-500/20 bg-sky-500/10 text-sky-300 hover:bg-sky-500/20"
                                            >
                                                <i class="fa fa-list"></i>
                                                <span>Season</span>
                                            </a>

                                            <!-- Awards -->
                                            <button
                                                type="button"
                                                class="row-action border-amber-500/20 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20"
                                                @click.prevent="
                                                    isSeasonAwardsModalOpen =
                                                        season.id
                                                "
                                            >
                                                <i class="fa fa-medal"></i>
                                                <span>Awards</span>
                                            </button>

                                            <!-- Storyline -->
                                            <button
                                                type="button"
                                                :disabled="season.status <= 12"
                                                :class="
                                                    season.status <= 12
                                                        ? 'cursor-not-allowed opacity-30'
                                                        : ''
                                                "
                                                class="row-action border-indigo-500/20 bg-indigo-500/10 text-indigo-300 hover:bg-indigo-500/20 disabled:hover:bg-indigo-500/10"
                                                @click.prevent="
                                                    isSeasonStoryLineModalOpen =
                                                        season.id
                                                "
                                            >
                                                <i class="fa fa-image"></i>
                                                <span>Storyline</span>
                                            </button>

                                            <!-- Draft -->
                                            <button
                                                type="button"
                                                :disabled="season.id == 1"
                                                :class="
                                                    season.id == 1
                                                        ? 'cursor-not-allowed opacity-30'
                                                        : ''
                                                "
                                                class="row-action border-pink-500/20 bg-pink-500/10 text-pink-300 hover:bg-pink-500/20 disabled:hover:bg-pink-500/10"
                                                @click.prevent="
                                                    isSeasonDraftModalOpen =
                                                        season.id
                                                "
                                            >
                                                <i class="fa fa-users"></i>
                                                <span>Draft</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Loading -->
                            <tbody v-else-if="loading">
                                <tr
                                    v-for="index in 6"
                                    :key="`loading-${index}`"
                                    class="border-b border-gray-800"
                                >
                                    <td
                                        v-for="column in 11"
                                        :key="column"
                                        class="px-3 py-4"
                                    >
                                        <div
                                            class="h-4 animate-pulse rounded bg-gray-800"
                                            :class="
                                                column === 11
                                                    ? 'w-32'
                                                    : 'w-24'
                                            "
                                        ></div>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Empty -->
                            <tbody v-else>
                                <tr>
                                    <td
                                        colspan="11"
                                        class="px-4 py-16 text-center"
                                    >
                                        <div
                                            class="mx-auto flex max-w-sm flex-col items-center"
                                        >
                                            <div
                                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-gray-800 bg-gray-900 text-gray-600"
                                            >
                                                <i
                                                    class="fa fa-calendar-times text-lg"
                                                ></i>
                                            </div>

                                            <p
                                                class="text-sm font-semibold text-gray-400"
                                            >
                                                No seasons found
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-gray-600"
                                            >
                                                Try changing your search
                                                criteria.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer / Pagination -->
                    <div
                        v-if="seasons.total_count"
                        class="border-t border-gray-800 bg-gray-900/50 px-4 py-3"
                    >
                        <div class="overflow-x-auto">
                            <Paginator
                                :page_number="search_seasons.page_num"
                                :total_rows="seasons.total_count ?? 0"
                                :itemsperpage="search_seasons.itemsperpage"
                                @page_num="handlePagination"
                            />
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     CURRENT PLAYER SIGNINGS
                ====================================================== -->
                <Modal
                    :show="isPlayerSigningModalOpen"
                    :maxWidth="'6xl'"
                    title="Free Agent Signings"
                    @close="isPlayerSigningModalOpen = false"
                >
                    <div class="bg-gray-950 p-3">
                        <FreeAgents @newSeason="handleNewSeason" />
                    </div>
                </Modal>

                <!-- =====================================================
                     ROOKIE DRAFT
                ====================================================== -->
                <Modal
                    :show="isDraftModalOpen"
                    :maxWidth="'6xl'"
                    title="Draft Rookie Players"
                    @close="isDraftModalOpen = false"
                >
                    <div class="bg-gray-950 p-0">
                        <Draft
                            :key="seasons.current_season"
                            :season_id="seasons.current_season"
                            @newSeason="handleNewSeason"
                        />
                    </div>
                </Modal>

                <!-- =====================================================
                     CURRENT SEASON AWARDS
                ====================================================== -->
                <Modal
                    :show="isPlayerAwardsModalOpen"
                    :maxWidth="'6xl'"
                    title="Season Awards"
                    @close="closeAwardsModal"
                >
                    <div class="bg-gray-950 p-3">
                        <Awards
                            :team_ids="seasons.team_ids"
                            @newSeason="handleNewSeason"
                        />
                    </div>
                </Modal>

                <!-- =====================================================
                     TRADE SEASON
                ====================================================== -->
                <Modal
                    :show="isTradeModalOpen"
                    :maxWidth="'fullscreen'"
                    title="Trade Players"
                    @close="isTradeModalOpen = false"
                >
                    <div class="bg-gray-950 p-3">
                        <Trade @newSeason="handleSeasonInfo" />
                    </div>
                </Modal>

                <!-- =====================================================
                     HISTORICAL AWARDS
                ====================================================== -->
                <Modal
                    :show="isSeasonAwardsModalOpen"
                    :maxWidth="'fullscreen'"
                    :title="`Season ${isSeasonAwardsModalOpen} Awards`"
                    @close="isSeasonAwardsModalOpen = false"
                >
                    <div class="bg-gray-950 p-3">
                        <SeasonAwards
                            :key="isSeasonAwardsModalOpen"
                            :season_id="isSeasonAwardsModalOpen"
                            @newSeason="handleSeasonInfo"
                        />
                    </div>
                </Modal>

                <!-- =====================================================
                     HISTORICAL DRAFT
                ====================================================== -->
                <Modal
                    :show="isSeasonDraftModalOpen"
                    :maxWidth="'fullscreen'"
                    :title="`Season ${isSeasonDraftModalOpen} Draft Results`"
                    @close="isSeasonDraftModalOpen = false"
                >
                    <div class="bg-gray-950 p-3">
                        <DraftBoard
                            :key="isSeasonDraftModalOpen"
                            :season_id="isSeasonDraftModalOpen"
                            @newSeason="handleSeasonInfo"
                        />
                    </div>
                </Modal>

                <!-- =====================================================
                     COACH SIGNINGS
                ====================================================== -->
                <Modal
                    :show="isCoachSigningModalOpen"
                    :maxWidth="'fullscreen'"
                    title="Season Coach Signing"
                    @close="isCoachSigningModalOpen = false"
                >
                    <div class="bg-gray-950 p-3">
                        <FreeAgentsCoach
                            :showControls="true"
                            :key="isCoachSigningModalOpen"
                            @newSeason="handleSeasonInfo"
                        />
                    </div>
                </Modal>

                <!-- =====================================================
                     STORYLINE
                ====================================================== -->
                <Modal
                    :show="isSeasonStoryLineModalOpen"
                    :maxWidth="'4xl'"
                    :title="`Season ${isSeasonStoryLineModalOpen} Storyline`"
                    @close="isSeasonStoryLineModalOpen = false"
                >
                    <div class="bg-gray-950 p-3">
                        <StoryLine
                            :key="isSeasonStoryLineModalOpen"
                            :season_id="isSeasonStoryLineModalOpen"
                            @newSeason="handleSeasonInfo"
                        />
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    </div>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import Paginator from "@/Components/Paginator.vue";

import { ref, onMounted, computed } from "vue";
import Swal from "sweetalert2";
import axios from "axios";

import Trade from "@/Pages/Seasons/Module/Trade.vue";
import FreeAgents from "@/Pages/Seasons/Module/FreeAgents.vue";
import FreeAgentsCoach from "@/Pages/Coaches/FreeAgentsCoach.vue";
import SeasonAwards from "@/Pages/Seasons/Module/SeasonAwards.vue";
import Awards from "@/Pages/Seasons/Module/Awards.vue";
import Draft from "@/Pages/Seasons/Module/Draft.vue";
import DraftBoard from "@/Pages/Seasons/Module/DraftBoard.vue";
import Add from "@/Pages/Seasons/Module/Add.vue";
import StoryLine from "@/Pages/Seasons/Module/Storyline.vue";

/* =========================================================
   MODALS
========================================================= */

const isTradeModalOpen = ref(false);
const isPlayerSigningModalOpen = ref(false);
const isDraftModalOpen = ref(false);
const isPlayerAwardsModalOpen = ref(false);
const isSeasonAwardsModalOpen = ref(false);
const isSeasonDraftModalOpen = ref(false);
const isCoachSigningModalOpen = ref(false);
const isSeasonStoryLineModalOpen = ref(false);

/* =========================================================
   DATA
========================================================= */

const seasons = ref({
    seasons: [],
    total_pages: 0,
    total_count: 0,
    current_season: 0,
    is_new_season: 0,
    is_draft_finished: false,
    team_ids: [],
});

const loading = ref(false);

const leagues_dropdown = ref([]);

const season_id = ref(0);

const isProcessing = ref(false);

const currentTab = ref("Regular");

/* =========================================================
   SEARCH
========================================================= */

const search_seasons = ref({
    current_page: 1,
    page_num: 1,
    total_pages: 0,
    total: 0,
    search: "",
    change_key: "",
    itemsperpage: 10,
});

/* =========================================================
   FORM
========================================================= */

const form = useForm({
    type: 3,
    start: 16,
    league_id: 1,
    seasons_id: 0,
    conference_id: 0,
    match_type: 1,
    errors: [],
});

/* =========================================================
   COMPUTED
========================================================= */

const hasCurrentSeasonActions = computed(() => {
    return [
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
    ].includes(Number(seasons.value?.is_new_season));
});

/* =========================================================
   FETCH SEASONS
========================================================= */

const fetchSeasons = async (page = null) => {
    try {
        loading.value = true;

        if (page !== null) {
            search_seasons.value.page_num = page;
            search_seasons.value.current_page = page;
        }

        const response = await axios.post(
            route("seasons.list"),
            search_seasons.value
        );

        seasons.value = {
            ...seasons.value,
            ...(response.data ?? {}),
        };
    } catch (error) {
        console.error("Error fetching seasons:", error);

        Swal.fire({
            icon: "error",
            title: "Unable to load seasons",
            text: "There was a problem loading the season list.",
        });
    } finally {
        loading.value = false;
    }
};

/* =========================================================
   PAGINATION
========================================================= */

const handlePagination = (page_num) => {
    const page = page_num ?? 1;

    search_seasons.value.page_num = page;
    search_seasons.value.current_page = page;

    fetchSeasons();
};

/* =========================================================
   REFRESH HANDLERS
========================================================= */

const handleSeasonInfo = () => {
    fetchSeasons();
};

const handleCreateSeason = () => {
    fetchSeasons();
};

const handleNewSeason = (newSeason) => {
    if (newSeason) {
        isPlayerSigningModalOpen.value = false;
    }

    fetchSeasons();
};

/* =========================================================
   LEAGUE DROPDOWN
========================================================= */

const leagueDropdown = async () => {
    try {
        const response = await axios.get(
            route("leagues.dropdown")
        );

        if (!response) {
            throw new Error("Failed to fetch leagues");
        }

        leagues_dropdown.value = response.data;
    } catch (error) {
        console.error("Error fetching leagues:", error);
    }
};

/* =========================================================
   PREPARE PLAYER STATS
========================================================= */

const prepareCurrentSeasonStats = async () => {
    try {
        const team_ids = seasons.value.team_ids ?? [];

        for (let i = 0; i < team_ids.length; i++) {
            const team_id = team_ids[i];

            await updatePlayerStatsPerTeam(
                i,
                team_id,
                team_ids.length
            );
        }
    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to update player status for some or all teams. Please try again later.",
        });
    }
};

const updatePlayerStatsPerTeam = async (
    index,
    team_id,
    team_count
) => {
    try {
        const response = await axios.post(
            route("store.player.stats"),
            {
                team_id,
            }
        );

        return response;
    } catch (error) {
        console.error(error);

        throw error;
    }
};

/* =========================================================
   UPDATE PLAYER STATUS
========================================================= */

const updatePlayerStatus = async () => {
    try {
        const team_ids = seasons.value.team_ids ?? [];

        if (!team_ids.length) {
            Swal.fire({
                icon: "warning",
                title: "No teams found",
                text: "There are no teams available for player status updates.",
            });

            return;
        }

        isProcessing.value = true;

        /*
         * Process every team sequentially.
         * This avoids multiple simultaneous updates to the
         * same season/player data.
         */
        for (let i = 0; i < team_ids.length; i++) {
            const team_id = team_ids[i];

            await updatePlayerStatusPerTeam(
                i,
                team_id,
                i === team_ids.length - 1
            );
        }

        await fetchSeasons();
    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to update player status for some or all teams. Please try again later.",
        });
    } finally {
        isProcessing.value = false;
    }
};

const updatePlayerStatusPerTeam = async (
    index,
    team_id,
    is_last
) => {
    try {
        form.is_last = is_last;
        form.team_id = team_id;

        const response = await axios.post(
            route("update.player.status"),
            form
        );

        const improvedPlayers =
            response.data.improved_players || [];

        const declinedPlayers =
            response.data.declined_players || [];

        const reSignedPlayers =
            response.data.re_signed_players || [];

        const teamName =
            response.data.team_name || "Unknown Team";

        const htmlMessage = `
            <div style="
                color: #d1d5db;
                font-size: 12px;
                text-align: left;
            ">
                <p style="
                    margin-bottom: 14px;
                    color: #9ca3af;
                ">
                    Player status for
                    <strong style="color:#f3f4f6;">
                        ${escapeHtml(teamName)}
                    </strong>
                    has been updated.
                </p>

                ${buildPlayerUpdateTable(
                    "Improved Players",
                    improvedPlayers,
                    "#34d399"
                )}

                ${buildPlayerUpdateTable(
                    "Declined Players",
                    declinedPlayers,
                    "#fb7185"
                )}

                ${buildPlayerUpdateTable(
                    "Re-Signed Players",
                    reSignedPlayers,
                    "#60a5fa"
                )}
            </div>
        `;

        Swal.fire({
            title: `${teamName} Player Update`,
            html: htmlMessage,
            showConfirmButton: true,
            confirmButtonText: "Continue",
            background: "#111827",
            color: "#e5e7eb",
            position: "top",
            width: 700,
        });

        return response;
    } catch (error) {
        console.error(error);

        Swal.fire({
            icon: "error",
            title: "Update Failed",
            text: `Failed to update player status for team ID ${team_id}. Please try again later.`,
            background: "#111827",
            color: "#e5e7eb",
        });

        throw error;
    }
};

/* =========================================================
   SEASON DROPDOWN
========================================================= */

const seasonsDropdown = async () => {
    try {
        const response = await axios.post(
            route("seasons.dropdown"),
            {
                season_id: 0,
            }
        );

        localStorage.setItem(
            "seasons",
            JSON.stringify(response.data)
        );
    } catch (error) {
        console.error(
            "Error fetching season dropdown:",
            error
        );
    }
};

/* =========================================================
   TABS
========================================================= */

const changeTab = (tab) => {
    currentTab.value = tab;
};

/* =========================================================
   AWARDS MODAL
========================================================= */

const closeAwardsModal = async () => {
    await fetchSeasons();

    isPlayerAwardsModalOpen.value = false;
};

/* =========================================================
   TABLE HELPERS
========================================================= */

const finalsWinnerScore = (season) => {
    if (
        season.finals_winner_score == null ||
        season.finals_loser_score == null
    ) {
        return "";
    }

    return Math.max(
        Number(season.finals_winner_score),
        Number(season.finals_loser_score)
    );
};

const finalsLoserScore = (season) => {
    if (
        season.finals_winner_score == null ||
        season.finals_loser_score == null
    ) {
        return "";
    }

    return Math.min(
        Number(season.finals_winner_score),
        Number(season.finals_loser_score)
    );
};

/*
 * Preserves the special visual indication from the
 * original component:
 *
 * 1. Finals winner is also regular champion
 * 2. Winner/loser conference names are the same
 * 3. Same champion appeared in consecutive rows
 */
const seasonRowClass = (season, index) => {
    const classes = [];

    if (
        season.finals_winner_id ===
        season.champion_id
    ) {
        classes.push(
            "bg-amber-500/[0.035]"
        );
    }

    if (
        season.winner_conference_name &&
        season.winner_conference_name ===
            season.loser_conference_name
    ) {
        classes.push(
            "bg-slate-500/[0.06]"
        );
    }

    if (
        index > 0 &&
        seasons.value.seasons?.[index - 1]?.champion_name ===
            season.champion_name
    ) {
        classes.push(
            "bg-emerald-500/[0.035]"
        );
    }

    return classes;
};

const conferenceCellClass = (
    season,
    conferenceId,
    color
) => {
    const isChampion =
        season.finals_winner_id == conferenceId;

    const classes = [];

    if (isChampion) {
        const colors = {
            red: "bg-red-500/[0.06]",
            blue: "bg-blue-500/[0.06]",
            green: "bg-emerald-500/[0.06]",
            yellow: "bg-yellow-500/[0.06]",
        };

        classes.push(colors[color]);
    }

    return classes;
};

/* =========================================================
   SWEETALERT HELPERS
========================================================= */

const escapeHtml = (value) => {
    if (value === null || value === undefined) {
        return "";
    }

    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
};

const buildPlayerUpdateTable = (
    title,
    players,
    accent
) => {
    if (!players?.length) {
        return `
            <div style="
                margin-bottom: 12px;
                padding: 10px 12px;
                border: 1px solid #1f2937;
                border-radius: 8px;
                background: #0f172a;
            ">
                <div style="
                    color: ${accent};
                    font-weight: 700;
                    font-size: 11px;
                    margin-bottom: 3px;
                ">
                    ${title}
                </div>

                <div style="
                    color: #6b7280;
                    font-size: 11px;
                ">
                    None
                </div>
            </div>
        `;
    }

    return `
        <div style="
            margin-bottom: 14px;
            overflow: hidden;
            border: 1px solid #1f2937;
            border-radius: 8px;
            background: #0f172a;
        ">
            <div style="
                padding: 9px 12px;
                border-bottom: 1px solid #1f2937;
                color: ${accent};
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
            ">
                ${title}
            </div>

            <table style="
                width:100%;
                border-collapse:collapse;
                font-size:11px;
            ">
                <thead>
                    <tr style="color:#6b7280;">
                        <th style="
                            padding:8px;
                            text-align:left;
                            border-bottom:1px solid #1f2937;
                        ">
                            Player
                        </th>

                        <th style="
                            padding:8px;
                            text-align:left;
                            border-bottom:1px solid #1f2937;
                        ">
                            Role
                        </th>

                        <th style="
                            padding:8px;
                            text-align:right;
                            border-bottom:1px solid #1f2937;
                        ">
                            Years
                        </th>
                    </tr>
                </thead>

                <tbody>
                    ${players
                        .map(
                            (player) => `
                                <tr>
                                    <td style="
                                        padding:8px;
                                        color:#e5e7eb;
                                        border-bottom:1px solid #1f2937;
                                    ">
                                        ${escapeHtml(player.name)}
                                    </td>

                                    <td style="
                                        padding:8px;
                                        color:#9ca3af;
                                        border-bottom:1px solid #1f2937;
                                    ">
                                        ${escapeHtml(player.role)}
                                    </td>

                                    <td style="
                                        padding:8px;
                                        color:#d1d5db;
                                        text-align:right;
                                        border-bottom:1px solid #1f2937;
                                    ">
                                        ${escapeHtml(
                                            player.contract_years
                                        )}
                                    </td>
                                </tr>
                            `
                        )
                        .join("")}
                </tbody>
            </table>
        </div>
    `;
};

/* =========================================================
   MOUNT
========================================================= */

onMounted(() => {
    fetchSeasons();
    leagueDropdown();
});
</script>

<style scoped>
.season-action {
    @apply inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-3 py-2 text-xs font-semibold transition-all duration-150;
}

.row-action {
    @apply inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border px-2.5 py-2 text-[11px] font-semibold transition-all duration-150;
}

.conference-dot {
    @apply h-1.5 w-1.5 shrink-0 rounded-full;
}
</style>