<?php

namespace App\Providers;

use App\Services\EncryptionService;
use App\Services\WorkspaceService;
use App\Support\BootLogger;
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
        $this->clearStaleCachesOnVersionChange();

        $this->app->singleton(EncryptionService::class);
        $this->app->singleton(WorkspaceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        BootLogger::start();

        $this->runMigrations();
        $this->configureDefaults();

        BootLogger::log('AppServiceProvider::boot() complete');
    }

    /**
     * Run pending migrations automatically on boot.
     * Essential for NativePHP/Electron where users get app updates
     * but have no way to run artisan commands manually.
     *
     * Optimized: compares on-disk file count against a cached count
     * to skip DB queries entirely when no new migrations exist.
     */
    protected function runMigrations(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $diskCount = count(glob(database_path('migrations/*.php')) ?: []);
            $cachedCount = (int) get_setting('system.migration_count', 0);

            if ($diskCount > 0 && $diskCount === $cachedCount) {
                BootLogger::log("runMigrations: skipped — count matches ({$diskCount})");

                return;
            }

            BootLogger::log("runMigrations: running (disk={$diskCount}, cached={$cachedCount})");

            Artisan::call('migrate', ['--force' => true]);

            set_setting('system.migration_count', $diskCount);

            BootLogger::log('runMigrations: completed');
        } catch (\Throwable) {
            // Silently fail if the database isn't available yet
        }
    }

    /**
     * On version change (app update), delete stale route cache and compiled views.
     *
     * Must run in register() — BEFORE any provider boots — so that
     * RouteServiceProvider::boot() finds no cache file and loads routes
     * dynamically. This fixes the Livewire JS 404 caused by the route
     * cache containing a hash derived from the build-time APP_KEY while
     * the runtime .env (persisted across updates) has a different key.
     *
     * Uses a simple file-based version check because the DB is not
     * available during register().
     */
    protected function clearStaleCachesOnVersionChange(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $versionFile = $this->app->storagePath('framework/app_version');
        $currentVersion = $this->app->make('config')->get('app.version');

        if (file_exists($versionFile) && trim((string) file_get_contents($versionFile)) === $currentVersion) {
            return;
        }

        // Delete route cache — built with build-time APP_KEY, mismatches runtime key.
        // Livewire's EndpointResolver hashes config('app.key') to create the JS route
        // path (e.g. /livewire-abc123/livewire.min.js). The cached route has the hash
        // from the CI key, but runtime computes a different hash from the persisted key.
        $routeCache = $this->app->getCachedRoutesPath();
        if (file_exists($routeCache)) {
            @unlink($routeCache);
        }

        // Delete compiled Blade views — may embed stale asset URLs
        foreach (glob($this->app->storagePath('framework/views/*.php')) ?: [] as $file) {
            @unlink($file);
        }

        @file_put_contents($versionFile, $currentVersion);
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

        BootLogger::log('configureDefaults: done');
    }
}
