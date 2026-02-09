<?php

namespace App\Providers;

use App\Events\CheckForUpdatesRequested;
use App\Events\OpenHelpRequested;
use App\Events\OpenSettingsRequested;
use App\Events\OpenWelcomeRequested;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\AutoUpdater;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $this->configureMenu();
        $this->openMainWindow();

        AutoUpdater::checkForUpdates();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '300',
        ];
    }

    protected function configureMenu(): void
    {
        Menu::create(
            Menu::label(config('app.name'))->submenu(
                Menu::label('About '.config('app.name'))->event(OpenSettingsRequested::class),
                Menu::separator(),
                Menu::label('Settings...')->event(OpenSettingsRequested::class)->hotkey('CmdOrCtrl+,'),
                Menu::separator(),
                Menu::quit(),
            ),
            Menu::label('File')->submenu(
                Menu::label('Settings...')->event(OpenSettingsRequested::class)->hotkey('CmdOrCtrl+,'),
                Menu::separator(),
                Menu::close(),
            ),
            Menu::edit(),
            Menu::view(),
            Menu::label('Help')->submenu(
                Menu::label('Help')->event(OpenHelpRequested::class),
                Menu::label('Welcome Guide')->event(OpenWelcomeRequested::class),
                Menu::separator(),
                Menu::label('Check for Updates...')->event(CheckForUpdatesRequested::class),
            ),
        );
    }

    protected function openMainWindow(): void
    {
        Window::open()
            ->width(1280)
            ->height(800)
            ->minWidth(800)
            ->minHeight(600)
            ->rememberState();
    }
}
