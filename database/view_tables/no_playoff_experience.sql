CREATE OR REPLACE VIEW view_non_rookie_no_playoff_experience AS
SELECT 
    p.id AS player_id,
    p.name AS player_name,
    p.team_id,
    t.name AS team_name,
    ps.season_experience
FROM players p
JOIN teams t ON p.team_id = t.id
JOIN (
    SELECT 
        player_id,
        COUNT(DISTINCT season_id) AS season_experience
    FROM player_season_stats
    GROUP BY player_id
) ps ON p.id = ps.player_id
WHERE ps.season_experience > 1
  AND p.id NOT IN (
      SELECT DISTINCT player_id
      FROM player_playoff_appearances
  );
