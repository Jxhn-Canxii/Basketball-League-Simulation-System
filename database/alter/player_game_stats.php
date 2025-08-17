ADD COLUMN `trade_value` DECIMAL(10, 2) GENERATED ALWAYS AS (
    ((per * 0.5) + (avg_points_per_game * 0.3) - (avg_turnovers_per_game * 0.2))
) STORED;