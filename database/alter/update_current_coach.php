UPDATE teams
LEFT JOIN coaches ON teams.id = coaches.team_id
SET teams.coach_id = COALESCE(coaches.id, 0);

UPDATE coaches 
SET 
    career_wins = 0,
    contract_years = 0,
    career_losses = 0,
    team_id = 0,
    winning_percentage = 0;


UPDATE `teams` SET `coach_id`=0;