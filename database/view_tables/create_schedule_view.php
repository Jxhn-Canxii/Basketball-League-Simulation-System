CREATE OR REPLACE VIEW schedule_view AS
            SELECT
                s.*,
                t_home.name AS home_team_name,
                t_away.name AS away_team_name,
                t_home.city AS home_team_city,
                t_away.city AS away_team_city,
                se.name AS season_name,
                l.name AS league_name,
                se.type AS league_type
            FROM
                schedules s
            JOIN
                teams t_home ON s.home_id = t_home.id
            JOIN
                teams t_away ON s.away_id = t_away.id
            JOIN
                seasons se ON s.season_id = se.id
            JOIN
                leagues l ON se.league_id = l.id
