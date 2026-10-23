# Liga System Feature Plan

## Goal

Grow Liga into a deeper and more reliable basketball management simulation. This roadmap covers the full system: league operations, team management, player and coach careers, game simulation, history, and the user experience.

## Guiding priorities

1. Protect save data and keep season progression reliable.
2. Make simulation outcomes understandable and consistent across screens.
3. Add management choices that have visible consequences over time.
4. Keep long-running saves responsive as historical data grows.

## Roadmap

### 1. League and season operations

- Add configurable league rules for schedule length, playoff format, conferences, divisions, roster size, and overtime.
- Add a season calendar showing games, deadlines, draft, free agency, awards, and off-season milestones.
- Add a commissioner flow for expansion, relocation, team renaming, and league realignment between seasons.
- Make season progression resumable and safe to retry if a simulation job is interrupted.
- Add a season summary with champions, awards, records, major transactions, and notable storylines.

### 2. Team management and front office

- Add team budgets and salary-cap or payroll rules as configurable league options.
- Show roster needs and role coverage alongside contract years, salaries, and player availability.
- Add configurable coaching strategies for rotation, pace, offensive focus, defensive scheme, and late-game decisions.
- Make trades evaluate roster fit, team direction, contract value, and future picks in addition to player ratings.
- Provide a transaction review history showing the roster and financial impact of each move.

### 3. Player and coach careers

- Expand player development with skill-specific training, mentorship, role fit, morale, and playing-time satisfaction.
- Show career timelines for teams, contracts, awards, injuries, statistics, and major milestones.
- Make scouting uncertain: reports should show ranges and confidence that improve with scouting investment.
- Add coach tendencies, staff specialties, coaching chemistry, and career progression.
- Tie fatigue, injury risk, recovery, and workload to actual usage and medical or training staff quality.

### 4. Game simulation and presentation

- Track on-court lineups and actual playing intervals so substitutions and minutes played match the simulated rotation.
- Extend play-by-play with period transitions, substitutions, individual free throws, timeouts, and late-game sequences.
- Keep the score, box score, player availability, and play-by-play consistent after every game.
- Add team and player tactical tendencies that affect possession selection and outcomes.
- Add game recaps that summarize key runs, top performers, turning points, and injuries using recorded events and stats.

### 5. League history and records

- Build searchable history pages for seasons, champions, awards, playoffs, teams, players, coaches, and transactions.
- Add career and single-season record books with filters by league era and team.
- Preserve historical team identity and player affiliations when players change teams or retire.
- Define an archive policy for old game logs and statistics that keeps history accessible without slowing active seasons.

### 6. News, storylines, and engagement

- Generate news from actual simulation events such as streaks, records, trades, injuries, rivalries, and playoff races.
- Add a league activity feed with filters for team, player, transaction, and competition.
- Add configurable notifications for important events and upcoming user decisions.
- Provide season goals and team direction choices that shape front-office evaluation and offseason behavior.

### 7. User experience and accessibility

- Add global search for players, teams, coaches, seasons, and games.
- Improve dense tables with saved filters, sorting, pagination, and clear empty/loading/error states.
- Make core management screens usable on smaller displays and keyboard accessible.
- Add a consistent glossary and contextual explanations for ratings, contracts, cap rules, fatigue, and simulation results.
- Allow users to compare players, teams, contracts, and season statistics side by side.

### 8. Reliability, performance, and maintainability

- Move remaining business rules into focused services and keep controllers responsible for request handling.
- Find and fix N+1 queries; use bulk reads and writes for season simulation where appropriate.
- Add database indexes based on real query patterns and archive cold historical data without losing access to it.
- Make long-running simulations observable with progress, timing, and recoverable checkpoints.
- Add automated coverage for season progression, trades, contracts, draft, injuries, standings, playoffs, awards, and archival behavior.
- Add data-integrity checks for duplicate games, invalid rosters, score/stat mismatches, and incomplete season transitions.

## Suggested delivery order

### Phase 1 — Reliability foundation

- Map critical season workflows and their database writes.
- Make game and season simulation safe to resume after failure.
- Add data-integrity checks and targeted regression coverage.

### Phase 2 — Better simulation and game reporting

- Track actual lineups, substitutions, and player minutes.
- Reconcile game events, scores, box scores, injuries, and availability.
- Add concise post-game recaps based on the recorded game data.

### Phase 3 — More meaningful management

- Improve scouting uncertainty, player development choices, and coaching strategy.
- Add clearer roster, contract, and team-needs tools.
- Improve trade evaluation and transaction impact reporting.

### Phase 4 — Deeper league experience

- Add league calendar and season milestones.
- Expand searchable history, record books, news, and team/player timelines.
- Add optional league customization and commissioner tools.

### Phase 5 — Scale and polish

- Profile long saves and optimize the slowest queries and simulation stages.
- Improve mobile and accessibility support across core screens.
- Refine notifications, saved views, and comparison tools based on actual use.

## How to use this plan

- Treat this as a proposal, not a commitment to implement every item.
- Before starting a feature, inspect existing services, database tables, and screens to avoid duplicating current functionality.
- Deliver one coherent feature at a time, including data changes, simulation rules, interface updates, and migration/backward-compatibility needs.
- Prefer configurable rules where leagues may reasonably differ.
- Update this roadmap as features ship or priorities change.
