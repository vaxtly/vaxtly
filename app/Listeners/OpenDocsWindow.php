<?php

namespace App\Listeners;

use App\Events\OpenHelpRequested;
use Native\Desktop\Facades\Window;

class OpenDocsWindow
{
    public function handle(OpenHelpRequested $event): void
    {
        Window::open('docs')
            ->route('docs')
            ->width(960)
            ->height(700)
            ->minWidth(700)
            ->minHeight(500)
            ->title('Vaxtly User Guide')
            ->hideMenu()
            ->rememberState();
    }
}
