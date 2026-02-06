<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OpenSettingsRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
