INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES
-- Conference 1
('Bolts', 'BLT', 'Doha', 'Founded in 1982, the Bolts from Doha strike fast and hard, mirroring the desert storms of Qatar.', 'FFCC00', '000000', 1, 1, NOW(), NOW()),
('Knights', 'KNT', 'Lahore', 'Rooted in tradition, the Lahore Knights uphold honor and resilience, inspired by centuries of history.', '333399', 'CCCCCC', 1, 1, NOW(), NOW()),
('Razorbacks', 'RBK', 'Peshawar', 'The Razorbacks of Peshawar fight with the fierce spirit of the frontier, forged in rugged terrain.', '990000', '666666', 1, 1, NOW(), NOW()),
('Cheetahs', 'CHT', 'Amman', 'The Cheetahs of Amman bring speed and agility, outrunning their opponents with lightning-fast plays.', 'FFD700', '800000', 1, 1, NOW(), NOW()),

-- Conference 2
('Ghosts', 'GHS', 'Muscat', 'From the misty coasts of Muscat, the Ghosts slip past opponents with haunting finesse.', 'A9A9A9', '777777', 1, 2, NOW(), NOW()),
('Sonics', 'SNC', 'Bengaluru', 'Powered by innovation, the Sonics of Bengaluru move at the speed of thought through tech-savvy tactics.', '1E90FF', '003366', 1, 2, NOW(), NOW()),
('Cowboys', 'CBY', 'Karachi', 'Karachi Cowboys ride into battle with grit and tradition, roping in victories with a classic flair.', 'A0522D', 'FFD700', 1, 2, NOW(), NOW()),
('Raptors', 'RPT', 'Chennai', 'The Raptors of Chennai are fierce and relentless, striking fear with their powerful offensive play.', 'FF6347', '000000', 1, 2, NOW(), NOW()),

-- Conference 3
('Marlins', 'MRL', 'Kochi', 'Surfacing from the Arabian Sea, the Marlins of Kochi strike with aquatic agility and tropical flair.', '4682B4', 'FF69B4', 1, 3, NOW(), NOW()),
('Robots', 'RBT', 'Dubai', 'Engineered for dominance, Dubai’s Robots blend AI precision with futuristic gameplay.', '808080', '2E8B57', 1, 3, NOW(), NOW()),
('Freeze', 'FRZ', 'Srinagar', 'From the frosty peaks of Srinagar, the Freeze remain calm and calculated, chilling opponents into submission.', 'ADD8E6', '00008B', 1, 3, NOW(), NOW()),
('Vultures', 'VLT', 'Karimabad', 'The Vultures of Karimabad soar above all, circling with sharp vision and striking when the moment is right.', 'FF4500', '4B0082', 1, 3, NOW(), NOW()),

-- Conference 4
('Bad Boyz', 'BBZ', 'Basra', 'Rebels from Basra, the Bad Boyz embrace their defiant identity with unapologetic aggression.', '111111', 'FF0000', 1, 4, NOW(), NOW()),
('Strikers', 'STR', 'Dhaka', 'The Dhaka Strikers are sharp and precise, delivering powerful shots and relentless attacks.', 'FF4500', '008000', 1, 4, NOW(), NOW()),
('Storms', 'STM', 'Colombo', 'Born where monsoons rage, the Storms of Colombo bring chaos and energy to every match.', '0000FF', 'B0C4DE', 1, 4, NOW(), NOW()),
('Black Bulls', 'BLK', 'Riyadh', 'The Black Bulls of Riyadh are an unstoppable force, charging through their competition with brute strength and willpower.', 'FFD700', '8B0000', 1, 4, NOW(), NOW());
