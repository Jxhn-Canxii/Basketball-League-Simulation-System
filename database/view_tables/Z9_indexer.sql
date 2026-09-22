/* ============================================================
   LIGA2 INDEX RESET + OPTIMIZED INDEX SET
   MySQL 8.x
   ============================================================ */

SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS drop_liga2_secondary_indexes;

DELIMITER $$

CREATE PROCEDURE drop_liga2_secondary_indexes()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(64);
    DECLARE v_index VARCHAR(64);

    DECLARE cur CURSOR FOR
        SELECT DISTINCT
            TABLE_NAME,
            INDEX_NAME
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN (
              'players',
              'game_quarter_breakdown',
              'player_per_quarter_stats',
              'player_season_playoff_stats',
              'player_game_stats',
              'playoff_series',
              'schedules',
              'seasons',
              'team_season_info',
              'transactions',
              'drafts',
              'draft_pick_rights',
              'conferences',
              'coaches'
          )
          AND INDEX_NAME <> 'PRIMARY'
          AND NON_UNIQUE = 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    read_loop: LOOP

        FETCH cur INTO v_table, v_index;

        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET @sql = CONCAT(
            'ALTER TABLE `',
            v_table,
            '` DROP INDEX `',
            v_index,
            '`'
        );

        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

CALL drop_liga2_secondary_indexes();

DROP PROCEDURE IF EXISTS drop_liga2_secondary_indexes;

SET FOREIGN_KEY_CHECKS = 1;


/* ============================================================
   PLAYERS
   ============================================================ */

ALTER TABLE players

    ADD INDEX idx_players_team_active
        (team_id, is_active),

    ADD INDEX idx_players_team_role
        (team_id, role),

    ADD INDEX idx_players_team_position
        (team_id, position),

    ADD INDEX idx_players_team_active_position
        (team_id, is_active, position),

    ADD INDEX idx_players_season_rookie
        (draft_id, is_rookie, team_id),

    ADD INDEX idx_players_draft_status
        (draft_id, is_drafted, draft_status),

    ADD INDEX idx_players_draft_order
        (draft_id, draft_order),

    ADD INDEX idx_players_contract
        (team_id, contract_years, is_active),

    ADD INDEX idx_players_contract_expiry
        (contract_expires_at, is_active),

    ADD INDEX idx_players_free_agent
        (team_id, is_active, is_restricted_fa),

    ADD INDEX idx_players_injury
        (team_id, is_injured, injury_recovery_games),

    ADD INDEX idx_players_reserved
        (team_id, is_reserved, is_active),

    ADD INDEX idx_players_role_position
        (role, position),

    ADD INDEX idx_players_overall
        (overall_rating),

    ADD INDEX idx_players_potential
        (potential_rating);


/* ============================================================
   GAME QUARTER BREAKDOWN
   ============================================================ */

ALTER TABLE game_quarter_breakdown

    ADD INDEX idx_gqb_game
        (game_id),

    ADD INDEX idx_gqb_season_game
        (season_id, game_id),

    ADD INDEX idx_gqb_team_game
        (team_id, game_id),

    ADD INDEX idx_gqb_season_team
        (season_id, team_id);


/* ============================================================
   PLAYER PER QUARTER STATS
   ============================================================ */

ALTER TABLE player_per_quarter_stats

    ADD INDEX idx_ppqs_game
        (game_id),

    ADD INDEX idx_ppqs_player_game
        (player_id, game_id),

    ADD INDEX idx_ppqs_player_season
        (player_id, season_id),

    ADD INDEX idx_ppqs_team_game
        (team_id, game_id),

    ADD INDEX idx_ppqs_season_team
        (season_id, team_id),

    ADD INDEX idx_ppqs_season_player
        (season_id, player_id),

    ADD INDEX idx_ppqs_game_team
        (game_id, team_id),

    ADD INDEX idx_ppqs_season_playoff
        (season_id, is_playoff),

    ADD INDEX idx_ppqs_quarter
        (game_id, quarter);


/* ============================================================
   PLAYER SEASON PLAYOFF STATS
   ============================================================ */

