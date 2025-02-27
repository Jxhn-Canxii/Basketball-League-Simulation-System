CREATE OR REPLACE VIEW teams_with_hardship_contracts AS
SELECT 
    teams.id AS team_id,
    teams.name AS team_name,
    COUNT(players.id) AS hardship_players_count
FROM players
JOIN teams ON players.team_id = teams.id
WHERE players.hardship_contract > 0
GROUP BY teams.id, teams.name;
