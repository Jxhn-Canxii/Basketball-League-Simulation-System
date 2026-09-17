<?php

namespace App\Services\Player;

use App\Models\Player;
use App\Services\Contract\ContractService;
use App\Services\Helper\HelperService;
use App\Services\Player\PlayerValuationService;
use App\Services\Stats\PlayerSeasonStatsService;
use Illuminate\Support\Facades\DB;

class FreeAgencyService
{
    protected $storeStats;
    protected $helper;
    protected $contractService;
    protected $valuationService;

    public function __construct()
    {
        // instantiate once so other methods can use it via $this->storeStats
        $this->storeStats = new PlayerSeasonStatsService();
        $this->helper = new HelperService();
        $this->contractService = new ContractService();
        $this->valuationService = new PlayerValuationService();
    }

    public function updateInjuryFreeAgents()
    {
        // Update injury recovery games for free agents and mark them as not injured if recovery games reach 0
        $deductionPerGame = 1; // Deduct 1 of a game


        $deductInjuryGames = DB::table('players')
            ->where('is_active', 1)
            ->where('is_injured', 1)
            ->where('injury_recovery_games', '>', 0)
            ->update([
                'injury_recovery_games' => DB::raw("GREATEST(injury_recovery_games - $deductionPerGame, 0)")
            ]);


        // Check if any rows were actually updated
        $affectedRows = DB::table('players')
            ->where('is_active', 1)
            ->where('is_injured', 1)
            ->where('injury_recovery_games', 0)
            ->count(); // Count players whose recovery reached 0

        if ($affectedRows > 0) {
            DB::table('players')
                ->where('is_active', 1)
                ->where('is_injured', 1)
                ->where('injury_recovery_games', '<=', 0)
                ->update(['is_injured' => 0]);
        }
    }

    public function getBestFreeAgentAvailable($position)
    {
        $positions = explode('/', strtoupper($position)); // Normalize casing

        // Flexible position filter: match any part of multi-position fields
        $positionFilter = function ($query) use ($positions) {
            $query->where(function ($q) use ($positions) {
                foreach ($positions as $pos) {
                    $q->orWhere('players.position', 'LIKE', '%' . $pos . '%');
                }
            });
        };

        // Get latest season id (adjust if your season logic is different)
        $latestSeasonId = get_current_season_id();

        // Top 10 by overall_rating
        $byOverall = DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(10)
            ->get();

        // Top 10 by awards count
        $byAwards = DB::table('players')
            ->leftJoin('season_awards', 'players.id', '=', 'season_awards.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                DB::raw('COUNT(season_awards.id) as awards_count')
            )
            ->groupBy(
                'players.id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('awards_count')
            ->limit(10)
            ->get();

        // Top 10 by EFF in latest season
        $byEff = DB::table('players')
            ->leftJoin('player_season_stats', 'players.id', '=', 'player_season_stats.player_id')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where('player_season_stats.season_id', $latestSeasonId)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role',
                'player_season_stats.eff'
            )
            ->orderByDesc('player_season_stats.eff')
            ->limit(10)
            ->get();

        // Merge all and deduplicate by player_id
        $merged = $byOverall->merge($byAwards)->merge($byEff)->unique('player_id')->values();

        // Return a random player from the merged top candidates
        if ($merged->isNotEmpty()) {
            return $merged->random();
        }

        // Fallback: any available player at the position
        return DB::table('players')
            ->where('players.is_active', 1)
            ->where('players.is_injured', 0)
            ->where('players.team_id', 0)
            ->where($positionFilter)
            ->select(
                'players.id as player_id',
                'players.name',
                'players.position',
                'players.team_id',
                'players.overall_rating',
                'players.injury_history',
                'players.age',
                'players.role'
            )
            ->orderByDesc('players.overall_rating')
            ->limit(1)
            ->first();
    }

    public function generateFreeAgencyOffers($seasonId = null): array
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $teams = DB::table('teams')->select('id', 'name')->get();
        $freeAgents = DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->where('age', '<=', 65)
            ->get();

        $offers = [];

        foreach ($freeAgents as $player) {
            $playerOffers = [];

            foreach ($teams as $team) {
                $offer = $this->contractService->getContractOffer($player, $team);
                $playerOffers[] = [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'salary' => $offer['salary'],
                    'years' => $offer['years'],
                    'contract_type' => $offer['contract_type'],
                    'score' => $this->scoreOffer($player, $team, $offer, $seasonId),
                ];
            }

            $offers[] = [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'best_offer' => $this->playerEvaluateOffers($player, $playerOffers),
                'offers' => $playerOffers,
            ];
        }

