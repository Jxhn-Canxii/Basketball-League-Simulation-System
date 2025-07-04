CREATE OR REPLACE VIEW view_player_projection_extremes AS
WITH PlayerComparisons AS (
    SELECT 
        p.id AS player_id,
        CONCAT(p.name, ' (', IF(p.team_id = 0, 'Free Agent', t.acronym), ')') AS player_name,
        p.age AS player_age,
        p.position,
        p.role,
        p.overall_rating AS current_rating,
        ps.avg_points_per_game AS current_ppg,
        ps.avg_assists_per_game AS current_apg,
        ps.avg_rebounds_per_game AS current_rpg,
        
        -- Comparable player info
        cp.id AS comparable_player_id,
        CONCAT(cp.name, ' (', IF(cp.team_id = 0, 'Free Agent', ct.acronym), ')') AS comparable_player_name,
        cp.age AS comparable_player_age,
        cp.overall_rating AS comparable_rating,
        cps.avg_points_per_game AS comparable_ppg,
        cps.avg_assists_per_game AS comparable_apg,
        cps.avg_rebounds_per_game AS comparable_rpg,
        
        -- Similarity score
        ABS(p.overall_rating - cp.overall_rating) +
        ABS(COALESCE(ps.avg_points_per_game, 0) - COALESCE(cps.avg_points_per_game, 0)) +
        ABS(COALESCE(ps.avg_assists_per_game, 0) - COALESCE(cps.avg_assists_per_game, 0)) +
        ABS(COALESCE(ps.avg_rebounds_per_game, 0) - COALESCE(cps.avg_rebounds_per_game, 0))
        AS similarity_score,
        
        -- Rank for highest and lowest projections
        ROW_NUMBER() OVER (
            PARTITION BY p.id 
            ORDER BY 
                (cp.overall_rating + COALESCE(cps.avg_points_per_game, 0) + 
                 COALESCE(cps.avg_assists_per_game, 0) + COALESCE(cps.avg_rebounds_per_game, 0)) DESC
        ) AS high_projection_rank,
        ROW_NUMBER() OVER (
            PARTITION BY p.id 
            ORDER BY 
                (cp.overall_rating + COALESCE(cps.avg_points_per_game, 0) + 
                 COALESCE(cps.avg_assists_per_game, 0) + COALESCE(cps.avg_rebounds_per_game, 0)) ASC
        ) AS low_projection_rank

    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN player_season_stats ps 
        ON ps.player_id = p.id AND ps.season_id = (SELECT MAX(id) FROM seasons)
    JOIN players cp
        ON cp.id != p.id 
        AND cp.position = p.position 
        AND cp.role = p.role 
        AND cp.age > p.age
    JOIN teams ct ON cp.team_id = ct.id OR cp.team_id = 0
    JOIN player_season_stats cps 
        ON cps.player_id = cp.id AND cps.season_id = ps.season_id
)
SELECT 
    player_id,
    player_name,
    player_age,
    position,
    role,
    current_rating,
    current_ppg,
    current_apg,
    current_rpg,
    
    -- Highest projection (best-case scenario)
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_player_id END) AS best_projection_player_id,
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_player_name END) AS best_projection_player_name,
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_player_age END) AS best_projection_player_age,
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_rating END) AS best_projection_rating,
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_ppg END) AS best_projection_ppg,
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_apg END) AS best_projection_apg,
    MAX(CASE WHEN high_projection_rank = 1 THEN comparable_rpg END) AS best_projection_rpg,
    MIN(CASE WHEN high_projection_rank = 1 THEN similarity_score END) AS best_projection_similarity_score,
    
    -- Lowest projection (worst-case scenario)
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_player_id END) AS worst_projection_player_id,
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_player_name END) AS worst_projection_player_name,
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_player_age END) AS worst_projection_player_age,
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_rating END) AS worst_projection_rating,
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_ppg END) AS worst_projection_ppg,
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_apg END) AS worst_projection_apg,
    MAX(CASE WHEN low_projection_rank = 1 THEN comparable_rpg END) AS worst_projection_rpg,
    MIN(CASE WHEN low_projection_rank = 1 THEN similarity_score END) AS worst_projection_similarity_score

FROM PlayerComparisons
WHERE high_projection_rank = 1 OR low_projection_rank = 1
GROUP BY player_id, player_name, player_age, position, role, current_rating, current_ppg, current_apg, current_rpg;