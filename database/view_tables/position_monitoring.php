CREATE OR REPLACE VIEW active_player_position_summary_with_warning_and_needed AS
SELECT
    team_stats.total_teams,
    
    -- Count of active players per position
    COUNT(CASE WHEN p.position LIKE '%PG%' AND p.is_active = 1 THEN 1 END) AS PG,
    COUNT(CASE WHEN p.position LIKE '%SG%' AND p.is_active = 1 THEN 1 END) AS SG,
    COUNT(CASE WHEN p.position LIKE '%SF%' AND p.is_active = 1 THEN 1 END) AS SF,
    COUNT(CASE WHEN p.position LIKE '%PF%' AND p.is_active = 1 THEN 1 END) AS PF,
    COUNT(CASE WHEN p.position LIKE '%C%'  AND p.is_active = 1 THEN 1 END) AS C,
    
    -- Needed players per position
    (team_stats.total_teams * 5) - COUNT(CASE WHEN p.position LIKE '%PG%' AND p.is_active = 1 THEN 1 END) AS PG_needed,
    (team_stats.total_teams * 5) - COUNT(CASE WHEN p.position LIKE '%SG%' AND p.is_active = 1 THEN 1 END) AS SG_needed,
    (team_stats.total_teams * 5) - COUNT(CASE WHEN p.position LIKE '%SF%' AND p.is_active = 1 THEN 1 END) AS SF_needed,
    (team_stats.total_teams * 5) - COUNT(CASE WHEN p.position LIKE '%PF%' AND p.is_active = 1 THEN 1 END) AS PF_needed,
    (team_stats.total_teams * 5) - COUNT(CASE WHEN p.position LIKE '%C%'  AND p.is_active = 1 THEN 1 END) AS C_needed,
    
    -- Warning message
    CASE
        WHEN
            COUNT(CASE WHEN p.position LIKE '%PG%' AND p.is_active = 1 THEN 1 END) >= team_stats.total_teams * 5 AND
            COUNT(CASE WHEN p.position LIKE '%SG%' AND p.is_active = 1 THEN 1 END) >= team_stats.total_teams * 5 AND
            COUNT(CASE WHEN p.position LIKE '%SF%' AND p.is_active = 1 THEN 1 END) >= team_stats.total_teams * 5 AND
            COUNT(CASE WHEN p.position LIKE '%PF%' AND p.is_active = 1 THEN 1 END) >= team_stats.total_teams * 5 AND
            COUNT(CASE WHEN p.position LIKE '%C%'  AND p.is_active = 1 THEN 1 END) >= team_stats.total_teams * 5
        THEN 'All positions sufficiently staffed'
        ELSE CONCAT(
            'Insufficient players at ',
            TRIM(BOTH ', ' FROM
                CONCAT(
                    CASE WHEN COUNT(CASE WHEN p.position LIKE '%PG%' AND p.is_active = 1 THEN 1 END) < team_stats.total_teams * 5 THEN 'PG, ' ELSE '' END,
                    CASE WHEN COUNT(CASE WHEN p.position LIKE '%SG%' AND p.is_active = 1 THEN 1 END) < team_stats.total_teams * 5 THEN 'SG, ' ELSE '' END,
                    CASE WHEN COUNT(CASE WHEN p.position LIKE '%SF%' AND p.is_active = 1 THEN 1 END) < team_stats.total_teams * 5 THEN 'SF, ' ELSE '' END,
                    CASE WHEN COUNT(CASE WHEN p.position LIKE '%PF%' AND p.is_active = 1 THEN 1 END) < team_stats.total_teams * 5 THEN 'PF, ' ELSE '' END,
                    CASE WHEN COUNT(CASE WHEN p.position LIKE '%C%'  AND p.is_active = 1 THEN 1 END) < team_stats.total_teams * 5 THEN 'C, '  ELSE '' END
                )
            )
        )
    END AS warning

FROM players p
JOIN (
    SELECT COUNT(*) AS total_teams FROM teams
) AS team_stats ON 1=1;
