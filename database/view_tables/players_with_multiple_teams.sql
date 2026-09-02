CREATE OR REPLACE VIEW players_multiple_teams AS
SELECT
  pss.player_id,
  pss.season_id,
  p.name AS player_name,
  p.role,
  p.overall_rating,
  GROUP_CONCAT(DISTINCT t.name ORDER BY t.name ASC) AS teams_played,
  COUNT(DISTINCT pss.team_id) AS total_teams
FROM
  player_season_stats pss
JOIN
  players p ON pss.player_id = p.id
LEFT JOIN
  teams t ON pss.team_id = t.id
GROUP BY
  pss.player_id, pss.season_id
HAVING
  total_teams > 1
ORDER BY
  pss.season_id DESC, player_name ASC;
