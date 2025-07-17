-- NCR (Conference 1) - Initial 10
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(1,  'Lions', 'LIO', '0076B6', 'B0B7BC', 1, 1, 'Quezon City', 'Roar with Pride!', 'RoarTech', NOW(), NOW()),
(2,  'Tigers', 'TIG', '0C2340', 'FA4616', 1, 1, 'Manila', 'Claw the Capital!', 'TigerCore', NOW(), NOW()),
(3,  'Bears', 'BEA', '0B162A', 'C83803', 1, 1, 'Makati', 'Grip the Victory!', 'IronPaw Inc.', NOW(), NOW()),
(4,  'Wolves', 'WOL', '236192', 'C4CED4', 1, 1, 'Taguig', 'Hunt as One!', 'Wolfbyte', NOW(), NOW()),
(5,  'Eagles', 'EAG', '004C54', 'A5ACAF', 1, 1, 'Pasig', 'Soar to Glory!', 'Skycrest', NOW(), NOW()),
(6,  'Falcons', 'FAL', 'A71930', '101820', 1, 1, 'Caloocan', 'Strike from Above!', 'FalcoFuel', NOW(), NOW()),
(7,  'Hawks', 'HAW', 'E03A3E', 'FDB927', 1, 1, 'Parañaque', 'Swoop to Conquer!', 'Hawkforge', NOW(), NOW()),
(8,  'Panthers', 'PAN', '0085CA', '101820', 1, 1, 'Mandaluyong', 'Prowl with Power!', 'Panthra', NOW(), NOW()),
(9,  'Athletics', 'ATH', '003831', 'EFB21E', 1, 1, 'San Juan', 'Run to Triumph!', 'MotionTech', NOW(), NOW()),
(10, 'Vipers', 'VIP', '1D428A', 'C8102E', 1, 1, 'Pasay', 'Strike with Venom!', 'VenomDrive', NOW(), NOW()),
(11, 'Jaguars',   'JAG', '006778', '9F7925', 1, 1, 'Las Piñas',    'Leap to Dominate!',  'JagX Energy',   NOW(), NOW()),
(12, 'Red Arrows',  'IMR', '4E342E', 'C62828', 1, 1, 'Imus',   'Straight. Swift. Sure.',   'Imus Flight System',  NOW(), NOW());

-- North (Conference 2) - Initial 10
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(17, 'Titans', 'TIT', '002244', 'C60C30', 1, 2, 'Baguio', 'Crush with Might!', 'TitanCore', NOW(), NOW()),
(18, 'Spartans', 'SPA', '18453B', 'A3A3A3', 1, 2, 'Laoag', 'Fight with Honor!', 'ForgeX', NOW(), NOW()),
(19, 'Trojans', 'TRO', '990000', 'FFC72C', 1, 2, 'Tarlac City', 'Conquer the Field!', 'ShieldPoint', NOW(), NOW()),
(20, 'Saints', 'SNT', '101820', 'D3BC8D', 1, 2, 'Vigan', 'March to Salvation!', 'Halo Holdings', NOW(), NOW()),
(21, 'Aliens', 'ALN', '1C2526', '6F2DA8', 1, 2, 'Olongapo', 'Invade the Game!', 'ZetaCom', NOW(), NOW()),
(22, 'Leopards', 'LEO', 'C8102E', 'FFD100', 1, 2, 'Legazpi', 'Stalk the Win!', 'Spotspeed', NOW(), NOW()),
(23, 'Sabertooths','SAB','00205B', 'A2AAAD', 1, 2, 'Lucena', 'Slash to Victory!', 'PrimalTech', NOW(), NOW()),
(24, 'Spiders', 'SPD', 'CE1141', '1D428A', 1, 2, 'Nueva Ecija', 'Weave the Win!', 'WebCore Labs', NOW(), NOW()),
(25, 'Vikings', 'VIK', '4F2683', 'FFC62F', 1, 2, 'Pampanga', 'Raid the Glory!', 'Drakkar Forge', NOW(), NOW()),
(26, 'Crows', 'CRW', '241F20', 'A2AAAD', 1, 2, 'Batangas City', 'Caw for Conquest!', 'BlackFeather Inc.', NOW(), NOW()),
(27, 'Royals',    'RYL', '004687', 'C5B358', 1, 2, 'Naga',         'Reign Supreme!',     'Regalia Corp.', NOW(), NOW()),
(28, 'Thunders',  'THN', '007AC1', 'F05133', 1, 2, 'Mindoro',      'Strike with Storm!', 'VoltEdge',      NOW(), NOW());

