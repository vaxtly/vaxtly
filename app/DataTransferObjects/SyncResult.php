<?php

namespace App\DataTransferObjects;

class SyncResult
{
    /**
     * @param  array<array{collection_id: string, collection_name: string}>  $conflicts
     * @param  array<string>  $errors
     */
    public function __construct(
        public int $pulled = 0,
        public int $pushed = 0,
        public array $conflicts = [],
        public array $errors = [],
    ) {}
}
