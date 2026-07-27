# Event Networking App — Phase 1 & 2 Implementation Plan (Foundation + Authentication, Laravel stack)

**Goal:** Stand up a Laravel API backend + separate Vue SPA frontend, with a working, tested register/login/logout flow using Laravel Sanctum token auth and a MySQL-backed user profile.

**Approach:** Scaffold backend and frontend independently, verify each layer in isolation (Laravel boots, DB migrates, SPA boots), then build authentication test-first with Pest (backend) and Vitest (frontend). MySQL is used for local/production; tests run against an in-memory SQLite database for speed, matching Laravel's standard testing setup.

**Tools/Stack:** Laravel (latest stable, installed via Composer) as a pure JSON API, Laravel Sanctum for token authentication, MySQL + Eloquent, Pest for backend tests. Vue 3 (Vite, Composition API), Vue Router, Pinia, Axios for the frontend, Vitest for frontend logic tests. Laravel Reverb is configured in this plan's setup steps so broadcasting is ready for the Messaging phase, but no channels are built yet.

This supersedes the earlier Node/Express/Firebase version of this plan. It covers spec sections 1–13 of the "Recommended implementation order" (Phase 1: Foundation, Phase 2: Authentication), re-mapped onto Laravel + MySQL + Sanctum + Vue. Events, quiz, matching, friends, messaging, dashboard, security hardening, and deployment remain separate plans to follow.

**Prerequisites:** PHP 8.2+, Composer, MySQL 8+ running locally (or accessible connection string), Node.js 18+, and the `pdo_sqlite` PHP extension enabled (used for the test database).

---

## File Structure

```text
event-networking-app/
├── client/
│   ├── src/
│   │   ├── layouts/
│   │   │   ├── DefaultLayout.vue
│   │   │   └── AuthLayout.vue
│   │   ├── pages/
│   │   │   ├── LandingPage.vue
│   │   │   ├── RegisterPage.vue
│   │   │   ├── LoginPage.vue
│   │   │   ├── OnboardingPage.vue
│   │   │   ├── CompatibilityQuizPage.vue
│   │   │   ├── DashboardPage.vue
│   │   │   ├── EventsPage.vue
│   │   │   ├── EventDetailsPage.vue
│   │   │   ├── SavedEventsPage.vue
│   │   │   ├── MatchesPage.vue
│   │   │   ├── FriendsPage.vue
│   │   │   ├── MessagesPage.vue
│   │   │   ├── ChatPage.vue
│   │   │   └── ProfilePage.vue
│   │   ├── router/
│   │   │   ├── guards.js
│   │   │   └── index.js
│   │   ├── stores/
│   │   │   └── authStore.js
│   │   ├── services/
│   │   │   └── apiClient.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── tests/
│   │   ├── router/guards.test.js
│   │   └── stores/authStore.test.js
│   ├── .env.example
│   └── package.json
│
├── server/                              (Laravel project root)
│   ├── app/
│   │   ├── Models/User.php
│   │   └── Http/
│   │       ├── Controllers/Api/AuthController.php
│   │       └── Requests/Api/RegisterRequest.php
│   ├── bootstrap/app.php
│   ├── config/
│   │   ├── cors.php
│   │   └── sanctum.php
│   ├── database/
│   │   └── migrations/
│   │       ├── 0001_01_01_000000_create_users_table.php   (framework default, untouched)
│   │       └── xxxx_xx_xx_xxxxxx_add_profile_fields_to_users_table.php
│   ├── routes/api.php
│   ├── tests/
│   │   ├── Pest.php
│   │   ├── Unit/Models/UserTest.php
│   │   └── Feature/Auth/
│   │       ├── RegisterTest.php
│   │       ├── LoginTest.php
│   │       ├── LogoutTest.php
│   │       └── ProtectedRouteTest.php
│   ├── phpunit.xml
│   ├── .env
│   └── .env.example
│
├── README.md
└── .gitignore
```

---

### Task 1: Initialize repository and root structure

**Files:**
- Create: `event-networking-app/.gitignore`
- Create: `event-networking-app/README.md`

- [ ] **Step 1: Create the root directory and git repo**

Run: `mkdir -p event-networking-app && cd event-networking-app && git init`

- [ ] **Step 2: Create the root `.gitignore`**

```gitignore
# Node / client
node_modules/
dist/
coverage/

# PHP / server
server/vendor/
server/node_modules/
server/storage/*.key
server/storage/framework/cache/*
server/storage/framework/sessions/*
server/storage/framework/views/*
server/storage/logs/*
server/bootstrap/cache/*.php
server/public/hot
server/public/storage

# Env files
.env
.env.local
*/.env

*.log
.DS_Store
```

- [ ] **Step 3: Create the root `README.md`**

```markdown
# Event Networking App

A low-pressure professional networking platform where users discover events,
choose how they want to interact, find compatible people, and communicate
before attending.

## Stack
- Backend: Laravel (JSON API), Sanctum token auth, MySQL + Eloquent, Reverb (real-time)
- Frontend: Vue 3 (Vite), Vue Router, Pinia, Axios

## Structure
- `client/` — Vue SPA
- `server/` — Laravel API

## Local development
1. `cd server && composer install && php artisan serve`
2. `cd client && npm install && npm run dev`

See `server/.env.example` and `client/.env.example` for required environment variables.
```

- [ ] **Step 4: Commit**

Run: `git add .gitignore README.md && git commit -m "Initialize repository"`

---

### Task 2: Scaffold the Laravel backend

**Files:**
- Create: `server/` (via Composer)

- [ ] **Step 1: Create the Laravel project**

Run: `cd event-networking-app && composer create-project laravel/laravel server`

- [ ] **Step 2: Create a local MySQL database**

