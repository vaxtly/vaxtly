<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OpenWelcomeRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
