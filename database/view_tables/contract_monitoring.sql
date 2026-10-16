CREATE OR REPLACE VIEW player_contract_monitoring AS
SELECT
    p.name AS player_name,
    p.age,
    t.name AS team_name,
    pc.salary,
    pc.contract_type,
    CONCAT('Season ',pc.season_id) AS signed_in,
    CONCAT('Season ',pc.contract_end) AS ends_in,
    pc.player_option,
    pc.team_option,
    pc.no_trade_clause
FROM player_contracts pc
JOIN players p
    ON p.id = pc.player_id
JOIN teams t
    ON t.id = pc.team_id
WHERE
    pc.status = 'signed'
ORDER BY
    pc.salary DESC,
    pc.season_id DESC,
    pc.no_trade_clause DESC,
    p.name ASC;

CREATE OR REPLACE VIEW player_max_contract_monitoring AS
SELECT
    p.name AS player_name,
    p.age,
    t.name AS team_name,
    pc.salary,
    pc.contract_type,
    CONCAT('Season ',pc.season_id) AS signed_in,
    CONCAT('Season ',pc.contract_end) AS ends_in,
    pc.player_option,
    pc.team_option,
    pc.no_trade_clause
FROM player_contracts pc
JOIN players p
    ON p.id = pc.player_id
JOIN teams t
    ON t.id = pc.team_id
WHERE
    pc.status = 'signed'
    AND pc.contract_type IN ('max', 'standard')
ORDER BY
    pc.salary DESC,
    pc.season_id DESC,
    pc.no_trade_clause DESC,
    p.name ASC;