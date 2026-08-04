#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

DB_NAME="event_networking"
DB_USER="root"
DB_PASS=""

echo "=== 1/9: Scaffolding Laravel into server/ ==="
if [ -d server ]; then
  echo "server/ already exists, skipping composer create-project (delete server/ to start fresh)"
else
  composer create-project laravel/laravel server
fi

echo "=== 2/9: Creating MySQL database (edit DB_USER/DB_PASS above if needed) ==="
if command -v mysql >/dev/null 2>&1; then
  mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4;" \
    || echo "Could not auto-create the database — create '${DB_NAME}' manually, then re-run this script."
else
  echo "mysql client not found on PATH — create the '${DB_NAME}' database manually, then re-run this script."
fi

cd server

echo "=== 3/9: Installing Sanctum + API routes ==="
php artisan install:api --no-interaction || php artisan install:api

echo "=== 3a/9: Installing Laravel Reverb (real-time messaging, replaces Firebase) ==="
composer require laravel/reverb --no-interaction
# reverb:install is interactive with no non-interactive mode, so config/env
# are supplied directly by the overlay + .env.additions instead of running it.

echo "=== 4/9: Configuring .env for MySQL and the client origin ==="
if ! grep -q "^CLIENT_URL=" .env; then
  {
    echo ""
    cat "$ROOT_DIR/server-overlay/.env.additions"
  } >> .env
fi
sed -i.bak "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i.bak "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env 2>/dev/null || true
sed -i.bak "s#^APP_URL=.*#APP_URL=http://localhost:8000#" .env 2>/dev/null || true
sed -i.bak "s/^BROADCAST_CONNECTION=.*/BROADCAST_CONNECTION=reverb/" .env 2>/dev/null || true
# The CLIENT_URL guard above only appends .env.additions once, so on a machine
# that ran setup.sh before Reverb was added, these keys can be left over from
# an earlier ad-hoc `reverb:install` run with random values. Force them back
# to the fixed values the client's .env expects, so the two never drift.
sed -i.bak "s/^REVERB_APP_ID=.*/REVERB_APP_ID=event-networking/" .env 2>/dev/null || true
sed -i.bak "s/^REVERB_APP_KEY=.*/REVERB_APP_KEY=event-networking-key/" .env 2>/dev/null || true
sed -i.bak "s/^REVERB_APP_SECRET=.*/REVERB_APP_SECRET=event-networking-secret/" .env 2>/dev/null || true
rm -f .env.bak

echo "=== 5/9: Installing Pest ==="
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
cat > tests/Pest.php <<'PESTPHP'
<?php

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
PESTPHP

