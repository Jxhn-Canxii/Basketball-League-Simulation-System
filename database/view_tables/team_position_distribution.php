CREATE VIEW players_by_team_and_position AS
SELECT 
    t.`name` AS team_name,
    t.`id` AS team_id,
    COUNT(CASE WHEN p.`position` = 'PG' OR p.`position` LIKE 'PG%' THEN 1 END) AS `PG`,
    COUNT(CASE WHEN p.`position` = 'SG' OR p.`position` LIKE 'SG%' THEN 1 END) AS `SG`,
    COUNT(CASE WHEN p.`position` = 'SF' OR p.`position` LIKE 'SF%' THEN 1 END) AS `SF`,
    COUNT(CASE WHEN p.`position` = 'PF' OR p.`position` LIKE 'PF%' THEN 1 END) AS `PF`,
    COUNT(CASE WHEN p.`position` = 'C' OR p.`position` LIKE 'C%' THEN 1 END) AS `C`
FROM 
    `players` p
JOIN 
    `teams` t ON p.`team_id` = t.`id`
WHERE 
    p.`position` IN ('PG', 'SG', 'SF', 'PF', 'C')
    OR p.`position` LIKE '%/%'  -- This includes players with dual positions
GROUP BY 
    t.`id`
ORDER BY 
    t.`name`;
