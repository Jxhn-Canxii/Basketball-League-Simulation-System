UPDATE teams
JOIN coaches ON coaches.team_id = teams.id
SET teams.coach_id = coaches.id;