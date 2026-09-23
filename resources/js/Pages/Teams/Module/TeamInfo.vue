```vue
<template>
    <!-- =========================================================
         TEAM PROFILE
    ========================================================== -->
    <div
        v-if="team_info?.teams && !loading"
        class="min-h-screen text-white overflow-hidden"
        :style="pageStyle"
    >
        <!-- =====================================================
             HERO / TEAM HEADER
        ====================================================== -->
        <section
            class="relative overflow-hidden border-b border-white/10"
            :style="heroStyle"
        >
            <!-- Decorative background -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div
                    class="absolute -right-24 -top-32 w-80 h-80 rounded-full blur-3xl opacity-20"
                    :style="{ backgroundColor: primaryColor }"
                ></div>

                <div
                    class="absolute -left-32 -bottom-40 w-96 h-96 rounded-full blur-3xl opacity-10"
                    :style="{ backgroundColor: primaryColor }"
                ></div>

                <div
                    class="absolute right-0 bottom-0 w-1/2 h-px opacity-40"
                    :style="{ backgroundColor: primaryColor }"
                ></div>
            </div>

            <div class="relative px-4 sm:px-6 lg:px-8 py-5">
                <!-- Top row -->
                <div
                    class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5"
                >
                    <!-- Team identity -->
                    <div class="flex items-start gap-4 min-w-0">
                        <!-- Team color emblem -->
                        <div
                            class="relative flex-shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-2xl border border-white/20 shadow-xl overflow-hidden"
                            :style="teamLogoStyle"
                        >
                            <div
                                class="absolute inset-0 opacity-30"
                                :style="{
                                    backgroundColor: primaryColor
                                }"
                            ></div>

                            <div
                                class="relative h-full flex items-center justify-center text-white font-black text-lg sm:text-xl"
                            >
                                {{
                                    team_info.teams.acronym ?? "TEAM"
                                }}
                            </div>
                        </div>

                        <!-- Name -->
                        <div class="min-w-0">
                            <div
                                class="flex flex-wrap items-center gap-2 mb-1"
                            >
                                <span
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-widest border border-white/10 bg-white/5"
                                >
                                    Team Profile
                                </span>

                                <span
                                    v-if="team_info.teams.streak_status"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide"
                                    :class="
                                        isWinningStreak
                                            ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-400/20'
                                            : 'bg-red-500/15 text-red-400 border border-red-400/20'
                                    "
                                >
                                    <i
                                        class="fas fa-fire mr-1"
                                    ></i>
                                    {{ team_info.teams.streak_status }}
                                </span>
                            </div>

                            <h1
                                class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight truncate"
                            >
                                {{ team_info.teams.city ?? "-" }}
                                {{ team_info.teams.team_name ?? "-" }}
                            </h1>

                            <div
                                class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white/55"
                            >
                                <span class="font-semibold text-white/80">
                                    {{
                                        team_info.teams.acronym ?? "-"
                                    }}
                                </span>

                                <span class="text-white/20">•</span>

                                <span>
                                    Coach:
                                    <span class="text-white/80">
                                        {{
                                            team_info.teams.coach_name ??
                                            "-"
                                        }}
                                    </span>
                                </span>

                                <span class="text-white/20">•</span>

                                <span>
                                    {{
                                        team_info.teams.coach_winning ??
                                        0
                                    }}%
                                    win rate
                                </span>

                                <span class="text-white/20">•</span>

                                <span>
                                    {{
                                        team_info.chemistry
                                            ?.coach_experience ?? 0
                                    }}
                                    yrs experience
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sponsor -->
                    <div
                        v-if="team_info.teams.sponsor"
                        class="xl:text-right"
                    >
                        <div
                            class="text-[10px] uppercase tracking-[0.25em] text-white/35 mb-1"
                        >
                            Official Sponsor
                        </div>

                        <div
                            class="text-xl sm:text-2xl lg:text-3xl font-black truncate max-w-full xl:max-w-[350px]"
                            :style="{ color: primaryColor }"
                        >
                            {{ team_info.teams.sponsor }}
                        </div>
                    </div>
                </div>

                <!-- Key metrics -->
                <div
                    class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3"
                >
                    <div
                        v-for="metric in headerMetrics"
                        :key="metric.label"
                        class="rounded-xl border border-white/10 bg-black/20 backdrop-blur-sm px-3 py-3"
                    >
                        <div
                            class="flex items-center gap-2 text-[10px] uppercase tracking-wider text-white/40 font-bold"
                        >
                            <i
                                :class="metric.icon"
                                :style="{ color: metric.color || primaryColor }"
                            ></i>

                            {{ metric.label }}
                        </div>

                        <div
                            class="mt-1 text-lg font-black text-white truncate"
                        >
                            {{ metric.value }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =====================================================
             MAIN CONTENT
        ====================================================== -->
        <main class="px-3 sm:px-5 lg:px-8 py-5 space-y-5">
            <!-- =================================================
                 CAREER / HISTORY STATISTICS
            ================================================== -->
            <section>
                <SectionHeader
                    eyebrow="FRANCHISE"
                    title="Historical Record"
                    icon="fas fa-landmark"
                />

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
                >
                    <!-- All time -->
                    <StatCard
                        title="All-Time"
                        icon="fas fa-chart-line"
                        :accent="primaryColor"
                    >
                        <div
                            class="flex items-end justify-between gap-3"
                        >
                            <div>
                                <div
                                    class="text-3xl font-black"
                                >
                                    {{ allTimeWins }}
                                </div>
                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Wins
                                </div>
                            </div>

                            <div class="text-right">
                                <div
                                    class="text-xl font-bold text-white/60"
                                >
                                    {{ allTimeLosses }}
                                </div>
                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Losses
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t border-white/10 flex justify-between text-xs"
                        >
                            <span class="text-white/40">
                                {{ allTimeGames }} games
                            </span>

                            <span
                                class="font-bold"
                                :style="{ color: primaryColor }"
                            >
                                {{ allTimeWinRate }}%
                            </span>
                        </div>
                    </StatCard>

                    <!-- Finals -->
                    <StatCard
                        title="Finals"
                        icon="fas fa-trophy"
                        :accent="primaryColor"
                    >
                        <div
                            class="flex items-end justify-between"
                        >
                            <div>
                                <div
                                    class="text-3xl font-black"
                                >
                                    {{ finalsWins }}
                                </div>
                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Wins
                                </div>
                            </div>

                            <div class="text-right">
                                <div
                                    class="text-xl font-bold text-white/60"
                                >
                                    {{ finalsLosses }}
                                </div>
                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Losses
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t border-white/10"
                        >
                            <span class="text-xs text-white/40">
                                Appearances
                            </span>

                            <span
                                class="float-right font-bold text-white"
                            >
                                {{ finalsAppearances }}
                            </span>
                        </div>
                    </StatCard>

                    <!-- Playoffs -->
                    <StatCard
                        title="Playoffs"
                        icon="fas fa-basketball-ball"
                        :accent="primaryColor"
                    >
                        <div
                            class="flex items-end justify-between"
                        >
                            <div>
                                <div
                                    class="text-3xl font-black"
                                >
                                    {{ playoffWins }}
                                </div>
                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Wins
                                </div>
                            </div>

                            <div class="text-right">
                                <div
                                    class="text-xl font-bold text-white/60"
                                >
                                    {{ playoffLosses }}
                                </div>
                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Losses
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t border-white/10"
                        >
                            <span class="text-xs text-white/40">
                                Appearances
                            </span>

                            <span
                                class="float-right font-bold text-white"
                            >
                                {{ playoffAppearances }}
                            </span>
                        </div>
                    </StatCard>

                    <!-- Streak -->
                    <StatCard
                        title="Best Streaks"
                        icon="fas fa-fire"
                        :accent="primaryColor"
                    >
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div
                                    class="text-3xl font-black text-emerald-400"
                                >
                                    {{ bestWinningStreak }}
                                </div>

                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Win Streak
                                </div>
                            </div>

                            <div>
                                <div
                                    class="text-3xl font-black text-red-400"
                                >
                                    {{ bestLosingStreak }}
                                </div>

                                <div
                                    class="text-[10px] uppercase tracking-wider text-white/35"
                                >
                                    Loss Streak
                                </div>
                            </div>
                        </div>
                    </StatCard>
                </div>
            </section>

            <!-- =================================================
                 PLAYOFF HISTORY
            ================================================== -->
            <section v-if="team_last_season">
                <SectionHeader
                    eyebrow="POSTSEASON"
                    title="Playoff History"
                    icon="fas fa-route"
                />

                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-2"
                >
                    <HistoryCard
                        label="Play-In R1"
                        :value="
                            team_last_season.lastPlayInsRound1Season
                        "
                        :primary="false"
                        :primary-color="primaryColor"
                    />

                    <HistoryCard
                        label="Play-In R2"
                        :value="
                            team_last_season.lastPlayInsRound2Season
                        "
                        :primary="true"
                        :primary-color="primaryColor"
                    />

                    <HistoryCard
                        label="Play-In Final"
                        :value="
                            team_last_season.lastPlayInsFinalsSeason
                        "
                        :primary="false"
                        :primary-color="primaryColor"
                    />

                    <HistoryCard
                        label="Round of 16"
                        :value="
                            team_last_season.lastRoundOf16Season
                        "
                        :primary="true"
                        :primary-color="primaryColor"
                    />

                    <HistoryCard
                        label="Quarter Final"
                        :value="
                            team_last_season.lastQuarterFinalSeason
                        "
                        :primary="false"
                        :primary-color="primaryColor"
                    />

                    <HistoryCard
                        label="Semi Final"
                        :value="
                            team_last_season.lastSemiFinalSeason
                        "
                        :primary="true"
                        :primary-color="primaryColor"
                    />

                    <HistoryCard
                        label="Final"
                        :value="
                            team_last_season.lastFinalSeason
                        "
                        :primary="false"
                        :primary-color="primaryColor"
                    />
                </div>
            </section>

            <!-- =================================================
                 SEASON ACHIEVEMENTS
            ================================================== -->
            <section>
                <SectionHeader
                    eyebrow="LEGACY"
                    title="Season Achievements"
                    icon="fas fa-medal"
                />

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
                >
                    <AchievementCard
                        title="Champions"
                        icon="fas fa-trophy"
                        :items="
                            team_season_finals
                                ?.finalsWinSeasons
                        "
                        :accent="primaryColor"
                    />

                    <AchievementCard
                        title="Finals"
                        icon="fas fa-flag-checkered"
                        :items="
                            team_season_finals
                                ?.finalsSeasons
                        "
                        :accent="primaryColor"
                    />

                    <AchievementCard
                        title="Top Seasons"
                        icon="fas fa-arrow-up"
                        :items="
                            team_season_standings
                                ?.topStandingsSeasons
                        "
                        :accent="primaryColor"
                    />

                    <AchievementCard
                        title="Playoffs"
                        icon="fas fa-basketball-ball"
                        :items="
                            team_season_standings
                                ?.playOffAppearance
                        "
                        :accent="primaryColor"
                    />

                    <AchievementCard
                        title="Worst Seasons"
                        icon="fas fa-arrow-down"
                        :items="
                            team_season_standings
                                ?.bottomStandingsSeasons
                        "
                        :accent="'#ef4444'"
                        danger
                    />
                </div>
            </section>

            <!-- =================================================
                 RIVALS
            ================================================== -->
            <section>
                <SectionHeader
                    eyebrow="RIVALRIES"
                    title="Top Rivals"
                    icon="fas fa-bolt"
                />

                <div
                    v-if="
                        team_rivals?.top_rivals?.length
                    "
                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3"
                >
                    <div
                        v-for="(team, index) in team_rivals.top_rivals"
                        :key="index"
                        class="group rounded-2xl border border-white/10 bg-white/[0.035] hover:bg-white/[0.06] transition-all duration-200 overflow-hidden"
                    >
                        <div class="p-4">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <div
                                        class="text-[10px] uppercase tracking-widest text-white/30"
                                    >
                                        Rival
                                    </div>

                                    <h4
                                        class="mt-1 font-bold truncate"
                                    >
                                        {{
                                            team.opponent_name ??
                                            "-"
                                        }}
                                    </h4>
                                </div>

                                <div
                                    class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center bg-white/5 text-white/50"
                                >
                                    <i
                                        class="fas fa-bolt"
                                    ></i>
                                </div>
                            </div>

                            <div
                                class="mt-4 grid grid-cols-2 gap-2"
                            >
                                <div
                                    class="rounded-xl bg-emerald-500/10 border border-emerald-500/10 p-3"
                                >
                                    <div
                                        class="text-[9px] uppercase tracking-wider text-emerald-400/60"
                                    >
                                        Wins
                                    </div>

                                    <div
                                        class="mt-1 text-xl font-black text-emerald-400"
                                    >
                                        {{ team.wins ?? 0 }}
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl bg-red-500/10 border border-red-500/10 p-3"
                                >
                                    <div
                                        class="text-[9px] uppercase tracking-wider text-red-400/60"
                                    >
                                        Losses
                                    </div>

                                    <div
                                        class="mt-1 text-xl font-black text-red-400"
                                    >
                                        {{ team.losses ?? 0 }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-3 text-[10px] text-white/30 text-center"
                            >
                                {{
                                    rivalRecord(team)
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    message="No rivalry information available"
                />
            </section>

            <!-- =================================================
                 RECENT GAMES
            ================================================== -->
            <section>
                <SectionHeader
                    eyebrow="FORM"
                    :title="`Last Ten Games`"
                    icon="fas fa-history"
                >
                    <template #right>
                        <div
                            v-if="
                                team_matches?.lastTenGames
                                    ?.length
                            "
                            class="px-3 py-1 rounded-full text-xs font-black bg-white/5 border border-white/10"
                        >
                            {{
                                calculateRecord(
                                    team_matches.lastTenGames
                                )
                            }}
                        </div>
                    </template>
                </SectionHeader>

                <div
                    v-if="
                        team_matches?.lastTenGames?.length
                    "
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3"
                >
                    <div
                        v-for="game in team_matches.lastTenGames"
                        :key="game.id"
                        class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.035] hover:bg-white/[0.055] transition-all"
                    >
                        <!-- Result indicator -->
                        <div
                            class="absolute top-0 left-0 w-1 h-full"
                            :class="
                                game.status === 'Win'
                                    ? 'bg-emerald-400'
                                    : 'bg-red-400'
                            "
                        ></div>

                        <div class="p-4 pl-5">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="text-[9px] uppercase tracking-widest text-white/30"
                                >
                                    {{
                                        roundNameFormatter(
                                            game.round
                                        )
                                    }}
                                </span>

                                <span
                                    class="text-[10px] font-black uppercase"
                                    :class="
                                        game.status === 'Win'
                                            ? 'text-emerald-400'
                                            : 'text-red-400'
                                    "
                                >
                                    {{ game.status }}
                                </span>
                            </div>

                            <div
                                class="mt-4 space-y-2"
                            >
                                <!-- Home -->
                                <div
                                    class="flex items-center justify-between gap-3"
                                    :class="{
                                        'text-white font-bold':
                                            game.home_score >
                                            game.away_score,
                                        'text-white/45':
                                            game.home_score <
                                            game.away_score
                                    }"
                                >
                                    <span
                                        class="truncate text-sm"
                                    >
                                        {{
                                            game.home_team_name
                                        }}
                                    </span>

                                    <span
                                        class="text-lg font-black tabular-nums"
                                    >
                                        {{
                                            game.home_score
                                        }}
                                    </span>
                                </div>

                                <!-- Away -->
                                <div
                                    class="flex items-center justify-between gap-3"
                                    :class="{
                                        'text-white font-bold':
                                            game.away_score >
                                            game.home_score,
                                        'text-white/45':
                                            game.away_score <
                                            game.home_score
                                    }"
                                >
                                    <span
                                        class="truncate text-sm"
                                    >
                                        {{
                                            game.away_team_name
                                        }}
                                    </span>

                                    <span
                                        class="text-lg font-black tabular-nums"
                                    >
                                        {{
                                            game.away_score
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    message="No recent matches available"
                />
            </section>

            <!-- =================================================
                 HEAD TO HEAD
            ================================================== -->
            <section>
                <SectionHeader
                    eyebrow="HISTORY"
                    title="Head-to-Head Battles"
                    icon="fas fa-crosshairs"
                />

                <div
                    v-if="
                        team_head_2_head?.headToHeadBattles
                            ?.length
                    "
                    class="rounded-2xl border border-white/10 bg-white/[0.035] overflow-hidden"
                >
                    <!-- Search -->
                    <div
                        class="p-3 sm:p-4 border-b border-white/10"
                    >
                        <div class="relative max-w-md">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-white/25 text-sm"
                            ></i>

                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search head-to-head history..."
                                class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-black/20 border border-white/10 text-sm text-white placeholder-white/25 focus:outline-none focus:border-white/20 focus:ring-1 focus:ring-white/10"
                            />
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-white/10 bg-black/10"
                                >
                                    <th
                                        v-for="(
                                            key, index
                                        ) in h2hKeys"
                                        :key="index"
                                        class="px-4 sm:px-5 py-3 text-left text-[10px] uppercase tracking-wider font-bold text-white/35 whitespace-nowrap"
                                    >
                                        {{
                                            key.replaceAll(
                                                "_",
                                                " "
                                            )
                                        }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-white/5"
                            >
                                <tr
                                    v-for="(
                                        battle, index
                                    ) in paginatedBattles"
                                    :key="index"
                                    class="hover:bg-white/[0.025] transition-colors"
                                >
                                    <td
                                        v-for="(
                                            value, key
                                        ) in battle"
                                        :key="key"
                                        class="px-4 sm:px-5 py-3 whitespace-nowrap text-white/70"
                                    >
                                        {{ value ?? "-" }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="flex items-center justify-between gap-3 p-3 border-t border-white/10"
                    >
                        <span
                            class="text-xs text-white/30"
                        >
                            Page {{ currentPage }} of
                            {{ totalPages }}
                        </span>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white/60 hover:bg-white/10 disabled:opacity-30"
                                :disabled="
                                    currentPage === 1
                                "
                                @click="currentPage--"
                            >
                                <i
                                    class="fas fa-chevron-left mr-1"
                                ></i>
                                Previous
                            </button>

                            <button
                                type="button"
                                class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white/60 hover:bg-white/10 disabled:opacity-30"
                                :disabled="
                                    currentPage === totalPages
                                "
                                @click="currentPage++"
                            >
                                Next
                                <i
                                    class="fas fa-chevron-right ml-1"
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else
                    message="No head-to-head battle information available"
                />
            </section>
        </main>
    </div>

    <!-- =========================================================
         LOADING
    ========================================================== -->
    <div
        v-else
        class="min-h-screen bg-[#09090b] text-white p-3 sm:p-5 lg:p-8 animate-pulse"
    >
        <!-- Header skeleton -->
        <div
            class="rounded-2xl border border-white/10 bg-white/[0.035] p-5"
        >
            <div class="flex gap-4 items-center">
                <div
                    class="w-16 h-16 rounded-2xl bg-white/10"
                ></div>

                <div class="flex-1 space-y-3">
                    <div
                        class="h-3 w-24 bg-white/10 rounded"
                    ></div>

                    <div
                        class="h-7 w-64 max-w-full bg-white/10 rounded"
                    ></div>

                    <div
                        class="h-3 w-80 max-w-full bg-white/10 rounded"
                    ></div>
                </div>

                <div
                    class="hidden lg:block w-40 space-y-2"
                >
                    <div
                        class="h-2 w-20 ml-auto bg-white/10 rounded"
                    ></div>

                    <div
                        class="h-6 w-40 ml-auto bg-white/10 rounded"
                    ></div>
                </div>
            </div>

            <div
                class="mt-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="h-20 rounded-xl bg-white/5 border border-white/5"
                ></div>
            </div>
        </div>

        <!-- Content skeleton -->
        <div class="mt-5 space-y-5">
            <div
                v-for="section in 4"
                :key="section"
                class="space-y-3"
            >
                <div
                    class="h-4 w-40 bg-white/10 rounded"
                ></div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
                >
                    <div
                        v-for="card in 4"
                        :key="card"
                        class="h-36 rounded-2xl bg-white/[0.035] border border-white/5"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from "axios";
import { roundNameFormatter, numberFormatter } from "@/Utility/Formatter";

const props = defineProps({
    team_id: {
        type: Number,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(false);

const team_info = ref(false);
const team_season_standings = ref(false);
const team_season_finals = ref(false);
const team_last_season = ref(false);
const team_matches = ref(false);
const team_rivals = ref(false);
const team_head_2_head = ref(false);

const searchQuery = ref("");
const currentPage = ref(1);

const pageSize = 10;

/*
|--------------------------------------------------------------------------
| Colors
|--------------------------------------------------------------------------
*/

const primaryColor = computed(() => {
    const color =
        team_info.value?.teams?.primary_color;

    return color ? `#${color.replace("#", "")}` : "#ffffff";
});

