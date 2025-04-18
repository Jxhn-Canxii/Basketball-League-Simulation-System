CREATE OR REPLACE VIEW players_by_team_and_position AS
SELECT 
    t.`id` AS team_id,
    t.`name` AS team_name,
    COUNT(CASE WHEN p.`position` REGEXP 'PG' THEN 1 END) AS `PG`,
    COUNT(CASE WHEN p.`position` REGEXP 'SG' THEN 1 END) AS `SG`,
    COUNT(CASE WHEN p.`position` REGEXP 'SF' THEN 1 END) AS `SF`,
    COUNT(CASE WHEN p.`position` REGEXP 'PF' THEN 1 END) AS `PF`,
    COUNT(CASE WHEN p.`position` REGEXP '\\bC\\b' THEN 1 END) AS `C`
FROM 
    `players` p
JOIN 
    `teams` t ON p.`team_id` = t.`id`
WHERE 
    p.`position` REGEXP '^(PG|SG|SF|PF|C)(\\/(PG|SG|SF|PF|C))*$'
GROUP BY 
    t.`id`, t.`name`
ORDER BY 
    t.`name`;