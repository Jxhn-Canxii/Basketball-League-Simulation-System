CREATE OR REPLACE VIEW `team_reputation_view` AS
SELECT
    s.team_id,
    s.team_name,
    s.team_city,
    s.season_id,
    s.wins,
    s.chemistry,
    s.championships,
    s.conference_championships,
    s.conference_finals_appearances,
    s.finals_appearances,
    s.playoff_appearances,
    s.is_defending_champion,
    s.streak_status,

    -- Previous season comparisons
    prev.wins AS prev_wins,
    prev.overall_rank AS prev_rank,
    prev.chemistry AS prev_chemistry,

    -- Deltas
    (s.wins - COALESCE(prev.wins, s.wins)) AS wins_diff,
    (COALESCE(prev.overall_rank, s.overall_rank) - s.overall_rank) AS rank_improvement,
    (s.chemistry - COALESCE(prev.chemistry, s.chemistry)) AS chemistry_diff,

    -- Reputation Score
    ROUND(
        (s.wins * 0.16) +
        (s.playoff_appearances * 0.12) +
        (s.finals_appearances * 0.10) +
        (s.conference_championships * 0.10) +
        (s.championships * 0.20) +
        (CASE WHEN s.is_defending_champion = 1 THEN 10 ELSE 0 END) * 0.08 +
        (s.chemistry * 0.10) +
        (CASE WHEN s.streak_status = 'win' THEN 5 ELSE 0 END) * 0.05 +

        ((s.wins - COALESCE(prev.wins, s.wins)) * 0.25) +
        ((COALESCE(prev.overall_rank, s.overall_rank) - s.overall_rank) * 0.3) +
        ((s.chemistry - COALESCE(prev.chemistry, s.chemistry)) * 0.15)
    , 2) AS reputation_score,

    -- Fanbase Estimate (scaled with market size, no negatives)
    FLOOR(
        GREATEST(
            0,
            (
                (s.wins * 0.16) +
                (s.playoff_appearances * 0.12) +
                (s.finals_appearances * 0.10) +
                (s.conference_championships * 0.10) +
                (s.championships * 0.20) +
                (CASE WHEN s.is_defending_champion = 1 THEN 10 ELSE 0 END) * 0.08 +
                (s.chemistry * 0.10) +
                (CASE WHEN s.streak_status = 'win' THEN 5 ELSE 0 END) * 0.05 +
                ((s.wins - COALESCE(prev.wins, s.wins)) * 0.25) +
                ((COALESCE(prev.overall_rank, s.overall_rank) - s.overall_rank) * 0.3) +
                ((s.chemistry - COALESCE(prev.chemistry, s.chemistry)) * 0.15)
            ) * t.market_size * 1000 -- Multiply by market size and a scale factor
        )
    ) AS estimated_fans

FROM standings_view s
LEFT JOIN standings_snapshots prev
    ON s.team_id = prev.team_id
    AND prev.season_id = s.season_id - 1
INNER JOIN teams t
    ON s.team_id = t.id
ORDER BY reputation_score DESC;
