<?php

namespace App\Exceptions;

use RuntimeException;

class SyncConflictException extends RuntimeException
{
    /**
     * @param  array<string>  $conflictedFiles
     */
    public function __construct(
        public readonly array $conflictedFiles = [],
        string $message = '',
    ) {
        parent::__construct($message ?: 'Sync conflict detected: '.implode(', ', array_map('basename', $conflictedFiles)));
    }
}
