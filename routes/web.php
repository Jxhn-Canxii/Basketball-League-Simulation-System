<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\LeaguesController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SeasonsController;
use App\Http\Controllers\ConferenceController;
use App\Http\Controllers\PlayersController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\AwardsController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SimulateController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LeadersController;
use App\Http\Controllers\TradeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {

    Route::prefix('records/')->group(function(){
        Route::get('', [RecordsController::class, 'index'])->name('records.index');
        Route::post('season-champions', [RecordsController::class, 'champions'])->name('records.champions');
        Route::post('recent-games', [RecordsController::class, 'recent'])->name('records.recent');
        Route::post('top-scorer-teams', [RecordsController::class, 'topScorerTeams'])->name('records.team.topscorer');
        Route::post('winningest-teams', [RecordsController::class, 'winningestTeams'])->name('records.team.winningest');
        Route::post('rivals-per-team', [RecordsController::class, 'getRivalries'])->name('records.rivalries');
        Route::post('playoff-appearances', [RecordsController::class, 'playoffAppearances'])->name('records.playoff.appearances');
        Route::post('stats-leaders', [RecordsController::class, 'statsLeaders'])->name('records.player.stats.leaders');
        Route::get('override-playoff/{season_id}', [RecordsController::class, 'updatePlayerPlayoffAppearances'])->name('records.player.stats.override');
    });

    Route::prefix('teams/')->group(function(){
        Route::get('', [TeamsController::class, 'index'])->name('teams.index');
        Route::post('list-teams', [TeamsController::class, 'list'])->name('teams.list');
        Route::post('add-teams', [TeamsController::class, 'add'])->name('teams.add');
        Route::post('update-teams', [TeamsController::class, 'update'])->name('teams.update');
        Route::post('delete-teams', [TeamsController::class, 'delete'])->name('teams.delete');
        Route::post('team-info', [TeamsController::class, 'teaminfo'])->name('teams.info');
        Route::post('team-season-finals', [TeamsController::class, 'teamSeasonFinals'])->name('teams.season.finals');
        Route::post('team-season-standings', [TeamsController::class, 'teamSeasonStandings'])->name('teams.season.standings');
        Route::post('team-season-history', [TeamsController::class, 'teamSeasonHistory'])->name('teams.season.history');
        Route::post('team-last-season-performance', [TeamsController::class, 'teamLastSeason'])->name('teams.last.season');
        Route::post('team-recent-matches', [TeamsController::class, 'teamMatches'])->name('teams.matches');
        Route::post('team-head2head-records', [TeamsController::class, 'teamMatchesH2H'])->name('teams.matches.h2h');
        Route::post('team-rivals', [TeamsController::class, 'teamRivals'])->name('teams.rivals');
        Route::post('team-latest-season', [TeamsController::class, 'teamsLatestSeason'])->name('teams.latest.season');
        Route::post('team-transaction-history', [TeamsController::class, 'teamsTransactionHistory'])->name('teams.transaction.history');
        Route::post('team-match-history', [TeamsController::class, 'matchHistory'])->name('match.history');
        Route::post('team-recent-season-performance', [TeamsController::class, 'countTeamOnePicksAndCheckChampion'])->name('team.recent.performance');
        Route::post('team-per-conference', [TeamsController::class, 'getTeamsByConference'])->name('conference.team.dropdown');
        
    });

    Route::prefix('simulate/')->group(function(){
        Route::post('game-playoff', [SimulateController::class, 'simulatePlayoff'])->name('game.simulate.playoff');
        Route::post('game-regular', [SimulateController::class, 'simulateRegular'])->name('game.simulate.regular');
        Route::post('get-round-schedule-ids', [SimulateController::class, 'getScheduleIds'])->name('game.per.round');
        Route::post('game-per-round', [SimulateController::class, 'simulatePerRound'])->name('game.simulate.round');
        Route::get('overrride-all-team-roles', [SimulateController::class, 'updateRolesForAllTeams'])->name('override-team-roles');
        Route::get('testroles', [SimulateController::class, 'testRoleAssignment'])->name('test.roles');
        
    });

    Route::prefix('schedule/')->group(function(){
        Route::get('', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::post('list-schedules', [ScheduleController::class, 'list'])->name('schedule.list');
        //simulation and scheduling
        Route::post('create-schedule-regular', [ScheduleController::class, 'createSeasonandSchedule'])->name('create.schedule.regular');
        Route::post('create-schedule-playoff', [ScheduleController::class, 'playoffSchedule'])->name('create.schedule.playoff');

    });

    Route::prefix('leaders/')->group(function(){
        Route::get('', [LeadersController::class, 'index'])->name('leaders.index');
        Route::get('single-stats-leaders', [LeadersController::class, 'getSingleStatsLeaders'])->name('single.stats.leaders');
        Route::get('total-stats-leaders', [LeadersController::class, 'getTotalStatsLeaders'])->name('total.stats.leaders');
        Route::get('average-stats-leaders', [LeadersController::class, 'getAverageStatsLeaders'])->name('average.stats.leaders');
        Route::get('update-stats-leaders', [LeadersController::class, 'updateAllTimeTopStats'])->name('update.stats.leaders');
        Route::get('update-season-stats-leaders/{season_id}', [LeadersController::class, 'updateAllTimeTopStatsPerSeason'])->name('update.season.stats.leaders');
    });

    Route::prefix('ratings/')->group(function(){
        Route::post('update-player-status', [RatingsController::class, 'updateActivePlayers'])->name('update.player.status');
    });

    Route::prefix('analytics/')->group(function(){
        Route::get('', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::post('get-all-standings', [AnalyticsController::class, 'getAllStandings'])->name('analytics.standings');
        Route::get('player-stats', [AnalyticsController::class, 'countPlayers'])->name('analytics.player.count');
        Route::post('current-season-leaders', [AnalyticsController::class, 'getSeasonLeaders'])->name('players.season.leaders');
        Route::get('draft-statistics', [AnalyticsController::class, 'getDraftPlayerStatistics'])->name('draft.statistics');
        Route::get('alltime-game-records', [AnalyticsController::class, 'getAllStatistics'])->name('alltime.game.records');
    });

    Route::prefix('draft/')->group(function(){
        Route::post('players-list', [DraftController::class, 'rookieDraftees'])->name('draft.list');
        Route::get('draft-order', [DraftController::class, 'draftOrder'])->name('draft.orders');
        Route::get('draft-latest-results', [DraftController::class, 'draftResults'])->name('draft.results');
        Route::post('draft-season-results', [DraftController::class, 'draftResultsPerSeason'])->name('draft.season.results');
        Route::get('draft-players', [DraftController::class, 'draftPlayers'])->name('draft.players');

    });

    Route::prefix('seasons/')->group(function(){
        Route::get('', [SeasonsController::class, 'index'])->name('seasons.index');
        Route::get('season-details/{season_id}', [SeasonsController::class, 'details'])->name('seasons.details');
        Route::post('list-season', [SeasonsController::class, 'list'])->name('seasons.list');

        //season info
        Route::post('season-information', [SeasonsController::class, 'seasonInfo'])->name('seasons.info');
        Route::post('seasons-per-league', [SeasonsController::class, 'seasonsPerLeague'])->name('league.seasons');
        Route::post('seasons-per-league-paginate', [SeasonsController::class, 'seasonsPerLeaguePaginate'])->name('league.seasons.paginate');
        Route::post('dropdown-season', [SeasonsController::class, 'getSeasonsDropdown'])->name('seasons.dropdown');

    });

    Route::prefix('leagues/')->group(function(){
        Route::get('', [LeaguesController::class, 'index'])->name('leagues.index');
        Route::post('list-leagues', [LeaguesController::class, 'list'])->name('leagues.list');
        Route::post('add-league', [LeaguesController::class, 'add'])->name('leagues.add');
        Route::post('update-league', [LeaguesController::class, 'update'])->name('leagues.update');
        Route::post('delete-league', [LeaguesController::class, 'delete'])->name('leagues.delete');
        Route::get('dropdown-league', [LeaguesController::class, 'dropdown'])->name('leagues.dropdown');

        Route::get('reset-league', [LeaguesController::class, 'resetLeague'])->name('leagues.reset');  
    });

    Route::prefix('conferences/')->group(function(){
        Route::post('list-conferences', [ConferenceController::class, 'list'])->name('conferences.list');
        Route::post('add-conference', [ConferenceController::class, 'add'])->name('conferences.add');
        Route::post('delete-conference', [ConferenceController::class, 'delete'])->name('conferences.delete');

        Route::post('conference-standings', [ConferenceController::class, 'seasonStandings'])->name('conferences.standings');
        Route::post('conference-schedules', [ConferenceController::class, 'seasonSchedules'])->name('conferences.schedules');
        Route::post('conference-playoffs', [ConferenceController::class, 'seasonsPlayoffs'])->name('conferences.playoffs');
        Route::post('league-conference', [ConferenceController::class, 'leagueConference'])->name('conference.season.dropdown');

        Route::post('get-rounds-per-conference', [ConferenceController::class, 'getConferenceRoundNotSimulated'])->name('upcoming.rounds.season.conference');

        Route::post('get-rounds-per-season', [ConferenceController::class, 'getSeasonRoundNotSimulated'])->name('upcoming.rounds.season');
    });

    Route::prefix('games/')->group(function(){
        Route::post('box-score', [GameController::class, 'getBoxScore'])->name('game.boxscore');
    });
    Route::prefix('coaches/')->group(function(){
        Route::get('', [CoachController::class, 'index'])->name('coaches.index');
        Route::post('list-coaches', [CoachController::class, 'listCoaches'])->name('coaches.list');
        Route::post('add-coach', [CoachController::class, 'addFreeAgentCoach'])->name('coaches.add.free.agent');
        Route::get('assign-coach-teams', [CoachController::class, 'assignFreeAgentCoaches'])->name('assign.coach.teams');
        Route::get('end-coach-signings', [CoachController::class, 'endCoachSignings'])->name('end.coach.signings');
        Route::get('duplicate-coaches', [CoachController::class, 'fixDuplicateCoaches'])->name('check.duplicate.team.coach');
       
    });
    Route::prefix('players/')->group(function(){
        Route::get('', [PlayersController::class, 'index'])->name('players.index');
        Route::get('experience', [PlayersController::class, 'experience'])->name('experience.index');
        Route::get('freeagents', [PlayersController::class, 'freeAgents'])->name('freeagents.index');
        Route::post('list-players', [PlayersController::class, 'listTeamRoster'])->name('players.team.roster');
        Route::post('add-player', [PlayersController::class, 'addPlayer'])->name('players.add');
        Route::post('add-free-agent', [PlayersController::class, 'addFreeAgentPlayer'])->name('players.add.free.agent');

        Route::post('free-agents', [PlayersController::class, 'getFreeAgents'])->name('players.free.agents');
        Route::post('all-players', [PlayersController::class, 'getAllPlayers'])->name('players.list.all');
        Route::post('player-season-performance', [PlayersController::class, 'getPlayerSeasonperformance'])->name('players.season.performance');
        Route::post('player-play-off-performance', [PlayersController::class, 'getPlayerPlayoffperformance'])->name('players.playoff.performance');
        Route::post('player-main-performance', [PlayersController::class, 'getPlayerMainPerformance'])->name('players.main.performance');
        Route::post('player-transactions', [PlayersController::class, 'getPlayerTransactions'])->name('players.season.transactions');
        Route::post('player-role-history', [PlayersController::class, 'getRoleChangeHistory'])->name('players.role.history');
        Route::post('player-injury', [PlayersController::class, 'getPlayerInjuryHistory'])->name('players.season.injury');

        Route::post('player-game-logs', [PlayersController::class, 'getPlayerGameLogs'])->name('players.game.logs');
        Route::post('player-latest-game-logs', [PlayersController::class, 'getPlayerLatestGameLogs'])->name('players.latest.game.logs');
        Route::post('players-playoff-filters', [PlayersController::class, 'getPlayersWithFilters'])->name('filter.playoffs.player');

        Route::get('player-best-alltime', [PlayersController::class, 'getTop20PlayersAllTime'])->name('best.players.alltime');
        Route::post('player-best-alltime-by-team', [PlayersController::class, 'getTop10PlayersByTeam'])->name('best.team.players.alltime');
        Route::post('players-per-team-by-season', [PlayersController::class, 'getStarPlayersByTeam'])->name('best.team.star.players');
    });

    Route::prefix('transactions/')->group(function(){
        Route::post('assign-team-free-agents', [TransactionsController::class, 'assignPlayerToRandomTeam'])->name('assign.freeagent.teams');
        Route::post('auto-assign-team-free-agents', [TransactionsController::class, 'assignRemainingFreeAgents'])->name('auto.assign.freeagent.teams');
        Route::get('override-assign-team-free-agents', [TransactionsController::class, 'assignRemainingFreeAgents'])->name('override.auto.assign.freeagent.teams');
        Route::post('waive-player', [TransactionsController::class, 'waivePlayer'])->name('players.waive');
        Route::post('extend-contract-player', [TransactionsController::class, 'extendContract'])->name('players.contract.extend');
        Route::post('player-transactions', [TransactionsController::class, 'getTransactions'])->name('players.transactions');
        Route::post('recent-transactions', [TransactionsController::class, 'getRecentNonTransferTransactions'])->name('recent.transactions');
        
        // Route::post('freeagent-market', [TransactionsController::class, 'getFreeAgentsByCompositeScore'])->name('players.best.free.agent');
    });

    Route::prefix('awards/')->group(function(){
        Route::get('', [AwardsController::class, 'index'])->name('awards.index');
        Route::post('store-player-stats', [AwardsController::class, 'storePlayerSeasonStats'])->name('store.player.stats');
        Route::get('override-store-player-stats/{season_id}', [AwardsController::class, 'storeAllPlayerSeasonStats'])->name('store.player.stats.override');
        Route::post('player-awards', [AwardsController::class, 'storeSeasonAwards'])->name('player.awards');
        Route::get('player-awards-dropdown', [AwardsController::class, 'getAwardNamesDropdown'])->name('player.awards.dropdown');
        Route::post('player-awards-filter', [AwardsController::class, 'filterAwardsPerSeason'])->name('player.awards.filter');
        Route::post('player-season-awards', [AwardsController::class, 'getSeasonAwards'])->name('player.season.awards');
        Route::get('awarding/{season_id}', [AwardsController::class, 'storeSeasonAwardsAuto'])->name('awarding.per.season');
        Route::get('awards-mvp-status', [AwardsController::class, 'getFinalsMVPList'])->name('awards.mvp.status');
    });

    Route::prefix('trades/')->group(function(){
        Route::post('trade-list-pending', [TradeController::class, 'getPendingTradeProposals'])->name('trade.list.pending');
        Route::post('trade-list-approved', [TradeController::class, 'getApprovedTradeProposals'])->name('trade.list.approved');
        
        Route::post('trade-end-inseason', [TradeController::class, 'endInSeasonTradeWindow'])->name('trade.end.inseason');
        Route::post('trade-end-offseason', [TradeController::class, 'endOffSeasonTradeWindow'])->name('trade.end.offseason');
        // Route::post('trade-approve', [TradeController::class, 'approveTrade'])->name('trade.approve');
        // Route::post('trade-reject', [TradeController::class, 'rejectTrade'])->name('trade.reject');
        Route::post('trade-generate', [TradeController::class, 'generateTradeProposals'])->name('trade.generate');
        Route::post('trade-auto-decide', [TradeController::class, 'automatedTradeDecision'])->name('trade.decision.automated');
        
    });

    Route::prefix('users/')->group(function(){
        Route::get('', [UserController::class, 'index'])->name('users.index');
    });

    Route::prefix('profile/')->group(function(){
        Route::get('', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
