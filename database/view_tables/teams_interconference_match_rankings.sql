CREATE OR REPLACE VIEW inter_conference_team_rankings AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    COUNT(CASE WHEN s.winner_id = t.id THEN 1 END) AS wins,
    COUNT(CASE 
        WHEN (s.home_id = t.id OR s.away_id = t.id) AND s.winner_id != t.id 
        THEN 1 
    END) AS losses,
    COUNT(CASE WHEN (s.home_id = t.id OR s.away_id = t.id) THEN 1 END) AS games_played,
    ROUND(
        COUNT(CASE WHEN s.winner_id = t.id THEN 1 END) * 1.0 /
        NULLIF(COUNT(CASE WHEN (s.home_id = t.id OR s.away_id = t.id) THEN 1 END), 0),
        3
    ) AS win_rate
FROM schedules s
JOIN teams t ON t.id IN (s.home_id, s.away_id)
WHERE s.status = 2
  AND s.game_id LIKE '%inter%'
GROUP BY t.id, t.name
ORDER BY win_rate DESC, wins DESC;
