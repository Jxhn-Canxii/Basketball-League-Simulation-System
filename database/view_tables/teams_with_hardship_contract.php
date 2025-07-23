CREATE OR REPLACE VIEW team_droughts AS
SELECT 
    tsi.team_id,
    t.name AS team_name,

    -- Latest season ID and name
    MAX(tsi.season_id) AS latest_season_id,
    MAX(s.name) AS latest_season_name,

    -- Last championship season ID
    MAX(CASE WHEN tsi.is_defending_champion = 1 THEN tsi.season_id ELSE NULL END) AS last_championship_season_id,

    -- Last championship season name
    (
        SELECT s2.name
        FROM team_season_info tsi2
        JOIN seasons s2 ON s2.id = tsi2.season_id
        WHERE tsi2.team_id = tsi.team_id AND tsi2.is_defending_champion = 1
        ORDER BY tsi2.season_id DESC
        LIMIT 1
    ) AS last_championship_season_name,

    -- Coach who won the last championship
    (
        SELECT c.name
        FROM team_season_info tsi2
        JOIN coaches c ON c.id = tsi2.coach_id
        WHERE tsi2.team_id = tsi.team_id AND tsi2.is_defending_champion = 1
        ORDER BY tsi2.season_id DESC
        LIMIT 1
    ) AS last_championship_coach_name,

    -- Latest coach (from most recent season)
    (
        SELECT c.name
        FROM team_season_info tsi2
        JOIN coaches c ON c.id = tsi2.coach_id
        WHERE tsi2.team_id = tsi.team_id
        ORDER BY tsi2.season_id DESC
        LIMIT 1
    ) AS latest_coach_name,

    -- Championship drought (in seasons)
    MAX(tsi.season_id) - 
    COALESCE(MAX(CASE WHEN tsi.is_defending_champion = 1 THEN tsi.season_id ELSE NULL END), 0) AS championship_drought_seasons,

    -- Last playoff appearance season ID
    MAX(CASE WHEN tsi.is_playoff_qualified = 1 THEN tsi.season_id ELSE NULL END) AS last_playoff_season_id,

    -- Playoff drought (in seasons)
    MAX(tsi.season_id) - 
    COALESCE(MAX(CASE WHEN tsi.is_playoff_qualified = 1 THEN tsi.season_id ELSE NULL END), 0) AS playoff_drought_seasons

FROM team_season_info tsi
JOIN teams t ON t.id = tsi.team_id
JOIN seasons s ON s.id = tsi.season_id
GROUP BY tsi.team_id, t.name;
