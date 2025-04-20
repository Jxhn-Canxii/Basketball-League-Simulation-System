CREATE OR REPLACE VIEW team_needs_summary_view AS
SELECT
    t.id AS team_id,
    t.name AS team_name,

    CONCAT_WS(', ',
        CASE WHEN AVG(CASE WHEN p.position = 'PF' THEN p.defense_rating END) < 65 THEN 'Needs Defensive PF' END,
        CASE WHEN AVG(CASE WHEN p.position = 'PF' THEN (p.two_point_rating + p.strength_rating) / 2 END) < 70 THEN 'Needs Offensive PF' END,
        CASE WHEN AVG(CASE WHEN p.position = 'SG' THEN p.defense_rating END) < 65 THEN 'Needs Defensive SG' END,
        CASE WHEN AVG(CASE WHEN p.position = 'SG' THEN (p.three_point_rating + p.shooting_rating) / 2 END) < 70 THEN 'Needs Offensive SG' END,
        CASE WHEN AVG(CASE WHEN p.position = 'PG' THEN (p.passing_rating + p.basketball_iq_rating) / 2 END) < 70 THEN 'Needs Playmaking PG' END,
        CASE WHEN AVG(CASE WHEN p.position = 'C' THEN (p.rebounding_rating + p.defense_rating) / 2 END) < 68 THEN 'Needs Defensive C' END,
        CASE WHEN AVG(CASE WHEN p.position = 'SF' THEN (p.athleticism_rating + p.defense_rating) / 2 END) < 70 THEN 'Needs Defensive SF' END
    ) AS team_needs_summary

FROM players p
JOIN teams t ON t.id = p.team_id
WHERE p.is_active = 1
GROUP BY t.id, t.name;
