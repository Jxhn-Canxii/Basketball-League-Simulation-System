CREATE OR REPLACE VIEW season_storyline AS
SELECT 
  s.id AS season_id,
  s.name AS season_name,
  CONCAT(
    -- Opening line randomizer
    CASE FLOOR(RAND() * 3)
      WHEN 0 THEN CONCAT('The ', COALESCE(NULLIF(s.name, ''), 'Unknown Season'), ' season unfolded like an epic saga, ')
      WHEN 1 THEN CONCAT('In the heart-pounding drama of the ', COALESCE(NULLIF(s.name, ''), 'Unknown Season'), ', ')
      ELSE CONCAT('The ', COALESCE(NULLIF(s.name, ''), 'Unknown Season'), ' season was a rollercoaster of triumphs, ')
    END,

    -- Championship outcome with repeat champion storyline
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) >= 3 THEN
        CONCAT(COALESCE(NULLIF(s.finals_winner_name, ''), 'Unknown Champion'), ' cemented their dynasty, capturing an incredible ',
        (SELECT COUNT(*) FROM seasons s2 
         WHERE s2.finals_winner_id = s.finals_winner_id 
         AND s2.id <= s.id AND s2.status = 17),
        'th championship after a ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) = 2 THEN
        CONCAT(COALESCE(NULLIF(s.finals_winner_name, ''), 'Unknown Champion'), ' achieved a three-peat, completing their championship trilogy with a ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT(COALESCE(NULLIF(s.finals_winner_name, ''), 'Unknown Champion'), ' went back-to-back, defending their title after a ')
      ELSE CONCAT(COALESCE(NULLIF(s.finals_winner_name, ''), 'Unknown Champion'), ' emerged victorious, hoisting the national championship trophy after a ')
    END,
    
    CASE 
      WHEN s.finals_winner_score - s.finals_loser_score >= 3 THEN 'decisive rout'
      WHEN s.finals_winner_score - s.finals_loser_score <= 2 THEN 'hard-fought battle'
      ELSE (CASE FLOOR(RAND() * 3)
              WHEN 0 THEN 'grueling showdown'
              WHEN 1 THEN 'nail-biting series'
              ELSE 'thrilling clash'
            END)
    END,
    ' against the ', COALESCE(NULLIF(s.finals_loser_name, ''), 'Unknown Opponent'),
    ', winning ', COALESCE(s.finals_winner_score, 'N/A'), ' to ', COALESCE(s.finals_loser_score, 'N/A'), '. ',

    -- Finals MVP performance
    'In the Finals, ',
    COALESCE((SELECT p.name FROM player_game_stats pgs
              JOIN schedules sch ON pgs.game_id = sch.game_id
              JOIN players p ON p.id = pgs.player_id
              WHERE sch.season_id = s.id AND sch.round = 'finals'
                AND pgs.team_id = s.finals_winner_id
              ORDER BY pgs.points DESC LIMIT 1), 'An unknown star'),
    ' shone brightly, dropping ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs
              JOIN schedules sch ON pgs.game_id = sch.game_id
              WHERE sch.season_id = s.id AND sch.round = 'finals'
                AND pgs.team_id = s.finals_winner_id
              ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points. ',

    -- Highlight game in Finals
    'A pivotal game saw ',
    COALESCE((SELECT CASE WHEN sch.winner_id = sch.home_id THEN t1.name ELSE t2.name END
              FROM schedules sch
              JOIN teams t1 ON sch.home_id = t1.id
              JOIN teams t2 ON sch.away_id = t2.id
              WHERE sch.season_id = s.id AND sch.round = 'finals'
                AND sch.winner_id = s.finals_winner_id
              ORDER BY (sch.home_score + sch.away_score) DESC LIMIT 1), 'Unknown Team'),
    ' overpower ',
    COALESCE((SELECT CASE WHEN sch.winner_id = sch.home_id THEN t2.name ELSE t1.name END
              FROM schedules sch
              JOIN teams t1 ON sch.home_id = t1.id
              JOIN teams t2 ON sch.away_id = t2.id
              WHERE sch.season_id = s.id AND sch.round = 'finals'
                AND sch.winner_id = s.finals_winner_id
              ORDER BY (sch.home_score + sch.away_score) DESC LIMIT 1), 'Unknown Opponent'),
    ' ',
    COALESCE((SELECT CASE WHEN sch.winner_id = sch.home_id THEN sch.home_score ELSE sch.away_score END
              FROM schedules sch
              WHERE sch.season_id = s.id AND sch.round = 'finals'
                AND sch.winner_id = s.finals_winner_id
              ORDER BY (sch.home_score + sch.away_score) DESC LIMIT 1), 'N/A'), '-',
    COALESCE((SELECT CASE WHEN sch.winner_id = sch.home_id THEN sch.away_score ELSE sch.home_score END
              FROM schedules sch
              WHERE sch.season_id = s.id AND sch.round = 'finals'
                AND sch.winner_id = s.finals_winner_id
              ORDER BY (sch.home_score + sch.away_score) DESC LIMIT 1), 'N/A'), '. ',

    -- Finals MVP season stats
    COALESCE(s.finals_mvp, 'The Finals MVP'), ' was the heartbeat of the ',
    COALESCE(NULLIF(s.finals_winner_name, ''), 'Unknown Winner'), ', ',
    CASE 
      WHEN pss.avg_points_per_game IS NOT NULL 
           AND (pss.avg_points_per_game > 0 OR pss.avg_rebounds_per_game > 0 OR pss.avg_assists_per_game > 0) THEN 
        CONCAT('delivering ', ROUND(pss.avg_points_per_game, 1), ' points, ', 
               ROUND(pss.avg_rebounds_per_game, 1), ' rebounds, and ', 
               ROUND(pss.avg_assists_per_game, 1), ' assists per game with ')
      ELSE 'leading with unmatched prowess with '
    END,
    CASE FLOOR(RAND() * 3)
      WHEN 0 THEN 'unstoppable flair'
      WHEN 1 THEN 'relentless grit'
      ELSE 'dazzling precision'
    END, '. ',

    -- Conference champions with repeat storylines
    'On the road to the championship, the four conference champions made their mark. ',
    
    -- Visayas Conference
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.north_champion_id = s.north_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('In Visayas, ', COALESCE(s.north_champion_name, 'Visayas'), ' continued their reign of terror, securing their ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.north_champion_id = s.north_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              ' consecutive conference title behind ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.north_champion_id = s.north_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('In Visayas, ', COALESCE(s.north_champion_name, 'Visayas'), ' repeated as conference champions, proving their dominance with ')
      ELSE CONCAT('In Visayas, ', COALESCE(s.north_champion_name, 'Visayas'), ' was led by ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.north_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'an emerging star'),
    ' who dropped ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.north_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points. ',

    -- Mindanao Conference
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.south_champion_id = s.south_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('In Mindanao, ', COALESCE(s.south_champion_name, 'Mindanao'), ' established their conference dynasty with a staggering ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.south_champion_id = s.south_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              ' straight championships as ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.south_champion_id = s.south_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('In Mindanao, ', COALESCE(s.south_champion_name, 'Mindanao'), ' defended their conference crown with ')
      ELSE CONCAT('In Mindanao, ', COALESCE(s.south_champion_name, 'Mindanao'), ' stunned fans as ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.south_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'a reliable veteran'),
    ' poured in ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.south_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points to secure the title. ',

    -- Luzon Conference
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.east_champion_id = s.east_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('In Luzon, ', COALESCE(s.east_champion_name, 'Luzon'), ' became the team to beat with their ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.east_champion_id = s.east_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              'th consecutive conference championship spearheaded by ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.east_champion_id = s.east_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('In Luzon, ', COALESCE(s.east_champion_name, 'Luzon'), ' went back-to-back as conference champs behind ')
      ELSE CONCAT('In Luzon, ', COALESCE(s.east_champion_name, 'Luzon'), ' turned heads behind the brilliance of ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.east_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'a clutch performer'),
    ', who lit up the scoreboard with ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.east_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' points. ',

    -- NCR Conference
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.west_champion_id = s.west_champion_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('Meanwhile in NCR, ', COALESCE(s.west_champion_name, 'NCR'), ' rewrote the history books with an unprecedented ',
              (SELECT COUNT(*) FROM seasons s2 
               WHERE s2.west_champion_id = s.west_champion_id 
               AND s2.id <= s.id AND s2.status = 17),
              ' straight conference titles, powered by ')
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.west_champion_id = s.west_champion_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        CONCAT('Meanwhile in NCR, ', COALESCE(s.west_champion_name, 'NCR'), ' repeated as conference kings with ')
      ELSE CONCAT('Meanwhile in NCR, ', COALESCE(s.west_champion_name, 'NCR'), ' secured the crown thanks to ')
    END,
    COALESCE((SELECT p.name FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.west_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'an unstoppable force'),
    ' scoring ',
    COALESCE((SELECT pgs.points FROM player_game_stats pgs JOIN players p ON p.id = pgs.player_id WHERE pgs.team_id = s.west_champion_id AND p.draft_id = s.id ORDER BY pgs.points DESC LIMIT 1), 'N/A'), ' in their final test. ',

    -- Redemption or comeback storyline
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) >= 3 THEN
        'Their championship dynasty shows no signs of slowing down, striking fear into future challengers. '
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) = 2 THEN
        'The three-peat cements their legacy as one of the greatest teams in league history. '
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) = 1 THEN
        'Back-to-back titles prove this was no fluke - a true powerhouse has emerged. '
      WHEN EXISTS (
        SELECT 1 FROM seasons s2 
        WHERE s2.id < s.id 
          AND s2.finals_loser_id = s.finals_winner_id 
          AND s2.status = 17
      ) THEN 'In a storybook comeback, the champions redeemed a painful past finals loss. '
      ELSE 'A new titan rose, conquering the league for the first time. '
    END,

    -- Future outlook with dynasty context
    CASE 
      WHEN (SELECT COUNT(*) FROM seasons s2 
            WHERE s2.finals_winner_id = s.finals_winner_id 
            AND s2.id < s.id AND s2.status = 17) >= 2 THEN
        CONCAT('Can anyone dethrone the ', COALESCE(NULLIF(s.finals_winner_name, ''), 'reigning champions'), ' dynasty? ')
      ELSE 'Looking ahead, fans wonder who will rise to challenge next season. '
    END,
    
    'Will we witness new challengers emerge, or will the established powers continue their reign? Only time will tell.'
  ) AS storyline,

  s.created_at AS season_created_at,
  s.updated_at AS season_updated_at

FROM seasons s
LEFT JOIN team_season_info tsi 
  ON s.id = tsi.season_id AND s.finals_winner_id = tsi.team_id
LEFT JOIN player_season_stats pss 
  ON s.finals_mvp_id = pss.player_id AND s.id = pss.season_id
WHERE s.status = 17
ORDER BY s.id;