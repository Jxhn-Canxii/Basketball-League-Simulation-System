CREATE OR REPLACE VIEW team_finals_summary AS
WITH finals_games AS (
    SELECT home_id AS team_id, season_id
    FROM schedule_view
    WHERE round = 'Finals'

    UNION ALL

    SELECT away_id AS team_id, season_id
    FROM schedule_view
    WHERE round = 'Finals'
),
finals_summary AS (
    SELECT 
        team_id,
        COUNT(DISTINCT season_id) AS finals_appearances,
        MAX(season_id) AS last_finals_season_id
    FROM finals_games
    GROUP BY team_id
),
latest_season AS (
    SELECT MAX(season_id) AS current_season FROM schedule_view
)
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    COALESCE(fs.finals_appearances, 0) AS finals_appearances,
    s.name AS last_finals_season,
    ls.current_season - COALESCE(fs.last_finals_season_id, 0) AS finals_drought
FROM teams t
LEFT JOIN finals_summary fs ON t.id = fs.team_id
LEFT JOIN seasons s ON fs.last_finals_season_id = s.id
CROSS JOIN latest_season ls
ORDER BY 
    finals_appearances DESC,
    fs.last_finals_season_id DESC;
