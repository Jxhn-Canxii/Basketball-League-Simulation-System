<?php

namespace App\Services\Game;

ini_set('max_execution_time', 0); // Unlimited execution time

use Illuminate\Http\Request;
use App\Services\Helper\HelperService;
use App\Services\League\NewsService;
use App\Services\Player\FreeAgencyService;
use App\Services\Stats\PlayerCareerStatsService;
use App\Services\Stats\PlayoffStatsService;
use App\Services\Game\PlayerStatsService;
use App\Services\Team\TeamChemistryService;
use App\Services\Team\TeamManagementService;
use App\Services\Team\TeamStreakService;
use Illuminate\Support\Facades\DB;

class PlayByPlayService
{
    protected $teamManagement;
    protected $playerStats;
    protected $helper;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->teamManagement = new TeamManagementService();
        $this->playerStats = new PlayerStatsService();
        $this->helper = new HelperService();
    }

    public function statsEngine($currentSeasonId, $gameData, $player, $playerMinutes, $chemistry)
    {
        $minutes = (int) $playerMinutes[$player->id];

        $performanceFactor = $this->playerStats->calculatePerformanceFactor($player);
        $defensiveImpact =  $this->playerStats->calculateDefensiveImpact($gameData->away_team_id);

        $turnovers =  $this->playerStats->calculateTurnOver($player, $minutes, $performanceFactor, $defensiveImpact);
        $fouls =  $this->playerStats->calculateFoul($player, $minutes, $performanceFactor, $defensiveImpact);

        $totalFouls = $player->last_quarter_fouls + $fouls;
        $isPlayerOut = $totalFouls > 5 ? 1 : 0;

        //set is foul out to true
        $player->is_fouled_out = $isPlayerOut;

        if ($isPlayerOut == 1 || $minutes == 0 || $player->is_injured == 1 || $player->is_fouled_out == 1 || $player->is_reserved == 1) {
            $playerGameStats = $this->playerStats->createInactivePlayerStats($player, $gameData, $currentSeasonId);
        }
        else{
            $shotStats =  $this->playerStats->calculateShotAttempts($player, $minutes, $defensiveImpact, $fouls, $turnovers, $chemistry, true, false);

            // Assign returned values to variables
            $twoPointAttempts = $shotStats['two_point_attempts'];
            $twoPointMade = $shotStats['two_point_made'];

            $threePointAttempts = $shotStats['three_point_attempts'];
            $threePointMade = $shotStats['three_point_made'];

            $freeThrowAttempts = $shotStats['free_throw_attempts'];
            $freeThrowMade = $shotStats['free_throw_made'];


            $points =  $this->playerStats->calculatePoints($player, $twoPointMade, $threePointMade, $freeThrowMade, $fouls);

            // Simulate other stats
            $rebounds =  $this->playerStats->calculateRebounds($player, $minutes, $performanceFactor, $fouls);
            $blocks =  $this->playerStats->calculateBlocks($player, $minutes, $performanceFactor, $fouls);
            $steals =  $this->playerStats->calculateSteals($player, $minutes, $performanceFactor, $fouls);

        
            $playerGameStats = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => $player->is_injured,
                'role' => $player->role,
                'points' => $points,
                'rebounds' => $rebounds,
                'assists' => 0, // Temporary value
                'steals' => $steals,
                'blocks' => $blocks,
                'turnovers' => $turnovers,
                'fouls' => $fouls,
                'minutes' => $minutes,
                'field_goal_attempts' => $twoPointAttempts + $threePointAttempts,
                'field_goals_made' => $twoPointMade + $threePointMade,
                'three_point_attempts' => $threePointAttempts,
                'three_pointers_made' => $threePointMade,
                'two_pointers_made' => $twoPointMade,
                'two_point_attempts' => $twoPointAttempts,
                'free_throw_attempts' => $freeThrowAttempts,
                'free_throws_made' => $freeThrowMade,
            ];
        }

        $this->teamManagement->fatigueRate($player, $minutes, $gameData->game_id);

        return $playerGameStats;

    }

    /**
     * Simulate one complete quarter.
     *
     * Returns:
     *
     * [
     *     'stats' => [...],
     *     'events' => [...],
     *     'home_points' => 0,
     *     'away_points' => 0,
     * ]
     */
    public function simulateQuarterPossessions(
        $currentSeasonId,
        $gameData,
        $homePlayers,
        $awayPlayers,
        $homeMinutes,
        $awayMinutes,
        $quarter,
        float $homeChemistry = 75,
        float $awayChemistry = 75,
        int $possessions = 115
    ): array {

        $allPlayers = array_merge($homePlayers->toArray(),$awayPlayers->toArray());
        // dd($allPlayers);
        /*
         * Initialize every player once.
         */
        $stats = [];

        foreach ($allPlayers as $player) {
            $stats[$player->id] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => (int) ($player->is_injured ?? 0),
                'role' => $player->role,
                'points' => 0,
                'rebounds' => 0,
                'assists' => 0,
                'steals' => 0,
                'blocks' => 0,
                'turnovers' => 0,
                'fouls' => 0,
                'minutes' => 0,
                'field_goal_attempts' => 0,
                'field_goals_made' => 0,
                'three_point_attempts' => 0,
                'three_pointers_made' => 0,
                'two_point_attempts' => 0,
                'two_pointers_made' => 0,
                'free_throw_attempts' => 0,
                'free_throws_made' => 0,
                'is_fouled_out' => (int) ($player->is_fouled_out ?? 0),
            ];
        }

        /*
         * Minutes are already determined by your existing
         * distributeMinutes() system.
         */
        foreach ($homeMinutes as $playerId => $minutes) {
            if (isset($stats[$playerId])) {
                $stats[$playerId]['minutes'] = (int) $minutes;
            }
        }

        foreach ($awayMinutes as $playerId => $minutes) {
            if (isset($stats[$playerId])) {
                $stats[$playerId]['minutes'] = (int) $minutes;
            }
        }

        /*
         * Cache performance once per player per quarter.
         *
         * DO NOT call calculatePerformanceFactor()
         * for every possession.
         */
        $performance = [];

        foreach ($allPlayers as $player) {
            $performance[$player->id] =
                $this->playerStats->calculatePerformanceFactor(
                    $player,
                    false
                );
        }

        /*
         * Defensive impact is calculated from the loaded
         * players, not from the database.
         */
        $homeDefense =
            $this->calculateRosterDefense($homePlayers);

        $awayDefense =
            $this->calculateRosterDefense($awayPlayers);

        /*
         * Precalculate player weights.
         */
        $homeWeights = $this->buildOffensiveWeights(
            $homePlayers,
            $homeMinutes
        );

        $awayWeights = $this->buildOffensiveWeights(
            $awayPlayers,
            $awayMinutes
        );

        /*
         * Fouls carried into this quarter.
         */
        $fouls = [];

        foreach ($allPlayers as $player) {
            $fouls[$player->id] =
                (int) ($player->last_quarter_fouls ?? 0);
        }

        $events = [];

        $homePoints = 0;
        $awayPoints = 0;

        /*
         * Start with home possession.
         */
        $offenseIsHome = true;

        /*
         * A small clock representation.
         *
         * It is not used to determine statistics.
         * It simply gives play-by-play a believable clock.
         */
        $clock = 60 * 60;

        for ($possession = 1; $possession <= $possessions; $possession++) {

            $offensePlayers = $offenseIsHome
                ? $homePlayers
                : $awayPlayers;

            $defensePlayers = $offenseIsHome
                ? $awayPlayers
                : $homePlayers;

            $offenseWeights = $offenseIsHome
                ? $homeWeights
                : $awayWeights;

            $offensivePlayer =
                $this->pickAvailablePlayer(
                    $offensePlayers,
                    $offenseWeights,
                    $stats
                );

            if (!$offensivePlayer) {
                $offenseIsHome = !$offenseIsHome;
                continue;
            }

            $defensivePlayer =
                $this->pickDefender(
                    $defensePlayers,
                    $offensivePlayer,
                    $stats
                );

            if (!$defensivePlayer) {
                $offenseIsHome = !$offenseIsHome;
                continue;
            }

            $defensiveImpact = $offenseIsHome
                ? $awayDefense
                : $homeDefense;

            $chemistry = $offenseIsHome
                ? $homeChemistry
                : $awayChemistry;

            $performanceFactor =
                $performance[$offensivePlayer->id] ?? 1.0;

            /*
             * -------------------------------------------------
             * 1. TURNOVER
             * -------------------------------------------------
             */
            $turnoverProbability =
                $this->playerStats->possessionTurnoverProbability(
                    $offensivePlayer,
                    $defensivePlayer,
                    $defensiveImpact,
                    $performanceFactor
                );

            if (
                $this->playerStats->possessionChance(
                    $turnoverProbability
                )
            ) {
                $stats[$offensivePlayer->id]['turnovers']++;

                /*
                 * Credit steal to defender sometimes.
                 */
                $stealProbability =
                    $this->stealProbability(
                        $defensivePlayer,
                        $offensivePlayer
                    );

                if (
                    $this->playerStats->possessionChance(
                        $stealProbability
                    )
                ) {
                    $stats[$defensivePlayer->id]['steals']++;

                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $offensivePlayer->team_id,
                            $offensivePlayer->id,
                            $defensivePlayer->id,
                            'steal',
                            0,
                            null,
                            false,
                            "{$defensivePlayer->name} steals the ball from {$offensivePlayer->name}."
                        );
                } else {
                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $offensivePlayer->team_id,
                            $offensivePlayer->id,
                            null,
                            'turnover',
                            0,
                            null,
                            false,
                            "{$offensivePlayer->name} turns the ball over."
                        );
                }

                $clock = max(
                    0,
                    $clock - mt_rand(10, 20)
                );

                $offenseIsHome = !$offenseIsHome;

                continue;
            }

            /*
             * -------------------------------------------------
             * 2. FOUL
             * -------------------------------------------------
             */
            $foulProbability =
                $this->playerStats->possessionFoulProbability(
                    $defensivePlayer,
                    $offensivePlayer,
                    $defensiveImpact,
                    $performanceFactor
                );

            if (
                $this->playerStats->possessionChance(
                    $foulProbability
                )
            ) {
                $stats[$defensivePlayer->id]['fouls']++;
                $fouls[$defensivePlayer->id]++;

                $isFouledOut =
                    $fouls[$defensivePlayer->id] >= 6;

                if ($isFouledOut) {
                    $stats[$defensivePlayer->id]['is_fouled_out'] = 1;
                }

                /*
                 * Decide whether this was a shooting foul.
                 */
                $shootingFoul =
                    $this->playerStats->possessionChance(
                        0.62
                    );

                if (!$shootingFoul) {
                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $offensivePlayer->team_id,
                            $defensivePlayer->id,
                            $offensivePlayer->id,
                            'foul',
                            0,
                            null,
                            false,
                            "{$defensivePlayer->name} commits a foul on {$offensivePlayer->name}."
                        );

                    $offenseIsHome = !$offenseIsHome;

                    continue;
                }

                /*
                 * Determine 2 or 3 free throws.
                 */
                $threePointFoul =
                    $this->playerStats->possessionChance(
                        0.24
                    );

                $ftAttempts = $threePointFoul ? 3 : 2;

                /*
                 * The foul still results in free throws.
                 */
                $ftPct =
                    $this->playerStats->possessionFreeThrowPercentage(
                        $offensivePlayer,
                        $performanceFactor
                    );

                $ftMade =
                    $this->playerStats->possessionBinomial(
                        $ftAttempts,
                        $ftPct
                    );

                $stats[$offensivePlayer->id]['free_throw_attempts']
                    += $ftAttempts;

                $stats[$offensivePlayer->id]['free_throws_made']
                    += $ftMade;

                $stats[$offensivePlayer->id]['points']
                    += $ftMade;

                if ($offenseIsHome) {
                    $homePoints += $ftMade;
                } else {
                    $awayPoints += $ftMade;
                }

                $events[] =
                    $this->event(
                        $currentSeasonId,
                        $gameData->game_id,
                        $quarter,
                        $possession,
                        $clock,
                        $offensivePlayer->team_id,
                        $offensivePlayer->id,
                        $defensivePlayer->id,
                        'shooting_foul',
                        $ftMade,
                        null,
                        false,
                        "{$defensivePlayer->name} fouls {$offensivePlayer->name}. {$offensivePlayer->name} makes {$ftMade} of {$ftAttempts} free throws."
                    );

                /*
                 * Fouled-out player is automatically removed
                 * from future defensive selections.
                 */

                $clock = max(
                    0,
                    $clock - mt_rand(8, 18)
                );

                /*
                 * After free throws possession changes.
                 */
                $offenseIsHome = !$offenseIsHome;

                continue;
            }

            /*
             * -------------------------------------------------
             * 3. CHOOSE 2PT OR 3PT
             * -------------------------------------------------
             */
            $threeProbability =
                $this->playerStats->possessionThreePointProbability(
                    $offensivePlayer,
                    $defensiveImpact,
                    $performanceFactor,
                    $chemistry,
                    $offenseIsHome
                );

            $isThree =
                $this->playerStats->possessionChance(
                    $threeProbability
                );

            /*
             * -------------------------------------------------
             * 4. SHOT ATTEMPT
             * -------------------------------------------------
             */
            if ($isThree) {
                $stats[$offensivePlayer->id]['three_point_attempts']++;
            } else {
                $stats[$offensivePlayer->id]['two_point_attempts']++;
            }

            $stats[$offensivePlayer->id]['field_goal_attempts']++;

            /*
             * -------------------------------------------------
             * 5. BLOCK CHECK
             * -------------------------------------------------
             */
            $blocked = false;

            if (!$isThree) {
                $blockProbability =
                    $this->playerStats->possessionBlockProbability(
                        $defensivePlayer,
                        $offensivePlayer,
                        $defensiveImpact
                    );

                if (
                    $this->playerStats->possessionChance(
                        $blockProbability
                    )
                ) {
                    $blocked = true;

                    $stats[$defensivePlayer->id]['blocks']++;

                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $offensivePlayer->team_id,
                            $offensivePlayer->id,
                            $defensivePlayer->id,
                            'block',
                            0,
                            2,
                            false,
                            "{$defensivePlayer->name} blocks {$offensivePlayer->name}."
                        );
                }
            }

            /*
             * -------------------------------------------------
             * 6. SHOT RESULT
             * -------------------------------------------------
             */
            $shotMade = false;

            if (!$blocked) {
                $shotPercentage =
                    $this->playerStats->possessionShotPercentage(
                        $offensivePlayer,
                        $isThree,
                        $defensiveImpact,
                        $performanceFactor,
                        $offenseIsHome
                    );

                $shotMade =
                    $this->playerStats->possessionChance(
                        $shotPercentage
                    );
            }

            if ($shotMade) {

                $shotValue = $isThree ? 3 : 2;

                $stats[$offensivePlayer->id]['field_goals_made']++;
                $stats[$offensivePlayer->id]['points']
                    += $shotValue;

                if ($isThree) {
                    $stats[$offensivePlayer->id]['three_pointers_made']++;
                } else {
                    $stats[$offensivePlayer->id]['two_pointers_made']++;
                }

                if ($offenseIsHome) {
                    $homePoints += $shotValue;
                } else {
                    $awayPoints += $shotValue;
                }

                /*
                 * -------------------------------------------------
                 * ASSIST
                 * -------------------------------------------------
                 *
                 * This is where assists now happen naturally.
                 */
                $assister =
                    $this->pickAssister(
                        $offenseIsHome
                            ? $homePlayers
                            : $awayPlayers,
                        $offensivePlayer,
                        $stats
                    );

                $assistGiven = false;

                if ($assister) {
                    $assistProbability =
                        $this->assistProbability(
                            $assister,
                            $offensivePlayer,
                            $isThree
                        );

                    if (
                        $this->playerStats->possessionChance(
                            $assistProbability
                        )
                    ) {
                        $stats[$assister->id]['assists']++;
                        $assistGiven = true;
                    }
                }

                $description =
                    "{$offensivePlayer->name} makes a {$shotValue}-point shot.";

                if ($assistGiven) {
                    $description .=
                        " Assist by {$assister->name}.";
                }

                $events[] =
                    $this->event(
                        $currentSeasonId,
                        $gameData->game_id,
                        $quarter,
                        $possession,
                        $clock,
                        $offensivePlayer->team_id,
                        $offensivePlayer->id,
                        $assistGiven
                            ? $assister->id
                            : null,
                        $isThree
                            ? 'three_pointer_made'
                            : 'two_pointer_made',
                        $shotValue,
                        $shotValue,
                        true,
                        $description
                    );

                $clock = max(
                    0,
                    $clock - mt_rand(12, 22)
                );

                /*
                 * Made basket → other team gets possession.
                 */
                $offenseIsHome = !$offenseIsHome;

                continue;
            }

            /*
             * -------------------------------------------------
             * 7. MISSED SHOT
             * -------------------------------------------------
             */
            $events[] =
                $this->event(
                    $currentSeasonId,
                    $gameData->game_id,
                    $quarter,
                    $possession,
                    $clock,
                    $offensivePlayer->team_id,
                    $offensivePlayer->id,
                    $defensivePlayer->id,
                    $isThree
                        ? 'three_pointer_missed'
                        : 'two_pointer_missed',
                    0,
                    $isThree ? 3 : 2,
                    false,
                    "{$offensivePlayer->name} misses a " .
                        ($isThree ? "3-point" : "2-point") .
                        " shot."
                );

            /*
             * -------------------------------------------------
             * 8. REBOUND
             * -------------------------------------------------
             */
            $offensiveReboundChance =
                $this->offensiveReboundProbability(
                    $offensivePlayer,
                    $defensePlayers
                );

            if (
                $this->playerStats->possessionChance(
                    $offensiveReboundChance
                )
            ) {
                $rebounder =
                    $this->pickRebounder(
                        $offensePlayers,
                        $stats
                    );

                if ($rebounder) {
                    $stats[$rebounder->id]['rebounds']++;

                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $offensivePlayer->team_id,
                            $rebounder->id,
                            null,
                            'offensive_rebound',
                            0,
                            null,
                            false,
                            "{$rebounder->name} grabs the offensive rebound."
                        );

                    /*
                     * Same team retains possession.
                     */
                }
            } else {
                $rebounder =
                    $this->pickRebounder(
                        $defensePlayers,
                        $stats
                    );

                if ($rebounder) {
                    $stats[$rebounder->id]['rebounds']++;

                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $rebounder->team_id,
                            $rebounder->id,
                            $offensivePlayer->id,
                            'defensive_rebound',
                            0,
                            null,
                            false,
                            "{$rebounder->name} grabs the defensive rebound."
                        );
                }

                $offenseIsHome = !$offenseIsHome;
            }

            $clock = max(
                0,
                $clock - mt_rand(10, 22)
            );
        }

        /*
         * Return final stats.
         */
        return [
            'stats' => array_values($stats),
            'events' => $events,
            'home_points' => $homePoints,
            'away_points' => $awayPoints,
        ];
    }

    /**
     * Build offensive player weights.
     */
    private function buildOffensiveWeights(
        array $players,
        array $minutes
    ): array {
        $weights = [];

        foreach ($players as $player) {
            $playerMinutes =
                (int) ($minutes[$player->id] ?? 0);

            if ($playerMinutes <= 0) {
                $weights[$player->id] = 0;
                continue;
            }

            if (
                !empty($player->is_injured) ||
                !empty($player->is_reserved) ||
                !empty($player->is_fouled_out)
            ) {
                $weights[$player->id] = 0;
                continue;
            }

            $role = strtolower(
                trim($player->role ?? 'starter')
            );

            $roleWeight = [
                'star player' => 1.35,
                'all star'    => 1.18,
                'starter'     => 1.00,
                'role player' => 0.82,
                'bench'       => 0.62,
            ][$role] ?? 0.85;

            $scoring =
                $this->rating($player, 'shooting_rating', 60) * 0.30;

            $scoring +=
                $this->rating($player, 'two_point_rating', 60) * 0.20;

            $scoring +=
                $this->rating($player, 'three_point_rating', 60) * 0.20;

            $scoring +=
                $this->rating($player, 'basketball_iq_rating', 60) * 0.15;

            $scoring +=
                $this->rating($player, 'athleticism_rating', 60) * 0.15;

            $scoring /= 70;

            $weights[$player->id] =
                max(
                    0.01,
                    $playerMinutes *
                        $roleWeight *
                        $scoring
                );
        }

        return $weights;
    }


    /**
     * Pick offensive player while respecting foul-out/injury state.
     */
    private function pickAvailablePlayer(
        array $players,
        array $weights,
        array $stats
    ) {
        $available = [];
        $availableWeights = [];

        foreach ($players as $player) {
            if (
                ($stats[$player->id]['minutes'] ?? 0) <= 0 ||
                ($stats[$player->id]['is_fouled_out'] ?? 0) ||
                !empty($player->is_injured) ||
                !empty($player->is_reserved)
            ) {
                continue;
            }

            $weight = $weights[$player->id] ?? 0;

            if ($weight <= 0) {
                continue;
            }

            $available[] = $player;
            $availableWeights[$player->id] = $weight;
        }

        return $this->playerStats->weightedPossessionPlayer(
            $available,
            $availableWeights
        );
    }

    /**
     * Pick a defender based on defensive ability.
     */
    private function pickDefender(
        array $players,
        $offensivePlayer,
        array $stats
    ) {
        $candidates = [];
        $weights = [];

        foreach ($players as $player) {
            if (
                ($stats[$player->id]['minutes'] ?? 0) <= 0 ||
                ($stats[$player->id]['is_fouled_out'] ?? 0) ||
                !empty($player->is_injured) ||
                !empty($player->is_reserved)
            ) {
                continue;
            }

            $defense =
                $this->rating($player, 'defense_rating', 60);

            $iq =
                $this->rating($player, 'basketball_iq_rating', 60);

            $athleticism =
                $this->rating($player, 'athleticism_rating', 60);

            $weight =
                0.55 * $defense +
                0.20 * $iq +
                0.15 * $athleticism +
                0.10 * $this->rating(
                    $player,
                    'strength_rating',
                    60
                );

            $candidates[] = $player;
            $weights[$player->id] =
                max(0.01, $weight);
        }

        return $this->playerStats->weightedPossessionPlayer(
            $candidates,
            $weights
        );
    }

    /**
     * In-memory version of your existing defensive-impact calculation.
     *
     * Your existing calculateDefensiveImpact() queries players from DB,
     * so it must NOT be called from the possession loop.
     */
    private function calculateRosterDefense(
        array $players
    ): float {
        $ratings = [];

        foreach ($players as $player) {
            if (
                !empty($player->is_injured) ||
                !empty($player->is_reserved)
            ) {
                continue;
            }

            $skill =
                $this->rating($player, 'defense_rating', 50) * 0.55;

            $skill +=
                $this->rating(
                    $player,
                    'basketball_iq_rating',
                    50
                ) * 0.15;

            $skill +=
                $this->rating(
                    $player,
                    'athleticism_rating',
                    50
                ) * 0.15;

            $skill +=
                $this->rating(
                    $player,
                    'strength_rating',
                    50
                ) * 0.05;

            $skill +=
                $this->rating(
                    $player,
                    'stamina_rating',
                    50
                ) * 0.10;

            $morale =
                $this->rating(
                    $player,
                    'morale',
                    75
                );

            $skill *=
                0.90 +
                ($morale / 1000);

            $ratings[] = $skill;
        }

        if (empty($ratings)) {
            return 0.08;
        }

        rsort($ratings);

        $topCount =
            max(
                5,
                (int) ceil(count($ratings) / 2)
            );

        $topRatings =
            array_slice(
                $ratings,
                0,
                min($topCount, count($ratings))
            );

        $defenseRating =
            array_sum($topRatings) /
            count($topRatings);

        return round(
            max(
                0.02,
                min(
                    0.15,
                    0.035 +
                        (($defenseRating - 50) / 1000)
                )
            ),
            3
        );
    }


    private function stealProbability(
        $defender,
        $offender
    ): float {
        $defense =
            $this->rating(
                $defender,
                'defense_rating',
                60
            );

        $iq =
            $this->rating(
                $defender,
                'basketball_iq_rating',
                60
            );

        $athleticism =
            $this->rating(
                $defender,
                'athleticism_rating',
                60
            );

        $offensiveIq =
            $this->rating(
                $offender,
                'basketball_iq_rating',
                60
            );

        $probability =
            0.20 +
            ($defense / 100) * 0.18 +
            ($iq / 100) * 0.10 +
            ($athleticism / 100) * 0.07;

        $probability -=
            ($offensiveIq / 100) * 0.08;

        /*
         * This function is only called AFTER a turnover,
         * so this is the conditional probability of receiving
         * the steal credit.
         */
        return max(
            0.05,
            min(0.85, $probability)
        );
    }

     private function assistProbability(
        $assister,
        $shooter,
        bool $isThree
    ): float {
        $passing =
            $this->rating(
                $assister,
                'passing_rating',
                60
            );

        $iq =
            $this->rating(
                $assister,
                'basketball_iq_rating',
                60
            );

        $chemistry =
            $this->rating(
                $assister,
                'morale',
                75
            );

        /*
         * Three-point shots generally have a slightly higher
         * chance of being assisted.
         */
        $base =
            $isThree
            ? 0.70
            : 0.58;

        $probability =
            $base +
            (($passing - 60) / 500) +
            (($iq - 60) / 900) +
            (($chemistry - 75) / 2500);

        return max(
            0.35,
            min(0.90, $probability)
        );
    }


    private function pickAssister(
        array $players,
        $shooter,
        array $stats
    ) {
        $candidates = [];
        $weights = [];

        foreach ($players as $player) {
            if ($player->id == $shooter->id) {
                continue;
            }

            if (
                ($stats[$player->id]['minutes'] ?? 0) <= 0 ||
                ($stats[$player->id]['is_fouled_out'] ?? 0)
            ) {
                continue;
            }

            $passing =
                $this->rating(
                    $player,
                    'passing_rating',
                    60
                );

            $iq =
                $this->rating(
                    $player,
                    'basketball_iq_rating',
                    60
                );

            $weight =
                $passing * 0.70 +
                $iq * 0.30;

            $weight *=
                max(
                    0.5,
                    ($stats[$player->id]['minutes'] ?? 1) / 10
                );

            $candidates[] = $player;
            $weights[$player->id] =
                max(0.01, $weight);
        }

        return $this->playerStats->weightedPossessionPlayer(
            $candidates,
            $weights
        );
    }

     private function offensiveReboundProbability(
        $offensivePlayer,
        array $defenders
    ): float {
        $rebounding =
            $this->rating(
                $offensivePlayer,
                'rebounding_rating',
                60
            );

        $position =
            strtoupper(
                trim(
                    explode(
                        '/',
                        $offensivePlayer->position ?? 'SF'
                    )[0]
                )
            );

        $positionFactor = [
            'PG' => 0.35,
            'SG' => 0.45,
            'SF' => 0.55,
            'PF' => 0.78,
            'C'  => 0.95,
        ][$position] ?? 0.55;

        /*
         * The probability here is deliberately low because
         * one player is being selected rather than modelling
         * all five players simultaneously.
         */
        $probability =
            0.055 +
            ($rebounding / 100) * 0.055;

        $probability *=
            $positionFactor;

        return max(
            0.03,
            min(0.15, $probability)
        );
    }


    private function pickRebounder(
        array $players,
        array $stats
    ) {
        $candidates = [];
        $weights = [];

        foreach ($players as $player) {
            if (
                ($stats[$player->id]['minutes'] ?? 0) <= 0 ||
                ($stats[$player->id]['is_fouled_out'] ?? 0)
            ) {
                continue;
            }

            $rebounding =
                $this->rating(
                    $player,
                    'rebounding_rating',
                    60
                );

            $athleticism =
                $this->rating(
                    $player,
                    'athleticism_rating',
                    60
                );

            $strength =
                $this->rating(
                    $player,
                    'strength_rating',
                    60
                );

            $position =
                strtoupper(
                    trim(
                        explode(
                            '/',
                            $player->position ?? 'SF'
                        )[0]
                    )
                );

            $positionFactor = [
                'PG' => 0.40,
                'SG' => 0.50,
                'SF' => 0.70,
                'PF' => 1.10,
                'C'  => 1.35,
            ][$position] ?? 0.70;

            $weight =
                (
                    $rebounding * 0.60 +
                    $athleticism * 0.20 +
                    $strength * 0.20
                ) * $positionFactor;

            $candidates[] = $player;
            $weights[$player->id] =
                max(0.01, $weight);
        }

        return $this->playerStats->weightedPossessionPlayer(
            $candidates,
            $weights
        );
    }

     private function event(
        $seasonId,
        $gameId,
        $quarter,
        int $possession,
        int $clock,
        $teamId,
        $playerId = null,
        $secondaryPlayerId = null,
        string $eventType = 'play',
        int $points = 0,
        ?int $shotValue = null,
        ?bool $shotMade = null,
        ?string $description = null
    ): array {
        return [
            'season_id' => $seasonId,
            'game_id' => $gameId,
            'quarter' => $quarter,
            'possession_number' => $possession,
            'team_id' => $teamId,
            'player_id' => $playerId,
            'secondary_player_id' => $secondaryPlayerId,
            'game_clock_seconds' => $clock,
            'event_type' => $eventType,
            'points' => $points,
            'shot_value' => $shotValue,
            'shot_made' => $shotMade,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }


    private function rating(
        $player,
        string $attribute,
        float $default = 50
    ): float {
        $value =
            $player->$attribute ?? $default;

        return max(
            0,
            min(100, (float) $value)
        );
    }
}