ALTER TABLE player_season_playoff_stats

    ADD INDEX idx_psps_season_player
        (season_id, player_id),

    ADD INDEX idx_psps_player_season
        (player_id, season_id),

    ADD INDEX idx_psps_team_season
        (team_id, season_id),

    ADD INDEX idx_psps_season_team_role
        (season_id, team_id, role),

    ADD INDEX idx_psps_season_eff
        (season_id, eff),

    ADD INDEX idx_psps_season_games
        (season_id, total_games_played);


/* ============================================================
   PLAYER GAME STATS
   ============================================================ */

ALTER TABLE player_game_stats

    ADD INDEX idx_pgs_game
        (game_id),

    ADD INDEX idx_pgs_player_game
        (player_id, game_id),

    ADD INDEX idx_pgs_player_season
        (player_id, season_id),

    ADD INDEX idx_pgs_team_game
        (team_id, game_id),

    ADD INDEX idx_pgs_season_team
        (season_id, team_id),

    ADD INDEX idx_pgs_season_player
        (season_id, player_id),

    ADD INDEX idx_pgs_game_team
        (game_id, team_id),

    ADD INDEX idx_pgs_season_playoff
        (season_id, is_playoff),

    ADD INDEX idx_pgs_season_reserved
        (season_id, is_reserved),

    ADD INDEX idx_pgs_game_player
        (game_id, player_id);


/* ============================================================
   PLAYOFF SERIES
   ============================================================ */

ALTER TABLE playoff_series

    ADD INDEX idx_ps_season
        (season_id),

    ADD INDEX idx_ps_season_round
        (season_id, round),

    ADD INDEX idx_ps_season_round_status
        (season_id, round, status),

    ADD INDEX idx_ps_season_conference_round
        (season_id, conference_id, round),

    ADD INDEX idx_ps_home_team
        (home_team_id),

    ADD INDEX idx_ps_away_team
        (away_team_id),

    ADD INDEX idx_ps_home_away
        (home_team_id, away_team_id),

    ADD INDEX idx_ps_winner
        (winner_team_id),

    ADD INDEX idx_ps_loser
        (loser_team_id),

    ADD INDEX idx_ps_status
        (status);


/* ============================================================
   SCHEDULES
   ============================================================ */

ALTER TABLE schedules

    ADD INDEX idx_schedules_game
        (game_id),

    ADD INDEX idx_schedules_season
        (season_id),

    ADD INDEX idx_schedules_season_status
        (season_id, status),

    ADD INDEX idx_schedules_season_round
        (season_id, round),

    ADD INDEX idx_schedules_season_round_status
        (season_id, round, status),

    ADD INDEX idx_schedules_series
        (series_id),

    ADD INDEX idx_schedules_series_status
        (series_id, status),

    ADD INDEX idx_schedules_series_game
        (series_id, game_number),

    ADD INDEX idx_schedules_home
        (home_id),

    ADD INDEX idx_schedules_away
        (away_id),

    ADD INDEX idx_schedules_home_away
        (home_id, away_id),

    ADD INDEX idx_schedules_winner
        (winner_id),

    ADD INDEX idx_schedules_conference_round
        (season_id, conference_id, round);


/* ============================================================
   SEASONS
   ============================================================ */

ALTER TABLE seasons

    ADD INDEX idx_seasons_status
        (status),

    ADD INDEX idx_seasons_league
        (league_id),

    ADD INDEX idx_seasons_league_status
        (league_id, status),

    ADD INDEX idx_seasons_type
        (type),

    ADD INDEX idx_seasons_match_type
        (match_type),

    ADD INDEX idx_seasons_playoff_type
        (playoff_type),

    ADD INDEX idx_seasons_playoffs
        (start_playoffs),

    ADD INDEX idx_seasons_champion
        (champion_id),

    ADD INDEX idx_seasons_final_winner
        (finals_winner_id);


/* ============================================================
   TEAM SEASON INFO
   ============================================================ */

ALTER TABLE team_season_info

    ADD INDEX idx_tsi_season
        (season_id),

    ADD INDEX idx_tsi_team
        (team_id),

    ADD INDEX idx_tsi_team_season
        (team_id, season_id),

    ADD INDEX idx_tsi_season_team
        (season_id, team_id),

    ADD INDEX idx_tsi_conference
        (season_id, conference_id),

    ADD INDEX idx_tsi_season_conference
        (season_id, conference_id, team_id),

    ADD INDEX idx_tsi_playoff_qualified
        (season_id, is_playoff_qualified),

    ADD INDEX idx_tsi_defending_champion
        (season_id, is_defending_champion);


