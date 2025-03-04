CREATE OR REPLACE VIEW star_players_count_per_team_all_seasons AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    COUNT(DISTINCT p.id) AS star_players_on_roster, -- Count unique star players who played for the team
    (SELECT COUNT(DISTINCT p2.id) 
     FROM players p2 
     WHERE p2.drafted_team_id = t.id 
       AND p2.role = 'star player') AS star_players_produced -- Count unique star players drafted by the team
FROM player_season_stats ps
JOIN players p ON ps.player_id = p.id
JOIN teams t ON ps.team_id = t.id -- Use `ps.team_id` to track historical team roster, not just current team
WHERE p.role = 'star player'
GROUP BY t.id, t.name
ORDER BY star_players_produced DESC, star_players_on_roster DESC; -- Sort by drafted star players first, then rostered players
