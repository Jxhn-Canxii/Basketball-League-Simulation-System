```vue
<template>
    <!-- =========================================================
        LOADING
    ========================================================== -->
    <div v-if="!gameDetails" class="game-page loading-page">
        <div class="loading-panel">
            <div class="loading-logo">
                <div class="loading-ball">
                    <i class="fas fa-basketball-ball"></i>
                </div>

                <div>
                    <span>LIGA PILIPINAS</span>
                    <strong>GAME #{{ props.game_id }}</strong>
                </div>
            </div>

            <div class="loading-score">
                <div class="skeleton skeleton-team"></div>
                <div class="skeleton skeleton-score"></div>
                <div class="skeleton skeleton-team"></div>
            </div>

            <div class="loading-message">
                <span class="loading-dot"></span>
                Preparing Game Center
            </div>

            <div class="loading-time">
                <i class="fas fa-clock"></i>
                {{ formatTime(time) }}
            </div>
        </div>
    </div>

    <!-- =========================================================
        GAME CENTER
    ========================================================== -->
    <div v-else class="game-page">

        <!-- =====================================================
            GAME HEADER
        ====================================================== -->
        <section class="game-header">

            <div class="header-meta">
                <div>
                    <span class="league-name">
                        {{ gameDetails?.league_name }}
                    </span>

                    <span class="season-name">
                        {{ gameDetails?.season_name }}
                    </span>
                </div>

                <div class="header-right">
                    <span class="final-badge">
                        <span></span>
                        FINAL
                    </span>

                    <span class="game-number">
                        GAME #{{ gameDetails?.game_id }}
                    </span>
                </div>
            </div>

            <!-- =================================================
                SCOREBOARD
            ================================================== -->
            <div class="scoreboard">

                <!-- HOME -->
                <div
                    class="score-team home"
                    :class="{
                        winner:
                            gameDetails.home_team.score >
                            gameDetails.away_team.score
                    }"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails.home_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails.home_team.secondary_color
                    }"
                >
                    <span class="location-label">HOME</span>

                    <div class="team-number">
                        #{{ gameDetails.home_team.team_id }}
                    </div>

                    <div class="team-name">
                        <TeamDetails
                            :team_id="gameDetails.home_team.team_id"
                            :showButton="0"
                            :text="`${gameDetails.home_team.city} ${gameDetails.home_team.name}`"
                        />
                    </div>

                    <div class="score">
                        {{ gameDetails.home_team.score }}
                    </div>

                    <div class="team-record">
                        {{ gameDetails.home_team.streak }}
                    </div>

                    <div class="team-meta">
                        <span>
                            <i class="fas fa-user-tie"></i>
                            {{ playerFormatter(gameDetails.home_team.coach) }}
                        </span>

                        <span>
                            {{ gameDetails.home_team.sponsor }}
                        </span>
                    </div>

                    <div
                        v-if="
                            gameDetails.home_team.score >
                            gameDetails.away_team.score
                        "
                        class="winner-label"
                    >
                        <i class="fas fa-crown"></i>
                        WINNER
                    </div>
                </div>

                <!-- CENTER -->
                <div class="score-center">

                    <div class="game-context">
                        {{
                            isNaN(gameDetails?.round)
                                ? "PLAYOFFS"
                                : "REGULAR SEASON"
                        }}
                    </div>

                    <div class="round-title">
                        {{
                            roundNameFormatter(
                                isNaN(gameDetails?.round)
                                    ? gameDetails?.round
                                    : parseFloat(gameDetails?.round)
                            )
                        }}
                    </div>

                    <div class="vs">
                        <span>VS</span>
                    </div>

                    <div class="head-to-head">
                        <span>HEAD TO HEAD</span>
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
                </div>

                <!-- AWAY -->
                <div
                    class="score-team away"
                    :class="{
                        winner:
                            gameDetails.away_team.score >
                            gameDetails.home_team.score
                    }"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails.away_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails.away_team.secondary_color
                    }"
                >
                    <span class="location-label">AWAY</span>

                    <div class="team-number">
                        #{{ gameDetails.away_team.team_id }}
                    </div>

                    <div class="team-name">
                        <TeamDetails
                            :team_id="gameDetails.away_team.team_id"
                            :showButton="0"
                            :text="`${gameDetails.away_team.city} ${gameDetails.away_team.name}`"
                        />
                    </div>

                    <div class="score">
                        {{ gameDetails.away_team.score }}
                    </div>

                    <div class="team-record">
                        {{ gameDetails.away_team.streak }}
                    </div>

                    <div class="team-meta">
                        <span>
                            <i class="fas fa-user-tie"></i>
                            {{ playerFormatter(gameDetails.away_team.coach) }}
                        </span>

                        <span>
                            {{ gameDetails.away_team.sponsor }}
                        </span>
                    </div>

                    <div
                        v-if="
                            gameDetails.away_team.score >
                            gameDetails.home_team.score
                        "
                        class="winner-label"
                    >
                        <i class="fas fa-crown"></i>
                        WINNER
                    </div>
                </div>
            </div>

            <!-- =================================================
                GAME FLOW
            ================================================== -->
            <div
                v-if="breakDown?.length"
                class="game-flow"
            >
                <div class="flow-header">
                    <div>
                        <span>GAME FLOW</span>
                        <strong>Quarter Breakdown</strong>
                    </div>

                    <span
                        v-if="isOvertime > 0"
                        class="overtime-badge"
                    >
                        {{ isOvertime }} OT
                    </span>
                </div>

                <div class="flow-table-wrapper">
                    <table class="flow-table">
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

                                <th class="total-column">TOTAL</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="b in breakDown"
                                :key="b.id"
                            >
                                <td>
                                    {{ b.team_name }}
                                </td>

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
        </section>

        <!-- =====================================================
            TEAM RATINGS
        ====================================================== -->
        <section
            v-if="!props.showBoxScore"
            class="ratings-panel"
        >
            <div class="ratings-team">
                <span>{{ gameDetails.home_team.name }}</span>

                <div class="ratings">
                    <div>
                        <strong>
                            {{ gameDetails.home_team.ratings.offense_rating }}
                        </strong>
                        <small>OFF</small>
                    </div>

                    <div>
                        <strong>
                            {{ gameDetails.home_team.ratings.defense_rating }}
                        </strong>
                        <small>DEF</small>
                    </div>

                    <div>
                        <strong>
                            {{ gameDetails.home_team.ratings.passing_rating }}
                        </strong>
                        <small>PASS</small>
                    </div>

                    <div>
                        <strong>
                            {{ gameDetails.home_team.ratings.rebounding_rating }}
                        </strong>
                        <small>REB</small>
                    </div>
                </div>
            </div>

            <div class="ratings-title">
                TEAM RATINGS
            </div>

            <div class="ratings-team away">
                <div class="ratings">
                    <div>
                        <strong>
                            {{ gameDetails.away_team.ratings.offense_rating }}
                        </strong>
                        <small>OFF</small>
                    </div>

                    <div>
                        <strong>
                            {{ gameDetails.away_team.ratings.defense_rating }}
                        </strong>
                        <small>DEF</small>
                    </div>

                    <div>
                        <strong>
                            {{ gameDetails.away_team.ratings.passing_rating }}
                        </strong>
                        <small>PASS</small>
                    </div>

                    <div>
                        <strong>
                            {{ gameDetails.away_team.ratings.rebounding_rating }}
                        </strong>
                        <small>REB</small>
                    </div>
                </div>

                <span>{{ gameDetails.away_team.name }}</span>
            </div>
        </section>

        <!-- =====================================================
            BOX SCORE
        ====================================================== -->
        <section
            v-if="props.showBoxScore"
            class="content-section"
        >

            <div class="section-top">
                <div>
                    <span class="eyebrow">GAME CENTER</span>
                    <h2>Player Statistics</h2>
                </div>

                <!-- Quarter Selector -->
                <div class="quarter-tabs">

                    <button
                        :class="{ active: quarter == 0 }"
                        @click.prevent="switchQuarter(0)"
                    >
                        ALL
                    </button>

                    <button
                        v-for="i in 4"
                        :key="i"
                        :class="{ active: quarter == 'Q' + i }"
                        @click.prevent="switchQuarter('Q' + i)"
                    >
                        Q{{ i }}
                    </button>

                    <button
                        v-for="i in isOvertime"
                        :key="'ot' + i"
                        :class="{ active: quarter == 'OT' + i }"
                        @click.prevent="switchQuarter('OT' + i)"
                    >
                        OT{{ i }}
                    </button>
                </div>
            </div>

            <div class="boxscore-grid">

                <!-- =================================================
                    HOME BOX SCORE
                ================================================== -->
                <div
                    class="box-card"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails.home_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails.home_team.secondary_color
                    }"
                >
                    <div class="box-card-header">

                        <div>
                            <span class="side-tag">HOME</span>

                            <h3>
                                {{ gameDetails.home_team.name }}
                            </h3>
                        </div>

                        <div class="box-score">
                            {{ gameDetails.home_team.score }}
                        </div>

                        <button
                            class="icon-button"
                            @click.prevent="
                                showHomeDepthChart =
                                    !showHomeDepthChart
                            "
                            title="Toggle Depth Chart"
                        >
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>

                    <div
                        v-if="!showHomeDepthChart"
                        class="table-scroll"
                    >
                        <table class="player-table">

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
                                    :class="{
                                        'top-player':
                                            top5HomePlayers.includes(
                                                player.name
                                            )
                                    }"
                                    @click.prevent="
                                        showPlayerProfileModal = player
                                    "
                                >
                                    <td
                                        class="player-name-cell"
                                        :class="
                                            boxScoreRoleBadgeClass(
                                                player.role,
                                                player.minutes
                                            )
                                        "
                                    >
                                        <div>
                                            <span>
                                                {{ player.name }}
                                            </span>

                                            <small>
                                                {{
                                                    player.is_rookie
                                                        ? "R"
                                                        : "V"
                                                }}

                                                <i
                                                    v-if="player.is_injured"
                                                    class="fas fa-plus-square injury-icon"
                                                ></i>

                                                <i
                                                    v-if="player.fouled_out"
                                                    class="fas fa-square fouled-icon"
                                                ></i>
                                            </small>
                                        </div>
                                    </td>

                                    <td>{{ player.position }}</td>

                                    <td class="number">
                                        {{
                                            player.minutes === 0
                                                ? "DNP"
                                                : player.minutes.toFixed(1)
                                        }}
                                    </td>

                                    <td class="number points">
                                        {{ player.points.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.rebounds.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.assists.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.steals.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.blocks.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{
                                            player.minutes === 0
                                                ? "0.0"
                                                : player.turnovers.toFixed(1)
                                        }}
                                    </td>

                                    <td class="number">
                                        {{ player.field_goals_made }}
                                        /
                                        {{ player.field_goal_attempts }}
                                    </td>

                                    <td class="number">
                                        {{
                                            isNaN(parseFloat(player.per))
                                                ? "0.00"
                                                : parseFloat(player.per).toFixed(2)
                                        }}
                                    </td>

                                    <td class="number">
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

                <!-- =================================================
                    AWAY BOX SCORE
                ================================================== -->
                <div
                    class="box-card"
                    :style="{
                        '--team-primary':
                            '#' + gameDetails.away_team.primary_color,
                        '--team-secondary':
                            '#' + gameDetails.away_team.secondary_color
                    }"
                >
                    <div class="box-card-header">

                        <div>
                            <span class="side-tag">AWAY</span>

                            <h3>
                                {{ gameDetails.away_team.name }}
                            </h3>
                        </div>

                        <div class="box-score">
                            {{ gameDetails.away_team.score }}
                        </div>

                        <button
                            class="icon-button"
                            @click.prevent="
                                showAwayDepthChart =
                                    !showAwayDepthChart
                            "
                            title="Toggle Depth Chart"
                        >
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>

                    <div
                        v-if="!showAwayDepthChart"
                        class="table-scroll"
                    >
                        <table class="player-table">

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
                                    :class="{
                                        'top-player':
                                            top5AwayPlayers.includes(
                                                player.name
                                            )
                                    }"
                                    @click.prevent="
                                        showPlayerProfileModal = player
                                    "
                                >
                                    <td
                                        class="player-name-cell"
                                        :class="
                                            boxScoreRoleBadgeClass(
                                                player.role,
                                                player.minutes
                                            )
                                        "
                                    >
                                        <div>
                                            <span>
                                                {{ player.name }}
                                            </span>

                                            <small>
                                                {{
                                                    player.is_rookie
                                                        ? "R"
                                                        : "V"
                                                }}

                                                <i
                                                    v-if="player.is_injured"
                                                    class="fas fa-plus-square injury-icon"
                                                ></i>

                                                <i
                                                    v-if="player.fouled_out"
                                                    class="fas fa-square fouled-icon"
                                                ></i>
                                            </small>
                                        </div>
                                    </td>

                                    <td>{{ player.position }}</td>

                                    <td class="number">
                                        {{
                                            player.minutes === 0
                                                ? "DNP"
                                                : player.minutes.toFixed(1)
                                        }}
                                    </td>

                                    <td class="number points">
                                        {{ player.points.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.rebounds.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.assists.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.steals.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{ player.blocks.toFixed(1) }}
                                    </td>

                                    <td class="number">
                                        {{
                                            player.minutes === 0
                                                ? "0.0"
                                                : player.turnovers.toFixed(1)
                                        }}
                                    </td>

                                    <td class="number">
                                        {{ player.field_goals_made }}
                                        /
                                        {{ player.field_goal_attempts }}
                                    </td>

                                    <td class="number">
                                        {{
                                            isNaN(parseFloat(player.per))
                                                ? "0.00"
                                                : parseFloat(player.per).toFixed(2)
                                        }}
                                    </td>

                                    <td class="number">
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
            LOWER CONTENT
        ====================================================== -->
        <section class="lower-grid">

            <!-- =================================================
                PLAYER OF GAME
            ================================================== -->
            <div class="info-card player-award-card">

                <div class="info-header">
                    <div>
                        <span class="eyebrow">GAME AWARD</span>
                        <h2>Player of the Game</h2>
                    </div>

                    <i class="fas fa-star header-icon"></i>
                </div>

                <div
                    v-if="bestPlayer"
                    class="player-award"
                >
                    <div class="player-award-top">

                        <div class="player-avatar">
                            <i class="fas fa-user"></i>
                        </div>

                        <div class="player-award-name">
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
                            class="role-badge"
                            :class="roleBadgeClass(bestPlayer.role)"
                        >
                            {{ bestPlayer.role }}
                        </span>
                    </div>

                    <div class="award-stat-grid">

                        <div
                            v-if="bestPlayer.points > 0"
                            class="award-stat featured"
                        >
                            <strong>{{ bestPlayer.points }}</strong>
                            <span>PTS</span>
                        </div>

                        <div
                            v-if="bestPlayer.rebounds > 0"
                            class="award-stat"
                        >
                            <strong>{{ bestPlayer.rebounds }}</strong>
                            <span>REB</span>
                        </div>

                        <div
                            v-if="bestPlayer.assists > 0"
                            class="award-stat"
                        >
                            <strong>{{ bestPlayer.assists }}</strong>
                            <span>AST</span>
                        </div>

                        <div
                            v-if="bestPlayer.steals > 3"
                            class="award-stat"
                        >
                            <strong>{{ bestPlayer.steals }}</strong>
                            <span>STL</span>
                        </div>

                        <div
                            v-if="bestPlayer.blocks > 3"
                            class="award-stat"
                        >
                            <strong>{{ bestPlayer.blocks }}</strong>
                            <span>BLK</span>
                        </div>

                        <div
                            v-if="bestPlayer.eff > 5"
                            class="award-stat"
                        >
                            <strong>{{ bestPlayer.eff }}</strong>
                            <span>EFF</span>
                        </div>
                    </div>

                    <div class="award-tags">

                        <span
                            v-if="bestPlayer.is_finals_mvp"
                            class="award-tag"
                        >
                            <i class="fas fa-medal"></i>
                            Finals MVP
                        </span>

                        <span
                            v-if="bestPlayer.is_season_mvp"
                            class="award-tag"
                        >
                            <i class="fas fa-star"></i>
                            Season MVP
                        </span>

                        <span
                            v-if="bestPlayer.is_defensive_poy"
                            class="award-tag"
                        >
                            <i class="fas fa-shield-alt"></i>
                            Defensive Player
                        </span>

                        <span
                            v-if="bestPlayer.is_rookie_poy"
                            class="award-tag"
                        >
                            <i class="fas fa-medal"></i>
                            Rookie of the Season
                        </span>

                        <span
                            v-if="bestPlayer.is_all_rookie"
                            class="award-tag"
                        >
                            <i class="fas fa-medal"></i>
                            All-Rookie
                        </span>

                        <span
                            v-if="bestPlayer.is_most_improved"
                            class="award-tag"
                        >
                            <i class="fas fa-chart-line"></i>
                            MIP
                        </span>

                        <span
                            v-if="bestPlayer.is_sixth_man"
                            class="award-tag"
                        >
                            <i class="fas fa-user"></i>
                            Sixth Man
                        </span>
                    </div>

                    <div class="draft-line">
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

            <!-- =================================================
                INSIGHTS
            ================================================== -->
            <div class="info-card insights-card">

                <div class="info-header">

                    <div>
                        <span class="eyebrow">GAME INSIGHTS</span>

                        <h2>
                            {{
                                showGameNews
                                    ? "Game News"
                                    : "Stat Leaders"
                            }}
                        </h2>
                    </div>

                    <button
                        class="switch-button"
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

                <!-- NEWS -->
                <div
                    v-if="showGameNews"
                    class="news-container"
                >
                    <GameNews
                        :key="gameNews.id"
                        :data="gameNews"
                        :showNews="true"
                    />
                </div>

                <!-- LEADERS -->
                <div
                    v-else
                    class="leaders"
                >
                    <div
                        v-if="statLeaders.points"
                        class="leader"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-basketball-ball"></i>
                        </div>

                        <div class="leader-details">
                            <span>POINTS</span>

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

                        <b>
                            {{ statLeaders.points.points }}
                        </b>
                    </div>

                    <div
                        v-if="statLeaders.assists"
                        class="leader"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-hands"></i>
                        </div>

                        <div class="leader-details">
                            <span>ASSISTS</span>

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

                        <b>
                            {{ statLeaders.assists.assists }}
                        </b>
                    </div>

                    <div
                        v-if="statLeaders.rebounds"
                        class="leader"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>

                        <div class="leader-details">
                            <span>REBOUNDS</span>

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

                        <b>
                            {{ statLeaders.rebounds.rebounds }}
                        </b>
                    </div>

                    <div
                        v-if="
                            statLeaders.steals &&
                            statLeaders.steals.steals > 0
                        "
                        class="leader"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>

                        <div class="leader-details">
                            <span>STEALS</span>

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

                        <b>
                            {{ statLeaders.steals.steals }}
                        </b>
                    </div>

                    <div
                        v-if="
                            statLeaders.blocks &&
                            statLeaders.blocks.blocks > 0
                        "
                        class="leader"
                    >
                        <div class="leader-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>

                        <div class="leader-details">
                            <span>BLOCKS</span>

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

                        <b>
                            {{ statLeaders.blocks.blocks }}
                        </b>
                    </div>
                </div>

                <!-- BREAKING NEWS -->
                <div class="breaking">

                    <div class="breaking-head">
                        <span>
                            <i class="fas fa-bolt"></i>
                            BREAKING
                        </span>
                    </div>

                    <div class="breaking-content">

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
        maxWidth="6xl"
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
import {
    ref,
    computed,
    onMounted,
    onUnmounted
} from "vue";

import axios from "axios";

import {
    roundNameFormatter,
    roleBadgeClass,
    playerFormatter,
    boxScoreRoleBadgeClass
} from "@/Utility/Formatter";

import Modal from "@/Components/Modal.vue";
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

    showGameNews: {
        type: Boolean,
        default: false,
    }
});

const showPlayerProfileModal = ref(false);

const gameDetails = ref(false);

const playerStats = ref({
    home: [],
    away: []
});

const bestPlayer = ref(null);
const statLeaders = ref([]);
const injuredPlayers = ref([]);
const seasonLeaders = ref([]);
const showGameNews = ref(false);
const showAwayDepthChart = ref(false);
const showHomeDepthChart = ref(false);
const gameNews = ref([]);
const breakDown = ref([]);
const isOvertime = ref(0);
const quarter = ref(0);

const time = ref(0);
const interval = ref(null);
const gameFinished = ref(false);

/*
|--------------------------------------------------------------------------
| TIMER
|--------------------------------------------------------------------------
*/

const startTimer = () => {
    if (interval.value) return;

    interval.value = setInterval(() => {
        time.value++;
    }, 1000);
};

const stopTimer = () => {
    if (interval.value) {
        clearInterval(interval.value);
        interval.value = null;
    }
};

const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return `${String(minutes).padStart(2, "0")}:${String(
        remainingSeconds
    ).padStart(2, "0")}`;
};

/*
|--------------------------------------------------------------------------
| BOX SCORE
|--------------------------------------------------------------------------
*/

const switchQuarter = async (activeQuarter) => {
    quarter.value = activeQuarter;

    await fetchBoxScore();
};

const fetchBoxScore = async () => {
    try {
        gameFinished.value = false;

        startTimer();

        gameDetails.value = false;

        const response = await axios.post(
            route("game.boxscore"),
            {
                game_id: props.game_id,
                show_stats: props.showBoxScore,
                season_id: props.season_id,
                quarter: quarter.value
            }
        );

        const data = response.data.box_score;

        gameDetails.value = data;

        playerStats.value.home =
            data.player_stats.home ?? [];

        playerStats.value.away =
            data.player_stats.away ?? [];

        bestPlayer.value =
            data.best_player ?? null;

        statLeaders.value =
            data.stat_leaders ?? {};

        injuredPlayers.value =
            data.injury ?? [];

        seasonLeaders.value =
            data.league_leaders ?? [];

        gameNews.value =
            data.news ?? {};

        breakDown.value =
            data.per_quarter_breakdown ?? [];

        isOvertime.value =
            Number(data.is_overtime ?? 0);

        gameFinished.value = true;

    } catch (error) {
        console.error(
            "Error fetching box score:",
            error
        );
    }
};

/*
|--------------------------------------------------------------------------
| PLAYER SORTING
|--------------------------------------------------------------------------
*/

const sortedHomePlayers = computed(() => {
    return playerStats.value.home
        .slice()
        .sort((a, b) => b.points - a.points);
});

const sortedAwayPlayers = computed(() => {
    return playerStats.value.away
        .slice()
        .sort((a, b) => b.points - a.points);
});

const top5HomePlayers = computed(() => {
    return sortedHomePlayers.value
        .slice(0, 5)
        .map(player => player.name);
});

const top5AwayPlayers = computed(() => {
    return sortedAwayPlayers.value
        .slice(0, 5)
        .map(player => player.name);
});

/*
|--------------------------------------------------------------------------
| INJURY NEWS
|--------------------------------------------------------------------------
*/

const messageMap = new Map();

const formatInjuredPlayers = (player) => {
    if (!messageMap.has(player.player_id)) {

        const messages = [
            `🚨 ${player.player_name} ${player.position} (${player.team_when_injured}) - ${player.injury_type.replaceAll("_", " ")} | ${player.role} | Estimated recovery ${player.recovery_games} days.`,

            `⚠️ ${player.team_when_injured}'s ${player.player_name} ${player.position} - ${player.injury_type.replaceAll("_", " ")} | ${player.role} | Estimated recovery ${player.recovery_games} days.`,

            `🏥 ${player.player_name} ${player.position} (${player.team_when_injured}) - ${player.injury_type.replaceAll("_", " ")} | ${player.role} | Will miss ${player.recovery_games} days.`,

            `⛔ ${player.team_when_injured} loses ${player.player_name} ${player.position} - ${player.injury_type.replaceAll("_", " ")} | ${player.role} | ${player.recovery_games} days estimated recovery.`
        ];

        const randomMessage =
            messages[
                Math.floor(
                    Math.random() * messages.length
                )
            ];

        messageMap.set(
            player.player_id,
            randomMessage
        );
    }

    return (
        messageMap.get(player.player_id) +
        " • "
    );
};

