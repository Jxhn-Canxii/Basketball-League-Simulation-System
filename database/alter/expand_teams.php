INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES


-- Conference 1 (Luzon) - Moved from NCR due to city conflicts
('Angels', 'ACA', 'Angeles City', 'Angeles City: Soaring High with Thunder and Grace', 'FFCC00', '003366', 1, 1, NOW(), NOW()),
('Knights', 'KNT', 'Malolos', 'Bulacan noble warriors with historical valor', '333399', 'CCCCCC', 1, 1, NOW(), NOW()),

-- Conference 2 (Luzon) - Original Luzon teams updated
('Ghosts', 'GHS', 'Bataan', 'Bataan spectral figures haunting the peninsula\'s history', 'A9A9A9', '777777', 1, 2, NOW(), NOW()),
('Sonics', 'SNC', 'La Union', 'La Union sound warriors riding the surf\'s rhythm', '1E90FF', '003366', 1, 2, NOW(), NOW()),

-- Conference 3 (Visayas) - Updated with new cities
('Storms', 'STM', 'Sipalay', 'Negros tempestuous squalls sweeping sugar fields', '0000FF', 'B0C4DE', 1, 3, NOW(), NOW()),
('Devil Bats', 'DBT', 'Escalante', 'Northern Negros nocturnal flyers with sugarcane stealth', '8B0000', '000000', 1, 3, NOW(), NOW()),

-- Conference 4 (Mindanao) - New strategic locations
('Marlins', 'MRL', 'Koronadal', 'South Cotabato aquatic hunters thriving in Lake Sebu', '4682B4', 'FF69B4', 1, 4, NOW(), NOW()),
('Black Bulls', 'BBL', 'Misamis', 'Misamis dark chargers with rainforest momentum', '000000', 'B8860B', 1, 4, NOW(), NOW());



INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES

-- Conference 1 (Luzon) - Moved from NCR due to city conflicts
('Rascals', 'RSC', 'Manila', 'Manila Rascals: A fast-paced, high-energy team embodying the relentless spirit of Metro Manila’s streets, known for their hustle and high-pressure gameplay.', 'FFCC00', 'FF5733', 1, 1, NOW(), NOW()),
('Guardians', 'GRD', 'Rizal', 'Rizal Guardians: A team built on strength, courage, and resilience, representing the warrior spirit of Rizal with each powerful drive to the basket.', '004B87', 'F1C40F', 1, 1, NOW(), NOW()),

-- Conference 2 (Luzon) - Original Luzon teams updated
('Kryptonites', 'KRY', 'Bulacan', 'Bulacan Kryptonites: Unstoppable on the court, embodying the power and resilience of Kryptonite, delivering explosive dunks and unstoppable fast breaks.', '3B8C3A', 'FFD700', 1, 2, NOW(), NOW()),
('Razorbacks', 'RAZ', 'Valenzuela', 'Valenzuela Razorbacks: A team with razor-sharp precision, cutting through defenses with speed, precision, and an aggressive offensive attack.', '8B0000', 'A52A2A', 1, 2, NOW(), NOW()),

-- Conference 3 (Visayas) - Updated with new cities
('Sky Express', 'SSE', 'Silay', 'Silay Sky Express: Soaring above the competition with lightning-fast breaks and pinpoint shooting, inspired by the winds over Negros.', '1E90FF', 'ADD8E6', 1, 3, NOW(), NOW()),
('Ravens', 'MAN', 'Mandaue', 'Mandaue Ravens: Mysterious and elusive, known for their quick reflexes, lockdown defense, and clutch performances when the game is on the line.', '000000', '800080', 1, 3, NOW(), NOW()),

-- Conference 4 (Mindanao) - New strategic locations
('Steels', 'SLU', 'Sulu', 'Sulu Steels: Fearless warriors who fight for every point, bringing strength and resilience to each game, determined to conquer the competition.', '4682B4', 'A9A9A9', 1, 4, NOW(), NOW()),
('Cobras', 'VLC', 'Valencia', 'Valencia Cobras: Known for their swift moves and deadly strikes, controlling the game with quickness, precision shooting, and defensive prowess.', '006400', 'FFD700', 1, 4, NOW(), NOW());
