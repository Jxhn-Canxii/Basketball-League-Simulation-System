CREATE OR REPLACE VIEW free_agent_position_summary AS
SELECT
    COUNT(CASE WHEN position LIKE '%PG%' THEN 1 END) AS PG,
    COUNT(CASE WHEN position LIKE '%SG%' THEN 1 END) AS SG,
    COUNT(CASE WHEN position LIKE '%SF%' THEN 1 END) AS SF,
    COUNT(CASE WHEN position LIKE '%PF%' THEN 1 END) AS PF,
    COUNT(CASE WHEN position LIKE '%C%'  THEN 1 END) AS C
FROM players
WHERE team_id = 0
  AND is_active = 1;
