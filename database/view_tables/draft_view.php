CREATE VIEW top_five_picks AS
SELECT 
    d.id,
    d.team_id,
    t1.name AS draft_team_name,
    d.player_id,
    p.name AS player_name,
    p.team_id AS current_team_id,
    t2.name AS current_team_name,
    p.role AS player_role,
    d.season_id,
    d.round,
    d.pick_number,
    d.draft_status
FROM drafts d
JOIN players p ON d.player_id = p.id
JOIN teams t1 ON d.team_id = t1.id
LEFT JOIN teams t2 ON p.team_id = t2.id
WHERE d.pick_number <= 5
ORDER BY d.season_id, d.pick_number;