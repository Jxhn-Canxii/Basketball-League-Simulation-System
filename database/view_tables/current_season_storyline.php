CREATE OR REPLACE VIEW current_season_storyline AS
WITH RECURSIVE

/* =========================================================
   CURRENT COMPLETED SEASON
   ========================================================= */
current_season AS (
    SELECT *
    FROM seasons
    WHERE status > 10
    ORDER BY id DESC
    LIMIT 1
),

/* =========================================================
   CHAMPIONSHIP HISTORY
   ========================================================= */
championship_history AS (
    SELECT
        s.id,
        s.finals_winner_id,
        s.finals_winner_name,

        COUNT(*) OVER (
            PARTITION BY s.finals_winner_id
            ORDER BY s.id
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        ) AS total_titles,

        LAG(s.id) OVER (
            PARTITION BY s.finals_winner_id
            ORDER BY s.id
        ) AS previous_title_season
    FROM seasons s
    WHERE s.status > 10
      AND s.finals_winner_id IS NOT NULL
),

champion_streaks AS (
    SELECT
        ch.*,
        CASE
            WHEN ch.previous_title_season = ch.id - 1 THEN 0
            ELSE 1
        END AS streak_break
    FROM championship_history ch
),

champion_groups AS (
    SELECT
        cs.*,
        SUM(streak_break) OVER (
            PARTITION BY finals_winner_id
            ORDER BY id
        ) AS streak_group
    FROM champion_streaks cs
),

champion_runs AS (
    SELECT
        id,
        finals_winner_id,
        total_titles,
        COUNT(*) OVER (
            PARTITION BY finals_winner_id, streak_group
            ORDER BY id
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        ) AS consecutive_titles
    FROM champion_groups
),

/* =========================================================
   PREVIOUS FINALS
   Used to determine redemption / revenge storylines.
   ========================================================= */
previous_finals AS (
    SELECT
        s.id AS season_id,
        s.finals_winner_id,
        s.finals_loser_id,
        s.finals_winner_name,
        s.finals_loser_name
    FROM seasons s
    WHERE s.status > 10
),

redemption AS (
    SELECT
        cs.id AS season_id,
        cs.finals_winner_id,
        MAX(pf.season_id) AS previous_final_loss_season
    FROM current_season cs
    JOIN previous_finals pf
        ON pf.finals_loser_id = cs.finals_winner_id
       AND pf.season_id < cs.id
    GROUP BY
        cs.id,
        cs.finals_winner_id
),

/* =========================================================
   PLAYOFF SERIES
   IMPORTANT:
   best_of = wins required
   series_length = maximum possible games
   ========================================================= */
season_series AS (
    SELECT
        ps.*,
        t1.name AS home_team_name,
        t2.name AS away_team_name,
        tw.name AS winner_name,
        tl.name AS loser_name,

        (ps.home_wins + ps.away_wins) AS games_played,

        CASE
            WHEN ps.winner_team_id = ps.home_team_id
                THEN ps.home_wins
            ELSE ps.away_wins
        END AS winner_wins,

        CASE
            WHEN ps.loser_team_id = ps.home_team_id
                THEN ps.home_wins
            ELSE ps.away_wins
        END AS loser_wins

    FROM playoff_series ps
    LEFT JOIN teams t1
        ON t1.id = ps.home_team_id
    LEFT JOIN teams t2
        ON t2.id = ps.away_team_id
    LEFT JOIN teams tw
        ON tw.id = ps.winner_team_id
    LEFT JOIN teams tl
        ON tl.id = ps.loser_team_id
    JOIN current_season cs
        ON cs.id = ps.season_id
),

/* =========================================================
   SERIES COUNTS
   ========================================================= */
