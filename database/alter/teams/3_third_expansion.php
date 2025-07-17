-- NCR (Conference 1) – Final 2 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(15, 'Blazers',   'BLZ', 'E03A3E', '000000', 1, 1, 'Marikina',     'Blaze the Trail!',   'Fireline',      NOW(), NOW()),
(16, 'Kings',     'KIN', '5A2D81', '63727A', 1, 1, 'Muntinlupa',   'Rule the Court!',    'CrownOne',      NOW(), NOW());

-- North (Conference 2) – Final 2 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(31, 'Red Fox',   'RF',  '5B2B2F', 'FFB612', 1, 2, 'Tuguegarao',   'Outfox the Foe!',    'FoxShift Labs', NOW(), NOW()),
(32, 'Cougars',   'CGR', '003B4D', '007C92', 1, 2, 'Puerto Princesa', 'Pounce to Power!', 'CougaCore',     NOW(), NOW());

-- Visayas (Conference 3) – Final 2 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(47, 'Aces',      'ACE', 'B4975A', '000000', 1, 3, 'Bogo',         'Ace the Game!',      'AceLine Sports',NOW(), NOW()),
(48, 'Monsters',  'MNT', '6F263D', 'FDBB30', 1, 3, 'Guihulngan',   'Unleash the Beast!', 'BeastMode Inc.',NOW(), NOW());

-- Mindanao (Conference 4) – Final 2 Teams
INSERT INTO `teams` (`id`, `name`, `acronym`, `primary_color`, `secondary_color`,`league_id`, `conference_id`,`city`, `description`, `sponsor`, `created_at`, `updated_at`) VALUES
(63, 'Rebels',    'RBL', 'EF0107', '1E1E2F', 1, 4, 'Koronadal','Defy and Conquer!', 'FreeFront Labs',NOW(), NOW()),
(64, 'Owls',      'OWL', '0C2340', 'C8102E', 1, 4, 'Dipolog City','Hunt by Night!',  'NoctuaTech',    NOW(), NOW());
