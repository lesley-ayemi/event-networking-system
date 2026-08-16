#!/usr/bin/env bash
set -euo pipefail

# This image runs two different roles depending on PROCESS_TYPE: the default
# is the HTTP API (php artisan serve). A second Render service runs the same
# image with PROCESS_TYPE=reverb to run the WebSocket server instead, so
# real-time delivery doesn't need a separate Dockerfile or codebase.
if [ "${PROCESS_TYPE:-api}" = "reverb" ]; then
  echo "Starting Reverb on port ${PORT}..."
  exec php artisan reverb:start --host=0.0.0.0 --port="${PORT}"
fi

# No managed database wired up yet? Fall back to SQLite on the container's
# own disk so the API still boots and the app is browsable. This is a demo
# stopgap, not a deployment target: Render rebuilds the filesystem on every
# deploy and on wake from idle, so anything written here is lost. Set DB_URL
# (e.g. a Neon connection string) and this branch is skipped entirely.
if [ -z "${DB_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
  echo "WARNING: no DB_URL or DB_HOST set - falling back to ephemeral SQLite."
  echo "WARNING: data will not survive a redeploy or a spin-down from idle."
  export DB_CONNECTION=sqlite
  export DB_DATABASE=/app/database/database.sqlite
  mkdir -p /app/database
  touch "${DB_DATABASE}"
fi

php artisan storage:link --force || true

echo "Running migrations..."
php artisan migrate --force

# Unconditional rather than gated on EPHEMERAL_DB: DemoSeeder already bails
# out if any event exists (see its own guard), so this seeds an empty
# database exactly once - the ephemeral SQLite fallback on every boot, or a
# brand-new real database on its first ever boot - and no-ops forever after
# real content exists. DemoSeeder specifically, not DatabaseSeeder: the
# latter runs EventSeeder, which uses model factories, which need faker - a
# require-dev package that isn't in the production image.
echo "Seeding demo data if the database is empty..."
php artisan db:seed --class=DemoSeeder --force

php artisan config:cache
php artisan route:cache

# PHP's built-in server is single-threaded unless told otherwise; without this
# one slow request blocks every other one, which looks like the app hanging.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

echo "Starting server on port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
