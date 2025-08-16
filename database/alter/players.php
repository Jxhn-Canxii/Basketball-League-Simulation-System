ALTER TABLE players
ADD COLUMN is_injured BOOLEAN DEFAULT FALSE,
ADD COLUMN injury_type VARCHAR(255) NULL,
ADD COLUMN fatigue INT DEFAULT 0,
ADD COLUMN injury_history INT DEFAULT 0,
ADD COLUMN injury_recovery_games INT DEFAULT 0

<!-- 26/04/25 -->
ALTER TABLE players
ADD COLUMN morale INT(2) DEFAULT 0,


ALTER TABLE players
ADD COLUMN loyalty_rating TINYINT GENERATED ALWAYS AS (
    LEAST(100, GREATEST(0,
        ROUND(
            (overall_rating * 0.3) +
            (work_ethic_rating * 0.3) +
            (morale * 0.2) +
            IF(age >= 28, 10, 0)
        )
    ))
) STORED,

ADD COLUMN satisfaction_rating TINYINT GENERATED ALWAYS AS (
    LEAST(100, GREATEST(0,
        ROUND(
            (morale * 0.4) +
            (work_ethic_rating * 0.3) +
            (leadership_rating * 0.2) +
            (overall_rating * 0.1)
        )
    ))
) STORED,

ADD COLUMN ambition_rating TINYINT GENERATED ALWAYS AS (
    LEAST(100, GREATEST(0,
        ROUND(
            ((100 - age) * 0.2) +
            (overall_rating * 0.3) +
            (work_ethic_rating * 0.3) +
            (clutch_rating * 0.2)
        )
    ))
) STORED,

ADD COLUMN negotiation_skill_rating TINYINT GENERATED ALWAYS AS (
    LEAST(100, GREATEST(0,
        ROUND(
            (basketball_iq_rating * 0.4) +
            (overall_rating * 0.3) +
            IF(age >= 26, 15, 0)
        )
    ))
) STORED;

ALTER TABLE players
ADD COLUMN bashing_factor TINYINT GENERATED ALWAYS AS (
    LEAST(100, GREATEST(0,
        ROUND(
            (strength_rating * 0.3) +
            (athleticism_rating * 0.2) +
            (100 - basketball_iq_rating) * 0.25 +  -- Lower IQ = more aggression
            (100 - work_ethic_rating) * 0.15 +   -- Poor work ethic = more bashing
            IF(injury_prone_percentage > 70, 10, 0) +  -- Injury-prone players more aggressive
            IF(is_rookie, 5, 0)                     -- Rookies get slight boost
        )
    ))
) STORED COMMENT '0-100 scale of player aggression/controversy';

