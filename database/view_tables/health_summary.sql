CREATE OR REPLACE VIEW health_rating_summary AS
SELECT 
    COUNT(id) AS total_players,
    SUM(CASE WHEN injury_prone_percentage > 70 THEN 1 ELSE 0 END) AS above_70,
    SUM(CASE WHEN injury_prone_percentage BETWEEN 61 AND 70 THEN 1 ELSE 0 END) AS between_61_70,
    SUM(CASE WHEN injury_prone_percentage <= 60 AND injury_prone_percentage >= 20 THEN 1 ELSE 0 END) AS below_60,
    SUM(CASE WHEN injury_prone_percentage <= 20 AND injury_prone_percentage >= 1 THEN 1 ELSE 0 END) AS below_20,
    SUM(CASE WHEN injury_prone_percentage <= 0 THEN 1 ELSE 0 END) AS healthy,
    ROUND(SUM(CASE WHEN injury_prone_percentage > 70 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS above_70_percent,
    ROUND(SUM(CASE WHEN injury_prone_percentage BETWEEN 61 AND 70 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS between_61_70_percent,
    ROUND(SUM(CASE WHEN injury_prone_percentage <= 60 AND injury_prone_percentage >= 20 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS below_60_percent,
    ROUND(SUM(CASE WHEN injury_prone_percentage <= 20 AND injury_prone_percentage >= 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS below_20_percent,
    ROUND(SUM(CASE WHEN injury_prone_percentage <= 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(id), 2) AS zero_percent
FROM players;
