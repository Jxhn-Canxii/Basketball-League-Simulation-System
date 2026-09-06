CREATE OR REPLACE VIEW current_fouled_out_players AS
SELECT 
    players.name AS player_name,
    teams.name AS team_name,
    psq.quarter As quarter_out,
    psq.season_id,
    psq.fouls
FROM player_per_quarter_stats AS psq
JOIN players ON psq.player_id = players.id
JOIN teams ON psq.team_id = teams.id
WHERE psq.is_fouled_out = true;


CREATE OR REPLACE VIEW fatigue_monitoring AS 
SELECT 
    players.name as player_name,
    teams.name as team_name,
    players.role,
    players.fatigue, 
    players.is_injured 
FROM players 
JOIN teams ON teams.id = players.id 
WHERE players.fatigue > 0 AND players.team_id > 0 
ORDER BY players.fatigue DESC
