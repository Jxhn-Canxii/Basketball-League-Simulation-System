CREATE OR REPLACE VIEW active_player_position_summary_with_warning_and_needed AS
SELECT
    team_stats.total_teams,

    position_counts.PG,
    position_counts.SG,
    position_counts.SF,
    position_counts.PF,
    position_counts.C,

    GREATEST((team_stats.total_teams * 5) - position_counts.PG, 0) AS PG_needed,
    GREATEST((team_stats.total_teams * 5) - position_counts.SG, 0) AS SG_needed,
    GREATEST((team_stats.total_teams * 5) - position_counts.SF, 0) AS SF_needed,
    GREATEST((team_stats.total_teams * 5) - position_counts.PF, 0) AS PF_needed,
    GREATEST((team_stats.total_teams * 5) - position_counts.C, 0)  AS C_needed,

    CASE
        WHEN
            position_counts.PG >= team_stats.total_teams * 5 AND
            position_counts.SG >= team_stats.total_teams * 5 AND
            position_counts.SF >= team_stats.total_teams * 5 AND
            position_counts.PF >= team_stats.total_teams * 5 AND
            position_counts.C  >= team_stats.total_teams * 5
        THEN 'All positions sufficiently staffed'
        ELSE CONCAT(
            'Insufficient players at ',
            TRIM(BOTH ', ' FROM CONCAT(
                CASE WHEN position_counts.PG < team_stats.total_teams * 5 THEN 'PG, ' ELSE '' END,
                CASE WHEN position_counts.SG < team_stats.total_teams * 5 THEN 'SG, ' ELSE '' END,
                CASE WHEN position_counts.SF < team_stats.total_teams * 5 THEN 'SF, ' ELSE '' END,
                CASE WHEN position_counts.PF < team_stats.total_teams * 5 THEN 'PF, ' ELSE '' END,
                CASE WHEN position_counts.C  < team_stats.total_teams * 5 THEN 'C, '  ELSE '' END
            ))
        )
    END AS warning

FROM (
    SELECT
        COUNT(CASE WHEN position LIKE '%PG%' AND is_active = 1 THEN 1 END) AS PG,
        COUNT(CASE WHEN position LIKE '%SG%' AND is_active = 1 THEN 1 END) AS SG,
        COUNT(CASE WHEN position LIKE '%SF%' AND is_active = 1 THEN 1 END) AS SF,
        COUNT(CASE WHEN position LIKE '%PF%' AND is_active = 1 THEN 1 END) AS PF,
        COUNT(CASE WHEN position LIKE '%C%'  AND is_active = 1 THEN 1 END) AS C
    FROM players
) AS position_counts
CROSS JOIN (
    SELECT COUNT(*) AS total_teams FROM teams
) AS team_stats;