const secondaryColor = computed(() => {
    const color =
        team_info.value?.teams?.secondary_color;

    return color ? `#${color.replace("#", "")}` : "#09090b";
});

const pageStyle = computed(() => ({
    background: `
        radial-gradient(
            circle at 10% 0%,
            ${primaryColor.value}18 0%,
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #09090b 0%,
            ${secondaryColor.value}55 45%,
            #09090b 100%
        )
    `,
}));

const heroStyle = computed(() => ({
    background: `
        linear-gradient(
            135deg,
            ${secondaryColor.value} 0%,
            ${secondaryColor.value}cc 50%,
            #09090b 100%
        )
    `,
}));

const teamLogoStyle = computed(() => ({
    background: `linear-gradient(
        135deg,
        ${primaryColor.value} 0%,
        ${primaryColor.value}99 48%,
        ${secondaryColor.value} 49%,
        ${secondaryColor.value} 100%
    )`,
}));

/*
|--------------------------------------------------------------------------
| Header Metrics
|--------------------------------------------------------------------------
*/

const headerMetrics = computed(() => [
    {
        label: "Market",
        value:
            team_info.value?.teams?.market_size ??
            "-",
        icon: "fas fa-building",
    },
    {
        label: "Fans",
        value: numberFormatter(
            team_info.value?.teams?.estimated_fans ?? 0
        ),
        icon: "fas fa-users",
    },
    {
        label: "Reputation",
        value:
            team_info.value?.teams?.reputation_score !=
            null
                ? Number(
                      team_info.value.teams
                          .reputation_score
                  ).toFixed(2)
                : "-",
        icon: "fas fa-chart-line",
    },
    {
        label: "Streak",
        value:
            team_info.value?.teams?.streak_status ??
            "-",
        icon: "fas fa-fire",
    },
    {
        label: "Chemistry",
        value:
            team_info.value?.teams?.chemistry ??
            "-",
        icon: "fas fa-flask",
    },
    {
        label: "Avg Age",
        value:
            team_info.value?.chemistry?.average_age ??
            "-",
        icon: "fas fa-user-clock",
    },
]);

