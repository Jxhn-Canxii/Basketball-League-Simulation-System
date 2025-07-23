CREATE OR REPLACE VIEW team_droughts AS
SELECT 
    tsi.team_id,
    t.name AS team_name,

    -- Latest season this team has info
    MAX(tsi.season_id) AS latest_season_id,

    -- Last championship season (defending champion = true)
    MAX(CASE WHEN tsi.is_defending_champion = 1 THEN tsi.season_id ELSE NULL END) AS last_championship_season_id,

    -- Championship drought
    MAX(tsi.season_id) - 
    COALESCE(MAX(CASE WHEN tsi.is_defending_champion = 1 THEN tsi.season_id ELSE NULL END), 0) AS championship_drought_seasons,

    -- Last playoff appearance
    MAX(CASE WHEN tsi.is_playoff_qualified = 1 THEN tsi.season_id ELSE NULL END) AS last_playoff_season_id,

    -- Playoff drought
    MAX(tsi.season_id) - 
    COALESCE(MAX(CASE WHEN tsi.is_playoff_qualified = 1 THEN tsi.season_id ELSE NULL END), 0) AS playoff_drought_seasons

FROM team_season_info tsi
JOIN teams t ON t.id = tsi.team_id
GROUP BY tsi.team_id, t.name;
