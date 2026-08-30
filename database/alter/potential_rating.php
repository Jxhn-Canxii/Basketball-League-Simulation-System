UPDATE players
SET potential_rating =
    CASE
        WHEN overall_rating >= 90 THEN 99
        WHEN overall_rating >= 85 THEN 95
        WHEN overall_rating >= 75 THEN 90
        WHEN overall_rating >= 60 THEN 80
        ELSE 70
    END;
