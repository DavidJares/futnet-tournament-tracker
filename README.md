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
  - slug is auto-generated from tournament name and shown as read-only with copy action
  - slug uniqueness is handled automatically with numeric suffixes when needed
  - delete tournament (with confirmation)
  - link to tournament detail
- Tournament admin foundation (`/admin/tournament?id={id}`)
  - tab-like section navigation (server-rendered routes):
    - Tournament
    - Groups
    - Group Stage
    - Knockout
    - Public View
    - Teams
  - edit tournament settings:
    - name
    - slug (read-only with copy action)
    - when renaming tournament:
      - keep current slug
      - or regenerate unique slug from new name
    - event date
    - start time
    - location
    - tournament admin password
    - number of groups
    - number of courts
    - match duration in minutes
    - advancing teams count
    - `group_stage_mode` (`fixed_2_sets`, `best_of_3`)
    - `knockout_mode` (`fixed_2_sets`, `best_of_3`)
  - group names auto-generated as `A`, `B`, `C`, ...
  - Public View settings:
    - `public_view_enabled`
    - `autoplay_enabled`
    - `rotation_interval_seconds`
    - per-screen enabled flag and sort order
- Team management
  - add team
  - edit team
  - delete team (with confirmation)
- Team-to-group assignment management
  - team can be assigned to a group or left unassigned ("No group")
  - automatic balanced random assignment across groups
  - manual reassignment per team via dropdown
  - summary of total teams, groups, teams per group, and unassigned count
- Group-stage match generation and initial scheduling
  - round robin inside each group (assigned teams only)
  - confirmation required when unassigned teams are present
  - confirmation required when regenerating existing group-stage matches
  - regeneration replaces only `stage = 'group'` matches
  - stores `court_number`, `schedule_order`, and `planned_start`
  - shows generated group-stage matches in tournament admin page
- Group match lifecycle and score entry
  - match detail pages:
    - superadmin: `/admin/tournament/matches/{matchId}?id={tournamentId}`
    - tournament admin: `/tournament/{slug}/admin/matches/{matchId}`
  - match status flow:
    - `scheduled -> in_progress` via explicit Start action
    - `scheduled|in_progress -> finished` by saving valid score
  - Start action is available in both match detail and matches overview
  - score entry stores per-set values in `match_sets`
  - saving score updates `matches.sets_summary_a`, `matches.sets_summary_b`, `matches.winner_team_id`, and `matches.status`
  - finished group matches can be reopened in detail and corrected (prefilled values)
  - finished group matches can be reset back to `scheduled` (clears `match_sets`, summaries, winner)
  - supports both modes:
    - `fixed_2_sets`: exactly 2 sets
    - `best_of_3`: 2 or 3 sets, first to 2 set wins
  - match rows in Matches tab are clickable and open match detail
  - existing group/court filters remain available and are preserved when navigating to/from match detail
- Knockout stage generation (full bracket structure)
  - new Knockout section in tournament admin
  - generate knockout bracket structure from finished group-stage standings with per-group advancement
  - generation allowed only when all group-stage matches are finished
  - supports standard and non-standard advancing counts using byes
  - advancing selection:
    - `base = floor(N / G)` teams from each group (`G` = groups, `N` = advancing teams)
    - remaining slots are wildcards from next-best teams across groups
  - wildcard ranking uses:
    - tournament points
    - point difference
    - points scored
    - stable fallback by team id
  - global seeding orders selected teams by:
    - group position (all 1st-place teams, then all 2nd-place teams, etc.)
    - tournament points
    - point difference
    - points scored
    - stable fallback by team id
  - bracket sizing:
    - `B = next power of two >= N`
    - `bye_count = B - N`
    - top seeds receive byes
  - opening pairings:
    - 1 vs N
    - 2 vs N-1
    - etc.
  - regenerating knockout requires confirmation and replaces only `stage = 'knockout'` matches
  - stores knockout matches in `matches` with:
    - `stage = 'knockout'`
    - `group_id = NULL`
    - `round_name`
    - `bracket_position`
    - `team_a_id` / `team_b_id`
    - `team_a_source` / `team_b_source` for pending rounds
    - `status = 'scheduled'` or `status = 'pending'`
- Knockout match detail and progression
  - knockout matches are clickable from Knockout tab
  - score entry follows `knockout_mode`
  - saving knockout result sets winner and marks match `finished`
  - winner is automatically propagated to dependent next-round slots
  - dependent match status is updated automatically:
    - `scheduled` when both participants are known
    - `pending` when one side is still unknown
  - editing finished knockout result is allowed
  - if downstream knockout matches already have results, confirmation is required
  - confirmed change resets the entire downstream branch and re-applies progression
- Group standings in Groups tab
  - computed dynamically from finished group-stage matches only
  - per group table includes:
    - matches played, wins, draws, losses
    - sets for/against
    - points scored/conceded
    - point difference
    - tournament points
  - sorting order:
    - tournament points
    - head-to-head result
    - point difference
    - points scored
    - random fallback
- Tournament admin slug-based access
  - login at `/tournament/{slug}/login` with tournament password
  - protected tournament admin pages:
    - `/tournament/{slug}/admin`
    - `/tournament/{slug}/admin/groups`
    - `/tournament/{slug}/admin/matches`
    - `/tournament/{slug}/admin/knockout`
    - `/tournament/{slug}/admin/public_view`
    - `/tournament/{slug}/admin/teams`
  - tournament admin session is bound to one specific tournament
  - tournament admin logout at `POST /tournament/{slug}/logout`
  - superadmin session remains separate and unchanged
- Public read-only routes (no login required)
  - `/public/{slug}/overview`
  - `/public/{slug}/next`
  - `/public/{slug}/standings`
  - `/public/{slug}/schedule`
  - `/public/{slug}/knockout`
  - `/public/{slug}/results`
  - `/public/{slug}/display`
  - routes show unavailable page when `public_view_enabled` is off
  - each screen displays a QR code pointing to the current screen URL
  - Overview can act as a tournament invitation/information screen with optional:
    - public title override
    - public description
    - tournament logo
    - map link button

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
- `20260428_000005_public_view_settings`
  - adds `tournaments.public_view_enabled`
  - adds `tournaments.autoplay_enabled`
  - adds `tournaments.rotation_interval_seconds`
  - creates `tournament_public_screens` table for screen enabled/order config
- `20260428_000006_public_overview_metadata`
  - adds `tournaments.public_title_override`
  - adds `tournaments.public_description`
  - adds `tournaments.public_logo_path`
  - adds `tournaments.public_map_url`

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

- Knockout progression
- Bracket visualization
- Public pages
- Drag and drop scheduling/reordering
