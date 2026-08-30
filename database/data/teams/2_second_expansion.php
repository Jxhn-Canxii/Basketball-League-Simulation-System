-- NCR (Conference 1) – Add 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Rockets',   'RCK', 'CE1141', 'C4CED4', 1, 1, 'Navotas',      'Launch to Greatness!', 'NovaLaunch',   NOW(), NOW()),
('Braves',    'BRV', '13274F', 'CE1141', 1, 1, 'Malabon',      'Charge with Courage!', 'Valor Systems',NOW(), NOW());

-- North (Conference 2) – Add 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Warriors',  'WAR', '1D428A', 'FDB927', 1, 2, 'Kalinga',      'Battle to Win!',      'ValorForge',    NOW(), NOW()),
('Hellhounds','HH',  '1D1D1D', 'FF0000', 1, 2, 'Sorsogon',     'Unleash the Fury!',   'Inferno Systems',NOW(), NOW());

-- Visayas (Conference 3) – Add 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Fire',      'FRE', '98002E', 'FDB927', 1, 3, 'Maasin',       'Ignite the Fight!',   'Pyrosys',       NOW(), NOW()),
('Patriots',  'PAT', '002244', 'C60C30', 1, 3, 'Cadiz',        'Defend the Pride!',   'NationCore',    NOW(), NOW());

-- Mindanao (Conference 4) – Add 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Bulldogs',  'BD',  '003087', 'A3A3A3', 1, 4, 'Dapitan City', 'Bark and Bite!',      'Dogma Sportswear',NOW(), NOW()),
('Hornets',   'HRN', '1C2526', '00B2A9', 1, 4, 'Digos City',   'Buzz to Victory!',    'HexaWasp',      NOW(), NOW());
