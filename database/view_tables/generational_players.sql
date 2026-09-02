CREATE OR REPLACE VIEW generational_players_view AS
SELECT 
    p.name,
    p.role,
    p.draft_status,
    t.name AS team_name,
    p.overall_rating,
    
    -- Player playoff data
    ppa.championships_won AS player_championships_won,
    ppa.total_playoff_appearances AS player_total_playoff_appearances,
    ppa.seasons_played_in_playoffs,

    ppa.interconference_semi_finals_appearances as big_4_appearances,
    ppa.finals_appearances as finals_appearances

FROM players p
LEFT JOIN teams t ON p.team_id = t.id
LEFT JOIN player_playoff_appearances ppa ON p.id = ppa.player_id
WHERE p.type = 'generational';
