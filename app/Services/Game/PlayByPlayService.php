<?php

namespace App\Services\Game;

ini_set('max_execution_time', 1048); // Unlimited execution time

use App\Services\Helper\HelperService;
use App\Services\Game\PlayerStatsService;
use App\Services\Game\PlayByPlayStatsService;
use App\Services\Team\TeamManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PlayByPlayService
{
    protected $teamManagement;
    protected $playerStats;
    protected $playByPlay;
    protected $helper;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->teamManagement = new TeamManagementService();
        $this->playerStats = new PlayerStatsService();
        $this->playByPlay = new PlayByPlayStatsService();
        $this->helper = new HelperService();

        $this->homeScore = 0;
        $this->awayScore = 0;
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
        int $possessions = 115,
        float $gameMinutes,
    ): array {

        /*
         * Initialize every player once.
         */
        $stats = [];

        foreach ($homePlayers as $player) {
        
            $stats[$player->id] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => (int) ($player->is_injured ?? 0),
                'role' => $player->role,
                'points' => 0,
                'rebounds' => 0,
                'offensive_rebounds' => 0,
                'defensive_rebounds' => 0,
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
        foreach ($awayPlayers as $player) {
            
            $stats[$player->id] = [
                'player_id' => $player->id,
                'game_id' => $gameData->game_id,
                'season_id' => $currentSeasonId,
                'team_id' => $player->team_id,
                'is_injured' => (int) ($player->is_injured ?? 0),
                'role' => $player->role,
                'points' => 0,
                'rebounds' => 0,
                'offensive_rebounds' => 0,
                'defensive_rebounds' => 0,
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
        $maxMinutesPerPlayer = ($gameMinutes / 5);

        foreach ($homeMinutes as $playerId => $minutes) {
            if (isset($stats[$playerId])) {
                $stats[$playerId]['minutes'] = (int) min($maxMinutesPerPlayer,$minutes);
            }
        }

        foreach ($awayMinutes as $playerId => $minutes) {
            if (isset($stats[$playerId])) {
                $stats[$playerId]['minutes'] = (int) min($maxMinutesPerPlayer,$minutes);
            }
        }

        /*
         * Cache performance once per player per quarter.
         *
         * DO NOT call calculatePerformanceFactor()
         * for every possession.
         */
        $performance = [];

        foreach ($homePlayers as $player) {
            $performance[$player->id] =
                $this->playerStats->calculatePerformanceFactor(
                    $player,
                    false
                );

            $this->teamManagement->fatigueRate($player, $stats[$player->id]['minutes'], $gameData->game_id);
            
        }

        foreach ($awayPlayers as $player) {
            $performance[$player->id] =
                $this->playerStats->calculatePerformanceFactor(
                    $player,
                    false
                );

            $this->teamManagement->fatigueRate($player, $stats[$player->id]['minutes'], $gameData->game_id);
        
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

        foreach ($homePlayers as $player) {
            $fouls[$player->id] =
                (int) ($player->last_quarter_fouls ?? 0);
        }
        foreach ($awayPlayers as $player) {
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
        $clock = ($gameMinutes / 5) * 60;

        // dd('gameMinutes:'.$gameMinutes.' clock:'.$clock);
        for ($possession = 1; $possession <= $possessions; $possession++) {

            if($clock == 0){
                break;
            }
            
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

            // Rare in-game injuries remove the player from later possessions.
            foreach ([$offensivePlayer, $defensivePlayer] as $participant) {
                $injuryChance = min(0.006, max(0.0005, ((float) ($participant->injury_prone_percentage ?? 10)) / 10000));
                if (empty($participant->is_injured) && $this->playByPlay->possessionChance($injuryChance)) {
                    $this->playByPlay->inGameInjury($participant, $gameData->game_id, $currentSeasonId);
                    $participant->is_injured = true;
                    $stats[$participant->id]['is_injured'] = 1;
                    $teamPlayers = $participant->team_id == $offensivePlayer->team_id ? $offensePlayers : $defensePlayers;
                    $substitute = $this->pickSubstitute($teamPlayers, $participant, $stats);
                    $events[] = $this->event(
                        $currentSeasonId,
                        $gameData->game_id,
                        $quarter,
                        $possession,
                        $clock,
                        $gameData->home_team_id,
                        $participant->team_id,
                        $participant->id,
                        $substitute?->id,
                        'injury',
                        0,
                        null,
                        false,
                        $substitute
                            ? "{$participant->name} is injured and replaced by {$substitute->name}."
                            : "{$participant->name} is injured and leaves the game."
                    );
                    $injuryOccurred = true;
                }
            }

            if (!empty($injuryOccurred)) {
                $injuryOccurred = false;
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
                $this->playByPlay->possessionTurnoverProbability(
                    $offensivePlayer,
                    $defensivePlayer,
                    $defensiveImpact,
                    $performanceFactor
                );

            if (
                $this->playByPlay->possessionChance(
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
                    $this->playByPlay->possessionChance(
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
                            $gameData->home_team_id,
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
                            $gameData->home_team_id,
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

                $clock = max(0,$clock - rand(10,20));

                $offenseIsHome = !$offenseIsHome;

                continue;
            }

            /*
             * -------------------------------------------------
             * 2. FOUL
             * -------------------------------------------------
             */
            $foulProbability =
                $this->playByPlay->possessionFoulProbability(
                    $defensivePlayer,
                    $offensivePlayer,
                    $defensiveImpact,
                    $performanceFactor
                );

            if (
                $this->playByPlay->possessionChance(
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

                $foulDescription = "{$defensivePlayer->name} commits a foul on {$offensivePlayer->name}.";
                if ($isFouledOut) {
                    $foulDescription .= " {$defensivePlayer->name} has fouled out.";
                }

                /*
                 * Decide whether this was a shooting foul.
                 */
                $shootingFoul =
                    $this->playByPlay->possessionChance(
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
                            $gameData->home_team_id,
                            $offensivePlayer->team_id,
                            $defensivePlayer->id,
                            $offensivePlayer->id,
                            'foul',
                            0,
                            null,
                            false,
                            $foulDescription
                        );

                    $offenseIsHome = !$offenseIsHome;

                    continue;
                }

                /*
                 * Determine 2 or 3 free throws.
                 */
                $threePointFoul =
                    $this->playByPlay->possessionChance(
                        0.24
                    );

                $ftAttempts = $threePointFoul ? 3 : 2;

                /*
                 * The foul still results in free throws.
                 */
                $ftPct =
                    $this->playByPlay->possessionFreeThrowPercentage(
                        $offensivePlayer,
                        $performanceFactor
                    );

                $ftMade =
                    $this->playByPlay->possessionBinomial(
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
                        $gameData->home_team_id,
                        $offensivePlayer->team_id,
                        $offensivePlayer->id,
                        $defensivePlayer->id,
                        'shooting_foul',
                        $ftMade,
                        null,
                        false,
                        "{$defensivePlayer->name} fouls {$offensivePlayer->name}. {$offensivePlayer->name} makes {$ftMade} of {$ftAttempts} free throws." . ($isFouledOut ? " {$defensivePlayer->name} has fouled out." : '')
                    );

                if ($isFouledOut) {
                    $substitute = $this->pickSubstitute(
                        $defensePlayers,
                        $defensivePlayer,
                        $stats
                    );
                    $events[] = $this->event(
                        $currentSeasonId,
                        $gameData->game_id,
                        $quarter,
                        $possession,
                        $clock,
                        $gameData->home_team_id,
                        $defensivePlayer->team_id,
                        $defensivePlayer->id,
                        $substitute?->id,
                        'substitution',
                        0,
                        null,
                        false,
                        $substitute
                            ? "{$defensivePlayer->name} fouls out and is replaced by {$substitute->name}."
                            : "{$defensivePlayer->name} fouls out."
                    );
                }

                /*
                 * Fouled-out player is automatically removed
                 * from future defensive selections.
                 */

                $clock = max(0,$clock - mt_rand(8, 18));

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
                $this->playByPlay->possessionThreePointProbability(
                    $offensivePlayer,
                    $defensiveImpact,
                    $performanceFactor,
                    $chemistry,
                    $offenseIsHome
                );

            $isThree =
                $this->playByPlay->possessionChance(
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
                    $this->playByPlay->possessionBlockProbability(
                        $defensivePlayer,
                        $offensivePlayer,
                        $defensiveImpact
                    );

                if (
                    $this->playByPlay->possessionChance(
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
                            $gameData->home_team_id,
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
                    $this->playByPlay->possessionShotPercentage(
                        $offensivePlayer,
                        $isThree,
                        $defensiveImpact,
                        $performanceFactor,
                        $offenseIsHome
                    );

                $shotMade =
                    $this->playByPlay->possessionChance(
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
                        $this->playByPlay->possessionChance(
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
                        $gameData->home_team_id,
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

                $clock = max(0,$clock - mt_rand(12, 22));

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
                    $gameData->home_team_id,
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
                $this->playByPlay->possessionChance(
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
                    $stats[$rebounder->id]['offensive_rebounds']++;

                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $gameData->home_team_id,
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
                    $stats[$rebounder->id]['defensive_rebounds']++;

                    $events[] =
                        $this->event(
                            $currentSeasonId,
                            $gameData->game_id,
                            $quarter,
                            $possession,
                            $clock,
                            $gameData->home_team_id,
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

            $clock = max(0,$clock - mt_rand(10, 22));
        }

        /*
         * Return final stats.
         */
        usort(
            $events,
            fn($a, $b) =>
            $a['possession_number'] <=> $b['possession_number']
        );

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
        $players,
        $minutes
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
        $players,
        $weights,
        $stats
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

        $possessionPlayer = $this->playByPlay->weightedPossessionPlayer(
            $available,
            $availableWeights
        );

        // dd($possessionPlayer);
        // dd($available);

        return $possessionPlayer;
    }

    /**
     * Pick a defender based on defensive ability.
     */
    private function pickDefender(
        $players,
        $offensivePlayer,
        $stats
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

        return $this->playByPlay->weightedPossessionPlayer(
            $candidates,
            $weights
        );
    }

    private function pickSubstitute($players, $removedPlayer, $stats)
    {
        foreach ($players as $player) {
            if (
                $player->id !== $removedPlayer->id &&
                ($stats[$player->id]['minutes'] ?? 0) > 0 &&
                empty($stats[$player->id]['is_fouled_out']) &&
                empty($player->is_injured) &&
                empty($player->is_reserved)
            ) {
                return $player;
            }
        }

        return null;
    }

    /**
     * In-memory version of your existing defensive-impact calculation.
     *
     * Your existing calculateDefensiveImpact() queries players from DB,
     * so it must NOT be called from the possession loop.
     */
    private function calculateRosterDefense(
        $players
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
        $players,
        $shooter,
        $stats
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

        return $this->playByPlay->weightedPossessionPlayer(
            $candidates,
            $weights
        );
    }

     private function offensiveReboundProbability(
        $offensivePlayer,
        $defenders
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
        $players,
        $stats
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

        return $this->playByPlay->weightedPossessionPlayer(
            $candidates,
            $weights
        );
    }

    public function recordGameEvents($events){
        try {
            DB::beginTransaction();
            //code...
            DB::table('play_by_play')->insert($events);

            DB::commit();
        } catch (\Throwable $e) {
            //throw $th;
            DB::rollBack();
        }
    }

    private $homeScore = 0;
    private $awayScore = 0;

    private function event(
        $seasonId,
        $gameId,
        $quarter,
        int $possession,
        int $clock,
        int $homeTeamId,
        int $teamId,
        $playerId = null,
        $secondaryPlayerId = null,
        string $eventType = 'play',
        int $points = 0,
        ?int $shotValue = 0,
        ?bool $shotMade = false,
        ?string $description = null,
    ): array {

        $isHomeTeamId = ($homeTeamId == $teamId);
        $this->homeScore = $isHomeTeamId ?  $this->homeScore + $points :  $this->homeScore;
        $this->awayScore = !$isHomeTeamId ? $this->awayScore + $points :  $this->awayScore;

        $scoreUpdate = $this->homeScore.'-'.$this->awayScore;
        
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
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
            'score_update' => $scoreUpdate,
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

