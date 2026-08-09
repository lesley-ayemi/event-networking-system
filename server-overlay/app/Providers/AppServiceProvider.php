<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Short, stable aliases for polymorphic report targets — stored in
        // reports.reportable_type instead of a raw namespaced class name, and
        // what clients submit for Report::REPORTABLE_TYPES.
        Relation::morphMap([
            'user' => User::class,
            'message' => Message::class,
            'event' => Event::class,
        ]);

        // Baseline for every route carrying the 'api' middleware group
        // (enabled via ->throttleApi() in bootstrap/app.php). Keyed by user
        // when authenticated, otherwise IP.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Deliberately much stricter than the general API baseline: login,
        // register, forgot-password, and reset-password are exactly the
        // endpoints a credential-stuffing or brute-force script would hit.
        // Keyed by email+IP together so one IP can't cycle through many
        // accounts, and one leaked/guessed IP list can't be used to hammer a
        // single account from many addresses.
        RateLimiter::for('auth', function (Request $request) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Loose enough for legitimate use (reporting a handful of bad actors
        // in a session) while still ruling out a script spamming reports.
        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
