CREATE OR REPLACE VIEW playoff_series_headtohead AS
SELECT 
    LEAST(ps.home_team_id, ps.away_team_id) AS team1_id,
    GREATEST(ps.home_team_id, ps.away_team_id) AS team2_id,
    t1.name AS team1_name,
    t1.acronym AS team1_acronym,
    t2.name AS team2_name,
    t2.acronym AS team2_acronym,

    COUNT(*) AS series_played,

    -- total games played across all series
    SUM(ps.home_wins + ps.away_wins) AS total_matches_played,

    -- total wins/losses for team1
    SUM(
        CASE WHEN ps.home_team_id = LEAST(ps.home_team_id, ps.away_team_id) 
             THEN ps.home_wins ELSE ps.away_wins END
    ) AS team1_wins,
    SUM(
        CASE WHEN ps.home_team_id = LEAST(ps.home_team_id, ps.away_team_id) 
             THEN ps.away_wins ELSE ps.home_wins END
    ) AS team1_losses,

    -- total wins/losses for team2
    SUM(
        CASE WHEN ps.home_team_id = GREATEST(ps.home_team_id, ps.away_team_id) 
             THEN ps.home_wins ELSE ps.away_wins END
    ) AS team2_wins,
    SUM(
        CASE WHEN ps.home_team_id = GREATEST(ps.home_team_id, ps.away_team_id) 
             THEN ps.away_wins ELSE ps.home_wins END
    ) AS team2_losses

FROM playoff_series ps
JOIN teams t1 ON t1.id = LEAST(ps.home_team_id, ps.away_team_id)
JOIN teams t2 ON t2.id = GREATEST(ps.home_team_id, ps.away_team_id)
WHERE ps.status = 2 -- only completed series
GROUP BY team1_id, team2_id, t1.name, t2.name, t1.acronym, t2.acronym
ORDER BY series_played DESC, total_matches_played DESC;
