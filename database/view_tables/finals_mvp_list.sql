CREATE OR REPLACE VIEW finals_mvp_with_stats AS
SELECT
    p.id   AS player_id,
    p.name AS player_name,
    p.is_active,
    p.role AS player_role,

    /* current club reported once */
    GROUP_CONCAT(DISTINCT t1.name ORDER BY t1.name)              AS current_team_names,

    /* Finals‑MVP‑winning clubs per season */
    GROUP_CONCAT(DISTINCT CONCAT(t2.name,' (',s.name,')')
                 ORDER BY s.name)                                AS mvp_winning_team_names,

    /* latest stats, awards … */
    MAX(ps.total_games)             AS total_games,
    MAX(ps.total_games_played)      AS total_games_played,
    MAX(ps.avg_minutes_per_game)    AS avg_minutes_per_game,
    MAX(ps.avg_points_per_game)     AS avg_points_per_game,
    MAX(ps.avg_rebounds_per_game)   AS avg_rebounds_per_game,
    MAX(ps.avg_assists_per_game)    AS avg_assists_per_game,
    MAX(ps.avg_steals_per_game)     AS avg_steals_per_game,
    MAX(ps.avg_blocks_per_game)     AS avg_blocks_per_game,
    MAX(ps.avg_turnovers_per_game)  AS avg_turnovers_per_game,
    MAX(ps.avg_fouls_per_game)      AS avg_fouls_per_game,
    MAX(ps.total_points)            AS total_points,
    MAX(ps.total_rebounds)          AS total_rebounds,
    MAX(ps.total_assists)           AS total_assists,
    MAX(ps.total_steals)            AS total_steals,
    MAX(ps.total_blocks)            AS total_blocks,
    MAX(ps.total_turnovers)         AS total_turnovers,
    MAX(ps.total_fouls)             AS total_fouls,
    MAX(ps.created_at)              AS stats_created_at,
    MAX(ps.updated_at)              AS stats_updated_at,

    ( SELECT GROUP_CONCAT(CONCAT(sa.award_name,' (',season.name,')')
                          ORDER BY season.name)
      FROM   season_awards sa
      JOIN   seasons season ON sa.season_id = season.id
      WHERE  sa.player_id = p.id )                           AS awards_won

FROM   seasons s
LEFT JOIN players            p  ON s.finals_mvp_id  = p.id
LEFT JOIN player_season_stats ps ON ps.player_id     = p.id
LEFT JOIN teams              t1 ON p.team_id        = t1.id
LEFT JOIN teams              t2 ON s.finals_winner_id = t2.id
WHERE  s.finals_mvp_id IS NOT NULL
GROUP BY p.id, p.name, p.role, p.is_active
ORDER BY player_name;
