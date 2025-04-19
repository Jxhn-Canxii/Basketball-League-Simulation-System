INSERT INTO `teams` 
(`id`, `name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `created_at`, `updated_at`) 
VALUES
-- Conference 1 (NCR) - NFL/NCAA Color Schemes
(1, 'Lions', 'LIO', '0076B6', 'B0B7BC', 1, 1, 'Quezon City', 'NCR predators roaring with Commonwealth pride', NOW(), NOW()), -- Detroit Lions (NFL)
(2, 'Tigers', 'TIG', 'FB4F14', '000000', 1, 1, 'Manila', 'Capital wildcats with Intramuros strength', NOW(), NOW()), -- Cincinnati Bengals (NFL)
(3, 'Bears', 'BEA', '0B162A', 'C83803', 1, 1, 'Makati', 'Financial jungle brutes with skyscraper grit', NOW(), NOW()), -- Chicago Bears (NFL)
(4, 'Wolves', 'WOL', '0C2340', '236192', 1, 1, 'Taguig', 'Bonifacio hunters with elite urban tactics', NOW(), NOW()), -- Minnesota Timberwolves (NBA)
(5, 'Eagles', 'EAG', '004C54', 'A5ACAF', 1, 1, 'Pasig', 'Ortigas raptors soaring above traffic chaos', NOW(), NOW()), -- Philadelphia Eagles (NFL)
(6, 'Falcons', 'FAL', 'A71930', '000000', 1, 1, 'Caloocan', 'North Metro aerial attackers with steel resolve', NOW(), NOW()), -- Atlanta Falcons (NFL)
(7, 'Hawks', 'HAW', 'E03A3E', 'C1D32F', 1, 1, 'Parañaque', 'Southern sky hunters gliding past the bay breeze', NOW(), NOW()), -- Atlanta Hawks (NBA)
(8, 'Panthers', 'PAN', '0085CA', '101820', 1, 1, 'Mandaluyong', 'Urban jungle prowlers with central bite', NOW(), NOW()), -- Carolina Panthers (NFL)
(9, 'Athletics', 'ATH', '003831', 'EFB21E', 1, 1, 'San Juan', 'Tiny city titans running with green-hearted hustle', NOW(), NOW()), -- Oakland Athletics (MLB)
(10, 'Vipers', 'VIP', '007A33', 'FF8200', 1, 1, 'Pasay', 'Bayfront strikers with terminal velocity', NOW(), NOW()), -- Rio Grande Valley Vipers (G-League)
(11, 'Jaguars', 'JAG', '006778', 'D7A22A', 1, 1, 'Las Piñas', 'South Metro prowlers with bamboo toughness', NOW(), NOW()), -- Jacksonville Jaguars (NFL)
(12, 'Dolphins', 'DOL', '008E97', 'FC4C02', 1, 1, 'Valenzuela', 'Industrial sea acrobats with steel nerve', NOW(), NOW()), -- Miami Dolphins (NFL)
(13, 'Rockets', 'RCK', 'CE1141', 'C4CED4', 1, 1, 'Navotas', 'Fishing port launchers with harbor blast power', NOW(), NOW()), -- Houston Rockets (NBA)
(14, 'Braves', 'BRV', '13274F', 'BA0C2F', 1, 1, 'Malabon', 'Northern warriors with flood-tested bravery', NOW(), NOW()), -- Atlanta Braves (MLB)
(15, 'Blazers', 'BLZ', 'E03A3E', '000000', 1, 1, 'Marikina', 'Shoe capital torches sprinting with artisan fire', NOW(), NOW()), -- Portland Trail Blazers (NBA)
(16, 'Kings', 'KIN', '5A2D81', '8C7B6D', 1, 1, 'Muntinlupa', 'Southern monarchs guarding the lakeside throne', NOW(), NOW()), -- Sacramento Kings (NBA)

-- Conference 2 (North) - NCAA Focus
(17, 'Titans', 'TIT', '0C2340', '4B92DB', 1, 2, 'Baguio', 'Highland giants crushing foes with pine-fresh dominance', NOW(), NOW()), -- Tennessee Titans (NFL)
(18, 'Spartans', 'SPA', '18453B', 'B9B5B8', 1, 2, 'Laoag', 'Ilocano warriors standing tall with windmill might', NOW(), NOW()), -- Michigan State Spartans (NCAA)
(19, 'Trojans', 'TRO', '990000', 'FFD100', 1, 2, 'Tarlac City', 'Central Luzon tacticians breaking lines with shield and speed', NOW(), NOW()), -- USC Trojans (NCAA)
(20, 'Saints', 'SNT', 'D3BC8D', '000000', 1, 2, 'Vigan', 'Heritage believers with cobblestone resilience', NOW(), NOW()), -- New Orleans Saints (NFL)
(21, 'Aliens', 'ALN', '00A36C', 'FFD700', 1, 2, 'Olongapo', 'Subic spacewalkers beaming with naval discipline', NOW(), NOW()), -- Oregon Ducks Inspired
(22, 'Leopards', 'LEO', '862633', 'F1BE48', 1, 2, 'Legazpi', 'Mayon hunters striking with volcanic elegance', NOW(), NOW()), -- Lafayette Leopards (NCAA)
(23, 'Sabertooths', 'SAB', '041E42', 'C8102E', 1, 2, 'Lucena', 'Quezon predators slicing through coconut groves', NOW(), NOW()), -- Nashville Predators (NHL)
(24, 'Spiders', 'SPD', '003366', 'CC0000', 1, 2, 'Cabanatuan', 'Nueva Ecija web-weavers with farmer precision', NOW(), NOW()), -- Richmond Spiders (NCAA)
(25, 'Vikings', 'VIK', '4F2683', 'FFC62F', 1, 2, 'Pampanga', 'Pampanga raiders with culinary carnage', NOW(), NOW()), -- Minnesota Vikings (NFL)
(26, 'Crows', 'CRW', '241773', '000000', 1, 2, 'Batangas City', 'Heritage scavengers with refinery strength', NOW(), NOW()), -- Seattle Seahawks Inspired
(27, 'Royals', 'RYL', '004687', 'BA8B02', 1, 2, 'Naga', 'Bicolano kings with fiery river spirit', NOW(), NOW()), -- Kansas City Royals (MLB)
(28, 'Thunders', 'THN', '007AC1', 'EF3B24', 1, 2, 'Calapan', 'Oriental lightning strikers with island storm bursts', NOW(), NOW()), -- Oklahoma City Thunder (NBA)
(29, 'Warriors', 'WAR', '1D428A', 'FFC72C', 1, 2, 'Kalinga', 'Kalinga fighters charging with highland pride', NOW(), NOW()), -- Golden State Warriors (NBA)
(30, 'Hellhounds', 'HH', 'BB0000', '000000', 1, 2, 'Sorsogon', 'Bicolano guardians unleashing underworld ferocity', NOW(), NOW()), -- Chicago Blackhawks Inspired
(31, 'Red Fox', 'RF', 'CC0000', '000000', 1, 2, 'Tuguegarao', 'Cagayan tricksters darting through valley winds', NOW(), NOW()), -- James Madison Dukes Inspired
(32, 'Cougars', 'CGR', 'C8102E', '000000', 1, 2, 'Puerto Princesa', 'Palawan prowlers guarding nature’s sanctuary', NOW(), NOW()), -- Houston Cougars (NCAA)

-- Conference 3 (Visayas) - Mixed League Styles
(33, 'Waves', 'WAV', '005C5C', '000000', 1, 3, 'Cebu City', 'Queen City surfers riding Central Visayan currents', NOW(), NOW()), -- Pepperdine Waves (NCAA)
(34, 'Predators', 'PRD', '041E42', 'C8102E', 1, 3, 'Iloilo City', 'Dinagyang hunters feasting on heritage and heart', NOW(), NOW()), -- Nashville Predators (NHL)
(35, 'Trilogy', 'TRI', '8A8D8F', 'FFC72C', 1, 3, 'Bacolod', 'MassKara trio lighting up with sugar-fueled rhythm', NOW(), NOW()), -- San Antonio Spurs Inspired
(36, 'Monarchs', 'MON', '512D6D', 'FFD700', 1, 3, 'Tacloban', 'Leyte royalty rising from storm-swept history', NOW(), NOW()), -- Old Dominion Monarchs (NCAA)
(37, 'Krakens', 'KRK', '001628', '99D9D9', 1, 3, 'Dumaguete', 'Sea beasts breaching with university intellect', NOW(), NOW()), -- Seattle Kraken (NHL)
(38, 'Jets', 'JET', '203731', '000000', 1, 3, 'Tagbilaran', 'Bohol launchers soaring over Chocolate Hills', NOW(), NOW()), -- New York Jets (NFL)
(39, 'Northern Stars', 'NS', '00205B', 'B0B7BC', 1, 3, 'Roxas City', 'Capiz brilliance shining beyond seafood shores', NOW(), NOW()), -- Dallas Stars (NHL)
(40, 'Ninjas', 'NIN', '000000', 'FF671F', 1, 3, 'Ormoc', 'Leyte shadows striking silently from the west', NOW(), NOW()), -- San Jose Sharks Inspired
(41, 'Dragons', 'DRA', 'FF5910', '000000', 1, 3, 'Kalibo', 'Ati-atihan serpents blazing through Panay winds', NOW(), NOW()), -- Drexel Dragons (NCAA)
(42, 'Phoenix', 'PHO', '862633', '000000', 1, 3, 'Catarman', 'Northern Samar flames rising from island ashes', NOW(), NOW()), -- Elon Phoenix (NCAA)
(43, 'Sharks', 'SHA', '006272', 'EAE6DE', 1, 3, 'San Jose', 'Antique predators circling with quiet fury', NOW(), NOW()), -- Miami Sharks (NHL Retro)
(44, 'Giants', 'GNT', '0B2265', 'A71930', 1, 3, 'Baybay', 'Southern Leyte titans grounded in historic soil', NOW(), NOW()), -- New York Giants (NFL)
(45, 'Fire', 'FRE', 'CE1141', '000000', 1, 3, 'Maasin', 'Fiery defenders igniting island battles', NOW(), NOW()), -- Chicago Fire (MLS)
(46, 'Patriots', 'PAT', 'C60C30', '0033A0', 1, 3, 'Cadiz', 'Negros patriots blazing with sugarland pride', NOW(), NOW()), -- New England Patriots (NFL)
(47, 'Aces', 'ACE', '002D62', 'BA0C2F', 1, 3, 'Bogo', 'Northern Cebu elites with coastal confidence', NOW(), NOW()), -- St. Louis Cardinals Inspired
(48, 'Monsters', 'MNT', '6F263D', 'FFB81C', 1, 3, 'Guihulngan', 'Mountain beasts roaring with resilience', NOW(), NOW()), -- Lake Erie Monsters (AHL)

-- Conference 4 (Mindanao) - Bold Color Schemes
(49, 'Pirates', 'PIR', '000000', 'FFB81C', 1, 4, 'Davao City', 'King City raiders sailing with durian dominance', NOW(), NOW()), -- Pittsburgh Pirates (MLB)
(50, 'Scorpions', 'SCR', 'FFC72C', '003087', 1, 4, 'Zamboanga', 'Chavacano stingers dancing to Latin precision', NOW(), NOW()), -- FAU Owls Inspired
(51, 'Enemies', 'ENM', '4B5320', '000000', 1, 4, 'Cagayan de Oro', 'Golden rivals armed with friendship fury', NOW(), NOW()), -- Army Black Knights Inspired
(52, 'Reapers', 'RPR', '000000', 'FF8200', 1, 4, 'General Santos', 'Tuna town harvesters with knockout menace', NOW(), NOW()), -- Jacksonville Jumbo Shrimp Inspired
(53, 'Raiders', 'RAI', '000000', 'A5ACAF', 1, 4, 'Butuan', 'Caraga marauders with ancient balangay speed', NOW(), NOW()), -- Las Vegas Raiders (NFL)
(54, 'Whales', 'WH', '00205B', '000000', 1, 4, 'Tagum', 'Davao del Norte giants gliding through palm rows', NOW(), NOW()), -- Connecticut Whale (NWHL)
(55, 'Poseidons', 'POS', '00205B', '7EC8E3', 1, 4, 'Surigao City', 'Sea gods ruling tides with mining muscle', NOW(), NOW()), -- UC San Diego Tritons (NCAA)
(56, 'Cyclones', 'CYC', '512888', 'FFD100', 1, 4, 'Cotabato City', 'River city storms with Maguindanao force', NOW(), NOW()), -- Iowa State Cyclones (NCAA)
(57, 'Force', 'FRC', '002F6C', 'BA0C2F', 1, 4, 'Pagadian', 'Zamboanga swirl charging with hilltop speed', NOW(), NOW()), -- Air Force Falcons (NCAA)
(58, 'Astronauts', 'AST', '002D62', 'BA0C2F', 1, 4, 'Marawi', 'Lanao explorers orbiting in rebuilt glory', NOW(), NOW()), -- Houston Astros (MLB)
(59, 'Demons', 'DMN', '6F263D', '000000', 1, 4, 'Iligan City', 'Waterfall phantoms striking from hydro heights', NOW(), NOW()), -- Northwestern State Demons (NCAA)
(60, 'Devils', 'DVL', 'CE1126', '000000', 1, 4, 'Malaybalay', 'Bukidnon tempters rising from pine-kissed plateaus', NOW(), NOW()), -- Duke Blue Devils (NCAA)
(61, 'Bulldogs', 'BD', '0C2340', 'FF0000', 1, 4, 'Kidapawan', 'Volcano guards bulldozing with hot spring grit', NOW(), NOW()), -- Georgia Bulldogs (NCAA)
(62, 'Hornets', 'HRN', '1D1160', '00788C', 1, 4, 'Digos', 'Southern stingers buzzing past banana lines', NOW(), NOW()), -- Charlotte Hornets (NBA)
(63, 'Rebels', 'RBL', 'BA0C2F', '000000', 1, 4, 'Panabo City', 'Agri city insurgents defending with fearless crops', NOW(), NOW()), -- Ole Miss Rebels (NCAA)
(64, 'Owls', 'OWL', 'B1040E', '000000', 1, 4, 'Tandag', 'Midnight wisdom flying from the coastal dark', NOW(), NOW()); -- Temple Owls (NCAA)