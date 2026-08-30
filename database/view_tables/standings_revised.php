CREATE OR REPLACE VIEW standings_view AS

WITH latest_season AS (
    SELECT MAX(id) AS season_id
    FROM seasons
),

/* =========================================================
   NEXT SCHEDULED GAME
   ========================================================= */

next_games AS (
    SELECT
        t.id AS team_id,
        s.id AS game_id,
        s.home_id,
        s.away_id,

        ROW_NUMBER() OVER (
            PARTITION BY t.id
            ORDER BY s.id ASC
        ) AS rn

    FROM teams t

    JOIN schedules s
        ON (
            s.home_id = t.id
            OR s.away_id = t.id
        )
        AND s.status = 1
        AND s.season_id = (
            SELECT season_id
            FROM latest_season
        )
),

/* =========================================================
   COMPLETED REGULAR-SEASON GAMES
   Used for streaks and last 5 games.
   ========================================================= */

team_games AS (
    SELECT
        teams.id AS team_id,
        teams.name AS team_name,
        teams.city AS team_city,

        teams.primary_color,
        teams.secondary_color,
        teams.acronym AS team_acronym,

        teams.conference_id,
        conferences.name AS conference_name,

        schedules.id AS game_id,
        schedules.season_id,
        schedules.round,

        schedules.id AS game_date,

        CASE
            WHEN schedules.winner_id = teams.id
                THEN 'W'

            WHEN schedules.winner_id IS NOT NULL
                 AND schedules.winner_id <> teams.id
                THEN 'L'

            ELSE NULL
        END AS game_result

    FROM teams

    LEFT JOIN schedules
        ON (
            schedules.home_id = teams.id
            OR schedules.away_id = teams.id
        )
        AND schedules.status = 2
        AND schedules.season_id = (
            SELECT season_id
            FROM latest_season
        )

    LEFT JOIN conferences
        ON teams.conference_id = conferences.id

    WHERE
        schedules.id IS NULL

        OR schedules.round NOT IN (
            'play_ins_elims_round_1',
            'play_ins_elims_round_2',
            'play_ins_finals',
            'round_of_32',
            'round_of_16',
            'quarter_finals',
            'semi_finals',
            'interconference_semi_finals',
            'finals'
        )
),

/* =========================================================
   LAST 5 GAMES
   ========================================================= */

last_five_games AS (
    SELECT
        team_id,
        season_id,

        GROUP_CONCAT(
            game_result
            ORDER BY game_date DESC
            SEPARATOR ''
        ) AS last_5

    FROM (
        SELECT
            team_id,
            season_id,
            game_result,
            game_date,

            ROW_NUMBER() OVER (
                PARTITION BY team_id, season_id
                ORDER BY game_date DESC
            ) AS rn

        FROM team_games

        WHERE game_result IS NOT NULL
    ) AS ranked_games

    WHERE rn <= 5

    GROUP BY
        team_id,
        season_id
),

/* =========================================================
   STREAK GROUPS
   ========================================================= */

streaks AS (
    SELECT
        team_id,
        season_id,
        game_result,

        COUNT(*) AS streak_length,
        MAX(game_id) AS last_game_id

    FROM (
        SELECT
            team_id,
            season_id,
            game_result,
            game_id,

            ROW_NUMBER() OVER (
                PARTITION BY team_id, season_id
                ORDER BY game_id
            )
            -
            ROW_NUMBER() OVER (
                PARTITION BY team_id, season_id, game_result
                ORDER BY game_id
            ) AS streak_id

        FROM team_games

        WHERE game_result IS NOT NULL
    ) AS streak_groups

    GROUP BY
        team_id,
        season_id,
        game_result,
        streak_id
),

/* =========================================================
   CURRENT STREAK
   ========================================================= */

latest_streak AS (
    SELECT
        team_id,
        season_id,
        game_result,
        streak_length

    FROM (
        SELECT
            team_id,
            season_id,
            game_result,
            streak_length,
            last_game_id,

            ROW_NUMBER() OVER (
                PARTITION BY team_id, season_id
                ORDER BY last_game_id DESC
            ) AS rn

        FROM streaks
    ) AS ranked_streaks

    WHERE rn = 1
),

