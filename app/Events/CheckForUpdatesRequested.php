<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CheckForUpdatesRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