/*
|--------------------------------------------------------------------------
| LIFECYCLE
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchBoxScore();

    showGameNews.value =
        props.showGameNews;
});

onUnmounted(() => {
    stopTimer();
});
</script>

<style scoped>
/* =========================================================
   PAGE
========================================================= */

.game-page {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top,
            rgba(255,255,255,.035),
            transparent 32%
        ),
        #090b0f;
    color: #e5e7eb;
    padding: 18px;
}

.game-page > * {
    max-width: 1600px;
    margin-left: auto;
    margin-right: auto;
}

/* =========================================================
   LOADING
========================================================= */

.loading-page {
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-panel {
    width: min(520px, 94vw);
    background: #111318;
    border: 1px solid #252932;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 25px 60px rgba(0,0,0,.45);
}

.loading-logo {
    display: flex;
    align-items: center;
    gap: 14px;
}

.loading-ball {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #181b21;
    color: #f59e0b;
    font-size: 19px;
}

.loading-logo span,
.loading-logo strong {
    display: block;
}

.loading-logo span {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .12em;
    color: #9ca3af;
}

.loading-logo strong {
    margin-top: 3px;
    font-size: 13px;
    color: white;
}

.loading-score {
    display: grid;
    grid-template-columns: 1fr 100px 1fr;
    gap: 14px;
    align-items: center;
    margin: 35px 0;
}

.skeleton {
    background:
        linear-gradient(
            90deg,
            #17191e 25%,
            #23262d 50%,
            #17191e 75%
        );
    background-size: 200% 100%;
    animation: skeleton 1.5s infinite;
    border-radius: 8px;
}

.skeleton-team {
    height: 42px;
}

.skeleton-score {
    height: 65px;
}

@keyframes skeleton {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

.loading-message {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
    font-size: 12px;
}

.loading-dot,
.final-badge span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 8px rgba(34,197,94,.7);
}

.loading-time {
    text-align: center;
    margin-top: 16px;
    color: #6b7280;
    font-family: monospace;
    font-size: 12px;
}

/* =========================================================
   GAME HEADER
========================================================= */

.game-header {
    background: #101217;
    border: 1px solid #24272e;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 18px 45px rgba(0,0,0,.28);
}

.header-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 18px;
    border-bottom: 1px solid #20232a;
}