Run: `mysql -u root -e "CREATE DATABASE event_networking CHARACTER SET utf8mb4;"` (adjust the `-u`/`-p` flags to match your local MySQL credentials)

- [ ] **Step 3: Configure `server/.env` for MySQL**

Edit `server/.env` (already created by the installer) and set:

```env
APP_NAME="Event Networking"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_networking
DB_USERNAME=root
DB_PASSWORD=

CLIENT_URL=http://localhost:5173
```

- [ ] **Step 4: Create `server/.env.example`** matching the same keys with blank/placeholder secrets, for onboarding future contributors:

```env
APP_NAME="Event Networking"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_networking
DB_USERNAME=root
DB_PASSWORD=

CLIENT_URL=http://localhost:5173
```

- [ ] **Step 5: Run the default migrations**

Run: `cd server && php artisan migrate`
Expected: `Migration table created successfully.` followed by the `users`, `password_reset_tokens`, `sessions` (or `cache`/`jobs` depending on version) migrations running with no errors.

- [ ] **Step 6: Verify the server boots**

Run: `php artisan serve`
Expected: `Server running on [http://127.0.0.1:8000]`. Visiting that URL in a browser shows the Laravel welcome page.

- [ ] **Step 7: Commit**

Run: `cd .. && git add server && git commit -m "Scaffold Laravel backend"`

---

### Task 3: Install Sanctum and configure CORS for the SPA

**Files:**
- Modify: `server/bootstrap/app.php`
- Create: `server/routes/api.php` (if not already created by the installer)
- Modify: `server/config/cors.php`
- Modify: `server/app/Models/User.php`

- [ ] **Step 1: Install Sanctum and the API route file**

Run: `cd server && php artisan install:api`
Expected output includes `Sanctum installed successfully` and confirmation that `routes/api.php` was created and registered.

- [ ] **Step 2: Verify `routes/api.php` exists and is empty of custom routes so far**

```php
<?php

use Illuminate\Support\Facades\Route;
```

(If the installer left a default `Route::get('/user', ...)` block using session-based `auth:sanctum`, leave it — it will be replaced in Task 9.)

- [ ] **Step 3: Confirm the API route file is registered in `server/bootstrap/app.php`**

It should contain an `api:` entry inside `withRouting`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

If the `api:` line is missing, add it exactly as shown.

- [ ] **Step 4: Explicitly add the `HasApiTokens` trait to the User model**

Edit `server/app/Models/User.php` so the top matches:

```php
<?php

namespace App\Models;

// Illuminate\Contracts\Auth\MustVerifyEmail
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
```

(Leave the rest of the generated class body as-is for now — `$fillable`, `$hidden`, and `$casts` are rewritten in Task 6.)

- [ ] **Step 5: Configure `server/config/cors.php`** to allow the Vue SPA origin

```php
<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('CLIENT_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
```

- [ ] **Step 6: Commit**

Run: `git add . && git commit -m "Install Sanctum, register API routes, configure CORS"`

---

### Task 4: Configure Pest and the in-memory test database

**Files:**
- Create: `server/tests/` Pest config (installed by tooling)
- Modify: `server/phpunit.xml`

- [ ] **Step 1: Install Pest**

Run: `cd server && composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies`

- [ ] **Step 2: Run the Pest installer**

Run: `php artisan pest:install`
Expected: creates `tests/Pest.php` and converts the default test suite to Pest syntax. Answer "yes" if prompted to remove PHPUnit's default `ExampleTest`.

- [ ] **Step 3: Confirm `server/phpunit.xml` runs tests against in-memory SQLite**

Edit `server/phpunit.xml` so the `<php>` block contains:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="APP_MAINTENANCE_DRIVER" value="file"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
</php>
```

- [ ] **Step 4: Write a smoke test**

Create `server/tests/Feature/SmokeTest.php`:

```php
<?php

