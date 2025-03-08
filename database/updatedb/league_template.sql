-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2025 at 09:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `liga2`
--

-- --------------------------------------------------------

--
-- Table structure for table `all_time_top_stats`
--

CREATE TABLE `all_time_top_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stat_category` varchar(50) NOT NULL,
  `player_id` int(11) NOT NULL,
  `player_name` varchar(100) NOT NULL,
  `game_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `opponent_id` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  `stat_value` int(11) NOT NULL,
  `recorded_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conferences`
--

CREATE TABLE `conferences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `league_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `draft_player_statistics`
-- (See below for the actual view)
--
CREATE TABLE `draft_player_statistics` (
`draft_id` bigint(20) unsigned
,`total_players` bigint(21)
,`active_players_with_team` decimal(22,0)
,`active_players` decimal(22,0)
,`active_percentage_with_team` decimal(28,2)
,`active_percentage` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `finals_mvp_with_stats`
-- (See below for the actual view)
--
CREATE TABLE `finals_mvp_with_stats` (
`player_id` bigint(20) unsigned
,`player_name` varchar(255)
,`is_active` tinyint(1)
,`player_role` varchar(100)
,`current_team_names` mediumtext
,`mvp_winning_team_names` mediumtext
,`total_games` int(11)
,`total_games_played` int(11)
,`avg_minutes_per_game` decimal(5,2)
,`avg_points_per_game` decimal(5,2)
,`avg_rebounds_per_game` decimal(5,2)
,`avg_assists_per_game` decimal(5,2)
,`avg_steals_per_game` decimal(5,2)
,`avg_blocks_per_game` decimal(5,2)
,`avg_turnovers_per_game` decimal(5,2)
,`avg_fouls_per_game` decimal(5,2)
,`total_points` int(11)
,`total_rebounds` int(11)
,`total_assists` int(11)
,`total_steals` int(11)
,`total_blocks` int(11)
,`total_turnovers` int(11)
,`total_fouls` int(11)
,`stats_created_at` timestamp
,`stats_updated_at` timestamp
,`awards_won` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `generational_players_view`
-- (See below for the actual view)
--
CREATE TABLE `generational_players_view` (
`name` varchar(255)
,`role` varchar(100)
,`draft_status` varchar(255)
,`team_name` varchar(255)
,`overall_rating` decimal(5,2)
,`player_championships_won` int(11)
,`player_total_playoff_appearances` int(11)
,`seasons_played_in_playoffs` int(11)
,`big_4_appearances` int(11)
,`finals_appearances` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `head_to_head`
--

CREATE TABLE `head_to_head` (
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `opponent_id` bigint(20) UNSIGNED NOT NULL,
  `wins` int(11) NOT NULL DEFAULT 0,
  `losses` int(11) NOT NULL DEFAULT 0,
  `draws` int(11) NOT NULL DEFAULT 0,
  `points_for` int(11) NOT NULL DEFAULT 0,
  `points_against` int(11) NOT NULL DEFAULT 0,
  `win_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `injured_players_view`
-- (See below for the actual view)
--
CREATE TABLE `injured_players_view` (
`injury_id` bigint(20) unsigned
,`player_id` bigint(20) unsigned
,`overall_rating` decimal(5,2)
,`game_id` bigint(20) unsigned
,`team_id` bigint(20) unsigned
,`season_id` bigint(20) unsigned
,`player_name` varchar(255)
,`role` varchar(100)
,`current_team_name` varchar(255)
,`team_when_injured` varchar(255)
,`injury_type` varchar(255)
,`recovery_games` int(11)
,`injury_recovery_games` int(11)
,`status` varchar(9)
);

-- --------------------------------------------------------

--
-- Table structure for table `injury_histories`
--

CREATE TABLE `injury_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `injury_type` varchar(255) NOT NULL,
  `recovery_games` int(11) NOT NULL,
  `performance_impact` decimal(3,2) NOT NULL,
  `injury_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `recovery_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leagues`
--

