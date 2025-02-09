CREATE TABLE player_game_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL DEFAULT 0,  -- Default 0 for season_id
    game_id VARCHAR(255),    -- Default 0 for game_id
    player_id INT NOT NULL DEFAULT 0,  -- Default 0 for player_id
    team_id INT NOT NULL DEFAULT 0,    -- Default 0 for team_id
    minutes FLOAT NOT NULL DEFAULT 0,  -- Default 0 for minutes
    points INT NOT NULL DEFAULT 0,     -- Default 0 for points
    rebounds INT NOT NULL DEFAULT 0,   -- Default 0 for rebounds
    assists INT NOT NULL DEFAULT 0,    -- Default 0 for assists
    steals INT NOT NULL DEFAULT 0,     -- Default 0 for steals
    blocks INT NOT NULL DEFAULT 0,     -- Default 0 for blocks
    turnovers INT NOT NULL DEFAULT 0,  -- Default 0 for turnovers
    fouls INT NOT NULL DEFAULT 0,      -- Default 0 for fouls
    field_goal_attempts INT NOT NULL DEFAULT 0,  -- Default 0 for field_goal_attempts
    field_goals_made INT NOT NULL DEFAULT 0,     -- Default 0 for field_goals_made
    three_point_attempts INT NOT NULL DEFAULT 0, -- Default 0 for three_point_attempts
    three_pointers_made INT NOT NULL DEFAULT 0,  -- Default 0 for three_pointers_made
    free_throw_attempts INT NOT NULL DEFAULT 0,  -- Default 0 for free_throw_attempts
    free_throws_made INT NOT NULL DEFAULT 0,     -- Default 0 for free_throws_made
    
    -- Two-Point Stats (Manually Input)
    two_point_attempts INT NOT NULL DEFAULT 0,    -- Default 0 for two_point_attempts
    two_pointers_made INT NOT NULL DEFAULT 0,      -- Default 0 for two_pointers_made
    
    -- Advanced Metrics (Generated Fields)
    per FLOAT GENERATED ALWAYS AS (
        (points + rebounds + assists + steals + blocks - (field_goal_attempts - field_goals_made) - turnovers) 
        / NULLIF(minutes, 0)  -- Avoid division by zero
    ) STORED,
    
    ts_percent FLOAT GENERATED ALWAYS AS (
        points / NULLIF(2 * (field_goal_attempts + (0.44 * free_throw_attempts)), 0)  -- Avoid division by zero
    ) STORED,
    
    eff FLOAT GENERATED ALWAYS AS (
        (points + rebounds + assists + steals + blocks - (field_goal_attempts + free_throw_attempts + turnovers))
    ) STORED,
    
    -- Added Stored Columns for Shooting Percentages
    field_goal_percentage FLOAT GENERATED ALWAYS AS (
        CASE
            WHEN field_goal_attempts = 0 THEN 0
            ELSE (field_goals_made / field_goal_attempts) * 100
        END
    ) STORED,
    
    three_point_percentage FLOAT GENERATED ALWAYS AS (
        CASE
            WHEN three_point_attempts = 0 THEN 0
            ELSE (three_pointers_made / three_point_attempts) * 100
        END
    ) STORED,
    
    free_throw_percentage FLOAT GENERATED ALWAYS AS (
        CASE
            WHEN free_throw_attempts = 0 THEN 0
            ELSE (free_throws_made / free_throw_attempts) * 100
        END
    ) STORED,
    
    -- Added Stored Column for Two-Point Percentage
    two_point_percentage FLOAT GENERATED ALWAYS AS (
        CASE
            WHEN two_point_attempts = 0 THEN 0
            ELSE (two_pointers_made / two_point_attempts) * 100
        END
    ) STORED,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
