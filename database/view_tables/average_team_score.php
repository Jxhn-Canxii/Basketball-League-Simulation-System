CREATE VIEW avg_league_scores AS
SELECT 
    ROUND(AVG(home_score)) AS avg_home_score,
    ROUND(AVG(away_score)) AS avg_away_score,
    ROUND(AVG(home_score + away_score) / 2) AS avg_total_score
FROM schedules;