/*
|--------------------------------------------------------------------------
| All-Time Stats
|--------------------------------------------------------------------------
*/

const allTimeWins = computed(
    () =>
        Number(
            team_info.value?.allTimeStats
                ?.all_time_wins ?? 0
        )
);

const allTimeLosses = computed(
    () =>
        Number(
            team_info.value?.allTimeStats
                ?.all_time_losses ?? 0
        )
);

const allTimeGames = computed(
    () =>
        allTimeWins.value +
        allTimeLosses.value
);

const allTimeWinRate = computed(() => {
    if (!allTimeGames.value) return "0.00";

    return (
        (allTimeWins.value /
            allTimeGames.value) *
        100
    ).toFixed(2);
});

/*
|--------------------------------------------------------------------------
| Finals
|--------------------------------------------------------------------------
*/

const finalsWins = computed(
    () =>
        Number(
            team_info.value?.finalsStats
                ?.finals_wins ?? 0
        )
);

const finalsLosses = computed(
    () =>
        Number(
            team_info.value?.finalsStats
                ?.finals_losses ?? 0
        )
);

const finalsAppearances = computed(
    () =>
        Number(
            team_info.value?.finalsStats
                ?.finals_appearances ?? 0
        )
);

/*
|--------------------------------------------------------------------------
| Playoffs
|--------------------------------------------------------------------------
*/

