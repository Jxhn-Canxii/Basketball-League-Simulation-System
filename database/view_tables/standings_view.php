CREATE OR REPLACE VIEW standings_view AS
WITH team_games AS (
    SELECT
        teams.id AS team_id,
        teams.name AS team_name,
        teams.city AS team_city,
        teams.primary_color AS primary_color,
        teams.secondary_color AS secondary_color,
        teams.acronym AS team_acronym,
        teams.conference_id AS conference_id,
        conferences.name AS conference_name,
        schedules.id AS game_id,
        schedules.season_id,
        schedules.round,
        schedules.id as game_date,
        CASE
            WHEN schedules.winner_id = teams.id THEN 'W'
            WHEN schedules.winner_id IS NOT NULL AND schedules.winner_id <> teams.id THEN 'L'
            ELSE NULL
        END AS game_result
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
    LEFT JOIN conferences
        ON teams.conference_id = conferences.id
    WHERE
        schedules.round NOT IN (
            'play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals',
            'round_of_32', 'round_of_16', 'quarter_finals', 'semi_finals',
            'interconference_semi_finals', 'finals'
        )
        OR schedules.id IS NULL -- <-- IMPORTANT: allow NULL schedules
),

last_five_games AS (
    SELECT
        team_id,
        season_id,
        GROUP_CONCAT(game_result ORDER BY game_date DESC SEPARATOR '') AS last_5
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
    GROUP BY team_id, season_id
),

streaks AS (
    SELECT
        team_id,
        season_id,
        game_result,
        COUNT(*) AS streak_length
    FROM (
        SELECT
            team_id,
            season_id,
            game_result,
            ROW_NUMBER() OVER (PARTITION BY team_id, season_id ORDER BY game_id) -
            ROW_NUMBER() OVER (PARTITION BY team_id, season_id, game_result ORDER BY game_id) AS streak_id
        FROM team_games
    ) AS streak_groups
    WHERE game_result IS NOT NULL
    GROUP BY team_id, season_id, game_result, streak_id
),

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
            ROW_NUMBER() OVER (
                PARTITION BY team_id, season_id
                ORDER BY streak_length DESC
            ) AS rn
        FROM streaks
    ) AS ranked_streaks
    WHERE rn = 1
),

team_rankings AS (
    SELECT
        teams.id AS team_id,
        teams.name AS team_name,
        teams.city AS team_city,
        teams.acronym AS team_acronym,
        teams.primary_color AS primary_color,
        teams.secondary_color AS secondary_color,
        teams.conference_id AS conference_id,
        conferences.name AS conference_name,
        COALESCE(SUM(CASE WHEN schedules.status = 2 AND schedules.winner_id = teams.id THEN 1 ELSE 0 END), 0) AS wins,
        COALESCE(SUM(CASE WHEN schedules.status = 2 AND schedules.winner_id IS NOT NULL AND schedules.winner_id <> teams.id THEN 1 ELSE 0 END), 0) AS losses,
        COALESCE(SUM(CASE WHEN schedules.status = 2 AND schedules.home_id = teams.id THEN schedules.home_score ELSE 0 END), 0) AS total_home_score,
        COALESCE(SUM(CASE WHEN schedules.status = 2 AND schedules.away_id = teams.id THEN schedules.away_score ELSE 0 END), 0) AS total_away_score,
        ROUND(
            COALESCE(SUM(CASE WHEN schedules.status = 2 AND schedules.home_id = teams.id THEN schedules.home_score ELSE 0 END), 0)
            / NULLIF(COUNT(CASE WHEN schedules.status = 2 AND schedules.home_id = teams.id THEN 1 END), 0), 2
        ) AS home_ppg,
        ROUND(
            COALESCE(SUM(CASE WHEN schedules.status = 2 AND schedules.away_id = teams.id THEN schedules.away_score ELSE 0 END), 0)
            / NULLIF(COUNT(CASE WHEN schedules.status = 2 AND schedules.away_id = teams.id THEN 1 END), 0), 2
        ) AS away_ppg,
        COALESCE(SUM(CASE
            WHEN schedules.status = 2 AND schedules.home_id = teams.id THEN schedules.home_score - schedules.away_score
            WHEN schedules.status = 2 AND schedules.away_id = teams.id THEN schedules.away_score - schedules.home_score
            ELSE 0
        END), 0) AS score_difference,
        COALESCE(MAX(schedules.season_id), 0) AS season_id
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
    LEFT JOIN conferences
        ON teams.conference_id = conferences.id
    GROUP BY
        teams.id, teams.name, teams.city, teams.acronym,
        teams.conference_id, teams.primary_color, teams.secondary_color,
        conferences.name
),

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
        wins,
        losses,
        total_home_score,
        total_away_score,
        home_ppg,
        away_ppg,
        score_difference,
        season_id,
        RANK() OVER (
            PARTITION BY season_id, conference_id
            ORDER BY wins DESC, score_difference DESC, home_ppg DESC, away_ppg DESC
        ) AS conference_rank,
        RANK() OVER (
            PARTITION BY season_id
            ORDER BY wins DESC, score_difference DESC
        ) AS overall_rank
    FROM team_rankings
),

