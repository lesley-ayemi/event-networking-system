<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

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
    }
}
