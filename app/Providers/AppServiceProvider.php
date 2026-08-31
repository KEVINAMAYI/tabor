<?php

namespace App\Providers;

use App\Models\StudentFeeItem;
use App\Observers\StudentFeeItemObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Laravel's migrator only globs database/migrations/*.php (one level,
        // non-recursive) — these feature-grouped subdirectories were never
        // registered, so a fresh `php artisan migrate` (a new environment,
        // `migrate:fresh`, or the test suite's RefreshDatabase) silently skips
        // every table in them. They only "work" on existing installs because
        // someone ran `migrate --path=database/migrations/<dir>` by hand once
        // and the migrations table remembers it. Registering them here makes
        // a from-scratch migrate complete and reproducible.
        $this->loadMigrationsFrom([
            database_path('migrations/finance-module'),
            database_path('migrations/lms'),
            database_path('migrations/blogs'),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (config('app.disabled')) {
            abort(response()->view(
                'maintenance.simple',
                [],
                503
            ));
        }

        StudentFeeItem::observe(StudentFeeItemObserver::class);
    }
}
