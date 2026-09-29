DELETE FROM `player_per_quarter_stats` WHERE game_id='S3-all-star';
DELETE FROM `player_per_quarter_stats` WHERE game_id='S3-all-rookie';

DELETE FROM `player_game_stats` WHERE game_id='S3-all-star';
DELETE FROM `player_game_stats` WHERE game_id='S3-all-rookie';

DELETE FROM `game_quarter_breakdown` WHERE game_id='S3-all-star';
DELETE FROM `game_quarter_breakdown` WHERE game_id='S3-all-rookie';

DELETE FROM `game_news` WHERE game_id='S3-all-star';
DELETE FROM `game_news` WHERE game_id='S3-all-rookie';

DELETE FROM `transactions` WHERE status='selected' AND season_id=3;