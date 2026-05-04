# BracketBird Architecture

## 1. High-Level Architecture

BracketBird is a server-rendered PHP MVC-style application (lightweight, framework-free):

- Router maps HTTP routes to controller methods.
- Controllers coordinate request validation, auth checks, and model calls.
- Models encapsulate SQL access via PDO prepared statements.
- Views render Bootstrap-based HTML templates.

Design goals:

- shared-hosting compatibility
- minimal abstraction overhead
- clear, readable PHP

## 2. Project Structure

```text
public/
  index.php              # Front controller
  .htaccess              # Rewrite and web-root protections
  uploads/               # Public assets (logos), execution-restricted
src/
  bootstrap.php          # Autoload + service bootstrapping
  Router.php             # Lightweight router
  Config/                # app + local config
  Controllers/           # Request handlers
  Models/                # Database access layer
  Views/                 # Server-rendered templates
  Migrations/            # Schema migration files
scripts/
  migrate.php            # Migration runner
storage/                 # Runtime storage (non-public)
docs/                    # Project documentation
```

## 3. Routing Overview

- Entry point: `public/index.php`
- Route registration: `src/Router.php` + controller wiring in front controller
- Main route groups:
  - Setup: `/setup`
  - Superadmin auth + dashboard: `/admin/...`
  - Tournament admin auth + management: `/tournament/{slug}/...`
  - Public read-only screens: `/public/{slug}/...`

## 4. Authentication Model

## Superadmin

- Global administrator role
- Auth routes:
  - `GET /admin/login`
  - `POST /admin/login`
  - `POST /admin/logout`
- Access to dashboard and cross-tournament administration

## Tournament Admin

- Tournament-scoped role
- Auth route:
  - `GET|POST /tournament/{slug}/login`
  - `POST /tournament/{slug}/logout`
- Session bound to a specific tournament ID/slug

## Public Access

- No login required
- Read-only routes only (`/public/{slug}/...`)
- Visibility controlled by `public_view_enabled`

## 5. Database Schema Summary

Primary tables:

- `superadmins`
- `tournaments`
- `tournament_groups`
- `teams`
- `matches`
- `match_sets`
- `schema_migrations`
- `tournament_public_screens`

Conceptual model:

- One tournament has many groups, teams, and matches.
- Matches are split by stage (`group`, `knockout`).
- Per-set score details are in `match_sets`.
- Public screen toggles/order are stored per tournament.

## 6. Migrations Overview

Migrations build initial schema and apply incremental features including:

- team group assignment support
- public display settings and metadata
- public map URL/embed support
- match mode split for group and knockout

`scripts/migrate.php` applies unapplied migrations tracked in `schema_migrations`.

## 7. Tournament Lifecycle

1. Superadmin creates tournament.
2. Tournament settings configured (date, time, courts, modes, advancement count).
3. Teams added.
4. Teams assigned to groups (manual/automatic).
5. Group matches generated and scheduled.
6. Group scores entered, standings computed.
7. Knockout bracket generated from standings.
8. Knockout results entered with progression.
9. Public screens used for event display.

## 8. Group Stage Logic

- Round-robin pairings per group
- Scheduling uses available courts and match duration
- Regeneration requires confirmation
- Unassigned teams are handled with confirmation safeguards

## 9. Score Entry Logic

- Supports:
  - `fixed_2_sets`
  - `best_of_3`
- Input validation ensures valid set structure and winner derivation
- Saves:
  - per-set details (`match_sets`)
  - summary + winner on `matches`
- Supports resetting finished group matches back to scheduled

## 10. Standings Logic

Computed from finished group matches only, including:

- played, wins, draws, losses
- sets for/against
- points for/against
- point difference
- tournament points

Sorting priority:

1. tournament points
2. head-to-head
3. point difference
4. points scored
5. deterministic/random fallback depending on context

## 11. Knockout Generation Logic

Features:

- Per-group advancement (`floor(N/G)` base)
- Wildcards for remaining slots
- Global seeding by position + performance
- Bracket size expanded to next power of two
- BYE support for top seeds
- Source mapping (`team_a_source`, `team_b_source`) for progression

Progression behavior:

- Saving knockout result advances winner automatically
- Downstream matches update between `pending` and `scheduled`
- If upstream result is edited after downstream scoring, confirmation is required
- Confirmed change resets dependent branch and re-applies progression

## 12. Public View System

Screens:

- overview
- next matches
- standings
- schedule
- knockout
- recent results

Display capabilities:

- configurable screen order/enable flags
- autoplay rotation
- dedicated display endpoint (`/public/{slug}/display`)
- QR code links to current screen URL
- overview metadata:
  - public title override
  - description
  - logo
  - map button URL
  - sanitized Google Maps embed URL

## 13. Security Architecture

## CSRF

- CSRF token generated in session
- POST requests validated globally
- Forms include CSRF token (auto-injection + explicit helper support)

## Session Hardening

- Cookie flags: `httponly`, `samesite`, `secure` (on HTTPS)
- Session ID regeneration on login/logout
- Session-based brute-force throttling for login attempts

## Upload Validation

- Logo upload size limits
- Extension allowlist: PNG/JPG/JPEG/WEBP
- MIME validation (`finfo_file` fallback-safe)
- Randomized server filename
- Upload directories protected from script execution

## Environment and Errors

- Environment-controlled error display (`APP_ENV`)
- Production mode disables error display to end users
- Error logging remains enabled

## Protected Internal Folders

- Recommended: web root at `public/`
- Fallback root protections block direct access to internal directories when needed
- Directory listing disabled via Apache rules