-- Visayas (Conference 3) - Initial 10
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(33, 'Waves', 'WAV', '002244', 'EFD9A1', 1, 3, 'Cebu City', 'Ride the Surge!', 'Wavefront Co.', NOW(), NOW()),
(34, 'Predators', 'PRD', '041E42', 'FFB81C', 1, 3, 'Iloilo City', 'Hunt the Glory!', 'PredaLabs', NOW(), NOW()),
(35, 'Trilogy', 'TRI', 'C8102E', '002B5C', 1, 3, 'Bacolod', 'Trio of Triumph!', 'TriSpark Inc.', NOW(), NOW()),
(36, 'Monarchs', 'MON', '5A2D81', 'D6AF36', 1, 3, 'Tacloban', 'Rule the Realm!', 'DynastiTech', NOW(), NOW()),
(37, 'Krakens', 'KRK', '001628', '99D9D9', 1, 3, 'Dumaguete', 'Unleash the Deep!', 'Abyss Corp.', NOW(), NOW()),
(38, 'Jets', 'JET', '003F2D', '78B833', 1, 3, 'Tagbilaran', 'Soar to Speed!', 'Jetline Systems', NOW(), NOW()),
(39, 'Northern Stars','NS','006847','A2AAAD',1,3,'Roxas City', 'Shine Unrivaled!', 'Polaris Edge', NOW(), NOW()),
(40, 'Ninjas', 'NIN', '000000', 'C4CED4', 1, 3, 'Ormoc', 'Strike from Shadows!', 'SilentEdge', NOW(), NOW()),
(41, 'Dragons', 'DRA', 'B5121B', 'F7B32B', 1, 3, 'Kalibo', 'Breathe the Fire!', 'Ignix Industries', NOW(), NOW()),
(42, 'Phoenix', 'PHO', '1D1160', 'E56020', 1, 3, 'Catarman', 'Rise from Ashes!', 'Rebirth Energy', NOW(), NOW()),
(43, 'Sharks',    'SHA', '006D75', 'A2AAAD', 1, 3, 'San Jose',     'Bite to Win!',       'Fintrek Co.',   NOW(), NOW()),
(44, 'Giants',    'GNT', '0B2265', 'A71930', 1, 3, 'Baybay',       'Tower Over All!',    'Goliath Works', NOW(), NOW());

-- Mindanao (Conference 4) - Initial 10
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(49, 'Pirates', 'PIR', 'FFB612', '34302B', 1, 4, 'Davao City', 'Plunder the Prize!', 'Skullwave', NOW(), NOW()),
(50, 'Scorpions', 'SCR', '8A4F00', 'FFD700', 1, 4, 'Zamboanga', 'Sting with Force!', 'Scorpex Systems', NOW(), NOW()),
(51, 'Golds', 'ENM', 'FFD700', '000000', 1, 4, 'Cagayan de Oro', 'Crush the Rivals!', 'Aurix Corp.', NOW(), NOW()),
(52, 'Reapers', 'RPR', '1B1B1B', 'D50000', 1, 4, 'General Santos', 'Harvest the Win!', 'Darkharvest Ltd.', NOW(), NOW()),
(53, 'Raiders', 'RAI', '000000', 'A5ACAF', 1, 4, 'Butuan', 'Seize the Victory!', 'BlackFlag Co.', NOW(), NOW()),
(54, 'Whales', 'WH', '005C5C', 'A1D6E2', 1, 4, 'Tagum', 'Dive to Dominate!', 'OceanCore', NOW(), NOW()),
(55, 'Poseidons', 'POS', '003B4F', '00B4D8', 1, 4, 'Surigao City', 'Command the Tides!', 'Trident Co.', NOW(), NOW()),
(56, 'Cyclones', 'CYC', '4B0082', 'FFD700', 1, 4, 'Cotabato City', 'Spin to Glory!', 'StormVibe Inc.', NOW(), NOW()),
(57, 'Force', 'FRC', '006BB6', 'ED174C', 1, 4, 'Zamboanga del Sur','Charge with Power!', 'CoreForce', NOW(), NOW()),
(58, 'Astronauts', 'AST', '1A1A40', 'F28C28', 1, 4, 'Lanao del Sur', 'Orbit to Victory!', 'Orbitek Systems', NOW(), NOW()),
(59, 'Demons',    'DMN', '0B162A', 'FF3C00', 1, 4, 'Iligan City',  'Unleash the Chaos!', 'Hellspawn Gear',NOW(), NOW()),
(60, 'Devils',    'DVL', 'D50A0A', '000000', 1, 4, 'Malaybalay',   'Burn with Passion!', 'Infernal Works',NOW(), NOW());
