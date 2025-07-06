CREATE OR REPLACE VIEW top_five_picks AS
SELECT
    d.season_id,
    d.pick_number,
    t1.name AS draft_team_name,
    p.name AS player_name,
    t2.name AS current_team_name,
    p.role AS player_role,
    p.position AS player_position,
    p.overall_rating AS player_rating,
    d.player_id,
    d.round,
    d.id,
    d.team_id,
    p.team_id AS current_team_id,
    d.draft_status
FROM drafts d
JOIN players p ON d.player_id = p.id
JOIN teams t1 ON d.team_id = t1.id
LEFT JOIN teams t2 ON p.team_id = t2.id
WHERE d.pick_number <= 5 && d.round = 1
ORDER BY d.season_id DESC, d.pick_number;

CREATE OR REPLACE VIEW top_ten_picks_ranked AS
SELECT 
    ROW_NUMBER() OVER (ORDER BY COUNT(*) DESC, t1.name) AS id,
    t1.name AS draft_team_name,
    COUNT(*) AS pick_count,
    RANK() OVER (ORDER BY COUNT(*) DESC) AS team_rank
FROM drafts d
JOIN teams t1 ON d.team_id = t1.id
WHERE d.pick_number <= 10 AND d.round = 1
GROUP BY t1.id, t1.name
ORDER BY team_rank, t1.name;