test('the application boots', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
```

- [ ] **Step 5: Run the test suite**

Run: `php artisan test`
Expected: PASS (1 test)

- [ ] **Step 6: Commit**

Run: `git add . && git commit -m "Install Pest and configure in-memory SQLite for tests"`

---

### Task 5: TDD the profile fields migration

**Files:**
- Create: `server/database/migrations/xxxx_xx_xx_xxxxxx_add_profile_fields_to_users_table.php`
- Create: `server/tests/Feature/UsersTableTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use Illuminate\Support\Facades\Schema;

test('the users table has the expected profile columns', function () {
    expect(Schema::hasColumns('users', [
        'first_name',
        'last_name',
        'bio',
        'job_title',
        'industry',
        'profile_image',
        'interaction_preferences',
        'quiz_answers',
        'compatibility_profile',
        'onboarding_completed',
    ]))->toBeTrue();

    expect(Schema::hasColumn('users', 'name'))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UsersTableTest`
Expected: FAIL — several columns missing (`first_name`, `bio`, etc.), `name` still present.

- [ ] **Step 3: Generate the migration**

Run: `php artisan make:migration add_profile_fields_to_users_table --table=users`

- [ ] **Step 4: Fill in the generated migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->text('bio')->nullable()->after('password');
            $table->string('job_title')->nullable()->after('bio');
            $table->string('industry')->nullable()->after('job_title');
            $table->string('profile_image')->nullable()->after('industry');
            $table->json('interaction_preferences')->nullable()->after('profile_image');
            $table->json('quiz_answers')->nullable()->after('interaction_preferences');
            $table->json('compatibility_profile')->nullable()->after('quiz_answers');
            $table->boolean('onboarding_completed')->default(false)->after('compatibility_profile');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'bio',
                'job_title',
                'industry',
                'profile_image',
                'interaction_preferences',
                'quiz_answers',
                'compatibility_profile',
                'onboarding_completed',
            ]);
            $table->string('name')->after('id');
        });
    }
};
```

- [ ] **Step 5: Run the local MySQL migration**

Run: `php artisan migrate`
Expected: the new migration runs with no errors.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=UsersTableTest`
Expected: PASS (1 test) — Pest tests run migrations fresh against the in-memory SQLite DB via `RefreshDatabase`, independent of your local MySQL state. If it doesn't pass, confirm `UsersTableTest` uses the trait (see Step 6a).

- [ ] **Step 6a: Add `RefreshDatabase` to the test**

Edit `server/tests/Feature/UsersTableTest.php`, add at the top:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('the users table has the expected profile columns', function () {
```

Re-run: `php artisan test --filter=UsersTableTest` — Expected: PASS.

- [ ] **Step 7: Commit**

Run: `git add . && git commit -m "Add profile fields migration for users table"`

---

### Task 6: Update the User model (fillable, hidden, casts, defaults)

**Files:**
- Modify: `server/app/Models/User.php`
- Create: `server/tests/Unit/Models/UserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creating a user applies interaction preference defaults', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    expect($user->onboarding_completed)->toBeFalse();
    expect($user->interaction_preferences)->toBe([
        'preferred_mode' => 'one-to-one',
        'preferred_group_size' => 2,
        'virtual_preferred' => false,
        'message_before_event' => true,
        'allow_match_requests' => true,
    ]);
});

test('the password is hashed automatically', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    expect($user->password)->not->toBe('supersecret');
    expect(password_verify('supersecret', $user->password))->toBeTrue();
});

test('the password and remember token are hidden from array/JSON output', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $array = $user->toArray();

    expect($array)->not->toHaveKey('password');
    expect($array)->not->toHaveKey('remember_token');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserTest`
Expected: FAIL — `interaction_preferences` is `null`, not the default array (defaults don't exist yet).

- [ ] **Step 3: Rewrite `server/app/Models/User.php`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'bio',
        'job_title',
        'industry',
        'profile_image',
        'interaction_preferences',
        'quiz_answers',
        'compatibility_profile',
        'onboarding_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'bio' => '',
        'job_title' => '',
        'industry' => '',
        'profile_image' => '',
        'onboarding_completed' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interaction_preferences' => 'array',
            'quiz_answers' => 'array',
            'compatibility_profile' => 'array',
            'onboarding_completed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (is_null($user->interaction_preferences)) {
                $user->interaction_preferences = [
                    'preferred_mode' => 'one-to-one',
                    'preferred_group_size' => 2,
                    'virtual_preferred' => false,
                    'message_before_event' => true,
                    'allow_match_requests' => true,
                ];
            }

            if (is_null($user->quiz_answers)) {
                $user->quiz_answers = [];
            }

            if (is_null($user->compatibility_profile)) {
                $user->compatibility_profile = [];
            }
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=UserTest`
Expected: PASS (3 tests)

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS (all suites so far: SmokeTest, UsersTableTest, UserTest)

- [ ] **Step 6: Commit**

Run: `git add . && git commit -m "Add profile defaults, casts, and hidden fields to User model"`

---

### Task 7: TDD the register endpoint

**Files:**
- Create: `server/app/Http/Requests/Api/RegisterRequest.php`
- Create: `server/app/Http/Controllers/Api/AuthController.php`
- Modify: `server/routes/api.php`
- Create: `server/tests/Feature/Auth/RegisterTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can register and receives a token', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
        'password_confirmation' => 'supersecret',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email'], 'token']);
    $response->assertJsonMissingPath('user.password');

    expect(User::where('email', 'lesley@example.com')->exists())->toBeTrue();
});

test('registration fails with a mismatched password confirmation', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
        'password_confirmation' => 'different',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
});