const playoffWins = computed(
    () =>
        Number(
            team_info.value?.playoffStats
                ?.playoff_wins ?? 0
        )
);

const playoffLosses = computed(
    () =>
        Number(
            team_info.value?.playoffStats
                ?.playoff_losses ?? 0
        )
);

const playoffAppearances = computed(
    () =>
        Number(
            team_info.value?.playoffStats
                ?.playoff_appearances ?? 0
        ) +
        Number(
            team_info.value?.playoffStats
                ?.play_in_appearances ?? 0
        )
);

/*
|--------------------------------------------------------------------------
| Streaks
|--------------------------------------------------------------------------
*/

const bestWinningStreak = computed(
    () =>
        Number(
            team_info.value?.streaks?.[0]
                ?.best_winning_streak ?? 0
        )
);

const bestLosingStreak = computed(
    () =>
        Number(
            team_info.value?.streaks?.[0]
                ?.best_losing_streak ?? 0
        )
);

const isWinningStreak = computed(() =>
    String(
        team_info.value?.teams?.streak_status ??
            ""
    )
        .toLowerCase()
        .startsWith("w")
);

/*
|--------------------------------------------------------------------------
| Head To Head
|--------------------------------------------------------------------------
*/

const filteredBattles = computed(() => {
    const battles =
        team_head_2_head.value
            ?.headToHeadBattles ?? [];

    const query =
        searchQuery.value
            .trim()
            .toLowerCase();

    if (!query) return battles;

    return battles.filter((battle) =>
        Object.values(battle).some((value) =>
            String(value ?? "")
                .toLowerCase()
                .includes(query)
        )
    );
});

