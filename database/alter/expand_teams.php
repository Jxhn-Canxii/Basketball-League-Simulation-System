INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES
-- Conference 1 (Philippines)
('Bolts', 'BLT', 'Dagupan', 'Pangasinan powerhouse bringing electric plays with milkfish energy', 'FFCC00', '000000', 1, 1, NOW(), NOW()),
('Knights', 'KNT', 'Batangas', 'Barako stronghold defenders with coffee-powered intensity', '333399', 'CCCCCC', 1, 1, NOW(), NOW()),
('Razorbacks', 'RBK', 'Butuan', 'Agusan warriors with timber strength and riverside tenacity', '990000', '666666', 1, 1, NOW(), NOW()),

-- Conference 2 (China and Taiwan)
('Ghosts', 'GHS', 'Tianjin', 'Port city phantoms with maritime stealth and industrial might', 'A9A9A9', '777777', 1, 2, NOW(), NOW()),
('Sonics', 'SNC', 'Suzhou', 'Garden city innovators with classical precision plays', '1E90FF', '003366', 1, 2, NOW(), NOW()),
('Cowboys', 'CBY', 'Taoyuan', 'Airport city riders with aerospace determination', 'A0522D', 'FFD700', 1, 2, NOW(), NOW()),

-- Conference 3 (Thailand, Indonesia, Malaysia, Vietnam)
('Bad Boyz', 'BBZ', 'Medan', 'Sumatra rebels with volcanic attitude', '111111', 'FF0000', 1, 3, NOW(), NOW()),
('Strikers', 'STR', 'Hat Yai', 'Southern Thai attackers with rubber city bounce', 'FF4500', '008000', 1, 3, NOW(), NOW()),
('Storms', 'STM', 'Kota Kinabalu', 'Borneo tempest with Mount Kinabalu strength', '0000FF', 'B0C4DE', 1, 3, NOW(), NOW()),

-- Conference 4 (Japan and Korea)
('Marlins', 'MRL', 'Sendai', 'Tohoku sea hunters with samurai spirit', '4682B4', 'FF69B4', 1, 4, NOW(), NOW()),
('Robots', 'RBT', 'Ulsan', 'Industrial automatons with shipyard strength', '808080', '2E8B57', 1, 4, NOW(), NOW()),
('Freeze', 'FRZ', 'Saitama', 'Super Arena ice warriors with suburban power', 'ADD8E6', '00008B', 1, 4, NOW(), NOW());
