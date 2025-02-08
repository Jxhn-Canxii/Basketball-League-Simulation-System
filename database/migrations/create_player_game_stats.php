CREATE TABLE player_game_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    game_id INT NOT NULL,
    player_id INT NOT NULL,
    team_id INT NOT NULL,
    minutes FLOAT NOT NULL,
    points INT NOT NULL,
    rebounds INT NOT NULL,
    assists INT NOT NULL,
    steals INT NOT NULL,
    blocks INT NOT NULL,
    turnovers INT NOT NULL,
    fouls INT NOT NULL,
    field_goal_attempts INT NOT NULL,
    field_goals_made INT NOT NULL,
    three_point_attempts INT NOT NULL,
    three_pointers_made INT NOT NULL,
    free_throw_attempts INT NOT NULL,
    free_throws_made INT NOT NULL,
    per FLOAT GENERATED ALWAYS AS (
        (points + rebounds + assists + steals + blocks - (field_goal_attempts - field_goals_made) - turnovers) / minutes
    ) STORED,
    ts_percent FLOAT GENERATED ALWAYS AS (
        points / (2 * (field_goal_attempts + (0.44 * free_throw_attempts)))
    ) STORED,
    eff FLOAT GENERATED ALWAYS AS (
        (points + rebounds + assists + steals + blocks - (field_goal_attempts + free_throw_attempts + turnovers))
    ) STORED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
