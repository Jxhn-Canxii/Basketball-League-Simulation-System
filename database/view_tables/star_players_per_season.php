SET SESSION group_concat_max_len = 10000;

SET @sql = NULL;

SELECT 
    GROUP_CONCAT(
        DISTINCT CONCAT(
            'GROUP_CONCAT(CASE WHEN t.name = ''', name, ''' THEN p.name END SEPARATOR '', '') AS `', name, '`'
        )
    ) INTO @sql
FROM teams;

SET @sql = CONCAT(
    'CREATE OR REPLACE VIEW star_players_per_season AS 
    SELECT ps.season_id, ', @sql, '
    FROM (
        SELECT ps.*
        FROM player_season_stats ps
        INNER JOIN (
            -- Get the latest player_season_stats.id for each player
            SELECT player_id, MAX(id) AS latest_id
            FROM player_season_stats
            GROUP BY player_id
        ) latest_stats ON ps.player_id = latest_stats.player_id AND ps.id = latest_stats.latest_id
    ) ps
    JOIN teams t ON ps.team_id = t.id
    JOIN players p ON ps.player_id = p.id
    WHERE ps.role = ''star player''
    GROUP BY ps.season_id
    ORDER BY ps.season_id DESC;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
