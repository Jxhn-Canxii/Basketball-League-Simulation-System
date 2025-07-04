CREATE OR REPLACE VIEW view_players_to_prove_raw AS
SELECT
    p.id AS player_id,
    p.name,
    p.overall_rating,
    p.age,
    p.position,
    -- Draft Info
    CASE
        WHEN p.is_drafted = 1 THEN d.draft_status
        WHEN p.draft_id = 1 THEN 'Special Draft'
        ELSE 'Undrafted'
    END AS draft_status,
    -- Reason for pressure
    CASE 
        WHEN p.draft_id = 1 AND ps.eff < 10 THEN 'Underperforming Pioneer'
        WHEN p.contract_years <= 1 THEN 'Expiring Contract'
        WHEN p.is_injured = 1 THEN 'Coming Off Injury'
        WHEN ps.total_games_played >= 10 
             AND ps.avg_points_per_game < 10 
             AND ps.avg_assists_per_game < 2 
             AND ps.avg_rebounds_per_game < 3 THEN 'Low Impact'
        ELSE 'Other'
    END AS reason,

    -- Pressure Score
    CASE 
        WHEN p.draft_id = 1 AND ps.eff < 10 THEN 5
        WHEN p.contract_years <= 1 THEN 4
        WHEN p.is_injured = 1 THEN 3
        WHEN ps.total_games_played >= 10 
             AND ps.avg_points_per_game < 10 
             AND ps.avg_assists_per_game < 2 
             AND ps.avg_rebounds_per_game < 3 THEN 2
        ELSE 1
    END AS pressure_score,

    -- Bust detection
    CASE 
        WHEN p.is_drafted = 1 
             AND d.round = 1 
             AND p.overall_rating < 70 
             AND ps.eff < 10 
             AND ps.total_games_played >= 10
        THEN 1 ELSE 0
    END AS bust_status,

    -- Player report summary
    CONCAT_WS(', ',
        CASE 
            WHEN p.is_drafted = 1 AND d.round = 1 AND p.overall_rating < 70 AND ps.eff < 10 AND ps.total_games_played >= 10 THEN '1st Round Bust'
            ELSE NULL
        END,
        CASE WHEN p.is_injured = 1 THEN 'Injury Comeback' ELSE NULL END,
        CASE WHEN p.contract_years <= 1 THEN 'Expiring Deal' ELSE NULL END,
        CASE WHEN p.team_id = 0 THEN 'Free Agent' ELSE NULL END
    ) AS player_report,

    -- Player info
    p.role,
    p.contract_years,
    p.is_injured,
    p.team_id,
    IF(p.team_id = 0, 'Free Agent', t.name) AS team_name,
    ps.season_id,
    ps.avg_minutes_per_game,
    ps.avg_points_per_game,
    ps.avg_assists_per_game,
    ps.avg_rebounds_per_game,
    ps.eff,
    
    d.round AS draft_round,
    d.pick_number AS draft_pick,
    dt.acronym AS drafted_team

FROM players p
LEFT JOIN teams t ON p.team_id = t.id
LEFT JOIN player_season_stats ps 
    ON ps.player_id = p.id AND ps.season_id = (SELECT MAX(id) FROM seasons)
LEFT JOIN drafts d ON d.player_id = p.id
LEFT JOIN teams dt ON d.team_id = dt.id

WHERE 
    p.is_active = 1
    AND p.team_id != 0
    AND (
        (p.draft_id = 1 AND ps.eff < 10) OR
        p.contract_years <= 1 OR
        p.is_injured = 1 OR
        (
            ps.total_games_played >= 10 AND
            ps.avg_points_per_game < 10 AND
            ps.avg_assists_per_game < 2 AND
            ps.avg_rebounds_per_game < 3
        )
    );
