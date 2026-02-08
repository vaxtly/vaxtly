<?php

namespace App\Providers;

use App\Services\WorkspaceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WorkspaceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->runMigrations();
        $this->configureDefaults();
    }

    /**
     * Run pending migrations automatically on boot.
     * Essential for NativePHP/Electron where users get app updates
     * but have no way to run artisan commands manually.
     */
    protected function runMigrations(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable) {
            // Silently fail if the database isn't available yet
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