/* =========================================================
   TEAM REGULAR-SEASON STATISTICS
   ========================================================= */

team_rankings AS (
    SELECT

        teams.id AS team_id,
        teams.name AS team_name,
        teams.city AS team_city,

        teams.acronym AS team_acronym,

        teams.primary_color,
        teams.secondary_color,

        teams.conference_id,
        conferences.name AS conference_name,

        (
            SELECT season_id
            FROM latest_season
        ) AS season_id,

        /* =====================================================
           WINS
           ===================================================== */

        COALESCE(
            SUM(
                CASE
                    WHEN schedules.winner_id = teams.id
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS wins,

        /* =====================================================
           LOSSES
           ===================================================== */

        COALESCE(
            SUM(
                CASE
                    WHEN schedules.winner_id IS NOT NULL
                         AND schedules.winner_id <> teams.id
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS losses,

        /* =====================================================
           HOME POINTS SCORED
           ===================================================== */

        COALESCE(
            SUM(
                CASE
                    WHEN schedules.home_id = teams.id
                    THEN COALESCE(schedules.home_score, 0)
                    ELSE 0
                END
            ),
            0
        ) AS total_home_score,

        /* =====================================================
           AWAY POINTS SCORED
           ===================================================== */

        COALESCE(
            SUM(
                CASE
                    WHEN schedules.away_id = teams.id
                    THEN COALESCE(schedules.away_score, 0)
                    ELSE 0
                END
            ),
            0
        ) AS total_away_score,

        /* =====================================================
           TOTAL POINTS FOR
           ===================================================== */

        COALESCE(
            SUM(
                CASE

                    WHEN schedules.home_id = teams.id
                        THEN COALESCE(schedules.home_score, 0)

                    WHEN schedules.away_id = teams.id
                        THEN COALESCE(schedules.away_score, 0)

                    ELSE 0

                END
            ),
            0
        ) AS total_points_for,

        /* =====================================================
           TOTAL POINTS AGAINST
           ===================================================== */

        COALESCE(
            SUM(
                CASE

                    WHEN schedules.home_id = teams.id
                        THEN COALESCE(schedules.away_score, 0)

                    WHEN schedules.away_id = teams.id
                        THEN COALESCE(schedules.home_score, 0)

                    ELSE 0

                END
            ),
            0
        ) AS total_points_against,

        /* =====================================================
           HOME PPG
           ===================================================== */

        ROUND(

            COALESCE(
                SUM(
                    CASE
                        WHEN schedules.home_id = teams.id
                        THEN COALESCE(schedules.home_score, 0)
                        ELSE 0
                    END
                ),
                0
            )

            /

            NULLIF(
                SUM(
                    CASE
                        WHEN schedules.home_id = teams.id
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ),

            2

        ) AS home_ppg,

        /* =====================================================
           AWAY PPG
           ===================================================== */

        ROUND(

            COALESCE(
                SUM(
                    CASE
                        WHEN schedules.away_id = teams.id
                        THEN COALESCE(schedules.away_score, 0)
                        ELSE 0
                    END
                ),
                0
            )

            /

            NULLIF(
                SUM(
                    CASE
                        WHEN schedules.away_id = teams.id
                        THEN 1
                        ELSE 0
                    END
                ),
                0
            ),

            2

        ) AS away_ppg

    FROM teams

    LEFT JOIN schedules
        ON (
            schedules.home_id = teams.id
            OR schedules.away_id = teams.id
        )

        AND schedules.season_id = (
            SELECT season_id
            FROM latest_season
        )

        AND schedules.status = 2

        /* IMPORTANT:
           Only regular-season games */
        AND schedules.round NOT IN (
            'play_ins_elims_round_1',
            'play_ins_elims_round_2',
            'play_ins_finals',
            'round_of_32',
            'round_of_16',
            'quarter_finals',
            'semi_finals',
            'interconference_semi_finals',
            'finals'
        )

    LEFT JOIN conferences
        ON teams.conference_id = conferences.id

    GROUP BY
        teams.id,
        teams.name,
        teams.city,
        teams.acronym,
        teams.primary_color,
        teams.secondary_color,
        teams.conference_id,
        conferences.name
),

/* =========================================================
   RANK TEAMS
   ========================================================= */

ranked_team_rankings AS (
    SELECT

        team_id,
        team_name,
        team_city,
        primary_color,
        secondary_color,
        team_acronym,

        conference_id,
        conference_name,

        season_id,

        wins,
        losses,

        total_home_score,
        total_away_score,

        total_points_for,
        total_points_against,

        home_ppg,
        away_ppg,

        /* =====================================================
           TRUE POINT DIFFERENTIAL
           ===================================================== */

        (
            CEIL((total_points_for) / (wins + losses))
        ) AS total_points_for_avg,

        (
            CEIL((total_points_against) / (wins + losses))
        ) AS total_points_against_avg,

        (
            CEIL((total_points_for
            -
            total_points_against) / (wins + losses))
        ) AS score_difference,

        /* =====================================================
           CONFERENCE RANK
           ===================================================== */

        RANK() OVER (
            PARTITION BY season_id, conference_id

            ORDER BY
                wins DESC,

                (
                    total_points_for
                    -
                    total_points_against
                ) DESC,

                home_ppg DESC,
                away_ppg DESC
        ) AS conference_rank,

        /* =====================================================
           OVERALL RANK
           ===================================================== */

        RANK() OVER (
            PARTITION BY season_id

            ORDER BY
                wins DESC,

                (
                    total_points_for
                    -
                    total_points_against
                ) DESC
        ) AS overall_rank

    FROM team_rankings
),

/* =========================================================
   LAST PLAYOFF APPEARANCE
   ========================================================= */

last_playoff_appearance AS (
    SELECT
        team_id,
        MAX(season_id) AS last_playoff_season_id

    FROM team_season_info

    WHERE is_playoff_qualified = 1

    GROUP BY team_id
),

/* =========================================================
   SEASON LIST
   ========================================================= */

seasons_list AS (
    SELECT
        id,
        name AS season_name

    FROM seasons
),

/* =========================================================
   TOTAL PLAYOFF APPEARANCES
   ========================================================= */

playoff_appearances AS (
    SELECT
        team_id,
        COUNT(*) AS playoff_appearances

    FROM team_season_info

    WHERE is_playoff_qualified = 1

    GROUP BY team_id
),

/* =========================================================
   CURRENT TEAM SEASON INFO
   ========================================================= */

current_team_season_info AS (
    SELECT
        team_id,
        season_id,
        is_defending_champion,
        chemistry

    FROM team_season_info

    WHERE season_id = (
        SELECT season_id
        FROM latest_season
    )
),

/* =========================================================
   FINALS APPEARANCES
   ========================================================= */

finals_appearances AS (
    SELECT
        team_id,

        COUNT(DISTINCT season_id)
            AS finals_appearances

    FROM (
        SELECT
            home_team_id AS team_id,
            season_id

        FROM playoff_series

        WHERE round = 'finals'

        UNION

        SELECT
            away_team_id AS team_id,
            season_id

        FROM playoff_series

        WHERE round = 'finals'
    ) AS finals_teams

    GROUP BY team_id
),

/* =========================================================
   CONFERENCE FINALS APPEARANCES
   ========================================================= */

conference_finals_appearances AS (
    SELECT
        team_id,

        COUNT(DISTINCT season_id)
            AS conference_finals_appearances

    FROM (
        SELECT
            home_team_id AS team_id,
            season_id

        FROM playoff_series

        WHERE round = 'semi_finals'

        UNION

        SELECT
            away_team_id AS team_id,
            season_id

        FROM playoff_series

        WHERE round = 'semi_finals'
    ) AS conference_final_teams

    GROUP BY team_id
),

/* =========================================================
   CHAMPIONSHIPS
   ========================================================= */

championships AS (
    SELECT
        winner_team_id AS team_id,

        COUNT(DISTINCT season_id)
            AS championships

    FROM playoff_series

    WHERE
        round = 'finals'
        AND winner_team_id IS NOT NULL

    GROUP BY winner_team_id
),

/* =========================================================
   CONFERENCE CHAMPIONSHIPS
   ========================================================= */

conference_championships AS (
    SELECT
        winner_team_id AS team_id,

        COUNT(DISTINCT season_id)
            AS conference_championships

    FROM playoff_series

    WHERE
        round = 'semi_finals'
        AND winner_team_id IS NOT NULL

    GROUP BY winner_team_id
)

/* =========================================================
   FINAL RESULT
   ========================================================= */

SELECT

    standings.*,

    /* Current season information */

    current_team_season_info.is_defending_champion,
    current_team_season_info.chemistry,

    /* Last playoff appearance */

    COALESCE(
        seasons_list.season_name,
        ''
    ) AS last_playoff_season_name,

    /* Playoff history */

    COALESCE(
        playoff_appearances.playoff_appearances,
        0
    ) AS playoff_appearances,

    COALESCE(
        finals_appearances.finals_appearances,
        0
    ) AS finals_appearances,

    COALESCE(
        conference_finals_appearances.conference_finals_appearances,
        0
    ) AS conference_finals_appearances,

    COALESCE(
        conference_championships.conference_championships,
        0
    ) AS conference_championships,

    COALESCE(
        championships.championships,
        0
    ) AS championships,

    /* Current streak */

    CASE

        WHEN latest_streak.game_result = 'W'
            THEN CONCAT(
                'W',
                latest_streak.streak_length
            )

        WHEN latest_streak.game_result = 'L'
            THEN CONCAT(
                'L',
                latest_streak.streak_length
            )

        ELSE NULL

    END AS streak_status,

    /* Last 5 */

    COALESCE(
        last_five_games.last_5,
        ''
    ) AS last_5_games,

    /* Next opponent */

    opponent.acronym
        AS next_opponent_acronym,

    opponent.name
        AS next_opponent_name

FROM ranked_team_rankings AS standings

/* =========================================================
   NEXT GAME
   ========================================================= */

LEFT JOIN next_games AS ng
    ON standings.team_id = ng.team_id
    AND ng.rn = 1

LEFT JOIN teams AS opponent
    ON (
        (
            ng.home_id = standings.team_id
            AND ng.away_id = opponent.id
        )

        OR

        (
            ng.away_id = standings.team_id
            AND ng.home_id = opponent.id
        )
    )

/* =========================================================
   LAST PLAYOFF
   ========================================================= */

LEFT JOIN last_playoff_appearance

    ON standings.team_id =
       last_playoff_appearance.team_id

LEFT JOIN seasons_list

    ON last_playoff_appearance.last_playoff_season_id =
       seasons_list.id

/* =========================================================
   PLAYOFF APPEARANCES
   ========================================================= */

LEFT JOIN playoff_appearances

    ON standings.team_id =
       playoff_appearances.team_id

/* =========================================================
   CURRENT TEAM SEASON
   ========================================================= */

LEFT JOIN current_team_season_info

    ON standings.team_id =
       current_team_season_info.team_id

    AND standings.season_id =
       current_team_season_info.season_id

/* =========================================================
   FINALS
   ========================================================= */

LEFT JOIN finals_appearances

    ON standings.team_id =
       finals_appearances.team_id

/* =========================================================
   CONFERENCE FINALS
   ========================================================= */

LEFT JOIN conference_finals_appearances

    ON standings.team_id =
       conference_finals_appearances.team_id

/* =========================================================
   CHAMPIONSHIPS
   ========================================================= */

LEFT JOIN championships

    ON standings.team_id =
       championships.team_id

/* =========================================================
   CONFERENCE CHAMPIONSHIPS
   ========================================================= */

LEFT JOIN conference_championships

    ON standings.team_id =
       conference_championships.team_id

/* =========================================================
   STREAK
   ========================================================= */

LEFT JOIN latest_streak

    ON standings.team_id =
       latest_streak.team_id

    AND standings.season_id =
       latest_streak.season_id

/* =========================================================
   LAST 5
   ========================================================= */

LEFT JOIN last_five_games

    ON standings.team_id =
       last_five_games.team_id

    AND standings.season_id =
       last_five_games.season_id;

