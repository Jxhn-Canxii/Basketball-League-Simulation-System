CREATE TABLE coaches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    coach_iq INT UNSIGNED DEFAULT 70,
    age INT UNSIGNED NOT NULL,
    retirement_age INT UNSIGNED DEFAULT 65,
    experience_years INT UNSIGNED DEFAULT 0,
    contract_years INT UNSIGNED DEFAULT 0,
    career_wins INT UNSIGNED DEFAULT 0,
    career_losses INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Virtual Column: winning percentage calculated dynamically
    winning_percentage DECIMAL(5, 2) GENERATED ALWAYS AS (
        CASE 
            WHEN (career_wins + career_losses) > 0 THEN
                ROUND(career_wins / (career_wins + career_losses) * 100, 2)
            ELSE
                0
        END
    ) STORED,

    CONSTRAINT fk_coaches_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);
