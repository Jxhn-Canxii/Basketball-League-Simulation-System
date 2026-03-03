<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\FreeAgentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\HelperController;

class TeamManagementController extends Controller
{

    protected $storeStats;
    protected $freeAgent;
    protected $contract;
    protected $helper;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->storeStats = new AwardsController();
        $this->freeAgent = new FreeAgentController();
        $this->contract = new ContractController();
        $this->helper = new HelperController();
    }

    public function updateSeasonTeamChemistryBeforeGame($teamId)
    {
        $seasonId = get_current_season_id();

        $chemistryRow = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->first();

        if (!$chemistryRow) {
            DB::table('team_season_info')->insert([
                'team_id' => $teamId,
                'season_id' => $seasonId,
                'chemistry' => 50, // default
            ]);
            $chemistry = 50;
        } else {
            $chemistry = $chemistryRow->chemistry;
        }

        $team = DB::table('teams')->where('id', $teamId)->first();
        if (!$team) return;

        $coachIQ = $chemistryRow->coach_iq;

        // 🎯 Last game outcome
        $lastGame = DB::table('schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->orderByDesc('id')
            ->first();

        if ($lastGame) {
            $wonLastGame = $lastGame->winner_id === $teamId;
            $chemistry += $wonLastGame ? 2 : -2;
        }

        // 🎯 Coach IQ
        if ($coachIQ >= 90) $chemistry += 1;
        elseif ($coachIQ <= 65) $chemistry -= 1;

        // 🎯 Leadership
        $leaders = DB::table('players')
            ->where('team_id', $teamId)
            ->orderByDesc('leadership_rating')
            ->pluck('leadership_rating');

        if ($leaders->isNotEmpty()) {
            $avgLeadership = $leaders->avg();
            if ($avgLeadership >= 85) $chemistry += 2;
            elseif ($avgLeadership <= 60) $chemistry -= 2;
        }

        // 🎯 Season win percentage
        $seasonGames = DB::table('schedules')
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->where('season_id', $seasonId)
            ->where('status', 2)
            ->get();

        $totalGames = $seasonGames->count();
        $wins = $seasonGames->filter(fn($g) => $g->winner_id === $teamId)->count();

        if ($totalGames >= 5) {
            $winRate = $wins / $totalGames;
            if ($winRate >= 0.7) $chemistry += 2;
            elseif ($winRate <= 0.3) $chemistry -= 2;
        }

        // 🎯 Morale
        $moraleAvg = DB::table('players')
            ->where('team_id', $teamId)
            ->avg('morale');

        if (!is_null($moraleAvg)) {
            if ($moraleAvg >= 85) $chemistry += 2;
            elseif ($moraleAvg <= 60) $chemistry -= 2;
        }

        $injuredCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', true) // assuming you track this
            ->count();

        if (!is_null($injuredCount)) {
            if ($injuredCount >= 3) $chemistry -= 3;
            elseif ($injuredCount === 1) $chemistry -= 1;
        }

        // 🧼 Clamp
        $chemistry = max(0, min(100, round($chemistry)));

        // ✅ Update
        DB::table('team_season_info')
            ->updateOrInsert(
                ['team_id' => $teamId, 'season_id' => $seasonId],
                ['chemistry' => $chemistry]
            );
    }
    /**
     * Fetches Coach IQ and Team Chemistry for a player's team.
     * 
     * @param int $teamId
     * @return array ['coach_iq' => int, 'chemistry' => int]
     */
    private function getTeamCoachAndChemistry(int $teamId): array
    {
        $teamSeasonInfo = DB::table('team_season_info')
            ->where('team_id', $teamId)
            ->select('coach_iq', 'chemistry')
            ->first();

        return [
            'coach_iq' => $teamSeasonInfo->coach_iq ?? 50, // Default if missing
            'chemistry' => $teamSeasonInfo->chemistry ?? 50,
        ];
    }

    public function fireLeopardRule($teamId)
    {
        $seasonId = get_current_season_id();

        // Count the number of active (non-injured) players
        $activePlayersCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_injured', false)
            ->count();

        // If the team has at least 11 healthy players, no action is needed
        if ($activePlayersCount >= 11) {
            return $activePlayersCount;
        }

        // Determine how many players need to be added
        $playersNeeded = 1;
        $signedPlayers = [];

        // Find free agents for temporary contracts
        $freeAgents = DB::table('players')
            ->where('team_id', 0) // Free agent pool
            ->where('is_injured', 0) // Not injured
            ->where('is_active', 1) // Ensure the player is active
            ->orderByDesc('overall_rating') // Sort by highest overall rating
            ->orderBy('injury_prone_percentage', 'asc') // Lowest injury-prone percentage first
            ->orderBy('age', 'asc') // Then sort by youngest age
            ->take($playersNeeded)
            ->get();


        foreach ($freeAgents as $freeAgent) {
            // Assign a temporary hardship contract (10-game contract)
            DB::table('players')->where('id', $freeAgent->id)->update([
                'team_id' => $teamId,
                'contract_years' => 0, // Temporary contract
                'hardship_contract' => 10, // The player is signed for 10 games only
            ]);

            // Log transaction
            DB::table('transactions')->insert([
                'player_id' => $freeAgent->id,
                'season_id' => $seasonId,
                'details' => 'Signed under hardship exception (10-game contract)',
                'from_team_id' => 0,
                'to_team_id' => $teamId,
                'status' => 'signed-hardship',
            ]);

            $this->storeStats->storePlayerSeasonStats($teamId, $freeAgent->id);

            $signedPlayers[] = $freeAgent;
        }

        return $signedPlayers;
    }

    public function handleInjuredPlayer($player, $seasonId, $seasonStatus)
    {
        try {
            // Check if player is already retired or inactive
            if (!$player->is_active) {
                \Log::info("Player {$player->name} is already inactive or retired. No action needed.");
                return;
            }

            // Track if retirement age was adjusted due to severe injury
            $retirementReason = 'reached retirement age';
            $injuryHistoryCount = 0;

            // Handle injury if player is injured
            if ($player->is_injured) {
                // Process injury recovery
                $deductionPerGame = 1;

                if ($player->injury_recovery_games > 0) {
                    // Decrement injury recovery games
                    DB::table('players')->where('id', $player->id)->decrement('injury_recovery_games', $deductionPerGame);
                    $updatedRecoveryGames = DB::table('players')->where('id', $player->id)->value('injury_recovery_games');
                    \Log::info("Decremented recovery games for {$player->name}. Remaining: {$updatedRecoveryGames}");
                } else {
                    $updatedRecoveryGames = $player->injury_recovery_games;
                    \Log::info("No recovery games left for {$player->name}.");
                }

                // Load injury config
                $injuries = config('injuries');
                $currentInjury = $player->injury_type;

                // Define severe injury criteria
                $severeInjuryThreshold = [
                    'recovery_games' => 15, // Severe if recovery takes 15+ games
                    'performance_impact' => 0.3, // Severe if performance impact is 30% or less
                ];
                $injuryHistoryThreshold = 5; // Threshold for "too many" injuries
                $retirementAgeReduction = 2; // Years to reduce retirement age
                $minimumRetirementAge = max($player->age, 30); // Minimum retirement age

                // Non-injury factors that shouldn't affect retirement age
                $nonInjuryFactors = [
                    'resting',
                    'suspension',
                    'personal_reason',
                    'logistics_issue',
                    'family_emergency',
                    'contract_dispute',
                    'mental_health',
                    'player_protest',
                    'travel_fatigue'
                ];

                // Check if the current injury is severe and not a non-injury factor
                $isSevereInjury = false;
                if (array_key_exists($currentInjury, $injuries) && !in_array($currentInjury, $nonInjuryFactors)) {
                    $injuryDetails = $injuries[$currentInjury];
                    if (
                        $injuryDetails['recovery_games'] >= $severeInjuryThreshold['recovery_games'] ||
                        $injuryDetails['performance_impact'] <= $severeInjuryThreshold['performance_impact']
                    ) {
                        $isSevereInjury = true;
                    }
                }

                // Get injury history count
                $injuryHistoryCount = DB::table('injury_histories')
                    ->where('player_id', $player->id)
                    ->count();

                // Adjust retirement age if injury is severe and injury history is high
                if ($isSevereInjury && $injuryHistoryCount > $injuryHistoryThreshold) {
                    $newRetirementAge = max($player->retirement_age - $retirementAgeReduction, $minimumRetirementAge);
                    if ($newRetirementAge < $player->retirement_age) {
                        DB::table('players')->where('id', $player->id)->update([
                            'retirement_age' => $newRetirementAge,
                            'updated_at' => now(),
                        ]);
                        \Log::info("Adjusted retirement age for {$player->name} from {$player->retirement_age} to {$newRetirementAge} due to severe injury ({$currentInjury}) and high injury history ({$injuryHistoryCount} injuries).");
                        // Update player object and retirement reason
                        $player->retirement_age = $newRetirementAge;
                        $retirementReason = "severe injury history ({$injuryHistoryCount} injuries)";
                    }
                }

                // If player fully recovered
                if ($updatedRecoveryGames <= 0) {
                    DB::table('players')->where('id', $player->id)->update([
                        'is_injured' => false,
                        'injury_type' => null,
                    ]);

                    // Update injury history recovery date
                    DB::table('injury_histories')
                        ->where('player_id', $player->id)
                        ->whereNull('recovery_date')
                        ->latest()
                        ->update([
                            'recovery_date' => now(),
                            'updated_at' => now(),
                        ]);

                    \Log::info("Player {$player->name} has fully recovered from injury.");
                }
            }

            // Check for forced retirement
            if ($player->age >= $player->retirement_age) {
                // Get team name for transaction log
                $teamName = $player->team_id
                    ? $this->helper->getTeamName($player->team_id)
                    : 'No Team';

                // Create detailed transaction message
                $details = "{$player->name} retired from the league at age {$player->age} (retirement age: {$player->retirement_age}) due to {$retirementReason}. Last team: {$teamName}";
                if ($retirementReason === 'severe injury history') {
                    $details .= " (injury count: {$injuryHistoryCount})";
                }

                // Update player to retired status
                DB::table('players')->where('id', $player->id)->update([
                    'is_active' => false,
                    'team_id' => null,
                    'updated_at' => now(),
                ]);

                // Log retirement in transactions table
                DB::table('transactions')->insert([
                    'player_id' => $player->id,
                    'season_id' => $seasonId,
                    'details' => $details,
                    'from_team_id' => $player->team_id ?? 0,
                    'to_team_id' => 0,
                    'status' => 'retired',
                ]);

                \Log::info("Player {$player->name} has been forced to retire due to {$retirementReason}.");
                return;
            }
        } catch (\Exception $e) {
            \Log::error("Error handling player {$player->id}: " . $e->getMessage());
        }
    }

    private function playerWaiverEvaluator($player, $seasonId, $seasonStatus)
    {
        if (is_array($player)) {
            $player = (object) $player;
        }

        $evaluation = $this->shouldWaivePlayer($player, $seasonId, $seasonStatus);

        if ($evaluation['waived']) {
            $reason = $evaluation['reason'] ?? 'No specific reason provided';
            $teamId = $player->team_id;

            // 🔁 Log waiver transaction
            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $seasonId,
                'details' => 'Waived: ' . $reason,
                'from_team_id' => $teamId,
                'to_team_id' => 0,
                'status' => 'waived',
            ]);

            // 🚫 Remove player from team
            DB::table('players')->where('id', $player->id)->update([
                'contract_years' => 0,
                'team_id' => 0,
            ]);

            // ✅ Find replacement
            $replacement = $this->freeAgent->getBestFreeAgentAvailable($player->position);
            if ($replacement) {
                $contractYears = $this->contract->getContractYearsBasedOnRole($player->role);

                DB::table('players')->where('id', $replacement->player_id)->update([
                    'team_id' => $teamId,
                    'contract_years' => $contractYears,
                ]);

                // Check if replacement is same as waived player
                $replacementDetails = ($replacement->player_id === $player->id)
                    ? 'Re-signed ' . $player->name . ' after reevaluation. Contract renewed for ' . $contractYears . ' year(s).'
                    : 'Signed as replacement for ' . $player->name . '. Contract Years: ' . $contractYears;

                DB::table('transactions')->insert([
                    'player_id' => $replacement->player_id,
                    'season_id' => $seasonId,
                    'details' => $replacementDetails,
                    'from_team_id' => 0,
                    'to_team_id' => $teamId,
                    'status' => 'signed',
                ]);

                $this->storeStats->storePlayerCurrentSeasonStats($teamId, $replacement->player_id);
            }

            return true;
        }

        return false;
    }
    
    public function updateInjuryAndWaiving($teamId)
    {
        $seasonId = get_current_season_id();

        $seasonStatus = $this->helper->seasonStatus($seasonId);

        if (!$teamId || !$seasonId || !$seasonStatus) {
            return; // Invalid parameters
        }

        $players = DB::table('players')->where('team_id', $teamId)->get();
        foreach ($players as $player) {
            $this->handleInjuredPlayer($player, $seasonId, $seasonStatus);
            // Evaluate whether player should be waived based on injury duration and season status
            $this->playerWaiverEvaluator($player, $seasonId, $seasonStatus);
        }
    }


    private function shouldWaivePlayer($player, int $seasonId, int $seasonStatus): array
    {
        // Waivers only allowed in first half of season
          // Get the number of rounds that are already simulated (status != 2)
        $simulatedRounds = $this->helper->simulatedRounds($seasonId);

        // Get the total number of rounds in the season
        $totalRounds = $this->helper->totalRounds($seasonId);

        $canWaivePlayerTreshold = ceil($totalRounds / 2) - 3;

        $canWaivePlayer = $simulatedRounds <= $canWaivePlayerTreshold && $seasonStatus == 1;

        if(!$canWaivePlayer) {
            return ['waived' => false, 'reason' => 'Season too late to waive'];
        }

        // Protect key players
        $protectedRoles = ['star player', 'all star', 'starter'];
        if (in_array(strtolower($player->role), $protectedRoles) && $player->contract_years >= 3) {
            return ['waived' => false, 'reason' => 'Protected star/all-star with long contract'];
        }

        if ($player->is_rookie && $this->isHighPickRookie($player->id)) {
            return ['waived' => false, 'reason' => 'Protected high-pick rookie'];
        }

        if ($this->isDevelopmentalPlayer($player) || $this->wasRecentlyDrafted($player->id, $seasonId)) {
            return ['waived' => false, 'reason' => 'Protected developmental or recently drafted player'];
        }

        if ($this->calculatePotentialScore($player) >= 75) {
            return ['waived' => false, 'reason' => 'High potential player'];
        }

        // Get season stats and team games
        $seasonStats = $this->getPlayerSeasonStats($player->id, $player->team_id, $seasonId);
        if (!$seasonStats) {
            return ['waived' => false, 'reason' => 'Missing season stats'];
        }

        $totalGames = $this->totalTeamGames($seasonId, $player->team_id);
        $minGamesPlayed = max(3, floor($totalGames * 0.20)); // 20% of team games
        $hasPlayedMinimumGames = ($seasonStats->total_games_played ?? 0) >= $minGamesPlayed;

        // Role-based usage threshold
        $role = strtolower($player->role);
        $usageMinutesThreshold = 7;
        if (in_array($role, ['bench', 'role player'])) {
            $usageMinutesThreshold = 5; // More tolerance for bench/role players
        }

        // Injury-based waiver (aligned with handleInjuredPlayer and injuries.php)
        $rolePctMap = [
            'star player' => 0.80,
            'all star'    => 0.70,
            'starter'     => 0.60,
            'role player' => 0.50,
            'bench'       => 0.40,
        ];
        $defaultPct = 0.30;
        $pct = $rolePctMap[$role] ?? $defaultPct;

        $totalContractGames = $totalGames * max($player->contract_years, 1);
        $baseRecoveryGames = ceil($totalContractGames * $pct);
        $maxRecoveryGames = 30;
        $requiredRecoveryGames = min($baseRecoveryGames, $maxRecoveryGames);

        if ($player->overall_rating >= 90) {
            $requiredRecoveryGames += 5;
        } elseif ($player->overall_rating >= 80) {
            $requiredRecoveryGames += 2;
        } elseif ($player->overall_rating <= 70) {
            $requiredRecoveryGames -= 2;
        } elseif ($player->overall_rating <= 60) {
            $requiredRecoveryGames -= 4;
        }
        $requiredRecoveryGames = max(2, min($requiredRecoveryGames, $totalContractGames));

        if ($player->injury_recovery_games > $requiredRecoveryGames) {
            return ['waived' => true, 'reason' => 'Injured too long'];
        }

        // Combined criteria for efficiency and improvement (stricter to reduce waivers)
        $isRebuilding = $this->isRebuildingTeam($player->team_id);
        $hasNotImproved = $this->hasNotImproved($player->id, $player->team_id, $seasonId);

        // Adjusted efficiency threshold (from eff < 4 to eff < 6)
        if ($seasonStats->eff !== null && $seasonStats->eff < 6 && $hasNotImproved && $hasPlayedMinimumGames && !$isRebuilding) {
            return ['waived' => true, 'reason' => 'Low efficiency and no improvement'];
        }

        // Adjusted composite score (from < 5 to < 7)
        $usageScore = ($seasonStats->avg_minutes_per_game * 0.5) +
            ($seasonStats->avg_points_per_game * 0.3) +
            ($seasonStats->avg_rebounds_per_game * 0.2);
        $compositeScore = $usageScore * ($seasonStats->eff / 10);

        if (
            $compositeScore < 7 && $seasonStats->avg_minutes_per_game < $usageMinutesThreshold &&
            $seasonStats->total_games_played <= ($totalGames * 0.30) && $hasNotImproved && $hasPlayedMinimumGames
        ) {
            return ['waived' => true, 'reason' => 'Low composite efficiency and no improvement'];
        }

        // Rebuilding team: waive veterans with moderate performance
        if ($isRebuilding && $player->age >= 32 && $seasonStats->eff < 12 && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Veteran waived by rebuilding team'];
        }

        // High fatigue or morale issues (require multiple conditions)
        if ($player->fatigue >= 85 && $seasonStats->eff < 8 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'High fatigue and underperforming with no improvement'];
        }

        if ($player->morale !== null && $player->morale < 40 && $seasonStats->eff < 8 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Low morale and underperforming with no improvement'];
        }

        // Aging players (stricter criteria)
        if ($player->age >= 34 && $seasonStats->eff < 10 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Aging player with poor impact and no improvement'];
        }

        // Bad value contract (require no improvement)
        if ($player->contract_years > 2 && $seasonStats->eff < 8 && $hasNotImproved && $hasPlayedMinimumGames) {
            return ['waived' => true, 'reason' => 'Bad value contract with no improvement'];
        }

        return ['waived' => false, 'reason' => null];
    }

        private array $playerYearsProCache = [];
    private array $playerYearsProWithTeamCache = [];

    private function getYearsPro(int $playerId): int
    {
        if (isset($this->playerYearsProCache[$playerId])) {
            return $this->playerYearsProCache[$playerId];
        }

        $yearsPro = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->distinct()
            ->count('season_id');

        return $this->playerYearsProCache[$playerId] = $yearsPro;
    }

    private function getYearsProWithTeam(int $playerId, int $teamId): int
    {
        if (isset($this->playerYearsProCache[$playerId])) {
            return $this->playerYearsProCache[$playerId];
        }

        $yearsPro = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->where('team_id', $teamId)
            ->distinct()
            ->count('season_id');

        return $this->playerYearsProWithTeamCache[$playerId] = $yearsPro;
    }

    private function isDevelopmentalPlayer($player): bool
    {
        $yearsPro = $this->getYearsPro($player->id);
        return ($player->age <= 23 || $yearsPro <= 2 || $player->is_rookie);
    }

    private function wasRecentlyDrafted(int $playerId, int $seasonId, int $roundLimit = 2): bool
    {
        $draft = DB::table('drafts')
            ->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->first();

        return $draft && $draft->round <= $roundLimit;
    }

    private function calculatePotentialScore($player): int
    {
        $score = 0;

        if ($player->age <= 23) $score += 30;
        if ($this->getYearsPro($player->id) <= 2) $score += 20;
        if ($player->work_ethic_rating >= 75) $score += 20;
        if ($player->basketball_iq_rating >= 70) $score += 15;
        if (isset($player->draft_pick_number) && $player->draft_pick_number <= 15) $score += 15;

        return $score; // Max score: 100
    }

    private function isHighPickRookie($playerId): bool
    {
        $draft = DB::table('drafts')
            ->where('player_id', $playerId)
            ->first();

        if (!$draft) return false;

        return $draft->round == 1 && $draft->pick_number <= 10;
    }

    private function hasNotImproved(int $playerId, int $teamId, int $currentSeasonId): bool
    {
        // Get the earliest season ID
        $firstSeasonId = DB::table('seasons')->min('id');

        if ($currentSeasonId == $firstSeasonId) {
            return false; // No past data
        }

        // Calculate composite improvement index
        $improvementIndex = $this->calculateImprovementIndex($playerId, $teamId, $currentSeasonId);

        if (is_null($improvementIndex)) {
            // Handle players with no recent prior season data
            $seasonStats = $this->getPlayerSeasonStats($playerId, $teamId, $currentSeasonId);
            if (!$seasonStats || $seasonStats->total_games_played < max(3, floor($seasonStats->total_games * 0.15))) {
                return false; // Not enough current season data
            }

            // Role-based efficiency threshold, adjusted for injuries
            $role = strtolower($seasonStats->role ?? 'bench');
            $effThresholds = [
                'star player' => 18,
                'all star' => 15,
                'starter' => 12,
                'role player' => 10,
                'bench' => 8,
            ];
            $effThreshold = $effThresholds[$role] ?? 8;

            // Check injury history for adjustment
            $injuryCount = DB::table('injury_histories')
                ->where('player_id', $playerId)
                ->where('season_id', '<', $currentSeasonId)
                ->whereNull('recovery_date')
                ->count();
            $effThreshold += $injuryCount * 1; // Increase threshold for each unrecovered injury

            // Also check per-minute efficiency
            $perPerMinute = $seasonStats->per / max($seasonStats->avg_minutes_per_game, 1);
            $perThresholds = [
                'star player' => 0.8,
                'all star' => 0.7,
                'starter' => 0.6,
                'role player' => 0.5,
                'bench' => 0.4,
            ];
            $perThreshold = $perThresholds[$role] ?? 0.4;

            return $seasonStats->eff < $effThreshold && $perPerMinute < $perThreshold;
        }

        // Dynamic decline threshold based on role
        $seasonStats = $this->getPlayerSeasonStats($playerId, $teamId, $currentSeasonId);
        $role = strtolower($seasonStats->role ?? 'bench');
        $declineThreshold = in_array($role, ['bench', 'role player']) ? -0.10 : -0.15;

        return $improvementIndex <= $declineThreshold;
    }

    private function calculateImprovementIndex(int $playerId, int $teamId, int $currentSeasonId): ?float
    {
        $firstSeasonId = DB::table('seasons')->min('id');
        if ($currentSeasonId == $firstSeasonId) {
            return null; // No prior data
        }

        $yearsPro = $this->getYearsPro($playerId);

        // Fetch stats from last played season and one prior
        $pastSeasons = DB::table('player_season_stats as pss')
            ->join(DB::raw('(
            SELECT season_id, player_id, role
            FROM player_season_stats
            WHERE id IN (
                SELECT MAX(id)
                FROM player_season_stats
                GROUP BY season_id, player_id
            )
        ) as latest_stats'), function ($join) {
                $join->on('pss.season_id', '=', 'latest_stats.season_id')
                    ->on('pss.player_id', '=', 'latest_stats.player_id');
            })
            ->join('players as p', 'pss.player_id', '=', 'p.id')
            ->leftJoin('injury_histories as ih', function ($join) use ($currentSeasonId) {
                $join->on('pss.player_id', '=', 'ih.player_id')
                    ->where('ih.season_id', '<', $currentSeasonId)
                    ->whereNull('ih.recovery_date');
            })
            ->select(
                'pss.season_id',
                DB::raw('AVG(pss.per / NULLIF(pss.avg_minutes_per_game, 0)) as per_per_minute'),
                DB::raw('LOWER(latest_stats.role) as role'),
                DB::raw('AVG(pss.avg_minutes_per_game) as avg_mpg'),
                DB::raw('MAX(pss.total_games_played) as games_played'),
                DB::raw('MAX(pss.total_games) as total_games'),
                'p.age',
                DB::raw('COUNT(ih.id) as injury_count'),
                DB::raw('AVG(pss.eff) as avg_eff')
            )
            ->where('pss.player_id', $playerId)
            ->where('pss.team_id', $teamId)
            ->where('pss.season_id', '<', $currentSeasonId)
            ->groupBy('pss.season_id', 'latest_stats.role', 'p.age')
            ->orderByDesc('pss.season_id')
            ->limit(2)
            ->get()
            ->toArray();

        // Check for no prior data
        if (count($pastSeasons) < 1) {
            return null;
        }

        $latestSeason = $pastSeasons[0];
        $olderSeason = count($pastSeasons) > 1 ? $pastSeasons[1] : null;

        // Dynamic minimum games
        $minGamesPlayed = max(3, floor($latestSeason->total_games * 0.15));
        if ($latestSeason->games_played < $minGamesPlayed) {
            return null;
        }

        // Handle non-consecutive seasons
        if ($latestSeason->season_id < $currentSeasonId - 1) {
            return null; // Trigger current season eff/per check in hasNotImproved
        }

        // If only one prior season, return null
        if (!$olderSeason) {
            return null;
        }

        // Require similar minutes (within 25%) and minimum games for older season
        $olderMinGamesPlayed = max(3, floor($olderSeason->total_games * 0.15));
        if (
            $olderSeason->games_played < $olderMinGamesPlayed ||
            ($olderSeason->avg_mpg > 0 && abs($latestSeason->avg_mpg - $olderSeason->avg_mpg) / $olderSeason->avg_mpg > 0.25)
        ) {
            return null;
        }

        // Role scores
        $roleScores = [
            'star player' => 5,
            'all star' => 4,
            'starter' => 3,
            'role player' => 2,
            'bench' => 1,
        ];

        $latestRoleScore = $roleScores[$latestSeason->role] ?? 1;
        $olderRoleScore = $roleScores[$olderSeason->role] ?? 1;

        // Per-minute efficiency difference
        $perDiffPct = $olderSeason->per_per_minute > 0 ? ($latestSeason->per_per_minute - $olderSeason->per_per_minute) / $olderSeason->per_per_minute : 0;
        $roleDiff = $latestRoleScore - $olderRoleScore;
        $mpgDiffPct = $olderSeason->avg_mpg > 0 ? ($latestSeason->avg_mpg - $olderSeason->avg_mpg) / $olderSeason->avg_mpg : 0;

        // Injury adjustment (using injuries.php performance_impact approximation)
        $injuryPenalty = $latestSeason->injury_count > 0 ? -0.05 * $latestSeason->injury_count : 0;

        // Age penalty
        $age = $latestSeason->age ?? 25;
        if ($age < 27) {
            $agePenalty = 0;
        } elseif ($age <= 30) {
            $agePenalty = -0.03 * ($age - 27);
        } else {
            $agePenalty = -0.12 - 0.05 * ($age - 30);
        }

        // Calculate improvement index with reduced role weight
        $improvementIndex = ($perDiffPct * 0.65) + ($roleDiff * 0.05) + ($mpgDiffPct * 0.2) + $agePenalty + $injuryPenalty;

        if ($yearsPro < 2 && $improvementIndex < 0) {
            $improvementIndex *= 0.5; // Leniency for young players
        }

        return $improvementIndex;
    }

    private function isRebuildingTeam(int $teamId): bool
    {
        $seasonCount = DB::table('seasons')->count();

        $standings = DB::table('standings_view')
            ->where('team_id', $teamId)
            ->first();

        if (!$standings) return false;

        $wins = (int) ($standings->wins ?? 0);
        $losses = (int) ($standings->losses ?? 0);
        $totalGames = $wins + $losses;

        // Avoid calling a team "rebuilding" too early
        if ($totalGames < 5) return false;

        $scoreDiff = $standings->score_difference ?? 0;
        $chemistry = $standings->chemistry ?? 100;
        $last5 = strtolower($standings->last_5_games ?? '');
        $recentWins = substr_count($last5, 'w');

        // Flags that apply in all seasons
        $flags = 0;
        $flags += $wins < 10 ? 1 : 0;
        $flags += $scoreDiff < -5 ? 1 : 0;
        $flags += $chemistry < 50 ? 1 : 0;
        $flags += $recentWins <= 1 ? 1 : 0;

        // If league has history, add legacy-based flags
        if ($seasonCount > 1) {
            $flags += ($standings->championships ?? 0) == 0 ? 1 : 0;
            $flags += ($standings->playoff_appearances ?? 0) < 2 ? 1 : 0;
        }

        // You can adjust how many flags are needed (3 is safe)
        return $flags >= 3;
    }

    private function getPlayerSeasonStats(int $playerId, int $teamId, int $seasonId)
    {
        $stats = DB::table('player_season_stats')
            ->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->where('team_id', $teamId)
            ->first();

        return $stats;
    }

    private function totalTeamGames($seasonId, $teamId)
    {

        $gamesPlayedCount = DB::table('schedules')
            ->where('season_id', $seasonId)
            ->where(function ($query) use ($teamId) {
                $query->where('home_id', $teamId)
                    ->orWhere('away_id', $teamId);
            })
            ->count();

        return $gamesPlayedCount;
    }
}
