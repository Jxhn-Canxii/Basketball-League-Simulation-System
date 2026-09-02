CREATE OR REPLACE VIEW player_rivals AS
SELECT 
    p1.game_id,
    p1.season_id,
    
    -- Player info
    p1.player_id,
    pl1.name AS player_name,
    p1.team_id,
    
    -- Rival info
    p2.player_id AS rival_id,
    pl2.name AS rival_name,
    p2.team_id AS rival_team_id,
    
    -- Optional performance stats for comparison
    p1.points AS player_points,
    p2.points AS rival_points,
    p1.rebounds AS player_rebounds,
    p2.rebounds AS rival_rebounds,
    p1.assists AS player_assists,
    p2.assists AS rival_assists,
    p1.per AS player_per,
    p2.per AS rival_per,
    p1.eff AS player_eff,
    p2.eff AS rival_eff

FROM player_game_stats p1
JOIN player_game_stats p2 
    ON p1.game_id = p2.game_id
   AND p1.team_id <> p2.team_id   -- only opponents
   AND p1.player_id <> p2.player_id
JOIN players pl1 ON p1.player_id = pl1.id
JOIN players pl2 ON p2.player_id = pl2.id;


CREATE OR REPLACE VIEW player_rivalry_counts AS
SELECT 
    LEAST(p1.player_id, p2.player_id) AS player_id,
    pl1.name AS player_name,
    GREATEST(p1.player_id, p2.player_id) AS rival_id,
    pl2.name AS rival_name,
    COUNT(DISTINCT p1.game_id) AS games_played_together
FROM player_game_stats p1
JOIN player_game_stats p2 
    ON p1.game_id = p2.game_id
   AND p1.team_id <> p2.team_id
   AND p1.player_id <> p2.player_id
JOIN players pl1 ON pl1.id = LEAST(p1.player_id, p2.player_id)
JOIN players pl2 ON pl2.id = GREATEST(p1.player_id, p2.player_id)
GROUP BY 
    LEAST(p1.player_id, p2.player_id), pl1.name,
    GREATEST(p1.player_id, p2.player_id), pl2.name
ORDER BY games_played_together DESC;
