# Event Networking App

A low-pressure professional networking platform where users discover events,
choose how they want to interact, find compatible people, and communicate
before attending.

## Stack
- Backend: Laravel (JSON API), Sanctum token auth, MySQL + Eloquent, Pest tests
- Frontend: Vue 3 (Vite), Vue Router, Pinia, Axios, Vitest tests

## This is Phase 1 + 2 only
Foundation + Authentication (register, login, logout, session restore, route
guards, profile onboarding shell). Events, matching, messaging, dashboard,
security hardening, and deployment are separate follow-up phases.

## Why there's a setup.sh
This project was generated in a sandbox with no PHP runtime and no package
registry access, so the Laravel skeleton itself (composer.json, config/,
public/index.php, etc.) could not be scaffolded or executed here. Everything
*application-specific* — models, controllers, requests, migrations, routes,
and the full Pest + Vitest test suites — is fully written and included.
`setup.sh` scaffolds a real Laravel project with Composer, overlays these
files on top, installs Sanctum + Pest, and runs both test suites so you get
a verified, passing build on first run.

## Prerequisites
- PHP 8.2+, Composer
- MySQL 8+ running locally (or update `server/.env` with your connection string)
- Node.js 18+
- `pdo_sqlite` PHP extension enabled (used for the test database)

## Setup
```bash
chmod +x setup.sh
./setup.sh
```
This will:
1. `composer create-project laravel/laravel server`
2. Create the `event_networking` MySQL database (edit the script if your
   MySQL user/password differ)
3. Run `php artisan install:api` (Sanctum)
4. Copy in the application code from `server-overlay/`
5. Install Pest and run `php artisan migrate` + `php artisan test`
6. `npm install` the client and run `npm test`

## Manual verification after setup.sh succeeds
```bash
# Terminal 1
cd server && php artisan serve

# Terminal 2
cd client && npm run dev
```
Then in a browser: visit the client URL, click "Get started," register with a
real-format email and 8+ character password, confirm you land on
`/onboarding`. Check the `users` table in MySQL for the new row. Visiting
`/dashboard` in a private window (logged out) should redirect to
`/login?redirect=/dashboard`.

See `PLAN.md` for the full task-by-task build plan this code was generated from.