/* ============================================================
   TRANSACTIONS
   ============================================================ */

ALTER TABLE transactions

    ADD INDEX idx_transactions_player
        (player_id),

    ADD INDEX idx_transactions_season
        (season_id),

    ADD INDEX idx_transactions_season_status
        (season_id, status),

    ADD INDEX idx_transactions_player_season
        (player_id, season_id),

    ADD INDEX idx_transactions_from_team
        (from_team_id),

    ADD INDEX idx_transactions_to_team
        (to_team_id),

    ADD INDEX idx_transactions_team_season
        (from_team_id, season_id),

    ADD INDEX idx_transactions_to_team_season
        (to_team_id, season_id),

    ADD INDEX idx_transactions_created
        (created_at),

    ADD INDEX idx_transactions_season_created
        (season_id, created_at);


/* ============================================================
   DRAFTS
   ============================================================ */

ALTER TABLE drafts

    ADD INDEX idx_drafts_season
        (season_id),

    ADD INDEX idx_drafts_season_round
        (season_id, round),

    ADD INDEX idx_drafts_season_pick
        (season_id, round, pick_number),

    ADD INDEX idx_drafts_team
        (team_id),

    ADD INDEX idx_drafts_original_team
        (original_team_id),

    ADD INDEX idx_drafts_player
        (player_id),

    ADD INDEX idx_drafts_pick_right
        (draft_pick_right_id),

    ADD INDEX idx_drafts_season_player
        (season_id, player_id),

    ADD INDEX idx_drafts_season_team
        (season_id, team_id),

    ADD INDEX idx_drafts_draft_status
        (season_id, draft_status),

    ADD INDEX idx_drafts_signed
        (season_id, signed),

    ADD INDEX idx_drafts_decision_maker
        (decision_maker_type);


/* ============================================================
   DRAFT PICK RIGHTS
   ============================================================ */

ALTER TABLE draft_pick_rights

    ADD INDEX idx_dpr_season
        (season_id),

    ADD INDEX idx_dpr_season_round
        (season_id, round),

    ADD INDEX idx_dpr_season_round_pick
        (season_id, round, pick_number),

    ADD INDEX idx_dpr_original_team
        (original_team_id),

    ADD INDEX idx_dpr_current_owner
        (current_owner_id),

    ADD INDEX idx_dpr_owner_season
        (current_owner_id, season_id),

    ADD INDEX idx_dpr_owner_season_round
        (current_owner_id, season_id, round),

    ADD INDEX idx_dpr_traded
        (is_traded),

    ADD INDEX idx_dpr_used
        (is_used),

    ADD INDEX idx_dpr_trade_proposal
        (trade_proposal_id),

    ADD INDEX idx_dpr_available
        (current_owner_id, season_id, is_used),

    ADD INDEX idx_dpr_tradeable
        (current_owner_id, season_id, is_used, is_traded);


/* ============================================================
   CONFERENCES
   ============================================================ */

ALTER TABLE conferences

    ADD INDEX idx_conferences_league
        (league_id),

    ADD INDEX idx_conferences_name
        (name);


/* ============================================================
   COACHES
   ============================================================ */

ALTER TABLE coaches

    ADD INDEX idx_coaches_team
        (team_id),

    ADD INDEX idx_coaches_team_active
        (team_id, is_active),

    ADD INDEX idx_coaches_active
        (is_active),

    ADD INDEX idx_coaches_player
        (player_id),

    ADD INDEX idx_coaches_contract
        (contract_years, is_active),

    ADD INDEX idx_coaches_contract_expiry
        (contract_expires_at, is_active),

    ADD INDEX idx_coaches_coaching_style
        (coaching_style),

    ADD INDEX idx_coaches_iq
        (coach_iq);


/* ============================================================
   DONE
   ============================================================ */

ANALYZE TABLE
    players,
    game_quarter_breakdown,
    player_per_quarter_stats,
    player_season_playoff_stats,
    player_game_stats,
    playoff_series,
    schedules,
    seasons,
    team_season_info,
    transactions,
    drafts,
    draft_pick_rights,
    conferences,
    coaches;