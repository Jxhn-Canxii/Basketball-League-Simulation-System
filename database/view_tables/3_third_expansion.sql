-- NCR (Conference 1) – Final 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Blazers',   'BLZ', 'E03A3E', '000000', 1, 1, 'Marikina',     'Blaze the Trail!',   'Fireline',      NOW(), NOW()),
('Kings',     'KIN', '5A2D81', '63727A', 1, 1, 'Muntinlupa',   'Rule the Court!',    'CrownOne',      NOW(), NOW());

-- North (Conference 2) – Final 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Red Fox',   'RF',  '5B2B2F', 'FFB612', 1, 2, 'Tuguegarao',   'Outfox the Foe!',    'FoxShift Labs', NOW(), NOW()),
('Cougars',   'CGR', '003B4D', '007C92', 1, 2, 'Puerto Princesa', 'Pounce to Power!', 'CougaCore',     NOW(), NOW());

-- Visayas (Conference 3) – Final 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Aces',      'ACE', 'B4975A', '000000', 1, 3, 'Bogo',         'Ace the Game!',      'AceLine Sports',NOW(), NOW()),
('Monsters',  'MNT', '6F263D', 'FDBB30', 1, 3, 'Guihulngan',   'Unleash the Beast!', 'BeastMode Inc.',NOW(), NOW());

-- Mindanao (Conference 4) – Final 2 Teams
INSERT INTO `teams` (`name`, `acronym`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
('Typhoons',  'TYP', '0077BE', '1E1E2F', 1, 4, 'Sultan Kudarat', 'The Eye of the South', 'Bagyo Energy Corp', NOW(), NOW()),
('Owls',      'OWL', '0C2340', 'C8102E', 1, 4, 'Dipolog City', 'Hunt by Night!',  'NoctuaTech',    NOW(), NOW());
