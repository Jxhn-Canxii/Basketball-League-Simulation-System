```vue
<template>
    <div
        v-if="team_info?.teams && !loading"
        class="min-h-screen w-full overflow-hidden bg-gray-950"
    >
        <!-- =========================================================
             TEAM HEADER
        ========================================================== -->
        <section
            class="relative overflow-hidden border-b border-white/10"
            :style="{
                background: `linear-gradient(135deg, #${team_info.teams.primary_color} 0%, #${team_info.teams.secondary_color} 100%)`
            }"
        >
            <!-- Decorative background -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div
                    class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/5 blur-3xl"
                ></div>

                <div
                    class="absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-black/10 blur-3xl"
                ></div>
            </div>

            <div class="relative px-4 py-6 sm:px-6 lg:px-8">
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
                >
                    <!-- Team identity -->
                    <div class="min-w-0">
                        <div class="mb-3 flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-black/20 shadow-lg backdrop-blur"
                            >
                                <i class="fas fa-users text-lg text-white"></i>
                            </div>

                            <div class="min-w-0">
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <h1
                                        class="truncate text-xl font-black tracking-tight text-white sm:text-2xl"
                                    >
                                        {{ team_info.teams.team_name ?? "-" }}
                                    </h1>

                                    <span
                                        class="rounded-lg border border-white/20 bg-black/20 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-white/90 backdrop-blur"
                                    >
                                        {{ team_info.teams.acronym ?? "-" }}
                                    </span>
                                </div>

                                <p
                                    class="mt-1 text-sm font-medium text-white/70"
                                >
                                    {{
                                        team_info.teams.conference_name ?? "-"
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Quick information -->
                        <div class="flex flex-wrap gap-2">
                            <div
                                class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2 text-xs font-semibold text-white/90 backdrop-blur"
                            >
                                <i
                                    class="fas fa-building text-white/60"
                                ></i>
                                <span>Team Roster</span>
                            </div>

                            <div
                                v-if="team_info.remaining_cap_space > 0"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-black/20 px-3 py-2 text-xs font-semibold text-white/90 backdrop-blur"
                            >
                                <i
                                    class="fas fa-wallet text-white/60"
                                ></i>

                                <span>
                                    Cap Space:
                                    {{
                                        moneyFormatter(
                                            team_info.remaining_cap_space ?? 0
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Season selector -->
                    <div class="w-full lg:w-auto lg:min-w-[220px]">
                        <label
                            class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.16em] text-white/60"
                        >
                            Season
                        </label>

                        <div class="relative">
                            <i
                                class="fas fa-calendar-alt pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-xs text-white/60"
                            ></i>

                            <select
                                v-model="season_id"
                                @change="seasonBehavior"
                                class="w-full appearance-none rounded-xl border border-white/20 bg-black/25 py-2.5 pl-9 pr-9 text-sm font-semibold text-white shadow-lg outline-none backdrop-blur transition hover:bg-black/30 focus:border-white/40 focus:ring-2 focus:ring-white/10"
                            >
                                <option
                                    value="0"
                                    disabled
                                    class="bg-gray-900 text-gray-400"
                                >
                                    Select Season
                                </option>

                                <option
                                    v-for="season in seasons"
                                    :key="season.season_id"
                                    :value="season.season_id"
                                    class="bg-gray-900 text-white"
                                >
                                    {{ season.name }}
                                </option>
                            </select>

                            <i
                                class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-white/60"
                            ></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========================================================
             NAVIGATION
        ========================================================== -->
        <section
            class="sticky top-0 z-30 border-b border-gray-800 bg-gray-950/95 backdrop-blur-xl"
        >
            <div class="px-3 sm:px-5 lg:px-8">
                <div
                    class="flex min-w-0 flex-col gap-2 py-2 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Primary navigation -->
                    <div
                        class="flex min-w-0 overflow-x-auto rounded-xl bg-gray-900 p-1 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-gray-700"
                    >
                        <button
                            type="button"
                            @click="viewType = 'roster'"
                            :class="[
                                'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition-all sm:px-4',
                                viewType === 'roster'
                                    ? 'bg-white text-gray-950 shadow'
                                    : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                            ]"
                        >
                            <i class="fas fa-users"></i>
                            Roster
                        </button>

                        <button
                            type="button"
                            @click="viewType = 'depth'"
                            :class="[
                                'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition-all sm:px-4',
                                viewType === 'depth'
                                    ? 'bg-white text-gray-950 shadow'
                                    : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                            ]"
                        >
                            <i class="fas fa-chart-bar"></i>
                            Depth Chart
                        </button>
                    </div>

                    <!-- Roster filters -->
                    <div
                        v-if="viewType === 'roster'"
                        class="flex min-w-0 overflow-x-auto rounded-xl border border-gray-800 bg-gray-900 p-1 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-gray-700"
                    >
                        <button
                            type="button"
                            @click="currentTab = 'all'"
                            :class="tabClass('all')"
                        >
                            <i class="fas fa-users"></i>
                            <span>All</span>
                        </button>

                        <button
                            v-if="season_id > 1"
                            type="button"
                            @click="currentTab = 'new'"
                            :class="tabClass('new')"
                        >
                            <i class="fas fa-user-plus"></i>
                            <span>New</span>

                            <span
                                v-if="filteredNewPlayers?.length"
                                class="rounded-full bg-blue-500/20 px-1.5 py-0.5 text-[10px] text-blue-300"
                            >
                                {{ filteredNewPlayers.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="currentTab = 'transferred'"
                            :class="tabClass('transferred')"
                        >
                            <i class="fas fa-exchange-alt"></i>
                            <span>Transferred</span>

                            <span
                                v-if="filteredTransferredPlayers?.length"
                                class="rounded-full bg-gray-700 px-1.5 py-0.5 text-[10px] text-gray-300"
                            >
                                {{ filteredTransferredPlayers.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="currentTab = 'injured'"
                            :class="tabClass('injured')"
                        >
                            <i class="fas fa-procedures"></i>
                            <span>Injured</span>

                            <span
                                v-if="filteredInjuredPlayers?.length"
                                class="rounded-full bg-red-500/20 px-1.5 py-0.5 text-[10px] text-red-300"
                            >
                                {{ filteredInjuredPlayers.length }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========================================================
             CONTENT
        ========================================================== -->
        <main class="px-3 py-4 sm:px-5 lg:px-8 lg:py-6">
            <!-- =====================================================
                 ROSTER
            ====================================================== -->
            <section v-if="viewType === 'roster'">
                <!-- Section heading -->
                <div
                    class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <div
                            class="mb-1 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-400"
                            ></span>

                            {{ sectionLabel }}
                        </div>

                        <h2
                            class="text-lg font-black tracking-tight text-white sm:text-xl"
                        >
                            {{ sectionTitle }}
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Click a player to view their complete profile.
                        </p>
                    </div>

                    <!-- Current roster toggle -->
                    <button
                        v-if="
                            currentTab === 'all' &&
                            team_info?.current_season_id == season_id
                        "
                        type="button"
                        @click="toggleShowTransferred"
                        class="inline-flex w-fit items-center gap-2 rounded-xl border border-gray-800 bg-gray-900 px-3 py-2 text-xs font-bold text-gray-300 transition hover:border-gray-700 hover:bg-gray-800 hover:text-white"
                    >
                        <i
                            :class="
                                showTransferred
                                    ? 'fas fa-eye-slash'
                                    : 'fas fa-eye'
                            "
                        ></i>

                        {{
                            showTransferred
                                ? "Hide Transferred"
                                : "Show Transferred"
                        }}
                    </button>
                </div>

                <!-- Legend -->
                <div
                    class="mb-4 flex flex-wrap items-center gap-2 rounded-xl border border-gray-800 bg-gray-900/60 px-3 py-2.5"
                >
                    <span
                        class="mr-1 text-[10px] font-bold uppercase tracking-wider text-gray-600"
                    >
                        Legend
                    </span>

                    <span class="legend-item">
                        <i class="fas fa-user-plus text-blue-400"></i>
                        New
                    </span>

                    <span class="legend-item">
                        <i class="fas fa-exchange-alt text-gray-400"></i>
                        Transferred
                    </span>

                    <span class="legend-item">
                        <i class="fas fa-user-times text-red-400"></i>
                        Near Retirement
                    </span>

                    <span class="legend-item">
                        <i class="fas fa-check-circle text-emerald-400"></i>
                        Active
                    </span>

                    <span class="legend-item">
                        <i
                            class="fas fa-hand-holding-medical text-orange-400"
                        ></i>
                        Hardship
                    </span>
                </div>

                <!-- Table -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 shadow-2xl"
                >
                    <div
                        class="overflow-x-auto scrollbar-thin scrollbar-track-gray-950 scrollbar-thumb-gray-700"
                    >
                        <table
                            class="roster-table min-w-[1700px] text-xs"
                        >
                            <thead>
                                <tr
                                    :style="{
                                        backgroundColor:
                                            '#' +
                                            team_info.teams.primary_color
                                    }"
                                >
                                    <th class="sticky-col first-col">
                                        #
                                    </th>

                                    <th>Achievements</th>
                                    <th>Draft</th>

                                    <th class="sticky-player">
                                        Player
                                    </th>

                                    <th>Pos</th>
                                    <th>Exp</th>
                                    <th>Role</th>
                                    <th title="Years with team">
                                        Team
                                    </th>
                                    <th title="Remaining contract">
                                        Contract
                                    </th>

                                    <th title="Overall Rating">
                                        OVR
                                    </th>

                                    <th title="Best Player of the Game">
                                        BPOTG
                                    </th>

                                    <th title="Total Team Games">
                                        GT
                                    </th>

                                    <th title="Games Played">
                                        GP
                                    </th>

                                    <th title="Minutes Per Game">
                                        MPG
                                    </th>

                                    <th title="Points Per Game">
                                        PPG
                                    </th>

                                    <th title="Rebounds Per Game">
                                        RPG
                                    </th>

                                    <th title="Assists Per Game">
                                        APG
                                    </th>

                                    <th title="Steals Per Game">
                                        SPG
                                    </th>

                                    <th title="Blocks Per Game">
                                        BPG
                                    </th>

                                    <th title="Turnovers Per Game">
                                        TOPG
                                    </th>

                                    <th title="Fouls Per Game">
                                        FPG
                                    </th>

                                    <th title="Efficiency">
                                        EFF
                                    </th>

                                    <th title="Player Efficiency Rating">
                                        PER
                                    </th>

                                    <th title="Player Valuation">
                                        PVE
                                    </th>

                                    <th>Legend</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(player, index) in activePlayers"
                                    :key="player.player_id"
                                    @click.prevent="showPlayerProfile(player)"
                                    :class="[
                                        'player-row',
                                        player.is_injured == 1
                                            ? 'injured-row'
                                            : '',
                                        currentTab === 'all' &&
                                        index >= 15 &&
                                        !player.is_injured
                                            ? 'reserve-row'
                                            : ''
                                    ]"
                                >
                                    <!-- Number -->
                                    <td class="sticky-col first-col number-cell">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Achievements -->
                                    <td>
                                        <div
                                            class="flex items-center gap-1"
                                        >
                                            <span
                                                v-if="
                                                    player.championships_won > 0
                                                "
                                                :title="`National Championships: ${player.championships_won}`"
                                                class="achievement gold"
                                            >
                                                {{ player.championships_won }}
                                            </span>

                                            <span
                                                v-if="
                                                    player.conference_championships_won >
                                                    0
                                                "
                                                :title="`Conference Championships: ${player.conference_championships_won}`"
                                                class="achievement silver"
                                            >
                                                {{
                                                    player.conference_championships_won
                                                }}
                                            </span>

                                            <span
                                                v-if="player.awards_won > 0"
                                                :title="`Awards Won: ${player.awards_won}`"
                                                class="achievement blue"
                                            >
                                                {{ player.awards_won }}
                                            </span>

                                            <span
                                                v-if="
                                                    !player.championships_won &&
                                                    !player.conference_championships_won &&
                                                    !player.awards_won
                                                "
                                                class="text-gray-700"
                                            >
                                                —
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Draft -->
                                    <td
                                        :title="
                                            'Draft class: ' +
                                            (player.draft_class ?? '-')
                                        "
                                        class="max-w-[180px]"
                                    >
                                        <span class="truncate text-gray-400">
                                            {{ draftLabel(player) }}
                                        </span>
                                    </td>

                                    <!-- Player -->
                                    <td
                                        class="sticky-player player-name-cell"
                                        :class="
                                            player.is_injured == 1
                                                ? 'injured-player-cell'
                                                : ''
                                        "
                                    >
                                        <div
                                            class="flex min-w-[190px] items-center gap-2"
                                        >
                                            <!-- Improvement -->
                                            <div
                                                class="flex w-3 shrink-0 justify-center"
                                            >
                                                <i
                                                    v-if="
                                                        player.has_improved == 1
                                                    "
                                                    class="fas fa-chevron-up text-[9px] text-lime-400"
                                                ></i>

                                                <i
                                                    v-else-if="
                                                        player.has_improved == 0
                                                    "
                                                    class="fas fa-chevron-down text-[9px] text-red-400"
                                                ></i>

                                                <i
                                                    v-else
                                                    class="fas fa-minus text-[8px] text-gray-600"
                                                ></i>
                                            </div>

                                            <div class="min-w-0">
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <span
                                                        class="truncate font-bold text-gray-200"
                                                    >
                                                        {{ player.name }}
                                                    </span>

                                                    <sup
                                                        class="shrink-0 text-[9px] font-bold text-gray-600"
                                                    >
                                                        {{ player.age }}
                                                    </sup>

                                                    <i
                                                        :class="
                                                            getMoraleIcon(
                                                                player.morale
                                                            )
                                                        "
                                                        :title="`${getMoraleTitle(player.morale)} ${player.morale}%`"
                                                        class="shrink-0 text-[11px]"
                                                    ></i>

                                                    <i
                                                        v-if="
                                                            player.is_reserved ==
                                                            1
                                                        "
                                                        class="fas fa-lock shrink-0 text-[9px] text-gray-600"
                                                        title="Reserved"
                                                    ></i>
                                                </div>

                                                <div
                                                    class="mt-0.5 text-[9px] text-gray-600"
                                                >
                                                    Retirement:
                                                    {{
                                                        player.retirement_age ??
                                                        "-"
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Position -->
                                    <td>
                                        <span
                                            class="position-pill"
                                        >
                                            {{ player.position ?? "-" }}
                                        </span>
                                    </td>

                                    <!-- Experience -->
                                    <td>
                                        {{
                                            player.total_seasons_played ?? 0
                                        }}
                                    </td>

                                    <!-- Role -->
                                    <td>
                                        <span
                                            :class="
                                                roleBadgeClass(
                                                    player.role ?? 'reserved'
                                                )
                                            "
                                        >
                                            {{ player.role ?? "reserved" }}
                                        </span>
                                    </td>

                                    <!-- Team years -->
                                    <td>
                                        {{ player.seasons_played_with_team ?? 0 }}
                                    </td>

                                    <!-- Contract -->
                                    <td>
                                        <div
                                            class="whitespace-nowrap font-semibold text-gray-300"
                                        >
                                            {{ player.salary ?? "-" }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-[9px] uppercase text-gray-600"
                                        >
                                            {{
                                                player.contract_type ?? "-"
                                            }}
                                            ·
                                            {{
                                                player.contract_years ?? 0
                                            }}y
                                        </div>
                                    </td>

                                    <!-- OVR -->
                                    <td>
                                        <div class="rating rating-ovr">
                                            {{ player.overall_rating ?? "-" }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-[9px] text-gray-600"
                                        >
                                            {{
                                                player.potential_status ?? ""
                                            }}
                                        </div>
                                    </td>

                                    <!-- BPOTG -->
                                    <td>
                                        {{
                                            number(
                                                player.bpg_game_leader
                                            )
                                        }}
                                    </td>

                                    <!-- GT -->
                                    <td>
                                        {{
                                            Math.round(
                                                player.team_total_games ?? 0
                                            )
                                        }}
                                    </td>

                                    <!-- GP -->
                                    <td>
                                        {{
                                            Math.round(
                                                player.games_played ?? 0
                                            )
                                        }}
                                    </td>

                                    <!-- MPG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_minutes_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- PPG -->
                                    <td class="stat-highlight">
                                        {{
                                            number(
                                                player.average_points_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- RPG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_rebounds_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- APG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_assists_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- SPG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_steals_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- BPG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_blocks_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- TOPG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_turnovers_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- FPG -->
                                    <td>
                                        {{
                                            number(
                                                player.average_fouls_per_game
                                            )
                                        }}
                                    </td>

                                    <!-- EFF -->
                                    <td>
                                        <span
                                            :class="
                                                player.effeciency <= 0
                                                    ? 'text-red-400'
                                                    : 'text-lime-400'
                                            "
                                            class="font-black"
                                        >
                                            {{ player.effeciency ?? "-" }}
                                        </span>
                                    </td>

                                    <!-- PER -->
                                    <td>
                                        <span
                                            class="font-bold text-gray-300"
                                        >
                                            {{
                                                player.per_game_score ?? "-"
                                            }}
                                        </span>
                                    </td>

                                    <!-- PVE -->
                                    <td>
                                        {{
                                            player.player_valuation ?? "-"
                                        }}
                                    </td>

                                    <!-- Legend -->
                                    <td>
                                        <PlayerLegend :player="player" />
                                    </td>
                                </tr>

                                <!-- Empty -->
                                <tr v-if="activePlayers.length === 0">
                                    <td
                                        colspan="25"
                                        class="py-16 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center justify-center"
                                        >
                                            <div
                                                class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-900"
                                            >
                                                <i
                                                    class="fas fa-users-slash text-gray-600"
                                                ></i>
                                            </div>

                                            <p
                                                class="text-sm font-bold text-gray-400"
                                            >
                                                No players found
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-gray-600"
                                            >
                                                There are no players matching
                                                this view.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom hint -->
                    <div
                        class="flex items-center justify-between border-t border-gray-800 bg-gray-900/50 px-4 py-2.5"
                    >
                        <span
                            class="text-[10px] font-medium text-gray-600"
                        >
                            <i class="fas fa-mouse-pointer mr-1"></i>
                            Select a player to view profile
                        </span>

                        <span
                            class="text-[10px] font-medium text-gray-600"
                        >
                            {{ activePlayers.length }} players
                        </span>
                    </div>
                </div>
            </section>

            <!-- =====================================================
                 DEPTH CHART
            ====================================================== -->
            <section v-else>
                <div
                    class="mb-5 flex items-end justify-between"
                >
                    <div>
                        <div
                            class="mb-1 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-blue-400"
                            ></span>
                            Rotation
                        </div>

                        <h2
                            class="text-xl font-black tracking-tight text-white"
                        >
                            Depth Chart
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Current player rotation and positional depth.
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 shadow-2xl"
                >
                    <div class="p-3 sm:p-5">
                        <DepthChartRow
                            v-if="team_roster"
                            :key="`${team_roster?.team_id}-${season_id}`"
                            :players="team_roster?.players"
                            :season_id="season_id"
                        />
                    </div>
                </div>
            </section>
        </main>

        <!-- =========================================================
             PLAYER PROFILE MODAL
        ========================================================== -->
        <Modal
            :show="showPlayerProfileModal"
            :maxWidth="'6xl'"
            title="Player Profile"
            @close="showPlayerProfileModal = false"
        >
            <div class="bg-gray-950 p-3 sm:p-6">
                <PlayerPerformance
                    v-if="selectedPlayer?.player_id"
                    :key="selectedPlayer.player_id"
                    :player_id="selectedPlayer.player_id"
                />
            </div>
        </Modal>
    </div>

    <!-- =============================================================
         LOADING
    ============================================================== -->
    <div
        v-else
        class="flex min-h-[420px] items-center justify-center bg-gray-950"
    >
        <div class="flex flex-col items-center">
            <div
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-800 bg-gray-900"
            >
                <i
                    class="fas fa-spinner fa-spin text-xl text-gray-400"
                ></i>
            </div>

            <p
                class="mt-4 text-sm font-semibold text-gray-400"
            >
                Loading team roster...
            </p>

            <p class="mt-1 text-xs text-gray-600">
                Preparing player information
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import axios from "axios";
import { roleBadgeClass, moneyFormatter } from "@/Utility/Formatter";

import PlayerPerformance from "../../Players/Module/PlayerPerformance.vue";
import DepthChartRow from "./DepthChartRow.vue";

const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});

const showExtendModal = ref(false);
const showTransferred = ref(true);
const loading = ref(false);
const showPlayerProfileModal = ref(false);

const currentTab = ref("all");
const viewType = ref("roster");

const selectedPlayer = ref(null);
const additionalYears = ref(1);
const newPlayerName = ref("");

const team_roster = ref([]);
const team_info = ref([]);
const seasons = ref([]);
const season_id = ref(0);

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(
    () => props.team_id,
    async (newId, oldId) => {
        if (newId !== oldId) {
            currentTab.value = "all";
            viewType.value = "roster";

            await seasonsDropdown(newId);
            await fetchTeamInfo(newId);
            await fetchTeamRoster(newId);
        }
    }
);

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(async () => {
    await seasonsDropdown(props.team_id);
    await fetchTeamInfo(props.team_id);
    await fetchTeamRoster(props.team_id);
});

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const filteredPlayers = computed(() => {
    return (
        team_roster.value?.players?.filter(
            (player) =>
                showTransferred.value || player.status !== 2
        ) || []
    );
});

const filteredTransferredPlayers = computed(() => {
    return (
        team_roster.value?.players?.filter(
            (player) => player.status == 2
        ) || []
    );
});

const filteredNewPlayers = computed(() => {
    return (
        team_roster.value?.players?.filter(
            (player) => player.seasons_played_with_team == 1
        ) || []
    );
});

const filteredInjuredPlayers = computed(() => {
    return (
        team_roster.value?.players?.filter(
            (player) =>
                player.is_injured == 1 &&
                player.status != 2
        ) || []
    );
});

/*
|--------------------------------------------------------------------------
| Active table data
|--------------------------------------------------------------------------
*/

const activePlayers = computed(() => {
    switch (currentTab.value) {
        case "new":
            return filteredNewPlayers.value;

        case "transferred":
            return filteredTransferredPlayers.value;

        case "injured":
            return filteredInjuredPlayers.value;

        default:
            return filteredPlayers.value;
    }
});

/*
|--------------------------------------------------------------------------
| Section labels
|--------------------------------------------------------------------------
*/

const sectionLabel = computed(() => {
    switch (currentTab.value) {
        case "new":
            return "Recent acquisitions";

        case "transferred":
            return "Roster movement";

        case "injured":
            return "Medical report";

        default:
            return "Team personnel";
    }
});

const sectionTitle = computed(() => {
    switch (currentTab.value) {
        case "new":
            return "Newly Acquired Players";

        case "transferred":
            return "Transferred Players";

        case "injured":
            return "Injured Players";

        default:
            return "All Players";
    }
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const tabClass = (tab) => {
    return [
        "flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-[11px] font-bold transition-all",
        currentTab.value === tab
            ? "bg-gray-800 text-white shadow-sm"
            : "text-gray-500 hover:bg-gray-800/60 hover:text-gray-300",
    ];
};

const number = (value) => {
    const numericValue = Number(value ?? 0);

    return Number.isFinite(numericValue)
        ? numericValue.toFixed(1)
        : "0.0";
};

const draftLabel = (player) => {
    if (player?.draft_status === "Undrafted") {
        return `S${player?.draft_id ?? "-"} Undrafted`;
    }

    const status = player?.draft_status ?? "-";
    const team = player?.drafted_team
        ? ` (${player.drafted_team})`
        : "";

    return `${status}${team}`;
};

const toggleShowTransferred = () => {
    showTransferred.value = !showTransferred.value;
};

/*
|--------------------------------------------------------------------------
| Depth Chart helpers
|--------------------------------------------------------------------------
*/

const sortByMinutes = (players) => {
    return [...players].sort(
        (a, b) =>
            Number(b.average_minutes_per_game ?? 0) -
            Number(a.average_minutes_per_game ?? 0)
    );
};

const guardDepthChart = computed(() => {
    return sortByMinutes(
        filteredPlayers.value.filter(
            (p) =>
                p.position?.includes("G") &&
                p.status !== 2
        )
    );
});

const forwardDepthChart = computed(() => {
    return sortByMinutes(
        filteredPlayers.value.filter(
            (p) =>
                p.position?.includes("F") &&
                p.status !== 2
        )
    );
});

const centerDepthChart = computed(() => {
    return sortByMinutes(
        filteredPlayers.value.filter(
            (p) =>
                p.position?.includes("C") &&
                p.status !== 2
        )
    );
});

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const fetchTeamInfo = async (id) => {
    try {
        const response = await axios.post(
            route("teams.info"),
            {
                team_id: id,
            }
        );

        team_info.value = response.data;
    } catch (error) {
        console.error(
            "Error fetching team info:",
            error
        );
    }
};

const fetchTeamRoster = async (id) => {
    loading.value = true;

    try {
        team_roster.value = [];

        const response = await axios.post(
            route("players.team.roster"),
            {
                team_id: id,
                season_id: season_id.value,
            }
        );

        team_roster.value = response.data;
    } catch (error) {
        console.error(
            "Error fetching team roster:",
            error
        );
    } finally {
        loading.value = false;
    }
};

const seasonsDropdown = async (id) => {
    try {
        const response = await axios.post(
            route("team.seasons.dropdown"),
            {
                team_id: id,
            }
        );

        seasons.value = response.data ?? [];

        season_id.value =
            seasons.value?.[0]?.season_id ?? 0;
    } catch (error) {
        console.error(
            "Error fetching seasons:",
            error
        );
    }
};

const seasonBehavior = () => {
    currentTab.value = "all";
    fetchTeamRoster(props.team_id);
};

/*
|--------------------------------------------------------------------------
| Player profile
|--------------------------------------------------------------------------
*/

const showPlayerProfile = (player) => {
    selectedPlayer.value = player;
    showPlayerProfileModal.value = true;
};

/*
|--------------------------------------------------------------------------
| Existing contract/player actions
|--------------------------------------------------------------------------
*/

const addPlayer = async () => {
    try {
        const response = await axios.post(
            route("players.add"),
            {
                name: newPlayerName.value,
                team_id: props.team_id,
            }
        );

        newPlayerName.value = "";

        await fetchTeamRoster(props.team_id);
    } catch (error) {
        console.error(
            "Error adding player:",
            error
        );

        Swal.fire({
            icon: "error",
            title: "Error!",
            text:
                error?.response?.data?.message ??
                "Unable to add player.",
        });
    }
};

const waivePlayer = async (playerId) => {
    try {
        await axios.post(
            route("players.waive"),
            {
                id: playerId,
            }
        );

        await fetchTeamRoster(props.team_id);
    } catch (error) {
        console.error(
            "Error waiving player:",
            error
        );
    }
};

const extendContract = (playerId) => {
    selectedPlayer.value =
        team_info.value?.players?.find(
            (player) => player.id === playerId
        );

    showExtendModal.value = true;
};

const confirmExtendContract = async () => {
    try {
        await axios.post(
            route("players.contract.extend"),
            {
                id: selectedPlayer.value.id,
                additional_years:
                    additionalYears.value,
            }
        );

        showExtendModal.value = false;

        await fetchTeamRoster(props.team_id);
    } catch (error) {
        console.error(
            "Error extending contract:",
            error
        );
    }
};

/*
|--------------------------------------------------------------------------
| Morale
|--------------------------------------------------------------------------
*/

const getMoraleIcon = (chemistry) => {
    const value = Number(chemistry ?? 0);

    if (value >= 80)
        return "fa-solid fa-face-laugh-beam text-yellow-400";

    if (value >= 60)
        return "fa-solid fa-face-smile text-green-400";

    if (value >= 40)
        return "fa-solid fa-face-meh text-gray-400";

    if (value >= 20)
        return "fa-solid fa-face-frown text-orange-400";

    return "fa-solid fa-face-angry text-red-500";
};

const getMoraleTitle = (chemistry) => {
    const value = Number(chemistry ?? 0);

    if (value >= 80) return "Locked In";
    if (value >= 60) return "Confident";
    if (value >= 40) return "Steady";
    if (value >= 20) return "Uncertain";

    return "Frustrated";
};
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

.roster-table {
    border-collapse: separate;
    border-spacing: 0;
}

.roster-table th {
    height: 42px;
    padding: 0.65rem 0.7rem;
    white-space: nowrap;
    text-align: left;
    font-size: 0.625rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.82);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.roster-table td {
    padding: 0.6rem 0.7rem;
    white-space: nowrap;
    border-bottom: 1px solid rgba(55, 65, 81, 0.45);
    color: #9ca3af;
    font-weight: 500;
}

.player-row {
    cursor: pointer;
    background: #030712;
    transition:
        background-color 150ms ease,
        box-shadow 150ms ease;
}

.player-row:hover {
    background: #111827;
}

.player-row:hover td {
    color: #d1d5db;
}

.injured-row {
    background: rgba(127, 29, 29, 0.16);
}

.injured-row:hover {
    background: rgba(127, 29, 29, 0.25);
}

.reserve-row {
    background: rgba(31, 41, 55, 0.35);
}

/*
|--------------------------------------------------------------------------
| Sticky columns
|--------------------------------------------------------------------------
*/

.sticky-col {
    position: sticky;
    left: 0;
    z-index: 10;
}

.first-col {
    min-width: 42px;
    width: 42px;
}

.sticky-player {
    position: sticky;
    left: 42px;
    z-index: 9;
}

.roster-table thead .sticky-col,
.roster-table thead .sticky-player {
    z-index: 20;
}

.roster-table tbody .sticky-col {
    background: #030712;
}

.roster-table tbody .sticky-player {
    background: #030712;
}

.roster-table tbody .injured-player-cell {
    background: rgba(85, 20, 20, 0.95);
}

.roster-table tbody tr:hover .sticky-col,
.roster-table tbody tr:hover .sticky-player {
    background: #111827;
}

.roster-table tbody tr.injured-row:hover .sticky-col,
.roster-table tbody tr.injured-row:hover .sticky-player {
    background: rgba(100, 25, 25, 0.95);
}

/*
|--------------------------------------------------------------------------
| Player / stat elements
|--------------------------------------------------------------------------
*/

.number-cell {
    color: #4b5563 !important;
    font-weight: 800 !important;
}

.position-pill {
    display: inline-flex;
    min-width: 30px;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(55, 65, 81, 0.6);
    padding: 0.25rem 0.45rem;
    font-size: 0.65rem;
    font-weight: 800;
    color: #d1d5db;
}

.rating {
    display: inline-flex;
    min-width: 34px;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    padding: 0.3rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 900;
}

.rating-ovr {
    background: rgba(255, 255, 255, 0.08);
    color: #f3f4f6;
}

.stat-highlight {
    font-weight: 800 !important;
    color: #e5e7eb !important;
}

.achievement {
    display: inline-flex;
    height: 23px;
    min-width: 23px;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    padding: 0 0.4rem;
    font-size: 0.6rem;
    font-weight: 900;
}

.achievement.gold {
    background: rgba(234, 179, 8, 0.14);
    color: #facc15;
    border: 1px solid rgba(234, 179, 8, 0.18);
}

.achievement.silver {
    background: rgba(156, 163, 175, 0.12);
    color: #d1d5db;
    border: 1px solid rgba(156, 163, 175, 0.16);
}

.achievement.blue {
    background: rgba(59, 130, 246, 0.14);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.18);
}

/*
|--------------------------------------------------------------------------
| Legend
|--------------------------------------------------------------------------
*/

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border-radius: 7px;
    background: rgba(31, 41, 55, 0.65);
    padding: 0.3rem 0.5rem;
    font-size: 0.625rem;
    font-weight: 700;
    color: #9ca3af;
}

/*
|--------------------------------------------------------------------------
| Scrollbars
|--------------------------------------------------------------------------
*/

::-webkit-scrollbar {
    width: 7px;
    height: 7px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 999px;
}

::-webkit-scrollbar-thumb:hover {
    background: #4b5563;
}
</style>
```