series_summary AS (
    SELECT
        season_id,

        COUNT(*) AS total_series,

        SUM(
            CASE
                WHEN games_played = 3
                 AND best_of = 3
                THEN 1
                ELSE 0
            END
        ) AS three_game_series,

        SUM(
            CASE
                WHEN games_played = 5
                 AND best_of = 3
                THEN 1
                ELSE 0
            END
        ) AS five_game_series,

        SUM(
            CASE
                WHEN games_played = 7
                 AND best_of = 4
                THEN 1
                ELSE 0
            END
        ) AS seven_game_series,

        SUM(
            CASE
                WHEN games_played = best_of
                THEN 1
                ELSE 0
            END
        ) AS sweeps,

        SUM(
            CASE
                WHEN games_played = series_length
                THEN 1
                ELSE 0
            END
        ) AS went_distance

    FROM season_series
    GROUP BY season_id
),

/* =========================================================
   FINALS SERIES
   ========================================================= */
finals_series AS (
    SELECT
        ss.*
    FROM season_series ss
    WHERE ss.round = 'finals'
    ORDER BY ss.id DESC
    LIMIT 1
),

/* =========================================================
   BIG 4 SERIES
   ========================================================= */
big4_series AS (
    SELECT
        ss.*
    FROM season_series ss
    WHERE ss.round = 'interconference_semi_finals'
),

big4_summary AS (
    SELECT
        season_id,
        COUNT(*) AS big4_series_count,

        SUM(
            CASE
                WHEN games_played = series_length
                THEN 1
                ELSE 0
            END
        ) AS big4_went_distance,

        GROUP_CONCAT(
            CONCAT(
                winner_name,
                ' defeated ',
                loser_name,
                ' ',
                winner_wins,
                '-',
                loser_wins
            )
            ORDER BY id
            SEPARATOR '; '
        ) AS big4_results

    FROM big4_series
    GROUP BY season_id
),

/* =========================================================
   CONFERENCE CHAMPIONS
   Your existing season fields identify them.
   ========================================================= */
conference_champions AS (
    SELECT
        cs.id AS season_id,

        cs.west_champion_name,
        cs.east_champion_name,
        cs.north_champion_name,
        cs.south_champion_name,

        cs.west_champion_id,
        cs.east_champion_id,
        cs.north_champion_id,
        cs.south_champion_id

    FROM current_season cs
),

/* =========================================================
   CONFERENCE CHAMPION HISTORY
   ========================================================= */
conference_history AS (
    SELECT
        s.id,
        s.west_champion_id,
        s.east_champion_id,
        s.north_champion_id,
        s.south_champion_id
    FROM seasons s
    WHERE s.status > 10
),

conference_totals AS (
    SELECT
        cc.season_id,

        (
            SELECT COUNT(*)
            FROM conference_history h
            WHERE h.west_champion_id = cc.west_champion_id
              AND h.id <= cc.season_id
        ) AS west_titles,

        (
            SELECT COUNT(*)
            FROM conference_history h
            WHERE h.east_champion_id = cc.east_champion_id
              AND h.id <= cc.season_id
        ) AS east_titles,

        (
            SELECT COUNT(*)
            FROM conference_history h
            WHERE h.north_champion_id = cc.north_champion_id
              AND h.id <= cc.season_id
        ) AS north_titles,

        (
            SELECT COUNT(*)
            FROM conference_history h
            WHERE h.south_champion_id = cc.south_champion_id
              AND h.id <= cc.season_id
        ) AS south_titles

    FROM conference_champions cc
),

/* =========================================================
   CONFERENCE PLAYOFF RESULTS
   ========================================================= */
conference_results AS (
    SELECT
        round,
        COUNT(*) AS series_count,

        SUM(
            CASE
                WHEN games_played = series_length
                THEN 1
                ELSE 0
            END
        ) AS seven_game_battles,

        GROUP_CONCAT(
            CONCAT(
                winner_name,
                ' ',
                winner_wins,
                '-',
                loser_wins,
                ' ',
                loser_name
            )
            ORDER BY id
            SEPARATOR '; '
        ) AS results

    FROM season_series
    WHERE round IN (
        'play_ins_elims_round_1',
        'play_ins_elims_round_2',
        'play_ins_elims',
        'play_ins_finals',
        'round_of_32',
        'round_of_16',
        'quarter_finals',
        'semi_finals'
    )
    GROUP BY round
),

