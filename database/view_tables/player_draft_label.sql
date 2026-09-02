DROP VIEW IF EXISTS player_draft_labels;

CREATE VIEW player_draft_labels AS
SELECT
    d.id AS draft_id,
    p.id AS player_id,
    p.name,
    d.round,
    d.pick_number,
    d.season_id AS draft_season_id,
    p.position,
    SUM(ps.eff) AS career_efficiency,

    CASE
        WHEN d.pick_number <= 10 AND SUM(ps.eff) < 1000 THEN 'Bust'
        WHEN d.pick_number >= 31 AND SUM(ps.eff) >= 2000 THEN 'Steal'
        ELSE 'Normal'
    END AS draft_label

FROM drafts d
JOIN players p ON p.id = d.player_id
LEFT JOIN player_season_stats ps ON ps.player_id = p.id

GROUP BY d.id, p.id, p.name, d.round, d.pick_number, d.season_id, p.position;
