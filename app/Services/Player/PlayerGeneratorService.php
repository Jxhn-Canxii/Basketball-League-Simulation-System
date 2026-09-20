<?php

namespace App\Services\Player;

use Faker\Factory as Faker;
use Behat\Transliterator\Transliterator;
use Illuminate\Support\Str;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Helper\HelperService;

class PlayerGeneratorService
{
   protected $helper;

    public function __construct()
    {
        $this->helper = new HelperService();
    }
   
        // Add a player to a team with random attributes
    public function addPlayer($request)
    {
    
        // Check if the team already has 15 players
        $playerCount = Player::where('team_id', $request->team_id)
            ->where('is_active', 1) // Ensure players are active
            ->count();

        if ($playerCount >= 15) {
            return response()->json([
                'error' => true,
                'message' => 'Team already has 15 players. Cannot add more.',
            ], 400);
        }

        // Check if a player with the same name already exists in any team
        $existingPlayer = Player::where('name', $request->name)->first();
        if ($existingPlayer) {
            return response()->json([
                'error' => true,
                'message' => 'A player with this name already exists in another team.',
            ], 400);
        }

        // Generate random attributes
        $age = mt_rand(18, 25);
        $retirementAge = rand($age + 1, 45); // Retirement age should be greater than current age
        $injuryPronePercentage = rand(0, 100); // Random injury-prone percentage between 0 and 100
        $contractYears = rand(1, 5); // Random contract years between 1 and 5

        // Randomize player role
        $roles = ['starter', 'all star', 'star player', 'role player', 'bench'];
        $role = $roles[array_rand($roles)];

        // Randomize player ratings
        $shootingRating = rand(1, 100);
        $defenseRating = rand(1, 100);
        $passingRating = rand(1, 100);
        $reboundingRating = rand(1, 100);
        $overallRating = ($shootingRating + $defenseRating + $passingRating + $reboundingRating) / 4;

        // Calculate contract expiration date
        $contractExpiresAt = Carbon::now()->addYears($contractYears);

        $player = Player::create([
            'name' => $request->name,
            'team_id' => $request->team_id,
            'age' => $age,
            'retirement_age' => $retirementAge,
            'injury_prone_percentage' => $injuryPronePercentage,
            'contract_years' => $contractYears,
            'contract_expires_at' => $contractExpiresAt,
            'is_active' => true,
            'role' => $role,
            'shooting_rating' => $shootingRating,
            'defense_rating' => $defenseRating,
            'passing_rating' => $passingRating,
            'rebounding_rating' => $reboundingRating,
            'overall_rating' => $overallRating,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Player added successfully',
            'player' => $player,
        ]);
    }
    