.league-name,
.season-name {
    display: block;
}

.league-name {
    font-size: 11px;
    font-weight: 800;
    color: #f3f4f6;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.season-name {
    margin-top: 3px;
    color: #6b7280;
    font-size: 10px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.final-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 5px 9px;
    border-radius: 6px;
    background: rgba(34,197,94,.08);
    border: 1px solid rgba(34,197,94,.18);
    color: #86efac;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .08em;
}

.game-number {
    color: #6b7280;
    font-size: 10px;
    font-family: monospace;
}

/* =========================================================
   SCOREBOARD
========================================================= */

.scoreboard {
    display: grid;
    grid-template-columns: 1fr 180px 1fr;
    min-height: 310px;
}

.score-team {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
    overflow: hidden;
}

.score-team::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            135deg,
            color-mix(
                in srgb,
                var(--team-primary) 13%,
                transparent
            ),
            transparent 55%
        );
    pointer-events: none;
}

.score-team.away::before {
    transform: scaleX(-1);
}

.score-team.winner {
    background:
        linear-gradient(
            180deg,
            rgba(255,255,255,.025),
            rgba(255,255,255,.005)
        );
}

.location-label {
    position: absolute;
    top: 18px;
    left: 20px;
    color: #6b7280;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .12em;
}

.away .location-label {
    left: auto;
    right: 20px;
}

