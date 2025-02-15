CREATE OR REPLACE VIEW top_10_single_game_points AS
SELECT 
    pgs.player_id,
    players.name AS player_name,
    pgs.points,
    pgs.game_id,
    pgs.season_id,
    players.draft_id,
    seasons.name AS season_name,
    player_team.name AS player_team,
    opponent_team.name AS opponent_team
FROM player_game_stats pgs
JOIN players ON pgs.player_id = players.id
JOIN seasons ON pgs.season_id = seasons.id
JOIN teams AS player_team ON pgs.team_id = player_team.id
JOIN schedules ON pgs.game_id = schedules.game_id
JOIN teams AS opponent_team ON opponent_team.id = 
    CASE 
        WHEN schedules.home_id = pgs.team_id THEN schedules.away_id
        ELSE schedules.home_id
    END
ORDER BY pgs.points DESC
LIMIT 10;



CREATE OR REPLACE VIEW top_10_single_game_assists AS
SELECT 
    pgs.player_id,
    players.name AS player_name,
    pgs.assists,
    pgs.game_id,
    pgs.season_id,
    players.draft_id,
    seasons.name AS season_name,
    player_team.name AS player_team,
    opponent_team.name AS opponent_team
FROM player_game_stats pgs
JOIN players ON pgs.player_id = players.id
JOIN seasons ON pgs.season_id = seasons.id
JOIN teams AS player_team ON pgs.team_id = player_team.id
JOIN schedules ON pgs.game_id = schedules.game_id
JOIN teams AS opponent_team ON opponent_team.id = 
    CASE 
        WHEN schedules.home_id = pgs.team_id THEN schedules.away_id
        ELSE schedules.home_id
    END
ORDER BY pgs.assists DESC
LIMIT 10;


CREATE OR REPLACE VIEW top_10_single_game_rebounds AS
SELECT 
    pgs.player_id,
    players.name AS player_name,
    pgs.rebounds,
    pgs.game_id,
    pgs.season_id,
    players.draft_id,
    seasons.name AS season_name,
    player_team.name AS player_team,
    opponent_team.name AS opponent_team
FROM player_game_stats pgs
JOIN players ON pgs.player_id = players.id
JOIN seasons ON pgs.season_id = seasons.id
JOIN teams AS player_team ON pgs.team_id = player_team.id
JOIN schedules ON pgs.game_id = schedules.game_id
JOIN teams AS opponent_team ON opponent_team.id = 
    CASE 
        WHEN schedules.home_id = pgs.team_id THEN schedules.away_id
        ELSE schedules.home_id
    END
ORDER BY pgs.rebounds DESC
LIMIT 10;


CREATE OR REPLACE VIEW top_10_single_game_steals AS
SELECT 
    pgs.player_id,
    players.name AS player_name,
    pgs.steals,
    pgs.game_id,
    pgs.season_id,
    players.draft_id,
    seasons.name AS season_name,
    player_team.name AS player_team,
    opponent_team.name AS opponent_team
FROM player_game_stats pgs
JOIN players ON pgs.player_id = players.id
JOIN seasons ON pgs.season_id = seasons.id
JOIN teams AS player_team ON pgs.team_id = player_team.id
JOIN schedules ON pgs.game_id = schedules.game_id
JOIN teams AS opponent_team ON opponent_team.id = 
    CASE 
        WHEN schedules.home_id = pgs.team_id THEN schedules.away_id
        ELSE schedules.home_id
    END
ORDER BY pgs.steals DESC
LIMIT 10;


CREATE OR REPLACE VIEW top_10_single_game_blocks AS
SELECT 
    pgs.player_id,
    players.name AS player_name,
    pgs.blocks,
    pgs.game_id,
    pgs.season_id,
    players.draft_id,
    seasons.name AS season_name,
    player_team.name AS player_team,
    opponent_team.name AS opponent_team
FROM player_game_stats pgs
JOIN players ON pgs.player_id = players.id
JOIN seasons ON pgs.season_id = seasons.id
JOIN teams AS player_team ON pgs.team_id = player_team.id
JOIN schedules ON pgs.game_id = schedules.game_id
JOIN teams AS opponent_team ON opponent_team.id = 
    CASE 
        WHEN schedules.home_id = pgs.team_id THEN schedules.away_id
        ELSE schedules.home_id
    END
ORDER BY pgs.blocks DESC
LIMIT 10;