test('registration fails with a duplicate email', function () {
    User::create([
        'first_name' => 'First',
        'last_name' => 'User',
        'email' => 'taken@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/register', [
        'first_name' => 'Second',
        'last_name' => 'User',
        'email' => 'taken@example.com',
        'password' => 'supersecret',
        'password_confirmation' => 'supersecret',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('registration fails with missing required fields', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RegisterTest`
Expected: FAIL — 404, route `/api/register` doesn't exist yet.

- [ ] **Step 3: Create `server/app/Http/Requests/Api/RegisterRequest.php`**

Run: `php artisan make:request Api/RegisterRequest`

Then fill it in:

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
```

- [ ] **Step 4: Create `server/app/Http/Controllers/Api/AuthController.php`** (register action only for now)

Run: `php artisan make:controller Api/AuthController`

Then fill it in:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}
```

- [ ] **Step 5: Add the route** to `server/routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=RegisterTest`
Expected: PASS (4 tests)

- [ ] **Step 7: Commit**

Run: `git add . && git commit -m "Add register endpoint"`

---

### Task 8: TDD the login endpoint

**Files:**
- Create: `server/app/Http/Requests/Api/LoginRequest.php`
- Modify: `server/app/Http/Controllers/Api/AuthController.php`
- Modify: `server/routes/api.php`
- Create: `server/tests/Feature/Auth/LoginTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can log in with correct credentials and receives a token', function () {
    User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email'], 'token']);
});

test('login fails with an incorrect password', function () {
    User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'lesley@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('login fails for an email that does not exist', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'supersecret',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LoginTest`
Expected: FAIL — 404, route `/api/login` doesn't exist yet.

- [ ] **Step 3: Create `server/app/Http/Requests/Api/LoginRequest.php`**

Run: `php artisan make:request Api/LoginRequest`

Then fill it in:

```php
<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticatedUser(): User
    {
        $user = User::where('email', $this->validated('email'))->first();

        if (! $user || ! password_verify($this->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        return $user;
    }
}
```

- [ ] **Step 4: Add the `login` action to `server/app/Http/Controllers/Api/AuthController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = $request->authenticatedUser();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
```

- [ ] **Step 5: Add the route** to `server/routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=LoginTest`
Expected: PASS (3 tests)

- [ ] **Step 7: Commit**

Run: `git add . && git commit -m "Add login endpoint"`

---

### Task 9: TDD the protected `/api/user` endpoint and logout

**Files:**
- Modify: `server/app/Http/Controllers/Api/AuthController.php`
- Modify: `server/routes/api.php`
- Create: `server/tests/Feature/Auth/ProtectedRouteTest.php`
- Create: `server/tests/Feature/Auth/LogoutTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// server/tests/Feature/Auth/ProtectedRouteTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an unauthenticated request to /api/user is rejected', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401);
});

test('an authenticated request to /api/user returns the current user', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->getJson('/api/user', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('email', 'lesley@example.com');
});
```

```php
<?php
// server/tests/Feature/Auth/LogoutTest.php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logout revokes the current token', function () {
    $user = User::create([
        'first_name' => 'Lesley',
        'last_name' => 'Ayemi',
        'email' => 'lesley@example.com',
        'password' => 'supersecret',
    ]);
    $token = $user->createToken('api-token')->plainTextToken;

    $logoutResponse = $this->postJson('/api/logout', [], [
        'Authorization' => "Bearer {$token}",
    ]);
    $logoutResponse->assertStatus(200);

    $followUpResponse = $this->getJson('/api/user', [
        'Authorization' => "Bearer {$token}",
    ]);
    $followUpResponse->assertStatus(401);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ProtectedRouteTest`
Expected: FAIL — route not registered.
Run: `php artisan test --filter=LogoutTest`
Expected: FAIL — route not registered.

- [ ] **Step 3: Add `user` and `logout` actions to `server/app/Http/Controllers/Api/AuthController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = $request->authenticatedUser();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
```

- [ ] **Step 4: Add the routes** to `server/routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=ProtectedRouteTest`
Expected: PASS (2 tests)
Run: `php artisan test --filter=LogoutTest`
Expected: PASS (1 test)

- [ ] **Step 6: Run the full backend suite**

Run: `php artisan test`
Expected: PASS (all suites: SmokeTest, UsersTableTest, UserTest, RegisterTest, LoginTest, ProtectedRouteTest, LogoutTest)

- [ ] **Step 7: Commit**

Run: `git add . && git commit -m "Add protected /api/user endpoint and logout"`

---

### Task 10: Manually verify the live Laravel API

**Files:** none (manual verification only)

- [ ] **Step 1: Boot the server**

Run: `php artisan serve` (leave running)

- [ ] **Step 2: Register a user via curl**

Run:
```bash
curl -s -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"first_name":"Lesley","last_name":"Ayemi","email":"lesley@example.com","password":"supersecret","password_confirmation":"supersecret"}'
```
Expected: JSON with `user` (no `password` field) and a `token` string. Copy the token value.

- [ ] **Step 3: Call the protected endpoint**

Run (replace `TOKEN` with the copied value):
```bash
curl -s http://127.0.0.1:8000/api/user \
  -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```
Expected: 200 with the user's JSON.

- [ ] **Step 4: Log out and confirm the token is revoked**

Run:
```bash
curl -s -X POST http://127.0.0.1:8000/api/logout \
  -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```
Then repeat Step 3's `curl` with the same token.
Expected: logout returns `{"message":"Logged out."}`; the repeated `/api/user` call now returns 401.

- [ ] **Step 5: Confirm the row exists in MySQL**

Run: `mysql -u root event_networking -e "SELECT id, first_name, last_name, email, onboarding_completed FROM users;"`
Expected: one row for Lesley Ayemi with `onboarding_completed = 0`.

---

### Task 11: Scaffold the Vue frontend

**Files:**
- Create: `client/` (via Vite)
- Create: `client/.env.example`

- [ ] **Step 1: Create the Vue project**

Run: `cd event-networking-app && npm create vite@latest client -- --template vue`

- [ ] **Step 2: Install dependencies**

Run: `cd client && npm install && npm install vue-router pinia axios`

- [ ] **Step 3: Install dev dependencies for testing**

Run: `npm install --save-dev vitest`

- [ ] **Step 4: Add the test script to `client/package.json`**

Edit `client/package.json`, add to `"scripts"`:

```json
"test": "vitest run"
```

- [ ] **Step 5: Create `client/.env.example`**

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

Run: `cp .env.example .env`

- [ ] **Step 6: Verify the dev server runs**

Run: `npm run dev`
Expected: prints a local URL (e.g. `http://localhost:5173/`); opening it shows the default Vite+Vue starter page.

- [ ] **Step 7: Commit**

Run: `cd .. && git add client && git commit -m "Scaffold Vue frontend"`

---

### Task 12: Create the API client with token attachment

**Files:**
- Create: `client/src/services/apiClient.js`

- [ ] **Step 1: Create `client/src/services/apiClient.js`**

```javascript
import axios from "axios";

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: "application/json",
  },
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem("authToken");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

- [ ] **Step 2: Commit**

Run: `git add client/src/services && git commit -m "Add API client with Bearer token attachment"`

---

### Task 13: TDD the authStore (register, login, logout, session restore)

**Files:**
- Create: `client/src/stores/authStore.js`
- Create: `client/tests/stores/authStore.test.js`

- [ ] **Step 1: Write the failing test** (mocks the API client; uses a fake in-memory `localStorage` since Vitest's default `jsdom`/`happy-dom` environment provides one)

```javascript
// client/tests/stores/authStore.test.js
import { describe, it, expect, vi, beforeEach } from "vitest";
import { setActivePinia, createPinia } from "pinia";

const post = vi.fn();
const get = vi.fn();
vi.mock("../../src/services/apiClient.js", () => ({
  apiClient: { post: (...args) => post(...args), get: (...args) => get(...args) },
}));

const { useAuthStore } = await import("../../src/stores/authStore.js");

describe("authStore", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    localStorage.clear();
    post.mockReset();
    get.mockReset();
  });

  it("starts unauthenticated", () => {
    const store = useAuthStore();
    expect(store.isAuthenticated).toBe(false);
    expect(store.user).toBeNull();
  });

  it("register() stores the user and token", async () => {
    post.mockResolvedValue({
      data: { user: { id: 1, first_name: "Lesley", email: "lesley@example.com" }, token: "token-1" },
    });

    const store = useAuthStore();
    await store.register({
      first_name: "Lesley",
      last_name: "Ayemi",
      email: "lesley@example.com",
      password: "supersecret",
      password_confirmation: "supersecret",
    });

    expect(post).toHaveBeenCalledWith("/register", {
      first_name: "Lesley",
      last_name: "Ayemi",
      email: "lesley@example.com",
      password: "supersecret",
      password_confirmation: "supersecret",
    });
    expect(store.isAuthenticated).toBe(true);
    expect(store.user.email).toBe("lesley@example.com");
    expect(localStorage.getItem("authToken")).toBe("token-1");
  });

  it("login() stores the user and token", async () => {
    post.mockResolvedValue({
      data: { user: { id: 2, first_name: "Returning", email: "returning@example.com" }, token: "token-2" },
    });

    const store = useAuthStore();
    await store.login({ email: "returning@example.com", password: "supersecret" });

    expect(post).toHaveBeenCalledWith("/login", {
      email: "returning@example.com",
      password: "supersecret",
    });
    expect(store.isAuthenticated).toBe(true);
    expect(localStorage.getItem("authToken")).toBe("token-2");
  });

  it("logout() clears state and calls the API", async () => {
    post.mockResolvedValueOnce({
      data: { user: { id: 3, email: "someone@example.com" }, token: "token-3" },
    });
    post.mockResolvedValueOnce({ data: { message: "Logged out." } });

    const store = useAuthStore();
    await store.login({ email: "someone@example.com", password: "supersecret" });
    await store.logout();

    expect(post).toHaveBeenCalledWith("/logout");
    expect(store.isAuthenticated).toBe(false);
    expect(store.user).toBeNull();
    expect(localStorage.getItem("authToken")).toBeNull();
  });

  it("restoreSession() re-hydrates from a stored token", async () => {
    localStorage.setItem("authToken", "existing-token");
    get.mockResolvedValue({ data: { id: 4, email: "restored@example.com" } });

    const store = useAuthStore();
    await store.restoreSession();

    expect(get).toHaveBeenCalledWith("/user");
    expect(store.isAuthenticated).toBe(true);
    expect(store.user.email).toBe("restored@example.com");
  });

  it("restoreSession() does nothing when there is no stored token", async () => {
    const store = useAuthStore();
    await store.restoreSession();

    expect(get).not.toHaveBeenCalled();
    expect(store.isAuthenticated).toBe(false);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- authStore.test.js`
Expected: FAIL — `Cannot find module '../../src/stores/authStore.js'`

- [ ] **Step 3: Create `client/src/stores/authStore.js`**

```javascript
import { defineStore } from "pinia";
import { apiClient } from "../services/apiClient.js";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
  }),

  getters: {
    isAuthenticated: (state) => state.user !== null,
  },

  actions: {
    async register(payload) {
      const response = await apiClient.post("/register", payload);
      this._setSession(response.data.user, response.data.token);
    },

    async login(payload) {
      const response = await apiClient.post("/login", payload);
      this._setSession(response.data.user, response.data.token);
    },

    async logout() {
      await apiClient.post("/logout");
      this._clearSession();
    },

    async restoreSession() {
      const token = localStorage.getItem("authToken");
      if (!token) {
        return;
      }
      const response = await apiClient.get("/user");
      this.user = response.data;
    },

    _setSession(user, token) {
      localStorage.setItem("authToken", token);
      this.user = user;
    },

    _clearSession() {
      localStorage.removeItem("authToken");
      this.user = null;
    },
  },
});
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- authStore.test.js`
Expected: PASS (6 tests)

- [ ] **Step 5: Wire Pinia into `client/src/main.js`**

```javascript
import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";

const app = createApp(App);
app.use(createPinia());
app.mount("#app");
```

- [ ] **Step 6: Commit**

Run: `git add client && git commit -m "Add authStore with register/login/logout/restoreSession"`

---

### Task 14: Build stub page components for every route

**Files:**
- Create: `client/src/pages/LandingPage.vue`
- Create: `client/src/pages/RegisterPage.vue`
- Create: `client/src/pages/LoginPage.vue`
- Create: `client/src/pages/OnboardingPage.vue`
- Create: `client/src/pages/CompatibilityQuizPage.vue`
- Create: `client/src/pages/DashboardPage.vue`
- Create: `client/src/pages/EventsPage.vue`
- Create: `client/src/pages/EventDetailsPage.vue`
- Create: `client/src/pages/SavedEventsPage.vue`
- Create: `client/src/pages/MatchesPage.vue`
- Create: `client/src/pages/FriendsPage.vue`
- Create: `client/src/pages/MessagesPage.vue`
- Create: `client/src/pages/ChatPage.vue`
- Create: `client/src/pages/ProfilePage.vue`

- [ ] **Step 1: Create the eleven static stub pages**, each with this exact content pattern, substituting the page's own title:

`client/src/pages/OnboardingPage.vue` — `<h1>Onboarding</h1>`
`client/src/pages/CompatibilityQuizPage.vue` — `<h1>Compatibility Quiz</h1>`
`client/src/pages/DashboardPage.vue` — `<h1>Dashboard</h1>`
`client/src/pages/EventsPage.vue` — `<h1>Events</h1>`
`client/src/pages/EventDetailsPage.vue` — `<h1>Event Details</h1>`
`client/src/pages/SavedEventsPage.vue` — `<h1>Saved Events</h1>`
`client/src/pages/MatchesPage.vue` — `<h1>Matches</h1>`
`client/src/pages/FriendsPage.vue` — `<h1>Friends</h1>`
`client/src/pages/MessagesPage.vue` — `<h1>Messages</h1>`
`client/src/pages/ChatPage.vue` — `<h1>Chat</h1>`
`client/src/pages/ProfilePage.vue` — `<h1>Profile</h1>`

Each file's full content:

```vue
<template>
  <section>
    <h1>PAGE TITLE HERE</h1>
    <p>This page will be built in a later phase.</p>
  </section>
</template>

<script setup>
</script>
```

- [ ] **Step 2: Create `client/src/pages/LandingPage.vue`**

```vue
<template>
  <section>
    <h1>Find your people, at your own pace.</h1>
    <p>
      Discover events, choose how you want to interact, and message before
      you ever have to make small talk in person.
    </p>
    <RouterLink to="/register">Get started</RouterLink>
    <RouterLink to="/login">Log in</RouterLink>
  </section>
</template>

<script setup>
import { RouterLink } from "vue-router";
</script>
```

- [ ] **Step 3: Create `client/src/pages/RegisterPage.vue`** (fully built — a Phase 2 deliverable, not a stub)

```vue
<template>
  <section>
    <h1>Create your account</h1>
    <form @submit.prevent="handleSubmit">
      <label>
        First name
        <input v-model="firstName" type="text" required />
      </label>
      <label>
        Last name
        <input v-model="lastName" type="text" required />
      </label>
      <label>
        Email
        <input v-model="email" type="email" required />
      </label>
      <label>
        Password
        <input v-model="password" type="password" minlength="8" required />
      </label>
      <label>
        Confirm password
        <input v-model="passwordConfirmation" type="password" minlength="8" required />
      </label>
      <p v-if="errorMessage" role="alert">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? "Creating account…" : "Create account" }}
      </button>
    </form>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore.js";

