CREATE OR REPLACE VIEW longest_tenured_players AS
WITH SeasonGroups AS (
    -- Identify consecutive season streaks using a difference between season_id and row number
    SELECT 
        pss.team_id,
        pss.player_id,
        pss.season_id,
        (pss.season_id - ROW_NUMBER() OVER (PARTITION BY pss.team_id, pss.player_id ORDER BY pss.season_id)) AS streak_group
    FROM 
        player_season_stats pss
),
StreakLengths AS (
    -- Calculate the length of each streak and the min/max season_id per streak
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
        COUNT(*) >= 3  -- Ensure at least 3 consecutive seasons
),
MaxStreak AS (
    -- Select the longest streak for each player-team
    SELECT 
        team_id,
        player_id,
        streak_start,
        streak_end,
        streak_length,
        ROW_NUMBER() OVER (PARTITION BY team_id, player_id ORDER BY streak_length DESC, streak_start ASC) AS rn
    FROM 
        StreakLengths
),
TeamTenure AS (
    -- Select the longest streak and ensure earliest season for the team
    SELECT 
        sl.team_id,
        sl.player_id,
        MIN(sl.streak_start) AS earliest_season,
        MAX(sl.streak_length) AS longest_streak,
        CONCAT(MIN(sl.streak_start), ' to ', MAX(CASE WHEN sl.rn = 1 THEN sl.streak_end END)) AS season_span,
        COUNT(DISTINCT pss.season_id) AS seasons_played
    FROM 
        MaxStreak sl
    JOIN 
        player_season_stats pss ON pss.team_id = sl.team_id AND pss.player_id = sl.player_id
    WHERE 
        sl.rn = 1  -- Select only the longest streak
        AND EXISTS (
            -- Ensure player is active in the most recent season for the team
            SELECT 1
            FROM player_season_stats pss2
            WHERE pss2.team_id = sl.team_id
            AND pss2.player_id = sl.player_id
            AND pss2.season_id = (
                SELECT MAX(pss3.season_id)
                FROM player_season_stats pss3
                WHERE pss3.team_id = sl.team_id
            )
        )
    GROUP BY 
        sl.team_id, sl.player_id
    HAVING 
        MIN(sl.streak_start) = (
            SELECT MIN(pss4.season_id)
            FROM player_season_stats pss4
            WHERE pss4.team_id = sl.team_id
            GROUP BY pss4.team_id
        )
)
SELECT 
    tt.team_id,
    t.name AS team_name,
    tt.player_id,
    p.name AS player_name,
    tt.earliest_season,
    tt.longest_streak,
    tt.season_span,
    tt.seasons_played,
    RANK() OVER (ORDER BY tt.longest_streak DESC) AS tenure_rank
FROM 
    TeamTenure tt
JOIN 
    teams t ON tt.team_id = t.id
JOIN 
    players p ON tt.player_id = p.id
ORDER BY 
    tt.longest_streak DESC, tt.earliest_season, tt.team_id, tt.player_id;