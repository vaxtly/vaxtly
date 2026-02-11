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
        $this->clearStaleCacheOnVersionChange();
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
     * After an app update the compiled Blade views in storage/framework/views
     * may reference stale asset URLs (e.g. old Livewire JS hash), causing 404s
     * that prevent the frontend from booting. Detect version changes and clear
     * compiled views + route/config caches so everything regenerates fresh.
     */
    protected function clearStaleCacheOnVersionChange(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $currentVersion = config('app.version');
            $cachedVersion = get_setting('system.app_version');

            if ($currentVersion === $cachedVersion) {
                BootLogger::log("clearStaleCache: version match ({$currentVersion})");

                return;
            }

            BootLogger::log("clearStaleCache: version changed ({$cachedVersion} -> {$currentVersion}), clearing caches");

            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');

            set_setting('system.app_version', $currentVersion);

            BootLogger::log('clearStaleCache: done');
        } catch (\Throwable) {
            // Silently fail if settings table isn't available yet
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

        BootLogger::log('configureDefaults: done');
    }
}
