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
    FROM player_season_stats ps
    JOIN teams t ON ps.team_id = t.id
    JOIN players p ON ps.player_id = p.id
    WHERE ps.role = ''star player''
    GROUP BY ps.season_id;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
