CREATE OR REPLACE VIEW standings_view AS
WITH latest_season AS (
    SELECT MAX(id) AS season_id
    FROM seasons
),
next_games AS (
    SELECT
        t.id AS team_id,
        s.id AS game_id,
        s.home_id,
        s.away_id,
        ROW_NUMBER() OVER (PARTITION BY t.id ORDER BY s.id ASC) AS rn
    FROM teams t
    JOIN schedules s
        ON (t.id = s.home_id OR t.id = s.away_id)
        AND s.status = 1 -- scheduled but not played
        AND s.season_id = (SELECT season_id FROM latest_season)
),
team_games AS (
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
        schedules.id AS game_date,
        CASE
            WHEN schedules.winner_id = teams.id THEN 'W'
            WHEN schedules.winner_id IS NOT NULL AND schedules.winner_id <> teams.id THEN 'L'
            ELSE NULL
        END AS game_result
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.status = 2
        AND schedules.season_id = (SELECT season_id FROM latest_season)
    LEFT JOIN conferences
        ON teams.conference_id = conferences.id
    WHERE
        (schedules.round NOT IN (
            'play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals',
            'round_of_32', 'round_of_16', 'quarter_finals', 'semi_finals',
            'interconference_semi_finals', 'finals'
        ) OR schedules.id IS NULL)
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
        COUNT(*) AS streak_length,
        MAX(game_id) AS last_game_id
    FROM (
        SELECT
            team_id,
            season_id,
            game_result,
            game_id,
            ROW_NUMBER() OVER (PARTITION BY team_id, season_id ORDER BY game_id) - 
            ROW_NUMBER() OVER (PARTITION BY team_id, season_id, game_result ORDER BY game_id) AS streak_id
        FROM team_games
        WHERE game_result IS NOT NULL
    ) AS streak_groups
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
                ORDER BY last_game_id DESC
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
        schedules.season_id,
        COALESCE(SUM(CASE 
            WHEN schedules.status = 2 
                 AND schedules.winner_id = teams.id 
                 AND schedules.round REGEXP '^[0-9]+$' 
            THEN 1 
            ELSE 0 
        END), 0) AS wins,
        COALESCE(SUM(CASE 
            WHEN schedules.status = 2 
                 AND schedules.winner_id IS NOT NULL 
                 AND schedules.winner_id <> teams.id 
                 AND schedules.round REGEXP '^[0-9]+$' 
            THEN 1 
            ELSE 0 
        END), 0) AS losses,
        COALESCE(SUM(CASE 
            WHEN schedules.status = 2 
                 AND schedules.home_id = teams.id 
                 AND schedules.round REGEXP '^[0-9]+$' 
            THEN schedules.home_score 
            ELSE 0 
        END), 0) AS total_home_score,
        COALESCE(SUM(CASE 
            WHEN schedules.status = 2 
                 AND schedules.away_id = teams.id 
                 AND schedules.round REGEXP '^[0-9]+$' 
            THEN schedules.away_score 
            ELSE 0 
        END), 0) AS total_away_score,
        ROUND(
            COALESCE(SUM(CASE 
                WHEN schedules.status = 2 
                     AND schedules.home_id = teams.id 
                     AND schedules.round REGEXP '^[0-9]+$' 
                THEN schedules.home_score 
                ELSE 0 
            END), 0)
            / NULLIF(COUNT(CASE 
                WHEN schedules.status = 2 
                     AND schedules.home_id = teams.id 
                     AND schedules.round REGEXP '^[0-9]+$' 
                THEN 1 
                ELSE NULL 
            END), 0), 2
        ) AS home_ppg,
        ROUND(
            COALESCE(SUM(CASE 
                WHEN schedules.status = 2 
                     AND schedules.away_id = teams.id 
                     AND schedules.round REGEXP '^[0-9]+$' 
                THEN schedules.away_score 
                ELSE 0 
            END), 0)
            / NULLIF(COUNT(CASE 
                WHEN schedules.status = 2 
                     AND schedules.away_id = teams.id 
                     AND schedules.round REGEXP '^[0-9]+$' 
                THEN 1 
                ELSE NULL 
            END), 0), 2
        ) AS away_ppg,
        COALESCE(SUM(CASE
            WHEN schedules.status = 2 
                 AND schedules.home_id = teams.id 
                 AND schedules.round REGEXP '^[0-9]+$' 
            THEN schedules.home_score - schedules.away_score
            WHEN schedules.status = 2 
                 AND schedules.away_id = teams.id 
                 AND schedules.round REGEXP '^[0-9]+$' 
            THEN schedules.away_score - schedules.home_score
            ELSE 0
        END), 0) AS score_difference
    FROM teams
    LEFT JOIN schedules
        ON (teams.id = schedules.home_id OR teams.id = schedules.away_id)
        AND schedules.season_id = (SELECT season_id FROM latest_season)
    LEFT JOIN conferences
        ON teams.conference_id = conferences.id
    GROUP BY
        teams.id, teams.name, teams.city, teams.acronym,
        teams.conference_id, teams.primary_color, teams.secondary_color,
        conferences.name, schedules.season_id
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
        season_id,
        wins,
        losses,
        total_home_score,
        total_away_score,
        home_ppg,
        away_ppg,
        score_difference,
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
last_playoff_appearance AS (
    SELECT
        team_id,
        MAX(season_id) AS last_playoff_season_id
    FROM
        team_season_info
    WHERE
        is_playoff_qualified = 1
    GROUP BY
        team_id
),
seasons_list AS (
    SELECT id, name AS season_name
    FROM seasons
),
playoff_appearances AS (
    SELECT
        team_id,
        COUNT(*) AS playoff_appearances
    FROM
        team_season_info
    WHERE
        is_playoff_qualified = 1
    GROUP BY
        team_id
),
team_season_info AS (
    SELECT
        team_id,
        season_id,
        is_defending_champion,
        chemistry
    FROM
        team_season_info
    WHERE
        season_id = (SELECT season_id FROM latest_season)
),
finals_appearances AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT playoff_series.season_id) AS finals_appearances
    FROM
        teams
    JOIN
        playoff_series ON teams.id = playoff_series.home_team_id OR teams.id = playoff_series.away_team_id
    WHERE
        playoff_series.round = 'finals'
    GROUP BY
        teams.id
),
conference_finals_appearances AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT playoff_series.season_id) AS conference_finals_appearances
    FROM
        teams
    JOIN
        playoff_series ON teams.id = playoff_series.home_team_id OR teams.id = playoff_series.away_team_id
    WHERE
        playoff_series.round = 'semi_finals'
    GROUP BY
        teams.id
),
championships AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT playoff_series.season_id) AS championships
    FROM
        teams
    JOIN
        playoff_series ON teams.id = playoff_series.home_team_id OR teams.id = playoff_series.away_team_id
    WHERE
        playoff_series.round = 'finals' AND playoff_series.winner_team_id = teams.id
    GROUP BY
        teams.id
),
conference_championships AS (
    SELECT
        teams.id AS team_id,
        COUNT(DISTINCT playoff_series.season_id) AS conference_championships
    FROM
        teams
    JOIN
        playoff_series ON teams.id = playoff_series.home_team_id OR teams.id = playoff_series.away_team_id
    WHERE
        playoff_series.round = 'semi_finals' AND playoff_series.winner_team_id = teams.id
    GROUP BY
        teams.id
)
SELECT
    standings.*,
    team_season_info.is_defending_champion,
    team_season_info.chemistry,
    COALESCE(seasons_list.season_name, '') AS last_playoff_season_name,
    COALESCE(playoff_appearances.playoff_appearances, 0) AS playoff_appearances,
    COALESCE(finals_appearances.finals_appearances, 0) AS finals_appearances,
    COALESCE(conference_finals_appearances.conference_finals_appearances, 0) AS conference_finals_appearances,
    COALESCE(conference_championships.conference_championships, 0) AS conference_championships,
    COALESCE(championships.championships, 0) AS championships,
    CASE
        WHEN latest_streak.game_result = 'W' THEN CONCAT('W', latest_streak.streak_length)
        WHEN latest_streak.game_result = 'L' THEN CONCAT('L', latest_streak.streak_length)
        ELSE NULL
    END AS streak_status,
    COALESCE(last_five_games.last_5, '') AS last_5_games,
    opponent.acronym AS next_opponent_acronym,
    opponent.name AS next_opponent_name