CREATE TABLE `leagues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_conference` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(23, '2014_10_12_000000_create_users_table', 1),
(24, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(25, '2019_08_19_000000_create_failed_jobs_table', 1),
(26, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(27, '2024_03_25_104443_create_seasons_table', 1),
(28, '2024_08_15_112322_create_player_ratings_table', 1),
(29, '2024_11_28_153521_create_trade_logs_table', 1),
(30, '2025_03_08_073834_create_streaks_table', 1),
(31, 'create_conferences_table', 1),
(32, 'create_leagues', 1),
(33, 'create_player_game_stats', 1),
(34, 'create_players', 1),
(35, 'create_schedules_table', 1),
(36, 'create_teams', 1),
(37, 'injury_history', 1),
(38, 'player_ratings', 1),
(39, 'player_season_stats', 1),
(40, 'playoff_appearance', 1),
(41, 'season_awards', 1),
(42, 'single_stats_records', 1),
(43, 'trade_proposals', 1),
(44, 'update_h1h2h', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contract_years` int(11) NOT NULL DEFAULT 0,
  `hardship_contract` int(11) NOT NULL DEFAULT 0,
  `contract_expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_rookie` tinyint(1) NOT NULL DEFAULT 0,
  `age` int(11) NOT NULL,
  `retirement_age` int(11) NOT NULL DEFAULT 35,
  `position` varchar(10) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `shooting_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `two_point_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `three_point_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `free_throw_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `defense_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `passing_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `rebounding_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `athleticism_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `basketball_iq_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `strength_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `stamina_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `clutch_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `leadership_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `work_ethic_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overall_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `type` varchar(50) DEFAULT NULL,
  `draft_id` bigint(20) UNSIGNED DEFAULT NULL,
  `draft_order` int(11) DEFAULT NULL,
  `drafted_team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_drafted` tinyint(1) NOT NULL DEFAULT 0,
  `draft_status` varchar(255) DEFAULT NULL,
  `injury_prone_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_injured` tinyint(1) NOT NULL DEFAULT 0,
  `injury_type` varchar(255) DEFAULT NULL,
  `fatigue` decimal(5,2) NOT NULL DEFAULT 0.00,
  `injury_history` text DEFAULT NULL,
  `injury_recovery_games` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `players_multiple_teams`
-- (See below for the actual view)
--
CREATE TABLE `players_multiple_teams` (
`player_id` bigint(20) unsigned
,`season_id` bigint(20) unsigned
,`player_name` varchar(255)
,`role` varchar(100)
,`overall_rating` decimal(5,2)
,`teams_played` mediumtext
,`total_teams` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `player_distribution_by_country`
-- (See below for the actual view)
--
CREATE TABLE `player_distribution_by_country` (
`country` varchar(100)
,`total_players` bigint(21)
,`total_rookies` decimal(22,0)
,`total_injured` decimal(22,0)
,`total_retired` decimal(22,0)
,`injured_percentage` decimal(28,2)
,`rookie_percentage` decimal(28,2)
,`retired_percentage` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `player_experience_status`
-- (See below for the actual view)
--
CREATE TABLE `player_experience_status` (
`player_id` bigint(20) unsigned
,`player_name` varchar(255)
,`age` int(11)
,`role` varchar(100)
,`is_active` tinyint(1)
,`overall_status` varchar(7)
,`experience_status` varchar(9)
,`total_points` decimal(54,0)
,`total_rebounds` decimal(54,0)
,`total_assists` decimal(54,0)
,`total_steals` decimal(54,0)
,`total_blocks` decimal(54,0)
,`total_turnovers` decimal(54,0)
,`total_fouls` decimal(54,0)
,`seasons_played` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `player_game_stats`
--

CREATE TABLE `player_game_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `game_id` varchar(255) NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `team_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `minutes` double(8,2) NOT NULL DEFAULT 0.00,
  `points` int(11) NOT NULL DEFAULT 0,
  `rebounds` int(11) NOT NULL DEFAULT 0,
  `assists` int(11) NOT NULL DEFAULT 0,
  `steals` int(11) NOT NULL DEFAULT 0,
  `blocks` int(11) NOT NULL DEFAULT 0,
  `turnovers` int(11) NOT NULL DEFAULT 0,
  `fouls` int(11) NOT NULL DEFAULT 0,
  `field_goal_attempts` int(11) NOT NULL DEFAULT 0,
  `field_goals_made` int(11) NOT NULL DEFAULT 0,
  `three_point_attempts` int(11) NOT NULL DEFAULT 0,
  `three_pointers_made` int(11) NOT NULL DEFAULT 0,
  `free_throw_attempts` int(11) NOT NULL DEFAULT 0,
  `free_throws_made` int(11) NOT NULL DEFAULT 0,
  `two_point_attempts` int(11) NOT NULL DEFAULT 0,
  `two_pointers_made` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `per` float GENERATED ALWAYS AS ((`points` + `rebounds` + `assists` + `steals` + `blocks` - (`field_goal_attempts` - `field_goals_made`) - `turnovers`) / nullif(`minutes`,0)) STORED,
  `ts_percent` float GENERATED ALWAYS AS (`points` / nullif(2 * (`field_goal_attempts` + 0.44 * `free_throw_attempts`),0)) STORED,
  `eff` float GENERATED ALWAYS AS (`points` + `rebounds` + `assists` + `steals` + `blocks` - (`field_goal_attempts` + `free_throw_attempts` + `turnovers`)) STORED,
  `field_goal_percentage` float GENERATED ALWAYS AS (case when `field_goal_attempts` = 0 then 0 else `field_goals_made` / `field_goal_attempts` * 100 end) STORED,
  `three_point_percentage` float GENERATED ALWAYS AS (case when `three_point_attempts` = 0 then 0 else `three_pointers_made` / `three_point_attempts` * 100 end) STORED,
  `free_throw_percentage` float GENERATED ALWAYS AS (case when `free_throw_attempts` = 0 then 0 else `free_throws_made` / `free_throw_attempts` * 100 end) STORED,
  `two_point_percentage` float GENERATED ALWAYS AS (case when `two_point_attempts` = 0 then 0 else `two_pointers_made` / `two_point_attempts` * 100 end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_playoff_appearances`
--

CREATE TABLE `player_playoff_appearances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `play_ins_elims_round_1_appearances` int(11) NOT NULL DEFAULT 0,
  `play_ins_elims_round_2_appearances` int(11) NOT NULL DEFAULT 0,
  `play_ins_finals_appearances` int(11) NOT NULL DEFAULT 0,
  `round_of_32_appearances` int(11) NOT NULL DEFAULT 0,
  `round_of_16_appearances` int(11) NOT NULL DEFAULT 0,
  `quarter_finals_appearances` int(11) NOT NULL DEFAULT 0,
  `semi_finals_appearances` int(11) NOT NULL DEFAULT 0,
  `interconference_semi_finals_appearances` int(11) NOT NULL DEFAULT 0,
  `finals_appearances` int(11) NOT NULL DEFAULT 0,
  `total_playoff_appearances` int(11) NOT NULL DEFAULT 0,
  `seasons_played_in_playoffs` int(11) NOT NULL DEFAULT 0,
  `total_seasons_played` int(11) NOT NULL DEFAULT 0,
  `championships_won` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `player_playoff_appearances_view`
-- (See below for the actual view)
--
CREATE TABLE `player_playoff_appearances_view` (
`player_id` bigint(20) unsigned
,`player_name` varchar(255)
,`teams_played_for_in_playoffs` mediumtext
,`team_acronyms` mediumtext
,`current_team_name` varchar(255)
,`active_status` tinyint(1)
,`play_ins_elims_round_1_appearances` bigint(21)
,`play_ins_elims_round_2_appearances` bigint(21)
,`play_ins_finals_appearances` bigint(21)
,`round_of_32_appearances` bigint(21)
,`round_of_16_appearances` bigint(21)
,`quarter_finals_appearances` bigint(21)
,`semi_finals_appearances` bigint(21)
,`interconference_semi_finals_appearances` bigint(21)
,`finals_appearances` bigint(21)
,`total_playoff_appearances` bigint(21)
,`seasons_played_in_playoffs` bigint(21)
,`total_seasons_played` bigint(21)
,`championships_won` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `player_playoff_status`
-- (See below for the actual view)
--
CREATE TABLE `player_playoff_status` (
`player_id` bigint(20) unsigned
,`player_name` varchar(255)
,`round_of_16_appearances` bigint(21)
,`quarter_finals_appearances` bigint(21)
,`semi_finals_appearances` bigint(21)
,`interconference_semi_finals_appearances` bigint(21)
,`finals_appearances` bigint(21)
,`finals_mvp_count` bigint(21)
,`seasons` mediumtext
);

-- --------------------------------------------------------

--
-- Table structure for table `player_ratings`
--

CREATE TABLE `player_ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_season_stats`
--

CREATE TABLE `player_season_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) NOT NULL,
  `avg_minutes_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_points_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_rebounds_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_assists_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_steals_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_blocks_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_turnovers_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_fouls_per_game` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_field_goals_made` int(11) NOT NULL DEFAULT 0,
  `total_field_goal_attempts` int(11) NOT NULL DEFAULT 0,
  `total_two_pointers_made` int(11) NOT NULL DEFAULT 0,
  `total_two_point_attempts` int(11) NOT NULL DEFAULT 0,
  `total_three_pointers_made` int(11) NOT NULL DEFAULT 0,
  `total_three_point_attempts` int(11) NOT NULL DEFAULT 0,
  `total_free_throws_made` int(11) NOT NULL DEFAULT 0,
  `total_free_throw_attempts` int(11) NOT NULL DEFAULT 0,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `total_rebounds` int(11) NOT NULL DEFAULT 0,
  `total_assists` int(11) NOT NULL DEFAULT 0,
  `total_steals` int(11) NOT NULL DEFAULT 0,
  `total_blocks` int(11) NOT NULL DEFAULT 0,
  `total_turnovers` int(11) NOT NULL DEFAULT 0,
  `total_fouls` int(11) NOT NULL DEFAULT 0,
  `total_minutes_played` int(11) NOT NULL DEFAULT 0,
  `total_games_played` int(11) NOT NULL DEFAULT 0,
  `total_games` int(11) NOT NULL DEFAULT 0,
  `bpg_game_leader` int(11) NOT NULL DEFAULT 0,
  `points_game_leader` int(11) NOT NULL DEFAULT 0,
  `rebounds_game_leader` int(11) NOT NULL DEFAULT 0,
  `assists_game_leader` int(11) NOT NULL DEFAULT 0,
  `steals_game_leader` int(11) NOT NULL DEFAULT 0,
  `blocks_game_leader` int(11) NOT NULL DEFAULT 0,
  `per` decimal(5,3) GENERATED ALWAYS AS (case when `total_minutes_played` = 0 then 0 else (`total_points` + `total_rebounds` + `total_assists` + `total_steals` + `total_blocks` - (`total_field_goal_attempts` - `total_field_goals_made`) - `total_turnovers`) / `total_minutes_played` end) STORED,
  `ts_percent` decimal(5,3) GENERATED ALWAYS AS (case when `total_field_goal_attempts` + 0.44 * `total_free_throw_attempts` = 0 then 0 else `total_points` / (2 * (`total_field_goal_attempts` + 0.44 * `total_free_throw_attempts`)) end) STORED,
  `eff` decimal(6,3) GENERATED ALWAYS AS (case when `total_field_goal_attempts` + `total_free_throw_attempts` + `total_turnovers` = 0 then 0 else `total_points` + `total_rebounds` + `total_assists` + `total_steals` + `total_blocks` - (`total_field_goal_attempts` + `total_free_throw_attempts` + `total_turnovers`) end) STORED,
  `field_goal_percentage` decimal(5,2) GENERATED ALWAYS AS (case when `total_field_goal_attempts` = 0 then 0 else `total_field_goals_made` / `total_field_goal_attempts` * 100 end) STORED,
  `two_point_percentage` decimal(5,2) GENERATED ALWAYS AS (case when `total_two_point_attempts` = 0 then 0 else `total_two_pointers_made` / `total_two_point_attempts` * 100 end) STORED,
  `three_point_percentage` decimal(5,2) GENERATED ALWAYS AS (case when `total_three_point_attempts` = 0 then 0 else `total_three_pointers_made` / `total_three_point_attempts` * 100 end) STORED,
  `free_throw_percentage` decimal(5,2) GENERATED ALWAYS AS (case when `total_free_throw_attempts` = 0 then 0 else `total_free_throws_made` / `total_free_throw_attempts` * 100 end) STORED,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `player_season_status`
-- (See below for the actual view)
--
CREATE TABLE `player_season_status` (
`player_id` bigint(20) unsigned
,`player_name` varchar(255)
,`age` int(11)
,`role` varchar(100)
,`is_active` tinyint(1)
,`overall_status` varchar(7)
,`experience_status` varchar(9)
,`team_id` bigint(20) unsigned
,`total_points` decimal(32,0)
,`total_rebounds` decimal(32,0)
,`total_assists` decimal(32,0)
,`total_steals` decimal(32,0)
,`total_blocks` decimal(32,0)
,`total_turnovers` decimal(32,0)
,`total_fouls` decimal(32,0)
,`games_played` bigint(21)
,`average_points_per_game` decimal(14,4)
,`average_rebounds_per_game` decimal(14,4)
,`average_assists_per_game` decimal(14,4)
,`average_steals_per_game` decimal(14,4)
,`average_blocks_per_game` decimal(14,4)
,`average_turnovers_per_game` decimal(14,4)
,`average_fouls_per_game` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `game_id` varchar(255) NOT NULL,
  `round` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  `conference_id` int(11) NOT NULL,
  `home_id` int(11) NOT NULL,
  `home_score` int(11) DEFAULT NULL,
  `away_id` int(11) NOT NULL,
  `away_score` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `schedule_view`
-- (See below for the actual view)
--
CREATE TABLE `schedule_view` (
`id` bigint(20) unsigned
,`game_id` varchar(255)
,`round` int(11)
,`season_id` int(11)
,`conference_id` int(11)
,`home_id` int(11)
,`home_score` int(11)
,`away_id` int(11)
,`away_score` int(11)
,`status` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`home_team_name` varchar(255)
,`away_team_name` varchar(255)
,`season_name` varchar(255)
,`league_name` varchar(255)
,`league_type` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `seasons`
--

CREATE TABLE `seasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `league_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `match_type` int(11) NOT NULL,
  `start_playoffs` int(11) NOT NULL,
  `is_conference` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `finals_mvp_id` int(11) DEFAULT NULL,
  `finals_mvp` varchar(255) DEFAULT NULL,
  `finals_winner_id` int(11) DEFAULT NULL,
  `finals_winner_name` varchar(255) DEFAULT NULL,
  `finals_winner_score` int(11) DEFAULT NULL,
  `finals_loser_id` int(11) DEFAULT NULL,
  `finals_loser_name` varchar(255) DEFAULT NULL,
  `finals_loser_score` int(11) DEFAULT NULL,
  `west_champion_id` int(11) DEFAULT NULL,
  `west_champion_name` varchar(255) DEFAULT NULL,
  `east_champion_id` int(11) DEFAULT NULL,
  `east_champion_name` varchar(255) DEFAULT NULL,
  `north_champion_id` int(11) DEFAULT NULL,
  `north_champion_name` varchar(255) DEFAULT NULL,
  `south_champion_id` int(11) DEFAULT NULL,
  `south_champion_name` varchar(255) DEFAULT NULL,
  `champion_id` int(11) DEFAULT NULL,
  `champion_name` varchar(255) DEFAULT NULL,
  `weakest_id` int(11) DEFAULT NULL,
  `weakest_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `season_awards`
--

CREATE TABLE `season_awards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `award_name` varchar(255) NOT NULL,
  `award_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `standings_view`
-- (See below for the actual view)
--
CREATE TABLE `standings_view` (
`team_id` bigint(20) unsigned
,`team_name` varchar(255)
,`team_acronym` varchar(10)
,`conference_id` int(11)
,`conference_name` varchar(255)
,`wins` decimal(22,0)
,`losses` decimal(22,0)
,`total_home_score` decimal(32,0)
,`total_away_score` decimal(32,0)
,`home_ppg` decimal(35,2)
,`away_ppg` decimal(35,2)
,`score_difference` decimal(33,0)
,`season_id` int(11)
,`conference_rank` bigint(21)
,`overall_rank` bigint(21)
,`playoff_appearances` bigint(21)
,`finals_appearances` bigint(21)
,`conference_finals_appearances` bigint(21)
,`conference_championships` bigint(21)
,`championships` bigint(21)
,`streak_status` varchar(22)
,`overall_1_rank` decimal(22,0)
,`conference_1_rank` decimal(22,0)
,`is_grandslam` int(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `star_players_count_per_team_all_seasons`
-- (See below for the actual view)
--
CREATE TABLE `star_players_count_per_team_all_seasons` (
`team_id` bigint(20) unsigned
,`team_name` varchar(255)
,`star_players_on_roster` bigint(21)
,`star_players_on_roster_list` mediumtext
,`star_players_produced` bigint(21)
,`star_players_produced_list` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `star_players_per_team`
-- (See below for the actual view)
--
CREATE TABLE `star_players_per_team` (
`team_id` bigint(20) unsigned
,`team_name` varchar(255)
,`player_id` bigint(20) unsigned
,`player_name` varchar(255)
,`season_id` bigint(20) unsigned
,`avg_points_per_game` decimal(5,2)
,`avg_rebounds_per_game` decimal(5,2)
,`avg_assists_per_game` decimal(5,2)
,`avg_steals_per_game` decimal(5,2)
,`avg_blocks_per_game` decimal(5,2)
,`per` decimal(5,3)
);

-- --------------------------------------------------------

--
-- Table structure for table `streak`
--

CREATE TABLE `streak` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `team_id` int(11) NOT NULL,
  `best_winning_streak` int(10) UNSIGNED NOT NULL,
  `best_losing_streak` int(10) UNSIGNED NOT NULL,
  `best_winning_streak_start_id` int(11) NOT NULL,
  `best_winning_streak_end_id` int(11) NOT NULL,
  `best_losing_streak_start_id` int(11) NOT NULL,
  `best_losing_streak_end_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `streak_view`
-- (See below for the actual view)
--
CREATE TABLE `streak_view` (
`id` bigint(20) unsigned
,`team_id` int(11)
,`team_name` varchar(255)
,`best_winning_streak` int(10) unsigned
,`best_losing_streak` int(10) unsigned
,`best_winning_streak_start_id` int(11)
,`best_winning_streak_end_id` int(11)
,`best_losing_streak_start_id` int(11)
,`best_losing_streak_end_id` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`last_winning_opponent` varchar(255)
,`last_losing_opponent` varchar(255)
,`winning_streak_season` varchar(255)
,`losing_streak_season` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `acronym` varchar(10) NOT NULL,
  `primary_color` varchar(8) NOT NULL,
  `secondary_color` varchar(8) NOT NULL,
  `league_id` int(11) NOT NULL,
  `conference_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `created_at`, `updated_at`) VALUES
(1, 'Lions', 'LIO', '121883', '5F12ED', 1, 1, NULL, NULL),
(2, 'Tigers', 'TIG', 'B4F3F7', 'FADB59', 1, 1, NULL, NULL),
(3, 'Bears', 'BEA', 'A3A79F', '5D8D6C', 1, 1, NULL, NULL),
(4, 'Wolves', 'WOL', '0EA6F5', 'C70FAA', 1, 1, NULL, NULL),
(5, 'Eagles', 'EAG', '517ED2', '56FB1A', 1, 1, NULL, NULL),
(6, 'Falcons', 'FAL', 'EC062E', '2FD182', 1, 1, NULL, NULL),
(7, 'Hawks', 'HAW', '298EB8', 'C4FD33', 1, 1, NULL, NULL),
(8, 'Panthers', 'PAN', '17F2CC', '840783', 1, 1, NULL, '2024-05-25 07:05:57'),
(9, 'Athletics', 'ATH', '19D26F', '7BA4E0', 1, 1, NULL, NULL),
(10, 'Vipers', 'VIP', '8B277A', 'B6BB6F', 1, 1, NULL, NULL),
(11, 'Jaguars', 'JAG', 'C324DC', '102F74', 1, 1, NULL, '2024-04-03 13:36:05'),
(12, 'Dolphins', 'DOL', '4E5B3F', 'C90FB1', 1, 1, NULL, NULL),
(13, 'Rockets', 'RCK', 'E7A0D1', '1FE699', 1, 1, NULL, '2024-04-03 13:32:25'),
(14, 'Braves', 'BRV', '1EA1F8', 'C36858', 1, 1, NULL, '2024-04-03 13:36:27'),
(15, 'Blazers', 'BLZ', '24A795', 'B349AD', 1, 1, NULL, '2024-04-03 13:31:53'),
(16, 'Kings', 'KIN', '3949BF', '6B0548', 1, 1, NULL, NULL),
(17, 'Titans', 'TIT', '095F4F', '88E18A', 1, 2, NULL, NULL),
(18, 'Spartans', 'SPA', 'E301A1', '7CECD4', 1, 2, NULL, NULL),
(19, 'Trojans', 'TRO', 'F37584', '9A38FB', 1, 2, NULL, NULL),
(20, 'Saints', 'SNT', '238D98', '04D461', 1, 2, NULL, '2024-08-04 00:15:59'),
(21, 'Aliens', 'ALN', '6EDACD', '3E5BFD', 1, 2, NULL, '2024-08-04 00:16:38'),
(22, 'Leopards', 'LEO', 'E47150', 'FA591F', 1, 2, NULL, '2024-04-04 10:02:18'),
(23, 'Sabertooths', 'SAB', 'B255CA', 'A8268E', 1, 2, NULL, '2024-04-19 15:52:34'),
(24, 'Spiders', 'SPD', 'A2DC08', '24A66C', 1, 2, NULL, '2024-07-06 04:38:05'),
(25, 'Vikings', 'VIK', '15B69A', 'B7F0F2', 1, 2, NULL, NULL),
(26, 'Crows', 'CRW', '9BDB7F', '9DBEB9', 1, 2, NULL, '2024-04-03 13:08:24'),
(27, 'Royals', 'RYL', '6A55B4', 'E5309A', 1, 2, NULL, '2024-04-03 13:34:47'),
(28, 'Thunders', 'THN', 'CAA202', 'FC1326', 1, 2, NULL, '2024-04-03 13:35:00'),
(29, 'Warriors', 'WAR', '1B970F', '99DC06', 1, 2, NULL, '2024-05-25 07:05:47'),
(30, 'Hellhounds', 'HH', '401E9C', '7EBD10', 1, 2, NULL, '2024-04-03 13:30:33'),
(31, 'Red Fox', 'RF', '20A265', '60E0C4', 1, 2, NULL, '2024-04-03 13:33:29'),
(32, 'Cougars', 'CGR', '6F8577', 'E84CC4', 1, 2, NULL, '2024-04-03 13:31:05'),
(33, 'Waves', 'WAV', '6894BE', '6048E4', 1, 3, NULL, '2024-04-03 13:35:23'),
(34, 'Predators', 'PRD', 'EB682E', '96AEBA', 1, 3, NULL, '2024-04-03 13:36:56'),
(35, 'Trilogy', 'TRI', '88DEDF', 'EEEA57', 1, 3, NULL, '2024-04-03 13:31:38'),
(36, 'Monarchs', 'MON', 'F1A926', 'C4CE32', 1, 3, NULL, '2024-08-04 00:17:44'),
(37, 'Krakens', 'KRK', '155ACC', '4DB89D', 1, 3, NULL, '2024-04-03 13:12:27'),
(38, 'Jets', 'JET', '2F88DF', '87F1FC', 1, 3, NULL, NULL),
(39, 'Northern Stars', 'NS', '9A325F', '492678', 1, 3, NULL, '2024-08-04 00:18:10'),
(40, 'Ninjas', 'NIN', '2C4E99', '85802C', 1, 3, NULL, '2024-04-03 13:13:24'),
(41, 'Dragons', 'DRA', '954019', '00FF92', 1, 3, NULL, NULL),
(42, 'Phoenix', 'PHO', 'F7BDEF', '3B5918', 1, 3, NULL, NULL),
(43, 'Sharks', 'SHA', 'AD988A', '70E379', 1, 3, NULL, '2024-08-04 00:18:49'),
(44, 'Giants', 'GNT', '1EA31E', '04B07D', 1, 3, NULL, '2024-04-03 13:12:44'),
(45, 'Fire', 'FRE', '43E2F0', '8F2962', 1, 3, NULL, '2024-08-04 00:19:21'),
(46, 'Patriots', 'PAT', 'FBB736', '3E51EB', 1, 3, NULL, '2024-08-04 00:19:45'),
(47, 'Aces', 'ACE', '8051DE', '603C57', 1, 3, NULL, '2024-07-09 11:07:39'),
(48, 'Monsters', 'MNT', '8D42F1', '8037B0', 1, 3, NULL, '2024-07-22 13:06:34'),
(49, 'Pirates', 'PIR', '9C4956', 'EB0A23', 1, 4, NULL, NULL),
(50, 'Scorpions', 'SCR', 'F0DBCE', 'ED4BBB', 1, 4, NULL, '2024-04-03 13:16:24'),
(51, 'Enemies', 'ENM', 'C8F4C9', '5D994D', 1, 4, NULL, '2024-04-03 13:23:33'),
(52, 'Reapers', 'RPR', '655052', '38EB92', 1, 4, NULL, '2024-04-03 13:18:44'),
(53, 'Raiders', 'RAI', '7748BE', 'EA72D0', 1, 4, NULL, NULL),
(54, 'Whales', 'WH', '97430E', '430904', 1, 4, NULL, '2024-04-03 13:17:10'),
(55, 'Poseidons', 'POS', 'B04893', '38B57D', 1, 4, NULL, '2024-04-03 13:17:26'),
(56, 'Cyclones', 'CYC', '229BEC', 'A6C3F3', 1, 4, NULL, '2024-08-04 00:20:57'),
(57, 'Force', 'FRC', '391253', '2217F8', 1, 4, NULL, '2024-04-03 13:18:19'),
(58, 'Astronauts', 'AST', '2F487D', '10D336', 1, 4, NULL, '2024-04-03 13:17:50'),
(59, 'Demons', 'DMN', 'C1B875', 'A979F2', 1, 4, NULL, '2024-04-03 13:19:49'),
(60, 'Devils', 'DVL', '8CFFBF', '2FD545', 1, 4, NULL, '2024-07-09 11:08:05'),
(61, 'Bulldogs', 'BD', 'A177A6', '12C948', 1, 4, NULL, '2024-04-03 13:20:50'),
(62, 'Hornets', 'HRN', '966B11', 'D36853', 1, 4, NULL, '2024-04-03 13:22:01'),
(63, 'Rebels', 'RBL', 'F9D71D', '155FBA', 1, 4, NULL, '2024-08-04 00:21:56'),
(64, 'Owls', 'OWL', '35EEC9', '61E96A', 1, 4, NULL, '2024-04-03 13:22:40'),
(65, 'Knights', 'KNI', '80EED1', '57D8CD', 1, 1, '2024-08-31 16:00:04', '2024-08-31 16:00:04'),
(66, 'Strikers', 'STR', '9A7276', '202237', 1, 1, '2024-08-31 16:00:17', '2024-08-31 16:00:17'),
(67, 'Sealions', 'SEA', 'AA9550', 'C6D3C2', 1, 3, '2024-08-31 16:00:31', '2024-08-31 16:01:17'),
(68, 'Dreamers', 'DRM', '0496FA', 'BE911F', 1, 1, '2024-08-31 16:00:43', '2024-08-31 16:00:43'),
(69, 'Bolts', 'BLT', '716ECC', '9504B7', 1, 1, '2024-08-31 16:02:06', '2024-08-31 16:02:06'),
(70, 'Sonics', 'SNC', 'F6B3B9', '782B44', 1, 2, '2024-08-31 16:02:27', '2024-08-31 16:02:27'),
(71, 'Octopus', 'OCT', '217AE0', 'D6063E', 1, 2, '2024-08-31 16:03:40', '2024-08-31 16:03:40'),
(72, 'Ghosts', 'GHO', '717181', '356379', 1, 2, '2024-08-31 16:03:49', '2024-08-31 16:03:49'),
(73, 'Blue Frogs', 'BF', 'EB82F9', '1C6D68', 1, 4, '2024-08-31 16:04:42', '2024-08-31 16:04:42'),
(74, 'Ravens', 'RVN', 'A1F5A2', '40B2E8', 1, 4, '2024-08-31 16:04:53', '2024-08-31 16:04:53'),
(75, 'Electric Eels', 'EEE', 'C5F2A7', '385EDA', 1, 4, '2024-08-31 16:05:03', '2024-08-31 16:05:03'),
(76, 'Peacemakers', 'PM', 'C6EC93', 'CAC5EC', 1, 4, '2024-08-31 16:05:14', '2024-08-31 16:05:14'),
(77, 'Tamaraws', 'TAM', '4956A1', '6BD906', 1, 3, '2024-08-31 16:05:31', '2024-08-31 16:06:34'),
(78, 'Earthquakes', 'EQ', 'F01EFA', '50F238', 1, 3, '2024-08-31 16:06:54', '2024-08-31 16:06:54'),
(79, 'Hurricanes', 'HUR', '4D8B9C', 'EBCE2E', 1, 3, '2024-08-31 16:07:10', '2024-08-31 16:07:10'),
(80, 'Mad Ants', 'MA', 'E85CB3', '26E2C7', 1, 2, '2024-08-31 16:09:29', '2024-08-31 16:09:29');

-- --------------------------------------------------------

--
-- Stand-in structure for view `teams_with_hardship_contracts`
-- (See below for the actual view)
--
CREATE TABLE `teams_with_hardship_contracts` (
`team_id` bigint(20) unsigned
,`team_name` varchar(255)
,`hardship_players_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `trade_logs`
--

CREATE TABLE `trade_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `team_from_id` bigint(20) UNSIGNED NOT NULL,
  `team_to_id` bigint(20) UNSIGNED NOT NULL,
  `player_id` bigint(20) UNSIGNED NOT NULL,
  `player_name` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `trade_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade_proposals`
--

CREATE TABLE `trade_proposals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `season_id` bigint(20) UNSIGNED NOT NULL,
  `team_to_id` bigint(20) UNSIGNED NOT NULL,
  `team_from_id` bigint(20) UNSIGNED NOT NULL,
  `player_from_id` bigint(20) UNSIGNED NOT NULL,
  `player_to_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `draft_player_statistics`
--
DROP TABLE IF EXISTS `draft_player_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `draft_player_statistics`  AS SELECT `players`.`draft_id` AS `draft_id`, count(0) AS `total_players`, sum(case when `players`.`is_active` = 1 and `players`.`team_id` <> 0 then 1 else 0 end) AS `active_players_with_team`, sum(case when `players`.`is_active` = 1 then 1 else 0 end) AS `active_players`, round(sum(case when `players`.`is_active` = 1 and `players`.`team_id` <> 0 then 1 else 0 end) * 100.0 / count(0),2) AS `active_percentage_with_team`, round(sum(case when `players`.`is_active` = 1 then 1 else 0 end) * 100.0 / count(0),2) AS `active_percentage` FROM `players` WHERE `players`.`draft_id` is not null GROUP BY `players`.`draft_id` ORDER BY `players`.`draft_id` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `finals_mvp_with_stats`
--
DROP TABLE IF EXISTS `finals_mvp_with_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `finals_mvp_with_stats`  AS SELECT `p`.`id` AS `player_id`, `p`.`name` AS `player_name`, `p`.`is_active` AS `is_active`, `p`.`role` AS `player_role`, group_concat(distinct concat(`t1`.`name`,' (',`s`.`name`,')') order by `s`.`name` ASC separator ',') AS `current_team_names`, group_concat(distinct concat(`t2`.`name`,' (',`s`.`name`,')') order by `s`.`name` ASC separator ',') AS `mvp_winning_team_names`, max(`ps`.`total_games`) AS `total_games`, max(`ps`.`total_games_played`) AS `total_games_played`, max(`ps`.`avg_minutes_per_game`) AS `avg_minutes_per_game`, max(`ps`.`avg_points_per_game`) AS `avg_points_per_game`, max(`ps`.`avg_rebounds_per_game`) AS `avg_rebounds_per_game`, max(`ps`.`avg_assists_per_game`) AS `avg_assists_per_game`, max(`ps`.`avg_steals_per_game`) AS `avg_steals_per_game`, max(`ps`.`avg_blocks_per_game`) AS `avg_blocks_per_game`, max(`ps`.`avg_turnovers_per_game`) AS `avg_turnovers_per_game`, max(`ps`.`avg_fouls_per_game`) AS `avg_fouls_per_game`, max(`ps`.`total_points`) AS `total_points`, max(`ps`.`total_rebounds`) AS `total_rebounds`, max(`ps`.`total_assists`) AS `total_assists`, max(`ps`.`total_steals`) AS `total_steals`, max(`ps`.`total_blocks`) AS `total_blocks`, max(`ps`.`total_turnovers`) AS `total_turnovers`, max(`ps`.`total_fouls`) AS `total_fouls`, max(`ps`.`created_at`) AS `stats_created_at`, max(`ps`.`updated_at`) AS `stats_updated_at`, (select group_concat(concat(`sa`.`award_name`,' (',`season`.`name`,')') order by `season`.`name` ASC separator ',') from (`season_awards` `sa` join `seasons` `season` on(`sa`.`season_id` = `season`.`id`)) where `sa`.`player_id` = `p`.`id`) AS `awards_won` FROM ((((`seasons` `s` left join `players` `p` on(`s`.`finals_mvp_id` = `p`.`id`)) left join `player_season_stats` `ps` on(`ps`.`player_id` = `p`.`id`)) left join `teams` `t1` on(`p`.`team_id` = `t1`.`id`)) left join `teams` `t2` on(`s`.`finals_winner_id` = `t2`.`id`)) WHERE `s`.`finals_mvp_id` is not null GROUP BY `p`.`id`, `p`.`name`, `p`.`role`, `p`.`is_active` ORDER BY `p`.`name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `generational_players_view`
--
DROP TABLE IF EXISTS `generational_players_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `generational_players_view`  AS SELECT `p`.`name` AS `name`, `p`.`role` AS `role`, `p`.`draft_status` AS `draft_status`, `t`.`name` AS `team_name`, `p`.`overall_rating` AS `overall_rating`, `ppa`.`championships_won` AS `player_championships_won`, `ppa`.`total_playoff_appearances` AS `player_total_playoff_appearances`, `ppa`.`seasons_played_in_playoffs` AS `seasons_played_in_playoffs`, `ppa`.`interconference_semi_finals_appearances` AS `big_4_appearances`, `ppa`.`finals_appearances` AS `finals_appearances` FROM ((`players` `p` left join `teams` `t` on(`p`.`team_id` = `t`.`id`)) left join `player_playoff_appearances` `ppa` on(`p`.`id` = `ppa`.`player_id`)) WHERE `p`.`type` = 'generational' ;

-- --------------------------------------------------------

--
-- Structure for view `injured_players_view`
--
DROP TABLE IF EXISTS `injured_players_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `injured_players_view`  AS SELECT `i`.`id` AS `injury_id`, `p`.`id` AS `player_id`, `p`.`overall_rating` AS `overall_rating`, `i`.`game_id` AS `game_id`, `i`.`team_id` AS `team_id`, `i`.`season_id` AS `season_id`, `p`.`name` AS `player_name`, `p`.`role` AS `role`, coalesce(`ct`.`name`,'Free Agent') AS `current_team_name`, coalesce(`t`.`name`,'Free Agent') AS `team_when_injured`, `i`.`injury_type` AS `injury_type`, `i`.`recovery_games` AS `recovery_games`, `p`.`injury_recovery_games` AS `injury_recovery_games`, CASE WHEN `p`.`injury_recovery_games` = 0 THEN 'Recovered' ELSE 'Injured' END AS `status` FROM (((`injury_histories` `i` join `players` `p` on(`i`.`player_id` = `p`.`id`)) left join `teams` `t` on(`i`.`team_id` = `t`.`id`)) left join `teams` `ct` on(`ct`.`id` = `p`.`team_id`)) ORDER BY `p`.`injury_recovery_games` DESC, `i`.`id` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `players_multiple_teams`
--
DROP TABLE IF EXISTS `players_multiple_teams`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `players_multiple_teams`  AS SELECT `pg`.`player_id` AS `player_id`, `pg`.`season_id` AS `season_id`, `p`.`name` AS `player_name`, `p`.`role` AS `role`, `p`.`overall_rating` AS `overall_rating`, group_concat(distinct `t`.`name` order by `t`.`name` ASC separator ',') AS `teams_played`, count(distinct `pg`.`team_id`) AS `total_teams` FROM ((`player_game_stats` `pg` join `players` `p` on(`pg`.`player_id` = `p`.`id`)) join `teams` `t` on(`pg`.`team_id` = `t`.`id`)) GROUP BY `pg`.`player_id`, `pg`.`season_id` HAVING `total_teams` > 1 ORDER BY `pg`.`season_id` DESC, `p`.`name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `player_distribution_by_country`
--
DROP TABLE IF EXISTS `player_distribution_by_country`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `player_distribution_by_country`  AS SELECT `players`.`country` AS `country`, count(`players`.`id`) AS `total_players`, sum(case when `players`.`is_rookie` = 1 then 1 else 0 end) AS `total_rookies`, sum(case when `players`.`is_injured` = 1 then 1 else 0 end) AS `total_injured`, sum(case when `players`.`is_active` = 0 then 1 else 0 end) AS `total_retired`, round(sum(case when `players`.`is_injured` = 1 then 1 else 0 end) * 100.0 / count(`players`.`id`),2) AS `injured_percentage`, round(sum(case when `players`.`is_rookie` = 1 then 1 else 0 end) * 100.0 / count(`players`.`id`),2) AS `rookie_percentage`, round(sum(case when `players`.`is_active` = 0 then 1 else 0 end) * 100.0 / count(`players`.`id`),2) AS `retired_percentage` FROM `players` GROUP BY `players`.`country` ORDER BY count(`players`.`id`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `player_experience_status`
--
DROP TABLE IF EXISTS `player_experience_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `player_experience_status`  AS SELECT `p`.`id` AS `player_id`, `p`.`name` AS `player_name`, `p`.`age` AS `age`, `p`.`role` AS `role`, `p`.`is_active` AS `is_active`, if(`p`.`is_active` = 0 and `p`.`retirement_age` <= `p`.`age`,'retired','active') AS `overall_status`, CASE WHEN count(distinct `ps`.`season_id`) > 5 THEN 'veteran' WHEN count(distinct `ps`.`season_id`) > 1 THEN 'sophomore' ELSE 'rookie' END AS `experience_status`, sum(`ps`.`total_points`) AS `total_points`, sum(`ps`.`total_rebounds`) AS `total_rebounds`, sum(`ps`.`total_assists`) AS `total_assists`, sum(`ps`.`total_steals`) AS `total_steals`, sum(`ps`.`total_blocks`) AS `total_blocks`, sum(`ps`.`total_turnovers`) AS `total_turnovers`, sum(`ps`.`total_fouls`) AS `total_fouls`, count(distinct `ps`.`season_id`) AS `seasons_played` FROM (`players` `p` left join (select `player_game_stats`.`player_id` AS `player_id`,`player_game_stats`.`season_id` AS `season_id`,sum(`player_game_stats`.`points`) AS `total_points`,sum(`player_game_stats`.`rebounds`) AS `total_rebounds`,sum(`player_game_stats`.`assists`) AS `total_assists`,sum(`player_game_stats`.`steals`) AS `total_steals`,sum(`player_game_stats`.`blocks`) AS `total_blocks`,sum(`player_game_stats`.`turnovers`) AS `total_turnovers`,sum(`player_game_stats`.`fouls`) AS `total_fouls` from `player_game_stats` group by `player_game_stats`.`player_id`,`player_game_stats`.`season_id`) `ps` on(`p`.`id` = `ps`.`player_id`)) GROUP BY `p`.`id`, `p`.`name`, `p`.`age`, `p`.`role`, `p`.`is_active`, `p`.`retirement_age` ;

-- --------------------------------------------------------

--
-- Structure for view `player_playoff_appearances_view`
--
DROP TABLE IF EXISTS `player_playoff_appearances_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `player_playoff_appearances_view`  AS SELECT `p`.`id` AS `player_id`, `p`.`name` AS `player_name`, coalesce(group_concat(distinct `t`.`name` order by `t`.`name` ASC separator ', '),'Free Agent') AS `teams_played_for_in_playoffs`, coalesce(group_concat(distinct `t`.`acronym` order by `t`.`acronym` ASC separator ', '),'N/A') AS `team_acronyms`, coalesce(max(`t2`.`name`),'Free Agent') AS `current_team_name`, `p`.`is_active` AS `active_status`, count(distinct case when `s`.`round` = 'play_ins_elims_round_1' then `s`.`game_id` end) AS `play_ins_elims_round_1_appearances`, count(distinct case when `s`.`round` = 'play_ins_elims_round_2' then `s`.`game_id` end) AS `play_ins_elims_round_2_appearances`, count(distinct case when `s`.`round` = 'play_ins_finals' then `s`.`game_id` end) AS `play_ins_finals_appearances`, count(distinct case when `s`.`round` = 'round_of_32' then `s`.`game_id` end) AS `round_of_32_appearances`, count(distinct case when `s`.`round` = 'round_of_16' then `s`.`game_id` end) AS `round_of_16_appearances`, count(distinct case when `s`.`round` = 'quarter_finals' then `s`.`game_id` end) AS `quarter_finals_appearances`, count(distinct case when `s`.`round` = 'semi_finals' then `s`.`game_id` end) AS `semi_finals_appearances`, count(distinct case when `s`.`round` = 'interconference_semi_finals' then `s`.`game_id` end) AS `interconference_semi_finals_appearances`, count(distinct case when `s`.`round` = 'finals' then `s`.`game_id` end) AS `finals_appearances`, count(distinct `s`.`game_id`) AS `total_playoff_appearances`, count(distinct case when `s`.`round` in ('play_ins_elims_round_1','play_ins_elims_round_2','play_ins_finals','round_of_32','round_of_16','quarter_finals','semi_finals','interconference_semi_finals','finals') then `s`.`season_id` end) AS `seasons_played_in_playoffs`, count(distinct `all_s`.`season_id`) AS `total_seasons_played`, count(distinct case when `s`.`round` = 'finals' and (`pg`.`team_id` = `s`.`home_id` and `s`.`home_score` > `s`.`away_score` or `pg`.`team_id` = `s`.`away_id` and `s`.`away_score` > `s`.`home_score`) then `s`.`game_id` end) AS `championships_won` FROM (((((`players` `p` left join `player_game_stats` `pg` on(`p`.`id` = `pg`.`player_id`)) left join `schedules` `s` on(`pg`.`game_id` = `s`.`game_id`)) left join `teams` `t` on(`pg`.`team_id` = `t`.`id`)) left join `teams` `t2` on(`p`.`team_id` = `t2`.`id`)) left join (select distinct `player_game_stats`.`player_id` AS `player_id`,`player_game_stats`.`season_id` AS `season_id` from `player_game_stats`) `all_s` on(`all_s`.`player_id` = `p`.`id`)) WHERE `s`.`round` in ('play_ins_elims_round_1','play_ins_elims_round_2','play_ins_finals','round_of_32','round_of_16','quarter_finals','semi_finals','interconference_semi_finals','finals') GROUP BY `p`.`id`, `p`.`name`, `p`.`is_active` ORDER BY count(distinct `s`.`game_id`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `player_playoff_status`
--
DROP TABLE IF EXISTS `player_playoff_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `player_playoff_status`  AS SELECT `p`.`id` AS `player_id`, `p`.`name` AS `player_name`, count(case when `s`.`round` = 'round_of_16' then 1 end) AS `round_of_16_appearances`, count(case when `s`.`round` = 'quarter_finals' then 1 end) AS `quarter_finals_appearances`, count(case when `s`.`round` = 'semi_finals' then 1 end) AS `semi_finals_appearances`, count(case when `s`.`round` = 'interconference_semi_finals' then 1 end) AS `interconference_semi_finals_appearances`, count(case when `s`.`round` = 'finals' then 1 end) AS `finals_appearances`, count(distinct case when `se`.`finals_mvp_id` = `p`.`id` then `se`.`id` end) AS `finals_mvp_count`, group_concat(distinct `se`.`name` order by `se`.`name` ASC separator ', ') AS `seasons` FROM (((`players` `p` left join `player_game_stats` `pg` on(`p`.`id` = `pg`.`player_id`)) left join `schedules` `s` on(`pg`.`game_id` = `s`.`game_id`)) left join `seasons` `se` on(`s`.`season_id` = `se`.`id`)) GROUP BY `p`.`id`, `p`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `player_season_status`
--
DROP TABLE IF EXISTS `player_season_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `player_season_status`  AS SELECT `ps`.`player_id` AS `player_id`, `p`.`name` AS `player_name`, `p`.`age` AS `age`, `p`.`role` AS `role`, `p`.`is_active` AS `is_active`, if(`p`.`is_active` = 0 and `p`.`retirement_age` <= `p`.`age`,'retired','active') AS `overall_status`, if(count(`ps`.`season_id`) > 5,'veteran',if(count(`ps`.`season_id`) > 1,'sophomore','rookie')) AS `experience_status`, `ps`.`team_id` AS `team_id`, sum(`ps`.`points`) AS `total_points`, sum(`ps`.`rebounds`) AS `total_rebounds`, sum(`ps`.`assists`) AS `total_assists`, sum(`ps`.`steals`) AS `total_steals`, sum(`ps`.`blocks`) AS `total_blocks`, sum(`ps`.`turnovers`) AS `total_turnovers`, sum(`ps`.`fouls`) AS `total_fouls`, count(`ps`.`season_id`) AS `games_played`, avg(`ps`.`points`) AS `average_points_per_game`, avg(`ps`.`rebounds`) AS `average_rebounds_per_game`, avg(`ps`.`assists`) AS `average_assists_per_game`, avg(`ps`.`steals`) AS `average_steals_per_game`, avg(`ps`.`blocks`) AS `average_blocks_per_game`, avg(`ps`.`turnovers`) AS `average_turnovers_per_game`, avg(`ps`.`fouls`) AS `average_fouls_per_game` FROM (`players` `p` left join `player_game_stats` `ps` on(`p`.`id` = `ps`.`player_id`)) GROUP BY `p`.`id`, `ps`.`team_id`, `ps`.`player_id` ;

-- --------------------------------------------------------

--
-- Structure for view `schedule_view`
--
DROP TABLE IF EXISTS `schedule_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `schedule_view`  AS SELECT `s`.`id` AS `id`, `s`.`game_id` AS `game_id`, `s`.`round` AS `round`, `s`.`season_id` AS `season_id`, `s`.`conference_id` AS `conference_id`, `s`.`home_id` AS `home_id`, `s`.`home_score` AS `home_score`, `s`.`away_id` AS `away_id`, `s`.`away_score` AS `away_score`, `s`.`status` AS `status`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at`, `t_home`.`name` AS `home_team_name`, `t_away`.`name` AS `away_team_name`, `se`.`name` AS `season_name`, `l`.`name` AS `league_name`, `se`.`type` AS `league_type` FROM ((((`schedules` `s` join `teams` `t_home` on(`s`.`home_id` = `t_home`.`id`)) join `teams` `t_away` on(`s`.`away_id` = `t_away`.`id`)) join `seasons` `se` on(`s`.`season_id` = `se`.`id`)) join `leagues` `l` on(`se`.`league_id` = `l`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `standings_view`
--
DROP TABLE IF EXISTS `standings_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `standings_view`  AS WITH team_games AS (SELECT `teams`.`id` AS `team_id`, `teams`.`name` AS `team_name`, `teams`.`acronym` AS `team_acronym`, `teams`.`conference_id` AS `conference_id`, `conferences`.`name` AS `conference_name`, `schedules`.`id` AS `game_id`, `schedules`.`season_id` AS `season_id`, `schedules`.`round` AS `round`, CASE WHEN `schedules`.`home_id` = `teams`.`id` AND `schedules`.`home_score` > `schedules`.`away_score` THEN 'W' WHEN `schedules`.`away_id` = `teams`.`id` AND `schedules`.`away_score` > `schedules`.`home_score` THEN 'W' WHEN `schedules`.`home_id` = `teams`.`id` AND `schedules`.`home_score` < `schedules`.`away_score` THEN 'L' WHEN `schedules`.`away_id` = `teams`.`id` AND `schedules`.`away_score` < `schedules`.`home_score` THEN 'L' ELSE NULL END AS `game_result` FROM ((`teams` left join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) left join `conferences` on(`teams`.`conference_id` = `conferences`.`id`)) WHERE `schedules`.`round` not in ('play_ins_elims_round_1','play_ins_elims_round_2','play_ins_finals','round_of_32','round_of_16','quarter_finals','semi_finals','interconference_semi_finals','finals')), streaks AS (SELECT `streak_groups`.`team_id` AS `team_id`, `streak_groups`.`season_id` AS `season_id`, `streak_groups`.`game_result` AS `game_result`, `streak_groups`.`round` AS `round`, count(0) AS `streak_length` FROM (select `team_games`.`team_id` AS `team_id`,`team_games`.`season_id` AS `season_id`,`team_games`.`game_result` AS `game_result`,`team_games`.`round` AS `round`,row_number() over ( partition by `team_games`.`team_id`,`team_games`.`season_id` order by `team_games`.`game_id`) - row_number() over ( partition by `team_games`.`team_id`,`team_games`.`season_id`,`team_games`.`game_result` order by `team_games`.`game_id`) AS `streak_id` from `team_games`) AS `streak_groups` WHERE `streak_groups`.`game_result` is not null GROUP BY `streak_groups`.`team_id`, `streak_groups`.`season_id`, `streak_groups`.`game_result`, `streak_groups`.`streak_id`, `streak_groups`.`round`), latest_streak AS (SELECT `ranked_streaks`.`team_id` AS `team_id`, `ranked_streaks`.`season_id` AS `season_id`, `ranked_streaks`.`game_result` AS `game_result`, `ranked_streaks`.`streak_length` AS `streak_length` FROM (select `streaks`.`team_id` AS `team_id`,`streaks`.`season_id` AS `season_id`,`streaks`.`game_result` AS `game_result`,`streaks`.`streak_length` AS `streak_length`,row_number() over ( partition by `streaks`.`team_id`,`streaks`.`season_id` order by `streaks`.`streak_length` desc,`streaks`.`round` desc) AS `rn` from `streaks`) AS `ranked_streaks` WHERE `ranked_streaks`.`rn` = 1), team_rankings AS (SELECT `teams`.`id` AS `team_id`, `teams`.`name` AS `team_name`, `teams`.`acronym` AS `team_acronym`, `teams`.`conference_id` AS `conference_id`, `conferences`.`name` AS `conference_name`, coalesce(sum(case when `schedules`.`home_score` > `schedules`.`away_score` and `schedules`.`home_id` = `teams`.`id` then 1 when `schedules`.`away_score` > `schedules`.`home_score` and `schedules`.`away_id` = `teams`.`id` then 1 else 0 end),0) AS `wins`, coalesce(sum(case when `schedules`.`home_score` < `schedules`.`away_score` and `schedules`.`home_id` = `teams`.`id` then 1 when `schedules`.`away_score` < `schedules`.`home_score` and `schedules`.`away_id` = `teams`.`id` then 1 else 0 end),0) AS `losses`, coalesce(sum(case when `schedules`.`home_id` = `teams`.`id` then `schedules`.`home_score` else 0 end),0) AS `total_home_score`, coalesce(sum(case when `schedules`.`away_id` = `teams`.`id` then `schedules`.`away_score` else 0 end),0) AS `total_away_score`, round(coalesce(sum(case when `schedules`.`home_id` = `teams`.`id` then `schedules`.`home_score` else 0 end),0) / nullif(count(case when `schedules`.`home_id` = `teams`.`id` then 1 end),0),2) AS `home_ppg`, round(coalesce(sum(case when `schedules`.`away_id` = `teams`.`id` then `schedules`.`away_score` else 0 end),0) / nullif(count(case when `schedules`.`away_id` = `teams`.`id` then 1 end),0),2) AS `away_ppg`, abs(coalesce(sum(case when `schedules`.`home_id` = `teams`.`id` then `schedules`.`home_score` - `schedules`.`away_score` when `schedules`.`away_id` = `teams`.`id` then `schedules`.`away_score` - `schedules`.`home_score` else 0 end),0)) AS `score_difference`, `schedules`.`season_id` AS `season_id` FROM ((`teams` left join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) left join `conferences` on(`teams`.`conference_id` = `conferences`.`id`)) WHERE `schedules`.`round` not in ('play_ins_elims_round_1','play_ins_elims_round_2','play_ins_finals','round_of_32','round_of_16','quarter_finals','semi_finals','interconference_semi_finals','finals') GROUP BY `teams`.`id`, `teams`.`name`, `teams`.`acronym`, `teams`.`conference_id`, `conferences`.`name`, `schedules`.`season_id`), ranked_team_rankings AS (SELECT `team_rankings`.`team_id` AS `team_id`, `team_rankings`.`team_name` AS `team_name`, `team_rankings`.`team_acronym` AS `team_acronym`, `team_rankings`.`conference_id` AS `conference_id`, `team_rankings`.`conference_name` AS `conference_name`, `team_rankings`.`wins` AS `wins`, `team_rankings`.`losses` AS `losses`, `team_rankings`.`total_home_score` AS `total_home_score`, `team_rankings`.`total_away_score` AS `total_away_score`, `team_rankings`.`home_ppg` AS `home_ppg`, `team_rankings`.`away_ppg` AS `away_ppg`, `team_rankings`.`score_difference` AS `score_difference`, `team_rankings`.`season_id` AS `season_id`, rank() over ( partition by `team_rankings`.`season_id`,`team_rankings`.`conference_id` order by `team_rankings`.`wins` desc,`team_rankings`.`score_difference` desc,`team_rankings`.`home_ppg` desc,`team_rankings`.`away_ppg` desc) AS `conference_rank`, rank() over ( partition by `team_rankings`.`season_id` order by `team_rankings`.`wins` desc,`team_rankings`.`score_difference` desc) AS `overall_rank` FROM `team_rankings`), rank_counts AS (SELECT `ranked_team_rankings`.`team_id` AS `team_id`, sum(case when `ranked_team_rankings`.`overall_rank` = 1 then 1 else 0 end) AS `overall_rank`, sum(case when `ranked_team_rankings`.`conference_rank` = 1 then 1 else 0 end) AS `conference_rank` FROM `ranked_team_rankings` GROUP BY `ranked_team_rankings`.`team_id`), playoff_appearances AS (SELECT `teams`.`id` AS `team_id`, count(distinct `schedules`.`season_id`) AS `playoff_appearances` FROM (`teams` join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) WHERE `schedules`.`round` in ('play_ins_elims_round_1','play_ins_elims_round_2','play_ins_finals','round_of_32','round_of_16','quarter_finals','semi_finals','interconference_semi_finals','finals') GROUP BY `teams`.`id`), finals_appearances AS (SELECT `teams`.`id` AS `team_id`, count(distinct `schedules`.`season_id`) AS `finals_appearances` FROM (`teams` join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) WHERE `schedules`.`round` = 'finals' GROUP BY `teams`.`id`), conference_finals_appearances AS (SELECT `teams`.`id` AS `team_id`, count(distinct `schedules`.`season_id`) AS `conference_finals_appearance` FROM (`teams` join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) WHERE `schedules`.`round` = 'semi_finals' GROUP BY `teams`.`id`), championships AS (SELECT `teams`.`id` AS `team_id`, count(distinct `schedules`.`season_id`) AS `championships` FROM (`teams` join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) WHERE `schedules`.`round` = 'finals' AND (`schedules`.`home_score` > `schedules`.`away_score` AND `schedules`.`home_id` = `teams`.`id` OR `schedules`.`away_score` > `schedules`.`home_score` AND `schedules`.`away_id` = `teams`.`id`) GROUP BY `teams`.`id`), conference_championships AS (SELECT `teams`.`id` AS `team_id`, count(distinct `schedules`.`season_id`) AS `championships` FROM (`teams` join `schedules` on(`teams`.`id` = `schedules`.`home_id` or `teams`.`id` = `schedules`.`away_id`)) WHERE `schedules`.`round` = 'semi_finals' AND (`schedules`.`home_score` > `schedules`.`away_score` AND `schedules`.`home_id` = `teams`.`id` OR `schedules`.`away_score` > `schedules`.`home_score` AND `schedules`.`away_id` = `teams`.`id`) GROUP BY `teams`.`id`)  SELECT `standings`.`team_id` AS `team_id`, `standings`.`team_name` AS `team_name`, `standings`.`team_acronym` AS `team_acronym`, `standings`.`conference_id` AS `conference_id`, `standings`.`conference_name` AS `conference_name`, `standings`.`wins` AS `wins`, `standings`.`losses` AS `losses`, `standings`.`total_home_score` AS `total_home_score`, `standings`.`total_away_score` AS `total_away_score`, `standings`.`home_ppg` AS `home_ppg`, `standings`.`away_ppg` AS `away_ppg`, `standings`.`score_difference` AS `score_difference`, `standings`.`season_id` AS `season_id`, `standings`.`conference_rank` AS `conference_rank`, `standings`.`overall_rank` AS `overall_rank`, coalesce(`playoff_appearances`.`playoff_appearances`,0) AS `playoff_appearances`, coalesce(`finals_appearances`.`finals_appearances`,0) AS `finals_appearances`, coalesce(`conference_finals_appearances`.`conference_finals_appearance`,0) AS `conference_finals_appearances`, coalesce(`conference_championships`.`championships`,0) AS `conference_championships`, coalesce(`championships`.`championships`,0) AS `championships`, CASE WHEN `latest_streak`.`game_result` = 'W' THEN concat('W',`latest_streak`.`streak_length`) WHEN `latest_streak`.`game_result` = 'L' THEN concat('L',`latest_streak`.`streak_length`) ELSE NULL END AS `streak_status`, coalesce(`rank_counts`.`overall_rank`,0) AS `overall_1_rank`, coalesce(`rank_counts`.`conference_rank`,0) AS `conference_1_rank`, CASE WHEN coalesce(`rank_counts`.`overall_rank`,0) = 1 AND coalesce(`rank_counts`.`conference_rank`,0) = 1 AND coalesce(`conference_championships`.`championships`,0) > 0 AND coalesce(`championships`.`championships`,0) > 0 THEN 1 ELSE 0 END AS `is_grandslam` FROM ((((((((select `ranked_team_rankings`.`team_id` AS `team_id`,`ranked_team_rankings`.`team_name` AS `team_name`,`ranked_team_rankings`.`team_acronym` AS `team_acronym`,`ranked_team_rankings`.`conference_id` AS `conference_id`,`ranked_team_rankings`.`conference_name` AS `conference_name`,`ranked_team_rankings`.`wins` AS `wins`,`ranked_team_rankings`.`losses` AS `losses`,`ranked_team_rankings`.`total_home_score` AS `total_home_score`,`ranked_team_rankings`.`total_away_score` AS `total_away_score`,`ranked_team_rankings`.`home_ppg` AS `home_ppg`,`ranked_team_rankings`.`away_ppg` AS `away_ppg`,`ranked_team_rankings`.`score_difference` AS `score_difference`,`ranked_team_rankings`.`season_id` AS `season_id`,`ranked_team_rankings`.`conference_rank` AS `conference_rank`,`ranked_team_rankings`.`overall_rank` AS `overall_rank` from `ranked_team_rankings`) `standings` left join `latest_streak` on(`standings`.`team_id` = `latest_streak`.`team_id` and `standings`.`season_id` = `latest_streak`.`season_id`)) left join `playoff_appearances` on(`standings`.`team_id` = `playoff_appearances`.`team_id`)) left join `finals_appearances` on(`standings`.`team_id` = `finals_appearances`.`team_id`)) left join `conference_championships` on(`standings`.`team_id` = `conference_championships`.`team_id`)) left join `conference_finals_appearances` on(`standings`.`team_id` = `conference_finals_appearances`.`team_id`)) left join `championships` on(`standings`.`team_id` = `championships`.`team_id`)) left join `rank_counts` on(`standings`.`team_id` = `rank_counts`.`team_id`)))  ;

-- --------------------------------------------------------

--
-- Structure for view `star_players_count_per_team_all_seasons`
--
DROP TABLE IF EXISTS `star_players_count_per_team_all_seasons`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `star_players_count_per_team_all_seasons`  AS SELECT `t`.`id` AS `team_id`, `t`.`name` AS `team_name`, count(distinct `ps`.`player_id`) AS `star_players_on_roster`, group_concat(distinct concat(`p`.`name`,' (',coalesce(`ct`.`acronym`,'FA'),')') order by `p`.`name` ASC separator ', ') AS `star_players_on_roster_list`, (select count(distinct `ps2`.`player_id`) from (`player_season_stats` `ps2` join `players` `p2` on(`ps2`.`player_id` = `p2`.`id`)) where `p2`.`drafted_team_id` = `t`.`id` and `ps2`.`role` = 'star player') AS `star_players_produced`, (select group_concat(distinct concat(`p3`.`name`,' (',coalesce(`ct2`.`acronym`,'FA'),')') order by `p3`.`name` ASC separator ', ') from ((`player_season_stats` `ps3` join `players` `p3` on(`ps3`.`player_id` = `p3`.`id`)) left join `teams` `ct2` on(`p3`.`team_id` = `ct2`.`id`)) where `p3`.`drafted_team_id` = `t`.`id` and `ps3`.`role` = 'star player') AS `star_players_produced_list` FROM (((`teams` `t` join `player_season_stats` `ps` on(`ps`.`team_id` = `t`.`id`)) join `players` `p` on(`ps`.`player_id` = `p`.`id`)) left join `teams` `ct` on(`p`.`team_id` = `ct`.`id`)) WHERE `ps`.`role` = 'star player' GROUP BY `t`.`id`, `t`.`name` ORDER BY (select count(distinct `ps2`.`player_id`) from (`player_season_stats` `ps2` join `players` `p2` on(`ps2`.`player_id` = `p2`.`id`)) where `p2`.`drafted_team_id` = `t`.`id` and `ps2`.`role` = 'star player') DESC, count(distinct `ps`.`player_id`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `star_players_per_team`
--
DROP TABLE IF EXISTS `star_players_per_team`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `star_players_per_team`  AS SELECT `t`.`id` AS `team_id`, `t`.`name` AS `team_name`, `p`.`id` AS `player_id`, `p`.`name` AS `player_name`, `ps`.`season_id` AS `season_id`, `ps`.`avg_points_per_game` AS `avg_points_per_game`, `ps`.`avg_rebounds_per_game` AS `avg_rebounds_per_game`, `ps`.`avg_assists_per_game` AS `avg_assists_per_game`, `ps`.`avg_steals_per_game` AS `avg_steals_per_game`, `ps`.`avg_blocks_per_game` AS `avg_blocks_per_game`, `ps`.`per` AS `per` FROM ((`player_season_stats` `ps` join `players` `p` on(`ps`.`player_id` = `p`.`id`)) join `teams` `t` on(`p`.`team_id` = `t`.`id`)) WHERE `ps`.`season_id` = (select max(`player_season_stats`.`season_id`) from `player_season_stats`) ORDER BY `ps`.`per` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `streak_view`
--
DROP TABLE IF EXISTS `streak_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `streak_view`  AS SELECT `s`.`id` AS `id`, `s`.`team_id` AS `team_id`, `t`.`name` AS `team_name`, `s`.`best_winning_streak` AS `best_winning_streak`, `s`.`best_losing_streak` AS `best_losing_streak`, `s`.`best_winning_streak_start_id` AS `best_winning_streak_start_id`, `s`.`best_winning_streak_end_id` AS `best_winning_streak_end_id`, `s`.`best_losing_streak_start_id` AS `best_losing_streak_start_id`, `s`.`best_losing_streak_end_id` AS `best_losing_streak_end_id`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at`, (select case when `sv`.`home_id` = `s`.`team_id` then `t2`.`name` else `t2`.`name` end from (`schedule_view` `sv` join `teams` `t2` on(`sv`.`home_id` = `t2`.`id` or `sv`.`away_id` = `t2`.`id`)) where `sv`.`id` = `s`.`best_winning_streak_end_id` limit 1) AS `last_winning_opponent`, (select case when `sv`.`home_id` = `s`.`team_id` then `t2`.`name` else `t2`.`name` end from (`schedule_view` `sv` join `teams` `t2` on(`sv`.`home_id` = `t2`.`id` or `sv`.`away_id` = `t2`.`id`)) where `sv`.`id` = `s`.`best_losing_streak_end_id` limit 1) AS `last_losing_opponent`, (select `se`.`name` from (`schedule_view` `sv` join `seasons` `se` on(`sv`.`season_id` = `se`.`id`)) where `sv`.`id` = `s`.`best_winning_streak_end_id` limit 1) AS `winning_streak_season`, (select `se`.`name` from (`schedule_view` `sv` join `seasons` `se` on(`sv`.`season_id` = `se`.`id`)) where `sv`.`id` = `s`.`best_losing_streak_end_id` limit 1) AS `losing_streak_season` FROM (`streak` `s` join `teams` `t` on(`s`.`team_id` = `t`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `teams_with_hardship_contracts`
--
DROP TABLE IF EXISTS `teams_with_hardship_contracts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `teams_with_hardship_contracts`  AS SELECT `teams`.`id` AS `team_id`, `teams`.`name` AS `team_name`, count(`players`.`id`) AS `hardship_players_count` FROM (`players` join `teams` on(`players`.`team_id` = `teams`.`id`)) WHERE `players`.`hardship_contract` > 0 GROUP BY `teams`.`id`, `teams`.`name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `all_time_top_stats`
--
ALTER TABLE `all_time_top_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conferences`
--
ALTER TABLE `conferences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `head_to_head`
--
ALTER TABLE `head_to_head`
  ADD PRIMARY KEY (`team_id`,`opponent_id`),
  ADD KEY `head_to_head_opponent_id_foreign` (`opponent_id`);

--
-- Indexes for table `injury_histories`
--
ALTER TABLE `injury_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `injury_histories_player_id_foreign` (`player_id`);

--
-- Indexes for table `leagues`
--
ALTER TABLE `leagues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_game_stats`
--
ALTER TABLE `player_game_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_playoff_appearances`
--
ALTER TABLE `player_playoff_appearances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_playoff_appearances_player_id_foreign` (`player_id`);

--
-- Indexes for table `player_ratings`
--
ALTER TABLE `player_ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `player_season_stats`
--
ALTER TABLE `player_season_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seasons`
--
ALTER TABLE `seasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `season_awards`
--
ALTER TABLE `season_awards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `season_awards_player_id_foreign` (`player_id`),
  ADD KEY `season_awards_season_id_foreign` (`season_id`);

--
-- Indexes for table `streak`
--
ALTER TABLE `streak`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trade_logs`
--
ALTER TABLE `trade_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trade_proposals`
--
ALTER TABLE `trade_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trade_proposals_season_id_foreign` (`season_id`),
  ADD KEY `trade_proposals_team_to_id_foreign` (`team_to_id`),
  ADD KEY `trade_proposals_team_from_id_foreign` (`team_from_id`),
  ADD KEY `trade_proposals_player_from_id_foreign` (`player_from_id`),
  ADD KEY `trade_proposals_player_to_id_foreign` (`player_to_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `all_time_top_stats`
--
ALTER TABLE `all_time_top_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conferences`
--
ALTER TABLE `conferences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `injury_histories`
--
ALTER TABLE `injury_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leagues`
--
ALTER TABLE `leagues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_game_stats`
--
ALTER TABLE `player_game_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_playoff_appearances`
--
ALTER TABLE `player_playoff_appearances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_ratings`
--
ALTER TABLE `player_ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `player_season_stats`
--
ALTER TABLE `player_season_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seasons`
--
ALTER TABLE `seasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `season_awards`
--
ALTER TABLE `season_awards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `streak`
--
ALTER TABLE `streak`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `trade_logs`
--
ALTER TABLE `trade_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trade_proposals`
--
ALTER TABLE `trade_proposals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `head_to_head`
--
ALTER TABLE `head_to_head`
  ADD CONSTRAINT `head_to_head_opponent_id_foreign` FOREIGN KEY (`opponent_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `head_to_head_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `injury_histories`
--
ALTER TABLE `injury_histories`
  ADD CONSTRAINT `injury_histories_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `player_playoff_appearances`
--
ALTER TABLE `player_playoff_appearances`
  ADD CONSTRAINT `player_playoff_appearances_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `season_awards`
--
ALTER TABLE `season_awards`
  ADD CONSTRAINT `season_awards_player_id_foreign` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `season_awards_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`);

--
-- Constraints for table `trade_proposals`
--
ALTER TABLE `trade_proposals`
  ADD CONSTRAINT `trade_proposals_player_from_id_foreign` FOREIGN KEY (`player_from_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `trade_proposals_player_to_id_foreign` FOREIGN KEY (`player_to_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `trade_proposals_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  ADD CONSTRAINT `trade_proposals_team_from_id_foreign` FOREIGN KEY (`team_from_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `trade_proposals_team_to_id_foreign` FOREIGN KEY (`team_to_id`) REFERENCES `teams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
