#!/usr/bin/env bash
set -euo pipefail

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
  EPHEMERAL_DB=1
else
  EPHEMERAL_DB=0
fi

php artisan storage:link --force || true

echo "Running migrations..."
php artisan migrate --force

# Only for the throwaway SQLite case: give a fresh database some events so the
# listing pages aren't empty. A real database keeps whatever is already there.
# DemoSeeder specifically, not DatabaseSeeder: the latter runs EventSeeder,
# which uses model factories, which need faker - a require-dev package that
# isn't in the production image.
if [ "${EPHEMERAL_DB}" = "1" ]; then
  echo "Seeding demo data into the ephemeral database..."
  php artisan db:seed --class=DemoSeeder --force
fi

php artisan config:cache
php artisan route:cache

# PHP's built-in server is single-threaded unless told otherwise; without this
# one slow request blocks every other one, which looks like the app hanging.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

echo "Starting server on port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
