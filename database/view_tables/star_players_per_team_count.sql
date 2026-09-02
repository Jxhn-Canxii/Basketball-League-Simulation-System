CREATE OR REPLACE VIEW star_players_count_per_team_all_seasons AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,

    -- Count unique star players who played for the team in ANY season
    COUNT(DISTINCT ps.player_id) AS star_players_on_roster,

    -- Get star player names with their current team acronym (for players on roster)
    GROUP_CONCAT(DISTINCT CONCAT(p.name, ' (', 
        COALESCE(ct.acronym, 'FA'), ')') 
        ORDER BY p.name SEPARATOR ', ') AS star_players_on_roster_list,

    -- Count unique star players drafted by the team (produced by the team)
    (SELECT COUNT(DISTINCT ps2.player_id) 
     FROM player_season_stats ps2
     JOIN players p2 ON ps2.player_id = p2.id
     WHERE p2.drafted_team_id = t.id 
       AND ps2.role = 'star player') AS star_players_produced,

    -- Get star player names with their current team acronym (for players produced)
    (SELECT GROUP_CONCAT(DISTINCT CONCAT(p3.name, ' (', 
        COALESCE(ct2.acronym, 'FA'), ')') 
        ORDER BY p3.name SEPARATOR ', ') 
     FROM player_season_stats ps3
     JOIN players p3 ON ps3.player_id = p3.id
     LEFT JOIN teams ct2 ON p3.team_id = ct2.id
     WHERE p3.drafted_team_id = t.id 
       AND ps3.role = 'star player'
    ) AS star_players_produced_list

FROM teams t
JOIN player_season_stats ps ON ps.team_id = t.id  -- Use player_season_stats.team_id
JOIN players p ON ps.player_id = p.id
LEFT JOIN teams ct ON p.team_id = ct.id  -- Left join to track current team (FA if null)

WHERE ps.role = 'star player' 

GROUP BY t.id, t.name
ORDER BY star_players_produced DESC, star_players_on_roster DESC;
