#!/usr/bin/env bash
set -euo pipefail

# Render's filesystem is rebuilt on every deploy, so the storage symlink and
# the migrations have to be (re)applied each boot rather than once at setup.
php artisan storage:link --force || true

echo "Running migrations..."
php artisan migrate --force

php artisan config:cache
php artisan route:cache

# PHP's built-in server is single-threaded unless told otherwise; without this
# one slow request blocks every other one, which looks like the app hanging.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"

echo "Starting server on port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
