CREATE OR REPLACE VIEW star_players_per_team AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    p.id AS player_id,
    p.name AS player_name,
    ps.season_id,
    ps.avg_points_per_game,
    ps.avg_rebounds_per_game,
    ps.avg_assists_per_game,
    ps.avg_steals_per_game,
    ps.avg_blocks_per_game,
	ps.per
FROM player_season_stats ps
JOIN players p ON ps.player_id = p.id
JOIN teams t ON p.team_id = t.id
WHERE ps.season_id = (SELECT MAX(season_id) FROM player_season_stats)
ORDER BY ps.per DESC; -- Prioritizing highest PPG players
