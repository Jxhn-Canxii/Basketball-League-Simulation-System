CREATE OR REPLACE VIEW player_distribution_by_country AS
SELECT 
    country,
    COUNT(id) AS total_players,
    SUM(CASE WHEN is_rookie = 1 THEN 1 ELSE 0 END) AS total_rookies,
    SUM(CASE WHEN is_injured = 1 THEN 1 ELSE 0 END) AS total_injured,
    ROUND(SUM(CASE WHEN is_injured = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS injured_percentage,
    ROUND(SUM(CASE WHEN is_rookie = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS rookie_percentage
FROM players
GROUP BY country
ORDER BY total_players DESC;