const h2hKeys = computed(() => {
    const battle =
        team_head_2_head.value
            ?.headToHeadBattles?.[0];

    return battle
        ? Object.keys(battle)
        : [];
});

const totalPages = computed(() =>
    Math.max(
        1,
        Math.ceil(
            filteredBattles.value.length /
                pageSize
        )
    )
);

const paginatedBattles = computed(() => {
    const start =
        (currentPage.value - 1) *
        pageSize;

    return filteredBattles.value.slice(
        start,
        start + pageSize
    );
});

watch(searchQuery, () => {
    currentPage.value = 1;
});

watch(totalPages, (pages) => {
    if (currentPage.value > pages) {
        currentPage.value = pages;
    }
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const calculateRecord = (games) => {
    let wins = 0;
    let losses = 0;

    games.forEach((game) => {
        if (game.status === "Win") wins++;
        if (game.status === "Loss") losses++;
    });

    return `${wins}-${losses}`;
};

const rivalRecord = (team) => {
    const wins = Number(team.wins ?? 0);
    const losses = Number(team.losses ?? 0);

    return `${wins + losses} total games`;
};

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

watch(
    () => props.team_id,
    async (newId, oldId) => {
        if (newId !== oldId) {
            await fetchDataForTeam(newId);
        }
    }
);

onMounted(() => {
    fetchDataForTeam(props.team_id);
});

const fetchDataForTeam = async (id) => {
    loading.value = true;

    // Reset stale data immediately when changing teams
    team_info.value = false;
    team_season_standings.value = false;
    team_season_finals.value = false;
    team_last_season.value = false;
    team_matches.value = false;
    team_rivals.value = false;
    team_head_2_head.value = false;

    searchQuery.value = "";
    currentPage.value = 1;

    try {
        /*
         * These requests are independent, so run them
         * simultaneously instead of waiting one by one.
         */
        await Promise.all([
            fetchTeamInfo(id),
            fetchTeamLastSeason(id),
            fetchTeamMatchesHead2Head(id),
            fetchTeamMatches(id),
            fetchTeamRivals(id),
            fetchTeamSeasonStandings(id),
            fetchTeamSeasonFinals(id),
        ]);
    } catch (error) {
        console.error(
            "Error fetching data for team:",
            error
        );
    } finally {
        loading.value = false;
    }
};

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

const fetchTeamRivals = async (id) => {
    try {
        const response = await axios.post(
            route("teams.rivals"),
            {
                team_id: id,
            }
        );

        team_rivals.value = response.data;
    } catch (error) {
        console.error(
            "Error fetching team rivals:",
            error
        );
    }
};

const fetchTeamSeasonStandings = async (id) => {
    try {
        const response = await axios.post(
            route("teams.season.standings"),
            {
                team_id: id,
            }
        );

        team_season_standings.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching team standings:",
            error
        );
    }
};

const fetchTeamSeasonFinals = async (id) => {
    try {
        const response = await axios.post(
            route("teams.season.finals"),
            {
                team_id: id,
            }
        );

        team_season_finals.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching team finals:",
            error
        );
    }
};

