
CREATE OR REPLACE VIEW player_contract_monitoring AS 
SELECT
    p.name AS player_name,
    t.name AS team_name,
    pc.*
FROM
    `player_contracts` pc
JOIN players p ON
    p.id = pc.player_id
JOIN teams t ON
    t.id = pc.team_id
WHERE
    pc.status = 'signed'
ORDER BY
    pc.salary
DESC;

CREATE OR REPLACE VIEW player_max_contract_monitoring AS 
SELECT
    p.name AS player_name,
    t.name AS team_name,
    pc.*
FROM
    `player_contracts` pc
JOIN players p ON
    p.id = pc.player_id
JOIN teams t ON
    t.id = pc.team_id
WHERE
    pc.status = 'signed'
AND pc.contract_type = 'max' OR pc.contract_type = 'standard'
ORDER BY
    pc.salary
DESC;
    