-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2025 at 03:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `liga2`
--

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `acronym` varchar(10) NOT NULL,
  `city` varchar(255) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `primary_color` varchar(8) NOT NULL,
  `secondary_color` varchar(8) NOT NULL,
  `league_id` int(11) NOT NULL,
  `conference_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `acronym`, `city`, `description`, `primary_color`, `secondary_color`, `league_id`, `conference_id`, `created_at`, `updated_at`) VALUES
(1, 'Lions', 'LIO', 'Lionsgate', 'Hailing from the plains of Lionsgate, this team embodies the courage of pioneers who tamed the wilderness. Their gold/brown colors reflect the region’s wheat fields and ancient forests.', 'FFB612', '563512', 1, 1, NULL, NULL),
(2, 'Tigers', 'TIG', 'Junglecrest', 'Based in the tropical city of Junglecrest, the Tigers are feared for their agility. Local legends say the city was founded by survivors of a jungle expedition gone rogue.', 'FA4616', '2D2926', 1, 1, NULL, NULL),
(3, 'Bears', 'BEA', 'Frostmoor', 'The frostbitten city of Frostmoor rallies behind the Bears’ navy/orange colors, symbolizing the Northern Lights that illuminate their long winters.', '0B162A', 'C83803', 1, 1, NULL, NULL),
(4, 'Wolves', 'WOL', 'Ironhaven', 'Ironhaven’s Wolves thrive in the city’s steel-gray industrial sprawl. Their pack mentality mirrors the unity of its factory workers.', '2F4F4F', '696969', 1, 1, NULL, NULL),
(5, 'Eagles', 'EAG', 'Cliffspire', 'Perched on coastal cliffs, the Eagles of Cliffspire dominate the skies—and the league. Their green/silver honors the pine forests and foggy shores.', '004C54', 'A5ACAF', 1, 1, NULL, NULL),
(6, 'Falcons', 'FAL', 'Emberfall', 'Emberfall’s Falcons soar above volcanic plains. The red/black represents ash-streaked skies and the city’s fiery forge industry.', 'A71930', '2D2926', 1, 1, NULL, NULL),
(7, 'Hawks', 'HAW', 'Sunspire', 'The Hawks of Sunspire are named for the city’s golden-domed temples. Red/gold banners fly during their legendary summer tournaments.', 'E03A3E', 'FFD700', 1, 1, NULL, NULL),
(8, 'Panthers', 'PAN', 'Stormreach', 'Stormreach’s Panthers stalk the bayous outside the city. Blue/black evokes the stormy seas that once sank invaders’ ships.', '0085CA', '101820', 1, 1, NULL, NULL),
(9, 'Athletics', 'ATH', 'Greenmarch', 'Greenmarch’s Athletics team draws strength from the city’s rolling farmlands. Green/gold mirrors the wheat and sunsets of the agrarian heartland.', '003831', 'EFB21E', 1, 1, NULL, NULL),
(10, 'Vipers', 'VIP', 'Dustspire', 'The desert city of Dustspire fears no opponent—its Vipers blend into the military-green cacti forests, striking with lethal precision.', '4B5320', '8A9A5B', 1, 1, NULL, NULL),
(11, 'Jaguars', 'JAG', 'Azure Reef', 'Azure Reef’s Jaguars prowl the teal lagoons of this island city. Gold accents pay homage to pirate treasure rumored buried offshore.', '006778', 'D7A22A', 1, 1, NULL, NULL),
(12, 'Dolphins', 'DOL', 'Coral Bay', 'Coral Bay’s Dolphins are beloved by sailors and divers. Aqua/orange reflects the coral reefs and tropical sunrises.', '008E97', 'FC4C02', 1, 1, NULL, NULL),
(13, 'Rockets', 'RCK', 'Ironforge', 'The Rockets of Ironforge are powered by the city’s famed red iron ore. Gray accents honor the smog-choked skies of their industrial home.', 'CE1141', '808080', 1, 1, NULL, NULL),
(14, 'Braves', 'BRV', 'Stonewatch', 'Stonewatch’s Braves defend the city’s ancient stone monoliths. Navy/red symbolizes the night sky and blood spilled in legendary sieges.', '13274F', 'BA0C2F', 1, 1, NULL, NULL),
(15, 'Blazers', 'BLZ', 'Crimson Keep', 'The Blazers of Crimson Keep light up the league with red/black flair, mirroring the city’s obsidian towers and lava rivers.', 'E03A3E', '2D2926', 1, 1, NULL, NULL),
(16, 'Kings', 'KIN', 'Royal Hollow', 'Royal Hollow’s Kings rule from a valley of purple wildflowers. Taupe represents the clay used in the city’s iconic pottery trade.', '5A2D81', '8C7B6D', 1, 1, NULL, NULL),
(17, 'Titans', 'TIT', 'Tideport', 'Tideport’s Titans wear navy/sky blue for the churning ocean and clear skies. The city’s sailors swear the team is blessed by Poseidon.', '0C2340', '418FDE', 1, 2, NULL, NULL),
(18, 'Spartans', 'SPA', 'Mossdeep', 'Mossdeep’s Spartans train in the city’s ancient green marble arenas. Bronze trim recalls their warriors’ legendary armor.', '18453B', '897A68', 1, 2, NULL, NULL),
(19, 'Trojans', 'TRO', 'Scarlet Citadel', 'The Trojans of Scarlet Citadel are named for the city’s crimson-walled fortress. Gold banners fly after every victory.', '990000', 'FFD100', 1, 2, NULL, NULL),
(20, 'Saints', 'SNT', 'Gilded Sands', 'Gilded Sands’ Saints are pious yet fierce. Gold/brown reflects the desert dunes and the city’s opulent temple spires.', 'D3BC8D', '654321', 1, 2, NULL, NULL),
(21, 'Aliens', 'ALN', 'Neon Spire', 'The Aliens of Neon Spire are a mystery—their mint/black colors glow under the city’s perpetual auroras. Conspiracy theories abound.', '75E4B3', '2D2926', 1, 2, NULL, NULL),
(22, 'Leopards', 'LEO', 'Sundew Valley', 'Sundew Valley’s Leopards wear yellow/brown to blend into the savannahs surrounding this sun-baked farming hub.', 'FDB827', '8B4513', 1, 2, NULL, NULL),
(23, 'Sabertooths', 'SAB', 'Amberhold', 'Amberhold’s Sabertooths honor the orange/brown amber fossils found in the city’s cliffs—remnants of prehistoric predators.', 'FF5910', '654321', 1, 2, NULL, NULL),
(24, 'Spiders', 'SPD', 'Shadowfen', 'Shadowfen’s Spiders lurk in the city’s blackstone alleys. Dark red whispers of old blood spilled in its murky canals.', '363636', '8B0000', 1, 2, NULL, NULL),
(25, 'Vikings', 'VIK', 'Fjordheim', 'Fjordheim’s Vikings sail icy waters by day and dominate the league by night. Purple/gold mirrors the fjord sunsets.', '4F2683', 'FFC62F', 1, 2, NULL, NULL),
(26, 'Crows', 'CRW', 'Raven’s Perch', 'Raven’s Perch’s Crows are omens of victory. Black/slate symbolizes the city’s raven-shaped cliffs and stormy skies.', '2D2926', '708090', 1, 2, NULL, NULL),
(27, 'Royals', 'RYL', 'Crownhaven', 'Crownhaven’s Royals reign from a navy/gold palace atop a hill. The city’s wealth comes from its legendary gold mines.', '004687', 'BA8B02', 1, 2, NULL, NULL),
(28, 'Thunders', 'THN', 'Volantis', 'Volantis’ Thunders crackle with orange/navy energy, mimicking the city’s eternal lightning storms over its steel skyscrapers.', 'F05133', '002D62', 1, 2, NULL, NULL),
(29, 'Warriors', 'WAR', 'Ironcliff', 'Ironcliff’s Warriors defend the city’s blue/orange banners—a nod to the iron-rich cliffs and blazing forges below.', '1D428A', 'FF9E1B', 1, 2, NULL, NULL),
(30, 'Hellhounds', 'HH', 'Ashenport', 'Ashenport’s Hellhounds emerged from the city’s volcanic ruins. Red/dark red mirrors the lava flows that reshape the land.', 'BB0000', '4B0000', 1, 2, NULL, NULL),
(31, 'Red Fox', 'RF', 'Copperwood', 'Copperwood’s Red Fox team is sly and swift. Red/brown reflects the city’s autumn forests and copper-mining legacy.', 'CC092F', '654321', 1, 2, NULL, NULL),
(32, 'Cougars', 'CGR', 'Twilight Spire', 'Twilight Spire’s Cougars hunt under purple dusk skies. Bronze accents honor the city’s clocktower, which chimes at midnight.', '512888', '897A68', 1, 2, NULL, NULL),
(33, 'Waves', 'WAV', 'Mariner’s Reach', 'Mariner’s Reach’s Waves ride navy/gold tides into battle. The city’s sailors say storms calm when the team plays.', '004785', 'FFD700', 1, 3, NULL, NULL),
(34, 'Predators', 'PRD', 'Goldspire', 'Goldspire’s Predators stalk their prey in gold/blue regalia, reflecting the city’s opulent palaces and deep sapphire bay.', 'CEB888', '002E5D', 1, 3, NULL, NULL),
(35, 'Trilogy', 'TRI', 'Obsidian Veil', 'Obsidian Veil’s Trilogy team wears purple/indigo for the city’s three moons and the obsidian mines beneath its streets.', '702F8A', '4B0082', 1, 3, NULL, NULL),
(36, 'Monarchs', 'MON', 'Sunscar', 'Sunscar’s Monarchs rule a desert empire. Orange/navy symbolizes the endless dunes and the night sky guiding nomadic tribes.', 'FF671F', '002F6C', 1, 3, NULL, NULL),
(37, 'Krakens', 'KRK', 'Abyssal Port', 'Abyssal Port’s Krakens are feared like the sea monsters in local lore. Navy/cadet blue mirrors the crushing depths offshore.', '001628', '5F9EA0', 1, 3, NULL, NULL),
(38, 'Jets', 'JET', 'Skyhaven', 'Skyhaven’s Jets streak across blue/gray skies, named for the city’s aeronautical labs that birthed the first steam-powered planes.', '0038A8', '808080', 1, 3, NULL, NULL),
(39, 'Northern Stars', 'NS', 'Frostspire', 'Frostspire’s Northern Stars shine in navy/steel blue—colors of the city’s glacial fjords and the auroras above.', '00205B', '4682B4', 1, 3, NULL, NULL),
(40, 'Ninjas', 'NIN', 'Nightshade', 'Nightshade’s Ninjas move unseen in black/dark red. The city’s assassins guild allegedly funds the team.', '2D2926', '8B0000', 1, 3, NULL, NULL),
(41, 'Dragons', 'DRA', 'Dragon’s Aerie', 'Dragon’s Aerie’s team breathes fire in burgundy/gold. The city’s mountaintop arenas overlook valleys where dragons once nested.', '862633', 'D4AF37', 1, 3, NULL, NULL),
(42, 'Phoenix', 'PHO', 'Emberfall', 'Emberfall’s Phoenix rises from orange/dark red ashes, symbolizing the city’s rebirth after a cataclysmic wildfire.', 'E56020', '8B0000', 1, 3, NULL, NULL),
(43, 'Sharks', 'SHA', 'Tidal Keep', 'Tidal Keep’s Sharks patrol teal/military-green waters. The city’s floating arenas host games during high tide.', '006272', '4B5320', 1, 3, NULL, NULL),
(44, 'Giants', 'GNT', 'Cinderford', 'Cinderford’s Giants tower in orange/black, mirroring the city’s smoldering volcano and ash-covered streets.', 'FD5A1E', '2D2926', 1, 3, NULL, NULL),
(45, 'Fire', 'FRE', 'Blazewood', 'Blazewood’s Fire team burns red/dark orange, inspired by the city’s enchanted forests that glow at night.', 'C8102E', 'FF8C00', 1, 3, NULL, NULL),
(46, 'Patriots', 'PAT', 'Liberty Spire', 'Liberty Spire’s Patriots rally under navy/red—colors of the city’s revolutionary flag that sparked a continent’s freedom.', '002244', 'BA0C2F', 1, 3, NULL, NULL),
(47, 'Aces', 'ACE', 'Onyx Peaks', 'Onyx Peaks’ Aces dominate in black/slate, a nod to the city’s onyx mines and the card sharks in its underground casinos.', '2D2926', '708090', 1, 3, NULL, NULL),
(48, 'Monsters', 'MNT', 'Bloodstone Bay', 'Bloodstone Bay’s Monsters terrify in red/dark blue. The city’s crimson tides are said to be cursed by ancient sea gods.', 'DD0000', '00008B', 1, 3, NULL, NULL),
(49, 'Pirates', 'PIR', 'Blackwater', 'Blackwater’s Pirates sail black/gold ships, plundering rival teams like they plunder the city’s shadowy merchant fleets.', '27251F', 'D4AF37', 1, 4, NULL, NULL),
(50, 'Scorpions', 'SCR', 'Stinghollow', 'Stinghollow’s Scorpions strike red/black terror. The city sits atop venom mines, where rare poisons are traded in secret.', 'AF1E2D', '2D2926', 1, 4, NULL, NULL),
(51, 'Enemies', 'ENM', 'Ironmarch', 'Ironmarch’s Enemies wear silver/dark gray—colors of the city’s war machines and the smoke from its endless factories.', '8A8D8F', '454545', 1, 4, NULL, NULL),
(52, 'Reapers', 'RPR', 'Veilspire', 'Veilspire’s Reapers harvest victories in indigo/dark purple. The city’s cults whisper that the team sold their souls for power.', '43459B', '301934', 1, 4, NULL, NULL),
(53, 'Raiders', 'RAI', 'Obsidian Gate', 'Obsidian Gate’s Raiders wear black/gray to blend into the city’s volcanic rock walls. Their raids are as sudden as earthquakes.', '2D2926', '808080', 1, 4, NULL, NULL),
(54, 'Whales', 'WH', 'Azure Depths', 'Azure Depths’ Whales glide through sky blue/navy seas. The city’s glass-domed stadiums sit beneath the ocean’s surface.', '00A9E0', '000080', 1, 4, NULL, NULL),
(55, 'Poseidons', 'POS', 'Neptune’s Fall', 'Neptune’s Fall’s Poseidons rule teal/dodger blue waves. The city sank twice—and rose both times, like a tidal force.', '005C5C', '1E90FF', 1, 4, NULL, NULL),
(56, 'Cyclones', 'CYC', 'Crimson Gorge', 'Crimson Gorge’s Cyclones whirl red/black chaos. The city’s canyon winds are said to carry the screams of fallen rivals.', 'BA0C2F', '2D2926', 1, 4, NULL, NULL),
(57, 'Force', 'FRC', 'Emerald Reach', 'Emerald Reach’s Force wears green/olive for the city’s jungle militias. Their tactics are studied by generals worldwide.', '00843D', '9B870C', 1, 4, NULL, NULL),
(58, 'Astronauts', 'AST', 'Stellaris', 'Stellaris’ Astronauts don navy/gray, honoring the city’s spaceport where rockets launch nightly to colonize distant stars.', '0B3D91', '808080', 1, 4, NULL, NULL),
(59, 'Demons', 'DMN', 'Hellspire', 'Hellspire’s Demons thrive in dark red—a city built atop a hellmouth, where lava rivers light the stadiums at night.', '660000', '450000', 1, 4, NULL, NULL),
(60, 'Devils', 'DVL', 'Inferno Peak', 'Inferno Peak’s Devils play in red/black, as if the city’s volcano itself fuels their relentless, smoldering aggression.', 'CE1126', '2D2926', 1, 4, NULL, NULL),
(61, 'Bulldogs', 'BD', 'Ironjaw Keep', 'Ironjaw Keep’s Bulldogs fight in red/dark red, named for the city’s iron-jawed mastiffs that guard its vaults.', 'BA0C2F', '8B0000', 1, 4, NULL, NULL),
(62, 'Hornets', 'HRN', 'Hiveport', 'Hiveport’s Hornets swarm in purple/teal, mirroring the city’s bioluminescent hives and its infamous honeycomb slums.', '1D1160', '00788C', 1, 4, NULL, NULL),
(63, 'Rebels', 'RBL', 'Ashspire', 'Ashspire’s Rebels wear dark gray/maroon—colors of the city’s smoldering rebellion against a tyrannical empire.', '454545', '800000', 1, 4, NULL, NULL),
(64, 'Owls', 'OWL', 'Silent Grove', 'Silent Grove’s Owls hunt in sage/dark olive. The city’s druidic temples forbid noise during games, creating eerie silence.', '8F9779', '454B1B', 1, 4, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