        return $offers;
    }

    public function playerEvaluateOffers($player, array $offers)
    {
        if (empty($offers)) {
            return null;
        }

        usort($offers, function ($left, $right) {
            return ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
        });

        return $offers[0];
    }

    public function handleRestrictedFA($player, $offer)
    {
        $currentTeamId = (int) ($player->team_id ?? 0);

        if ($currentTeamId <= 0) {
            return $offer;
        }

        $currentOffer = $this->contractService->getContractOffer($player, $currentTeamId);

        if (($currentOffer['salary'] ?? 0) >= (($offer['salary'] ?? 0) * 0.95)) {
            $offer['team_id'] = $currentTeamId;
            $offer['team_name'] = DB::table('teams')->where('id', $currentTeamId)->value('name');
            $offer['salary'] = $currentOffer['salary'];
            $offer['years'] = $currentOffer['years'];
            $offer['contract_type'] = $currentOffer['contract_type'];
        }

        return $offer;
    }

    public function signVeteranMinimum($teamId, $seasonId = null)
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $player = DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->where('age', '>=', 30)
            ->orderByDesc('overall_rating')
            ->first();

        if (!$player) {
            return null;
        }

        $offer = $this->contractService->getContractOffer($player, $teamId);
        $cap = $this->contractService->getSalaryCapValues($seasonId);
        $offer['salary'] = $cap['vet_min_value'];
        $offer['years'] = 1;
        $offer['contract_type'] = 'vet_min';

        return $this->signPlayer($player, $teamId, $offer, $seasonId);
    }

    //getFreeAgentsByPositionAndCompositeScore
    public function getBestAvailableFreeAgent($position, $usedPlayerIds)
    {
        $seasonId = get_current_season_id();

        $query = Player::select(
            'players.*',
            'teams.acronym as drafted_team',
            DB::raw("(
                    SELECT GROUP_CONCAT(CONCAT(award_name, ' (Season ', season_id, ')') SEPARATOR ', ')
                    FROM season_awards
                    WHERE season_awards.player_id = players.id
                ) as awards"),
            DB::raw("(
                    SELECT CONCAT('Finals MVP (Season ', seasons.id, ')')
                    FROM seasons
                    WHERE seasons.finals_mvp_id = players.id
                    ORDER BY seasons.id DESC
                    LIMIT 1
                ) as finals_mvp"),
            DB::raw("(
                    CASE WHEN EXISTS (
                        SELECT 1 FROM seasons WHERE seasons.finals_mvp_id = players.id
                    ) THEN 1 ELSE 0 END
                ) as is_finals_mvp"),
            DB::raw("(
                    SELECT GROUP_CONCAT(seasons.name SEPARATOR ', ')
                    FROM seasons
                    WHERE seasons.finals_mvp_id = players.id
                ) as finals_mvp_seasons")
        )
            ->where('players.contract_years', 0) // Only free agents
            ->where('players.is_active', 1) // Only active players
            ->leftJoin('teams', 'players.drafted_team_id', '=', 'teams.id');

        if ($position) {
            $query->where('players.position', 'LIKE', "%$position%");
        }

        if (!empty($usedPlayerIds)) {
            $query->whereNotIn('players.id', $usedPlayerIds);
        }

        $query->orderByRaw("
            LENGTH(awards) DESC,
            is_finals_mvp DESC,
            FIELD(role, 'star player', 'all star', 'starter', 'role player', 'bench')
        ");

        if($seasonId == 0){
            $query->limit(20); // Add randomization
            $query->inRandomOrder(); // Add randomization
        }

        return $query->first();
    }

    public function signPlayer($player, int $teamId, array $offer, int $seasonId)
    {
        if ($teamId <= 0) {
            return null;
        }

        if (!$this->contractService->canSignPlayer($teamId, $offer['salary'], $seasonId)) {
            return null;
        }

        DB::transaction(function () use ($player, $teamId, $offer, $seasonId) {
            $teamName = $this->helper->getTeamName($teamId);

            DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'team_id' => $teamId,
                    'contract_years' => $offer['years'],
                    'salary' => $offer['salary'],
                    'contract_type' => $offer['contract_type'],
                    'player_option' => $offer['player_option'] ?? false,
                    'team_option' => $offer['team_option'] ?? false,
                    'no_trade_clause' => $offer['no_trade_clause'] ?? false,
                ]);

            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $seasonId,
                'details' => $player->name . ' signed with team ' . $teamName .' for ₱' . number_format((float) $offer['salary'], 2) . ' on a ' . $offer['contract_type'] . ' contract. For '.$offer['years']. ' years.',
                'from_team_id' => 0,
                'to_team_id' => $teamId,
                'status' => 'signed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('player_contracts')->insert([
                'player_id' => $player->id,
                'season_id' => $seasonId,
                'team_id' => $teamId,
                'salary' => $offer['salary'],
                'contract_years' => $offer['years'],
                'contract_type' => $offer['contract_type'],
                'player_option' => $offer['player_option'] ?? false,
                'team_option' => $offer['team_option'] ?? false,
                'no_trade_clause' => $offer['no_trade_clause'] ?? false,
                'status' => 'signed',
            ]);
        });

        return [
            'player_id' => $player->id,
            'team_id' => $teamId,
            'salary' => $offer['salary'],
            'years' => $offer['years'],
            'contract_type' => $offer['contract_type'],
        ];
    }

    public function runFreeAgencyPeriod($seasonId = null): array
    {
        $seasonId = $seasonId ?? get_current_season_id();
        $offers = $this->generateFreeAgencyOffers($seasonId);
        $signed = [];

        foreach ($offers as $bundle) {
            $player = DB::table('players')->where('id', $bundle['player_id'])->first();
            if (!$player) {
                continue;
            }

            $bestOffer = $bundle['best_offer'];
            if (!$bestOffer) {
                continue;
            }

            $bestOffer = $this->handleRestrictedFA($player, $bestOffer);
            $signedRecord = $this->signPlayer($player, (int) $bestOffer['team_id'], $bestOffer, $seasonId);

            if ($signedRecord) {
                $signed[] = $signedRecord;
            }
        }

        return [
            'season_id' => $seasonId,
            'signed' => $signed,
            'signed_count' => count($signed),
        ];
    }

    /**
        * Fill open roster spots for a team after a trade.
        *
        * This is specifically for in-season roster vacancies.
        * It does NOT run the entire free agency process.
        *
        * @param int $teamId
        * @param int|null $maxSignings
        * @param int|null $preferredPosition
        * @return array
    */
    public function auditTeamRosterSpot(
        int $teamId,
        ?int $maxSignings = null,
        ?string $preferredPosition = null
    ): array 
    {
        $seasonId = get_current_season_id();

        if (!$seasonId) {
            return [
                'success' => false,
                'team_id' => $teamId,
                'signed' => [],
                'message' => 'Current season could not be determined.',
            ];
        }

        // Get the number of rounds that are already simulated (status != 2)
        $signed = [];
        $skipped = [];

        /*
        * Keep filling until the roster reaches 15 players
        * or no suitable free agent can be signed.
        */
        while (true) {

            /*
            * Count active players only.
            */
            $rosterCount = DB::table('players')
                ->where('team_id', $teamId)
                ->where('is_active', 1)
                ->count();

            /*
            * Roster is full.
            */
            if ($rosterCount == 15) {
                break;
            }


            /*
            * Roster is overloaded.
            */
            if ($rosterCount > 15) {
                $playerNeedsToWaive = CEIL($rosterCount - 15); //18 - 15 = 3 needs to be waived

                $this->clearRosterSpot($teamId,$seasonId,$playerNeedsToWaive);

                break;
            }

            /*
            * Optional safety limit.
            *
            * Example:
            * fillOpenRosterSpotsAfterTrade($teamId, 1)
            *
            * means only sign one player.
            */
            if ($maxSignings !== null && count($signed) >= $maxSignings) {
                break;
            }

            /*
            * Determine the position we should target.
            *
            * If a preferred position was supplied, use it.
            * Otherwise automatically determine the weakest position.
            */
            $position = $preferredPosition;

            if (!$position) {
                $position = $this->getTeamReplacementPosition($teamId);
            }

            /*
            * Find the best available free agent.
            *
            * First try the team's positional need.
            */
            $candidate = null;

            if ($position) {
                $candidate = $this->getBestFreeAgentAvailable($position);
            }

            /*
            * If no player exists at the needed position,
            * fall back to the best available free agent.
            */
            if (!$candidate) {
                $candidate = $this->getBestFreeAgentAvailable(null);
            }

            /*
            * Nothing available.
            */
            if (!$candidate) {
                $skipped[] = [
                    'reason' => 'No suitable free agent available.',
                    'position' => $position,
                ];

                break;
            }

            /*
            * getBestFreeAgentAvailable() returns player_id,
            * so retrieve the actual Player model.
            */
            $playerId = $candidate->player_id ?? $candidate->id ?? null;

            if (!$playerId) {
                $skipped[] = [
                    'reason' => 'Free agent candidate has no valid player ID.',
                ];

                break;
            }

            $player = Player::find($playerId);

            if (!$player) {
                $skipped[] = [
                    'reason' => 'Candidate player could not be found.',
                    'player_id' => $playerId,
                ];

                break;
            }

            /*
            * Make sure the player is still a free agent.
            *
            * This protects against another operation signing
            * the player between queries.
            */
            if (
                (int) $player->team_id !== 0 ||
                !(bool) $player->is_active ||
                (bool) $player->is_injured
            ) {
                $skipped[] = [
                    'reason' => 'Candidate is no longer available.',
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                ];

                continue;
            }

            /*
            * Let ContractService determine the contract.
            *
            * This is important because we don't want another
            * salary/contract calculation specifically for trades.
            */
            $offer = $this->contractService->getContractOffer(
                $player,
                $teamId
            );

            if (!$offer) {
                $skipped[] = [
                    'reason' => 'No contract offer could be generated.',
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                ];

                continue;
            }

            /*
            * Respect the team's salary-cap rules.
            */
            if (
                !$this->contractService->canSignPlayer(
                    $teamId,
                    $offer['salary'] ?? 0,
                    $seasonId
                )
            ) {
                $skipped[] = [
                    'reason' => 'Team cannot afford the available free agent.',
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                    'salary' => $offer['salary'] ?? 0,
                ];

                /*
                * We should not repeatedly select the same player.
                *
                * Try another available candidate by temporarily
                * excluding this player below.
                */
                $candidate = $this->getNextAffordableFreeAgent(
                    $teamId,
                    $position,
                    [$player->id],
                    $seasonId
                );

                if (!$candidate) {
                    break;
                }

                $player = $candidate;
                $offer = $this->contractService->getContractOffer(
                    $player,
                    $teamId
                );

                if (
                    !$offer ||
                    !$this->contractService->canSignPlayer(
                        $teamId,
                        $offer['salary'] ?? 0,
                        $seasonId
                    )
                ) {
                    break;
                }
            }

            /*
            * Use the EXISTING signPlayer() method.
            *
            * This means:
            * - players table is updated
            * - contract years are assigned
            * - salary is assigned
            * - contract type is assigned
            * - options are assigned
            * - transaction is logged
            * - player_contracts is created
            */
            $signedPlayer = $this->signPlayer(
                $player,
                $teamId,
                $offer,
                $seasonId
            );

            if (!$signedPlayer) {
                $skipped[] = [
                    'reason' => 'Contract signing failed.',
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                ];

                break;
            }

            $signed[] = [
                'player_id' => $player->id,
                'player_name' => $player->name,
                'position' => $player->position,
                'salary' => $offer['salary'],
                'years' => $offer['years'],
                'contract_type' => $offer['contract_type'],
            ];

            /*
            * If a preferred position was supplied, only use it
            * for the first signing. After that, recalculate needs.
            */
            $preferredPosition = null;
        }

        $finalRosterCount = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->count();

        return [
            'success' => true,
            'team_id' => $teamId,
            'signed' => $signed,
            'signed_count' => count($signed),
            'roster_count' => $finalRosterCount,
            'open_spots' => max(0, 15 - $finalRosterCount),
            'skipped' => $skipped,
        ];
    }
    // Waive a player (make them inactive)
    public function waivePlayer($request)
    {

        $player = Player::findOrFail($request->id);
        $player->update(['is_active' => false, 'team_id' => null]);

        return response()->json([
            'message' => 'Player waived successfully',
            'player' => $player,
        ]);
    }

    // Extend player's contract
    public function extendContract($request)
    {

        $player = Player::findOrFail($request->id);

        $newContractEnd = $player->contract_expires_at
            ? $player->contract_expires_at->addYears($request->additional_years)
            : now()->addYears($request->additional_years);

        $player->update([
            'contract_years' => min($player->contract_years + $request->additional_years, 5),
            'contract_expires_at' => $newContractEnd,
        ]);

        return response()->json([
            'message' => 'Contract extended successfully',
            'player' => $player,
        ]);
    }

    public function assignPlayerToRandomTeam($request)
    {

        // Fetch teams with fewer than 15 players
        $teamIds = DB::table('teams')
            ->leftJoin('players', 'teams.id', '=', 'players.team_id')
            ->select('teams.id')
            ->groupBy('teams.id')
            ->havingRaw('SUM(CASE WHEN players.is_active = 1 THEN 1 ELSE 0 END) < 15')
            ->pluck('teams.id');


        $teamsCount = $teamIds->count();

        if ($teamsCount === 0) {
            return response()->json([
                'message' => 'No teams available with fewer than 15 players.',
            ], 400);
        }

        // Fetch the player
        $player = Player::find($request->player_id);

        if (!$player) {
            return response()->json([
                'message' => 'Player not found.',
            ], 404);
        }

        $eligibleTeams = $teamIds->filter(function ($teamId) use ($player) {
            $offer = $this->contractService->getContractOffer($player, $teamId);

            return $this->contractService->canSignPlayer($teamId, $offer['salary']);
        })->values();

        if ($eligibleTeams->isEmpty()) {
            return response()->json([
                'message' => 'No teams can currently afford this player.',
            ], 400);
        }

        $teamId = $eligibleTeams->random();

        $offer = $this->contractService->getContractOffer($player, $teamId);
        $contractYears = $offer['years'];

        // Update the player's team and contract years
        $player->update([
            'team_id' => $teamId,
            'contract_years' => $contractYears,
            'salary' => $offer['salary'],
            'contract_type' => $offer['contract_type'],
            'player_option' => $offer['player_option'],
            'team_option' => $offer['team_option'],
            'no_trade_clause' => $offer['no_trade_clause'],
        ]);

        return response()->json([
            'message' => 'Player successfully assigned to a team. Remaining Teams that needed players: ' . $teamsCount,
            'team_id' => $teamId,
            'team_count' =>  $teamsCount,
            'salary' => $offer['salary'],
            'contract_type' => $offer['contract_type'],
        ]);
    }

    public function assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId)
    {
        // Team information
        $teamId = $team->id;
        $teamName = $team->name;
        $offer = $this->contractService->getContractOffer($player, $team);
        $contractYears = $offer['years'];

        // Start a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            if (!$this->contractService->canSignPlayer($teamId, $offer['salary'], $currentSeasonId)) {
                throw new \RuntimeException('Team cannot afford this signing under current cap rules.');
            }

            // Update the player's team_id and contract_years using DB
            if ($seasonId == 0) {
                DB::table('players')
                    ->where('id', $player->id)
                    ->update([
                        'team_id' => $teamId,
                        'drafted_team_id' => $teamId,
                        'is_drafted' => true,
                        'contract_years' => $contractYears,
                        'salary' => $offer['salary'],
                        'contract_type' => $offer['contract_type'],
                        'player_option' => $offer['player_option'],
                        'team_option' => $offer['team_option'],
                        'no_trade_clause' => $offer['no_trade_clause'],
                        'draft_status' => 'Special Draft'
                    ]);
            } else {
                DB::table('players')
                    ->where('id', $player->id)
                    ->update([
                        'team_id' => $teamId,
                        'contract_years' => $contractYears,
                        'salary' => $offer['salary'],
                        'contract_type' => $offer['contract_type'],
                        'player_option' => $offer['player_option'],
                        'team_option' => $offer['team_option'],
                        'no_trade_clause' => $offer['no_trade_clause']
                    ]);
            }


            // Insert the transaction record into the transactions table
            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $currentSeasonId,
                'details' => $player->name . ' has signed for ' . $teamName . ' for ' . $contractYears . ' years on a ' . $offer['contract_type'] . ' contract worth ₱' . number_format((float) $offer['salary'], 2) . '.',
                'from_team_id' => 0, // Assuming the player is a free agent and has no previous team
                'to_team_id' => $teamId,
                'status' => 'signed',
            ]);

            DB::table('player_contracts')->insert([
                'player_id' => $player->id,
                'season_id' => $currentSeasonId,
                'team_id' => $teamId,
                'salary' => $offer['salary'],
                'contract_years' => $offer['years'],
                'contract_type' => $offer['contract_type'],
                'player_option' => $offer['player_option'] ?? false,
                'team_option' => $offer['team_option'] ?? false,
                'no_trade_clause' => $offer['no_trade_clause'] ?? false,
                'status' => 'signed',
            ]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollback();

            // Rethrow or handle the error as needed
            throw $e;
        }
    }

    public function signFreeAgent($request)
    {
        $player = Player::findOrFail($request->player_id);
        $team = DB::table('teams')->where('id', $request->team_id)->first();

        if (!$team) {
            return response()->json(['message' => 'Team not found.'], 404);
        }

        $seasonId = get_current_season_id() ?? 0;
        $this->assignPlayerToTeam($player, $team, $seasonId, 1);

        return response()->json([
            'message' => 'Free agent signed successfully.',
            'player_id' => $player->id,
            'team_id' => $team->id,
        ]);
    }

    public function assignRemainingFreeAgents()
    {
        $seasonId = get_current_season_id() ?? 0;
        $currentSeasonId = $seasonId + 1;

        // Minimum required players per position for each team
        $minimumPositionCounts = [
            'PG' => 3,
            'SG' => 3,
            'SF' => 3,
            'PF' => 3,
            'C' => 3,
        ];

        // Step 1: Check position availability first
        $positionCheck = $this->checkPositionAvailability();

        // If checkPositionAvailability returns a response (indicating an error)
        if ($positionCheck !== true) {
            // If there is an issue (e.g., not enough players for some positions), return the response
            return $positionCheck;
        }

        // Get all active players and group by position
        $activePlayers = DB::table('players')
            ->where('is_active', 1) // Only active players
            ->select('id', 'position')
            ->get()
            ->groupBy('position'); // Group players by position


        // Proceed with assigning players to teams
        $teamsWithFewMembers = DB::table('teams')
            ->leftJoin('players', 'teams.id', '=', 'players.team_id')
            ->select('teams.id', 'teams.name', DB::raw('COUNT(players.id) as player_count'))
            ->groupBy('teams.id', 'teams.name')
            ->havingRaw('COUNT(players.id) < 15')
            ->inRandomOrder() // Add randomization
            ->get();
        
        $activeTeams = DB::table('teams')
            ->select('teams.id', 'teams.name')
            ->groupBy('teams.id', 'teams.name')
            ->inRandomOrder() // Add randomization
            ->get();

        $teamsCount = $teamsWithFewMembers->count();
        if ($teamsCount === 0) {
            $update = ($currentSeasonId == 1) ? $this->updateTeamRolesBasedOnStatsByRating() : $this->storeNextSeasonStatsPerTeam();

            if ($update) {
                if ($seasonId == 0) {
                    DB::table('players')
                        ->where('draft_id', 1)
                        ->where('is_drafted', 0)
                        ->update([
                            'draft_id' => 1,
                            'team_id' => 0,
                            'contract_years' => 0,
                            'draft_status' => 'Undrafted',
                            'is_rookie' => 1,
                        ]);
                } else {
                    DB::table('seasons')
                        ->where('id',  $seasonId)
                        ->update(['status' => config('timeline.player_signings')]);
                }

                return response()->json([
                    'error' => true,
                    'message' => 'All teams have signed 15 players, and roles have been updated based on last season\'s stats.',
                    'team_count' => $teamsCount,
                    'current_season_id' => $currentSeasonId,
                    'update' => $update
                ], 401);
            } else {
                return response()->json([
                    'message' => 'Role assigning error!',
                    'update' => $update,
                    'current_season_id' => $currentSeasonId
                ], 200);
            }
        } else {

            $usedPlayerIds = []; // Keep track of already-assigned players

            foreach ($teamsWithFewMembers as $team) {

                $teamPosCounts = $this->getTeamPositionCounts($team->id);
                $currentPlayerCount = $team->player_count;

                // Fill minimum position requirements first
                foreach ($minimumPositionCounts as $position => $minRequired) {
                    while (($teamPosCounts[$position] ?? 0) < $minRequired && $currentPlayerCount < 15) {
                        // Get the best available player for the current position
                        $player = $this->getBestAvailableFreeAgent($position, $usedPlayerIds);
                        if (!$player) break;

                        // Assign player to team
                        $this->assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId);
                        $usedPlayerIds[] = $player->id; // Mark as used
                        $teamPosCounts[$position] = ($teamPosCounts[$position] ?? 0) + 1;
                        $currentPlayerCount++;
                    }
                }

                // Fill remaining roster spots with best available players
                while ($currentPlayerCount < 15) {
                    // Get the best available player (no position requirement)
                    $player = $this->getBestAvailableFreeAgent('SG', $usedPlayerIds);
                    if (!$player) break;

                    // Assign player to team
                    $this->assignPlayerToTeam($player, $team, $currentSeasonId, $seasonId);
                    foreach (explode('/', $player->position) as $pos) {
                        $teamPosCounts[$pos] = ($teamPosCounts[$pos] ?? 0) + 1;
                    }
                    $usedPlayerIds[] = $player->id; // Mark as used
                    $currentPlayerCount++;
                }

            }

            // Final check for incomplete teams
            $incompleteTeams = DB::table('teams')
                ->leftJoin('players', 'teams.id', '=', 'players.team_id')
                ->select('teams.name', DB::raw('COUNT(players.id) as player_count'))
                ->groupBy('teams.name')
                ->havingRaw('COUNT(players.id) < 15')
                ->get()
                ->map(function ($team) {
                    return [
                        'team_name' => $team->name,
                        'players_needed' => 15 - $team->player_count,
                    ];
                })->filter(fn($team) => $team['players_needed'] > 0);

            return response()->json([
                'message' => 'Players have been assigned to teams.',
                'remaining_players' => count($activePlayers) - count($usedPlayerIds), // Calculate remaining players
                'incomplete_teams' => $incompleteTeams,
            ]);
        }
    }

    public function autoAssignFreeAgents()
    {
        $seasonId = get_current_season_id() ?? 0;
        $currentSeasonId = $seasonId + 1;

        $minimumPositionCounts = [
            'PG' => 3,
            'SG' => 3,
            'SF' => 3,
            'PF' => 3,
            'C'  => 3,
        ];

        $positionCheck = $this->checkPositionAvailability();
        if ($positionCheck !== true) return $positionCheck;

        $activePlayers = DB::table('players')
            ->where('is_active', 1)
            ->where('team_id', 0)
            ->select('id', 'name', 'position', 'role', 'overall_rating', 'loyalty_rating', 'satisfaction_rating', 'ambition_rating')
            ->get();

        // Load team reputation view
        $teamsReputation = DB::table('team_reputation_view')
            ->select('team_id', 'reputation_score', 'chemistry', 'is_defending_champion', 'overall_rank')
            ->get()
            ->keyBy('team_id');

        $teams = DB::table('teams')
            ->leftJoin('players', 'teams.id', '=', 'players.team_id')
            ->select('teams.id', 'teams.name', DB::raw('COUNT(players.id) as player_count'))
            ->groupBy('teams.id', 'teams.name')
            ->havingRaw('COUNT(players.id) < 15')
            ->get();

        // Sort teams by lowest reputation_score
        $teams = $teams->sortBy(function ($team) use ($teamsReputation) {
            $cred = $teamsReputation[$team->id] ?? null;
            return $cred->reputation_score ?? 1000; // lower score = weaker team
        });

        $usedPlayerIds = [];

        foreach ($teams as $team) {
            $teamPosCounts = $this->getTeamPositionCounts($team->id);
            $currentPlayerCount = $team->player_count;
            $cred = $teamsReputation[$team->id] ?? null;

            // Fill minimum positional requirements
            foreach ($minimumPositionCounts as $position => $minRequired) {
                while (($teamPosCounts[$position] ?? 0) < $minRequired && $currentPlayerCount < 15) {
                    $player = $this->selectBestCompatiblePlayer($activePlayers, $team, $teamPosCounts, $position, $usedPlayerIds, $cred);
                    if (!$player) break;

                    $this->assignPlayerWithTransaction($player, $team, $currentSeasonId, $seasonId);
                    $usedPlayerIds[] = $player->id;

                    foreach (explode('/', $player->position) as $pos) {
                        $teamPosCounts[$pos] = ($teamPosCounts[$pos] ?? 0) + 1;
                    }
                    $currentPlayerCount++;
                }
            }

            // Fill remaining slots
            while ($currentPlayerCount < 15) {
                $player = $this->selectBestCompatiblePlayer($activePlayers, $team, $teamPosCounts, null, $usedPlayerIds, $cred);
                if (!$player) break;

                $this->assignPlayerWithTransaction($player, $team, $currentSeasonId, $seasonId);
                foreach (explode('/', $player->position) as $pos) {
                    $teamPosCounts[$pos] = ($teamPosCounts[$pos] ?? 0) + 1;
                }
                $usedPlayerIds[] = $player->id;
                $currentPlayerCount++;
            }

        }

        return response()->json([
            'message' => 'Players assigned based on ratings, reputation, and personality.',
            'remaining_players' => count($activePlayers) - count($usedPlayerIds),
        ]);
    }

    /**
     * Private helper: Select best compatible player for a team
     */
    private function selectBestCompatiblePlayer($players, $team, $teamPosCounts, $targetPosition, $usedPlayerIds, $cred = null)
    {
        $candidates = $players->whereNotIn('id', $usedPlayerIds)
            ->filter(function ($p) use ($team, $teamPosCounts, $targetPosition, $cred) {
                if ($targetPosition) {
                    $positions = explode('/', $p->position);
                    if (!in_array($targetPosition, $positions)) return false;
                }

                // Avoid overloading stars
                $teamStarCount = DB::table('players')
                    ->where('team_id', $team->id)
                    ->whereIn('role', ['star player', 'all star'])
                    ->count();

                if ($teamStarCount >= 3 && $p->role === 'star player') return false;

                // === Team Credibility Influence ===
                $credibilityScore = 0;
                if ($cred) {
                    // Lower reputation score = weaker team
                    $credibilityScore += (100 - ($cred->reputation_score ?? 50)) * 0.3;
                    $credibilityScore += ($cred->chemistry ?? 50) * 0.2;
                    if ($cred->is_defending_champion ?? false) {
                        $credibilityScore += 10;
                    }
                }

                // === Personality Influence ===
                $joinChance = $p->loyalty_rating * 0.2
                    + $p->satisfaction_rating * 0.2
                    + $p->ambition_rating * 0.2
                    + $credibilityScore
                    + rand(0, 20); // Add randomness

                return $joinChance >= 60; // Raise threshold slightly with added influence
            });


        return $candidates->sortByDesc('overall_rating')->first();
    }

     /**
     * Private helper: Assign a player to a team with a DB transaction
     */
    private function assignPlayerWithTransaction($player, $team, $currentSeasonId, $seasonId)
    {
        $contractYears = $this->contractService->getContractYearsBasedOnRole($player->role);
        $teamId = $team->id;
        $teamName = $team->name;

        DB::beginTransaction();
        try {
            DB::table('players')
                ->where('id', $player->id)
                ->update([
                    'team_id' => $teamId,
                    'contract_years' => $contractYears,
                ]);

            DB::table('transactions')->insert([
                'player_id' => $player->id,
                'season_id' => $currentSeasonId,
                'details' => $player->name . ' signed with ' . $teamName . ' for ' . $contractYears . ' years.',
                'from_team_id' => 0,
                'to_team_id' => $teamId,
                'status' => 'signed',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getTeamPositionCounts($teamId)
    {
        $positionCounts = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->select('position', DB::raw('COUNT(*) as count'))
            ->groupBy('position')
            ->pluck('count', 'position')
            ->toArray();

        return $positionCounts;
    }

    public function checkPositionAvailability()
    {
        // Define the minimum number of players required per position for each team
        $minimumPositionCounts = [
            'PG' => 3,
            'SG' => 3,
            'SF' => 3,
            'PF' => 3,
            'C' => 3,
        ];

        // Get the total number of teams
        $totalTeams = DB::table('teams')->count();

        // Calculate the total required players per position (5 positions, 3 per position)
        $totalRequiredPlayers = $totalTeams * 3;

        // Loop through each position to check the availability of players
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];

        // Initialize an array to track position shortages
        $positionShortages = [];

        foreach ($positions as $position) {
            // Get the count of active players for the current position
            $availablePlayers = DB::table('players')
                ->where('position', 'like', '%' . $position . '%')  // Checks if position contains $position value
                ->where('is_active', 1)  // Only active players
                ->count();

            // Check if the available players are less than the required total players
            if ($availablePlayers < $totalRequiredPlayers) {
                // If there aren't enough players, record the shortage for that position
                $positionShortages[$position] = [
                    'needed' => $totalRequiredPlayers,
                    'available' => $availablePlayers,
                ];
            }
        }

        // If there are shortages, return a response with the message containing the shortages
        if (!empty($positionShortages)) {
            $shortageMessage = "Not enough players available for some positions: ";
            foreach ($positionShortages as $position => $data) {
                $shortageMessage .= "{$position} (Needed: {$data['needed']}, Available: {$data['available']}), ";
            }
            $shortageMessage = rtrim($shortageMessage, ', '); // Remove trailing comma

            return response()->json([
                'message' => $shortageMessage,
            ], 500);
        }

        // If no shortages, return true (or any success response)
        return true;
    }

     private function updateTeamRolesBasedOnStatsByRating()
    {
        $seasonId = get_current_season_id();
        $teams = DB::table('teams')->pluck('id');
        // use the shared PlayerSeasonStatsController instance instead of creating a new one here
        // $storeStats = new PlayerSeasonStatsController; // removed

        foreach ($teams as $teamId) {
            DB::beginTransaction();

            try {
                // Fetch all players on the team, ordered by overall rating (veterans and rookies combined)
                $players = DB::table('players')
                    ->where('team_id', $teamId)
                    ->orderByDesc('overall_rating')
                    ->get();

                // Assign roles based on overall rating
                $starCount = 0;
                $allStarCount = 0;
                $starterCount = 0;
                $rolePlayerCount = 0;

                foreach ($players as $index => $player) {
                    if ($starCount < 1) {
                        // Highest PER player gets 'star player'
                        $newRole = 'star player';
                        $starCount++;
                    } elseif ($allStarCount < 2) {
                        // Next two highest PER players get 'all star'
                        $newRole = 'all star';
                        $allStarCount++;
                    } elseif ($starterCount < 2) {
                        // Next two highest PER players get 'starter'
                        $newRole = 'starter';
                        $starterCount++;
                    } elseif ($rolePlayerCount < 5) {
                        // Next five highest PER players get 'role player'
                        $newRole = 'role player';
                        $rolePlayerCount++;
                    } else {
                        // Remaining players get 'bench'
                        $newRole = 'bench';
                    }

                    $updateRole = DB::table('players')
                        ->where('id', $player->id)
                        ->update(['role' => $newRole]);

                    // Store player stats for the next season
                    $this->storeStats->storePlayerNextSeasonStats($teamId, $player->id);
                }

                // Commit the transaction for this team
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                // Log the error message with more details
                return false; // Return false if an error occurs during the update
            }
        }

        return true; // Return true if all updates succeed
    }


    private function storeNextSeasonStatsPerTeam()
    {
        $teams = DB::table('teams')->pluck('id');
        foreach ($teams as $teamId) {
            DB::beginTransaction(); // Keep transaction but check rollback issues

            try {
                //Log::info("Processing Team ID: {$teamId}");

                $allPlayersStats = DB::table('players')
                    ->where('team_id', $teamId)
                    ->where('is_active', 1)
                    ->where('contract_years', '>', 0)
                    ->get();

                foreach ($allPlayersStats as $playerStat) {
                    // use shared PlayerSeasonStatsController instance
                    $this->updateContractExtensionData($playerStat->id);
                    $this->storeStats->storePlayerNextSeasonStats($teamId, $playerStat->id);


                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                //Log::error("Error assigning role for team {$teamId}: " . $e->getMessage());
                return $e->getMessage();
            }
        }

        return true;
    }

    private function updateContractExtensionData(int $playerId)
    {
        $seasonId = get_current_season_id();
        $nextSeasonId = get_current_season_id() + 1;

        $contractOffer = DB::table('player_contracts')
            ->where('player_id', $playerId)
            ->where('season_id', $seasonId)
            ->where('status','signed')
            ->first();

        if(!$contractOffer) return;

        DB::table('players')
            ->where('player_id', $playerId)
            ->update([
                'salary' =>  $contractOffer->salary,
                'contract_years' =>  $contractOffer->years,
                'contract_type' =>  $contractOffer->contract_type,
                'player_option' =>  $contractOffer->player_option ?? false,
                'team_option' =>  $contractOffer->team_option ?? false,
                'no_trade_clause' =>  $contractOffer->no_trade_clause ?? false,
            ]);

        DB::table('player_contracts')
            ->where('player_id', $playerId)
            ->where('contract_end','<', $nextSeasonId)
            ->update([
                'status' => 'ended',
            ]);

    }
    
    private function clearRosterSpot($teamId,$seasonId,$playerNeedsToWaive): void 
    {
        $playersToCut = DB::table('player_season_stats as pss')
                ->select('teams.name as team_name','pss.season_id','pss.team_id','pss.player_id')
                ->join('teams','teams.id','=','pss.team_id')
                ->join('players as p','p.id','=','pss.player_id')
                ->where('p.team_id', $teamId)
                ->where('pss.season_id', $seasonId)
                ->where('p.is_active', 1)
                ->orderBy('pss.player_valuation','asc')
                ->orderBy('p.salary','asc')
                ->limit($playerNeedsToWaive)
                ->get();
                
        if($playersToCut->count() == $playerNeedsToWaive){
    
            foreach ($playersToCut as $player) {

                DB::table('players')
                ->where('id', $player->player_id)
                ->update([
                    'team_id' => 0,
                    'contract_years' => 0,
                    'salary' => 0,
                    'contract_type' => null,
                    'player_option' => 0,
                    'team_option' => 0,
                    'no_trade_clause' => 0,
                ]);

            /*
            * Terminate previous contract if one exists.
            */
            DB::table('player_contracts')
                ->where('player_id', $player->player_id)
                ->where('status', 'signed')
                ->update([
                    'status' => 'terminated',
                    'updated_at' => now(),
                ]);

            DB::table('transactions')
                ->insert([
                    'player_id' => $player->player_id,
                    'season_id' => $player->season_id,
                    'from_team_id' => $player->team_id,
                    'to_team_id' => 0,
                    'status' => 'waived',
                    'details' => "Waived by {$player->team_name} to make roster space",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
    /**
     * Determine the position that needs the most help.
     *
     * Supports players with multi-position values such as:
     * PG/SG, SF/PF, PF/C, etc.
     */
    private function getTeamReplacementPosition(int $teamId): ?string
    {
        $positions = [
            'PG' => 0,
            'SG' => 0,
            'SF' => 0,
            'PF' => 0,
            'C'  => 0,
        ];

        $players = DB::table('players')
            ->where('team_id', $teamId)
            ->where('is_active', 1)
            ->select('position')
            ->get();

        foreach ($players as $player) {
            if (!$player->position) {
                continue;
            }

            $playerPositions = array_map(
                'trim',
                explode('/', strtoupper($player->position))
            );

            foreach ($playerPositions as $position) {
                if (isset($positions[$position])) {
                    $positions[$position]++;
                }
            }
        }

        /*
        * Your target is effectively 3 players per position.
        *
        * The position with the lowest coverage becomes the
        * preferred replacement position.
        */
        asort($positions);

        return array_key_first($positions);
    }

    /**
     * Find an affordable free agent.
     *
     * Used when the highest-rated candidate cannot be signed
     * because of the salary-cap rules.
     */
    private function getNextAffordableFreeAgent(
        int $teamId,
        ?string $position,
        array $excludePlayerIds,
        int $seasonId
    ) 
    {
        $query = DB::table('players')
            ->where('team_id', 0)
            ->where('is_active', 1)
            ->where('is_injured', 0);

        if (!empty($excludePlayerIds)) {
            $query->whereNotIn('id', $excludePlayerIds);
        }

        if ($position) {
            $query->where(
                'position',
                'LIKE',
                '%' . $position . '%'
            );
        }

        $players = $query
            ->orderByDesc('overall_rating')
            ->limit(25)
            ->get();

        foreach ($players as $player) {

            $offer = $this->contractService->getContractOffer(
                $player,
                $teamId
            );

            if (
                $offer &&
                $this->contractService->canSignPlayer(
                    $teamId,
                    $offer['salary'] ?? 0,
                    $seasonId
                )
            ) {
                return Player::find($player->id);
            }
        }

        return null;
    }

    private function scoreOffer($player, $team, array $offer, int $seasonId): float
    {
        $valuation = max(1.0, $this->valuationService->calculatePlayerValue($player));
        $salaryScore = (($offer['salary'] ?? 0) / $valuation) * 10;

        $teamWinRate = 0.5;
        $standing = DB::table('standings_view')
            ->where('season_id', $seasonId)
            ->where('team_id', $team->id)
            ->first();

        if ($standing) {
            $games = (int) (($standing->wins ?? 0) + ($standing->losses ?? 0));
            if ($games > 0) {
                $teamWinRate = ((int) ($standing->wins ?? 0)) / $games;
            }
        }

        $loyalty = (float) ($player->loyalty_rating ?? 50) / 10;
        $ambition = (float) ($player->ambition_rating ?? 50) / 10;

        return round(($salaryScore * 0.5) + ($teamWinRate * 10) + ($loyalty * 0.2) + ($ambition * 0.3), 2);
    }
}