const firstName = ref("");
const lastName = ref("");
const email = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);

const authStore = useAuthStore();
const router = useRouter();

async function handleSubmit() {
  errorMessage.value = "";
  isSubmitting.value = true;
  try {
    await authStore.register({
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    router.push("/onboarding");
  } catch (error) {
    errorMessage.value =
      "We couldn't create your account. Your information has not been lost. Please try again.";
  } finally {
    isSubmitting.value = false;
  }
}
</script>
```

- [ ] **Step 4: Create `client/src/pages/LoginPage.vue`** (fully built)

```vue
<template>
  <section>
    <h1>Log in</h1>
    <form @submit.prevent="handleSubmit">
      <label>
        Email
        <input v-model="email" type="email" required />
      </label>
      <label>
        Password
        <input v-model="password" type="password" required />
      </label>
      <p v-if="errorMessage" role="alert">{{ errorMessage }}</p>
      <button type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? "Logging in…" : "Log in" }}
      </button>
    </form>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/authStore.js";

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const isSubmitting = ref(false);

const authStore = useAuthStore();
const router = useRouter();

async function handleSubmit() {
  errorMessage.value = "";
  isSubmitting.value = true;
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push("/dashboard");
  } catch (error) {
    errorMessage.value = "We couldn't log you in. Please check your email and password.";
  } finally {
    isSubmitting.value = false;
  }
}
</script>
```

- [ ] **Step 5: Commit**

Run: `git add client/src/pages && git commit -m "Add page stubs and full register/login pages"`

---

### Task 15: TDD the router navigation guard and configure routes

**Files:**
- Create: `client/src/router/guards.js`
- Create: `client/tests/router/guards.test.js`
- Create: `client/src/router/index.js`
- Modify: `client/src/main.js`
- Modify: `client/src/App.vue`

- [ ] **Step 1: Write the failing test**

```javascript
// client/tests/router/guards.test.js
import { describe, it, expect } from "vitest";
import { resolveNavigation } from "../../src/router/guards.js";

