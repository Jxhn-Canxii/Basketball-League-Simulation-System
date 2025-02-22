CREATE OR REPLACE VIEW player_summary AS
SELECT 
    p.team_id,
    t.name AS team_name, 
    COUNT(p.id) AS active_players,
    SUM(p.is_rookie = 1) AS total_rookies,
    SUM(p.is_injured = 1) AS total_injured
FROM players p
JOIN teams t ON p.team_id = t.id
WHERE p.team_id != 0
GROUP BY p.team_id, t.name;