/* =========================================================
   PLAY-IN SUMMARY
   ========================================================= */
playin_summary AS (
    SELECT
        COUNT(*) AS playin_series,

        GROUP_CONCAT(
            CASE
                WHEN round = 'play_ins_elims_round_2'
            THEN
                CONCAT(
                    winner_name,
                    ' advances in the Play-ins Finals to decide who will get the last ticket to the playoffs. While,',
                    loser_name,
                    ' has been eliminated in the play-ins.',
                )
            WHEN round = 'play_ins_elims_round_1'
            THEN
                CONCAT(
                    winner_name,
                    ' advances in the play-offs as a 7th seed as they beat ',
                    loser_name,
                    ' The ',
                    loser_name,
                    ' will take the the winner of the Play-ins(9th vs 10th) as the battle for the last ticket for the playoffs.',
                )
            WHEN round = 'play_ins_finals'
            THEN
                CONCAT(
                    winner_name,
                    ' advances in the play-offs as the 8th seed as they beat ',
                    loser_name,
                    ' The ',
                    loser_name,
                    ' season will come to the end.',
                ) 
            ELSE
                CONCAT(
                    winner_name,
                    ' eliminated ',
                    loser_name,
                    ' ',
                    winner_wins,
                    '-',
                    loser_wins
                )
            ORDER BY id
            SEPARATOR '; '
        ) AS playin_results

    FROM season_series
    WHERE round IN (
        'play_ins_elims_round_1',
        'play_ins_elims_round_2',
        'play_ins_elims',
        'play_ins_finals'
    )
),

/* =========================================================
   FINALS MVP
   ========================================================= */
finals_mvp AS (
    SELECT
        cs.id AS season_id,
        p.name AS player_name
    FROM current_season cs
    LEFT JOIN players p
        ON p.id = cs.finals_mvp_id
),

/* =========================================================
   AWARDS
   ========================================================= */
awards AS (
    SELECT
        sa.season_id,

        MAX(
            CASE
                WHEN sa.award_name = 'Best Overall Player'
                THEN p.name
            END
        ) AS mvp,

        MAX(
            CASE
                WHEN sa.award_name = 'Best Defensive Player'
                THEN p.name
            END
        ) AS dpoy,

        MAX(
            CASE
                WHEN sa.award_name = 'Sixth Man of the Year'
                THEN p.name
            END
        ) AS sixth_man,

        MAX(
            CASE
                WHEN sa.award_name = 'Most Improved Player'
                THEN p.name
            END
        ) AS mip,

        MAX(
            CASE
                WHEN sa.award_name = 'Rookie of the Season'
                THEN p.name
            END
        ) AS roy

    FROM season_awards sa
    JOIN players p
        ON p.id = sa.player_id
    JOIN current_season cs
        ON cs.id = sa.season_id
    GROUP BY sa.season_id
),

/* =========================================================
   FINALS GAME STATISTICS
   ========================================================= */
finals_games AS (
    SELECT
        sch.*,
        ROW_NUMBER() OVER (
            PARTITION BY sch.season_id
            ORDER BY sch.game_id DESC
        ) AS rn
    FROM schedules sch
    JOIN current_season cs
        ON cs.id = sch.season_id
    WHERE sch.round = 'finals'
),

finals_last_game AS (
    SELECT
        fg.*
    FROM finals_games fg
    WHERE fg.rn = 1
),

/* =========================================================
   HIGHEST SCORING PLAYOFF GAME
   ========================================================= */
