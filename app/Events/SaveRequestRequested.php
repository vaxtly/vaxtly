<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SaveRequestRequested
{
    use Dispatchable;

    public function __construct(mixed ...$args) {}
}
