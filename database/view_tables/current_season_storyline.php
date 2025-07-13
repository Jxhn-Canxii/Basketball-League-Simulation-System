CREATE OR REPLACE VIEW current_season_storyline AS
WITH champion_runs AS (
  SELECT id,
         finals_winner_id,
         LAG(id) OVER (PARTITION BY finals_winner_id ORDER BY id) AS prev_id,
         CASE 
           WHEN id = LAG(id) OVER (PARTITION BY finals_winner_id ORDER BY id) + 1 
                OR LAG(id) OVER (PARTITION BY finals_winner_id ORDER BY id) IS NULL 
           THEN 0 
           ELSE 1 
         END AS streak_breaker
  FROM seasons
  WHERE status > 10
),
consecutive_titles AS (
  SELECT id,
         finals_winner_id,
         COUNT(*) AS consecutive_titles
  FROM (
    SELECT id,
           finals_winner_id,
           SUM(streak_breaker) OVER (PARTITION BY finals_winner_id ORDER BY id) AS grp
    FROM champion_runs
  ) consecutive_groups
  GROUP BY id, finals_winner_id, grp
),
total_titles AS (
  SELECT s.id,
         COUNT(*) AS total_titles
  FROM seasons s
  JOIN seasons s2
    ON s2.finals_winner_id = s.finals_winner_id
   AND s2.status > 10
   AND s2.id <= s.id
  WHERE s.status > 10
  GROUP BY s.id
),
redemption AS (
  SELECT s.id,
         CASE WHEN EXISTS (
           SELECT 1 FROM seasons s2
           WHERE s2.finals_loser_id = s.finals_winner_id
             AND s2.id < s.id AND s2.status > 10
         ) THEN 1 ELSE 0 END AS is_redemption,
         (SELECT MAX(s2.id) FROM seasons s2
          WHERE s2.finals_loser_id = s.finals_winner_id
            AND s2.id < s.id AND s2.status > 10) AS redemption_season_id
  FROM seasons s
  WHERE s.status > 10
),
conference_runs AS (
  SELECT id,
         west_champion_id, east_champion_id,
         north_champion_id, south_champion_id,
         ROW_NUMBER() OVER (PARTITION BY west_champion_id ORDER BY id) - ROW_NUMBER() OVER (ORDER BY id) AS grp_west,
         ROW_NUMBER() OVER (PARTITION BY east_champion_id ORDER BY id) - ROW_NUMBER() OVER (ORDER BY id) AS grp_east,
         ROW_NUMBER() OVER (PARTITION BY north_champion_id ORDER BY id) - ROW_NUMBER() OVER (ORDER BY id) AS grp_north,
         ROW_NUMBER() OVER (PARTITION BY south_champion_id ORDER BY id) - ROW_NUMBER() OVER (ORDER BY id) AS grp_south
  FROM seasons
  WHERE status > 10
),
conference_titles AS (
  SELECT s.id,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.west_champion_id = s.west_champion_id AND s2.status > 10 AND s2.id <= s.id) AS total_west_titles,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.east_champion_id = s.east_champion_id AND s2.status > 10 AND s2.id <= s.id) AS total_east_titles,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.north_champion_id = s.north_champion_id AND s2.status > 10 AND s2.id <= s.id) AS total_north_titles,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.south_champion_id = s.south_champion_id AND s2.status > 10 AND s2.id <= s.id) AS total_south_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.west_champion_id = s.west_champion_id AND cr2.grp_west = cr.grp_west AND cr2.id <= s.id) AS consecutive_west_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.east_champion_id = s.east_champion_id AND cr2.grp_east = cr.grp_east AND cr2.id <= s.id) AS consecutive_east_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.north_champion_id = s.north_champion_id AND cr2.grp_north = cr.grp_north AND cr2.id <= s.id) AS consecutive_north_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.south_champion_id = s.south_champion_id AND cr2.grp_south = cr.grp_south AND cr2.id <= s.id) AS consecutive_south_titles
  FROM seasons s
  LEFT JOIN conference_runs cr ON cr.id = s.id
  WHERE s.status > 10
  GROUP BY s.id
),
conference_redemption AS (
  SELECT s.id,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.west_champion_id OR sch.away_id = s.west_champion_id)
             AND sch.winner_id != s.west_champion_id
             AND s2.finals_winner_id != s.west_champion_id
             AND s2.status > 10
         ) THEN 1 ELSE 0 END AS west_redemption,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.east_champion_id OR sch.away_id = s.east_champion_id)
             AND sch.winner_id != s.east_champion_id
             AND s2.finals_winner_id != s.east_champion_id
             AND s2.status > 10
         ) THEN 1 ELSE 0 END AS east_redemption,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.north_champion_id OR sch.away_id = s.north_champion_id)
             AND sch.winner_id != s.north_champion_id
             AND s2.finals_winner_id != s.north_champion_id
             AND s2.status > 10
         ) THEN 1 ELSE 0 END AS north_redemption,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.south_champion_id OR sch.away_id = s.south_champion_id)
             AND sch.winner_id != s.south_champion_id
             AND s2.finals_winner_id != s.south_champion_id
             AND s2.status > 10
         ) THEN 1 ELSE 0 END AS south_redemption
  FROM seasons s
  WHERE s.status > 10
),
award_counts AS (
  SELECT player_id, award_name, COUNT(*) AS award_count
  FROM season_awards
  WHERE award_name IN ('Best Overall Player', 'Best Defensive Player', 'Sixth Man of the Year', 'Most Improved Player', 'Rookie of the Season')
  GROUP BY player_id, award_name
),
awards AS (
  SELECT sa.season_id,
         MAX(CASE WHEN sa.award_name = 'Best Overall Player' THEN CONCAT(p.name, ' (', ac.award_count, 'x MVP)') END) AS best_overall,
         MAX(CASE WHEN sa.award_name = 'Best Defensive Player' THEN CONCAT(p.name, ' (', ac.award_count, 'x DPOY)') END) AS best_defense,
         MAX(CASE WHEN sa.award_name = 'Sixth Man of the Year' THEN CONCAT(p.name, ' (', ac.award_count, 'x 6th Man)') END) AS sixth_man,
         MAX(CASE WHEN sa.award_name = 'Most Improved Player' THEN CONCAT(p.name, ' (', ac.award_count, 'x MIP)') END) AS most_improved,
         MAX(CASE WHEN sa.award_name = 'Rookie of the Season' THEN CONCAT(p.name, ' (', ac.award_count, 'x ROY)') END) AS rookie_year
  FROM season_awards sa
  JOIN players p ON p.id = sa.player_id
  LEFT JOIN award_counts ac ON ac.player_id = sa.player_id AND ac.award_name = sa.award_name
  GROUP BY sa.season_id
),
finals_mvp AS (
  SELECT s.id AS season_id, p.name AS finals_mvp_name
  FROM seasons s
  JOIN players p ON p.id = s.finals_mvp_id
  WHERE s.status > 10
),
finals_details AS (
  SELECT s.id,
         MAX(sch.home_score) AS home_score,
         MAX(sch.away_score) AS away_score,
         MAX(t1.name) AS home_team,
         MAX(t2.name) AS away_team,
         MAX(sch.winner_id) AS winner_id,
         sch.round,
         MAX(sch.home_id) AS home_id,
         MAX(sch.away_id) AS away_id,
         MAX(sch.game_id) AS game_id
  FROM schedules sch
  JOIN seasons s ON s.id = sch.season_id
  JOIN teams t1 ON t1.id = sch.home_id
  JOIN teams t2 ON t2.id = sch.away_id
  WHERE sch.round = 'finals' -- Restrict to finals only
    AND s.status > 10
  GROUP BY s.id, sch.round
),
finals_winner_rank AS (
  SELECT ss.season_id, ss.team_id, MAX(ss.conference_rank) AS conference_rank
  FROM standings_snapshots ss
  GROUP BY ss.season_id, ss.team_id
),
playoff_series AS (
  SELECT 
    s.id AS season_id,
    MAX(sch.round) AS round,
    MAX(t1.name) AS team1_name,
    MAX(t2.name) AS team2_name,
    SUM(CASE WHEN sch.winner_id = sch.home_id THEN 1 ELSE 0 END) AS team1_wins,
    SUM(CASE WHEN sch.winner_id = sch.away_id THEN 1 ELSE 0 END) AS team2_wins,
    COALESCE(
      (SELECT MAX(pgs.points)
       FROM player_game_stats pgs
       JOIN schedules sch2 ON sch2.game_id = pgs.game_id
       WHERE sch2.season_id = s.id
         AND sch2.round = 'finals'
         AND pgs.team_id = sch.winner_id
         AND pgs.points > 0),
      (SELECT ROUND(pss.avg_points_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id
         AND pss.player_id = s.finals_mvp_id
         AND pss.avg_points_per_game > 0), 
      30) AS high_score,
    COALESCE(
      (SELECT MAX(pgs.rebounds)
       FROM player_game_stats pgs
       JOIN schedules sch2 ON sch2.game_id = pgs.game_id
       WHERE sch2.season_id = s.id
         AND sch2.round = 'finals'
         AND pgs.team_id = sch.winner_id
         AND pgs.rebounds > 0),
      (SELECT ROUND(pss.avg_rebounds_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id
         AND pss.player_id = s.finals_mvp_id
         AND pss.avg_rebounds_per_game > 0), 
      10) AS high_rebounds,
    COALESCE(
      (SELECT MAX(pgs.assists)
       FROM player_game_stats pgs
       JOIN schedules sch2 ON sch2.game_id = pgs.game_id
       WHERE sch2.season_id = s.id
         AND sch2.round = 'finals'
         AND pgs.team_id = sch.winner_id
         AND pgs.assists > 0),
      (SELECT ROUND(pss.avg_assists_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id
         AND pss.player_id = s.finals_mvp_id
         AND pss.avg_assists_per_game > 0), 
      8) AS high_assists,
    COALESCE(
      (SELECT p.name
       FROM player_game_stats pgs
       JOIN players p ON p.id = pgs.player_id
       JOIN schedules sch2 ON sch2.game_id = pgs.game_id
       WHERE sch2.season_id = s.id
         AND sch2.round = 'finals'
         AND pgs.team_id = sch.winner_id
         AND pgs.points > 0
       ORDER BY pgs.points DESC
       LIMIT 1), 
      (SELECT p.name 
       FROM players p 
       WHERE p.id = s.finals_mvp_id), 
      'Unknown Player') AS high_scorer
  FROM schedules sch
  JOIN seasons s ON s.id = sch.season_id
  JOIN teams t1 ON t1.id = sch.home_id
  JOIN teams t2 ON t2.id = sch.away_id
  WHERE sch.round = 'finals' -- Restrict to finals only
    AND s.status > 10
  GROUP BY s.id
),
highest_team_score AS (
  SELECT s.id AS season_id,
         MAX(sch.home_score) AS max_team_score,
         MAX((SELECT t.name FROM teams t WHERE t.id = sch.home_id)) AS scoring_team
  FROM schedules sch
  JOIN seasons s ON s.id = sch.season_id
  WHERE sch.round IN ('quarter_finals', 'semi_finals', 'finals')
    AND s.status > 10
  GROUP BY s.id
),
top_scorers AS (
  SELECT 
    s.id AS season_id,
    COALESCE(
      (SELECT p.name 
       FROM player_game_stats pgs
       JOIN players p ON p.id = pgs.player_id
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.north_champion_id OR sch.away_id = s.north_champion_id)
         AND pgs.team_id = s.north_champion_id
         AND pgs.points > 0
       ORDER BY pgs.points DESC
       LIMIT 1), 
      (SELECT p.name 
       FROM players p 
       JOIN season_awards sa ON sa.player_id = p.id
       WHERE sa.season_id = s.id 
         AND sa.award_name = 'Best Overall Player'
         AND s.north_champion_id IN (SELECT team_id FROM player_season_stats WHERE player_id = p.id AND season_id = s.id)), 
      (SELECT p.name 
       FROM player_season_stats pss
       JOIN players p ON p.id = pss.player_id
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.north_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      'Unknown Player') AS north_top_scorer,
    COALESCE(
      (SELECT MAX(pgs.points) 
       FROM player_game_stats pgs
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.north_champion_id OR sch.away_id = s.north_champion_id)
         AND pgs.team_id = s.north_champion_id
         AND pgs.points > 0), 
      (SELECT ROUND(pss.avg_points_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.north_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      28) AS north_top_points,
    COALESCE(
      (SELECT p.name 
       FROM player_game_stats pgs
       JOIN players p ON p.id = pgs.player_id
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.south_champion_id OR sch.away_id = s.south_champion_id)
         AND pgs.team_id = s.south_champion_id
         AND pgs.points > 0
       ORDER BY pgs.points DESC
       LIMIT 1), 
      (SELECT p.name 
       FROM players p 
       WHERE p.id = s.finals_mvp_id 
         AND s.south_champion_id = s.finals_winner_id), 
      (SELECT p.name 
       FROM player_season_stats pss
       JOIN players p ON p.id = pss.player_id
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.south_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      'Unknown Player') AS south_top_scorer,
    COALESCE(
      (SELECT MAX(pgs.points) 
       FROM player_game_stats pgs
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.south_champion_id OR sch.away_id = s.south_champion_id)
         AND pgs.team_id = s.south_champion_id
         AND pgs.points > 0), 
      (SELECT ROUND(pss.avg_points_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.south_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      30) AS south_top_points,
    COALESCE(
      (SELECT p.name 
       FROM player_game_stats pgs
       JOIN players p ON p.id = pgs.player_id
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.east_champion_id OR sch.away_id = s.east_champion_id)
         AND pgs.team_id = s.east_champion_id
         AND pgs.points > 0
       ORDER BY pgs.points DESC
       LIMIT 1), 
      (SELECT p.name 
       FROM players p 
       JOIN season_awards sa ON sa.player_id = p.id
       WHERE sa.season_id = s.id 
         AND sa.award_name = 'Best Defensive Player'
         AND s.east_champion_id IN (SELECT team_id FROM player_season_stats WHERE player_id = p.id AND season_id = s.id)), 
      (SELECT p.name 
       FROM player_season_stats pss
       JOIN players p ON p.id = pss.player_id
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.east_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      'Unknown Player') AS east_top_scorer,
    COALESCE(
      (SELECT MAX(pgs.points) 
       FROM player_game_stats pgs
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.east_champion_id OR sch.away_id = s.east_champion_id)
         AND pgs.team_id = s.east_champion_id
         AND pgs.points > 0), 
      (SELECT ROUND(pss.avg_points_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.east_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      25) AS east_top_points,
    COALESCE(
      (SELECT p.name 
       FROM player_game_stats pgs
       JOIN players p ON p.id = pgs.player_id
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.west_champion_id OR sch.away_id = s.west_champion_id)
         AND pgs.team_id = s.west_champion_id
         AND pgs.points > 0
       ORDER BY pgs.points DESC
       LIMIT 1), 
      (SELECT p.name 
       FROM player_season_stats pss
       JOIN players p ON p.id = pss.player_id
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.west_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      'Unknown Player') AS west_top_scorer,
    COALESCE(
      (SELECT MAX(pgs.points) 
       FROM player_game_stats pgs
       JOIN schedules sch ON sch.game_id = pgs.game_id
       WHERE sch.season_id = s.id 
         AND sch.round = 'semi_finals'
         AND (sch.home_id = s.west_champion_id OR sch.away_id = s.west_champion_id)
         AND pgs.team_id = s.west_champion_id
         AND pgs.points > 0), 
      (SELECT ROUND(pss.avg_points_per_game)
       FROM player_season_stats pss
       WHERE pss.season_id = s.id 
         AND pss.team_id = s.west_champion_id
       ORDER BY pss.avg_points_per_game DESC
       LIMIT 1), 
      20) AS west_top_points
  FROM seasons s
  WHERE s.status > 10
  GROUP BY s.id
)
SELECT
  s.id AS season_id,
  s.name AS season_name,
  s.created_at,
  s.updated_at,
  CONCAT_WS('',
    CASE
      WHEN s.id = 1 THEN 'INAUGURAL CHAMPIONS CROWNED IN HISTORIC FIRST SEASON'
      WHEN ct2.consecutive_titles >= 3 THEN CONCAT(UPPER(s.finals_winner_name), ' ETCH THEIR LEGACY WITH HISTORIC THREE-PEAT')
      WHEN r.is_redemption = 1 THEN CONCAT('REDEMPTION REALIZED: ', UPPER(s.finals_winner_name), ' AVENGE PAST DEFEAT IN EPIC FASHION')
      WHEN fwr.conference_rank >= 6 THEN CONCAT('CINDERELLA SENSATION: ', UPPER(s.finals_winner_name), ' SHOCKS LEAGUE FROM ', fwr.conference_rank, 
          CASE 
            WHEN fwr.conference_rank % 100 BETWEEN 11 AND 13 THEN 'TH'
            WHEN fwr.conference_rank % 10 = 1 THEN 'ST'
            WHEN fwr.conference_rank % 10 = 2 THEN 'ND'
            WHEN fwr.conference_rank % 10 = 3 THEN 'RD'
            ELSE 'TH'
          END, ' SEED')
      WHEN tt.total_titles >= 3 THEN CONCAT(UPPER(s.finals_winner_name), ' CLAIM ', 
          CASE tt.total_titles
              WHEN 1 THEN '1st'
              WHEN 2 THEN '2nd'
              WHEN 3 THEN '3rd'
              ELSE CONCAT(tt.total_titles, 'th')
          END, ' TITLE IN DOMINANT DISPLAY')
      WHEN tt.total_titles = 1 THEN CONCAT('A NEW ERA DAWNS: ', UPPER(s.finals_winner_name), ' SEIZE FIRST CHAMPIONSHIP')
      ELSE CONCAT(UPPER(s.finals_winner_name), ' TRIUMPH OVER ', UPPER(COALESCE(s.finals_loser_name, 'THE FIELD')))
    END,
    '\n\n',
    'The ', s.name, ' unfolded as a saga of grit, glory, and unforgettable moments, with the ', COALESCE(s.finals_winner_name, 'Unknown Champion'), ' emerging as the ultimate victors.',
    CASE 
      WHEN s.id > 1 AND r.is_redemption = 1 THEN CONCAT(' After their heart-wrenching defeat in Season ', r.redemption_season_id, ', the ', s.finals_winner_name, ' roared back with unrelenting determination to claim their ',
          CASE COALESCE(tt.total_titles, 1)
              WHEN 1 THEN '1st'
              WHEN 2 THEN '2nd'
              WHEN 3 THEN '3rd'
              ELSE CONCAT(COALESCE(tt.total_titles, 1), 'th')
          END, ' championship.') 
      ELSE ' Their journey to the top was marked by resilience and brilliance.' 
    END,
    CASE 
      WHEN s.id > 1 AND COALESCE(ct2.consecutive_titles, 0) > 1 THEN CONCAT(' This victory marks their ', 
          CASE ct2.consecutive_titles
              WHEN 1 THEN '1st'
              WHEN 2 THEN '2nd'
              WHEN 3 THEN '3rd'
              ELSE CONCAT(ct2.consecutive_titles, 'th')
          END, ' consecutive title, cementing their status as a budding dynasty.') 
      ELSE '' 
    END,
    CASE 
      WHEN s.id > 1 AND r.is_redemption = 1 THEN ' This triumph silenced doubters and completed a redemption arc that will echo through league history.' 
      ELSE '' 
    END,
    '\n\n',
    'The path to the championship was paved with epic battles across the four conferences. ',
    CASE 
      WHEN ct.consecutive_north_titles >= 2 THEN
        CONCAT('In Visayas, the ', COALESCE(s.north_champion_name, 'Visayas'), ' reigned supreme, clinching their ',
              ct.consecutive_north_titles,
              CASE 
                WHEN ct.consecutive_north_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.consecutive_north_titles % 10 = 1 THEN 'st'
                WHEN ct.consecutive_north_titles % 10 = 2 THEN 'nd'
                WHEN ct.consecutive_north_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' consecutive conference crown, led by ')
      WHEN ct.total_north_titles >= 2 THEN
        CONCAT('In Visayas, the ', COALESCE(s.north_champion_name, 'Visayas'), ' bolstered their legacy with their ',
              ct.total_north_titles,
              CASE 
                WHEN ct.total_north_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.total_north_titles % 10 = 1 THEN 'st'
                WHEN ct.total_north_titles % 10 = 2 THEN 'nd'
                WHEN ct.total_north_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' conference title, powered by ')
      ELSE CONCAT('In Visayas, the ', COALESCE(s.north_champion_name, 'Visayas'), ' stormed to their first conference championship, driven by ')
    END,
    ts.north_top_scorer,
    ', who erupted for ',
    ts.north_top_points, ' points',
    CASE 
      WHEN cr.north_redemption = 1 THEN ', overcoming past semi-final heartbreak to claim Visayas glory.'
      ELSE ' in a commanding conference final performance.'
    END,
    ' ',
    CASE 
      WHEN ct.consecutive_south_titles >= 2 THEN
        CONCAT('In Mindanao, the ', COALESCE(s.south_champion_name, 'Mindanao'), ' solidified their dynasty with a jaw-dropping ',
              ct.consecutive_south_titles,
              CASE 
                WHEN ct.consecutive_south_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.consecutive_south_titles % 10 = 1 THEN 'st'
                WHEN ct.consecutive_south_titles % 10 = 2 THEN 'nd'
                WHEN ct.consecutive_south_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' consecutive championship, fueled by ')
      WHEN ct.total_south_titles >= 2 THEN
        CONCAT('In Mindanao, the ', COALESCE(s.south_champion_name, 'Mindanao'), ' defended their crown with a relentless ',
              ct.total_south_titles,
              CASE 
                WHEN ct.total_south_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.total_south_titles % 10 = 1 THEN 'st'
                WHEN ct.total_south_titles % 10 = 2 THEN 'nd'
                WHEN ct.total_south_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' conference title, driven by ')
      ELSE CONCAT('In Mindanao, the ', COALESCE(s.south_champion_name, 'Mindanao'), ' shocked the league with their first championship, led by ')
    END,
    ts.south_top_scorer,
    ', who poured in ',
    ts.south_top_points, ' points',
    CASE 
      WHEN cr.south_redemption = 1 THEN ', rising from past semi-final defeats to seize Mindanao''s crown.'
      ELSE ' to secure the title.'
    END,
    ' ',
    CASE 
      WHEN ct.consecutive_east_titles >= 2 THEN
        CONCAT('In Luzon, the ', COALESCE(s.east_champion_name, 'Luzon'), ' asserted dominance with their ',
              ct.consecutive_east_titles,
              CASE 
                WHEN ct.consecutive_east_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.consecutive_east_titles % 10 = 1 THEN 'st'
                WHEN ct.consecutive_east_titles % 10 = 2 THEN 'nd'
                WHEN ct.consecutive_east_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' straight conference championship, spearheaded by ')
      WHEN ct.total_east_titles >= 2 THEN
        CONCAT('In Luzon, the ', COALESCE(s.east_champion_name, 'Luzon'), ' went back-to-back with their ',
              ct.total_east_titles,
              CASE 
                WHEN ct.total_east_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.total_east_titles % 10 = 1 THEN 'st'
                WHEN ct.total_east_titles % 10 = 2 THEN 'nd'
                WHEN ct.total_east_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' conference title, powered by ')
      ELSE CONCAT('In Luzon, the ', COALESCE(s.east_champion_name, 'Luzon'), ' captivated fans with their first championship, driven by ')
    END,
    ts.east_top_scorer,
    ', who dazzled with ',
    ts.east_top_points, ' points',
    CASE 
      WHEN cr.east_redemption = 1 THEN ', redeeming past semi-final losses with a masterful performance.'
      ELSE ' in a thrilling conference final.'
    END,
    ' ',
    CASE 
      WHEN ct.consecutive_west_titles >= 2 THEN
        CONCAT('Meanwhile in NCR, the ', COALESCE(s.west_champion_name, 'NCR'), ' rewrote history with an unprecedented ',
              ct.consecutive_west_titles,
              CASE 
                WHEN ct.consecutive_west_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.consecutive_west_titles % 10 = 1 THEN 'st'
                WHEN ct.consecutive_west_titles % 10 = 2 THEN 'nd'
                WHEN ct.consecutive_west_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' consecutive conference title, powered by ')
      WHEN ct.total_west_titles >= 2 THEN
        CONCAT('Meanwhile in NCR, the ', COALESCE(s.west_champion_name, 'NCR'), ' repeated as champions with their ',
              ct.total_west_titles,
              CASE 
                WHEN ct.total_west_titles % 100 BETWEEN 11 AND 13 THEN 'th'
                WHEN ct.total_west_titles % 10 = 1 THEN 'st'
                WHEN ct.total_west_titles % 10 = 2 THEN 'nd'
                WHEN ct.total_west_titles % 10 = 3 THEN 'rd'
                ELSE 'th'
              END, ' conference title, led by ')
      ELSE CONCAT('Meanwhile in NCR, the ', COALESCE(s.west_champion_name, 'NCR'), ' claimed their first championship with ')
    END,
    ts.west_top_scorer,
    ', who delivered ',
    ts.west_top_points, ' points',
    CASE 
      WHEN cr.west_redemption = 1 THEN ', overcoming past semi-final setbacks to claim NCR supremacy.'
      ELSE ' to secure the crown.'
    END,
    '\n\n',
    'The championship game was a spectacle that will be etched in fans’ memories for years. ',
    (SELECT CONCAT('The ', s.finals_winner_name, ' clashed with the ', s.finals_loser_name, ', emerging triumphant with a score of ',
                    f.home_team, ' ', f.home_score, ' - ', f.away_score, ' ', f.away_team)
     FROM finals_details f WHERE f.id = s.id AND f.round = 'finals'),
    CASE 
      WHEN (SELECT ABS(f.home_score - f.away_score) FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' LIMIT 1) <= 5 
        THEN ' in a heart-stopping finish that came down to the final seconds, with the crowd roaring as the buzzer sounded.'
      WHEN (SELECT ABS(f.home_score - f.away_score) FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' LIMIT 1) <= 10 
        THEN ' in a fiercely contested battle that had fans on the edge of their seats until the final whistle.'
      WHEN (SELECT ABS(f.home_score - f.away_score) FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' LIMIT 1) >= 20 
        THEN ' in a commanding performance that showcased their unrivaled dominance.'
      ELSE ' in a thrilling showdown that captivated the league.'
    END,
    CASE 
      WHEN r.is_redemption = 1 AND s.id = r.redemption_season_id + 1 
        THEN CONCAT(' In a dramatic reversal of last season’s heartbreak against the ', s.finals_loser_name, ', the ', s.finals_winner_name, ' seized their moment of glory.')
      WHEN r.is_redemption = 1 
        THEN CONCAT(' After falling in the finals of Season ', r.redemption_season_id, ', the ', s.finals_winner_name, ' completed an epic redemption arc.')
      ELSE ''
    END,
    CASE 
      WHEN ct2.consecutive_titles >= 3 
        THEN CONCAT(' This victory solidifies the ', s.finals_winner_name, ' as a dynasty with a rare ', ct2.consecutive_titles, '-peat.')
      WHEN ct2.consecutive_titles = 2 
        THEN CONCAT(' With back-to-back titles, the ', s.finals_winner_name, ' are poised to build a dynasty.')
      ELSE ''
    END,
    ' Finals MVP honors went to ', COALESCE(fm.finals_mvp_name, 'an outstanding player'), 
    CASE 
      WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.season_id = s.id AND sa.award_name = 'Best Overall Player' AND sa.player_id = s.finals_mvp_id)
        THEN ', who also claimed the regular season MVP, achieving a rare double crown.'
      ELSE ', whose clutch performance defined the finals.'
    END,
    '\n\n',
    '### Championship Game Recap\n',
    (SELECT CONCAT('The ', s.finals_winner_name, ' faced the ', s.finals_loser_name, ' in a high-stakes, single-elimination showdown that electrified the arena. ',
                    'With the scoreline reading ', f.home_team, ' ', f.home_score, ' - ', f.away_score, ' ', f.away_team, ', the ',
                    s.finals_winner_name, ' claimed victory. ',
                    COALESCE(ps.high_scorer, fm.finals_mvp_name, 'Unknown Player'), ' led the charge with a scintillating ',
                    ps.high_score, ' points, ',
                    ps.high_rebounds, ' rebounds, and ',
                    ps.high_assists, ' assists, setting the tone for the win. ',
                    CASE 
                      WHEN ABS(f.home_score - f.away_score) <= 5 THEN CONCAT('A critical play in the final moments by ', COALESCE(ps.high_scorer, fm.finals_mvp_name, 'Unknown Player'), ' sealed the championship.')
                      ELSE 'Their relentless offense and stifling defense overwhelmed their opponents.'
                    END, ' The raucous crowd erupted as the ', s.finals_winner_name, ' hoisted the trophy.')
     FROM finals_details f LEFT JOIN playoff_series ps ON ps.season_id = s.id WHERE f.id = s.id AND f.round = 'finals'),
    '\n\n',
    '### Season Trivia\n',
    '- ',
    CASE 
      WHEN ct2.consecutive_titles >= 2 THEN CONCAT(s.finals_winner_name, ' joined an elite group as only the ',
          CASE 
            WHEN ct2.consecutive_titles = 2 THEN 'second team'
            WHEN ct2.consecutive_titles = 3 THEN 'first team'
            ELSE CONCAT(ct2.consecutive_titles, 'th team')
          END, ' in league history to win ', ct2.consecutive_titles, ' consecutive championships.')
      ELSE CONCAT(s.finals_winner_name, ' etched their name in history with their ',
          CASE tt.total_titles
              WHEN 1 THEN 'first'
              WHEN 2 THEN 'second'
              WHEN 3 THEN 'third'
              ELSE CONCAT(tt.total_titles, 'th')
          END, ' championship title.')
    END,
    '\n- ',
    CASE 
      WHEN r.is_redemption = 1 THEN CONCAT(s.finals_winner_name, ' completed a rare redemption arc, overcoming their finals loss in Season ', r.redemption_season_id, ' to claim the ultimate prize.')
      ELSE CONCAT(COALESCE(fm.finals_mvp_name, 'The Finals MVP'), ' delivered a playoff masterclass, leading the ', s.finals_winner_name, ' with poise and precision.')
    END,
    '\n- ',
    CASE 
      WHEN fwr.conference_rank >= 6 THEN CONCAT(s.finals_winner_name, ' defied all odds as a ', fwr.conference_rank,
          CASE 
            WHEN fwr.conference_rank % 100 BETWEEN 11 AND 13 THEN 'th'
            WHEN fwr.conference_rank % 10 = 1 THEN 'st'
            WHEN fwr.conference_rank % 10 = 2 THEN 'nd'
            WHEN fwr.conference_rank % 10 = 3 THEN 'rd'
            ELSE 'th'
          END, ' seed, marking one of the most improbable championship runs in league history.')
      ELSE CONCAT('The ', s.name, ' featured a historic ',
          COALESCE(hts.max_team_score, 'N/A'), '-point performance by the ', COALESCE(hts.scoring_team, 'a standout team'), ' in a single playoff game.')
    END,
    '\n- ',
    CASE 
      WHEN (SELECT COUNT(*) FROM season_awards sa WHERE sa.season_id = s.id AND sa.player_id = s.finals_mvp_id) > 1 
        THEN CONCAT(COALESCE(fm.finals_mvp_name, 'The Finals MVP'), ' made history by winning multiple awards this season, including Finals MVP and ',
             COALESCE((SELECT sa.award_name FROM season_awards sa WHERE sa.season_id = s.id AND sa.player_id = s.finals_mvp_id AND sa.award_name != 'Finals MVP' LIMIT 1), 'another prestigious honor'), '.')
      ELSE CONCAT('The ', s.name, ' saw ',
          COALESCE(
            (SELECT p.name 
             FROM player_game_stats pgs 
             JOIN players p ON p.id = pgs.player_id 
             JOIN schedules sch ON sch.game_id = pgs.game_id 
             WHERE sch.season_id = s.id 
               AND sch.round IN ('quarter_finals', 'semi_finals', 'finals') 
               AND pgs.assists > 0
             ORDER BY pgs.assists DESC 
             LIMIT 1), 
            (SELECT p.name 
             FROM player_season_stats pss
             JOIN players p ON p.id = pss.player_id
             WHERE pss.season_id = s.id 
               AND pss.team_id = s.finals_winner_id
             ORDER BY pss.avg_assists_per_game DESC
             LIMIT 1), 
            fm.finals_mvp_name, 
            'Unknown Playmaker'),
          ' dish out a playoff-high ', 
          COALESCE(
            (SELECT pgs.assists 
             FROM player_game_stats pgs 
             JOIN schedules sch ON sch.game_id = pgs.game_id 
             WHERE sch.season_id = s.id 
               AND sch.round IN ('quarter_finals', 'semi_finals', 'finals') 
               AND pgs.assists > 0
             ORDER BY pgs.assists DESC 
             LIMIT 1), 
            (SELECT ROUND(pss.avg_assists_per_game)
             FROM player_season_stats pss
             WHERE pss.season_id = s.id 
               AND pss.team_id = s.finals_winner_id
             ORDER BY pss.avg_assists_per_game DESC
             LIMIT 1), 
            8), ' assists in a single game.')
    END,
    '\n\n',
    'Season MVP: ', COALESCE(aw.best_overall, 'Not awarded'), '. ',
    'Defensive Player of the Year: ', COALESCE(aw.best_defense, 'Not awarded'), '. ',
    CASE WHEN aw.sixth_man IS NOT NULL THEN CONCAT('Sixth Man of the Year: ', aw.sixth_man, '. ') ELSE '' END,
    CASE WHEN aw.most_improved IS NOT NULL THEN CONCAT('Most Improved Player: ', aw.most_improved, '. ') ELSE '' END,
    CASE WHEN aw.rookie_year IS NOT NULL THEN CONCAT('Rookie of the Year: ', aw.rookie_year, '. ') ELSE '' END,
    '\n\n',
    CASE 
      WHEN r.is_redemption = 1 THEN 'With redemption fulfilled and new legends born, the league awaits the next chapter in this saga of triumph.'
      WHEN ct2.consecutive_titles >= 2 THEN CONCAT('As the ', s.finals_winner_name, ' build a dynasty, rivals sharpen their blades for the next season. Can anyone dethrone them?')
      WHEN fwr.conference_rank >= 6 THEN 'This Cinderella story has redefined what’s possible. Will this underdog triumph inspire a new wave of contenders?'
      WHEN tt.total_titles = 1 THEN 'With a new champion crowned, the league enters an electrifying era of competition and ambition.'
      ELSE 'As legends rise and challengers emerge, the question looms: who will write the next unforgettable chapter?'
    END
  ) AS storyline
FROM seasons s
LEFT JOIN total_titles tt ON tt.id = s.id
LEFT JOIN consecutive_titles ct2 ON ct2.id = s.id AND ct2.finals_winner_id = s.finals_winner_id
LEFT JOIN conference_titles ct ON ct.id = s.id
LEFT JOIN conference_redemption cr ON cr.id = s.id
LEFT JOIN redemption r ON r.id = s.id
LEFT JOIN awards aw ON aw.season_id = s.id
LEFT JOIN finals_mvp fm ON fm.season_id = s.id
LEFT JOIN finals_winner_rank fwr ON fwr.season_id = s.id AND fwr.team_id = s.finals_winner_id
LEFT JOIN highest_team_score hts ON hts.season_id = s.id
LEFT JOIN top_scorers ts ON ts.season_id = s.id
WHERE s.status > 10 AND s.id = (SELECT MAX(id) FROM seasons WHERE status > 10)
LIMIT 1; -- Ensure only one row is returned