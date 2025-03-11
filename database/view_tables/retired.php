CREATE VIEW retired_players AS
SELECT players.name AS player_name,
       teams.name AS team_name,
       dt.name As drafted_team_name,
       transactions.season_id
FROM transactions
JOIN players ON transactions.player_id = players.id
JOIN teams ON transactions.from_team_id = teams.id
JOIN teams dt ON players.drafted_team_id = dt.id
WHERE transactions.status = 'retired';
