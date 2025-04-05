INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES
-- Conference 1
('Bolts', 'BLT', 'Thunder City', 'Founded in 1982, the Bolts have built a legacy on speed and explosive plays.', '#FFCC00', '#000000', 1, 1, NOW(), NOW()),
('Knights', 'KNT', 'Valorburg', 'A storied franchise known for their disciplined defense and chivalrous spirit.', '#333399', '#CCCCCC', 1, 1, NOW(), NOW()),
('Razorbacks', 'RBK', 'Ironhill', 'Rising from a rugged mining town, the Razorbacks are known for their grit.', '#990000', '#666666', 1, 1, NOW(), NOW()),

-- Conference 2
('Ghosts', 'GHS', 'Phantom Bay', 'An elusive team born from the mist, the Ghosts have haunted opponents since 1995.', '#FFFFFF', '#777777', 1, 2, NOW(), NOW()),
('Sonics', 'SNC', 'Echo Point', 'Formed during the tech boom, the Sonics are innovators in high-speed play.', '#00CCFF', '#003366', 1, 2, NOW(), NOW()),
('Cowboys', 'CBY', 'Prairie Town', 'With roots in tradition, the Cowboys have roped in championships since the early days.', '#A0522D', '#FFD700', 1, 2, NOW(), NOW()),

-- Conference 3
('Marlins', 'MRL', 'Coral Coast', 'The Marlins surfaced as a powerhouse from the southern shores in 2001.', '#00BFFF', '#FF69B4', 1, 3, NOW(), NOW()),
('Robots', 'RBT', 'Neo City', 'Engineered for success, the Robots are a product of data-driven domination.', '#808080', '#00FF00', 1, 3, NOW(), NOW()),
('Freeze', 'FRZ', 'Glacier Falls', 'Since 1990, the Freeze have been known for their icy composure under pressure.', '#ADD8E6', '#00008B', 1, 3, NOW(), NOW()),

-- Conference 4
('Bad Boyz', 'BBZ', 'Rebel Town', 'Controversial yet beloved, the Bad Boyz have embraced their villain role since day one.', '#111111', '#FF0000', 1, 4, NOW(), NOW()),
('Strikers', 'STR', 'Goalville', 'A precision-driven team, the Strikers have made a name for their sharp offensive tactics.', '#FF4500', '#008000', 1, 4, NOW(), NOW()),
('Storms', 'STM', 'Tempest Bay', 'Forged in chaos, the Storms are known for unpredictable yet powerful plays.', '#0000FF', '#B0C4DE', 1, 4, NOW(), NOW());
