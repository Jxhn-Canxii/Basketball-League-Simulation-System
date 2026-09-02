# Liga: Basketball Simulation

## 🚧 Next Update Roadmap

The next major update for **Liga: Basketball Simulation** will focus on improving the game's internal architecture, database performance, and long-term scalability.

The goal is to make the codebase **cleaner, easier to maintain, faster to simulate, and capable of handling many seasons of historical data** without progressively slowing down the game.

---

## 1. 🏗️ Introduce Service Layer

### Goal

Move business logic out of controllers and into dedicated service classes.

Currently, some controllers contain database queries, simulation logic, calculations, and multiple responsibilities in the same methods. The next update will separate these concerns.

### Planned Structure

```text
Controller
    ↓
Service
    ↓
Repository / Query Layer
    ↓
Database
```

### Example

Instead of:

```php
CoachController
    ├── Database queries
    ├── Coach calculations
    ├── Contract logic
    ├── Retirement logic
    └── Team assignment
```

Move toward:

```text
CoachController
    ↓
CoachService
    ├── CoachCareerService
    ├── CoachContractService
    ├── CoachRetirementService
    └── CoachAssignmentService
```

### Objectives

* Reduce controller size
* Keep controllers focused on HTTP/API responsibilities
* Centralize business logic
* Improve code readability
* Make logic easier to test
* Make simulation systems reusable
* Reduce duplicated code
* Make future features easier to implement

---

# 2. 🧩 Segregate Functions Based on Concerns

Large methods will be reviewed and divided into smaller functions based on their actual responsibility.

### Example

Instead of one large season-processing method:

```text
processSeason()
    ├── update standings
    ├── simulate games
    ├── calculate player development
    ├── process injuries
    ├── process contracts
    ├── process coaches
    ├── process awards
    ├── process retirement
    └── archive data
```

Separate the responsibilities:

```text
SeasonService
    ├── simulateRegularSeason()
    ├── updateStandings()
    ├── processPlayerDevelopment()
    ├── processInjuries()
    ├── processContracts()
    ├── processCoachChanges()
    ├── processAwards()
    ├── processRetirements()
    └── archiveSeason()
```

### Objectives

* Single Responsibility Principle
* Smaller and easier-to-understand functions
* Easier debugging
* Easier unit testing
* Easier performance profiling
* Reduced side effects between systems

---

# 3. ⚡ Database Query Optimization

Review and optimize database queries throughout the simulation.

The goal is to reduce unnecessary database operations, especially during season simulation where thousands of players, games, teams, and statistics may be processed.

### Planned Improvements

* Identify N+1 queries
* Reduce unnecessary queries inside loops
* Use eager loading where appropriate
* Replace repeated queries with cached data
* Use bulk inserts and updates
* Use database transactions where appropriate
* Select only required columns
* Optimize complex joins
* Add appropriate database indexes
* Review frequently-used filters and sorting
* Reduce unnecessary `COUNT()` and existence queries
* Optimize season and player statistics queries

### Example

Avoid:

```php
foreach ($players as $player) {
    DB::table('player_stats')
        ->where('player_id', $player->id)
        ->first();
}
```

Prefer retrieving the required data once:

```php
$playerStats = DB::table('player_stats')
    ->whereIn('player_id', $playerIds)
    ->get()
    ->keyBy('player_id');
```

---

# 4. 🗄️ Historical Data Archiving

As Liga progresses through more seasons, historical data will continue to grow.

Keeping every piece of historical information in the primary operational tables can eventually make queries and simulations slower.

The next update will introduce a proper **data archiving strategy**.

### Potential Data to Archive

```text
Game statistics
Player game statistics
Season standings
Historical schedules
Historical player statistics
Historical team statistics
Playoff statistics
Historical contracts
Historical transactions
Retired players
Historical injuries
Awards history
Coach history
```

### Example Architecture

```text
ACTIVE DATA
│
├── Current Season
├── Active Players
├── Active Teams
├── Current Contracts
└── Current Statistics
        │
        │ End of Season
        ↓
ARCHIVE
│
├── Archived Seasons
├── Archived Games
├── Archived Player Stats
├── Archived Team Stats
├── Archived Contracts
└── Archived Transactions
```

The objective is to keep frequently accessed data small while preserving historical information for the game's history features.

---

# 5. 🚀 Simulation Performance

The overall goal is to make season simulation faster as Liga grows.

Performance improvements will focus on:

* Fewer database queries
* Fewer repeated calculations
* Better data retrieval
* Batch database operations
* Reduced memory usage
* Efficient historical data storage
* Better caching
* More efficient simulation services

### Target

A long-running Liga save should remain playable even after hundreds of simulated seasons.

---

# 6. 🧪 Testing & Reliability

As business logic moves into services, more of the simulation can be tested independently.

Planned testing areas:

```text
Player Development
Coach Development
Coach Retirement
Contracts
Free Agency
Trades
Draft
Standings
Playoffs
Awards
Injuries
Season Progression
Data Archiving
```

The goal is to make major simulation systems predictable and prevent changes in one system from unexpectedly breaking another.

---

# 🗺️ Development Order

The planned implementation order is:

### Phase 1 — Architecture

* [ ] Audit existing controllers
* [ ] Identify oversized controller methods
* [ ] Create service layer
* [ ] Move business logic from controllers
* [ ] Separate functions by concern
* [ ] Remove duplicated business logic

### Phase 2 — Database

* [ ] Audit database queries
* [ ] Identify N+1 queries
* [ ] Optimize frequently-used queries
* [ ] Review database indexes
* [ ] Reduce queries inside simulation loops
* [ ] Implement batch operations where possible

### Phase 3 — Data Architecture

* [ ] Identify historical data that should be archived
* [ ] Design archive tables
* [ ] Implement season archiving
* [ ] Move old game/statistical data into archives
* [ ] Update historical pages to read archived data
* [ ] Verify historical data integrity

### Phase 4 — Performance

* [ ] Profile season simulation
* [ ] Identify slow simulation processes
* [ ] Optimize expensive calculations
* [ ] Reduce memory consumption
* [ ] Introduce caching where appropriate
* [ ] Benchmark before and after optimization

### Phase 5 — Testing

* [ ] Add service-level tests
* [ ] Test season progression
* [ ] Test player progression
* [ ] Test coach progression
* [ ] Test contracts and transactions
* [ ] Test playoffs
* [ ] Test archiving
* [ ] Run full-season simulation tests

---

# 🎯 Main Objective

The purpose of this update is **not to add a large number of new gameplay features**.

The priority is to strengthen the foundation of Liga.

> **Clean architecture → optimized queries → efficient data storage → faster simulation → easier future development**

Once this foundation is in place, new systems and gameplay features can be added without continually increasing the complexity and performance cost of the existing codebase.

---

## Liga Development Philosophy

Liga should be able to simulate a basketball world that continues to evolve over many seasons while maintaining:

* 🏗️ Clean architecture
* ⚡ Fast simulation
* 🗄️ Efficient historical data
* 🧩 Separated responsibilities
* 🧪 Testable systems
* 📈 Long-term scalability
* 🏀 Rich basketball history

**Next major milestone: Refactor, Optimize, Archive, and Scale.**


commands

Run All View  table = php artisan db:run-view-tables