const fetchTeamLastSeason = async (id) => {
    try {
        const response = await axios.post(
            route("teams.last.season"),
            {
                team_id: id,
            }
        );

        team_last_season.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching last season:",
            error
        );
    }
};

const fetchTeamMatches = async (id) => {
    try {
        const response = await axios.post(
            route("teams.matches"),
            {
                team_id: id,
            }
        );

        team_matches.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching team matches:",
            error
        );
    }
};

const fetchTeamMatchesHead2Head = async (
    id
) => {
    try {
        const response = await axios.post(
            route("teams.matches.h2h"),
            {
                team_id: id,
            }
        );

        team_head_2_head.value =
            response.data;
    } catch (error) {
        console.error(
            "Error fetching H2H:",
            error
        );
    }
};
</script>

<script>
/*
|--------------------------------------------------------------------------
| Local presentation components
|--------------------------------------------------------------------------
|
| Keeping these components here means you don't have to create
| additional Vue component files.
|--------------------------------------------------------------------------
*/

export default {
    components: {
        SectionHeader: {
            props: {
                eyebrow: String,
                title: String,
                icon: String,
            },

            template: `
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="flex-shrink-0 w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white/50"
                        >
                            <i :class="icon"></i>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="text-[9px] font-black tracking-[0.2em] text-white/25 uppercase"
                            >
                                {{ eyebrow }}
                            </div>

                            <h2
                                class="text-lg sm:text-xl font-black truncate"
                            >
                                {{ title }}
                            </h2>
                        </div>
                    </div>

                    <slot name="right"></slot>
                </div>
            `,
        },

        StatCard: {
            props: {
                title: String,
                icon: String,
                accent: String,
            },

            template: `
                <div
                    class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.035] p-4"
                >
                    <div
                        class="absolute top-0 left-0 right-0 h-px opacity-50"
                        :style="{ backgroundColor: accent }"
                    ></div>

                    <div class="flex items-center gap-2 mb-4">
                        <i
                            :class="icon"
                            class="text-xs"
                            :style="{ color: accent }"
                        ></i>

                        <span
                            class="text-[10px] uppercase tracking-widest font-bold text-white/40"
                        >
                            {{ title }}
                        </span>
                    </div>

                    <slot></slot>
                </div>
            `,
        },

        HistoryCard: {
            props: {
                label: String,
                value: [String, Number],
                primary: Boolean,
                primaryColor: String,
            },

            template: `
                <div
                    class="rounded-xl border p-3 min-w-0"
                    :class="
                        primary
                            ? 'border-white/5'
                            : 'border-white/10'
                    "
                    :style="
                        primary
                            ? {
                                  backgroundColor:
                                      primaryColor + '18',
                              }
                            : {
                                  backgroundColor:
                                      'rgba(255,255,255,0.025)',
                              }
                    "
                >
                    <div
                        class="text-[9px] uppercase tracking-wider font-bold text-white/35 truncate"
                    >
                        {{ label }}
                    </div>

                    <div
                        class="mt-2 text-base font-black truncate"
                        :style="
                            primary
                                ? {
                                      color: primaryColor,
                                  }
                                : {}
                        "
                    >
                        {{ value || 'n/a' }}
                    </div>
                </div>
            `,
        },

        AchievementCard: {
            props: {
                title: String,
                icon: String,
                items: {
                    type: Array,
                    default: () => [],
                },
                accent: String,
                danger: Boolean,
            },

            template: `
                <div
                    class="rounded-2xl border border-white/10 bg-white/[0.035] p-4 min-h-[150px]"
                >
                    <div
                        class="flex items-center gap-2 mb-4"
                    >
                        <i
                            :class="icon"
                            class="text-xs"
                            :style="{ color: accent }"
                        ></i>

                        <span
                            class="text-[10px] uppercase tracking-wider font-bold text-white/45"
                        >
                            {{ title }}
                        </span>
                    </div>

                    <div
                        v-if="items?.length"
                        class="flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="(season, index) in items"
                            :key="index"
                            class="px-2 py-1 rounded-lg text-[10px] font-bold border"
                            :style="{
                                color: accent,
                                borderColor: accent + '30',
                                backgroundColor: accent + '12'
                            }"
                        >
                            {{ season }}
                        </span>
                    </div>

                    <div
                        v-else
                        class="h-20 flex items-center justify-center text-center text-xs text-white/20"
                    >
                        No seasons recorded
                    </div>
                </div>
            `,
        },

        EmptyState: {
            props: {
                message: String,
            },

            template: `
                <div
                    class="rounded-2xl border border-dashed border-white/10 bg-white/[0.02] py-10 px-5 text-center"
                >
                    <div
                        class="w-10 h-10 mx-auto rounded-xl bg-white/5 flex items-center justify-center text-white/20"
                    >
                        <i class="fas fa-inbox"></i>
                    </div>

                    <p class="mt-3 text-sm text-white/30">
                        {{ message }}
                    </p>
                </div>
            `,
        },
    },
};
</script>

<style scoped>
.tabular-nums {
    font-variant-numeric: tabular-nums;
}

/* Smooth scrollbar for horizontal tables */
.overflow-x-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 999px;
}

/* Prevent long team names from destroying the layout */
.truncate {
    min-width: 0;
}
</style>
