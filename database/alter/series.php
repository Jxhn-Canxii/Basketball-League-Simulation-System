CREATE OR REPLACE TABLE playoff_series (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    season_id BIGINT UNSIGNED NOT NULL,
    conference_id VARCHAR(50) NULL,
    round VARCHAR(50) NOT NULL,
    series_id VARCHAR(255) NULL,
    home_team_id BIGINT UNSIGNED NOT NULL,
    away_team_id BIGINT UNSIGNED NOT NULL,
    best_of INT NOT NULL DEFAULT 7,
    home_wins INT NOT NULL DEFAULT 0,
    away_wins INT NOT NULL DEFAULT 0,
    series_length INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 0,
    winner_team_id BIGINT UNSIGNED NULL,
    loser_team_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
);
