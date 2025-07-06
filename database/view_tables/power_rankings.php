CREATE OR REPLACE VIEW view_team_power_rankings AS
SELECT
    s.*,
    (s.wins * 1.0) / NULLIF(s.wins + s.losses, 0) AS win_pct,
    ROW_NUMBER() OVER (
        PARTITION BY s.season_id
        ORDER BY 
            (s.wins * 1.0) / NULLIF(s.wins + s.losses, 0) DESC,
            s.score_difference DESC
    ) AS power_rank
FROM standings_view s;


CREATE OR REPLACE VIEW view_team_power_tiers AS
SELECT
    r.team_id,
    r.team_name,
    r.team_city,
    r.primary_color,
    r.secondary_color,
    r.team_acronym,
    r.conference_id,
    r.conference_name,
    r.season_id,
    r.wins,
    r.losses,
    r.score_difference,
    r.win_pct,
    r.power_rank,
    CASE
        WHEN r.power_rank <= 6 THEN 'Tier 1: Contenders'
        WHEN r.power_rank <= 16 THEN 'Tier 2: Playoff Locks'
        WHEN r.power_rank <= 32 THEN 'Tier 3: In the Mix'
        ELSE 'Tier 4: Rebuilders'
    END AS tier_label
FROM view_team_power_rankings r
WHERE r.season_id = (SELECT MAX(season_id) FROM standings_view);
