CREATE TABLE players (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(100),
    address VARCHAR(255),
    team_id BIGINT UNSIGNED,
    contract_years INT DEFAULT 0,
    contract_expires_at DATE,
    is_active BOOLEAN DEFAULT TRUE,
    is_rookie BOOLEAN DEFAULT FALSE,
    age INT NOT NULL,
    retirement_age INT DEFAULT 35,

    -- New Position Column
    position VARCHAR(10),

    role VARCHAR(100),

    -- Ratings
    shooting_rating DECIMAL(5,2) DEFAULT 0,
    two_point_rating DECIMAL(5,2) DEFAULT 0,
    three_point_rating DECIMAL(5,2) DEFAULT 0,
    free_throw_rating DECIMAL(5,2) DEFAULT 0,
    defense_rating DECIMAL(5,2) DEFAULT 0,
    passing_rating DECIMAL(5,2) DEFAULT 0,
    rebounding_rating DECIMAL(5,2) DEFAULT 0,
    athleticism_rating DECIMAL(5,2) DEFAULT 0,
    basketball_iq_rating DECIMAL(5,2) DEFAULT 0,
    strength_rating DECIMAL(5,2) DEFAULT 0,
    stamina_rating DECIMAL(5,2) DEFAULT 0,
    clutch_rating DECIMAL(5,2) DEFAULT 0,
    leadership_rating DECIMAL(5,2) DEFAULT 0,
    work_ethic_rating DECIMAL(5,2) DEFAULT 0,
    overall_rating DECIMAL(5,2) DEFAULT 0,
    type VARCHAR(50),

    -- Draft Information
    draft_id BIGINT UNSIGNED,
    draft_order INT DEFAULT NULL,
    drafted_team_id BIGINT UNSIGNED DEFAULT NULL,
    is_drafted BOOLEAN DEFAULT FALSE,
    draft_status VARCHAR(255) DEFAULT NULL,

    -- Injury & Fatigue
    injury_prone_percentage DECIMAL(5,2) DEFAULT 0,
    is_injured BOOLEAN DEFAULT FALSE,
    injury_type VARCHAR(255) DEFAULT NULL,
    fatigue DECIMAL(5,2) DEFAULT 0,
    injury_history TEXT DEFAULT NULL,
    injury_recovery_games INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
