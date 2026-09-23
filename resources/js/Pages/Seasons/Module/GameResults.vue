<template>
    <!-- =========================================================
        LOADING SCREEN
    ========================================================== -->
    <div
        v-if="!gameDetails"
        class="game-shell min-h-screen flex items-center justify-center"
    >
        <div class="loading-card">
            <div class="loading-brand">
                <div class="loading-ball">
                    <i class="fas fa-basketball-ball"></i>
                </div>

                <div>
                    <div class="loading-league">
                        LIGA PILIPINAS
                    </div>
                    <div class="loading-game">
                        GAME #{{ props.game_id }}
                    </div>
                </div>
            </div>

            <div class="loading-score">
                <div class="skeleton skeleton-team"></div>
                <div class="skeleton skeleton-score"></div>
                <div class="skeleton skeleton-team"></div>
            </div>

            <div class="loading-status">
                <span class="loading-dot"></span>
                Preparing game center
            </div>

            <div class="loading-timer">
                <i class="fas fa-clock mr-2"></i>
                {{ formatTime(time) }}
            </div>
        </div>
    </div>

    <!-- =========================================================
        GAME CENTER
    ========================================================== -->
    <div v-else class="game-shell min-h-screen">

        <!-- =====================================================
            SCOREBOARD
        ====================================================== -->
        <section class="scoreboard-card">

            <!-- League / Game Meta -->
            <div class="scoreboard-top">
                <div>
                    <div class="league-label">
                        {{ gameDetails?.league_name }}
                    </div>

                    <div class="season-label">
                        {{ gameDetails?.season_name }}
                    </div>
                </div>

                <div class="game-meta">
                    <span class="status-pill">
                        <span class="status-dot"></span>
                        FINAL
                    </span>

                    <span>
                        GAME #{{ gameDetails?.game_id }}
                    </span>
                </div>
            </div>

            <!-- Teams -->
            <div class="scoreboard-main">

                <!-- HOME -->
                <div
                    class="team-score-card"
                    :class="{
                        winner:
                            gameDetails.home_team.score >
                            gameDetails.away_team.score
                    }"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails?.home_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails?.home_team.secondary_color
                    }"
                >
                    <div class="team-side-label">HOME</div>

                    <small class="team-id">
                        #{{ gameDetails?.home_team.team_id }}
                    </small>

                    <div class="team-score">
                        {{ gameDetails?.home_team.score }}
                    </div>

                    <div class="team-name">
                        <TeamDetails
                            :team_id="gameDetails?.home_team.team_id"
                            :key="gameDetails?.home_team.team_id"
                            :showButton="0"
                            :text="`${gameDetails?.home_team.city} ${gameDetails?.home_team.name}`"
                        />
                    </div>

                    <div class="team-streak">
                        {{ gameDetails?.home_team.streak }}
                    </div>

                    <div class="team-footer">
                        <span>
                            <i class="fas fa-user-tie"></i>
                            {{ playerFormatter(gameDetails?.home_team.coach) }}
                        </span>

                        <span>
                            {{ gameDetails?.home_team.sponsor }}
                        </span>
                    </div>

                    <div
                        v-if="
                            gameDetails.home_team.score >
                            gameDetails.away_team.score
                        "
                        class="winner-badge"
                    >
                        <i class="fas fa-crown"></i>
                        WINNER
                    </div>
                </div>

                <!-- CENTER -->
                <div class="scoreboard-center">

                    <div class="vs-badge">
                        VS
                    </div>

                    <div class="game-type">
                        {{
                            isNaN(gameDetails?.round)
                                ? "PLAYOFFS"
                                : "REGULAR SEASON"
                        }}
                    </div>

                    <div class="round-name">
                        {{
                            roundNameFormatter(
                                isNaN(gameDetails?.round)
                                    ? gameDetails?.round
                                    : parseFloat(gameDetails?.round)
                            )
                        }}
                    </div>

                    <div class="head-to-head">
                        <span>HEAD-TO-HEAD</span>

                        <strong>
                            {{
                                gameDetails?.head_to_head_record
                                    ?.home_team_wins ?? 0
                            }}
                            -
                            {{
                                gameDetails?.head_to_head_record
                                    ?.away_team_wins ?? 0
                            }}
                        </strong>
                    </div>

                    <!-- Quarter Breakdown -->
                    <div
                        v-if="breakDown?.length"
                        class="quarter-card"
                    >
                        <div class="quarter-header">
                            <span>GAME FLOW</span>

                            <span v-if="isOvertime > 0" class="ot-label">
                                {{ isOvertime }} OT
                            </span>
                        </div>

                        <div class="quarter-table-wrapper">
                            <table class="quarter-table">
                                <thead>
                                    <tr>
                                        <th>TEAM</th>
                                        <th>Q1</th>
                                        <th>Q2</th>
                                        <th>Q3</th>
                                        <th>Q4</th>

                                        <th
                                            v-for="ot in isOvertime"
                                            :key="ot"
                                        >
                                            OT{{ ot }}
                                        </th>

                                        <th>T</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="b in breakDown"
                                        :key="b.id"
                                    >
                                        <td>{{ b.team_name }}</td>
                                        <td>{{ b.Q1 }}</td>
                                        <td>{{ b.Q2 }}</td>
                                        <td>{{ b.Q3 }}</td>
                                        <td>{{ b.Q4 }}</td>

                                        <td v-if="isOvertime > 0">
                                            {{ b.OT1 }}
                                        </td>

                                        <td v-if="isOvertime > 1">
                                            {{ b.OT2 }}
                                        </td>

                                        <td v-if="isOvertime > 2">
                                            {{ b.OT3 }}
                                        </td>

                                        <td class="total-score">
                                            {{ b.total }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- AWAY -->
                <div
                    class="team-score-card"
                    :class="{
                        winner:
                            gameDetails.away_team.score >
                            gameDetails.home_team.score
                    }"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails?.away_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails?.away_team.secondary_color
                    }"
                >
                    <div class="team-side-label">AWAY</div>

                    <small class="team-id">
                        #{{ gameDetails?.away_team.team_id }}
                    </small>

                    <div class="team-score">
                        {{ gameDetails?.away_team.score }}
                    </div>

                    <div class="team-name">
                        <TeamDetails
                            :team_id="gameDetails?.away_team.team_id"
                            :key="gameDetails?.away_team.team_id"
                            :showButton="0"
                            :text="`${gameDetails?.away_team.city} ${gameDetails?.away_team.name}`"
                        />
                    </div>

                    <div class="team-streak">
                        {{ gameDetails?.away_team.streak }}
                    </div>

                    <div class="team-footer">
                        <span>
                            <i class="fas fa-user-tie"></i>
                            {{ playerFormatter(gameDetails?.away_team.coach) }}
                        </span>

                        <span>
                            {{ gameDetails?.away_team.sponsor }}
                        </span>
                    </div>

                    <div
                        v-if="
                            gameDetails.away_team.score >
                            gameDetails.home_team.score
                        "
                        class="winner-badge"
                    >
                        <i class="fas fa-crown"></i>
                        WINNER
                    </div>
                </div>
            </div>
        </section>

        <!-- =====================================================
            TEAM RATINGS
        ====================================================== -->
        <section
            v-if="!props.showBoxScore"
            class="rating-comparison"
        >
            <div class="rating-team">
                <span>
                    {{ gameDetails.home_team.name }}
                </span>

                <div class="rating-items">
                    <div class="rating-item">
                        <b>{{ gameDetails.home_team.ratings.offense_rating }}</b>
                        <small>OFF</small>
                    </div>

                    <div class="rating-item">
                        <b>{{ gameDetails.home_team.ratings.defense_rating }}</b>
                        <small>DEF</small>
                    </div>

                    <div class="rating-item">
                        <b>{{ gameDetails.home_team.ratings.passing_rating }}</b>
                        <small>PASS</small>
                    </div>

                    <div class="rating-item">
                        <b>{{ gameDetails.home_team.ratings.rebounding_rating }}</b>
                        <small>REB</small>
                    </div>
                </div>
            </div>

            <div class="rating-divider">
                TEAM RATINGS
            </div>

            <div class="rating-team away">
                <span>
                    {{ gameDetails.away_team.name }}
                </span>

                <div class="rating-items">
                    <div class="rating-item">
                        <b>{{ gameDetails.away_team.ratings.offense_rating }}</b>
                        <small>OFF</small>
                    </div>

                    <div class="rating-item">
                        <b>{{ gameDetails.away_team.ratings.defense_rating }}</b>
                        <small>DEF</small>
                    </div>

                    <div class="rating-item">
                        <b>{{ gameDetails.away_team.ratings.passing_rating }}</b>
                        <small>PASS</small>
                    </div>

                    <div class="rating-item">
                        <b>{{ gameDetails.away_team.ratings.rebounding_rating }}</b>
                        <small>REB</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- =====================================================
            BOX SCORE
        ====================================================== -->
        <section v-if="props.showBoxScore" class="boxscore-section">

            <!-- Header -->
            <div class="section-header">
                <div>
                    <span class="section-kicker">GAME CENTER</span>
                    <h2>Player Statistics</h2>
                </div>

                <div class="quarter-selector">

                    <button
                        @click.prevent="switchQuarter(0)"
                        :class="{ active: quarter == 0 }"
                    >
                        ALL
                    </button>

                    <button
                        v-for="i in 4"
                        :key="i"
                        @click.prevent="switchQuarter('Q' + i)"
                        :class="{ active: quarter == 'Q' + i }"
                    >
                        Q{{ i }}
                    </button>

                    <button
                        v-for="i in isOvertime"
                        :key="'ot' + i"
                        @click.prevent="switchQuarter('OT' + i)"
                        :class="{ active: quarter == 'OT' + i }"
                    >
                        OT{{ i }}
                    </button>
                </div>
            </div>

            <!-- Team Box Scores -->
            <div class="boxscore-grid">

                <!-- HOME -->
                <div
                    class="boxscore-card"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails.home_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails.home_team.secondary_color
                    }"
                >
                    <div class="boxscore-heading">
                        <div>
                            <span class="team-mini-label">HOME</span>
                            <h3>
                                {{ gameDetails.home_team.name }}
                            </h3>
                        </div>

                        <div class="boxscore-heading-score">
                            {{ gameDetails.home_team.score }}
                        </div>

                        <button
                            class="depth-toggle"
                            @click.prevent="
                                showHomeDepthChart =
                                    !showHomeDepthChart
                            "
                            title="Depth Chart"
                        >
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>

                    <div
                        v-if="!showHomeDepthChart"
                        class="table-scroll"
                    >
                        <table class="modern-boxscore">
                            <thead>
                                <tr>
                                    <th>PLAYER</th>
                                    <th>POS</th>
                                    <th>MIN</th>
                                    <th>PTS</th>
                                    <th>REB</th>
                                    <th>AST</th>
                                    <th>STL</th>
                                    <th>BLK</th>
                                    <th>TO</th>
                                    <th>FG</th>
                                    <th>PER</th>
                                    <th>EFF</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="player in sortedHomePlayers"
                                    :key="player.name"
                                    @click.prevent="
                                        showPlayerProfileModal = player
                                    "
                                    :class="{
                                        'top-player':
                                            top5HomePlayers.includes(
                                                player.name
                                            )
                                    }"
                                >
                                    <td
                                        class="player-cell"
                                        :class="
                                            boxScoreRoleBadgeClass(
                                                player.role,
                                                player.minutes
                                            )
                                        "
                                    >
                                        <span>
                                            {{ player.name }}
                                        </span>

                                        <small>
                                            {{ player.is_rookie ? "R" : "V" }}

                                            <i
                                                v-if="player.is_injured"
                                                class="fas fa-plus-square injury-icon"
                                            ></i>

                                            <i
                                                v-if="player.fouled_out"
                                                class="fas fa-square fouled-icon"
                                            ></i>
                                        </small>
                                    </td>

                                    <td>{{ player.position }}</td>

                                    <td class="numeric">
                                        {{
                                            player.minutes === 0
                                                ? "DNP"
                                                : player.minutes.toFixed(1)
                                        }}
                                    </td>

                                    <td class="numeric stat-highlight">
                                        {{ player.points.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.rebounds.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.assists.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.steals.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.blocks.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{
                                            player.minutes === 0
                                                ? "0.0"
                                                : player.turnovers.toFixed(1)
                                        }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.field_goals_made }}
                                        /
                                        {{ player.field_goal_attempts }}
                                    </td>

                                    <td class="numeric">
                                        {{
                                            isNaN(parseFloat(player.per))
                                                ? "0.00"
                                                : parseFloat(player.per).toFixed(2)
                                        }}
                                    </td>

                                    <td class="numeric">
                                        <strong
                                            :class="
                                                player.efficiency <= 0
                                                    ? 'negative-stat'
                                                    : 'positive-stat'
                                            "
                                        >
                                            {{ player.efficiency ?? 0 }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        sortedHomePlayers.length === 0
                                    "
                                >
                                    <td
                                        colspan="12"
                                        class="empty-row"
                                    >
                                        No player statistics available.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <DepthChartRow
                        v-if="
                            gameDetails?.home_team.team_id &&
                            showHomeDepthChart
                        "
                        :key="gameDetails?.home_team.team_id"
                        :players="sortedHomePlayers"
                        :season_id="gameDetails?.current_season"
                    />
                </div>

                <!-- AWAY -->
                <div
                    class="boxscore-card"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails.away_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails.away_team.secondary_color
                    }"
                >
                    <div class="boxscore-heading">
                        <div>
                            <span class="team-mini-label">AWAY</span>
                            <h3>
                                {{ gameDetails.away_team.name }}
                            </h3>
                        </div>

                        <div class="boxscore-heading-score">
                            {{ gameDetails.away_team.score }}
                        </div>

                        <button
                            class="depth-toggle"
                            @click.prevent="
                                showAwayDepthChart =
                                    !showAwayDepthChart
                            "
                            title="Depth Chart"
                        >
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>

                    <div
                        v-if="!showAwayDepthChart"
                        class="table-scroll"
                    >
                        <table class="modern-boxscore">
                            <thead>
                                <tr>
                                    <th>PLAYER</th>
                                    <th>POS</th>
                                    <th>MIN</th>
                                    <th>PTS</th>
                                    <th>REB</th>
                                    <th>AST</th>
                                    <th>STL</th>
                                    <th>BLK</th>
                                    <th>TO</th>
                                    <th>FG</th>
                                    <th>PER</th>
                                    <th>EFF</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="player in sortedAwayPlayers"
                                    :key="player.name"
                                    @click.prevent="
                                        showPlayerProfileModal = player
                                    "
                                    :class="{
                                        'top-player':
                                            top5AwayPlayers.includes(
                                                player.name
                                            )
                                    }"
                                >
                                    <td
                                        class="player-cell"
                                        :class="
                                            boxScoreRoleBadgeClass(
                                                player.role,
                                                player.minutes
                                            )
                                        "
                                    >
                                        <span>
                                            {{ player.name }}
                                        </span>

                                        <small>
                                            {{ player.is_rookie ? "R" : "V" }}

                                            <i
                                                v-if="player.is_injured"
                                                class="fas fa-plus-square injury-icon"
                                            ></i>

                                            <i
                                                v-if="player.fouled_out"
                                                class="fas fa-square fouled-icon"
                                            ></i>
                                        </small>
                                    </td>

                                    <td>{{ player.position }}</td>

                                    <td class="numeric">
                                        {{
                                            player.minutes === 0
                                                ? "DNP"
                                                : player.minutes.toFixed(1)
                                        }}
                                    </td>

                                    <td class="numeric stat-highlight">
                                        {{ player.points.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.rebounds.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.assists.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.steals.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.blocks.toFixed(1) }}
                                    </td>

                                    <td class="numeric">
                                        {{
                                            player.minutes === 0
                                                ? "0.0"
                                                : player.turnovers.toFixed(1)
                                        }}
                                    </td>

                                    <td class="numeric">
                                        {{ player.field_goals_made }}
                                        /
                                        {{ player.field_goal_attempts }}
                                    </td>

                                    <td class="numeric">
                                        {{
                                            isNaN(parseFloat(player.per))
                                                ? "0.00"
                                                : parseFloat(player.per).toFixed(2)
                                        }}
                                    </td>

                                    <td class="numeric">
                                        <strong
                                            :class="
                                                player.efficiency <= 0
                                                    ? 'negative-stat'
                                                    : 'positive-stat'
                                            "
                                        >
                                            {{ player.efficiency ?? 0 }}
                                        </strong>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        sortedAwayPlayers.length === 0
                                    "
                                >
                                    <td
                                        colspan="12"
                                        class="empty-row"
                                    >
                                        No player statistics available.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <DepthChartRow
                        v-if="
                            gameDetails?.away_team.team_id &&
                            showAwayDepthChart
                        "
                        :key="gameDetails?.away_team.team_id"
                        :players="sortedAwayPlayers"
                        :season_id="gameDetails?.current_season"
                    />
                </div>
            </div>
        </section>

        <!-- =====================================================
            BOTTOM CONTENT
        ====================================================== -->
        <section class="bottom-grid">

            <!-- PLAYER OF THE GAME -->
            <div class="featured-card">

                <div class="section-header compact">
                    <div>
                        <span class="section-kicker">GAME AWARD</span>
                        <h2>Player of the Game</h2>
                    </div>

                    <i class="fas fa-star section-icon"></i>
                </div>

                <div
                    v-if="bestPlayer"
                    class="player-of-game"
                >
                    <div class="pog-header">

                        <div class="pog-avatar">
                            <i class="fas fa-user"></i>
                        </div>

                        <div class="pog-name">
                            <h3>
                                {{ playerFormatter(bestPlayer.name) }}
                            </h3>

                            <span>
                                {{ bestPlayer.team }}
                                ·
                                {{ bestPlayer.position }}
                                ·
                                Age {{ bestPlayer.age }}
                            </span>
                        </div>

                        <span
                            :class="roleBadgeClass(bestPlayer.role)"
                            class="pog-role"
                        >
                            {{ bestPlayer.role }}
                        </span>
                    </div>

                    <div class="pog-stats">

                        <div
                            v-if="bestPlayer.points > 0"
                            class="pog-stat primary"
                        >
                            <strong>{{ bestPlayer.points }}</strong>
                            <span>PTS</span>
                        </div>

                        <div
                            v-if="bestPlayer.rebounds > 0"
                            class="pog-stat"
                        >
                            <strong>{{ bestPlayer.rebounds }}</strong>
                            <span>REB</span>
                        </div>

                        <div
                            v-if="bestPlayer.assists > 0"
                            class="pog-stat"
                        >
                            <strong>{{ bestPlayer.assists }}</strong>
                            <span>AST</span>
                        </div>

                        <div
                            v-if="bestPlayer.steals > 3"
                            class="pog-stat"
                        >
                            <strong>{{ bestPlayer.steals }}</strong>
                            <span>STL</span>
                        </div>

                        <div
                            v-if="bestPlayer.blocks > 3"
                            class="pog-stat"
                        >
                            <strong>{{ bestPlayer.blocks }}</strong>
                            <span>BLK</span>
                        </div>

                        <div
                            v-if="bestPlayer.eff > 5"
                            class="pog-stat"
                        >
                            <strong>{{ bestPlayer.eff }}</strong>
                            <span>EFF</span>
                        </div>
                    </div>

                    <div class="pog-awards">

                        <span
                            v-if="bestPlayer.is_finals_mvp"
                            class="award gold"
                        >
                            <i class="fas fa-medal"></i>
                            Finals MVP
                        </span>

                        <span
                            v-if="bestPlayer.is_season_mvp"
                            class="award green"
                        >
                            <i class="fas fa-star"></i>
                            Season MVP
                        </span>

                        <span
                            v-if="bestPlayer.is_defensive_poy"
                            class="award gray"
                        >
                            <i class="fas fa-shield-alt"></i>
                            Defensive Player
                        </span>

                        <span
                            v-if="bestPlayer.is_rookie_poy"
                            class="award gold"
                        >
                            <i class="fas fa-medal"></i>
                            Rookie of the Season
                        </span>

                        <span
                            v-if="bestPlayer.is_all_rookie"
                            class="award blue"
                        >
                            <i class="fas fa-medal"></i>
                            All-Rookie
                        </span>

                        <span
                            v-if="bestPlayer.is_most_improved"
                            class="award purple"
                        >
                            <i class="fas fa-chart-line"></i>
                            MIP
                        </span>

                        <span
                            v-if="bestPlayer.is_sixth_man"
                            class="award gray"
                        >
                            <i class="fas fa-user"></i>
                            Sixth Man
                        </span>
                    </div>

                    <div class="draft-info">
                        <i class="fas fa-drafting-compass"></i>

                        <span>
                            Draft:
                            {{
                                bestPlayer.draft_status == "Undrafted"
                                    ? `S${bestPlayer.draft_id} ${bestPlayer.draft_status}`
                                    : bestPlayer.draft_status +
                                      (
                                          bestPlayer.drafted_team_acro
                                              ? ` (${bestPlayer.drafted_team_acro})`
                                              : ""
                                      )
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="side-content">

                <!-- Toggle -->
                <div class="side-header">
                    <div>
                        <span class="section-kicker">
                            GAME INSIGHTS
                        </span>

                        <h2>
                            {{ showGameNews ? "Game News" : "Stat Leaders" }}
                        </h2>
                    </div>

                    <button
                        class="panel-toggle"
                        @click.prevent="
                            showGameNews = !showGameNews
                        "
                    >
                        <i
                            :class="
                                showGameNews
                                    ? 'fas fa-chart-bar'
                                    : 'fas fa-newspaper'
                            "
                        ></i>
                    </button>
                </div>

                <!-- GAME NEWS -->
                <div
                    v-if="showGameNews"
                    class="news-panel"
                >
                    <GameNews
                        :key="gameNews.id"
                        :data="gameNews"
                        :showNews="true"
                    />
                </div>

                <!-- STAT LEADERS -->
                <div v-else class="leaders-list">

                    <div
                        v-if="statLeaders.points"
                        class="leader-card"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-basketball-ball"></i>
                        </div>

                        <div class="leader-info">
                            <span class="leader-category">
                                POINTS
                            </span>

                            <strong>
                                {{
                                    playerFormatter(
                                        statLeaders.points.player_name
                                    )
                                }}
                            </strong>

                            <small>
                                {{ statLeaders.points.team_name }}
                            </small>
                        </div>

                        <div class="leader-value">
                            {{ statLeaders.points.points }}
                        </div>
                    </div>

                    <div
                        v-if="statLeaders.assists"
                        class="leader-card"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-hands"></i>
                        </div>

                        <div class="leader-info">
                            <span class="leader-category">
                                ASSISTS
                            </span>

                            <strong>
                                {{
                                    playerFormatter(
                                        statLeaders.assists.player_name
                                    )
                                }}
                            </strong>

                            <small>
                                {{ statLeaders.assists.team_name }}
                            </small>
                        </div>

                        <div class="leader-value">
                            {{ statLeaders.assists.assists }}
                        </div>
                    </div>

                    <div
                        v-if="statLeaders.rebounds"
                        class="leader-card"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>

                        <div class="leader-info">
                            <span class="leader-category">
                                REBOUNDS
                            </span>

                            <strong>
                                {{
                                    playerFormatter(
                                        statLeaders.rebounds.player_name
                                    )
                                }}
                            </strong>

                            <small>
                                {{ statLeaders.rebounds.team_name }}
                            </small>
                        </div>

                        <div class="leader-value">
                            {{ statLeaders.rebounds.rebounds }}
                        </div>
                    </div>

                    <div
                        v-if="
                            statLeaders.steals &&
                            statLeaders.steals.steals > 0
                        "
                        class="leader-card"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>

                        <div class="leader-info">
                            <span class="leader-category">
                                STEALS
                            </span>

                            <strong>
                                {{
                                    playerFormatter(
                                        statLeaders.steals.player_name
                                    )
                                }}
                            </strong>

                            <small>
                                {{ statLeaders.steals.team_name }}
                            </small>
                        </div>

                        <div class="leader-value">
                            {{ statLeaders.steals.steals }}
                        </div>
                    </div>

                    <div
                        v-if="
                            statLeaders.blocks &&
                            statLeaders.blocks.blocks > 0
                        "
                        class="leader-card"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>

                        <div class="leader-info">
                            <span class="leader-category">
                                BLOCKS
                            </span>

                            <strong>
                                {{
                                    playerFormatter(
                                        statLeaders.blocks.player_name
                                    )
                                }}
                            </strong>

                            <small>
                                {{ statLeaders.blocks.team_name }}
                            </small>
                        </div>

                        <div class="leader-value">
                            {{ statLeaders.blocks.blocks }}
                        </div>
                    </div>
                </div>

                <!-- INJURY NEWS -->
                <div class="breaking-news">

                    <div class="breaking-title">
                        <span>
                            <i class="fas fa-exclamation-circle"></i>
                            BREAKING NEWS
                        </span>
                    </div>

                    <div class="breaking-body">

                        <div
                            v-if="injuredPlayers?.length"
                            class="breaking-marquee"
                        >
                            <span
                                v-for="injury in injuredPlayers"
                                :key="injury.id"
                            >
                                {{ formatInjuredPlayers(injury) }}
                            </span>
                        </div>

                        <p v-else>
                            {{ gameNews?.title ?? "No breaking news." }}
                        </p>
                    </div>
                </div>

                <!-- SEASON LEADER -->
                <div
                    v-if="!props.showBoxScore && seasonLeaders"
                    class="season-leader"
                >
                    <span>
                        {{ seasonLeaders.message }}
                    </span>

                    <strong>
                        {{ seasonLeaders.player_name }}
                    </strong>

                    <small>
                        {{ seasonLeaders.stat_value }}
                        {{ seasonLeaders.stat_type }}
                        ·
                        {{ seasonLeaders.team_acronym }}
                    </small>
                </div>
            </div>
        </section>

        <!-- =====================================================
            TIMER
        ====================================================== -->
        <div
            v-if="!showBoxScore"
            class="game-timer"
        >
            <i class="fas fa-clock"></i>
            {{ formatTime(time) }}
        </div>
    </div>

    <!-- =========================================================
        PLAYER MODAL
    ========================================================== -->
    <Modal
        :show="showPlayerProfileModal"
        :maxWidth="'6xl'"
        title="Player Profile"
        @close="showPlayerProfileModal = false"
    >
        <div class="p-6">
            <PlayerPerformance
                :key="showPlayerProfileModal.player_id"
                :player_id="showPlayerProfileModal.player_id"
            />
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import axios from "axios"; 
import { roundNameFormatter, roleBadgeClass, playerFormatter,boxScoreRoleBadgeClass } from "@/Utility/Formatter";
import Modal from "@/Components/Modal.vue";
import Swal from "sweetalert2";
import PlayerPerformance from "@/Pages/Players/Module/PlayerPerformance.vue";
import TeamDetails from "@/Pages/Teams/Module/TeamDetails.vue";
import DepthChartRow from "@/Pages/Teams/Module/DepthChartRow.vue";
import GameNews from "@/Pages/Seasons/Module/GameNews.vue";

const props = defineProps({
    game_id: {
        type: String,
        required: true,
    },
    season_id: {
        type: [String, Number],
        required: true,
    },
    showBoxScore: {
        type: Boolean,
        default: true,
    },
    showGameNews:{
        type: Boolean,
        default: false,
    }
});
const showPlayerProfileModal = ref(false);
const isTeamRosterModalOpen = ref(false);
const gameDetails = ref(false);
const playerStats = ref({ home: [], away: [] });
const bestPlayer = ref(null);
const statLeaders = ref([]);
const injuredPlayers =ref([]);
const seasonLeaders = ref([]);
const showGameNews = ref(false);
const showAwayDepthChart = ref(false);
const showHomeDepthChart = ref(false);
const gameNews = ref([]);
const breakDown = ref([]);
const isOvertime = ref(0);
const quarter = ref(0);
// Fetch the box score data
const time = ref(0); // Timer in seconds
const interval = ref(null); // Stores interval ID
const gameFinished = ref(false); // Flag for game completion

// Start the timer
const startTimer = () => {
  if (interval.value) return; // Prevent multiple intervals running

  interval.value = setInterval(() => {
    time.value++;
  }, 1000);
}

// Stop the timer
const stopTimer = () => {
  if (interval.value) {
    clearInterval(interval.value);
    interval.value = null; // Reset interval
  }
}

// Format time to mm:ss
const  formatTime  = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

const switchQuarter = async (activeQuarter) => {
    quarter.value = activeQuarter;

    await fetchBoxScore();
}
const fetchBoxScore = async () => {
    try {
        gameFinished.value = false; // Reset the game status
        startTimer(); // Start the timer
        gameDetails.value = false;
        const response = await axios.post(route("game.boxscore"), {
            game_id: props.game_id,
            show_stats: props.showBoxScore,
            season_id: props.season_id,
            quarter: quarter.value
        });
        const data = response.data.box_score;

        gameDetails.value = data;
        playerStats.value.home = data.player_stats.home;
        playerStats.value.away = data.player_stats.away;
        bestPlayer.value = data.best_player;
        statLeaders.value = data.stat_leaders;
        injuredPlayers.value = data.injury;
        seasonLeaders.value = data.league_leaders;
        gameNews.value = data.news;
        breakDown.value = data.per_quarter_breakdown;
        isOvertime.value = data.is_overtime;
        gameFinished.value = true;
        // stopTimer(); // Stop the timer when the game finishes
    } catch (error) {
        console.error("Error fetching box score:", error);
    }
};

onUnmounted(() => {
    stopTimer();
});

// Sort players by points and get top 5 players
const sortedHomePlayers = computed(() => {
    return playerStats.value.home.slice().sort((a, b) => b.points - a.points);
});

const sortedAwayPlayers = computed(() => {
    return playerStats.value.away.slice().sort((a, b) => b.points - a.points);
});

const top5HomePlayers = computed(() => {
    return sortedHomePlayers.value.slice(0, 5).map((player) => player.name);
});

const top5AwayPlayers = computed(() => {
    return sortedAwayPlayers.value.slice(0, 5).map((player) => player.name);
});

const messageMap = new Map(); // Store message format for each player

const formatInjuredPlayers = (player) => {
    // If player already has a message format, use it
    if (!messageMap.has(player.player_id)) {
        const messages = [
            `🚨 ${player.player_name} ${player.position} (${player.team_when_injured}) - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | Estimated recovery ${player.recovery_games} days.`,
            `⚠️ ${player.team_when_injured}'s ${player.player_name} ${player.position} - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | Estimated recovery ${player.recovery_games} days.`,
            `🏥 ${player.player_name} ${player.position} (${player.team_when_injured}) - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | Will miss ${player.recovery_games} days.`,
            `⛔ ${player.team_when_injured} loses ${player.player_name} ${player.position} - ${player.injury_type.replaceAll('_', ' ')} | ${player.role} | ${player.recovery_games} days estimated recovery.`
        ];

        // Pick a random message format
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        messageMap.set(player.player_id, randomMessage); // Store the message format
    }

    return messageMap.get(player.player_id) + ' • '; // Add bullet point separator
};

onMounted(() => {
    fetchBoxScore();
    showGameNews.value = props.showGameNews;
});
</script>
