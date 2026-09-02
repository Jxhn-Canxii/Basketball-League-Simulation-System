CREATE OR REPLACE VIEW players_retiring_next_year AS
SELECT 
    players.id,
    players.name,
    players.age,
    players.retirement_age,
    players.team_id,
    teams.name AS team_name
FROM 
    players
JOIN 
    teams ON players.team_id = teams.id
WHERE 
    players.retirement_age - players.age = 1;