describe("resolveNavigation", () => {
  it("allows navigation to a route that does not require auth", () => {
    const to = { meta: { requiresAuth: false }, path: "/" };
    const result = resolveNavigation(to, { isAuthenticated: false });
    expect(result).toBe(true);
  });

  it("allows navigation to a protected route when authenticated", () => {
    const to = { meta: { requiresAuth: true }, path: "/dashboard" };
    const result = resolveNavigation(to, { isAuthenticated: true });
    expect(result).toBe(true);
  });

  it("redirects to /login when navigating to a protected route while unauthenticated", () => {
    const to = { meta: { requiresAuth: true }, path: "/dashboard" };
    const result = resolveNavigation(to, { isAuthenticated: false });
    expect(result).toEqual({ path: "/login", query: { redirect: "/dashboard" } });
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- guards.test.js`
Expected: FAIL — `Cannot find module '../../src/router/guards.js'`

- [ ] **Step 3: Create `client/src/router/guards.js`**

```javascript
export function resolveNavigation(to, authState) {
  const requiresAuth = to.meta?.requiresAuth === true;

  if (!requiresAuth) {
    return true;
  }

  if (authState.isAuthenticated) {
    return true;
  }

  return { path: "/login", query: { redirect: to.path } };
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- guards.test.js`
Expected: PASS (3 tests)

- [ ] **Step 5: Create `client/src/router/index.js`**

```javascript
import { createRouter, createWebHistory } from "vue-router";
import { resolveNavigation } from "./guards.js";
import { useAuthStore } from "../stores/authStore.js";

import LandingPage from "../pages/LandingPage.vue";
import RegisterPage from "../pages/RegisterPage.vue";
import LoginPage from "../pages/LoginPage.vue";
import OnboardingPage from "../pages/OnboardingPage.vue";
import CompatibilityQuizPage from "../pages/CompatibilityQuizPage.vue";
import DashboardPage from "../pages/DashboardPage.vue";
import EventsPage from "../pages/EventsPage.vue";
import EventDetailsPage from "../pages/EventDetailsPage.vue";
import SavedEventsPage from "../pages/SavedEventsPage.vue";
import MatchesPage from "../pages/MatchesPage.vue";
import FriendsPage from "../pages/FriendsPage.vue";
import MessagesPage from "../pages/MessagesPage.vue";
import ChatPage from "../pages/ChatPage.vue";
import ProfilePage from "../pages/ProfilePage.vue";

const routes = [
  { path: "/", component: LandingPage, meta: { requiresAuth: false } },
  { path: "/register", component: RegisterPage, meta: { requiresAuth: false } },
  { path: "/login", component: LoginPage, meta: { requiresAuth: false } },
  { path: "/onboarding", component: OnboardingPage, meta: { requiresAuth: true } },
  { path: "/quiz", component: CompatibilityQuizPage, meta: { requiresAuth: true } },
  { path: "/dashboard", component: DashboardPage, meta: { requiresAuth: true } },
  { path: "/events", component: EventsPage, meta: { requiresAuth: true } },
  { path: "/events/:id", component: EventDetailsPage, meta: { requiresAuth: true } },
  { path: "/saved-events", component: SavedEventsPage, meta: { requiresAuth: true } },
  { path: "/matches", component: MatchesPage, meta: { requiresAuth: true } },
  { path: "/friends", component: FriendsPage, meta: { requiresAuth: true } },
  { path: "/messages", component: MessagesPage, meta: { requiresAuth: true } },
  {
    path: "/messages/:conversationId",
    component: ChatPage,
    meta: { requiresAuth: true },
  },
  { path: "/profile", component: ProfilePage, meta: { requiresAuth: true } },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  const authStore = useAuthStore();
  return resolveNavigation(to, { isAuthenticated: authStore.isAuthenticated });
});
```

- [ ] **Step 6: Wire the router into `client/src/main.js`**

```javascript
import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import { router } from "./router/index.js";
import { useAuthStore } from "./stores/authStore.js";

const app = createApp(App);
app.use(createPinia());
app.use(router);

useAuthStore().restoreSession().finally(() => {
  app.mount("#app");
});
```

- [ ] **Step 7: Replace `client/src/App.vue` with a router outlet**

```vue
<template>
  <RouterView />
</template>

<script setup>
import { RouterView } from "vue-router";
</script>
```

- [ ] **Step 8: Manually verify routing and the guard**

Run: `npm run dev` (with the Laravel server from Task 10 also running), then in the browser:
1. Visit `/` — Landing page loads.
2. Visit `/dashboard` directly — redirected to `/login?redirect=/dashboard` (not authenticated yet).
3. Visit `/register`, `/login` — both load without redirect.

- [ ] **Step 9: Commit**

Run: `git add client/src && git commit -m "Configure Vue Router with full route table, auth guard, and session restore"`

---

### Task 16: Build layouts and wire them to routes

**Files:**
- Create: `client/src/layouts/DefaultLayout.vue`
- Create: `client/src/layouts/AuthLayout.vue`
- Modify: `client/src/pages/LandingPage.vue`, `RegisterPage.vue`, `LoginPage.vue`, and all eleven authenticated stub pages

- [ ] **Step 1: Create `client/src/layouts/AuthLayout.vue`** (calm, minimal chrome for register/login/landing)

```vue
<template>
  <div class="auth-layout">
    <slot />
  </div>
</template>

<script setup>
</script>

<style scoped>
.auth-layout {
  max-width: 420px;
  margin: 0 auto;
  padding: 2rem;
}
</style>
```

- [ ] **Step 2: Create `client/src/layouts/DefaultLayout.vue`** (authenticated app shell)

```vue
<template>
  <div class="default-layout">
    <nav>
      <RouterLink to="/dashboard">Dashboard</RouterLink>
      <RouterLink to="/events">Events</RouterLink>
      <RouterLink to="/saved-events">Saved</RouterLink>
      <RouterLink to="/matches">Matches</RouterLink>
      <RouterLink to="/friends">Friends</RouterLink>
      <RouterLink to="/messages">Messages</RouterLink>
      <RouterLink to="/profile">Profile</RouterLink>
    </nav>
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router";
</script>

<style scoped>
.default-layout main {
  max-width: 960px;
  margin: 0 auto;
  padding: 1.5rem;
}
</style>
```

- [ ] **Step 3: Wrap `LandingPage.vue`, `RegisterPage.vue`, `LoginPage.vue` in `AuthLayout`**

In each of the three files, wrap the existing `<section>...</section>` with `<AuthLayout><section>...</section></AuthLayout>` and add `import AuthLayout from "../layouts/AuthLayout.vue";` to the `<script setup>` block. Existing form logic is untouched — only the wrapping tag and one import line change.

- [ ] **Step 4: Wrap every authenticated stub page in `DefaultLayout`**

Apply this same two-line change to each of `OnboardingPage.vue`, `CompatibilityQuizPage.vue`, `DashboardPage.vue`, `EventsPage.vue`, `EventDetailsPage.vue`, `SavedEventsPage.vue`, `MatchesPage.vue`, `FriendsPage.vue`, `MessagesPage.vue`, `ChatPage.vue`, `ProfilePage.vue`. Example for `DashboardPage.vue`:

```vue
<template>
  <DefaultLayout>
    <h1>Dashboard</h1>
    <p>This page will be built in a later phase.</p>
  </DefaultLayout>
</template>

<script setup>
import DefaultLayout from "../layouts/DefaultLayout.vue";
</script>
```

- [ ] **Step 5: Manually verify**

Run: `npm run dev`. Visit `/` and `/login` — no nav bar (auth layout). Log in, visit `/dashboard` — nav bar with links present (default layout).

- [ ] **Step 6: Commit**

Run: `git add client/src && git commit -m "Add DefaultLayout and AuthLayout, wire to pages"`

---

### Task 17: Build the profile onboarding shell

**Files:**
- Modify: `client/src/pages/OnboardingPage.vue`

- [ ] **Step 1: Replace `OnboardingPage.vue` with a progress-bar shell** (static — the three steps get real fields in later phases; this task only establishes the shell and step navigation, matching spec section 12's progress bar)

```vue
<template>
  <DefaultLayout>
    <h1>Complete your profile</h1>
    <ol class="progress">
      <li :class="{ active: currentStep === 1 }">Profile</li>
      <li :class="{ active: currentStep === 2 }">Preferences</li>
      <li :class="{ active: currentStep === 3 }">Compatibility Quiz</li>
      <li :class="{ active: currentStep === 4 }">Complete</li>
    </ol>

    <section v-if="currentStep === 1">
      <p>Personal information fields will go here (Phase: Profile onboarding).</p>
      <button type="button" @click="currentStep = 2">Continue</button>
    </section>

    <section v-if="currentStep === 2">
      <p>Communication preference fields will go here.</p>
      <button type="button" @click="currentStep = 1">Back</button>
      <button type="button" @click="currentStep = 3">Continue</button>
    </section>

    <section v-if="currentStep === 3">
      <p>The compatibility quiz will be embedded here.</p>
      <button type="button" @click="currentStep = 2">Back</button>
      <button type="button" @click="currentStep = 4">Continue</button>
    </section>

    <section v-if="currentStep === 4">
      <p>You're all set.</p>
      <RouterLink to="/dashboard">Go to your dashboard</RouterLink>
    </section>
  </DefaultLayout>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import DefaultLayout from "../layouts/DefaultLayout.vue";

const currentStep = ref(1);
</script>
```

- [ ] **Step 2: Manually verify**

Run: `npm run dev`, log in, navigate to `/onboarding`. Click through Continue/Back and confirm the four steps advance correctly and the final step links to `/dashboard`.

- [ ] **Step 3: Commit**

Run: `git add client/src/pages/OnboardingPage.vue && git commit -m "Add profile onboarding shell with progress steps"`

---

### Task 18: Full end-to-end verification

**Files:** none (manual verification only)

- [ ] **Step 1: Run every automated test suite**

Run: `cd server && php artisan test` — expect all suites passing.
Run: `cd client && npm test` — expect all suites passing (authStore, guards).

- [ ] **Step 2: Boot both servers**

Run: `cd server && php artisan serve` (leave running)
Run, in a second terminal: `cd client && npm run dev` (leave running)

- [ ] **Step 3: Walk the real registration → login journey in the browser**

1. Visit the client URL, click "Get started."
2. Fill in first name, last name, a real-format email, an 8+ character password, and matching confirmation. Submit.
3. Confirm you land on `/onboarding` and the progress bar shows step 1.
4. Query MySQL for the `users` table — confirm a row exists with the matching `email`, `first_name`, `last_name`, and `onboarding_completed = 0`.
5. In the browser dev tools, confirm `localStorage.getItem("authToken")` is a non-empty string.
6. Refresh the page while still on `/dashboard` — confirm you stay logged in (session restore) rather than being redirected to `/login`.
7. In a private/incognito window (unauthenticated), visit `/dashboard` directly — confirm redirect to `/login?redirect=/dashboard`.

- [ ] **Step 4: Confirm error handling reads calmly**

Try registering with an email already in use, and try logging in with a wrong password. Confirm the on-screen messages match the calm tone from spec section 23 (no "Something went terribly wrong" style text) — both `RegisterPage.vue` and `LoginPage.vue` already use the required phrasing.

- [ ] **Step 5: Record results and stop**

If every check in Steps 1–4 passes, Phase 1 (Foundation) and Phase 2 (Authentication) are complete on the Laravel stack. Note any failures found here as follow-up tasks before starting the Events phase plan.

---

## Coverage Check (Phase 1 + 2 items from the spec's "Recommended implementation order")

1. Create repository → Task 1
2. Set up Vue → Task 11
3. Set up Express → Task 2, 3 (Laravel API in place of Express)
4. Connect MongoDB → Task 2 (MySQL in place of MongoDB)
5. Configure Firebase → N/A — Sanctum token auth replaces Firebase Authentication (Tasks 3, 6–9); Reverb replaces Firestore for real-time and is configured when the Messaging plan is written
6. Create basic layouts → Task 16
7. Configure routing → Tasks 15
8. Build registration → Tasks 7, 13, 14
9. Build login → Tasks 8, 13, 14
10. Add backend token verification → Task 9 (`auth:sanctum` middleware)
11. Create MongoDB user profiles → Tasks 5, 6 (MySQL `users` table in place of MongoDB documents)
12. Add protected routes → Tasks 9, 15
13. Add profile onboarding → Task 17

All 13 items are covered under the new stack. Events, matching, friends, messaging (Reverb channels), dashboard build-out, security hardening, moderation, and deployment (spec items 14–48) remain out of scope for this plan and will be their own plans.
