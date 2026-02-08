<?php

namespace App\Services\GitProviders;

use App\Contracts\GitProviderInterface;
use App\DataTransferObjects\FileContent;
use Illuminate\Support\Facades\Http;

class GitHubProvider implements GitProviderInterface
{
    private string $baseUrl = 'https://api.github.com';

    public function __construct(
        private string $repository,
        private string $token,
        private string $branch = 'main',
    ) {}

    /**
     * List files in a directory (non-recursive, files only).
     *
     * @return array<FileContent>
     */
    public function listFiles(string $path): array
    {
        $response = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/contents/{$path}", [
                'ref' => $this->branch,
            ]);

        if ($response->status() === 404) {
            return [];
        }

        $response->throw();

        $files = [];
        foreach ($response->json() as $item) {
            if ($item['type'] === 'file' && str_ends_with($item['name'], '.yaml')) {
                $files[] = new FileContent(
                    path: $item['path'],
                    content: '',
                    sha: $item['sha'],
                );
            }
        }

        return $files;
    }

    /**
     * List all items in a directory recursively (files and subdirectories).
     * Uses GitHub's Git Trees API for efficient recursive listing.
     *
     * @return array<array{type: string, path: string, sha: string}>
     */
    public function listDirectoryRecursive(string $path): array
    {
        // First, get the tree SHA for the branch
        $refResponse = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/git/ref/heads/{$this->branch}");

        if (! $refResponse->successful()) {
            return [];
        }

        $commitSha = $refResponse->json('object.sha');

        // Get the commit to find the tree SHA
        $commitResponse = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/git/commits/{$commitSha}");

        if (! $commitResponse->successful()) {
            return [];
        }

        $treeSha = $commitResponse->json('tree.sha');

        // Get the full tree recursively
        $treeResponse = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/git/trees/{$treeSha}", [
                'recursive' => '1',
            ]);

        if (! $treeResponse->successful()) {
            return [];
        }

        $items = [];
        $pathPrefix = $path ? $path.'/' : '';

        foreach ($treeResponse->json('tree') ?? [] as $item) {
            // Only include items under the specified path
            if ($pathPrefix && ! str_starts_with($item['path'], $pathPrefix)) {
                continue;
            }

            // Skip items that are exactly the path (not children)
            if ($item['path'] === $path) {
                continue;
            }

            $items[] = [
                'type' => $item['type'] === 'tree' ? 'dir' : 'file',
                'path' => $item['path'],
                'sha' => $item['sha'],
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
        $response = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/contents/{$path}", [
                'ref' => $this->branch,
            ]);

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $data = $response->json();

        return new FileContent(
            path: $data['path'],
            content: base64_decode($data['content']),
            sha: $data['sha'],
        );
    }

    public function createFile(string $path, string $content, string $commitMessage): string
    {
        $response = $this->request()
            ->put("{$this->baseUrl}/repos/{$this->repository}/contents/{$path}", [
                'message' => $commitMessage,
                'content' => base64_encode($content),
                'branch' => $this->branch,
            ]);

        $response->throw();

        return $response->json('content.sha');
    }

    public function updateFile(string $path, string $content, string $sha, string $commitMessage): string
    {
        $response = $this->request()
            ->put("{$this->baseUrl}/repos/{$this->repository}/contents/{$path}", [
                'message' => $commitMessage,
                'content' => base64_encode($content),
                'sha' => $sha,
                'branch' => $this->branch,
            ]);

        $response->throw();

        return $response->json('content.sha');
    }

    public function deleteFile(string $path, string $sha, string $commitMessage): void
    {
        $response = $this->request()
            ->delete("{$this->baseUrl}/repos/{$this->repository}/contents/{$path}", [
                'message' => $commitMessage,
                'sha' => $sha,
                'branch' => $this->branch,
            ]);

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    /**
     * Delete an entire directory and all its contents.
     * Uses the Git Data API to create a commit that removes the tree.
     */
    public function deleteDirectory(string $path, string $commitMessage): void
    {
        // Get all files in the directory
        $items = $this->listDirectoryRecursive($path);

        if (empty($items)) {
            return;
        }

        // Delete files one by one (GitHub API doesn't support batch deletes via Contents API)
        // We need to delete in reverse order (deepest first) to avoid issues
        $files = array_filter($items, fn ($item) => $item['type'] === 'file');

        foreach ($files as $file) {
            $fileContent = $this->getFile($file['path']);
            if ($fileContent) {
                $this->deleteFile($file['path'], $fileContent->sha, $commitMessage);
            }
        }
    }

    /**
     * Commit multiple files in a single atomic commit using Git Data API.
     * Uses inline content in tree entries to minimize API calls.
     *
     * @param  array<string, string>  $files  Map of path => content
     * @return string The new commit SHA
     */
    public function commitMultipleFiles(array $files, string $commitMessage, array $deletePaths = []): string
    {
        // 1. Get the current commit SHA for the branch
        $refResponse = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/git/ref/heads/{$this->branch}");
        $refResponse->throw();
        $currentCommitSha = $refResponse->json('object.sha');

        // 2. Get the tree SHA from the current commit
        $commitResponse = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}/git/commits/{$currentCommitSha}");
        $commitResponse->throw();
        $baseTreeSha = $commitResponse->json('tree.sha');

        // 3. Build tree entries with inline content (no separate blob creation needed)
        $treeEntries = [];
        foreach ($files as $path => $content) {
            $treeEntries[] = [
                'path' => $path,
                'mode' => '100644', // Regular file
                'type' => 'blob',
                'content' => $content, // Inline content - GitHub creates blob automatically
            ];
        }

        // 4. Add deletion entries (null sha = remove file from tree)
        foreach ($deletePaths as $path) {
            $treeEntries[] = [
                'path' => $path,
                'mode' => '100644',
                'type' => 'blob',
                'sha' => null,
            ];
        }

        // 5. Create new tree with all files at once
        $treeResponse = $this->request()
            ->post("{$this->baseUrl}/repos/{$this->repository}/git/trees", [
                'base_tree' => $baseTreeSha,
                'tree' => $treeEntries,
            ]);
        $treeResponse->throw();
        $newTreeSha = $treeResponse->json('sha');

        // 6. Create new commit
        $newCommitResponse = $this->request()
            ->post("{$this->baseUrl}/repos/{$this->repository}/git/commits", [
                'message' => $commitMessage,
                'tree' => $newTreeSha,
                'parents' => [$currentCommitSha],
            ]);
        $newCommitResponse->throw();
        $newCommitSha = $newCommitResponse->json('sha');

        // 7. Update the branch reference
        $updateRefResponse = $this->request()
            ->patch("{$this->baseUrl}/repos/{$this->repository}/git/refs/heads/{$this->branch}", [
                'sha' => $newCommitSha,
            ]);
        $updateRefResponse->throw();

        return $newCommitSha;
    }

    public function testConnection(): bool
    {
        $response = $this->request()
            ->get("{$this->baseUrl}/repos/{$this->repository}");

        return $response->successful();
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->timeout(30);
    }
}
