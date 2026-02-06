<?php

namespace App\DataTransferObjects;

class FileContent
{
    public function __construct(
        public string $path,
        public string $content,
        public ?string $sha = null,
        public ?string $commitSha = null,
    ) {}
}
