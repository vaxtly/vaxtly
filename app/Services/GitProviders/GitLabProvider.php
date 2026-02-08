<?php

namespace App\Services\GitProviders;

use App\Contracts\GitProviderInterface;
use App\DataTransferObjects\FileContent;
use Illuminate\Support\Facades\Http;

class GitLabProvider implements GitProviderInterface
{
    private string $baseUrl = 'https://gitlab.com/api/v4';

    private string $projectId;

    public function __construct(
        private string $repository,
        private string $token,
        private string $branch = 'main',
    ) {
        $this->projectId = urlencode($this->repository);
    }

    /**
     * List files in a directory (non-recursive, files only).
     *
     * @return array<FileContent>
     */
    public function listFiles(string $path): array
    {
        $response = $this->request()
            ->get("{$this->baseUrl}/projects/{$this->projectId}/repository/tree", [
                'path' => $path,
                'ref' => $this->branch,
                'per_page' => 100,
            ]);

        if ($response->status() === 404) {
            return [];
        }

        $response->throw();

        $files = [];
        foreach ($response->json() as $item) {
            if ($item['type'] === 'blob' && str_ends_with($item['name'], '.yaml')) {
                $files[] = new FileContent(
                    path: $item['path'],
                    content: '',
                    sha: $item['id'],
                );
            }
        }

        return $files;
    }

    /**
     * List all items in a directory recursively (files and subdirectories).
     *
     * @return array<array{type: string, path: string, sha: string}>
     */
    public function listDirectoryRecursive(string $path): array
    {
        $response = $this->request()
            ->get("{$this->baseUrl}/projects/{$this->projectId}/repository/tree", [
                'path' => $path,
                'ref' => $this->branch,
                'recursive' => true,
                'per_page' => 100,
            ]);

        if ($response->status() === 404) {
            return [];
        }

        $response->throw();

        $items = [];
        foreach ($response->json() as $item) {
            $items[] = [
                'type' => $item['type'] === 'tree' ? 'dir' : 'file',
                'path' => $item['path'],
                'sha' => $item['id'],
            ];
        }

        return $items;
    }

    /**
     * Get the full directory tree with file contents.
     *
     * @return array<FileContent>
     */
    public function getDirectoryTree(string $path): array
    {
        $items = $this->listDirectoryRecursive($path);
        $files = [];

        foreach ($items as $item) {
            if ($item['type'] === 'file' && str_ends_with($item['path'], '.yaml')) {
                $file = $this->getFile($item['path']);
                if ($file) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    public function getFile(string $path): ?FileContent
    {
        $encodedPath = urlencode($path);

        $response = $this->request()
            ->get("{$this->baseUrl}/projects/{$this->projectId}/repository/files/{$encodedPath}", [
                'ref' => $this->branch,
            ]);

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $data = $response->json();

        return new FileContent(
            path: $path,
            content: base64_decode($data['content']),
            sha: $data['blob_id'],
            commitSha: $data['last_commit_id'] ?? null,
        );
    }

    public function createFile(string $path, string $content, string $commitMessage): string
    {
        $encodedPath = urlencode($path);

        $response = $this->request()
            ->post("{$this->baseUrl}/projects/{$this->projectId}/repository/files/{$encodedPath}", [
                'branch' => $this->branch,
                'content' => $content,
                'commit_message' => $commitMessage,
            ]);

        $response->throw();

        // Fetch the new blob_id
        $file = $this->getFile($path);

        return $file?->sha ?? '';
    }

    public function updateFile(string $path, string $content, string $sha, string $commitMessage): string
    {
        $encodedPath = urlencode($path);

        $response = $this->request()
            ->put("{$this->baseUrl}/projects/{$this->projectId}/repository/files/{$encodedPath}", [
                'branch' => $this->branch,
                'content' => $content,
                'commit_message' => $commitMessage,
                'last_commit_id' => $sha,
            ]);

        $response->throw();

        $file = $this->getFile($path);

        return $file?->sha ?? '';
    }

    public function deleteFile(string $path, string $sha, string $commitMessage): void
    {
        $encodedPath = urlencode($path);

        $response = $this->request()
            ->delete("{$this->baseUrl}/projects/{$this->projectId}/repository/files/{$encodedPath}", [
                'branch' => $this->branch,
                'commit_message' => $commitMessage,
            ]);

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    /**
     * Delete an entire directory and all its contents.
     */
    public function deleteDirectory(string $path, string $commitMessage): void
    {
        // Get all files in the directory
        $items = $this->listDirectoryRecursive($path);

        if (empty($items)) {
            return;
        }

        // Delete files one by one
        $files = array_filter($items, fn ($item) => $item['type'] === 'file');

        foreach ($files as $file) {
            $fileContent = $this->getFile($file['path']);
            if ($fileContent) {
                $this->deleteFile($file['path'], $fileContent->sha, $commitMessage);
            }
        }
    }

    /**
     * Commit multiple files in a single atomic commit using GitLab Commits API.
     *
     * @param  array<string, string>  $files  Map of path => content
     * @return string The new commit SHA
     */
    public function commitMultipleFiles(array $files, string $commitMessage, array $deletePaths = []): string
    {
        // Build actions array for the commit
        $actions = [];
        foreach ($files as $path => $content) {
            // Check if file exists to determine action type
            $existingFile = $this->getFile($path);

            $actions[] = [
                'action' => $existingFile ? 'update' : 'create',
                'file_path' => $path,
                'content' => $content,
            ];
        }

        // Add deletion actions
        foreach ($deletePaths as $path) {
            $actions[] = [
                'action' => 'delete',
                'file_path' => $path,
            ];
        }

        // Create commit with all actions
        $response = $this->request()
            ->post("{$this->baseUrl}/projects/{$this->projectId}/repository/commits", [
                'branch' => $this->branch,
                'commit_message' => $commitMessage,
                'actions' => $actions,
            ]);

        $response->throw();

        return $response->json('id');
    }

    public function testConnection(): bool
    {
        $response = $this->request()
            ->get("{$this->baseUrl}/projects/{$this->projectId}");

        return $response->successful();
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'PRIVATE-TOKEN' => $this->token,
        ])->timeout(30);
    }
}
