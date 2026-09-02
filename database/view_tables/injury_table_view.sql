CREATE OR REPLACE VIEW injured_players_view AS
SELECT
    i.id AS injury_id,
    p.id AS player_id,
    p.overall_rating AS overall_rating,
    i.game_id AS game_id,
    i.team_id AS team_id,
    i.season_id AS season_id,
    p.name AS player_name,
    p.role,
    COALESCE(ct.name, 'Free Agent') AS current_team_name,  -- If no team, show 'Free Agent'
    COALESCE(t.name, 'Free Agent') AS team_when_injured,  -- If no team, show 'Free Agent'
    i.injury_type,
    i.recovery_games,
    p.injury_recovery_games,
    CASE
        WHEN p.injury_recovery_games = 0 THEN 'Recovered'
        ELSE 'Injured'
    END AS status  -- If injury_recovery_games is 0, mark as recovered
FROM injury_histories i
JOIN players p ON i.player_id = p.id
LEFT JOIN teams t ON i.team_id = t.id  -- Team at the time of injury
LEFT JOIN teams ct ON ct.id = p.team_id  -- Player's current team
ORDER BY i.id DESC;