.team-number {
    color: #4b5563;
    font-size: 9px;
    font-family: monospace;
    margin-bottom: 4px;
}

.team-name {
    position: relative;
    z-index: 1;
    max-width: 100%;
    text-align: center;
}

.team-name :deep(*) {
    color: #f9fafb !important;
}

.score {
    position: relative;
    z-index: 1;
    margin: 8px 0;
    color: white;
    font-size: clamp(48px, 6vw, 76px);
    line-height: .9;
    font-weight: 900;
    letter-spacing: -.06em;
}

.winner .score {
    text-shadow:
        0 0 28px
        color-mix(
            in srgb,
            var(--team-primary) 40%,
            transparent
        );
}

.team-record {
    position: relative;
    z-index: 1;
    color: #9ca3af;
    font-size: 11px;
}

.team-meta {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 10px;
    margin-top: 20px;
    max-width: 100%;
    color: #6b7280;
    font-size: 9px;
}

.team-meta span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.team-meta i {
    margin-right: 4px;
}

.winner-label {
    position: absolute;
    bottom: 15px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 5px;
    background: rgba(245,158,11,.1);
    border: 1px solid rgba(245,158,11,.2);
    color: #fbbf24;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .08em;
}

/* =========================================================
   SCORE CENTER
========================================================= */

