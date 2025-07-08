CREATE OR REPLACE VIEW teams_with_hardship_contracts AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    p.id AS player_id,
    p.name AS player_name,
    GROUP_CONCAT(DISTINCT tr.season_id ORDER BY tr.season_id) AS seasons_signed,
    COUNT(*) AS hardship_contracts_count
FROM transactions tr
JOIN teams t ON tr.to_team_id = t.id
JOIN players p ON tr.player_id = p.id
WHERE tr.status = 'signed-hardship'
GROUP BY t.id, t.name, p.id, p.name
ORDER BY t.name, p.name;
