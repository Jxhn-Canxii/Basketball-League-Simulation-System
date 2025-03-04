CREATE OR REPLACE VIEW star_players_count_per_team_all_seasons AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    
    -- Count unique star players who played for the team across all seasons
    COUNT(DISTINCT p.id) AS star_players_on_roster,

    -- Get star player names with their current team acronym (for players on roster)
    GROUP_CONCAT(DISTINCT CONCAT(p.name, ' (', 
        COALESCE(ct.acronym, 'FA'), ')') 
        ORDER BY p.name SEPARATOR ', ') AS star_players_on_roster_list,

    -- Count unique star players produced (drafted by the team)
    (SELECT COUNT(DISTINCT p2.id) 
     FROM players p2 
     WHERE p2.drafted_team_id = t.id 
       AND p2.role = 'star player') AS star_players_produced,

    -- Get star player names with their current team acronym (for players produced)
    (SELECT GROUP_CONCAT(DISTINCT CONCAT(p3.name, ' (', 
        COALESCE(ct2.acronym, 'FA'), ')') 
        ORDER BY p3.name SEPARATOR ', ') 
     FROM players p3 
     LEFT JOIN teams ct2 ON p3.team_id = ct2.id  -- Left join to allow FA status
     WHERE p3.drafted_team_id = t.id 
       AND p3.role = 'star player'
    ) AS star_players_produced_list

FROM player_season_stats ps
JOIN players p ON ps.player_id = p.id
JOIN teams t ON ps.team_id = t.id  -- Use `ps.team_id` to track historical teams
LEFT JOIN teams ct ON p.team_id = ct.id  -- Left join to allow FA status

WHERE p.role = 'star player'

GROUP BY t.id, t.name
ORDER BY star_players_produced DESC, star_players_on_roster DESC;