.score-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-left: 1px solid #20232a;
    border-right: 1px solid #20232a;
    padding: 20px 10px;
}

.game-context {
    color: #9ca3af;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .13em;
}

.round-title {
    margin-top: 5px;
    color: #f3f4f6;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.vs {
    width: 42px;
    height: 42px;
    margin: 18px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #181b21;
    border: 1px solid #30343c;
}

.vs span {
    color: #9ca3af;
    font-size: 9px;
    font-weight: 900;
}

.head-to-head {
    text-align: center;
}

.head-to-head span,
.head-to-head strong {
    display: block;
}

.head-to-head span {
    color: #4b5563;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .1em;
}

.head-to-head strong {
    margin-top: 5px;
    color: #d1d5db;
    font-size: 13px;
}

/* =========================================================
   GAME FLOW
========================================================= */

.game-flow {
    border-top: 1px solid #20232a;
    padding: 14px 18px 16px;
}

.flow-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.flow-header span:first-child {
    display: block;
    color: #6b7280;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .1em;
}

.flow-header strong {
    display: block;
    margin-top: 2px;
    color: #d1d5db;
    font-size: 11px;
}

.overtime-badge {
    padding: 4px 7px;
    border-radius: 5px;
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.2);
    color: #fca5a5 !important;
    font-size: 8px !important;
}

