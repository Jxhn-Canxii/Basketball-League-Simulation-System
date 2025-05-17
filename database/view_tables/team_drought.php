CREATE OR REPLACE VIEW team_droughts AS
SELECT 
    t.id AS team_id,
    t.name AS team_name,
    COALESCE(MAX(s.id), 0) AS latest_season_id,

    -- Last championship season
    (
        SELECT MAX(sc.season_id)
        FROM schedules sc
        WHERE sc.round = 'finals'
          AND sc.winner_id = t.id
    ) AS last_championship_season_id,

    -- Championship drought (seasons since last championship win)
    (
        COALESCE(MAX(s.id), 0) -
        COALESCE((
            SELECT MAX(sc.season_id)
            FROM schedules sc
            WHERE sc.round = 'finals'
              AND sc.winner_id = t.id
        ), 0)
    ) AS championship_drought_seasons,

    -- Last playoff appearance season
    (
        SELECT MAX(sc.season_id)
        FROM schedules sc
        WHERE sc.round IN (
            'play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals',
            'round_of_32', 'round_of_16', 'quarter_finals', 'semi_finals',
            'interconference_semi_finals', 'finals'
        )
        AND (sc.home_team_id = t.id OR sc.away_team_id = t.id)
    ) AS last_playoff_season_id,

    -- Playoff drought (seasons since last playoff appearance)
    (
        COALESCE(MAX(s.id), 0) -
        COALESCE((
            SELECT MAX(sc.season_id)
            FROM schedules sc
            WHERE sc.round IN (
                'play_ins_elims_round_1', 'play_ins_elims_round_2', 'play_ins_finals',
                'round_of_32', 'round_of_16', 'quarter_finals', 'semi_finals',
                'interconference_semi_finals', 'finals'
            )
            AND (sc.home_team_id = t.id OR sc.away_team_id = t.id)
        ), 0)
    ) AS playoff_drought_seasons

FROM teams t
CROSS JOIN seasons s
GROUP BY t.id, t.name;
