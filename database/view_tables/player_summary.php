CREATE OR REPLACE VIEW player_summary AS
SELECT 
    p.team_id,
    t.name AS team_name, 
    COUNT(p.id) AS active_players,  -- Total active players on the team
    SUM(p.is_rookie = 1) AS total_rookies,  -- Total rookies on the team
    SUM(p.is_injured = 1) AS total_injured,  -- Total injured players
    COALESCE(pss.total_players_played, 0) AS total_players_played  -- Distinct players who played
FROM players p
JOIN teams t ON p.team_id = t.id
LEFT JOIN (
    SELECT team_id, COUNT(DISTINCT player_id) AS total_players_played
    FROM player_season_stats
    GROUP BY team_id
) pss ON p.team_id = pss.team_id
WHERE p.team_id != 0
GROUP BY p.team_id, t.name, pss.total_players_played;
