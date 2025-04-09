INSERT INTO `teams` (
    `name`, `acronym`, `city`, `description`, 
    `primary_color`, `secondary_color`, 
    `league_id`, `conference_id`, 
    `created_at`, `updated_at`
) VALUES


-- Conference 1 (Luzon) - Moved from NCR due to city conflicts
('Bolts', 'BLT', 'Angeles', 'Pampanga thunderbolts electrifying Clark\'s skies', 'FFCC00', '000000', 1, 1, NOW(), NOW()),
('Knights', 'KNT', 'Malolos', 'Bulacan noble warriors with historical valor', '333399', 'CCCCCC', 1, 1, NOW(), NOW()),
('Razorbacks', 'RBK', 'Antipolo', 'Rizal rugged hogs charging through mountainous terrain', '990000', '666666', 1, 1, NOW(), NOW()),

-- Conference 2 (Luzon) - Original Luzon teams updated
('Ghosts', 'GHS', 'Balanga', 'Bataan spectral figures haunting the peninsula\'s history', 'A9A9A9', '777777', 1, 2, NOW(), NOW()),
('Sonics', 'SNC', 'San Fernando (LU)', 'La Union sound warriors riding the surf\'s rhythm', '1E90FF', '003366', 1, 2, NOW(), NOW()),
('Cowboys', 'CBY', 'Lipa', 'Batangas rodeo riders with coffee-fueled energy', 'A0522D', 'FFD700', 1, 2, NOW(), NOW()),

-- Conference 3 (Visayas) - Updated with new cities
('Strikers', 'STR', 'Borongan', 'Eastern Visayas swift attackers with coastal agility', 'FF4500', '008000', 1, 3, NOW(), NOW()),
('Storms', 'STM', 'Sipalay', 'Negros tempestuous squalls sweeping sugar fields', '0000FF', 'B0C4DE', 1, 3, NOW(), NOW()),
('Devil Bats', 'DBT', 'Escalante', 'Northern Negros nocturnal flyers with sugarcane stealth', '8B0000', '000000', 1, 3, NOW(), NOW()),

-- Conference 4 (Mindanao) - New strategic locations
('Marlins', 'MRL', 'Koronadal', 'South Cotabato aquatic hunters thriving in Lake Sebu', '4682B4', 'FF69B4', 1, 4, NOW(), NOW()),
('Robots', 'RBT', 'Ozamiz', 'Misamis mechanical giants with portside precision', '808080', '2E8B57', 1, 4, NOW(), NOW()),
('Black Bulls', 'BBL', 'Gingoog', 'Misamis dark chargers with rainforest momentum', '000000', 'B8860B', 1, 4, NOW(), NOW());