.flow-table-wrapper {
    overflow-x: auto;
}

.flow-table {
    width: 100%;
    min-width: 500px;
    border-collapse: collapse;
}

.flow-table th {
    padding: 6px 8px;
    color: #4b5563;
    font-size: 8px;
    font-weight: 800;
    text-align: center;
}

.flow-table th:first-child {
    text-align: left;
}

.flow-table td {
    padding: 8px;
    border-top: 1px solid #1d2026;
    color: #9ca3af;
    font-size: 10px;
    text-align: center;
}

.flow-table td:first-child {
    color: #d1d5db;
    font-weight: 600;
    text-align: left;
}

.flow-table .total-score {
    color: white;
    font-weight: 900;
}

.total-column {
    color: #9ca3af !important;
}

/* =========================================================
   RATINGS
========================================================= */

.ratings-panel {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 20px;
    margin-top: 12px;
    padding: 14px 18px;
    background: #101217;
    border: 1px solid #24272e;
    border-radius: 14px;
}

.ratings-team {
    display: flex;
    align-items: center;
    gap: 18px;
}

.ratings-team.away {
    justify-content: flex-end;
}

.ratings-team > span {
    color: #d1d5db;
    font-size: 11px;
    font-weight: 700;
}

.ratings {
    display: flex;
    gap: 5px;
}

.ratings > div {
    min-width: 45px;
    padding: 5px;
    border-radius: 6px;
    background: #17191f;
    text-align: center;
}

.ratings strong,
.ratings small {
    display: block;
}

