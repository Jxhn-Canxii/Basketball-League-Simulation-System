CREATE TABLE player_season_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    player_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    season_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(255),

    -- Basic Per-Game Averages
    avg_points_per_game DECIMAL(5, 2) DEFAULT 0,
    avg_rebounds_per_game DECIMAL(5, 2) DEFAULT 0,
    avg_assists_per_game DECIMAL(5, 2) DEFAULT 0,
    avg_steals_per_game DECIMAL(5, 2) DEFAULT 0,
    avg_blocks_per_game DECIMAL(5, 2) DEFAULT 0,
    avg_turnovers_per_game DECIMAL(5, 2) DEFAULT 0,
    avg_fouls_per_game DECIMAL(5, 2) DEFAULT 0,

    -- Shooting Efficiency
    total_field_goals_made INT DEFAULT 0,
    total_field_goal_attempts INT DEFAULT 0,
    total_three_pointers_made INT DEFAULT 0,
    total_three_point_attempts INT DEFAULT 0,
    total_free_throws_made INT DEFAULT 0,
    total_free_throw_attempts INT DEFAULT 0,

    -- Totals
    total_points INT DEFAULT 0,
    total_rebounds INT DEFAULT 0,
    total_assists INT DEFAULT 0,
    total_steals INT DEFAULT 0,
    total_blocks INT DEFAULT 0,
    total_turnovers INT DEFAULT 0,
    total_fouls INT DEFAULT 0,
    total_minutes_played INT DEFAULT 0,
    total_games_played INT DEFAULT 0,

    -- Advanced Metrics
    per DECIMAL(6, 3) GENERATED ALWAYS AS (
        (total_points + total_rebounds + total_assists + total_steals + total_blocks - (total_field_goal_attempts - total_field_goals_made) - total_turnovers) / total_minutes_played
    ) STORED,
    
    ts_percent DECIMAL(6, 3) GENERATED ALWAYS AS (
        total_points / (2 * (total_field_goal_attempts + (0.44 * total_free_throw_attempts)))
    ) STORED,

    eff DECIMAL(6, 3) GENERATED ALWAYS AS (
        (total_points + total_rebounds + total_assists + total_steals + total_blocks - (total_field_goal_attempts + total_free_throw_attempts + total_turnovers))
    ) STORED,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