rank_counts AS (
    SELECT
        team_id,
        SUM(CASE WHEN overall_rank = 1 THEN 1 ELSE 0 END) AS overall_rank,
        SUM(CASE WHEN conference_rank = 1 THEN 1 ELSE 0 END) AS conference_rank
    FROM ranked_team_rankings
    GROUP BY team_id
),

playoff_appearances AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT schedules.season_id) AS playoff_appearances
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
    WHERE schedules.round IN (
        'play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals',
        'round_of_32', 'round_of_16', 'quarter_finals', 'semi_finals',
        'interconference_semi_finals', 'finals'
    )
    GROUP BY teams.id
),

finals_appearances AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT schedules.season_id) AS finals_appearances
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
    WHERE schedules.round = 'finals'
    GROUP BY teams.id
),

conference_finals_appearances AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT schedules.season_id) AS conference_finals_appearance
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
    WHERE schedules.round = 'semi_finals'
    GROUP BY teams.id
),

championships AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT schedules.season_id) AS championships
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
    WHERE schedules.round = 'finals' AND schedules.winner_id = teams.id
    GROUP BY teams.id
),

conference_championships AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT schedules.season_id) AS championships
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
    WHERE schedules.round = 'semi_finals' AND schedules.winner_id = teams.id
    GROUP BY teams.id
)

SELECT
    standings.*,
    COALESCE(playoff_appearances.playoff_appearances, 0) AS playoff_appearances,
    COALESCE(finals_appearances.finals_appearances, 0) AS finals_appearances,
    COALESCE(conference_finals_appearances.conference_finals_appearance, 0) AS conference_finals_appearances,
    COALESCE(conference_championships.championships, 0) AS conference_championships,
    COALESCE(championships.championships, 0) AS championships,
    CASE
        WHEN latest_streak.game_result = 'W' THEN CONCAT('W', latest_streak.streak_length)
        WHEN latest_streak.game_result = 'L' THEN CONCAT('L', latest_streak.streak_length)
        ELSE NULL
    END AS streak_status,
    COALESCE(last_five_games.last_5, '') AS last_5_games,
    COALESCE(rank_counts.overall_rank, 0) AS overall_1_rank,
    COALESCE(rank_counts.conference_rank, 0) AS conference_1_rank,
    CASE
        WHEN COALESCE(rank_counts.overall_rank, 0) = 1
             AND COALESCE(rank_counts.conference_rank, 0) = 1
             AND COALESCE(conference_championships.championships, 0) > 0
             AND COALESCE(championships.championships, 0) > 0
        THEN TRUE
        ELSE FALSE
    END AS is_grandslam
FROM ranked_team_rankings AS standings
LEFT JOIN latest_streak
    ON standings.team_id = latest_streak.team_id
    AND standings.season_id = latest_streak.season_id
LEFT JOIN playoff_appearances
    ON standings.team_id = playoff_appearances.team_id
LEFT JOIN finals_appearances
    ON standings.team_id = finals_appearances.team_id
LEFT JOIN conference_championships
    ON standings.team_id = conference_championships.team_id
LEFT JOIN conference_finals_appearances
    ON standings.team_id = conference_finals_appearances.team_id
LEFT JOIN championships
    ON standings.team_id = championships.team_id
LEFT JOIN rank_counts
    ON standings.team_id = rank_counts.team_id
LEFT JOIN last_five_games
    ON standings.team_id = last_five_games.team_id
    AND standings.season_id = last_five_games.season_id;