.ratings strong {
    color: #f3f4f6;
    font-size: 12px;
}

.ratings small {
    margin-top: 1px;
    color: #6b7280;
    font-size: 7px;
    font-weight: 800;
}

.ratings-title {
    color: #4b5563;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .1em;
}

/* =========================================================
   MAIN CONTENT
========================================================= */

.content-section {
    margin-top: 14px;
}

.section-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 10px;
}

.eyebrow {
    display: block;
    color: #6b7280;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: .12em;
}

.section-top h2,
.info-header h2 {
    margin: 3px 0 0;
    color: #f3f4f6;
    font-size: 16px;
    font-weight: 800;
}

.quarter-tabs {
    display: flex;
    padding: 3px;
    gap: 2px;
    background: #111318;
    border: 1px solid #24272e;
    border-radius: 8px;
}

.quarter-tabs button {
    border: 0;
    background: transparent;
    color: #6b7280;
    padding: 6px 9px;
    border-radius: 5px;
    font-size: 8px;
    font-weight: 800;
    cursor: pointer;
    transition: .15s ease;
}

.quarter-tabs button:hover {
    color: #d1d5db;
}

.quarter-tabs button.active {
    background: #272a31;
    color: white;
}

/* =========================================================
   BOX SCORES
========================================================= */

.boxscore-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.box-card {
    min-width: 0;
    background: #101217;
    border: 1px solid #24272e;
    border-radius: 14px;
    overflow: hidden;
}

.box-card-header {
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 12px;
    padding: 13px 15px;
    border-bottom: 1px solid #20232a;
}

.box-card-header::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--team-primary);
}

.side-tag {
    color: #6b7280;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: .1em;
}

.box-card-header h3 {
    margin: 2px 0 0;
    color: #f3f4f6;
    font-size: 13px;
}

.box-score {
    color: white;
    font-size: 22px;
    font-weight: 900;
}

.icon-button,
.switch-button {
    width: 31px;
    height: 31px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #2b2f37;
    border-radius: 7px;
    background: #17191f;
    color: #9ca3af;
    cursor: pointer;
    transition: .15s ease;
}

.icon-button:hover,
.switch-button:hover {
    color: white;
    background: #20232a;
}

/* =========================================================
   TABLE
========================================================= */

.table-scroll {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}

.player-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

.player-table th {
    padding: 8px 7px;
    background: #0c0e12;
    color: #4b5563;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: .05em;
    text-align: center;
    white-space: nowrap;
}

.player-table th:first-child {
    text-align: left;
    padding-left: 13px;
}

.player-table td {
    padding: 8px 7px;
    border-top: 1px solid #1c1f25;
    color: #9ca3af;
    font-size: 9px;
    text-align: center;
    white-space: nowrap;
}

.player-table tbody tr {
    cursor: pointer;
    transition: background .12s ease;
}

.player-table tbody tr:hover {
    background: rgba(255,255,255,.025);
}

.player-table tbody tr.top-player {
    background: rgba(255,255,255,.018);
}

.player-name-cell {
    min-width: 145px;
    text-align: left !important;
    padding-left: 13px !important;
}

.player-name-cell > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.player-name-cell span {
    overflow: hidden;
    text-overflow: ellipsis;
    color: #e5e7eb;
    font-weight: 600;
}

.player-name-cell small {
    color: #4b5563;
    font-size: 7px;
}

.number {
    font-variant-numeric: tabular-nums;
}

.points {
    color: #f3f4f6 !important;
    font-weight: 800;
}

.positive-stat {
    color: #86efac;
}

.negative-stat {
    color: #f87171;
}

.injury-icon {
    color: #ef4444;
    margin-left: 3px;
}

.fouled-icon {
    color: #f59e0b;
    margin-left: 3px;
}

.empty-row {
    padding: 30px !important;
    color: #4b5563 !important;
}

/* =========================================================
   LOWER CONTENT
========================================================= */

.lower-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
    gap: 12px;
    margin-top: 14px;
}

.info-card {
    min-width: 0;
    background: #101217;
    border: 1px solid #24272e;
    border-radius: 14px;
    overflow: hidden;
}

.info-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #20232a;
}

.header-icon {
    color: #f59e0b;
}

.player-award {
    padding: 16px;
}

.player-award-top {
    display: flex;
    align-items: center;
    gap: 12px;
}

.player-avatar {
    width: 43px;
    height: 43px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #181b21;
    border: 1px solid #2a2e36;
    color: #6b7280;
}

.player-award-name {
    min-width: 0;
    flex: 1;
}

.player-award-name h3 {
    margin: 0;
    color: #f3f4f6;
    font-size: 13px;
}

.player-award-name span {
    display: block;
    margin-top: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #6b7280;
    font-size: 9px;
}

.role-badge {
    flex-shrink: 0;
    padding: 4px 6px;
    border-radius: 4px;
    font-size: 7px;
    font-weight: 900;
}

.award-stat-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 5px;
    margin-top: 16px;
}

.award-stat {
    padding: 8px 4px;
    border-radius: 7px;
    background: #17191f;
    text-align: center;
}

.award-stat strong,
.award-stat span {
    display: block;
}

.award-stat strong {
    color: #d1d5db;
    font-size: 13px;
}

.award-stat span {
    margin-top: 2px;
    color: #4b5563;
    font-size: 7px;
    font-weight: 800;
}

.award-stat.featured {
    background: rgba(245,158,11,.08);
    border: 1px solid rgba(245,158,11,.15);
}

