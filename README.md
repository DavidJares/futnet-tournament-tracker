# BracketBird

A lightweight PHP tournament management system for futnet and similar sports.

BracketBird is built for small and medium local tournaments that need a practical admin workflow, public display screens, and shared-hosting-friendly deployment.

## Feature Overview

- Superadmin setup and authentication
- Tournament creation and management
- Tournament-admin access per tournament slug
- Team management and group assignment (manual + auto-balanced)
- Group stage match generation, scheduling, and score entry
- Standings with tie-break logic
- Knockout bracket generation with progression and dependent reset protection
- Public read-only screens (overview, next matches, standings, schedule, knockout, results)
- Rotating public display mode with QR links
- Public overview metadata (title, description, logo, map URL/embed)

## Stack

- PHP 8.x
- MySQL/MariaDB (PDO)
- Server-rendered pages
- Vanilla JS (small UX helpers)
- Bootstrap via CDN

## Quick Start (Local)

1. Copy local config:
   - `src/config/local.example.php` -> `src/config/local.php`
2. Fill DB credentials in `src/config/local.php`.
3. Run migrations:
   - `php scripts/migrate.php`
4. Configure web root to `public/`.
5. Open `/setup` and create the first superadmin.
6. Sign in at `/admin/login`.

## Security Summary

- CSRF protection on POST actions
- Session cookie hardening (`httponly`, `samesite`, `secure` on HTTPS)
- Session ID regeneration on login/logout
- Session-based login throttling
- Prepared statements via PDO
- Upload validation (size, extension, MIME) and upload execution blocking
- Internal folder protection for shared-hosting fallback setups
- Production-safe error display controls and baseline security headers

## Documentation

- [Deployment Guide](docs/deployment.md)
- [Architecture Documentation](docs/architecture.md)

## Current Status

BracketBird is an MVP with working:

- tournament administration
- group stage flow
- knockout generation and progression
- bracket views
- public display pages

The project is designed for incremental extension without introducing framework or build-tool complexity.
