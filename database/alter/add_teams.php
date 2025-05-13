INSERT INTO `teams`
(`id`, `name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `created_at`, `updated_at`)
VALUES
-- Conference 1 (NCR) - Metro Manila
(1,  'Lions',     'LIO', '0076B6', 'B0B7BC', 1, 1, 'Quezon City',     'NCR predators roaring with Commonwealth pride',       NOW(), NOW()), -- Detroit Lions (NFL)
(2,  'Tigers',    'TIG', '0C2340', 'FA4616', 1, 1, 'Manila',           'Capital wildcats with Intramuros strength',          NOW(), NOW()), -- Detroit Tigers (MLB)
(3,  'Bears',     'BEA', '0B162A', 'C83803', 1, 1, 'Makati',           'Financial jungle brutes with skyscraper grit',        NOW(), NOW()), -- Memphis Grizzlies (Dark blue + burnt orange accent)
(4,  'Wolves',    'WOL', '236192', 'C4CED4', 1, 1, 'Taguig',           'Bonifacio hunters with elite urban tactics',          NOW(), NOW()), -- Minnesota Timberwolves (Updated blue)
(5,  'Eagles',    'EAG', '004C54', 'A5ACAF', 1, 1, 'Pasig',            'Ortigas raptors soaring above traffic chaos',         NOW(), NOW()), -- Philadelphia Eagles (NFL)
(6,  'Falcons',   'FAL', 'A71930', '101820', 1, 1, 'Caloocan',         'North Metro aerial attackers with steel resolve',     NOW(), NOW()), -- Atlanta Falcons (black + red)
(7,  'Hawks',     'HAW', 'E03A3E', 'FDB927', 1, 1, 'Parañaque',        'Southern sky hunters gliding past the bay breeze',    NOW(), NOW()), -- Atlanta Hawks (NBA red + yellow)
(8,  'Panthers',  'PAN', '0085CA', '101820', 1, 1, 'Mandaluyong',      'Urban jungle prowlers with central bite',             NOW(), NOW()), -- Carolina Panthers (NFL)
(9,  'Athletics', 'ATH', '003831', 'EFB21E', 1, 1, 'San Juan',         'Tiny city titans running with green-hearted hustle',  NOW(), NOW()), -- Oakland Athletics (MLB)
(10, 'Vipers',    'VIP', '1D428A', 'C8102E', 1, 1, 'Pasay',            'Bayfront strikers with terminal velocity',            NOW(), NOW()), -- RGV Vipers (blue + red alt)
(11, 'Jaguars',   'JAG', '006778', '9F7925', 1, 1, 'Las Piñas',        'South Metro prowlers with bamboo toughness',          NOW(), NOW()), -- Jacksonville Jaguars (NFL)
(12, 'Dolphins',  'DOL', '008E97', 'F58220', 1, 1, 'Valenzuela',       'Industrial sea acrobats with steel nerve',            NOW(), NOW()), -- Miami Dolphins (NFL)
(13, 'Rockets',   'RCK', 'CE1141', 'C4CED4', 1, 1, 'Navotas',          'Fishing port launchers with harbor blast power',      NOW(), NOW()), -- Houston Rockets (NBA red + gray)
(14, 'Braves',    'BRV', '13274F', 'CE1141', 1, 1, 'Malabon',          'Northern warriors with flood-tested bravery',         NOW(), NOW()), -- Atlanta Braves (MLB navy + red)
(15, 'Blazers',   'BLZ', 'E03A3E', '000000', 1, 1, 'Marikina',         'Shoe capital torches sprinting with artisan fire',    NOW(), NOW()), -- Portland Trail Blazers (fixed to proper red + black)
(16, 'Kings',     'KIN', '5A2D81', '63727A', 1, 1, 'Muntinlupa',       'Southern monarchs guarding the lakeside throne',      NOW(), NOW()), -- Sacramento Kings (purple + silver-gray)

(17, 'Titans', 'TIT', '002244', 'C60C30', 1, 2, 'Baguio', 'Highland giants crushing foes with pine-fresh dominance', NOW(), NOW()), -- Tennessee Titans (NFL: navy + red)

(18, 'Spartans', 'SPA', '18453B', 'A3A3A3', 1, 2, 'Laoag', 'Ilocano warriors standing tall with windmill might', NOW(), NOW()), -- MSU Spartans (dark green + silver)

(19, 'Trojans', 'TRO', '990000', 'FFC72C', 1, 2, 'Tarlac City', 'Central Luzon tacticians breaking lines with shield and speed', NOW(), NOW()), -- USC Trojans (crimson + gold)

(20, 'Saints', 'SNT', '101820', 'D3BC8D', 1, 2, 'Vigan', 'Heritage believers with cobblestone resilience', NOW(), NOW()), -- Saints (black + old gold)

(21, 'Aliens', 'ALN', '1C2526', '6F2DA8', 1, 2, 'Olongapo', 'Subic spacewalkers beaming with naval discipline', NOW(), NOW()), -- Sci-fi inspired (deep gray + neon violet)

(22, 'Leopards', 'LEO', 'C8102E', 'FFD100', 1, 2, 'Legazpi', 'Mayon hunters striking with volcanic elegance', NOW(), NOW()), -- China CBA: red + yellow

(23, 'Sabertooths', 'SAB', '00205B', 'A2AAAD', 1, 2, 'Lucena', 'Quezon predators slicing through coconut groves', NOW(), NOW()), -- Buffalo Sabres: navy + silver

(24, 'Spiders', 'SPD', 'CE1141', '1D428A', 1, 2, 'Nueva Ecija', 'Nueva Ecija web-weavers with farmer precision', NOW(), NOW()), -- Red + royal blue contrast

(25, 'Vikings', 'VIK', '4F2683', 'FFC62F', 1, 2, 'Pampanga', 'Pampanga raiders with culinary carnage', NOW(), NOW()), -- Vikings (purple + yellow)

(26, 'Crows', 'CRW', '241F20', 'A2AAAD', 1, 2, 'Batangas City', 'Heritage scavengers with refinery strength', NOW(), NOW()), -- Black + silver-gray contrast

(27, 'Royals', 'RYL', '004687', 'C5B358', 1, 2, 'Naga', 'Bicolano kings with fiery river spirit', NOW(), NOW()), -- Royals (royal blue + gold)

(28, 'Thunders', 'THN', '007AC1', 'F05133', 1, 2, 'Mindoro', 'Oriental lightning strikers with island storm bursts', NOW(), NOW()), -- OKC Thunder (light blue + orange)

(29, 'Warriors', 'WAR', '1D428A', 'FDB927', 1, 2, 'Kalinga', 'Kalinga fighters charging with highland pride', NOW(), NOW()), -- Warriors (royal blue + gold)

(30, 'Hellhounds', 'HH', '1D1D1D', 'FF0000', 1, 2, 'Sorsogon', 'Bicolano guardians unleashing underworld ferocity', NOW(), NOW()), -- Red + black

(31, 'Red Fox', 'RF', '5B2B2F', 'FFB612', 1, 2, 'Tuguegarao', 'Cagayan tricksters darting through valley winds', NOW(), NOW()), -- Brown + yellow

(32, 'Cougars', 'CGR', '003B4D', '007C92', 1, 2, 'Puerto Princesa', 'Palawan prowlers guarding nature’s sanctuary', NOW(), NOW()), -- NFL teal + navy combo


-- Conference 3 (Visayas)
(33, 'Waves', 'WAV', '002244', 'EFD9A1', 1, 3, 'Cebu City', 'Queen City surfers riding Central Visayan currents', NOW(), NOW()), -- Blue ocean + sand yellow

(34, 'Predators', 'PRD', '041E42', 'FFB81C', 1, 3, 'Iloilo City', 'Dinagyang hunters feasting on heritage and heart', NOW(), NOW()), -- Nashville Predators: navy + gold

(35, 'Trilogy', 'TRI', 'C8102E', '002B5C', 1, 3, 'Bacolod', 'MassKara trio lighting up with sugar-fueled rhythm', NOW(), NOW()), -- Trilogy Big3: red + navy

(36, 'Monarchs', 'MON', '5A2D81', 'D6AF36', 1, 3, 'Tacloban', 'Leyte royalty rising from storm-swept history', NOW(), NOW()), -- Purple + royal gold

(37, 'Krakens', 'KRK', '001628', '99D9D9', 1, 3, 'Dumaguete', 'Sea beasts breaching with university intellect', NOW(), NOW()), -- Seattle Kraken: deep sea + ice teal

(38, 'Jets', 'JET', '003F2D', '78B833', 1, 3, 'Tagbilaran', 'Bohol launchers soaring over Chocolate Hills', NOW(), NOW()), -- NY Jets: green + lime green tint

(39, 'Northern Stars', 'NS', '006847', 'A2AAAD', 1, 3, 'Roxas City', 'Capiz brilliance shining beyond seafood shores', NOW(), NOW()), -- Dallas Stars: green + silver-gray

(40, 'Ninjas', 'NIN', '000000', 'C4CED4', 1, 3, 'Ormoc', 'Leyte shadows striking silently from the west', NOW(), NOW()), -- Black + pale steel

(41, 'Dragons', 'DRA', 'B5121B', 'F7B32B', 1, 3, 'Kalibo', 'Ati-atihan serpents blazing through Panay winds', NOW(), NOW()), -- Red + imperial gold

(42, 'Phoenix', 'PHO', '1D1160', 'E56020', 1, 3, 'Catarman', 'Northern Samar flames rising from island ashes', NOW(), NOW()), -- Suns: purple + orange

(43, 'Sharks', 'SHA', '006D75', 'A2AAAD', 1, 3, 'San Jose', 'Antique predators circling with quiet fury', NOW(), NOW()), -- SJ Sharks: teal + silver-gray

(44, 'Giants', 'GNT', '0B2265', 'A71930', 1, 3, 'Baybay', 'Southern Leyte titans grounded in historic soil', NOW(), NOW()), -- NY Giants: blue + red

(45, 'Fire', 'FRE', '98002E', 'FDB927', 1, 3, 'Maasin', 'Fiery defenders igniting island battles', NOW(), NOW()), -- Chicago Fire: fire red + flame yellow

(46, 'Patriots', 'PAT', '002244', 'C60C30', 1, 3, 'Cadiz', 'Negros patriots blazing with sugarland pride', NOW(), NOW()), -- Patriots: navy + red

(47, 'Aces', 'ACE', 'B4975A', '000000', 1, 3, 'Bogo', 'Northern Cebu elites with coastal confidence', NOW(), NOW()), -- Aces: champagne gold + black

(48, 'Monsters', 'MNT', '6F263D', 'FDBB30', 1, 3, 'Guihulngan', 'Mountain beasts roaring with resilience', NOW(), NOW()), -- Monsters: wine + gold

(49, 'Pirates', 'PIR', 'FFB612', '34302B', 1, 4, 'Davao City', 'King City raiders sailing with durian dominance', NOW(), NOW()), -- Swapped red for Pittsburgh yellow + pirate gray

(50, 'Scorpions', 'SCR', '8A4F00', 'FFD700', 1, 4, 'Zamboanga', 'Chavacano stingers dancing to Latin precision', NOW(), NOW()), -- Desert brown + golden sting

(51, 'Enemies', 'ENM', 'C8102E', '000000', 1, 4, 'Cagayan de Oro', 'Golden rivals armed with friendship fury', NOW(), NOW()), -- Intense red + black

(52, 'Reapers', 'RPR', '1B1B1B', 'D50000', 1, 4, 'General Santos', 'Tuna town harvesters with knockout menace', NOW(), NOW()), -- Shadow black + blood red

(53, 'Raiders', 'RAI', '000000', 'A5ACAF', 1, 4, 'Butuan', 'Caraga marauders with ancient balangay speed', NOW(), NOW()), -- Raiders: black + silver-gray

(54, 'Whales', 'WH', '005C5C', 'A1D6E2', 1, 4, 'Tagum', 'Davao del Norte giants gliding through palm rows', NOW(), NOW()), -- Sea teal + bubble blue

(55, 'Poseidons', 'POS', '003B4F', '00B4D8', 1, 4, 'Surigao City', 'Sea gods ruling tides with mining muscle', NOW(), NOW()), -- Deep sea blue + ocean teal

(56, 'Cyclones', 'CYC', '4B0082', 'FFD700', 1, 4, 'Cotabato City', 'River city storms with Maguindanao force', NOW(), NOW()), -- Royal purple + yellow lightning

(57, 'Force', 'FRC', '006BB6', 'ED174C', 1, 4, 'Zamboanga del Sur', 'Zamboanga swirl charging with hilltop speed', NOW(), NOW()), -- Sixers red + blue

(58, 'Astronauts', 'AST', '1A1A40', 'F28C28', 1, 4, 'Lanao del Sur', 'Lanao explorers orbiting in rebuilt glory', NOW(), NOW()), -- Space navy + NASA orange

(59, 'Demons', 'DMN', '0B162A', 'FF3C00', 1, 4, 'Iligan City', 'Waterfall phantoms striking from hydro heights', NOW(), NOW()), -- Bears navy + flame orange

(60, 'Devils', 'DVL', 'D50A0A', '000000', 1, 4, 'Malaybalay', 'Bukidnon tempters rising from pine-kissed plateaus', NOW(), NOW()), -- Classic devils: red + black

(61, 'Bulldogs', 'BD', '003087', 'A3A3A3', 1, 4, 'Kidapawan', 'Volcano guards bulldozing with hot spring grit', NOW(), NOW()), -- Georgia navy + bulldog gray

(62, 'Hornets', 'HRN', '1C2526', '00B2A9', 1, 4, 'Davao del Sur', 'Southern stingers buzzing past banana lines', NOW(), NOW()), -- Hornets black + teal

(63, 'Rebels', 'RBL', 'EF0107', '1E1E2F', 1, 4, 'Davao del Norte', 'Agri city insurgents defending with fearless crops', NOW(), NOW()), -- UNLV red + dark slate blue

(64, 'Owls', 'OWL', '0C2340', 'C8102E', 1, 4, 'Surigao del Norte', 'Midnight wisdom flying from the coastal dark', NOW(), NOW()) -- Temple: midnight navy + cardinal red
