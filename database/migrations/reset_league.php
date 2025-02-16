SET FOREIGN_KEY_CHECKS = 0; -- Temporarily disable foreign key checks

TRUNCATE TABLE drafts;
TRUNCATE TABLE head_to_head;
TRUNCATE TABLE injury_histories;
TRUNCATE TABLE players;
TRUNCATE TABLE player_game_stats;
TRUNCATE TABLE player_playoff_appearances;
TRUNCATE TABLE player_ratings;
TRUNCATE TABLE player_season_stats;
TRUNCATE TABLE schedules;
TRUNCATE TABLE seasons;
TRUNCATE TABLE season_awards;
TRUNCATE TABLE streak;
TRUNCATE TABLE trade_logs;
TRUNCATE TABLE trade_proposals;
TRUNCATE TABLE transactions;

SET FOREIGN_KEY_CHECKS = 1; -- Re-enable foreign key checks
