ALTER TABLE standings_snapshots
    ADD COLUMN total_points_for INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN total_points_against INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN total_points_for_avg INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN total_points_against_avg INT UNSIGNED NOT NULL DEFAULT 0;