    public function addFreeAgentPlayer($request)
    {
        $messages = [
            'name.regex' => 'The name must contain at least one vowel.',
            'name.max' => 'The name must not exceed 30 characters.',
        ];

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                'regex:/[aeiouAEIOU]/',
            ],
            'address' => 'required|string|max:255',
            'country' => 'required|string',
        ], $messages);

        $latestSeasonId = get_current_season_id();
        $currentSeasonId = $latestSeasonId ? (int) $latestSeasonId + 1 : 1;

        // Check if player already exists
        $finalName = $this->suffixNamingFormat($request->name);
        $existingPlayer = Player::where('name', $finalName)->first();

        if ($existingPlayer) {
            return response()->json([
                'error' => true,
                'message' => 'A player with this name already exists in another team.',
            ], 400);
        }

        // Generate player attributes
        $age = rand(18, 25);
        $contractYears = rand(1, 5);
        $attributes = $this->getRandomArchetypeAndAttributes();

        // Check generational limit
        if ($attributes['archetype'] === 'generational') {
            $generationalCount = Player::where('is_rookie', true)
                ->where('type', $attributes['archetype'])
                ->count();

            $maxGenLimit = ($currentSeasonId % 4 == 0) ? 30 : 15;
            if ($generationalCount >= $maxGenLimit) {
                return response()->json(['error' => 'Maximum limit of ' . $maxGenLimit . ' generational rookies reached for this season.'], 400);
            }
        }

        // 📌 Check and prioritize underfilled positions (including hybrids)
        $totalTeams = DB::table('teams')->count();
        $requiredPlayersPerPosition = $totalTeams * 5;
        // $requiredPlayersPerPosition = $currentSeasonId == 1 ? $requiredPlayersPerPosition : $totalTeams * 2;

        $corePositions = ['PG', 'SG', 'SF', 'PF', 'C'];
        $underfilledPositions = [];

        foreach ($corePositions as $corePos) {
            $count = DB::table('players')
                ->where(function ($query) use ($corePos) {
                    $query->where('position', 'like', $corePos)
                        ->orWhere('position', 'like', $corePos . '/%')
                        ->orWhere('position', 'like', '%/' . $corePos)
                        ->orWhere('position', 'like', '%/' . $corePos . '/%');
                })
                ->where('is_active', 1)
                ->where('is_injured', 0)
                ->count();

            if ($count < $requiredPlayersPerPosition) {
                $underfilledPositions[] = $corePos;
            }
        }

        // Assign position based on need
        if (!empty($underfilledPositions)) {
            $forcedCore = $underfilledPositions[array_rand($underfilledPositions)];
            $possibleHybrids = [
                'PG' => ['PG', 'PG/SG'],
                'SG' => ['SG', 'SG/SF', 'PG/SG'],
                'SF' => ['SF', 'SF/PF', 'SG/SF'],
                'PF' => ['PF', 'PF/C', 'SF/PF'],
                'C'  => ['C', 'PF/C'],
            ];
            $position = $possibleHybrids[$forcedCore][array_rand($possibleHybrids[$forcedCore])];
        } else {
            $position = $attributes['position'];
        }

        // Assign other attributes
        $selectedArchetype = $attributes['archetype'];
        $shootingRating = $attributes['shooting_rating'];
        $defenseRating = $attributes['defense_rating'];
        $passingRating = $attributes['passing_rating'];
        $reboundingRating = $attributes['rebounding_rating'];
        $athleticism = $attributes['athleticism_rating'];
        $basketballIq = $attributes['basketball_iq_rating'];
        $strength = $attributes['strength_rating'];
        $stamina = $attributes['stamina_rating'];
        $clutch = $attributes['clutch_rating'];
        $leadership = $attributes['leadership_rating'];
        $workEthic = $attributes['work_ethic_rating'];
        $twoPointRating = $attributes['two_point_rating'];
        $threePointRating = $attributes['three_point_rating'];
        $freeThrowRating = $attributes['free_throw_rating'];
        $injuryPercentage = $attributes['health_rating'];
        $healthRatings = 99 - $injuryPercentage;

        $overallRating = round((
            $defenseRating + $passingRating + $reboundingRating +
            $shootingRating
        ) / 4, 2);

        $potentialRating = 70;

        if ($overallRating >= 90) {
            $role = 'star player';
            $potentialRating = rand(96,99);
        } elseif ($overallRating >= 85 && $overallRating <= 89) {
            $role = 'all star';
            $potentialRating = rand(90, 95);
        } elseif ($overallRating >= 75 && $overallRating <= 84) {
            $role = 'starter';
            $potentialRating = rand(85, 95);
        } elseif ($overallRating >= 60 && $overallRating <= 74) {
            $role = 'role player';
            $potentialRating = rand(75, 90);
        } else {
            $role = 'bench';
            $potentialRating = rand(75,85);
        }

        $contractExpiresAt = Carbon::now()->addYears($contractYears);
        $minRetirementAge = max($age + 1, 35);
        $maxRetirementAge = 45 - (int)((99 - $healthRatings) / 5);
        $maxRetirementAge = max($minRetirementAge, $maxRetirementAge);
        $retirementAge = rand($minRetirementAge, $maxRetirementAge);

        // Save the player
        $player = Player::create([
            'name' => $finalName,
            'address' => $request->address,
            'country' => $request->country,
            'team_id' => 0,
            'age' => $age,
            'retirement_age' => $retirementAge,
            'injury_prone_percentage' => $injuryPercentage,
            'contract_years' => 0,
            'contract_expires_at' => $contractExpiresAt,
            'is_active' => true,
            'role' => $role,
            'position' => $position,
            'type' => $selectedArchetype,
            'shooting_rating' => $shootingRating,
            'defense_rating' => $defenseRating,
            'passing_rating' => $passingRating,
            'rebounding_rating' => $reboundingRating,
            'athleticism_rating' => $athleticism,
            'basketball_iq_rating' => $basketballIq,
            'strength_rating' => $strength,
            'stamina_rating' => $stamina,
            'clutch_rating' => $clutch,
            'leadership_rating' => $leadership,
            'work_ethic_rating' => $workEthic,
            'two_point_rating' => $twoPointRating,
            'three_point_rating' => $threePointRating,
            'free_throw_rating' => $freeThrowRating,
            'overall_rating' => $overallRating,
            'potential_rating' => $potentialRating,
            'draft_id' => $currentSeasonId,
            'draft_order' => 0,
            'drafted_team_id' => 0,
            'is_drafted' => 0,
            'draft_status' => 'Undrafted',
            'is_rookie' => true,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Player added successfully',
            'player' => $player,
        ]);
    }
    
    public function generateNewPlayer()
    {
        $doubleBarrelSurname = app('doubleBarrelSurname');
        $locale = $this->selectWeightedLocale();
        $faker = Faker::create($locale);
        $fakerUs = Faker::create('en_US');

        $firstNameRaw = $faker->firstNameMale;
        $doubleLastNameRaw = $doubleBarrelSurname->doubleBarreledSurname;
        $addressRaw = $fakerUs->address;
        $countryRaw = $fakerUs->country;

        // List of locales that may require transliteration (non-Latin scripts)
        $nonLatinLocales = ['zh_CN', 'ja_JP', 'ko_KR', 'ru_RU', 'ar_SA', 'th_TH'];

        // Function to check if a name needs transliteration (contains non-Latin characters)
        $needsTransliteration = function ($name) {
            return preg_match('/[^\x20-\x7E]/u', $name); // Detects non-ASCII characters
        };

        $lastNameChances = random_int(1, 100);

        if ($lastNameChances <= 85) {
            // 85% chance: Use regular last name
            $lastNameRaw = $faker->lastName;
            $name = "$firstNameRaw $lastNameRaw";
        } elseif ($lastNameChances <= 95) {
            // 10% chance: Modify an existing last name
            $lastNameRaw = $this->getLastName($faker);
            $name = "$firstNameRaw $lastNameRaw";
        } else {
            // 5% chance: Use a double last name
            $name = "$firstNameRaw $doubleLastNameRaw";
        }

        // Transliterate only if locale is non-Latin or name contains non-Latin characters
        if (in_array($locale, $nonLatinLocales) || $needsTransliteration($name)) {
            $name = Transliterator::transliterate($name);
            $name = Str::title(str_replace(['-', '_'], ' ', $name));
        }

        // Clean and format the name

        // Transliterate address and country (as they come from en_US, but clean anyway)
        $addressRaw = Transliterator::transliterate($addressRaw);
        $countryRaw = Transliterator::transliterate($countryRaw);

        // Clean and format address and country
        $name = Str::title($name); // Capitalize each word
        $address = Str::title(preg_replace('/[\-\_]+/', ' ', $addressRaw));
        $country = Str::title(str_replace(['-', '_'], ' ', $countryRaw));
        $address = preg_replace('/\s+/', ' ', trim($address));

        return response()->json([
            'name' => $name,
            'address' => $address,
            'country' => $country,
            'locale' => $locale,
        ]);
    }

    /**
     * Get a random archetype and its attributes.
     *
     * @return array
     */
    private function getRandomArchetypeAndAttributes()
    {
        $seasonId = get_current_season_id();  // Get latest season

        // Define archetypes with expanded attributes
        $archetypes = [
            'playmaker' => [
                'shooting' => [70, 85],
                'defense' => [65, 80],
                'passing' => [85, 99],
                'rebounding' => [60, 75],
                'athleticism' => [75, 90],
                'basketball_iq' => [85, 99],
                'strength' => [60, 75],
                'stamina' => [80, 95],
                'clutch' => [70, 90],
                'leadership' => [80, 95],
                'work_ethic' => [75, 90],
                'two_point_rating' => [75, 90],
                'three_point_rating' => [65, 80],
                'free_throw_rating' => [70, 85],
                'health_rating' => [0, 100]
            ],
            'defender' => [
                'shooting' => [60, 75],
                'defense' => [85, 99],
                'passing' => [60, 75],
                'rebounding' => [70, 85],
                'athleticism' => [70, 85],
                'basketball_iq' => [75, 90],
                'strength' => [75, 90],
                'stamina' => [75, 90],
                'clutch' => [65, 85],
                'leadership' => [70, 85],
                'work_ethic' => [80, 95],
                'two_point_rating' => [65, 80],
                'three_point_rating' => [55, 70],
                'free_throw_rating' => [60, 75],
                'health_rating' => [0, 100]
            ],
            'scorer' => [
                'shooting' => [85, 99],
                'defense' => [60, 75],
                'passing' => [65, 80],
                'rebounding' => [60, 75],
                'athleticism' => [80, 95],
                'basketball_iq' => [70, 85],
                'strength' => [65, 80],
                'stamina' => [75, 90],
                'clutch' => [85, 99],
                'leadership' => [70, 85],
                'work_ethic' => [70, 85],
                'two_point_rating' => [85, 99],
                'three_point_rating' => [80, 95],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ],
            'sharpshooter' => [
                'shooting' => [90, 99],
                'defense' => [55, 70],
                'passing' => [60, 75],
                'rebounding' => [50, 65],
                'athleticism' => [70, 85],
                'basketball_iq' => [80, 95],
                'strength' => [50, 65],
                'stamina' => [75, 90],
                'clutch' => [80, 95],
                'leadership' => [70, 85],
                'work_ethic' => [75, 90],
                'two_point_rating' => [75, 90],
                'three_point_rating' => [90, 99],
                'free_throw_rating' => [85, 99],
                'health_rating' => [0, 100]
            ],
            'big_man' => [
                'shooting' => [60, 75],
                'defense' => [75, 90],
                'passing' => [55, 70],
                'rebounding' => [85, 99],
                'athleticism' => [70, 85],
                'basketball_iq' => [75, 90],
                'strength' => [85, 99],
                'stamina' => [75, 90],
                'clutch' => [65, 80],
                'leadership' => [70, 85],
                'work_ethic' => [80, 95],
                'two_point_rating' => [80, 95],
                'three_point_rating' => [50, 65],
                'free_throw_rating' => [55, 70],
                'health_rating' => [0, 100]
            ],
            'generational' => [
                'shooting' => [95, 99],
                'defense' => [95, 99],
                'passing' => [95, 99],
                'rebounding' => [95, 99],
                'athleticism' => [95, 99],
                'basketball_iq' => [95, 99],
                'strength' => [95, 99],
                'stamina' => [95, 99],
                'clutch' => [95, 99],
                'leadership' => [95, 99],
                'work_ethic' => [95, 99],
                'two_point_rating' => [95, 99],
                'three_point_rating' => [95, 99],
                'free_throw_rating' => [95, 99],
                'health_rating' => [0, 100]
            ],
            'athletic_finisher' => [
                'shooting' => [70, 85],
                'defense' => [75, 90],
                'passing' => [60, 75],
                'rebounding' => [80, 95],
                'athleticism' => [90, 99],
                'basketball_iq' => [75, 90],
                'strength' => [80, 95],
                'stamina' => [80, 95],
                'clutch' => [75, 90],
                'leadership' => [70, 85],
                'work_ethic' => [80, 95],
                'two_point_rating' => [85, 99],
                'three_point_rating' => [60, 75],
                'free_throw_rating' => [70, 85],
                'health_rating' => [0, 100]
            ],
            'slasher' => [
                'shooting' => [75, 90],
                'defense' => [70, 85],
                'passing' => [60, 75],
                'rebounding' => [65, 80],
                'athleticism' => [90, 99],
                'basketball_iq' => [70, 85],
                'strength' => [75, 90],
                'stamina' => [75, 90],
                'clutch' => [70, 85],
                'leadership' => [60, 75],
                'work_ethic' => [75, 90],
                'two_point_rating' => [85, 99],
                'three_point_rating' => [65, 80],
                'free_throw_rating' => [70, 85],
                'health_rating' => [0, 100]
            ],
            'stretch_four' => [
                'shooting' => [80, 95],
                'defense' => [70, 85],
                'passing' => [65, 80],
                'rebounding' => [75, 90],
                'athleticism' => [75, 90],
                'basketball_iq' => [75, 90],
                'strength' => [70, 85],
                'stamina' => [75, 90],
                'clutch' => [75, 90],
                'leadership' => [70, 85],
                'work_ethic' => [75, 90],
                'two_point_rating' => [75, 90],
                'three_point_rating' => [85, 99],
                'free_throw_rating' => [75, 85],
                'health_rating' => [0, 100]
            ],
            'sixth_man' => [
                'shooting' => [75, 90],
                'defense' => [70, 85],
                'passing' => [65, 80],
                'rebounding' => [65, 80],
                'athleticism' => [80, 95],
                'basketball_iq' => [70, 85],
                'strength' => [65, 80],
                'stamina' => [75, 90],
                'clutch' => [85, 99],
                'leadership' => [70, 85],
                'work_ethic' => [80, 95],
                'two_point_rating' => [75, 90],
                'three_point_rating' => [70, 85],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ],
            'floor_general' => [
                'shooting' => [80, 90],
                'defense' => [70, 85],
                'passing' => [90, 99],
                'rebounding' => [60, 75],
                'athleticism' => [75, 90],
                'basketball_iq' => [90, 99],
                'strength' => [65, 80],
                'stamina' => [80, 95],
                'clutch' => [75, 90],
                'leadership' => [90, 99],
                'work_ethic' => [80, 95],
                'two_point_rating' => [80, 90],
                'three_point_rating' => [70, 85],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ],
            'rim_protector' => [
                'shooting' => [65, 80],
                'defense' => [90, 99],
                'passing' => [60, 75],
                'rebounding' => [85, 99],
                'athleticism' => [80, 95],
                'basketball_iq' => [85, 95],
                'strength' => [85, 99],
                'stamina' => [75, 90],
                'clutch' => [70, 85],
                'leadership' => [75, 90],
                'work_ethic' => [85, 95],
                'two_point_rating' => [75, 90],
                'three_point_rating' => [50, 65],
                'free_throw_rating' => [65, 80],
                'health_rating' => [0, 100]
            ],
            'point_forward' => [
                'shooting' => [75, 90],
                'defense' => [75, 90],
                'passing' => [85, 99],
                'rebounding' => [75, 90],
                'athleticism' => [80, 95],
                'basketball_iq' => [90, 99],
                'strength' => [75, 90],
                'stamina' => [80, 95],
                'clutch' => [80, 95],
                'leadership' => [85, 95],
                'work_ethic' => [80, 95],
                'two_point_rating' => [80, 95],
                'three_point_rating' => [70, 85],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ],
            'two_way_forward' => [
                'shooting' => [75, 90],
                'defense' => [85, 99],
                'passing' => [70, 85],
                'rebounding' => [80, 95],
                'athleticism' => [85, 95],
                'basketball_iq' => [85, 95],
                'strength' => [80, 95],
                'stamina' => [85, 95],
                'clutch' => [75, 90],
                'leadership' => [75, 90],
                'work_ethic' => [85, 95],
                'two_point_rating' => [80, 95],
                'three_point_rating' => [70, 85],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ],
            'post_scorer' => [
                'shooting' => [80, 95],
                'defense' => [75, 90],
                'passing' => [70, 85],
                'rebounding' => [80, 95],
                'athleticism' => [75, 90],
                'basketball_iq' => [85, 95],
                'strength' => [85, 99],
                'stamina' => [75, 90],
                'clutch' => [80, 95],
                'leadership' => [75, 90],
                'work_ethic' => [80, 95],
                'two_point_rating' => [85, 99],
                'three_point_rating' => [60, 75],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ],
            'paint_beast' => [
                'shooting' => [60, 75],
                'defense' => [85, 99],
                'passing' => [65, 80],
                'rebounding' => [90, 99],
                'athleticism' => [80, 95],
                'basketball_iq' => [80, 95],
                'strength' => [90, 99],
                'stamina' => [80, 95],
                'clutch' => [70, 85],
                'leadership' => [75, 90],
                'work_ethic' => [85, 95],
                'two_point_rating' => [80, 95],
                'three_point_rating' => [50, 65],
                'free_throw_rating' => [65, 80],
                'health_rating' => [0, 100]
            ],
            'versatile_big' => [
                'shooting' => [75, 90],
                'defense' => [80, 95],
                'passing' => [75, 90],
                'rebounding' => [85, 99],
                'athleticism' => [80, 95],
                'basketball_iq' => [85, 95],
                'strength' => [85, 95],
                'stamina' => [80, 95],
                'clutch' => [75, 90],
                'leadership' => [80, 95],
                'work_ethic' => [85, 95],
                'two_point_rating' => [80, 95],
                'three_point_rating' => [70, 85],
                'free_throw_rating' => [75, 90],
                'health_rating' => [0, 100]
            ]
        ];



        // Check if next season allows generational players
        $nextSeasonId = $seasonId + 1;
        if ($nextSeasonId % 4 === 0) {
            $archetypesToChooseFrom = array_merge($archetypes, [
                'generational' => $archetypes['generational']
            ]);
        } else {
            $archetypesToChooseFrom = $archetypes;
        }

        // Select archetype
        $archetypeKeys = array_keys($archetypesToChooseFrom);
        $selectedArchetype = $archetypeKeys[array_rand($archetypeKeys)];
        $archetypeAttributes = $archetypesToChooseFrom[$selectedArchetype];

        // Generate ratings

        $shooting = rand($archetypeAttributes['shooting'][0], $archetypeAttributes['shooting'][1]);
        $twoPoint = rand($archetypeAttributes['two_point_rating'][0], $archetypeAttributes['two_point_rating'][1]);
        $threePoint = rand($archetypeAttributes['three_point_rating'][0], $archetypeAttributes['three_point_rating'][1]);
        $freeThrow = rand($archetypeAttributes['free_throw_rating'][0], $archetypeAttributes['free_throw_rating'][1]);
        $defense = rand($archetypeAttributes['defense'][0], $archetypeAttributes['defense'][1]);
        $passing = rand($archetypeAttributes['passing'][0], $archetypeAttributes['passing'][1]);
        $rebounding = rand($archetypeAttributes['rebounding'][0], $archetypeAttributes['rebounding'][1]);
        $athleticism = rand($archetypeAttributes['athleticism'][0], $archetypeAttributes['athleticism'][1]);
        $basketballIq = rand($archetypeAttributes['basketball_iq'][0], $archetypeAttributes['basketball_iq'][1]);
        $strength = rand($archetypeAttributes['strength'][0], $archetypeAttributes['strength'][1]);
        $stamina = rand($archetypeAttributes['stamina'][0], $archetypeAttributes['stamina'][1]);
        $clutch = rand($archetypeAttributes['clutch'][0], $archetypeAttributes['clutch'][1]);
        $leadership = rand($archetypeAttributes['leadership'][0], $archetypeAttributes['leadership'][1]);
        $workEthic = rand($archetypeAttributes['work_ethic'][0], $archetypeAttributes['work_ethic'][1]);
        $healthRating = $this->generateHealthRating(); // i dont use arhetype health rating property hence i use probability

        // Assign position
        if ($selectedArchetype === 'generational') {
            // Generational players can be anything
            $hybrids = ['PG', 'SG', 'SF', 'PF', 'C', 'PG/SG', 'SG/SF', 'SF/PF', 'PF/C', 'PG/PF', 'PG/C', 'SG/C', 'SF/C', 'PG/SF'];
            $position = $hybrids[array_rand($hybrids)];
        } else {
            if ($passing >= 85) {
                if ($shooting >= 80) {
                    $position = 'PG/SG';
                } elseif ($defense >= 80) {
                    $position = 'PG/SF'; // crafty, defensive-minded PG
                } else {
                    $position = 'PG';
                }
            } elseif ($shooting >= 85) {
                if ($defense >= 75) {
                    $position = 'SG/SF';
                } elseif ($rebounding >= 70) {
                    $position = 'SG/PF'; // aggressive shooting forward
                } else {
                    $position = 'SG';
                }
            } elseif ($defense >= 85) {
                if ($rebounding >= 75) {
                    $position = 'SF/PF';
                } elseif ($passing >= 70) {
                    $position = 'SF/PG'; // defensive wing who can handle
                } else {
                    $position = 'SF';
                }
            } elseif ($rebounding >= 85) {
                if ($defense >= 75) {
                    $position = 'PF/C';
                } elseif ($passing >= 75) {
                    $position = 'PF/PG'; // rare playmaking forward
                } else {
                    $position = 'C';
                }
            } else {
                // Fallback for balanced or undeveloped players
                $hybrids = ['PG', 'SG', 'SF', 'PF', 'C', 'PG/SG', 'SG/SF', 'SF/PF', 'PF/C', 'SG/PF', 'SF/C', 'PG/SF'];
                $position = $hybrids[array_rand($hybrids)];
            }
        }

        return [
            'archetype' => $selectedArchetype,
            'position' => $position,
            'shooting_rating' => $shooting,
            'two_point_rating' => $twoPoint,
            'three_point_rating' => $threePoint,
            'free_throw_rating' => $freeThrow,
            'defense_rating' => $defense,
            'passing_rating' => $passing,
            'rebounding_rating' => $rebounding,
            'athleticism_rating' => $athleticism,
            'basketball_iq_rating' => $basketballIq,
            'strength_rating' => $strength,
            'stamina_rating' => $stamina,
            'clutch_rating' => $clutch,
            'leadership_rating' => $leadership,
            'work_ethic_rating' => $workEthic,
            'health_rating' => $healthRating,
        ];
    }

    private function selectWeightedLocale()
    {
        $locales = [
            'en_US',
            'en_GB',
            'fr_FR',
            'de_DE',
            'it_IT',
            'es_ES',
            'nl_NL',
            'pt_BR',
            'pt_PT',
            'tr_TR',
            'ru_RU',
            'ja_JP',
            'ko_KR',
            'zh_CN',
            'zh_TW',
            'hi_IN',
            'ar_SA',
            'sv_SE',
            'fi_FI',
            'no_NO',
            'da_DK',
            'pl_PL',
            'cs_CZ',
            'sk_SK',
            'ro_RO',
            'hu_HU',
            'el_GR',
            'he_IL',
            'id_ID',
            'ms_MY',
            'vi_VN',
            'en_PH',
            'bg_BG',
            'uk_UA',
            'ca_ES',
            'sl_SI',
            'hr_HR',
            'lt_LT',
            'lv_LV',
            'et_EE',
            'af_ZA',
            'sq_AL',
            'az_AZ',
            'be_BY',
            'bs_BA',
            'mk_MK',
            'sr_RS',
        ];

        // Define weights for each locale
        $weights = [];
        $usLocales = ['en_US', 'en_PH'];
        $europeanLocales = [
            'en_GB',
            'fr_FR',
            'de_DE',
            'it_IT',
            'es_ES',
            'nl_NL',
            'pt_PT',
            'sv_SE',
            'fi_FI',
            'no_NO',
            'da_DK',
            'pl_PL',
            'cs_CZ',
            'sk_SK',
            'ro_RO',
            'hu_HU',
            'el_GR',
            'ca_ES',
            'sl_SI',
            'hr_HR',
            'lt_LT',
            'lv_LV',
            'et_EE',
            'bg_BG',
            'uk_UA',
            'sq_AL',
            'bs_BA',
            'sr_RS'
        ];
        $asianAndOtherLocales = [
            'pt_BR',
            'tr_TR',
            'ru_RU',
            'ja_JP',
            'ko_KR',
            'zh_CN',
            'zh_TW',
            'hi_IN',
            'he_IL',
            'id_ID',
            'ms_MY',
            'vi_VN',
            'fa_IR',
            'af_ZA',
            'az_AZ',
            'be_BY',
            'mk_MK'
        ];

        // Assign weights: 40% for US, 50% for European, 10% for Asian/Others
        $totalLocales = count($locales);
        $usWeight = 0.6 / count($usLocales); // 60% split across US locales
        $europeanWeight = 0.35 / count($europeanLocales); // 35% split across European locales
        $asianOtherWeight = 0.05 / count($asianAndOtherLocales); // 5% split across Asian/Other locales

        foreach ($locales as $locale) {
            if (in_array($locale, $usLocales)) {
                $weights[$locale] = $usWeight;
            } elseif (in_array($locale, $europeanLocales)) {
                $weights[$locale] = $europeanWeight;
            } else {
                $weights[$locale] = $asianOtherWeight;
            }
        }

        // Perform weighted random selection
        $rand = mt_rand() / mt_getrandmax(); // Random float between 0 and 1
        $cumulativeWeight = 0;

        foreach ($weights as $locale => $weight) {
            $cumulativeWeight += $weight;
            if ($rand <= $cumulativeWeight) {
                return $locale;
            }
        }

        // Fallback to a random locale if something goes wrong
        return $locales[array_rand($locales)];
    }

    private function getLastName($faker)
    {

        $wordOptions = [
            // $faker->colorName,
            $faker->domainWord,
            $faker->citySuffix,
            $faker->streetSuffix,
        ];

        return collect($wordOptions)->random();
    }

    // This function generates a unique player name with suffixes if necessary
    private function suffixNamingFormat($name)
    {
        // Suffixes: Jr. and Roman numerals II to XX
        $suffixes = ['Jr.'];
        $romanNumerals = [
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
            13 => 'XIII',
            14 => 'XIV',
            15 => 'XV',
            16 => 'XVI',
            17 => 'XVII',
            18 => 'XVIII',
            19 => 'XIX',
            20 => 'XX'
        ];

        foreach ($romanNumerals as $roman) {
            $suffixes[] = $roman;
        }

        // Regex to remove suffix from name
        $pattern = '/\s(' . implode('|', array_map('preg_quote', $suffixes)) . ')$/';
        $baseName = preg_replace($pattern, '', trim($name));
        $finalName = $baseName;

        for ($i = 0; $i <= count($suffixes); $i++) {
            $existingPlayer = Player::where('name', $finalName)->first();

            if (!$existingPlayer) {
                return $finalName; // ✅ Return unique name
            }

            if ($i < count($suffixes)) {
                $finalName = $baseName . ' ' . $suffixes[$i];
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'A player with this name and all suffix variations (up to XX) already exists.',
                ], 400); // ❌ All suffixes used up
            }
        }
    }

    private function generateHealthRating()
    {
        $probabilityRanges = [
            [90, 100, 2],  // 2% chance
            [70, 89, 3],   // 3% chance
            [50, 69, 4],   // 4% chance
            [30, 49, 4],   // 4% chance
            [20, 29, 10],  // 10% chance
            [10, 19, 15],  // 15% chance
            [0, 9, 70],    // 70% chance
        ];

        $randomRoll = rand(1, 100);
        $sum = 0;

        foreach ($probabilityRanges as [$min, $max, $chance]) {
            $sum += $chance;
            if ($randomRoll <= $sum) {
                return rand($min, $max);
            }
        }

        return 0; // Fallback (should never happen)
    }
}
