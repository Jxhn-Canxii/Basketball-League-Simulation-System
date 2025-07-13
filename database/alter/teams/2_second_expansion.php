-- NCR (Conference 1) – Add 4 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(13, 'Rockets',   'RCK', 'CE1141', 'C4CED4', 1, 1, 'Navotas',      'Launch to Greatness!','NovaLaunch',   NOW(), NOW()),
(14, 'Braves',    'BRV', '13274F', 'CE1141', 1, 1, 'Malabon',      'Charge with Courage!','Valor Systems',NOW(), NOW());

-- North (Conference 2) – Add 4 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(29, 'Warriors',  'WAR', '1D428A', 'FDB927', 1, 2, 'Kalinga',      'Battle to Win!',     'ValorForge',    NOW(), NOW()),
(30, 'Hellhounds','HH',  '1D1D1D', 'FF0000', 1, 2, 'Sorsogon',     'Unleash the Fury!',  'Inferno Systems',NOW(), NOW());

-- Visayas (Conference 3) – Add 4 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(45, 'Fire',      'FRE', '98002E', 'FDB927', 1, 3, 'Maasin',       'Ignite the Fight!',  'Pyrosys',       NOW(), NOW()),
(46, 'Patriots',  'PAT', '002244', 'C60C30', 1, 3, 'Cadiz',        'Defend the Pride!',  'NationCore',    NOW(), NOW());

-- Mindanao (Conference 4) – Add 4 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(61, 'Bulldogs',  'BD',  '003087', 'A3A3A3', 1, 4, 'Kidapawan',    'Bark and Bite!',     'Dogma Sportswear',NOW(), NOW()),
(62, 'Hornets',   'HRN', '1C2526', '00B2A9', 1, 4, 'Davao del Sur','Buzz to Victory!',   'HexaWasp',      NOW(), NOW());