echo "=== 6/9: Overlaying application code from server-overlay/ ==="
cp "$ROOT_DIR/server-overlay/app/Models/User.php" app/Models/User.php
cp "$ROOT_DIR/server-overlay/app/Models/Event.php" app/Models/Event.php
cp "$ROOT_DIR/server-overlay/app/Models/EventRegistration.php" app/Models/EventRegistration.php
cp "$ROOT_DIR/server-overlay/app/Models/Bookmark.php" app/Models/Bookmark.php
cp "$ROOT_DIR/server-overlay/app/Models/UserBlock.php" app/Models/UserBlock.php
cp "$ROOT_DIR/server-overlay/app/Models/FriendRequest.php" app/Models/FriendRequest.php
cp "$ROOT_DIR/server-overlay/app/Models/Conversation.php" app/Models/Conversation.php
cp "$ROOT_DIR/server-overlay/app/Models/ConversationParticipant.php" app/Models/ConversationParticipant.php
cp "$ROOT_DIR/server-overlay/app/Models/Message.php" app/Models/Message.php
cp "$ROOT_DIR/server-overlay/app/Models/Report.php" app/Models/Report.php
cp "$ROOT_DIR/server-overlay/app/Models/AuditLog.php" app/Models/AuditLog.php
mkdir -p app/Http/Requests/Api
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/RegisterRequest.php" app/Http/Requests/Api/RegisterRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/LoginRequest.php" app/Http/Requests/Api/LoginRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/UpdateProfileRequest.php" app/Http/Requests/Api/UpdateProfileRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/StoreEventRequest.php" app/Http/Requests/Api/StoreEventRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/UpdateEventRequest.php" app/Http/Requests/Api/UpdateEventRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/StoreEventRegistrationRequest.php" app/Http/Requests/Api/StoreEventRegistrationRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/UpdateQuizRequest.php" app/Http/Requests/Api/UpdateQuizRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/StoreFriendRequestRequest.php" app/Http/Requests/Api/StoreFriendRequestRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/StoreConversationRequest.php" app/Http/Requests/Api/StoreConversationRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/StoreMessageRequest.php" app/Http/Requests/Api/StoreMessageRequest.php
cp "$ROOT_DIR/server-overlay/app/Http/Requests/Api/StoreReportRequest.php" app/Http/Requests/Api/StoreReportRequest.php
mkdir -p app/Http/Controllers/Api/Concerns app/Http/Controllers/Api/Admin app/Http/Middleware app/Console/Commands
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/AuthController.php" app/Http/Controllers/Api/AuthController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/ProfileController.php" app/Http/Controllers/Api/ProfileController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/EventController.php" app/Http/Controllers/Api/EventController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/BookmarkController.php" app/Http/Controllers/Api/BookmarkController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/QuizController.php" app/Http/Controllers/Api/QuizController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/MatchController.php" app/Http/Controllers/Api/MatchController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/FriendRequestController.php" app/Http/Controllers/Api/FriendRequestController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/FriendController.php" app/Http/Controllers/Api/FriendController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/BlockController.php" app/Http/Controllers/Api/BlockController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/ConversationController.php" app/Http/Controllers/Api/ConversationController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/MessageController.php" app/Http/Controllers/Api/MessageController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/ReportController.php" app/Http/Controllers/Api/ReportController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/OrganiserRequestController.php" app/Http/Controllers/Api/OrganiserRequestController.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/Concerns/AttachesEventUserContext.php" app/Http/Controllers/Api/Concerns/AttachesEventUserContext.php
cp "$ROOT_DIR/server-overlay/app/Http/Controllers/Api/Admin/"*.php app/Http/Controllers/Api/Admin/
cp "$ROOT_DIR/server-overlay/app/Http/Middleware/EnsureUserIsAdmin.php" app/Http/Middleware/EnsureUserIsAdmin.php
cp "$ROOT_DIR/server-overlay/app/Http/Middleware/EnsureAccountActive.php" app/Http/Middleware/EnsureAccountActive.php
cp "$ROOT_DIR/server-overlay/app/Console/Commands/PromoteAdmin.php" app/Console/Commands/PromoteAdmin.php
mkdir -p app/Http/Resources app/Services app/Events app/Exceptions
cp "$ROOT_DIR/server-overlay/app/Http/Resources/EventResource.php" app/Http/Resources/EventResource.php
cp "$ROOT_DIR/server-overlay/app/Services/CompatibilityCalculator.php" app/Services/CompatibilityCalculator.php
cp "$ROOT_DIR/server-overlay/app/Services/MessagingPolicy.php" app/Services/MessagingPolicy.php
cp "$ROOT_DIR/server-overlay/app/Events/MessageSent.php" app/Events/MessageSent.php
cp "$ROOT_DIR/server-overlay/app/Exceptions/ApiException.php" app/Exceptions/ApiException.php
cp "$ROOT_DIR/server-overlay/app/Providers/AppServiceProvider.php" app/Providers/AppServiceProvider.php
cp "$ROOT_DIR/server-overlay/bootstrap/app.php" bootstrap/app.php
cp "$ROOT_DIR/server-overlay/config/cors.php" config/cors.php
cp "$ROOT_DIR/server-overlay/config/broadcasting.php" config/broadcasting.php
cp "$ROOT_DIR/server-overlay/config/reverb.php" config/reverb.php
cp "$ROOT_DIR/server-overlay/routes/api.php" routes/api.php
cp "$ROOT_DIR/server-overlay/routes/channels.php" routes/channels.php
cp "$ROOT_DIR/server-overlay/database/migrations/"*.php database/migrations/
cp "$ROOT_DIR/server-overlay/database/factories/UserFactory.php" database/factories/UserFactory.php
cp "$ROOT_DIR/server-overlay/database/factories/EventFactory.php" database/factories/EventFactory.php
cp "$ROOT_DIR/server-overlay/database/seeders/DatabaseSeeder.php" database/seeders/DatabaseSeeder.php
cp "$ROOT_DIR/server-overlay/database/seeders/EventSeeder.php" database/seeders/EventSeeder.php
mkdir -p tests/Feature/Auth tests/Unit/Models tests/Feature/Profile tests/Feature/Events tests/Feature/Bookmarks tests/Feature/Quiz tests/Feature/Matches tests/Unit/Services tests/Feature/Friends tests/Feature/Blocks tests/Feature/Messaging tests/Feature/ErrorHandling tests/Feature/Admin tests/Feature/Reports tests/Feature/Organisers
cp "$ROOT_DIR/server-overlay/tests/Feature/SmokeTest.php" tests/Feature/SmokeTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/UsersTableTest.php" tests/Feature/UsersTableTest.php
cp "$ROOT_DIR/server-overlay/tests/Unit/Models/UserTest.php" tests/Unit/Models/UserTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/Auth/RegisterTest.php" tests/Feature/Auth/RegisterTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/Auth/LoginTest.php" tests/Feature/Auth/LoginTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/Auth/ProtectedRouteTest.php" tests/Feature/Auth/ProtectedRouteTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/Auth/LogoutTest.php" tests/Feature/Auth/LogoutTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/ErrorHandling/"*.php tests/Feature/ErrorHandling/
cp "$ROOT_DIR/server-overlay/tests/Feature/Profile/UpdateProfileTest.php" tests/Feature/Profile/UpdateProfileTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/Profile/UploadProfilePhotoTest.php" tests/Feature/Profile/UploadProfilePhotoTest.php
cp "$ROOT_DIR/server-overlay/tests/Feature/Events/"*.php tests/Feature/Events/
cp "$ROOT_DIR/server-overlay/tests/Feature/Bookmarks/"*.php tests/Feature/Bookmarks/
cp "$ROOT_DIR/server-overlay/tests/Feature/Quiz/"*.php tests/Feature/Quiz/
cp "$ROOT_DIR/server-overlay/tests/Feature/Matches/"*.php tests/Feature/Matches/
cp "$ROOT_DIR/server-overlay/tests/Unit/Services/"*.php tests/Unit/Services/
cp "$ROOT_DIR/server-overlay/tests/Feature/Friends/"*.php tests/Feature/Friends/
cp "$ROOT_DIR/server-overlay/tests/Feature/Blocks/"*.php tests/Feature/Blocks/
cp "$ROOT_DIR/server-overlay/tests/Feature/Messaging/"*.php tests/Feature/Messaging/
cp "$ROOT_DIR/server-overlay/tests/Feature/Admin/"*.php tests/Feature/Admin/
cp "$ROOT_DIR/server-overlay/tests/Feature/Reports/"*.php tests/Feature/Reports/
cp "$ROOT_DIR/server-overlay/tests/Feature/Organisers/"*.php tests/Feature/Organisers/

echo "=== 6a/9: Linking public storage for profile photo uploads ==="
php artisan storage:link

echo "=== 7/9: Confirming phpunit.xml runs tests against in-memory SQLite ==="
if ! grep -q 'name="DB_CONNECTION" value="sqlite"' phpunit.xml; then
  echo "WARNING: phpunit.xml does not set DB_CONNECTION=sqlite for tests."
  echo "Add these two lines inside the <php> block of server/phpunit.xml:"
  echo '  <env name="DB_CONNECTION" value="sqlite"/>'
  echo '  <env name="DB_DATABASE" value=":memory:"/>'
fi

echo "=== 8/9: Running migrations + seed (MySQL) and the backend test suite (SQLite) ==="
php artisan migrate --force
php artisan db:seed --force
php artisan test

echo "=== 9/9: Installing and testing the Vue client ==="
cd "$ROOT_DIR/client"
npm install
npm test

echo ""
echo "All done. Start the app with:"
echo "  Terminal 1: cd server && php artisan serve"
echo "  Terminal 2: cd server && php artisan reverb:start"
echo "  Terminal 3: cd client && npm run dev"
