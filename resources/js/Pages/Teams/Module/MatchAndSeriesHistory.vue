<template>
  <div class="mt-3 w-full">
    <!-- ========================================================= -->
    <!-- TAB NAVIGATION -->
    <!-- ========================================================= -->
    <div
      class="
        rounded-2xl
        border border-slate-800
        bg-slate-950
        shadow-xl
        overflow-hidden
      "
    >
      <div
        class="
          flex
          flex-col
          sm:flex-row
          sm:items-center
          gap-1
          p-2
          bg-slate-900/80
          border-b border-slate-800
        "
      >
        <!-- Recent Games -->
        <button
          type="button"
          class="
            relative
            flex-1
            sm:flex-none
            px-4
            py-2.5
            rounded-lg
            text-xs
            sm:text-sm
            font-semibold
            transition-all
            duration-200
            focus:outline-none
          "
          :class="
            activeTab === 'games'
              ? 'bg-slate-800 text-white shadow-sm'
              : 'text-slate-500 hover:text-slate-300 hover:bg-slate-800/50'
          "
          @click="activeTab = 'games'"
        >
          <span class="flex items-center justify-center gap-2">
            <i class="fas fa-basketball-ball text-[11px]"></i>
            Recent Games

            <span
              v-if="matches.length"
              class="
                px-1.5
                py-0.5
                rounded
                bg-slate-700
                text-[9px]
                text-slate-300
              "
            >
              {{ matches.length }}
            </span>
          </span>

          <span
            v-if="activeTab === 'games'"
            class="
              absolute
              bottom-0
              left-4
              right-4
              h-0.5
              rounded-full
              bg-indigo-500
            "
          ></span>
        </button>

        <!-- Series -->
        <button
          type="button"
          class="
            relative
            flex-1
            sm:flex-none
            px-4
            py-2.5
            rounded-lg
            text-xs
            sm:text-sm
            font-semibold
            transition-all
            duration-200
            focus:outline-none
          "
          :class="
            activeTab === 'series'
              ? 'bg-slate-800 text-white shadow-sm'
              : 'text-slate-500 hover:text-slate-300 hover:bg-slate-800/50'
          "
          @click="activeTab = 'series'"
        >
          <span class="flex items-center justify-center gap-2">
            <i class="fas fa-trophy text-[11px]"></i>
            Series History

            <span
              class="
                px-1.5
                py-0.5
                rounded
                bg-slate-700
                text-[9px]
                text-slate-300
              "
            >
              {{ series.length }}
            </span>
          </span>

          <span
            v-if="activeTab === 'series'"
            class="
              absolute
              bottom-0
              left-4
              right-4
              h-0.5
              rounded-full
              bg-indigo-500
            "
          ></span>
        </button>

        <!-- Trivia -->
        <button
          type="button"
          class="
            relative
            flex-1
            sm:flex-none
            px-4
            py-2.5
            rounded-lg
            text-xs
            sm:text-sm
            font-semibold
            transition-all
            duration-200
            focus:outline-none
          "
          :class="
            activeTab === 'trivia'
              ? 'bg-slate-800 text-white shadow-sm'
              : 'text-slate-500 hover:text-slate-300 hover:bg-slate-800/50'
          "
          @click="activeTab = 'trivia'"
        >
          <span class="flex items-center justify-center gap-2">
            <i class="fas fa-lightbulb text-[11px]"></i>
            Trivia
          </span>

          <span
            v-if="activeTab === 'trivia'"
            class="
              absolute
              bottom-0
              left-4
              right-4
              h-0.5
              rounded-full
              bg-indigo-500
            "
          ></span>
        </button>
      </div>

      <!-- ========================================================= -->
      <!-- RECENT GAMES -->
      <!-- ========================================================= -->
      <div v-if="activeTab === 'games'" class="p-3 sm:p-4">
        <!-- Loading -->
        <div
          v-if="loading"
          class="
            rounded-xl
            border border-slate-800
            bg-slate-900/60
            p-8
            text-center
          "
        >
          <div
            class="
              inline-flex
              items-center
              justify-center
              w-10
              h-10
              rounded-full
              bg-indigo-500/10
              mb-3
            "
          >
            <i class="fas fa-spinner fa-spin text-indigo-400"></i>
          </div>

          <p class="text-sm font-semibold text-slate-300">
            Loading Match History
          </p>

          <p class="mt-1 text-xs text-slate-600">
            Retrieving recent games...
          </p>
        </div>

        <!-- No Games -->
        <div
          v-else-if="!matches.length"
          class="
            rounded-xl
            border border-dashed border-slate-800
            bg-slate-900/40
            p-8
            text-center
          "
        >
          <div
            class="
              inline-flex
              items-center
              justify-center
              w-10
              h-10
              rounded-full
              bg-slate-800
              mb-3
            "
          >
            <i class="fas fa-basketball-ball text-slate-600"></i>
          </div>

          <p class="text-sm font-semibold text-slate-400">
            No Matches Found
          </p>

          <p class="mt-1 text-xs text-slate-600">
            There is no recent head-to-head history available.
          </p>
        </div>

        <!-- Games -->
        <div v-else class="space-y-4">
          <!-- Section Header -->
          <div
            class="
              flex
              flex-col
              lg:flex-row
              lg:items-center
              lg:justify-between
              gap-3
            "
          >
            <div>
              <div class="flex items-center gap-2">
                <span
                  class="w-1.5 h-6 rounded-full bg-indigo-500"
                ></span>

                <h2 class="text-base sm:text-lg font-bold text-white">
                  Recent Games
                </h2>
              </div>

              <p class="mt-1 text-xs text-slate-500">
                Head-to-head results between these teams
              </p>
            </div>

            <!-- H2H Record -->
            <div
              class="
                self-start
                lg:self-auto
                flex
                items-center
                gap-2
                px-3
                py-2
                rounded-xl
                border border-slate-800
                bg-slate-900
              "
            >
              <span class="text-[10px] uppercase tracking-wider text-slate-500">
                H2H
              </span>

              <span
                class="
                  px-2
                  py-1
                  rounded-md
                  text-xs
                  sm:text-sm
                  font-black
                  text-white
                "
                :style="
                  gradientStyle(
                    matches[0]?.home_primary_color,
                    matches[0]?.home_secondary_color,
                    matches[0]?.away_primary_color,
                    matches[0]?.away_secondary_color
                  )
                "
              >
                {{ matches[0]?.home_name }}
                {{ homeWins }}
                -
                {{ awayWins }}
                {{ matches[0]?.away_name }}
              </span>

              <span
                v-if="streak"
                class="
                  px-2
                  py-1
                  rounded-md
                  bg-slate-800
                  text-[10px]
                  font-bold
                "
                :class="
                  streak.endsWith('W')
                    ? 'text-emerald-400'
                    : 'text-red-400'
                "
              >
                {{ streak }}
              </span>
            </div>
          </div>

          <!-- Game Table -->
          <div
            class="
              rounded-xl
              border border-slate-800
              bg-slate-950
              overflow-hidden
            "
          >
            <div class="overflow-x-auto">
              <table class="min-w-[900px] w-full">
                <thead>
                  <tr
                    class="
                      bg-slate-900
                      border-b border-slate-800
                    "
                  >
                    <th class="table-header text-left">
                      Season
                    </th>

                    <th class="table-header text-left">
                      Round
                    </th>

                    <th class="table-header text-right">
                      Home
                    </th>

                    <th class="table-header text-center">
                      Final
                    </th>

                    <th class="table-header text-left">
                      Away
                    </th>

                    <th class="table-header text-center">
                      Result
                    </th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/70">
                  <tr
                    v-for="match in matches"
                    :key="match.id"
                    class="
                      group
                      transition-colors
                      duration-150
                      hover:bg-slate-900/80
                    "
                  >
                    <!-- Season -->
                    <td class="table-cell">
                      <div class="flex flex-col">
                        <span class="text-xs font-semibold text-slate-300">
                          {{ match.season_name || `Season ${match.season_id}` }}
                        </span>

                        <span class="text-[9px] text-slate-600">
                          #{{ match.season_id }}
                        </span>
                      </div>
                    </td>

                    <!-- Round -->
                    <td class="table-cell">
                      <span
                        class="
                          inline-flex
                          px-2
                          py-1
                          rounded-md
                          bg-slate-800
                          border border-slate-700
                          text-[10px]
                          font-semibold
                          text-slate-400
                        "
                      >
                        {{ roundNameFormatter(match.round) }}
                      </span>
                    </td>

                    <!-- Home -->
                    <td class="table-cell text-right">
                      <div
                        class="
                          flex
                          items-center
                          justify-end
                          gap-2
                        "
                      >
                        <span
                          class="text-xs sm:text-sm"
                          :class="
                            match.home_id === match.winner_id
                              ? 'font-bold text-white'
                              : 'font-medium text-slate-400'
                          "
                        >
                          {{ match.home_name }}
                        </span>

                        <span
                          v-if="match.home_id === match.winner_id"
                          class="text-[9px] text-emerald-400"
                          title="Winner"
                        >
                          <i class="fas fa-check"></i>
                        </span>
                      </div>
                    </td>

                    <!-- Score -->
                    <td class="table-cell text-center">
                      <div
                        class="
                          inline-flex
                          items-center
                          gap-2
                          px-3
                          py-1.5
                          rounded-lg
                          bg-slate-900
                          border border-slate-800
                          font-mono
                        "
                      >
                        <span
                          :class="
                            match.home_id === match.winner_id
                              ? 'text-white font-black'
                              : 'text-slate-500'
                          "
                        >
                          {{ match.home_score }}
                        </span>

                        <span class="text-slate-700">
                          -
                        </span>

                        <span
                          :class="
                            match.away_id === match.winner_id
                              ? 'text-white font-black'
                              : 'text-slate-500'
                          "
                        >
                          {{ match.away_score }}
                        </span>
                      </div>
                    </td>

                    <!-- Away -->
                    <td class="table-cell">
                      <div
                        class="
                          flex
                          items-center
                          gap-2
                        "
                      >
                        <span
                          v-if="match.away_id === match.winner_id"
                          class="text-[9px] text-emerald-400"
                          title="Winner"
                        >
                          <i class="fas fa-check"></i>
                        </span>

                        <span
                          class="text-xs sm:text-sm"
                          :class="
                            match.away_id === match.winner_id
                              ? 'font-bold text-white'
                              : 'font-medium text-slate-400'
                          "
                        >
                          {{ match.away_name }}
                        </span>
                      </div>
                    </td>

                    <!-- Result -->
                    <td class="table-cell text-center">
                      <span
                        class="
                          inline-flex
                          items-center
                          gap-1.5
                          px-2.5
                          py-1
                          rounded-full
                          text-[10px]
                          font-bold
                          text-white
                          shadow-sm
                        "
                        :style="
                          match.winner_id === match.home_id
                            ? gradientStyle(
                                match.home_primary_color,
                                match.home_secondary_color
                              )
                            : gradientStyle(
                                match.away_primary_color,
                                match.away_secondary_color
                              )
                        "
                      >
                        <i class="fas fa-trophy text-[8px]"></i>

                        {{ match.result_summary || "Win" }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- SERIES HISTORY -->
      <!-- ========================================================= -->
      <div v-else-if="activeTab === 'series'" class="p-3 sm:p-4">
        <!-- Loading -->
        <div
          v-if="loading"
          class="
            rounded-xl
            border border-slate-800
            bg-slate-900/60
            p-8
            text-center
          "
        >
          <div
            class="
              inline-flex
              items-center
              justify-center
              w-10
              h-10
              rounded-full
              bg-indigo-500/10
              mb-3
            "
          >
            <i class="fas fa-spinner fa-spin text-indigo-400"></i>
          </div>

          <p class="text-sm font-semibold text-slate-300">
            Loading Series
          </p>

          <p class="mt-1 text-xs text-slate-600">
            Retrieving series history...
          </p>
        </div>

        <!-- Empty -->
        <div
          v-else-if="!series.length"
          class="
            rounded-xl
            border border-dashed border-slate-800
            bg-slate-900/40
            p-8
            text-center
          "
        >
          <div
            class="
              inline-flex
              items-center
              justify-center
              w-10
              h-10
              rounded-full
              bg-slate-800
              mb-3
            "
          >
            <i class="fas fa-trophy text-slate-600"></i>
          </div>

          <p class="text-sm font-semibold text-slate-400">
            No Series Found
          </p>

          <p class="mt-1 text-xs text-slate-600">
            These teams have no recorded series history.
          </p>
        </div>

        <!-- Series -->
        <div v-else class="space-y-4">
          <div>
            <div class="flex items-center gap-2">
              <span
                class="w-1.5 h-6 rounded-full bg-indigo-500"
              ></span>

              <h2 class="text-base sm:text-lg font-bold text-white">
                Series History
              </h2>
            </div>

            <p class="mt-1 text-xs text-slate-500">
              Previous playoff and championship series
            </p>
          </div>

          <div
            class="
              rounded-xl
              border border-slate-800
              bg-slate-950
              overflow-hidden
            "
          >
            <div class="overflow-x-auto">
              <table class="min-w-[900px] w-full">
                <thead>
                  <tr
                    class="
                      bg-slate-900
                      border-b border-slate-800
                    "
                  >
                    <th class="table-header text-left">
                      Season
                    </th>

                    <th class="table-header text-left">
                      Round
                    </th>

                    <th class="table-header text-right">
                      Home
                    </th>

                    <th class="table-header text-left">
                      Away
                    </th>

                    <th class="table-header text-center">
                      Best Of
                    </th>

                    <th class="table-header text-center">
                      Result
                    </th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/70">
                  <tr
                    v-for="s in series"
                    :key="s.id"
                    class="
                      group
                      transition-colors
                      duration-150
                      hover:bg-slate-900/80
                    "
                  >
                    <!-- Season -->
                    <td class="table-cell">
                      <span class="text-xs font-semibold text-slate-300">
                        Season {{ s.season_id }}
                      </span>
                    </td>

                    <!-- Round -->
                    <td class="table-cell">
                      <span
                        class="
                          inline-flex
                          px-2
                          py-1
                          rounded-md
                          bg-slate-800
                          border border-slate-700
                          text-[10px]
                          font-semibold
                          text-slate-400
                        "
                      >
                        {{ roundNameFormatter(s.round) }}
                      </span>
                    </td>

                    <!-- Home -->
                    <td class="table-cell text-right">
                      <div
                        class="
                          flex
                          items-center
                          justify-end
                          gap-2
                        "
                      >
                        <span
                          v-if="s.home_team_id === s.winner_team_id"
                          class="text-[9px] text-emerald-400"
                        >
                          <i class="fas fa-trophy"></i>
                        </span>

                        <span
                          :class="
                            s.home_team_id === s.winner_team_id
                              ? 'font-bold text-white'
                              : 'font-medium text-slate-400'
                          "
                        >
                          {{ s.home_name }}
                          <span class="text-slate-600">
                            ({{ s.home_acronym }})
                          </span>
                        </span>
                      </div>
                    </td>

                    <!-- Away -->
                    <td class="table-cell">
                      <div
                        class="
                          flex
                          items-center
                          gap-2
                        "
                      >
                        <span
                          :class="
                            s.away_team_id === s.winner_team_id
                              ? 'font-bold text-white'
                              : 'font-medium text-slate-400'
                          "
                        >
                          {{ s.away_name }}
                          <span class="text-slate-600">
                            ({{ s.away_acronym }})
                          </span>
                        </span>

                        <span
                          v-if="s.away_team_id === s.winner_team_id"
                          class="text-[9px] text-emerald-400"
                        >
                          <i class="fas fa-trophy"></i>
                        </span>
                      </div>
                    </td>

                    <!-- Best Of -->
                    <td class="table-cell text-center">
                      <span
                        class="
                          inline-flex
                          items-center
                          justify-center
                          min-w-[42px]
                          px-2
                          py-1
                          rounded-md
                          bg-slate-800
                          text-xs
                          font-bold
                          text-slate-300
                        "
                      >
                        {{ s.series_length }}
                      </span>
                    </td>

                    <!-- Result -->
                    <td class="table-cell text-center">
                      <span
                        class="
                          inline-flex
                          items-center
                          gap-1.5
                          px-2.5
                          py-1
                          rounded-full
                          text-[10px]
                          font-bold
                          text-white
                        "
                        :style="
                          s.winner_team_id === s.home_team_id
                            ? gradientStyle(
                                s.home_primary_color,
                                s.home_secondary_color
                              )
                            : gradientStyle(
                                s.away_primary_color,
                                s.away_secondary_color
                              )
                        "
                      >
                        <i class="fas fa-trophy text-[8px]"></i>

                        {{ s.result_summary || "Completed" }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- TRIVIA -->
      <!-- ========================================================= -->
      <div v-else-if="activeTab === 'trivia'" class="p-3 sm:p-4">
        <!-- No Trivia -->
        <div
          v-if="!matches.length"
          class="
            rounded-xl
            border border-dashed border-slate-800
            bg-slate-900/40
            p-8
            text-center
          "
        >
          <div
            class="
              inline-flex
              items-center
              justify-center
              w-10
              h-10
              rounded-full
              bg-slate-800
              mb-3
            "
          >
            <i class="fas fa-lightbulb text-slate-600"></i>
          </div>

          <p class="text-sm font-semibold text-slate-400">
            No Trivia Found
          </p>

          <p class="mt-1 text-xs text-slate-600">
            More head-to-head games are needed to generate trivia.
          </p>
        </div>

        <!-- Trivia -->
        <div v-else class="space-y-4">
          <div>
            <div class="flex items-center gap-2">
              <span
                class="w-1.5 h-6 rounded-full bg-indigo-500"
              ></span>

              <h2 class="text-base sm:text-lg font-bold text-white">
                Head-to-Head Trivia
              </h2>
            </div>

            <p class="mt-1 text-xs text-slate-500">
              Interesting records from the matchup history
            </p>
          </div>

          <div
            class="
              grid
              grid-cols-1
              lg:grid-cols-2
              gap-3
              sm:gap-4
            "
          >
            <!-- ===================================================== -->
            <!-- HOME TRIVIA -->
            <!-- ===================================================== -->
            <div
              class="
                group
                rounded-2xl
                border border-slate-800
                bg-slate-900
                overflow-hidden
                shadow-lg
              "
            >
              <!-- Header -->
              <div
                class="
                  relative
                  px-4
                  py-4
                  overflow-hidden
                "
                :style="
                  gradientStyle(
                    matches[0]?.home_primary_color,
                    matches[0]?.home_secondary_color
                  )
                "
              >
                <!-- Decorative glow -->
                <div
                  class="
                    absolute
                    -right-8
                    -top-8
                    w-24
                    h-24
                    rounded-full
                    bg-white/10
                    blur-2xl
                  "
                ></div>

                <div class="relative flex items-center gap-3">
                  <div
                    class="
                      flex
                      items-center
                      justify-center
                      w-9
                      h-9
                      rounded-xl
                      bg-white/10
                      border border-white/10
                    "
                  >
                    <i class="fas fa-lightbulb text-white"></i>
                  </div>

                  <div class="min-w-0">
                    <p
                      class="
                        text-[9px]
                        uppercase
                        tracking-[0.18em]
                        text-white/60
                        font-semibold
                      "
                    >
                      Home Team
                    </p>

                    <h3
                      class="
                        text-sm
                        sm:text-base
                        font-bold
                        text-white
                        truncate
                      "
                    >
                      {{ matches[0]?.home_name }}
                    </h3>
                  </div>
                </div>
              </div>

              <!-- Stats -->
              <div class="p-3">
                <div class="grid grid-cols-1 gap-1">
                  <!-- Biggest Win -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-yellow-400/10">
                      <i class="fas fa-trophy text-yellow-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Biggest Win Margin
                      </p>

                      <p class="trivia-stat-description">
                        Largest victory
                      </p>
                    </div>

                    <span class="trivia-stat-value text-yellow-400">
                      {{ biggestWinMarginHome }}
                      <small>pts</small>
                    </span>
                  </div>

                  <!-- Biggest Loss -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-blue-400/10">
                      <i class="fas fa-sad-tear text-blue-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Biggest Losing Margin
                      </p>

                      <p class="trivia-stat-description">
                        Largest defeat
                      </p>
                    </div>

                    <span class="trivia-stat-value text-blue-400">
                      {{ biggestLoseMarginHome }}
                      <small>pts</small>
                    </span>
                  </div>

                  <!-- Win Streak -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-red-400/10">
                      <i class="fas fa-fire text-red-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Most Consecutive Wins
                      </p>

                      <p class="trivia-stat-description">
                        Longest winning streak
                      </p>
                    </div>

                    <span class="trivia-stat-value text-red-400">
                      {{ mostConsecutiveWinsHome }}
                    </span>
                  </div>

                  <!-- Loss Streak -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-slate-700">
                      <i class="fas fa-thumbs-down text-slate-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Most Consecutive Losses
                      </p>

                      <p class="trivia-stat-description">
                        Longest losing streak
                      </p>
                    </div>

                    <span class="trivia-stat-value text-slate-400">
                      {{ mostConsecutiveLossesHome }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===================================================== -->
            <!-- AWAY TRIVIA -->
            <!-- ===================================================== -->
            <div
              class="
                group
                rounded-2xl
                border border-slate-800
                bg-slate-900
                overflow-hidden
                shadow-lg
              "
            >
              <!-- Header -->
              <div
                class="
                  relative
                  px-4
                  py-4
                  overflow-hidden
                "
                :style="
                  gradientStyle(
                    matches[0]?.away_primary_color,
                    matches[0]?.away_secondary_color
                  )
                "
              >
                <div
                  class="
                    absolute
                    -right-8
                    -top-8
                    w-24
                    h-24
                    rounded-full
                    bg-white/10
                    blur-2xl
                  "
                ></div>

                <div class="relative flex items-center gap-3">
                  <div
                    class="
                      flex
                      items-center
                      justify-center
                      w-9
                      h-9
                      rounded-xl
                      bg-white/10
                      border border-white/10
                    "
                  >
                    <i class="fas fa-lightbulb text-white"></i>
                  </div>

                  <div class="min-w-0">
                    <p
                      class="
                        text-[9px]
                        uppercase
                        tracking-[0.18em]
                        text-white/60
                        font-semibold
                      "
                    >
                      Away Team
                    </p>

                    <h3
                      class="
                        text-sm
                        sm:text-base
                        font-bold
                        text-white
                        truncate
                      "
                    >
                      {{ matches[0]?.away_name }}
                    </h3>
                  </div>
                </div>
              </div>

              <!-- Stats -->
              <div class="p-3">
                <div class="grid grid-cols-1 gap-1">
                  <!-- Biggest Win -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-yellow-400/10">
                      <i class="fas fa-trophy text-yellow-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Biggest Win Margin
                      </p>

                      <p class="trivia-stat-description">
                        Largest victory
                      </p>
                    </div>

                    <span class="trivia-stat-value text-yellow-400">
                      {{ biggestWinMarginAway }}
                      <small>pts</small>
                    </span>
                  </div>

                  <!-- Biggest Loss -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-blue-400/10">
                      <i class="fas fa-sad-tear text-blue-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Biggest Losing Margin
                      </p>

                      <p class="trivia-stat-description">
                        Largest defeat
                      </p>
                    </div>

                    <span class="trivia-stat-value text-blue-400">
                      {{ biggestLoseMarginAway }}
                      <small>pts</small>
                    </span>
                  </div>

                  <!-- Win Streak -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-red-400/10">
                      <i class="fas fa-fire text-red-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Most Consecutive Wins
                      </p>

                      <p class="trivia-stat-description">
                        Longest winning streak
                      </p>
                    </div>

                    <span class="trivia-stat-value text-red-400">
                      {{ mostConsecutiveWinsAway }}
                    </span>
                  </div>

                  <!-- Loss Streak -->
                  <div class="trivia-stat">
                    <div class="trivia-stat-icon bg-slate-700">
                      <i class="fas fa-thumbs-down text-slate-400"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="trivia-stat-label">
                        Most Consecutive Losses
                      </p>

                      <p class="trivia-stat-description">
                        Longest losing streak
                      </p>
                    </div>

                    <span class="trivia-stat-value text-slate-400">
                      {{ mostConsecutiveLossesAway }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Optional matchup summary -->
          <div
            class="
              rounded-xl
              border border-slate-800
              bg-slate-900/60
              p-3
              sm:p-4
            "
          >
            <div
              class="
                flex
                flex-col
                sm:flex-row
                sm:items-center
                sm:justify-between
                gap-3
              "
            >
              <div class="flex items-center gap-3">
                <div
                  class="
                    flex
                    items-center
                    justify-center
                    w-8
                    h-8
                    rounded-lg
                    bg-indigo-500/10
                    text-indigo-400
                  "
                >
                  <i class="fas fa-chart-line text-xs"></i>
                </div>

                <div>
                  <p class="text-xs font-semibold text-slate-300">
                    Matchup Overview
                  </p>

                  <p class="text-[10px] text-slate-600">
                    Based on {{ matches.length }} recorded game{{
                      matches.length === 1 ? "" : "s"
                    }}
                  </p>
                </div>
              </div>

              <div
                class="
                  flex
                  items-center
                  gap-2
                  text-xs
                  font-bold
                "
              >
                <span class="text-slate-400">
                  {{ matches[0]?.home_name }}
                </span>

                <span
                  class="
                    px-2
                    py-1
                    rounded-md
                    bg-slate-800
                    text-white
                  "
                >
                  {{ homeWins }} - {{ awayWins }}
                </span>

                <span class="text-slate-400">
                  {{ matches[0]?.away_name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import {
  roundNameFormatter,
  gradientStyle,
} from "@/Utility/Formatter";

const props = defineProps({
  home_id: {
    type: Number,
    required: true,
  },

  away_id: {
    type: Number,
    required: true,
  },

  season_id: {
    type: Number,
    required: true,
  },
});

const matches = ref([]);
const series = ref([]);
const loading = ref(true);
const activeTab = ref("games");

/*
|--------------------------------------------------------------------------
| Head-to-head wins
|--------------------------------------------------------------------------
*/

const homeWins = computed(() => {
  return matches.value.filter(
    (match) => match.winner_id === props.home_id
  ).length;
});

const awayWins = computed(() => {
  return matches.value.filter(
    (match) => match.winner_id === props.away_id
  ).length;
});

/*
|--------------------------------------------------------------------------
| Current streak
|--------------------------------------------------------------------------
|
| Assumes matches are returned in chronological order from the API.
| If your backend returns newest-first, reverse the array before
| calculating streaks.
|--------------------------------------------------------------------------
*/

const streak = computed(() => {
  if (!matches.value.length) {
    return null;
  }

  let currentStreak = 0;
  let lastResult = null;

  for (const match of matches.value) {
    let result = null;

    if (match.winner_id === props.home_id) {
      result = "W";
    } else if (match.winner_id === props.away_id) {
      result = "L";
    }

    if (!result) {
      continue;
    }

    if (lastResult === result) {
      currentStreak++;
    } else {
      currentStreak = 1;
      lastResult = result;
    }
  }

  return currentStreak && lastResult
    ? `${currentStreak}${lastResult}`
    : null;
});

/*
|--------------------------------------------------------------------------
| Biggest win margins
|--------------------------------------------------------------------------
*/

const biggestWinMarginHome = computed(() => {
  const margins = matches.value
    .filter((match) => match.winner_id === props.home_id)
    .map((match) =>
      Math.abs(
        Number(match.home_score || 0) -
          Number(match.away_score || 0)
      )
    );

  return Math.max(...margins, 0);
});

const biggestWinMarginAway = computed(() => {
  const margins = matches.value
    .filter((match) => match.winner_id === props.away_id)
    .map((match) =>
      Math.abs(
        Number(match.home_score || 0) -
          Number(match.away_score || 0)
      )
    );

  return Math.max(...margins, 0);
});

/*
|--------------------------------------------------------------------------
| Biggest losing margins
|--------------------------------------------------------------------------
*/

const biggestLoseMarginHome = computed(() => {
  const margins = matches.value
    .filter(
      (match) =>
        match.winner_id &&
        match.winner_id !== props.home_id
    )
    .map((match) =>
      Math.abs(
        Number(match.home_score || 0) -
          Number(match.away_score || 0)
      )
    );

  return Math.max(...margins, 0);
});

const biggestLoseMarginAway = computed(() => {
  const margins = matches.value
    .filter(
      (match) =>
        match.winner_id &&
        match.winner_id !== props.away_id
    )
    .map((match) =>
      Math.abs(
        Number(match.home_score || 0) -
          Number(match.away_score || 0)
      )
    );

  return Math.max(...margins, 0);
});

/*
|--------------------------------------------------------------------------
| Points extremes
|--------------------------------------------------------------------------
*/

const mostPointsScored = computed(() => {
  if (!matches.value.length) {
    return 0;
  }

  return Math.max(
    ...matches.value.map((match) =>
      Math.max(
        Number(match.home_score || 0),
        Number(match.away_score || 0)
      )
    ),
    0
  );
});

const lowestPointsScored = computed(() => {
  if (!matches.value.length) {
    return 0;
  }

  return Math.min(
    ...matches.value.map((match) =>
      Math.min(
        Number(match.home_score || 0),
        Number(match.away_score || 0)
      )
    )
  );
});

/*
|--------------------------------------------------------------------------
| Longest streak helper
|--------------------------------------------------------------------------
*/

function getStreak(arr, teamId) {
  let maxWin = 0;
  let maxLoss = 0;
  let curWin = 0;
  let curLoss = 0;

  for (const match of arr) {
    if (match.winner_id === teamId) {
      curWin++;
      maxWin = Math.max(maxWin, curWin);
      curLoss = 0;
    } else if (match.winner_id) {
      curLoss++;
      maxLoss = Math.max(maxLoss, curLoss);
      curWin = 0;
    }
  }

  return {
    maxWin,
    maxLoss,
  };
}

/*
|--------------------------------------------------------------------------
| Consecutive wins / losses
|--------------------------------------------------------------------------
*/

const mostConsecutiveWinsHome = computed(() => {
  return getStreak(matches.value, props.home_id).maxWin;
});

const mostConsecutiveWinsAway = computed(() => {
  return getStreak(matches.value, props.away_id).maxWin;
});

const mostConsecutiveLossesHome = computed(() => {
  return getStreak(matches.value, props.home_id).maxLoss;
});

const mostConsecutiveLossesAway = computed(() => {
  return getStreak(matches.value, props.away_id).maxLoss;
});

/*
|--------------------------------------------------------------------------
| Fetch Match + Series History
|--------------------------------------------------------------------------
*/

const getMatchAndSeriesHistory = async () => {
  loading.value = true;

  try {
    const response = await axios.post(
      route("match.history"),
      {
        home_id: props.home_id,
        away_id: props.away_id,
        season_id: props.season_id,
      }
    );

    matches.value = Array.isArray(response.data?.matches)
      ? response.data.matches
      : [];

    series.value = Array.isArray(response.data?.series)
      ? response.data.series
      : [];
  } catch (error) {
    console.error(
      "Error fetching match and series history:",
      error
    );

    matches.value = [];
    series.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getMatchAndSeriesHistory();
});
</script>

<style scoped>
.table-header {
  padding: 0.7rem 0.75rem;
  white-space: nowrap;
  font-size: 0.625rem;
  line-height: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgb(100 116 139);
}

.table-cell {
  padding: 0.75rem;
  white-space: nowrap;
  vertical-align: middle;
}

.trivia-stat {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 58px;
  padding: 0.6rem 0.65rem;
  border-radius: 0.75rem;
  border: 1px solid rgb(30 41 59 / 0.65);
  background: rgb(15 23 42 / 0.55);
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.trivia-stat:hover {
  background: rgb(30 41 59 / 0.65);
  border-color: rgb(51 65 85);
  transform: translateX(2px);
}

.trivia-stat-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.6rem;
  font-size: 0.7rem;
}

.trivia-stat-label {
  font-size: 0.7rem;
  line-height: 1rem;
  font-weight: 600;
  color: rgb(203 213 225);
}

.trivia-stat-description {
  margin-top: 1px;
  font-size: 0.575rem;
  line-height: 0.8rem;
  color: rgb(71 85 105);
}

.trivia-stat-value {
  flex-shrink: 0;
  font-size: 1rem;
  line-height: 1rem;
  font-weight: 900;
  font-variant-numeric: tabular-nums;
}

.trivia-stat-value small {
  margin-left: 2px;
  font-size: 0.55rem;
  font-weight: 600;
  opacity: 0.7;
}

button {
  -webkit-tap-highlight-color: transparent;
}
</style>