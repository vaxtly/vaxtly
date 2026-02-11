<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CloseTabRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
