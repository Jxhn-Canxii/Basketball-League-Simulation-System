CREATE OR REPLACE VIEW team_positional_needs_view AS
SELECT
    team_id,

    -- Evaluate PF needs
    AVG(CASE WHEN position = 'PF' THEN defense_rating END) AS pf_defense,
    AVG(CASE WHEN position = 'PF' THEN two_point_rating + strength_rating END) / 2 AS pf_offense,

    -- Evaluate SG needs
    AVG(CASE WHEN position = 'SG' THEN defense_rating END) AS sg_defense,
    AVG(CASE WHEN position = 'SG' THEN three_point_rating + shooting_rating END) / 2 AS sg_offense,

    -- Evaluate C needs
    AVG(CASE WHEN position = 'C' THEN rebounding_rating + defense_rating END) / 2 AS c_defense,
    AVG(CASE WHEN position = 'C' THEN two_point_rating + strength_rating END) / 2 AS c_offense,

    -- Evaluate PG needs
    AVG(CASE WHEN position = 'PG' THEN passing_rating + basketball_iq_rating END) / 2 AS pg_playmaking,
    AVG(CASE WHEN position = 'PG' THEN shooting_rating + three_point_rating END) / 2 AS pg_offense,

    -- Evaluate SF needs
    AVG(CASE WHEN position = 'SF' THEN athleticism_rating + defense_rating END) / 2 AS sf_defense,
    AVG(CASE WHEN position = 'SF' THEN shooting_rating + clutch_rating END) / 2 AS sf_offense

FROM players
WHERE is_active = 1
GROUP BY team_id;
