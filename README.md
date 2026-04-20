# FTT Lightweight PHP MVP Skeleton

Server-rendered Futnet Tournament Tracker (FTT) MVP foundation for shared hosting.

## Stack

- PHP 8.x
- MySQL/MariaDB (PDO)
- Server-rendered pages
- Vanilla JS only for minor UX helpers
- Bootstrap (CDN)

## Project Structure

```text
public/
  .htaccess
  index.php
src/
  bootstrap.php
  Router.php
  config/
  controllers/
  migrations/
  models/
  views/
scripts/
  migrate.php
storage/
docs/
```

## Initial Setup

1. Copy local config:
   - `src/config/local.example.php` -> `src/config/local.php`
2. Fill database credentials in `src/config/local.php`.
3. Run migrations:
   - `php scripts/migrate.php`
4. Point web server document root to `public/`.
5. Open `/setup` and create the first superadmin account.
6. Sign in at `/admin/login`.

## MVP Features Implemented

- One-time setup page for first superadmin creation (`/setup`)
  - only available when no superadmin exists
  - stores password hash with `password_hash()`
- Superadmin authentication
  - login (`/admin/login`)
  - logout (`POST /admin/logout`)
  - protected dashboard (`/admin/dashboard`)
- Superadmin dashboard
  - list tournaments
  - create tournament
  - delete tournament (with confirmation)
  - link to tournament detail
- Tournament admin foundation (`/admin/tournament?id={id}`)
  - edit tournament settings:
    - name
    - slug
    - event date
    - location
    - tournament admin password
    - number of groups
    - number of courts
    - match duration in minutes
    - advancing teams count
    - `match_mode` (`fixed_2_sets`, `best_of_3`)
  - group names auto-generated as `A`, `B`, `C`, ...
- Team management
  - add team
  - edit team
  - delete team (with confirmation)
- Team-to-group assignment management
  - team can be assigned to a group or left unassigned ("No group")
  - automatic balanced random assignment across groups
  - manual reassignment per team via dropdown
  - summary of total teams, groups, teams per group, and unassigned count
- Tournament admin slug-based access
  - login at `/tournament/{slug}/login` with tournament password
  - protected tournament admin page at `/tournament/{slug}/admin`
  - tournament admin session is bound to one specific tournament
  - tournament admin logout at `POST /tournament/{slug}/logout`
  - superadmin session remains separate and unchanged

## Database Schema (Migrations)

Migrations create these tables:

- `schema_migrations`
- `superadmins`
- `tournaments`
- `tournament_groups`
- `teams`
- `matches`
- `match_sets`

Additional migration:

- `20260419_000002_add_group_id_to_teams`
  - adds nullable `teams.group_id`
  - adds FK to `tournament_groups.id` (`ON DELETE SET NULL`)
  - keeps existing teams safely as unassigned

### Notes About `matches` Table

Prepared for future scheduling and manual reorder with fields including:

- `tournament_id`
- `stage`
- `group_id` (nullable)
- `round_name` (nullable)
- `bracket_position` (nullable)
- `team_a_id` / `team_b_id` (nullable)
- `team_a_source` / `team_b_source` (nullable)
- `court_number` (nullable)
- `schedule_order` (nullable)
- `planned_start` (nullable)
- `status`
- `winner_team_id` (nullable)
- `sets_summary_a`
- `sets_summary_b`
- timestamps

`match_sets` stores per-set scores (`set_number`, `score_a`, `score_b`) for each match.

## Not Implemented Yet

- Match generation
- Standings
- Knockout progression
- Public pages
- Drag and drop scheduling/reordering
