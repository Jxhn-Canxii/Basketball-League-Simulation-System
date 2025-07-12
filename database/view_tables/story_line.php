CREATE OR REPLACE VIEW season_storyline AS
WITH champion_runs AS (
  SELECT id,
         finals_winner_id,
         ROW_NUMBER() OVER (ORDER BY id) -
         ROW_NUMBER() OVER (PARTITION BY finals_winner_id ORDER BY id) AS grp
  FROM seasons
  WHERE status = 17
),
consecutive_titles AS (
  SELECT cr1.id,
         cr1.finals_winner_id,
         COUNT(*) AS consecutive_titles
  FROM champion_runs cr1
  JOIN champion_runs cr2
    ON cr2.finals_winner_id = cr1.finals_winner_id
   AND cr2.grp = cr1.grp
   AND cr2.id <= cr1.id
  GROUP BY cr1.id, cr1.finals_winner_id, cr1.grp
),
total_titles AS (
  SELECT s.id,
         COUNT(*) AS total_titles
  FROM seasons s
  JOIN seasons s2
    ON s2.finals_winner_id = s.finals_winner_id
   AND s2.status = 17
   AND s2.id <= s.id
  WHERE s.status = 17
  GROUP BY s.id
),
redemption AS (
  SELECT s.id,
         CASE WHEN EXISTS (
           SELECT 1 FROM seasons s2
           WHERE s2.finals_loser_id = s.finals_winner_id
             AND s2.id < s.id AND s2.status = 17
         ) THEN 1 ELSE 0 END AS is_redemption,
         (SELECT MAX(s2.id) FROM seasons s2
          WHERE s2.finals_loser_id = s.finals_winner_id
            AND s2.id < s.id AND s2.status = 17) AS redemption_season_id
  FROM seasons s
  WHERE s.status = 17
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
  WHERE status = 17
),
conference_titles AS (
  SELECT s.id,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.west_champion_id = s.west_champion_id AND s2.status = 17 AND s2.id <= s.id) AS total_west_titles,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.east_champion_id = s.east_champion_id AND s2.status = 17 AND s2.id <= s.id) AS total_east_titles,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.north_champion_id = s.north_champion_id AND s2.status = 17 AND s2.id <= s.id) AS total_north_titles,
         (SELECT COUNT(*) FROM seasons s2 WHERE s2.south_champion_id = s.south_champion_id AND s2.status = 17 AND s2.id <= s.id) AS total_south_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.west_champion_id = s.west_champion_id AND cr2.grp_west = cr.grp_west AND cr2.id <= s.id) AS consecutive_west_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.east_champion_id = s.east_champion_id AND cr2.grp_east = cr.grp_east AND cr2.id <= s.id) AS consecutive_east_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.north_champion_id = s.north_champion_id AND cr2.grp_north = cr.grp_north AND cr2.id <= s.id) AS consecutive_north_titles,
         (SELECT COUNT(*) FROM conference_runs cr2 WHERE cr2.south_champion_id = s.south_champion_id AND cr2.grp_south = cr.grp_south AND cr2.id <= s.id) AS consecutive_south_titles
  FROM seasons s
  LEFT JOIN conference_runs cr ON cr.id = s.id
  WHERE s.status = 17
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
             AND s2.status = 17
         ) THEN 1 ELSE 0 END AS west_redemption,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.east_champion_id OR sch.away_id = s.east_champion_id)
             AND sch.winner_id != s.east_champion_id
             AND s2.finals_winner_id != s.east_champion_id
             AND s2.status = 17
         ) THEN 1 ELSE 0 END AS east_redemption,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.north_champion_id OR sch.away_id = s.north_champion_id)
             AND sch.winner_id != s.north_champion_id
             AND s2.finals_winner_id != s.north_champion_id
             AND s2.status = 17
         ) THEN 1 ELSE 0 END AS north_redemption,
         CASE WHEN EXISTS (
           SELECT 1 FROM schedules sch
           JOIN seasons s2 ON sch.season_id = s2.id
           WHERE sch.round = 'semi_finals'
             AND sch.season_id < s.id
             AND (sch.home_id = s.south_champion_id OR sch.away_id = s.south_champion_id)
             AND sch.winner_id != s.south_champion_id
             AND s2.finals_winner_id != s.south_champion_id
             AND s2.status = 17
         ) THEN 1 ELSE 0 END AS south_redemption
  FROM seasons s
  WHERE s.status = 17
),
award_counts AS (
  SELECT player_id, award_name, COUNT(*) AS award_count
  FROM season_awards
  WHERE award_name IN ('Best Overall Player', 'Best Defensive Player', 'Sixth Man of the Year', 'Most Improved Player', 'Rookie of the Year')
  GROUP BY player_id, award_name
),
awards AS (
  SELECT sa.season_id,
         MAX(CASE WHEN sa.award_name = 'Best Overall Player' THEN CONCAT(p.name, ' (', ac.award_count, 'x MVP)') END) AS best_overall,
         MAX(CASE WHEN sa.award_name = 'Best Defensive Player' THEN CONCAT(p.name, ' (', ac.award_count, 'x DPOY)') END) AS best_defense,
         MAX(CASE WHEN sa.award_name = 'Sixth Man of the Year' THEN CONCAT(p.name, ' (', ac.award_count, 'x 6th Man)') END) AS sixth_man,
         MAX(CASE WHEN sa.award_name = 'Most Improved Player' THEN p.name END) AS most_improved,
         MAX(CASE WHEN sa.award_name = 'Rookie of the Season' THEN p.name END) AS rookie_year
  FROM season_awards sa
  JOIN players p ON p.id = sa.player_id
  LEFT JOIN award_counts ac ON ac.player_id = sa.player_id AND ac.award_name = sa.award_name
  GROUP BY sa.season_id
),
finals_mvp AS (
  SELECT pss.season_id, p.name AS finals_mvp_name
  FROM player_season_stats pss
  JOIN players p ON p.id = pss.player_id
  JOIN seasons s ON s.id = pss.season_id AND s.finals_mvp_id = p.id
),
finals_details AS (
  SELECT s.id,
    sch.home_score, sch.away_score, t1.name AS home_team, t2.name AS away_team,
    sch.winner_id, sch.round, sch.home_id, sch.away_id
  FROM schedules sch
  JOIN seasons s ON s.id = sch.season_id
  JOIN teams t1 ON t1.id = sch.home_id
  JOIN teams t2 ON t2.id = sch.away_id
  WHERE sch.round IN ('finals', 'semi_finals')
    AND s.status = 17
),
finals_winner_rank AS (
  SELECT ss.season_id, ss.team_id, ss.conference_rank
  FROM standings_snapshots ss
  GROUP BY ss.season_id, ss.team_id, ss.conference_rank
),
playoff_series AS (
  SELECT 
    s.id AS season_id,
    sch.round,
    t1.name AS team1_name,
    t2.name AS team2_name,
    COUNT(CASE WHEN sch.winner_id = sch.home_id THEN 1 END) AS team1_wins,
    COUNT(CASE WHEN sch.winner_id = sch.away_id THEN 1 END) AS team2_wins,
    MAX(pgs.points) AS high_score
  FROM schedules sch
  JOIN seasons s ON s.id = sch.season_id
  JOIN teams t1 ON t1.id = sch.home_id
  JOIN teams t2 ON t2.id = sch.away_id
  LEFT JOIN player_game_stats pgs ON pgs.game_id = sch.id
  WHERE sch.round IN ('quarter_finals', 'semi_finals', 'finals')
    AND s.status = 17
  GROUP BY s.id, sch.round, t1.name, t2.name
),
season_stats AS (
  SELECT 
    s.id AS season_id,
    AVG(sch.home_score + sch.away_score) AS avg_points_per_game,
    MAX(pgs.points) AS season_high_points,
    COUNT(DISTINCT CASE WHEN pgs.points >= 50 THEN pgs.player_id END) AS fifty_point_games
  FROM seasons s
  JOIN schedules sch ON sch.season_id = s.id
  LEFT JOIN player_game_stats pgs ON pgs.game_id = sch.id
  WHERE s.status = 17
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
      WHEN ct2.consecutive_titles >= 3 THEN CONCAT(UPPER(s.finals_winner_name), ' COMPLETE HISTORIC THREE-PEAT RUN')
      WHEN r.is_redemption = 1 THEN CONCAT('REDEMPTION: ', UPPER(s.finals_winner_name), ' AVENGE PAST LOSS')
      WHEN fwr.conference_rank >= 6 THEN CONCAT('CINDERELLA RUN: ', UPPER(s.finals_winner_name), ' STUNS THE LEAGUE FROM ', fwr.conference_rank, 
          CASE 
            WHEN fwr.conference_rank % 100 BETWEEN 11 AND 13 THEN 'TH'
            WHEN fwr.conference_rank % 10 = 1 THEN 'ST'
            WHEN fwr.conference_rank % 10 = 2 THEN 'ND'
            WHEN fwr.conference_rank % 10 = 3 THEN 'RD'
            ELSE 'TH'
          END, ' SEED')
      WHEN tt.total_titles >= 3 THEN CONCAT(UPPER(s.finals_winner_name), ' CAPTURE ', 
          CASE tt.total_titles
              WHEN 1 THEN '1st'
              WHEN 2 THEN '2nd'
              WHEN 3 THEN '3rd'
              ELSE CONCAT(tt.total_titles, 'th')
          END, ' TITLE IN DOMINANT FASHION')
      WHEN tt.total_titles = 1 THEN CONCAT('A NEW DYNASTY BEGINS: ', UPPER(s.finals_winner_name), ' CLAIM FIRST CROWN')
      ELSE CONCAT(UPPER(s.finals_winner_name), ' CONQUER ', UPPER(COALESCE(s.finals_loser_name, 'THE FIELD')))
    END,
    '\n\n',
    'In a season filled with storylines, the ', s.name, ' belonged to the ', COALESCE(s.finals_winner_name, 'Unknown Champion'),
    CASE 
      WHEN s.id > 1 AND r.is_redemption = 1 THEN CONCAT('. After falling short in Season ', r.redemption_season_id, ', they stormed back with a vengeance to claim their ',
          CASE COALESCE(tt.total_titles, 1)
              WHEN 1 THEN '1st'
              WHEN 2 THEN '2nd'
              WHEN 3 THEN '3rd'
              ELSE CONCAT(COALESCE(tt.total_titles, 1), 'th')
          END, ' championship') 
      ELSE '.' 
    END,
    CASE 
      WHEN s.id > 1 AND COALESCE(ct2.consecutive_titles, 0) > 1 THEN CONCAT(' — marking their ', 
          CASE ct2.consecutive_titles
              WHEN 1 THEN '1st'
              WHEN 2 THEN '2nd'
              WHEN 3 THEN '3rd'
              ELSE CONCAT(ct2.consecutive_titles, 'th')
          END, ' consecutive title') 
      ELSE '' 
    END,
    CASE 
      WHEN s.id > 1 AND r.is_redemption = 1 THEN ', completing a powerful redemption arc that silenced critics.' 
      ELSE '' 
    END,
    '\n\n',
    'The season was marked by offensive fireworks, with teams averaging ', ROUND(stats.avg_points_per_game, 1), ' points per game. ',
    CASE 
      WHEN stats.fifty_point_games > 0 THEN CONCAT('A total of ', stats.fifty_point_games, ' players scored 50+ points in games, with the season high being ', stats.season_high_points, ' points. ')
      ELSE ''
    END,
    '\n\n',
    'On the road to the championship, the four conference champions made their mark. ',
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.north_champion_id = s.north_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('In Visayas, ', COALESCE(s.north_champion_name, 'Visayas'), ' continued their reign of terror, securing their ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.north_champion_id = s.north_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              CASE (SELECT COUNT(*) FROM seasons s2 
                    WHERE s2.north_champion_id = s.north_champion_id 
                    AND s2.id <= s.id AND s2.status = 17)
                  WHEN 1 THEN 'st'
                  WHEN 2 THEN 'nd'
                  WHEN 3 THEN 'rd'
                  ELSE 'th'
              END, ' consecutive conference title behind ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.north_champion_id = s.north_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('In Visayas, ', COALESCE(s.north_champion_name, 'Visayas'), ' repeated as conference champions, proving their dominance with ')
      ELSE CONCAT('In Visayas, ', COALESCE(s.north_champion_name, 'Visayas'), ' was led by ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.north_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'an emerging star'),
    ' who dropped ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.north_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points in their conference final. ',
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.south_champion_id = s.south_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('In Mindanao, ', COALESCE(s.south_champion_name, 'Mindanao'), ' established their conference dynasty with a staggering ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.south_champion_id = s.south_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              CASE (SELECT COUNT(*) FROM seasons s2 
                    WHERE s2.south_champion_id = s.south_champion_id 
                    AND s2.id <= s.id AND s2.status = 17)
                  WHEN 1 THEN 'st'
                  WHEN 2 THEN 'nd'
                  WHEN 3 THEN 'rd'
                  ELSE 'th'
              END, ' straight championships as ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.south_champion_id = s.south_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('In Mindanao, ', COALESCE(s.south_champion_name, 'Mindanao'), ' defended their conference crown with ')
      ELSE CONCAT('In Mindanao, ', COALESCE(s.south_champion_name, 'Mindanao'), ' stunned fans as ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.south_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'a reliable veteran'),
    ' poured in ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.south_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points to secure the title. ',
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.east_champion_id = s.east_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('In Luzon, ', COALESCE(s.east_champion_name, 'Luzon'), ' became the team to beat with their ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.east_champion_id = s.east_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              CASE (SELECT COUNT(*) FROM seasons s2 
                    WHERE s2.east_champion_id = s.east_champion_id 
                    AND s2.id <= s.id AND s2.status = 17)
                  WHEN 1 THEN 'st'
                  WHEN 2 THEN 'nd'
                  WHEN 3 THEN 'rd'
                  ELSE 'th'
              END, ' consecutive conference championship spearheaded by ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.east_champion_id = s.east_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('In Luzon, ', COALESCE(s.east_champion_name, 'Luzon'), ' went back-to-back as conference champs behind ')
      ELSE CONCAT('In Luzon, ', COALESCE(s.east_champion_name, 'Luzon'), ' turned heads behind the brilliance of ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.east_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'a clutch performer'),
    ', who lit up the scoreboard with ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.east_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points. ',
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.west_champion_id = s.west_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('Meanwhile in NCR, ', COALESCE(s.west_champion_name, 'NCR'), ' rewrote the history books with an unprecedented ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.west_champion_id = s.west_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              CASE (SELECT COUNT(*) FROM seasons s2 
                    WHERE s2.west_champion_id = s.west_champion_id 
                    AND s2.id <= s.id AND s2.status = 17)
                  WHEN 1 THEN 'st'
                  WHEN 2 THEN 'nd'
                  WHEN 3 THEN 'rd'
                  ELSE 'th'
              END, ' straight conference titles, powered by ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.west_champion_id = s.west_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('Meanwhile in NCR, ', COALESCE(s.west_champion_name, 'NCR'), ' repeated as conference kings with ')
      ELSE CONCAT('Meanwhile in NCR, ', COALESCE(s.west_champion_name, 'NCR'), ' secured the crown thanks to ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.west_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'an unstoppable force'),
    ' scoring ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.west_champion_id AND pgs.season_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' in their final test. ',
    '\n\n',
    'The finals delivered fireworks as the championship game concluded ',
    (SELECT CONCAT('with a scoreline of ', f.home_team, ' ', f.home_score, ' - ', f.away_score, ' ', f.away_team)
     FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' ORDER BY f.home_score + f.away_score DESC LIMIT 1),
    CASE 
      WHEN (SELECT ABS(f.home_score - f.away_score) FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' LIMIT 1) <= 5 
        THEN ' in a nail-biting finish that came down to the final possession.'
      WHEN (SELECT ABS(f.home_score - f.away_score) FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' LIMIT 1) <= 10 
        THEN ' in a closely contested battle that kept fans on the edge of their seats.'
      WHEN (SELECT ABS(f.home_score - f.away_score) FROM finals_details f WHERE f.id = s.id AND f.round = 'finals' LIMIT 1) >= 20 
        THEN ' in a dominant performance that left no doubt about the better team.'
      ELSE '.'
    END,
    ' Finals MVP honors went to ', COALESCE(fm.finals_mvp_name, 'an outstanding player'), 
    CASE 
      WHEN EXISTS (SELECT 1 FROM season_awards sa WHERE sa.season_id = s.id AND sa.award_name = 'Best Overall Player' AND sa.player_id = s.finals_mvp_id)
        THEN ', who also claimed the regular season MVP award.'
      ELSE '.'
    END,
    '\n\n',
    'Season MVP: ', COALESCE(aw.best_overall, 'Not awarded'), '. ',
    'Defensive anchor of the year: ', COALESCE(aw.best_defense, ''), '. ',
    'Sixth Man spark: ', COALESCE(aw.sixth_man, ''), '. ',
    CASE WHEN aw.most_improved IS NOT NULL THEN CONCAT('Most Improved Player: ', aw.most_improved, '. ') ELSE '' END,
    CASE WHEN aw.rookie_year IS NOT NULL THEN CONCAT('Rookie of the Year: ', aw.rookie_year, '. ') ELSE '' END,
    '\n\n',
    CASE 
      WHEN r.is_redemption = 1 THEN 'With redemption fulfilled and dynasties forming, fans now wonder: who will rise next?'
      WHEN ct2.consecutive_titles >= 2 THEN CONCAT('With ', s.finals_winner_name, ' establishing a potential dynasty, the league braces for their continued dominance. Can anyone stop them?')
      WHEN fwr.conference_rank >= 6 THEN 'This Cinderella story has rewritten the league narrative. Will this underdog success inspire other teams next season?'
      WHEN tt.total_titles = 1 THEN 'With the championship landscape shifting, the league enters an exciting new era of competition.'
      ELSE 'With dynasties forming and new challengers emerging, fans now wonder: who will rise next?'
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
LEFT JOIN finals_winner_rank fwr 
  ON fwr.season_id = s.id AND fwr.team_id = s.finals_winner_id
LEFT JOIN season_stats stats ON stats.season_id = s.id
WHERE s.status = 17
ORDER BY s.id;