.award-stat.featured strong {
    color: #fbbf24;
}

.award-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 12px;
}

.award-tag {
    padding: 4px 7px;
    border-radius: 5px;
    background: #181b21;
    border: 1px solid #292d35;
    color: #9ca3af;
    font-size: 7px;
    font-weight: 700;
}

.award-tag i {
    margin-right: 3px;
}

.draft-line {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 13px;
    padding-top: 11px;
    border-top: 1px solid #20232a;
    color: #6b7280;
    font-size: 8px;
}

.draft-line i {
    color: #818cf8;
}

/* =========================================================
   LEADERS
========================================================= */

.leaders {
    padding: 7px 10px;
}

.leader {
    display: grid;
    grid-template-columns: 32px 1fr auto;
    align-items: center;
    gap: 9px;
    padding: 9px 6px;
    border-bottom: 1px solid #1d2026;
}

.leader:last-child {
    border-bottom: 0;
}

.leader-icon {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    background: #181b21;
    color: #9ca3af;
    font-size: 10px;
}

.leader-details {
    min-width: 0;
}

.leader-details span,
.leader-details strong,
.leader-details small {
    display: block;
}

.leader-details span {
    color: #4b5563;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: .08em;
}

.leader-details strong {
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #d1d5db;
    font-size: 10px;
}

.leader-details small {
    margin-top: 2px;
    color: #6b7280;
    font-size: 8px;
}

.leader > b {
    color: #f3f4f6;
    font-size: 17px;
}

/* =========================================================
   BREAKING
========================================================= */

.breaking {
    margin: 10px;
    border: 1px solid rgba(239,68,68,.16);
    border-radius: 8px;
    overflow: hidden;
    background: rgba(239,68,68,.035);
}

.breaking-head {
    padding: 6px 9px;
    border-bottom: 1px solid rgba(239,68,68,.12);
}

.breaking-head span {
    color: #f87171;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: .1em;
}

.breaking-content {
    padding: 8px 9px;
    overflow: hidden;
}

.breaking-content p {
    margin: 0;
    color: #9ca3af;
    font-size: 8px;
}

.breaking-marquee {
    display: flex;
    gap: 30px;
    width: max-content;
    animation: marquee 28s linear infinite;
}

.breaking-marquee span {
    color: #9ca3af;
    font-size: 8px;
}

@keyframes marquee {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }
}

/* =========================================================
   SEASON LEADER
========================================================= */

.season-leader {
    margin: 10px;
    padding: 10px;
    border-radius: 8px;
    background: #17191f;
}

.season-leader span,
.season-leader strong,
.season-leader small {
    display: block;
}

.season-leader span {
    color: #6b7280;
    font-size: 8px;
}

.season-leader strong {
    margin-top: 3px;
    color: #e5e7eb;
    font-size: 11px;
}

.season-leader small {
    margin-top: 2px;
    color: #4b5563;
    font-size: 8px;
}

/* =========================================================
   TIMER
========================================================= */

.game-timer {
    width: fit-content;
    margin-top: 14px !important;
    padding: 7px 10px;
    border: 1px solid #24272e;
    border-radius: 7px;
    background: #101217;
    color: #6b7280;
    font-family: monospace;
    font-size: 9px;
}

.game-timer i {
    margin-right: 5px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {
    .scoreboard {
        grid-template-columns: 1fr 140px 1fr;
    }

    .lower-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    .game-page {
        padding: 10px;
    }

    .scoreboard {
        grid-template-columns: 1fr 90px 1fr;
        min-height: 270px;
    }

    .score {
        font-size: 48px;
    }

    .team-meta {
        flex-direction: column;
        gap: 3px;
        align-items: center;
    }

    .ratings-panel {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .ratings-title {
        order: -1;
    }

    .ratings-team.away {
        justify-content: flex-start;
    }
}

@media (max-width: 620px) {
    .header-meta {
        align-items: flex-start;
    }

    .header-right {
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
    }

    .scoreboard {
        grid-template-columns: 1fr;
    }

    .score-center {
        order: 2;
        min-height: 100px;
        border-top: 1px solid #20232a;
        border-bottom: 1px solid #20232a;
        border-left: 0;
        border-right: 0;
    }

    .score-team {
        min-height: 220px;
    }

    .score-team.away {
        order: 3;
    }

    .home {
        order: 1;
    }

    .vs {
        margin: 8px 0;
    }

    .team-meta {
        display: none;
    }

    .section-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .quarter-tabs {
        width: 100%;
        overflow-x: auto;
    }

    .quarter-tabs button {
        flex: 1;
        min-width: 42px;
    }

    .boxscore-grid {
        grid-template-columns: 1fr;
    }

    .award-stat-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .player-award-top {
        align-items: flex-start;
    }

    .role-badge {
        display: none;
    }
}

@media (max-width: 420px) {
    .game-page {
        padding: 6px;
    }

    .game-header,
    .box-card,
    .info-card,
    .ratings-panel {
        border-radius: 10px;
    }

    .header-meta {
        padding: 11px;
    }

    .score-team {
        min-height: 200px;
        padding: 25px 12px;
    }

    .score {
        font-size: 44px;
    }

    .team-name {
        max-width: 90%;
    }

    .player-table {
        min-width: 700px;
    }

    .award-stat-grid {
        gap: 3px;
    }

    .award-stat strong {
        font-size: 12px;
    }
}
</style>

