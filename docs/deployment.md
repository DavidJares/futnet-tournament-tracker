# BracketBird Deployment Guide

## 1. Hosting Requirements

- PHP 8.x
- MySQL or MariaDB
- PDO MySQL extension
- Apache with `.htaccess` support recommended
- File upload support (for tournament logos)

Shared hosting is supported. No Node.js or websocket infrastructure is required.

## 2. Recommended Web Root

Set document root to:

```text
<project>/public
```

This is the safest and cleanest production setup.

## 3. Fallback When Document Root Cannot Point to `public/`

If hosting forces document root to project root:

- Keep root `.htaccess` enabled.
- Keep root `index.php` redirect fallback in place.
- Verify directory listing is disabled.
- Verify direct access to internal folders is blocked (`src/`, `storage/`, `scripts/`, `docs/`).

## 4. Deploying Files (Git or FTP)

## Git-based deployment

1. Clone/pull repository on server.
2. Ensure writable permissions for `public/uploads/` (and managed subfolders).

## FTP deployment

1. Upload full project directory.
2. Preserve `.htaccess` files.
3. Ensure upload directory permissions allow image uploads.

## 5. Database Provisioning

1. Create production database.
2. Create dedicated DB user with least privileges required by the app.
3. Grant access only to BracketBird database.

## 6. Configuration (`src/config/local.php`)

Create `src/config/local.php` on server (never commit it).

Use `src/config/local.example.php` as template.

Typical values:

- host
- port
- database
- username
- password
- charset (`utf8mb4`)

## 7. Environment Configuration

Set production environment:

```text
APP_ENV=prod
```

Also ensure DB env variables are set if you use env-driven config:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

## 8. Run Migrations

From project root:

```bash
php scripts/migrate.php
```

This creates/updates schema for:

- superadmins
- tournaments
- groups
- teams
- matches
- match sets
- public view settings

## 9. One-Time Setup and First Superadmin

1. Open `/setup`.
2. Create first superadmin account.
3. Sign in at `/admin/login`.

After first account exists, `/setup` must be unavailable (returns minimal 404 behavior).

## 10. Post-Deployment Verification

1. Superadmin login works.
2. Tournament admin login (`/tournament/{slug}/login`) works.
3. Public routes under `/public/{slug}/...` work.
4. Logout works without CSRF errors.
5. Uploaded logo renders in public overview when configured.

## 11. Security Checklist

- Directory listing disabled.
- Internal folders not publicly accessible.
- `/setup` unavailable after first superadmin.
- CSRF protection active on POST actions.
- Upload hardening active:
  - PNG/JPG/WEBP only
  - file size limits enforced
  - upload path blocks script execution
- Production does not show PHP stack traces to users.
- Response headers present:
  - `X-Content-Type-Options`
  - `Referrer-Policy`
  - `X-Frame-Options`
  - `Content-Security-Policy`

## 12. Troubleshooting

## 404 on root

- Check document root mapping.
- If root mapping cannot be changed, verify fallback root `.htaccess` and root `index.php` exist.

## "Database credentials are not configured."

- Verify `src/config/local.php` exists on server.
- Verify DB values and/or environment variables are correct.

## Migrations not applied

- Run `php scripts/migrate.php`.
- Confirm DB user has schema change permissions.

## Public routes not working

- Confirm tournament slug exists.
- Confirm Public View is enabled for that tournament.
- Confirm rewrite rules are active (`.htaccess` support enabled).

## Uploads not showing

- Confirm upload succeeded and path saved.
- Confirm `public/uploads/` permissions.
- Confirm server serves static files from `public/`.
- Confirm blocked-script rules are present but do not block image extensions.