highest_scoring_game AS (
    SELECT
        sch.season_id,
        CASE
            WHEN sch.home_score >= sch.away_score
                THEN sch.home_id
            ELSE sch.away_id
        END AS team_id,

        GREATEST(
            sch.home_score,
            sch.away_score
        ) AS points

    FROM schedules sch
    JOIN current_season cs
        ON cs.id = sch.season_id
    WHERE sch.round IN (
        'play_ins_elims_round_1',
        'play_ins_elims_round_2',
        'play_ins_elims',
        'play_ins_finals',
        'round_of_32',
        'round_of_16',
        'quarter_finals',
        'semi_finals',
        'interconference_semi_finals',
        'finals'
    )
    ORDER BY points DESC
    LIMIT 1
),

highest_scoring_game_details AS (
    SELECT
        hsg.season_id,
        hsg.points,
        t.name AS team_name
    FROM highest_scoring_game hsg
    LEFT JOIN teams t
        ON t.id = hsg.team_id
),

/* =========================================================
   TOP PLAYOFF SCORER
   ========================================================= */
top_playoff_scorer AS (
    SELECT
        pgs.player_id,
        p.name AS player_name,
        pgs.points,
        sch.season_id

    FROM player_game_stats pgs
    JOIN schedules sch
        ON sch.game_id = pgs.game_id
    JOIN players p
        ON p.id = pgs.player_id
    JOIN current_season cs
        ON cs.id = sch.season_id
    WHERE sch.round IN (
        'play_ins_elims_round_1',
        'play_ins_elims_round_2',
        'play_ins_elims',
        'play_ins_finals',
        'round_of_32',
        'round_of_16',
        'quarter_finals',
        'semi_finals',
        'interconference_semi_finals',
        'finals'
    )
    ORDER BY pgs.points DESC
    LIMIT 1
),

/* =========================================================
   TOP PLAYOFF REBOUNDER
   ========================================================= */
top_playoff_rebounder AS (
    SELECT
        p.name AS player_name,
        pgs.rebounds,
        sch.season_id
    FROM player_game_stats pgs
    JOIN schedules sch
        ON sch.game_id = pgs.game_id
    JOIN players p
        ON p.id = pgs.player_id
    JOIN current_season cs
        ON cs.id = sch.season_id
    WHERE sch.round IN (
        'play_ins_elims_round_1',
        'play_ins_elims_round_2',
        'play_ins',
        'play_ins_finals',
        'round_of_32',
        'round_of_16',
        'quarter_finals',
        'semi_finals',
        'interconference_semi_finals',
        'finals'
    )
    ORDER BY pgs.rebounds DESC
    LIMIT 1
),

/* =========================================================
   TOP PLAYOFF ASSIST
   ========================================================= */
top_playoff_assist AS (
    SELECT
        p.name AS player_name,
        pgs.assists,
        sch.season_id
    FROM player_game_stats pgs
    JOIN schedules sch
        ON sch.game_id = pgs.game_id
    JOIN players p
        ON p.id = pgs.player_id
    JOIN current_season cs
        ON cs.id = sch.season_id
    WHERE sch.round IN (
        'play_ins_elims_round_1',
        'play_ins_elims_round_2',
        'play_ins',
        'play_ins_finals',
        'round_of_32',
        'round_of_16',
        'quarter_finals',
        'semi_finals',
        'interconference_semi_finals',
        'finals'
    )
    ORDER BY pgs.assists DESC
    LIMIT 1
)

/* =========================================================
   FINAL STORYLINE
   ========================================================= */
