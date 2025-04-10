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
('Ghosts', 'GHS', 'Balanga', 'Bataan spectral figures haunting the peninsula\'s history', 'A9A9A9', '777777', 1, 2, NOW(), NOW()),
('Sonics', 'SNC', 'La Union', 'La Union sound warriors riding the surf\'s rhythm', '1E90FF', '003366', 1, 2, NOW(), NOW()),

-- Conference 3 (Visayas) - Updated with new cities
('Storms', 'STM', 'Sipalay', 'Negros tempestuous squalls sweeping sugar fields', '0000FF', 'B0C4DE', 1, 3, NOW(), NOW()),
('Devil Bats', 'DBT', 'Escalante', 'Northern Negros nocturnal flyers with sugarcane stealth', '8B0000', '000000', 1, 3, NOW(), NOW()),

-- Conference 4 (Mindanao) - New strategic locations
('Marlins', 'MRL', 'Koronadal', 'South Cotabato aquatic hunters thriving in Lake Sebu', '4682B4', 'FF69B4', 1, 4, NOW(), NOW()),
('Black Bulls', 'BBL', 'Gingoog', 'Misamis dark chargers with rainforest momentum', '000000', 'B8860B', 1, 4, NOW(), NOW());



INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES

-- Conference 1 (Luzon) - Moved from NCR due to city conflicts
('Rascals', 'RSC', 'Manila', 'Manila Rascals: A fast-paced, high-energy team bringing the relentless spirit of the streets of Metro Manila to the court.', 'FFCC00', '003366', 1, 1, NOW(), NOW()),
('Guardians', 'GRD', 'Rizal', 'Rizal Guardians: A team built on strength, courage, and resilience, embodying the warrior spirit of Rizal in every play.', '333399', 'CCCCCC', 1, 1, NOW(), NOW()),

-- Conference 2 (Luzon) - Original Luzon teams updated
('Kryptonites', 'KRY', 'Bulacan', 'Bulacan Kryptonites: Unstoppable forces on the court, embodying the power and might of the Kryptonite legend in every dunk and fast break.', '4B0082', 'FFD700', 1, 2, NOW(), NOW()),
('Razorbacks', 'VAL', 'Valenzuela', 'Valenzuela Razorbacks: A team with razor-sharp precision, cutting through defenses with speed and intensity.', '1E90FF', '003366', 1, 2, NOW(), NOW()),

-- Conference 3 (Visayas) - Updated with new cities
('Sky Express', 'SSE', 'Silay', 'Silay Sky Express: Soaring high above the competition with fast breaks and precision shooting, just like the winds over Negros.', '0000FF', 'B0C4DE', 1, 3, NOW(), NOW()),
('Ravens', 'MAN', 'Mandaue', 'Mandaue Ravens: Mysterious and elusive on the court, known for their quick reflexes, sharp defense, and clutch plays in the final seconds.', '8B0000', '000000', 1, 3, NOW(), NOW()),

-- Conference 4 (Mindanao) - New strategic locations
('Knights', 'SLU', 'Sulu', 'Sulu Knights: A team of fearless warriors who fight tooth and nail for every point, riding the tide of victory in the harshest of competitions.', '4682B4', 'FF69B4', 1, 4, NOW(), NOW()),
('Cobras', 'VAL', 'Valencia', 'Valencia Cobras: Known for their swift moves and deadly strikes, the Cobras take control of the game with their quickness and deadly accuracy.', '006400', 'FFD700', 1, 4, NOW(), NOW());
