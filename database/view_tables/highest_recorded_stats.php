CREATE VIEW highest_player_game_stats AS
SELECT * FROM (
    SELECT
        'points' AS stat_type,
        player_id,
        game_id,
        team_id,
        season_id,
        points AS stat_value,
        minutes,
        created_at
    FROM player_game_stats
    ORDER BY points DESC
    LIMIT 1
) AS top_points

UNION ALL

SELECT * FROM (
    SELECT
        'rebounds' AS stat_type,
        player_id,
        game_id,
        team_id,
        season_id,
        rebounds AS stat_value,
        minutes,
        created_at
    FROM player_game_stats
    ORDER BY rebounds DESC
    LIMIT 1
) AS top_rebounds

UNION ALL

SELECT * FROM (
    SELECT
        'assists' AS stat_type,
        player_id,
        game_id,
        team_id,
        season_id,
        assists AS stat_value,
        minutes,
        created_at
    FROM player_game_stats
    ORDER BY assists DESC
    LIMIT 1
) AS top_assists

UNION ALL

SELECT * FROM (
    SELECT
        'steals' AS stat_type,
        player_id,
        game_id,
        team_id,
        season_id,
        steals AS stat_value,
        minutes,
        created_at
    FROM player_game_stats
    ORDER BY steals DESC
    LIMIT 1
) AS top_steals

UNION ALL

SELECT * FROM (
    SELECT
        'blocks' AS stat_type,
        player_id,
        game_id,
        team_id,
        season_id,
        blocks AS stat_value,
        minutes,
        created_at
    FROM player_game_stats
    ORDER BY blocks DESC
    LIMIT 1
) AS top_blocks;
