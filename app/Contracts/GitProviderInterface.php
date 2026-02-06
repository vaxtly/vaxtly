<?php

namespace App\Contracts;

use App\DataTransferObjects\FileContent;

interface GitProviderInterface
{
    /**
     * List files in a directory (non-recursive, files only).
     *
     * @return array<FileContent>
     */
    public function listFiles(string $path): array;

    /**
     * List all items in a directory recursively (files and subdirectories).
     *
     * @return array<array{type: string, path: string, sha: string}>
     */
    public function listDirectoryRecursive(string $path): array;

    /**
     * Get the full directory tree with file contents.
     *
     * @return array<FileContent>
     */
    public function getDirectoryTree(string $path): array;

    public function getFile(string $path): ?FileContent;

    /**
     * Create a file and return the new SHA.
     */
    public function createFile(string $path, string $content, string $commitMessage): string;

    /**
     * Update a file and return the new SHA.
     */
    public function updateFile(string $path, string $content, string $sha, string $commitMessage): string;

    public function deleteFile(string $path, string $sha, string $commitMessage): void;

    /**
     * Delete an entire directory and all its contents.
     */
    public function deleteDirectory(string $path, string $commitMessage): void;

    /**
     * Commit multiple files in a single atomic commit.
     * Much faster than individual file operations.
     *
     * @param  array<string, string>  $files  Map of path => content
     * @return string The new commit SHA
     */
    public function commitMultipleFiles(array $files, string $commitMessage): string;

    public function testConnection(): bool;
}