FROM ranked_team_rankings AS standings
LEFT JOIN next_games ng ON standings.team_id = ng.team_id AND ng.rn = 1
LEFT JOIN teams opponent ON (
    (ng.home_id = standings.team_id AND ng.away_id = opponent.id) OR
    (ng.away_id = standings.team_id AND ng.home_id = opponent.id)
)
LEFT JOIN last_playoff_appearance
    ON standings.team_id = last_playoff_appearance.team_id
LEFT JOIN seasons_list
    ON last_playoff_appearance.last_playoff_season_id = seasons_list.id
LEFT JOIN playoff_appearances
    ON standings.team_id = playoff_appearances.team_id
LEFT JOIN team_season_info
    ON standings.team_id = team_season_info.team_id
    AND standings.season_id = team_season_info.season_id
LEFT JOIN finals_appearances
    ON standings.team_id = finals_appearances.team_id
LEFT JOIN conference_finals_appearances
    ON standings.team_id = conference_finals_appearances.team_id
LEFT JOIN championships
    ON standings.team_id = championships.team_id
LEFT JOIN conference_championships
    ON standings.team_id = conference_championships.team_id
LEFT JOIN latest_streak
    ON standings.team_id = latest_streak.team_id
    AND standings.season_id = latest_streak.season_id
LEFT JOIN last_five_games
    ON standings.team_id = last_five_games.team_id
    AND standings.season_id = last_five_games.season_id;