SELECT
    cs.id AS season_id,
    cs.name AS season_name,
    cs.created_at,
    cs.updated_at,

    CONCAT(

        /* =================================================
           HEADLINE
           ================================================= */

        CASE

            WHEN cs.id = 1 THEN
                CONCAT(
                    'INAUGURAL GLORY: ',
                    UPPER(cs.finals_winner_name),
                    ' CROWNED THE LEAGUE''S FIRST CHAMPIONS'
                )

            WHEN cr.consecutive_titles >= 4 THEN
                CONCAT(
                    UPPER(cs.finals_winner_name),
                    ' ARE BUILDING A DYNASTY FOR THE AGES'
                )

            WHEN cr.consecutive_titles = 3 THEN
                CONCAT(
                    UPPER(cs.finals_winner_name),
                    ' COMPLETE A THREE-PEAT'
                )

            WHEN cr.consecutive_titles = 2 THEN
                CONCAT(
                    UPPER(cs.finals_winner_name),
                    ' GO BACK-TO-BACK'
                )

            WHEN r.previous_final_loss_season IS NOT NULL THEN
                CONCAT(
                    UPPER(cs.finals_winner_name),
                    ' FINALLY GET THEIR REVENGE'
                )

            WHEN cr.total_titles >= 2 THEN
                CONCAT(
                    UPPER(cs.finals_winner_name),
                    ' ADD ANOTHER CHAMPIONSHIP TO THEIR LEGACY'
                )

            ELSE
                CONCAT(
                    UPPER(cs.finals_winner_name),
                    ' BREAK THROUGH TO CLAIM THE CROWN'
                )

        END,

        '\n\n',

        /* =================================================
           OPENING PARAGRAPH
           ================================================= */

        'The ',
        cs.name,
        ' season delivered another chapter of ',
        CASE
            WHEN fs.games_played = fs.series_length
                THEN 'high-stakes basketball, capped by a Finals series that went the full distance'
            WHEN fs.games_played = fs.best_of
                THEN 'high-stakes basketball, capped by a decisive Finals sweep'
            ELSE
                'high-stakes basketball, with contenders battling through a demanding playoff bracket'
        END,
        '. ',

        cs.finals_winner_name,
        ' emerged as champion after a postseason defined by ',

        CASE
            WHEN ss.went_distance >= 3 THEN
                CONCAT(
                    ss.went_distance,
                    ' series that went the distance and left little room for error'
                )

            WHEN ss.sweeps >= 3 THEN
                CONCAT(
                    ss.sweeps,
                    ' playoff sweeps that showcased just how unforgiving the postseason could be'
                )

            WHEN ss.went_distance >= 1 THEN
                'several hard-fought series and at least one battle that went to the limit'
            
            ELSE
                'a string of decisive performances and tightly contested matchups'
        END,

        '. ',

        CASE
            WHEN r.previous_final_loss_season IS NOT NULL THEN
                CONCAT(
                    'For ',
                    cs.finals_winner_name,
                    ', the championship carried extra weight after their Finals defeat in Season ',
                    r.previous_final_loss_season,
                    '. '
                )

            WHEN cr.consecutive_titles >= 2 THEN
                CONCAT(
                    'The victory also extended their remarkable championship run to ',
                    cr.consecutive_titles,
                    ' straight titles. '
                )

            WHEN cr.total_titles = 1 THEN
                'It was the breakthrough moment the franchise had been chasing. '

            ELSE
                CONCAT(
                    'The latest championship adds another defining chapter to ',
                    cs.finals_winner_name,
                    '''s growing legacy. '
                )
        END,

        /* =================================================
           PLAY-IN
           ================================================= */

        CASE
            WHEN pi.playin_series > 0 THEN
                CONCAT(
                    '\n\nThe postseason race began in the ',
                    'Conference Play-ins, where ',
                    pi.playin_series,
                    ' series helped determine the final playoff field. ',

                    COALESCE(
                        pi.playin_results,
                        'Several teams fought through the pressure-packed opening round.'
                    ),
                    '.'
                )
            ELSE
                ''
        END,

        /* =================================================
           CONFERENCE CHAMPIONS
           ================================================= */

        '\n\n',

        'The conference races produced four champions: ',

        COALESCE(cc.north_champion_name, 'Visayas champion'),
        ' in Visayas, ',
        COALESCE(cc.south_champion_name, 'Mindanao champion'),
        ' in Mindanao, ',
        COALESCE(cc.east_champion_name, 'Luzon champion'),
        ' in Luzon, and ',
        COALESCE(cc.west_champion_name, 'NCR champion'),
        ' in NCR. ',

        CASE
            WHEN ct.north_titles >= 3 THEN
                CONCAT(
                    cc.north_champion_name,
                    ' continued a dominant run in Visayas with their ',
                    ct.north_titles,
                    'th conference crown. '
                )
            WHEN ct.north_titles = 2 THEN
                CONCAT(
                    cc.north_champion_name,
                    ' added a second Visayas championship to their trophy case. '
                )
            ELSE
                CONCAT(
                    cc.north_champion_name,
                    ' captured the Visayas crown for the first time. '
                )
        END,

        CASE
            WHEN ct.south_titles >= 3 THEN
                CONCAT(
                    cc.south_champion_name,
                    ' meanwhile continued to control Mindanao, collecting conference title number ',
                    ct.south_titles,
                    '. '
                )
            WHEN ct.south_titles = 2 THEN
                CONCAT(
                    cc.south_champion_name,
                    ' returned to the top of Mindanao for their second conference championship. '
                )
            ELSE
                CONCAT(
                    cc.south_champion_name,
                    ' broke through to claim their first Mindanao championship. '
                )
        END,

        /* =================================================
           BIG 4
           ================================================= */

        CASE
            WHEN b4.big4_series_count > 0 THEN
                CONCAT(
                    '\n\nThe Big 4 stage narrowed the championship field to its final two survivors. ',

                    CASE
                        WHEN b4.big4_went_distance > 0 THEN
                            CONCAT(
                                b4.big4_went_distance,
                                ' of those series went the full ',
                                'distance, turning the round into a true test of endurance.'
                            )
                        ELSE
                            'The matchups were decided without a single series reaching its maximum length.'
                    END,

                    ' The results were ',
                    COALESCE(b4.big4_results, 'closely contested battles'),
                    '.'
                )
            ELSE
                ''
        END,

        /* =================================================
           FINALS
           ================================================= */

        '\n\n',

        '### The Finals',

        '\n\n',
        CASE

            WHEN fs.games_played = fs.series_length THEN
                CONCAT(
                    'The Finals delivered a classic. ',
                    fs.winner_name,
                    ' survived ',
                    fs.loser_name,
                    ' ',
                    fs.winner_wins,
                    '-',
                    fs.loser_wins,
                    ' after ',
                    fs.games_played,
                    ' games, with the championship hanging in the balance until the final night.'
                )

            WHEN fs.games_played = fs.best_of THEN
                CONCAT(
                    fs.winner_name,
                    ' left no doubt in The Finals, sweeping ',
                    fs.loser_name,
                    ' ',
                    fs.winner_wins,
                    '-',
                    fs.loser_wins,
                    ' to seize the championship in emphatic fashion.'
                )

            WHEN fs.games_played = fs.best_of + 1 THEN
                CONCAT(
                    fs.winner_name,
                    ' closed out The Finals against ',
                    fs.loser_name,
                    ' ',
                    fs.winner_wins,
                    '-',
                    fs.loser_wins,
                    ' in ',
                    fs.games_played,
                    ' games, taking control when the series reached its decisive stage.'
                )

            ELSE
                CONCAT(
                    fs.winner_name,
                    ' defeated ',
                    fs.loser_name,
                    ' ',
                    fs.winner_wins,
                    '-',
                    fs.loser_wins,
                    ' in a ',
                    fs.games_played,
                    '-game championship battle.'
                )

        END,

        /* =================================================
           FINALS SCORE
           ================================================= */

        CASE
            WHEN fg.home_score IS NOT NULL THEN

                CONCAT(
                    ' The decisive game ended ',
                    ht.name,
                    ' ',
                    fg.home_score,
                    ' - ',
                    fg.away_score,
                    ' ',
                    at.name,
                    '. ',

                    CASE

                        WHEN ABS(fg.home_score - fg.away_score) <= 3 THEN
                            'It was a finish decided by the thinnest of margins, with every possession carrying championship weight.'

                        WHEN ABS(fg.home_score - fg.away_score) <= 7 THEN
                            'The margin stayed within striking distance deep into the game before the champions finally pulled clear.'

                        WHEN ABS(fg.home_score - fg.away_score) <= 12 THEN
                            'The champions created separation in the second half and refused to let the series slip away.'

                        ELSE
                            'The final scoreline reflected a commanding performance from the eventual champions.'
                    END
                )

            ELSE
                ''
        END,

        /* =================================================
           FINALS MVP
           ================================================= */

        CASE
            WHEN fm.player_name IS NOT NULL THEN
                CONCAT(
                    ' Finals MVP honors went to ',
                    fm.player_name,
                    ', whose postseason performance proved decisive for ',
                    cs.finals_winner_name,
                    '.'
                )
            ELSE
                ''
        END,

        /* =================================================
           PLAYOFF PERFORMERS
           ================================================= */

        CASE
            WHEN tps.player_name IS NOT NULL THEN
                CONCAT(
                    '\n\nOne of the postseason''s biggest individual performances came from ',
                    tps.player_name,
                    ', who erupted for ',
                    tps.points,
                    ' points in a single playoff game.'
                )
            ELSE
                ''
        END,

        CASE
            WHEN tpr.player_name IS NOT NULL THEN
                CONCAT(
                    ' The glass belonged to ',
                    tpr.player_name,
                    ', who grabbed ',
                    tpr.rebounds,
                    ' rebounds in one playoff performance.'
                )
            ELSE
                ''
        END,

        CASE
            WHEN tpa.player_name IS NOT NULL THEN
                CONCAT(
                    ' The playmaking highlight came from ',
                    tpa.player_name,
                    ', who handed out ',
                    tpa.assists,
                    ' assists in a single game.'
                )
            ELSE
                ''
        END,

        /* =================================================
           AWARDS
           ================================================= */

        '\n\n',

        '### Season Awards',
        
        '\n\n',
        CASE
            WHEN aw.mvp IS NOT NULL
                THEN CONCAT('Season MVP: ', aw.mvp, '. ')
            ELSE ''
        END,

        CASE
            WHEN aw.dpoy IS NOT NULL
                THEN CONCAT('Defensive Player of the Year: ', aw.dpoy, '. ')
            ELSE ''
        END,

        CASE
            WHEN aw.sixth_man IS NOT NULL
                THEN CONCAT('Sixth Man of the Year: ', aw.sixth_man, '. ')
            ELSE ''
        END,

        CASE
            WHEN aw.mip IS NOT NULL
                THEN CONCAT('Most Improved Player: ', aw.mip, '. ')
            ELSE ''
        END,

        CASE
            WHEN aw.roy IS NOT NULL
                THEN CONCAT('Rookie of the Season: ', aw.roy, '. ')
            ELSE ''
        END,

        /* =================================================
           TRIVIA
           ================================================= */
        '\n\n',

        '### Season Trivia',

        '\n\n',

        CASE
            WHEN cr.consecutive_titles >= 4 THEN
                CONCAT(
                    '- ',
                    cs.finals_winner_name,
                    ' extended its championship streak to ',
                    cr.consecutive_titles,
                    ' consecutive titles.'
                )

            WHEN cr.consecutive_titles = 3 THEN
                CONCAT(
                    '- ',
                    cs.finals_winner_name,
                    ' became a three-time consecutive champion.'
                )

            WHEN cr.consecutive_titles = 2 THEN
                CONCAT(
                    '- ',
                    cs.finals_winner_name,
                    ' successfully defended its championship.'
                )

            WHEN cr.total_titles = 1 THEN
                CONCAT(
                    '- ',
                    cs.finals_winner_name,
                    ' became the latest franchise to win its first championship.'
                )

            ELSE
                CONCAT(
                    '- ',
                    cs.finals_winner_name,
                    ' captured championship number ',
                    cr.total_titles,
                    '.'
                )
        END,

        '\n\n',

        CASE
            WHEN hsg.points IS NOT NULL THEN
                CONCAT(
                    '- The highest-scoring playoff performance by a team came from ',
                    hsg.team_name,
                    ', which put up ',
                    hsg.points,
                    ' points in a single game.'
                )
            ELSE
                ''
        END,

        '\n\n',

        CASE
            WHEN fs.games_played = fs.series_length THEN
                CONCAT(
                    '- The Finals went the full ',
                    fs.series_length,
                    ' games, making it one of the season''s defining series.'
                )

            WHEN ss.sweeps > 0 THEN
                CONCAT(
                    '- The postseason featured ',
                    ss.sweeps,
                    ' sweep',
                    CASE WHEN ss.sweeps = 1 THEN '' ELSE 's' END,
                    '.'
                )

            ELSE
                CONCAT(
                    '- The postseason featured ',
                    ss.total_series,
                    ' playoff series across the championship bracket.'
                )
        END,

        /* =================================================
           CLOSING
           ================================================= */

        '\n\n',

        CASE

            WHEN cr.consecutive_titles >= 3 THEN
                CONCAT(
                    'For now, the league belongs to ',
                    cs.finals_winner_name,
                    '. The rest of the contenders will spend the offseason trying to solve a team that has turned championship basketball into a habit.'
                )

            WHEN r.previous_final_loss_season IS NOT NULL THEN
                CONCAT(
                    'For ',
                    cs.finals_winner_name,
                    ', this was more than another championship. It was unfinished business finally settled on the biggest stage.'
                )

            WHEN cr.total_titles = 1 THEN
                CONCAT(
                    'A new champion has arrived. The question now is whether ',
                    cs.finals_winner_name,
                    ' has started a new era or simply stolen the spotlight for one unforgettable season.'
                )

            ELSE
                CONCAT(
                    cs.finals_winner_name,
                    ' leaves the season with the trophy, while the rest of the league heads into the offseason searching for answers. Another chapter is complete, but the race for the next championship has already begun.'
                )

        END

    ) AS storyline

FROM current_season cs

LEFT JOIN champion_runs cr
    ON cr.id = cs.id
   AND cr.finals_winner_id = cs.finals_winner_id

LEFT JOIN redemption r
    ON r.season_id = cs.id

LEFT JOIN series_summary ss
    ON ss.season_id = cs.id

LEFT JOIN finals_series fs
    ON 1 = 1

LEFT JOIN finals_last_game fg
    ON fg.season_id = cs.id

LEFT JOIN teams ht
    ON ht.id = fg.home_id

LEFT JOIN teams at
    ON at.id = fg.away_id

LEFT JOIN conference_champions cc
    ON cc.season_id = cs.id

LEFT JOIN conference_totals ct
    ON ct.season_id = cs.id

LEFT JOIN playin_summary pi
    ON 1 = 1

LEFT JOIN big4_summary b4
    ON b4.season_id = cs.id

LEFT JOIN finals_mvp fm
    ON fm.season_id = cs.id

LEFT JOIN awards aw
    ON aw.season_id = cs.id

LEFT JOIN highest_scoring_game_details hsg
    ON hsg.season_id = cs.id

LEFT JOIN top_playoff_scorer tps
    ON tps.season_id = cs.id

LEFT JOIN top_playoff_rebounder tpr
    ON tpr.season_id = cs.id

LEFT JOIN top_playoff_assist tpa
    ON tpa.season_id = cs.id

LIMIT 1;