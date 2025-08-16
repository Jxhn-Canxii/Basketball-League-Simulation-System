ALTER TABLE schedules
DROP COLUMN game_number,  -- Remove the old column
ADD COLUMN game_number INT 
GENERATED ALWAYS AS (
    CASE
        WHEN LOCATE('G', game_id) > 0 AND LENGTH(game_id) > LOCATE('G', game_id) + 1 THEN
            CAST(SUBSTRING(game_id, LOCATE('G', game_id) + 1, LOCATE('-', game_id, LOCATE('G', game_id)) - LOCATE('G', game_id) - 1) AS UNSIGNED)
        ELSE 
            CAST(SUBSTRING(game_id, LOCATE('G', game_id) + 1) AS UNSIGNED)
    END
) STORED;
