<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
     *
     * F-08 (failed-login logging) is handled by App\Listeners\LogFailedLogin,
     * which Laravel registers automatically by the event it type-hints — so no
     * explicit binding is needed here, and adding one would double-fire it.
     */
    public function boot(): void
    {
        // Plain-text pagination instead of the default Tailwind view, which
        // renders as oversized SVG arrows without a build step this project
        // deliberately does not have.
        Paginator::defaultView('pagination.minimal');
    }
}
