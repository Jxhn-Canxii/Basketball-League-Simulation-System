INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `sponsor`,
    `created_at`, `updated_at`
) VALUES
-- Conference 1 (Luzon)
('Angels',          'ACA', 'Angeles City',     'Soar with Grace!',       'FFCC00', '003366', 1, 1, 'AngelCore',         NOW(), NOW()),
('Knights',         'KNT', 'Malolos',          'Charge with Valor!',     '333399', 'CCCCCC', 1, 1, 'ValorArmory',       NOW(), NOW()),
('Rascals',         'RSC', 'West Manila',      'Hustle with Heart!',     'FFCC00', 'FF5733', 1, 1, 'RogueMotion',       NOW(), NOW()),
('Stingers',        'PTS', 'Pateros',          'Small Team. Big Sting.', 'FFD700', '1A1A1A', 1, 1, 'NanoSting',         NOW(), NOW()),

-- Conference 2 (Luzon)
('Ghosts',          'GHS', 'Bataan',           'Haunt the Court!',       'A9A9A9', '777777', 1, 2, 'PhantomWare',       NOW(), NOW()),
('Sonics',          'SNC', 'La Union',         'Ride the Rhythm!',       '1E90FF', '003366', 1, 2, 'EchoDrive',         NOW(), NOW()),
('Kryptonites',     'KRY', 'Bulacan',          'Shine Unstoppable!',     '3B8C3A', 'FFD700', 1, 2, 'GreenStar Labs',    NOW(), NOW()),
('Crimson Arrows',  'ICA', 'Imus',             'Bleed for the Mark!',    '8B0000', 'FFD700', 1, 2, 'ArrowForge',        NOW(), NOW()),

-- Conference 3 (Visayas)
('Storms',          'STM', 'Sipalay',          'Unleash the Tempest!',   '0000FF', 'B0C4DE', 1, 3, 'TempestCore',       NOW(), NOW()),
('Elites',          'ECE', 'Escalante',        'Rise to Excellence!',    '8B0000', '000000', 1, 3, 'PrimeWorks',        NOW(), NOW()),
('Sky Express',     'SSE', 'Silay',            'Soar to Victory!',       '1E90FF', 'ADD8E6', 1, 3, 'AeroGlide',         NOW(), NOW()),
('Ravens',          'MAN', 'Mandaue',          'Strike with Stealth!',   '000000', '800080', 1, 3, 'ShadowNest',        NOW(), NOW()),

-- Conference 4 (Mindanao)
('Marlins',         'MRL', 'Koronadal',        'Swim to Triumph!',       '4682B4', 'FF69B4', 1, 4, 'WaveRider Inc.',    NOW(), NOW()),
('Black Bulls',     'BBB', 'Basilan',          'Charge with Force!',     '000000', 'B8860B', 1, 4, 'IronHerd',          NOW(), NOW()),
('Steels',          'SLU', 'Sulu',             'Forge the Win!',         '4682B4', 'A9A9A9', 1, 4, 'SteelPoint',        NOW(), NOW()),
('Cobras',          'VLC', 'Valencia',         'Strike with Precision!', '006400', 'FFD700', 1, 4, 'StrikeLine Tech',   NOW(), NOW());
