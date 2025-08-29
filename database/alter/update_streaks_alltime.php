DELIMITER $$

CREATE PROCEDURE update_streaks()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE team INT;

    -- cursor for all distinct team IDs
    DECLARE cur CURSOR FOR
        SELECT DISTINCT team_id FROM (
            SELECT home_id AS team_id FROM schedules
            UNION
            SELECT away_id FROM schedules
        ) t;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO team;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- reset variables per team
        SET @current_win = 0;
        SET @best_win = 0;
        SET @best_win_start = NULL;
        SET @best_win_end = NULL;
        SET @current_win_start = NULL;

        SET @current_loss = 0;
        SET @best_loss = 0;
        SET @best_loss_start = NULL;
        SET @best_loss_end = NULL;
        SET @current_loss_start = NULL;

        -- process team games ordered by game_id
        -- single cursor, no extra temp table
        BEGIN
            DECLARE done2 INT DEFAULT FALSE;
            DECLARE g_id INT;
            DECLARE g_winner INT;

            DECLARE cur2 CURSOR FOR
                SELECT s.id, s.winner_id
                FROM schedules s
                WHERE (s.home_id = team OR s.away_id = team)
                  AND s.status = 2
                ORDER BY s.id;

            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done2 = TRUE;

            OPEN cur2;

            read_loop2: LOOP
                FETCH cur2 INTO g_id, g_winner;
                IF done2 THEN
                    LEAVE read_loop2;
                END IF;

                -- WIN CASE
                IF g_winner = team THEN
                    SET @current_win = @current_win + 1;
                    IF @current_win = 1 THEN
                        SET @current_win_start = g_id;
                    END IF;

                    IF @current_win > @best_win THEN
                        SET @best_win = @current_win;
                        SET @best_win_start = @current_win_start;
                        SET @best_win_end = g_id;
                    END IF;

                    SET @current_loss = 0;
                    SET @current_loss_start = NULL;
                ELSE
                    -- LOSS CASE
                    SET @current_loss = @current_loss + 1;
                    IF @current_loss = 1 THEN
                        SET @current_loss_start = g_id;
                    END IF;

                    IF @current_loss > @best_loss THEN
                        SET @best_loss = @current_loss;
                        SET @best_loss_start = @current_loss_start;
                        SET @best_loss_end = g_id;
                    END IF;

                    SET @current_win = 0;
                    SET @current_win_start = NULL;
                END IF;
            END LOOP read_loop2;

            CLOSE cur2;
        END;

        -- update streak table
        INSERT INTO streak (
            team_id, 
            best_winning_streak, best_losing_streak,
            best_winning_streak_start_id, best_winning_streak_end_id,
            best_losing_streak_start_id, best_losing_streak_end_id,
            created_at, updated_at
        )
        VALUES (
            team, 
            @best_win, @best_loss,
            @best_win_start, @best_win_end,
            @best_loss_start, @best_loss_end,
            NOW(), NOW()
        )
        ON DUPLICATE KEY UPDATE
            best_winning_streak = @best_win,
            best_losing_streak = @best_loss,
            best_winning_streak_start_id = @best_win_start,
            best_winning_streak_end_id = @best_win_end,
            best_losing_streak_start_id = @best_loss_start,
            best_losing_streak_end_id = @best_loss_end,
            updated_at = NOW();

    END LOOP read_loop;

    CLOSE cur;
END$$

DELIMITER ;
