CREATE OR REPLACE VIEW coach_championships AS
SELECT
    c.id AS coach_id,
    c.name AS coach_name,
    ct.name AS current_team_name,
    COUNT(*) AS total_championships,
    GROUP_CONCAT(
        CONCAT(s.name, ' - ', IFNULL(t.name, 'Unknown Team'))
        ORDER BY s.id SEPARATOR ', '
    ) AS championship_seasons_and_teams
FROM team_season_info tsi
JOIN coaches c ON c.id = tsi.coach_id
JOIN teams t ON t.id = tsi.team_id
LEFT JOIN teams ct ON ct.id = c.team_id
JOIN seasons s ON s.id = tsi.season_id
WHERE tsi.team_id = s.finals_winner_id
GROUP BY c.id, c.name
ORDER BY total_championships DESC, s.id DESC,c.name;
