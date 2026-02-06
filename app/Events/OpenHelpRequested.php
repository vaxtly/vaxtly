<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OpenHelpRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
