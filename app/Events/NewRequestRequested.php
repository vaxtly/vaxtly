<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NewRequestRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
