SET SESSION group_concat_max_len = 10000;

SET @sql = NULL;

SELECT 
    GROUP_CONCAT(
        DISTINCT CONCAT(
            'MAX(CASE WHEN t.name = ''', name, ''' THEN p.name END) AS `', name, '`'
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
            -- Get the latest player_season_stats.id for each team in a season
            SELECT season_id, team_id, MAX(id) AS latest_id
            FROM player_season_stats
            WHERE role = ''star player''
            GROUP BY season_id, team_id
        ) latest_stats 
        ON ps.team_id = latest_stats.team_id 
        AND ps.season_id = latest_stats.season_id 
        AND ps.id = latest_stats.latest_id
    ) ps
    JOIN teams t ON ps.team_id = t.id
    JOIN players p ON ps.player_id = p.id
    GROUP BY ps.season_id
    ORDER BY ps.season_id DESC;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
