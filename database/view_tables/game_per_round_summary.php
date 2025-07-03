CREATE OR REPLACE VIEW view_round_game_counts AS
SELECT 
    s.round,
    SUM(CASE WHEN th.conference_id = ta.conference_id THEN 1 ELSE 0 END) AS intra_games,
    SUM(CASE WHEN th.conference_id != ta.conference_id THEN 1 ELSE 0 END) AS inter_games,
    COUNT(*) AS total_games
FROM schedules s
JOIN teams th ON s.home_id = th.id
JOIN teams ta ON s.away_id = ta.id
GROUP BY s.round
ORDER BY s.round;
