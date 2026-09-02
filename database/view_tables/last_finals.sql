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
champions_summary AS (
    SELECT 
        winner_id AS team_id,
        COUNT(DISTINCT season_id) AS championships_won,
        MAX(season_id) AS last_championship_season_id
    FROM schedule_view
    WHERE round = 'Finals'
    GROUP BY winner_id
),
latest_season AS (
    SELECT MAX(season_id) AS current_season_id FROM schedule_view
),
ranked AS (
    SELECT 
        t.id AS team_id,
        t.name AS team_name,
        COALESCE(fs.finals_appearances, 0) AS finals_appearances,
        s1.name AS last_finals_season,
        ls.current_season_id - COALESCE(fs.last_finals_season_id, 0) AS finals_drought,
        COALESCE(cs.championships_won, 0) AS championships_won,
        s2.name AS last_championship_season,
        ls.current_season_id - COALESCE(cs.last_championship_season_id, 0) AS championship_drought
    FROM teams t
    LEFT JOIN finals_summary fs ON t.id = fs.team_id
    LEFT JOIN champions_summary cs ON t.id = cs.team_id
    LEFT JOIN seasons s1 ON fs.last_finals_season_id = s1.id
    LEFT JOIN seasons s2 ON cs.last_championship_season_id = s2.id
    CROSS JOIN latest_season ls
)
SELECT 
    ROW_NUMBER() OVER (ORDER BY finals_drought ASC, championship_drought ASC) AS ranking,
    team_id,
    team_name,
    finals_appearances,
    finals_drought,
    championship_drought,
    last_finals_season,
    championships_won,
    last_championship_season
   
FROM ranked
ORDER BY ranking;
