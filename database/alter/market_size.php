ALTER TABLE teams ADD COLUMN market_size INT DEFAULT 1;



UPDATE teams
SET market_size =
    CASE
        WHEN city IN (
            'Quezon City', 'Manila', 'Makati', 'Taguig', 'Pasig', 'Caloocan', 'Parañaque',
            'Mandaluyong', 'Pasay', 'Las Piñas', 'Marikina', 'Muntinlupa', 'Navotas', 'Malabon',
            'Valenzuela', 'Angeles City', 'Cebu City', 'Iloilo City', 'Bacolod', 'Mandaue',
            'Davao City', 'Zamboanga', 'General Santos'
        ) THEN 3

        WHEN city IN (
            'San Juan', 'Imus', 'Batangas City', 'Lucena', 'Pampanga', 'Olongapo', 'Naga',
            'Tarlac City', 'Tuguegarao', 'Dagupan', 'Cagayan de Oro', 'Malolos', 'Bulacan',
            'La Union', 'Bataan', 'Kalinga', 'Sorsogon', 'Tacloban', 'Tagbilaran', 'Roxas City',
            'Ormoc', 'Kalibo', 'Catarman', 'San Jose', 'Baybay', 'Sipalay', 'Escalante',
            'Silay', 'Oro', 'Butuan', 'Tagum', 'Pagadian City', 'Kidapawan City', 'Iligan City',
            'Malaybalay', 'Digos City', 'Dipolog City', 'Sultan Kudarat', 'Sarangani',
            'Basilan', 'Sulu', 'Valencia', 'Puerto Princesa', 'Malolos'
        ) THEN 2

        ELSE 1
    END;
