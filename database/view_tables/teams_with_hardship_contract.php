CREATE OR REPLACE VIEW teams_with_hardship_contracts AS
SELECT 
    t.name AS team_name,
    p.name AS player_name,
    GROUP_CONCAT(DISTINCT COALESCE(s.name, CONCAT('Season ID ', tr.season_id)) ORDER BY s.name) AS seasons_signed,
    COUNT(*) AS hardship_contracts_count
FROM transactions tr
JOIN teams t ON tr.to_team_id = t.id
JOIN players p ON tr.player_id = p.id
LEFT JOIN seasons s ON tr.season_id = s.id
WHERE tr.status = 'signed-hardship'
GROUP BY t.id, t.name, p.id, p.name
ORDER BY t.name, p.name;
