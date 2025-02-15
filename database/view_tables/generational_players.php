CREATE OR REPLACE VIEW generational_players_view AS
SELECT 
    p.name,
    p.role,
    p.draft_status,
    t.name AS team_name,
    p.overall_rating
FROM players p
LEFT JOIN teams t ON p.team_id = t.id
WHERE p.type = 'generational';
