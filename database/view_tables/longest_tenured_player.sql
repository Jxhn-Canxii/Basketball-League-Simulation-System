CREATE OR REPLACE VIEW longest_tenured_players AS
WITH SeasonGroups AS (
    SELECT 
        pss.team_id,
        pss.player_id,
        pss.season_id,
        (pss.season_id - ROW_NUMBER() OVER (PARTITION BY pss.team_id, pss.player_id ORDER BY pss.season_id)) AS streak_group
    FROM 
        player_season_stats pss
),
Streaks AS (
    SELECT 
        team_id,
        player_id,
        MIN(season_id) AS streak_start,
        MAX(season_id) AS streak_end,
        COUNT(*) AS streak_length
    FROM 
        SeasonGroups
    GROUP BY 
        team_id, player_id, streak_group
    HAVING 
        COUNT(*) >= 3
),
LatestTeamSeason AS (
    SELECT 
        team_id,
        MAX(season_id) AS latest_season
    FROM 
        player_season_stats
    GROUP BY 
        team_id
),
CurrentTenures AS (
    SELECT 
        s.team_id,
        s.player_id,
        s.streak_start,
        s.streak_end,
        s.streak_length
    FROM 
        Streaks s
    JOIN 
        LatestTeamSeason lts ON s.team_id = lts.team_id
    WHERE 
        s.streak_end = lts.latest_season  -- ensures player is active on team
),
RankedTenures AS (
    SELECT 
        ct.*,
        ROW_NUMBER() OVER (PARTITION BY ct.team_id ORDER BY ct.streak_length DESC, ct.streak_start ASC) AS rn
    FROM 
        CurrentTenures ct
),
FinalTenure AS (
    SELECT 
        rt.team_id,
        rt.player_id,
        rt.streak_start AS earliest_season,
        rt.streak_end AS latest_season,
        rt.streak_length,
        CONCAT(rt.streak_start, ' to ', rt.streak_end) AS season_span
    FROM 
        RankedTenures rt
    WHERE 
        rt.rn = 1
)
SELECT 
    ft.team_id,
    t.name AS team_name,
    ft.player_id,
    p.name AS player_name,
    ft.earliest_season,
    ft.latest_season,
    ft.streak_length AS longest_streak,
    ft.season_span,
    RANK() OVER (ORDER BY ft.streak_length DESC) AS tenure_rank
FROM 
    FinalTenure ft
JOIN 
    teams t ON ft.team_id = t.id
JOIN 
    players p ON ft.player_id = p.id
ORDER BY 
    ft.streak_length DESC, ft.earliest_season, ft.team_id, ft.player_id;
