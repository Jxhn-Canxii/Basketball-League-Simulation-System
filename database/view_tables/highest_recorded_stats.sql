CREATE OR REPLACE VIEW highest_player_game_stats AS
SELECT * FROM (
    SELECT
        'points' AS stat_type,
        pgs.player_id,
        pl.name AS player_name,
        pgs.team_id,
        t.name AS team_name,
        pgs.season_id,
        s.name AS season_name,
        pgs.game_id,
        pgs.points AS stat_value,
        pgs.minutes,
        pgs.created_at
    FROM player_game_stats pgs
    JOIN players pl ON pgs.player_id = pl.id
    JOIN teams t ON pgs.team_id = t.id
    JOIN seasons s ON pgs.season_id = s.id
    ORDER BY pgs.points DESC
    LIMIT 1
) AS top_points

UNION ALL

SELECT * FROM (
    SELECT
        'rebounds' AS stat_type,
        pgs.player_id,
        pl.name AS player_name,
        pgs.team_id,
        t.name AS team_name,
        pgs.season_id,
        s.name AS season_name,
        pgs.game_id,
        pgs.rebounds AS stat_value,
        pgs.minutes,
        pgs.created_at
    FROM player_game_stats pgs
    JOIN players pl ON pgs.player_id = pl.id
    JOIN teams t ON pgs.team_id = t.id
    JOIN seasons s ON pgs.season_id = s.id
    ORDER BY pgs.rebounds DESC
    LIMIT 1
) AS top_rebounds

UNION ALL

SELECT * FROM (
    SELECT
        'assists' AS stat_type,
        pgs.player_id,
        pl.name AS player_name,
        pgs.team_id,
        t.name AS team_name,
        pgs.season_id,
        s.name AS season_name,
        pgs.game_id,
        pgs.assists AS stat_value,
        pgs.minutes,
        pgs.created_at
    FROM player_game_stats pgs
    JOIN players pl ON pgs.player_id = pl.id
    JOIN teams t ON pgs.team_id = t.id
    JOIN seasons s ON pgs.season_id = s.id
    ORDER BY pgs.assists DESC
    LIMIT 1
) AS top_assists

UNION ALL

SELECT * FROM (
    SELECT
        'steals' AS stat_type,
        pgs.player_id,
        pl.name AS player_name,
        pgs.team_id,
        t.name AS team_name,
        pgs.season_id,
        s.name AS season_name,
        pgs.game_id,
        pgs.steals AS stat_value,
        pgs.minutes,
        pgs.created_at
    FROM player_game_stats pgs
    JOIN players pl ON pgs.player_id = pl.id
    JOIN teams t ON pgs.team_id = t.id
    JOIN seasons s ON pgs.season_id = s.id
    ORDER BY pgs.steals DESC
    LIMIT 1
) AS top_steals

UNION ALL

SELECT * FROM (
    SELECT
        'blocks' AS stat_type,
        pgs.player_id,
        pl.name AS player_name,
        pgs.team_id,
        t.name AS team_name,
        pgs.season_id,
        s.name AS season_name,
        pgs.game_id,
        pgs.blocks AS stat_value,
        pgs.minutes,
        pgs.created_at
    FROM player_game_stats pgs
    JOIN players pl ON pgs.player_id = pl.id
    JOIN teams t ON pgs.team_id = t.id
    JOIN seasons s ON pgs.season_id = s.id
    ORDER BY pgs.blocks DESC
    LIMIT 1
) AS top_blocks;
