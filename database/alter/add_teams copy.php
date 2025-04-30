INSERT INTO `teams`
(`id`, `name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `created_at`, `updated_at`)
VALUES
-- Conference 1 (NCR)
(1, 'Lions', 'LIO', '0076B6', 'B0B7BC', 1, 1, 'Quezon City', 'NCR predators roaring with Commonwealth pride', NOW(), NOW()),
(2, 'Tigers', 'TIG', '0C2340', 'FA4616', 1, 1, 'Manila', 'Capital wildcats with Intramuros strength', NOW(), NOW()),
(3, 'Bears', 'BEA', '0B162A', 'C83803', 1, 1, 'Makati', 'Financial jungle brutes with skyscraper grit', NOW(), NOW()),
(4, 'Wolves', 'WOL', '006847', '8A8D8F', 1, 1, 'Taguig', 'Bonifacio hunters with elite urban tactics', NOW(), NOW()), -- Changed: Dallas Stars
(5, 'Eagles', 'EAG', '004C54', 'A5ACAF', 1, 1, 'Pasig', 'Ortigas raptors soaring above traffic chaos', NOW(), NOW()),
(6, 'Falcons', 'FAL', '004C54', 'A5ACAF', 1, 1, 'Caloocan', 'North Metro aerial attackers with steel resolve', NOW(), NOW()),
(7, 'Hawks', 'HAW', 'E03A3E', '26282A', 1, 1, 'Parañaque', 'Southern sky hunters gliding past the bay breeze', NOW(), NOW()),
(8, 'Panthers', 'PAN', '006BB6', 'ED174C', 1, 1, 'Mandaluyong', 'Urban jungle prowlers with central bite', NOW(), NOW()), -- Changed: Philadelphia 76ers
(9, 'Athletics', 'ATH', '001628', 'E9072B', 1, 1, 'San Juan', 'Tiny city titans running with green-hearted hustle', NOW(), NOW()), -- Changed: Seattle Kraken
(10, 'Vipers', 'VIP', '1D428A', 'C8102E', 1, 1, 'Pasay', 'Bayfront strikers with terminal velocity', NOW(), NOW()),
(11, 'Jaguars', 'JAG', '006778', '9F7925', 1, 1, 'Las Piñas', 'South Metro prowlers with bamboo toughness', NOW(), NOW()),
(12, 'Dolphins', 'DOL', '008E97', 'F58220', 1, 1, 'Valenzuela', 'Industrial sea acrobats with steel nerve', NOW(), NOW()),
(13, 'Rockets', 'RCK', '5B2B2F', 'FFB612', 1, 1, 'Navotas', 'Fishing port launchers with harbor blast power', NOW(), NOW()), -- Changed: Washington Commanders
(14, 'Braves', 'BRV', '13274F', 'CE1141', 1, 1, 'Malabon', 'Northern warriors with flood-tested bravery', NOW(), NOW()),
(15, 'Blazers', 'BLZ', 'E03A3E', 'B6AFA9', 1, 1, 'Marikina', 'Shoe capital torches sprinting with artisan fire', NOW(), NOW()), -- Kept: Portland Trail Blazers
(16, 'Kings', 'KIN', '004687', 'C7A171', 1, 1, 'Muntinlupa', 'Southern monarchs guarding the lakeside throne', NOW(), NOW()), -- Changed: Kansas City Royals

-- Conference 2 (North)
(17, 'Titans', 'TIT', '0C2340', 'C8102E', 1, 2, 'Baguio', 'Highland giants crushing foes with pine-fresh dominance', NOW(), NOW()),
(18, 'Spartans', 'SPA', '0C2340', 'FA4616', 1, 2, 'Laoag', 'Ilocano warriors standing tall with windmill might', NOW(), NOW()), -- Changed: Detroit Tigers
(19, 'Trojans', 'TRO', 'EF0107', '023474', 1, 2, 'Tarlac City', 'Central Luzon tacticians breaking lines with shield and speed', NOW(), NOW()), -- Changed: Arsenal
(20, 'Saints', 'SNT', '101820', 'D3BC8D', 1, 2, 'Vigan', 'Heritage believers with cobblestone resilience', NOW(), NOW()),
(21, 'Aliens', 'ALN', '1D1160', 'E56020', 1, 2, 'Olongapo', 'Subic spacewalkers beaming with naval discipline', NOW(), NOW()), -- Changed: Phoenix Suns
(22, 'Leopards', 'LEO', 'C8102E', '041E42', 1, 2, 'Legazpi', 'Mayon hunters striking with volcanic elegance', NOW(), NOW()),
(23, 'Sabertooths', 'SAB', '0B162A', 'C83803', 1, 2, 'Lucena', 'Quezon predators slicing through coconut groves', NOW(), NOW()), -- Changed: Chicago Bears
(24, 'Spiders', 'SPD', 'CE1141', '003087', 1, 2, 'Nueva Ecija', 'Nueva Ecija web-weavers with farmer precision', NOW(), NOW()),
(25, 'Vikings', 'VIK', 'C8102E', '002B5C', 1, 2, 'Pampanga', 'Pampanga raiders with culinary carnage', NOW(), NOW()), -- Changed: Barangay Ginebra
(26, 'Crows', 'CRW', '004687', 'C7A171', 1, 2, 'Batangas City', 'Heritage scavengers with refinery strength', NOW(), NOW()), -- Changed: Kansas City Royals
(27, 'Royals', 'RYL', '004C54', 'A5ACAF', 1, 2, 'Naga', 'Bicolano kings with fiery river spirit', NOW(), NOW()), -- Changed: Philadelphia Eagles
(28, 'Thunders', 'THN', '007AC1', 'EF3B24', 1, 2, 'Mindoro', 'Oriental lightning strikers with island storm bursts', NOW(), NOW()),
(29, 'Warriors', 'WAR', 'D50A0A', '34302B', 1, 2, 'Kalinga', 'Kalinga fighters charging with highland pride', NOW(), NOW()), -- Changed: Tampa Bay Buccaneers
(30, 'Hellhounds', 'HH', '1D428A', 'C8102E', 1, 2, 'Sorsogon', 'Bicolano guardians unleashing underworld ferocity', NOW(), NOW()),
(31, 'Red Fox', 'RF', '5B2B2F', 'FFB612', 1, 2, 'Tuguegarao', 'Cagayan tricksters darting through valley winds', NOW(), NOW()),
(32, 'Cougars', 'CGR', '006778', '9F7925', 1, 2, 'Puerto Princesa', 'Palawan prowlers guarding nature’s sanctuary', NOW(), NOW()), -- Changed: Jacksonville Jaguars

-- Conference 3 (Visayas)
(33, 'Waves', 'WAV', '002244', '69BE28', 1, 3, 'Cebu City', 'Queen City surfers riding Central Visayan currents', NOW(), NOW()),
(34, 'Predators', 'PRD', '0B162A', 'C83803', 1, 3, 'Iloilo City', 'Dinagyang hunters feasting on heritage and heart', NOW(), NOW()), -- Changed: Chicago Bears
(35, 'Trilogy', 'TRI', 'C8102E', '002B5C', 1, 3, 'Bacolod', 'MassKara trio lighting up with sugar-fueled rhythm', NOW(), NOW()),
(36, 'Monarchs', 'MON', '004687', 'C7A171', 1, 3, 'Tacloban', 'Leyte royalty rising from storm-swept history', NOW(), NOW()), -- Changed: Kansas City Royals
(37, 'Krakens', 'KRK', '001628', 'E9072B', 1, 3, 'Dumaguete', 'Sea beasts breaching with university intellect', NOW(), NOW()),
(38, 'Jets', 'JET', '006847', '8A8D8F', 1, 3, 'Tagbilaran', 'Bohol launchers soaring over Chocolate Hills', NOW(), NOW()), -- Changed: Dallas Stars
(39, 'Northern Stars', 'NS', '006BB6', 'ED174C', 1, 3, 'Roxas City', 'Capiz brilliance shining beyond seafood shores', NOW(), NOW()), -- Changed: Philadelphia 76ers
(40, 'Ninjas', 'NIN', '000000', 'C4CED4', 1, 3, 'Ormoc', 'Leyte shadows striking silently from the west', NOW(), NOW()),
(41, 'Dragons', 'DRA', '1D1160', 'E56020', 1, 3, 'Kalibo', 'Ati-atihan serpents blazing through Panay winds', NOW(), NOW()),
(42, 'Phoenix', 'PHO', '1D1160', 'E56020', 1, 3, 'Catarman', 'Northern Samar flames rising from island ashes', NOW(), NOW()),
(43, 'Sharks', 'SHA', '006778', '9F7925', 1, 3, 'San Jose', 'Antique predators circling with quiet fury', NOW(), NOW()), -- Changed: Jacksonville Jaguars
(44, 'Giants', 'GNT', '0B2265', 'A71930', 1, 3, 'Baybay', 'Southern Leyte titans grounded in historic soil', NOW(), NOW()),
(45, 'Fire', 'FRE', 'D50A0A', '34302B', 1, 3, 'Maasin', 'Fiery defenders igniting island battles', NOW(), NOW()), -- Changed: Tampa Bay Buccaneers
(46, 'Patriots', 'PAT', '002244', 'C8102E', 1, 3, 'Cadiz', 'Negros patriots blazing with sugarland pride', NOW(), NOW()),
(47, 'Aces', 'ACE', 'B4975A', '000000', 1, 3, 'Bogo', 'Northern Cebu elites with coastal confidence', NOW(), NOW()),
(48, 'Monsters', 'MNT', '6F263D', 'FDBB30', 1, 3, 'Guihulngan', 'Mountain beasts roaring with resilience', NOW(), NOW()),

-- Conference 4 (Mindanao)
(49, 'Pirates', 'PIR', 'D50A0A', '34302B', 1, 4, 'Davao City', 'King City raiders sailing with durian dominance', NOW(), NOW()),
(50, 'Scorpions', 'SCR', '1D1160', 'E56020', 1, 4, 'Zamboanga', 'Chavacano stingers dancing to Latin precision', NOW(), NOW()), -- Changed: Phoenix Suns
(51, 'Enemies', 'ENM', '101820', 'FFB612', 1, 4, 'Cagayan de Oro', 'Golden rivals armed with friendship fury', NOW(), NOW()),
(52, 'Reapers', 'RPR', '5B2B2F', 'FFB612', 1, 4, 'General Santos', 'Tuna town harvesters with knockout menace', NOW(), NOW()), -- Changed: Washington Commanders
(53, 'Raiders', 'RAI', '000000', 'C4CED4', 1, 4, 'Butuan', 'Caraga marauders with ancient balangay speed', NOW(), NOW()),
(54, 'Whales', 'WH', '006847', '8A8D8F', 1, 4, 'Tagum', 'Davao del Norte giants gliding through palm rows', NOW(), NOW()), -- Changed: Dallas Stars
(55, 'Poseidons', 'POS', '006D75', 'E9072B', 1, 4, 'Surigao City', 'Sea gods ruling tides with mining muscle', NOW(), NOW()),
(56, 'Cyclones', 'CYC', '4B0082', 'FF4500', 1, 4, 'Cotabato City', 'River city storms with Maguindanao force', NOW(), NOW()),
(57, 'Force', 'FRC', '006BB6', 'ED174C', 1, 4, 'Pagadian', 'Zamboanga swirl charging with hilltop speed', NOW(), NOW()),
(58, 'Astronauts', 'AST', '004687', 'C7A171', 1, 4, 'Marawi', 'Lanao explorers orbiting in rebuilt glory', NOW(), NOW()), -- Changed: Kansas City Royals
(59, 'Demons', 'DMN', '0B162A', 'C83803', 1, 4, 'Iligan City', 'Waterfall phantoms striking from hydro heights', NOW(), NOW()),
(60, 'Devils', 'DVL', 'D50A0A', '34302B', 1, 4, 'Malaybalay', 'Bukidnon tempters rising from pine-kissed plateaus', NOW(), NOW()),
(61, 'Bulldogs', 'BD', 'C8102E', '002B5C', 1, 4, 'Kidapawan', 'Volcano guards bulldozing with hot spring grit', NOW(), NOW()), -- Changed: Barangay Ginebra
(62, 'Hornets', 'HRN', '006778', '9F7925', 1, 4, 'Digos', 'Southern stingers buzzing past banana lines', NOW(), NOW()), -- Changed: Jacksonville Jaguars
(63, 'Rebels', 'RBL', 'EF0107', '023474', 1, 4, 'Panabo City', 'Agri city insurgents defending with fearless crops', NOW(), NOW()),
(64, 'Owls', 'OWL', '0C2340', 'C8102E', 1, 4, 'Tandag', 'Midnight wisdom flying from the coastal dark', NOW(), NOW());