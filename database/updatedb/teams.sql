-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2025 at 03:13 PM
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
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `acronym` varchar(10) NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `primary_color` varchar(8) NOT NULL,
  `secondary_color` varchar(8) NOT NULL,
  `league_id` int(11) NOT NULL,
  `conference_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `acronym`, `city`, `description`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `created_at`, `updated_at`) VALUES
(1, 'Lions', 'LIO', 'Quezon City', 'NCR predators roaring with Commonwealth pride', '0076B6', 'B0B7BC', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(2, 'Tigers', 'TIG', 'Manila', 'Capital wildcats with Intramuros strength', 'FB4F14', '000000', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(3, 'Bears', 'BEA', 'Makati', 'Financial jungle brutes with skyscraper grit', '0B162A', 'C83803', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(4, 'Wolves', 'WOL', 'Taguig', 'Bonifacio hunters with elite urban tactics', '0C2340', '236192', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(5, 'Eagles', 'EAG', 'Pasig', 'Ortigas raptors soaring above traffic chaos', '004C54', 'A5ACAF', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(6, 'Falcons', 'FAL', 'Caloocan', 'North Metro aerial attackers with steel resolve', 'A71930', '000000', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(7, 'Hawks', 'HAW', 'Parañaque', 'Southern sky hunters gliding past the bay breeze', 'E03A3E', 'C1D32F', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(8, 'Panthers', 'PAN', 'Mandaluyong', 'Urban jungle prowlers with central bite', '0085CA', '101820', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(9, 'Athletics', 'ATH', 'San Juan', 'Tiny city titans running with green-hearted hustle', '003831', 'EFB21E', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(10, 'Vipers', 'VIP', 'Pasay', 'Bayfront strikers with terminal velocity', '007A33', 'FF8200', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(11, 'Jaguars', 'JAG', 'Las Piñas', 'South Metro prowlers with bamboo toughness', '006778', 'D7A22A', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(12, 'Dolphins', 'DOL', 'Valenzuela', 'Industrial sea acrobats with steel nerve', '008E97', 'FC4C02', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(13, 'Rockets', 'RCK', 'Navotas', 'Fishing port launchers with harbor blast power', 'CE1141', 'C4CED4', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(14, 'Braves', 'BRV', 'Malabon', 'Northern warriors with flood-tested bravery', '13274F', 'BA0C2F', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(15, 'Blazers', 'BLZ', 'Marikina', 'Shoe capital torches sprinting with artisan fire', 'E03A3E', '000000', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(16, 'Kings', 'KIN', 'Muntinlupa', 'Southern monarchs guarding the lakeside throne', '5A2D81', '8C7B6D', 1, 1, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(17, 'Titans', 'TIT', 'Baguio', 'Highland giants crushing foes with pine-fresh dominance', '0C2340', '4B92DB', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(18, 'Spartans', 'SPA', 'Laoag', 'Ilocano warriors standing tall with windmill might', '18453B', 'B9B5B8', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(19, 'Trojans', 'TRO', 'Tarlac City', 'Central Luzon tacticians breaking lines with shield and speed', '990000', 'FFD100', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(20, 'Saints', 'SNT', 'Vigan', 'Heritage believers with cobblestone resilience', 'D3BC8D', '000000', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(21, 'Aliens', 'ALN', 'Olongapo', 'Subic spacewalkers beaming with naval discipline', '228b22', '1a1a1a', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(22, 'Leopards', 'LEO', 'Legazpi', 'Mayon hunters striking with volcanic elegance', '862633', 'F1BE48', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(23, 'Sabertooths', 'SAB', 'Lucena', 'Quezon predators slicing through coconut groves', '041E42', 'C8102E', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(24, 'Spiders', 'SPD', 'Cabanatuan', 'Nueva Ecija web-weavers with farmer precision', '003366', 'CC0000', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(25, 'Vikings', 'VIK', 'Pampanga', 'Pampanga raiders with culinary carnage', '4F2683', 'FFC62F', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(26, 'Crows', 'CRW', 'Batangas City', 'Heritage scavengers with refinery strength', '241773', '000000', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(27, 'Royals', 'RYL', 'Naga', 'Bicolano kings with fiery river spirit', '004687', 'BA8B02', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(28, 'Thunders', 'THN', 'Calapan', 'Oriental lightning strikers with island storm bursts', '007AC1', 'EF3B24', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(29, 'Warriors', 'WAR', 'Kalinga', 'Kalinga fighters charging with highland pride', '1D428A', 'FFC72C', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(30, 'Hellhounds', 'HH', 'Sorsogon', 'Bicolano guardians unleashing underworld ferocity', 'BB0000', '000000', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(31, 'Red Fox', 'RF', 'Tuguegarao', 'Cagayan tricksters darting through valley winds', 'CC0000', '000000', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(32, 'Cougars', 'CGR', 'Puerto Princesa', 'Palawan prowlers guarding nature’s sanctuary', 'C8102E', '000000', 1, 2, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(33, 'Waves', 'WAV', 'Cebu City', 'Queen City surfers riding Central Visayan currents', '005C5C', '000000', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(34, 'Predators', 'PRD', 'Iloilo City', 'Dinagyang hunters feasting on heritage and heart', '041E42', 'C8102E', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(35, 'Trilogy', 'TRI', 'Bacolod', 'MassKara trio lighting up with sugar-fueled rhythm', '8A8D8F', 'FFC72C', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(36, 'Monarchs', 'MON', 'Tacloban', 'Leyte royalty rising from storm-swept history', '512D6D', 'FFD700', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(37, 'Krakens', 'KRK', 'Dumaguete', 'Sea beasts breaching with university intellect', '001628', '99D9D9', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(38, 'Jets', 'JET', 'Tagbilaran', 'Bohol launchers soaring over Chocolate Hills', '203731', '000000', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(39, 'Northern Stars', 'NS', 'Roxas City', 'Capiz brilliance shining beyond seafood shores', '00205B', 'B0B7BC', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(40, 'Ninjas', 'NIN', 'Ormoc', 'Leyte shadows striking silently from the west', '000000', 'FF671F', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(41, 'Dragons', 'DRA', 'Kalibo', 'Ati-atihan serpents blazing through Panay winds', 'FF5910', '000000', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(42, 'Phoenix', 'PHO', 'Catarman', 'Northern Samar flames rising from island ashes', '862633', '000000', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(43, 'Sharks', 'SHA', 'San Jose', 'Antique predators circling with quiet fury', '006272', 'EAE6DE', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(44, 'Giants', 'GNT', 'Baybay', 'Southern Leyte titans grounded in historic soil', '0B2265', 'A71930', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(45, 'Fire', 'FRE', 'Maasin', 'Fiery defenders igniting island battles', 'CE1141', '000000', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(46, 'Patriots', 'PAT', 'Cadiz', 'Negros patriots blazing with sugarland pride', 'C60C30', '0033A0', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(47, 'Aces', 'ACE', 'Bogo', 'Northern Cebu elites with coastal confidence', '002D62', 'BA0C2F', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(48, 'Monsters', 'MNT', 'Guihulngan', 'Mountain beasts roaring with resilience', '6F263D', 'FFB81C', 1, 3, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(49, 'Pirates', 'PIR', 'Davao City', 'King City raiders sailing with durian dominance', '000000', 'FFB81C', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(50, 'Scorpions', 'SCR', 'Zamboanga ', 'Chavacano stingers dancing to Latin precision', 'FFC72C', '003087', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(51, 'Enemies', 'ENM', 'Cagayan de Oro', 'Golden rivals armed with friendship fury', '4B5320', '000000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(52, 'Reapers', 'RPR', 'General Santos', 'Tuna town harvesters with knockout menace', '000000', 'FF8200', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(53, 'Raiders', 'RAI', 'Butuan', 'Caraga marauders with ancient balangay speed', '000000', 'A5ACAF', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(54, 'Whales', 'WH', 'Tagum', 'Davao del Norte giants gliding through palm rows', '00205B', '000000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(55, 'Poseidons', 'POS', 'Surigao City', 'Sea gods ruling tides with mining muscle', '00205B', '7EC8E3', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(56, 'Cyclones', 'CYC', 'Cotabato City', 'River city storms with Maguindanao force', '512888', 'FFD100', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(57, 'Force', 'FRC', 'Pagadian', 'Zamboanga swirl charging with hilltop speed', '002F6C', 'BA0C2F', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(58, 'Astronauts', 'AST', 'Marawi', 'Lanao explorers orbiting in rebuilt glory', '002D62', 'BA0C2F', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(59, 'Demons', 'DMN', 'Iligan City', 'Waterfall phantoms striking from hydro heights', '6F263D', '000000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(60, 'Devils', 'DVL', 'Malaybalay', 'Bukidnon tempters rising from pine-kissed plateaus', 'CE1126', '000000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(61, 'Bulldogs', 'BD', 'Kidapawan', 'Volcano guards bulldozing with hot spring grit', '0C2340', 'FF0000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(62, 'Hornets', 'HRN', 'Digos', 'Southern stingers buzzing past banana lines', '1D1160', '00788C', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(63, 'Rebels', 'RBL', 'Panabo City', 'Agri city insurgents defending with fearless crops', 'BA0C2F', '000000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(64, 'Owls', 'OWL', 'Tandag', 'Midnight wisdom flying from the coastal dark', 'B1040E', '000000', 1, 4, '2025-04-19 14:34:03', '2025-04-19 14:34:03'),
(65, 'Angels', 'ACA', 'Angeles City', 'Angeles City: Soaring High with Thunder and Grace', 'FFCC00', '003366', 1, 1, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(66, 'Knights', 'KNT', 'Malolos', 'Bulacan noble warriors with historical valor', '333399', 'CCCCCC', 1, 1, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(67, 'Ghosts', 'GHS', 'Bataan', 'Bataan spectral figures haunting the peninsula\'s history', 'A9A9A9', '777777', 1, 2, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(68, 'Sonics', 'SNC', 'La Union', 'La Union sound warriors riding the surf\'s rhythm', '1E90FF', '003366', 1, 2, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(69, 'Storms', 'STM', 'Sipalay', 'Negros tempestuous squalls sweeping sugar fields', '0000FF', 'B0C4DE', 1, 3, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(70, 'Devil Bats', 'DBT', 'Escalante', 'Northern Negros nocturnal flyers with sugarcane stealth', '8B0000', '000000', 1, 3, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(71, 'Marlins', 'MRL', 'Koronadal', 'South Cotabato aquatic hunters thriving in Lake Sebu', '4682B4', 'FF69B4', 1, 4, '2025-04-19 14:36:46', '2025-04-19 14:36:46'),
(72, 'Black Bulls', 'BBL', 'Misamis', 'Misamis dark chargers with rainforest momentum', '000000', 'B8860B', 1, 4, '2025-04-19 14:36:46', '2025-04-19 14:36:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
