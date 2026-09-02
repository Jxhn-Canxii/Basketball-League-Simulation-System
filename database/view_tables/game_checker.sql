CREATE OR REPLACE VIEW team_game_summary_latest_season AS
WITH latest_season AS (
    SELECT MAX(season_id) AS season_id FROM schedules
),
season_games AS (
    SELECT 
        s.id,
        s.game_id,
        s.home_id,
        s.away_id,
        s.season_id,
        s.conference_id,
        s.home_score,
        s.away_score,
        s.status,
        s.created_at,
        s.updated_at
    FROM schedules s
    JOIN latest_season ls ON s.season_id = ls.season_id
),
team_games AS (
    -- Home team perspective
    SELECT 
        home_id AS team_id,
        CASE WHEN game_id LIKE '%intra%' THEN 1 ELSE 0 END AS intra_game,
        CASE WHEN game_id LIKE '%inter%' THEN 1 ELSE 0 END AS inter_game
    FROM season_games

    UNION ALL

    -- Away team perspective
    SELECT 
        away_id AS team_id,
        CASE WHEN game_id LIKE '%intra%' THEN 1 ELSE 0 END AS intra_game,
        CASE WHEN game_id LIKE '%inter%' THEN 1 ELSE 0 END AS inter_game
    FROM season_games
),
aggregated AS (
    SELECT 
        team_id,
        SUM(intra_game) AS intra_games,
        SUM(inter_game) AS inter_games,
        COUNT(*) AS total_games
    FROM team_games
    GROUP BY team_id
)
SELECT 
    tgs.team_id,
    teams.name AS team_name,
    tgs.intra_games,
    tgs.inter_games,
    tgs.total_games
FROM aggregated tgs
JOIN teams ON teams.id = tgs.team_id;
