<?php

namespace App\Services;

use App\Contracts\GitProviderInterface;
use App\DataTransferObjects\FileContent;
use App\DataTransferObjects\SyncResult;
use App\Enums\GitProvider;
use App\Exceptions\SyncConflictException;
use App\Models\Collection;
use App\Models\Request;
use App\Services\GitProviders\GitHubProvider;
use App\Services\GitProviders\GitLabProvider;

class RemoteSyncService
{
    private const COLLECTIONS_PATH = 'collections';

    private ?GitProviderInterface $provider = null;

    public function getProvider(): ?GitProviderInterface
    {
        if ($this->provider) {
            return $this->provider;
        }

        $ws = app(WorkspaceService::class);
        $providerType = $ws->getSetting('remote.provider');
        $repository = $ws->getSetting('remote.repository');
        $token = $ws->getSetting('remote.token');
        $branch = $ws->getSetting('remote.branch', 'main');

        if (! $providerType || ! $repository || ! $token) {
            return null;
        }

        $this->provider = match ($providerType) {
            GitProvider::GitHub->value => new GitHubProvider($repository, $token, $branch),
            GitProvider::GitLab->value => new GitLabProvider($repository, $token, $branch),
            default => null,
        };

        return $this->provider;
    }

    public function isConfigured(): bool
    {
        return $this->getProvider() !== null;
    }

    public function testConnection(): bool
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return false;
        }

        return $provider->testConnection();
    }

    /**
     * Build consistent file state from remote FileContent objects.
     *
     * @param  array<FileContent>  $files
     * @return array<string, array{content_hash: string, remote_sha: string, commit_sha: string|null}>
     */
    public function buildFileStateFromRemote(array $files): array
    {
        $state = [];
        foreach ($files as $file) {
            $state[$file->path] = [
                'content_hash' => hash('sha256', $file->content),
                'remote_sha' => $file->sha,
                'commit_sha' => $file->commitSha,
            ];
        }

        return $state;
    }

    /**
     * Normalize file state for backward compatibility.
     * Converts old flat-string format (path => sha_string) to array format.
     *
     * @param  array<string, mixed>  $fileState
     * @return array<string, array{content_hash: string|null, remote_sha: string|null, commit_sha: string|null}>
     */
    public function normalizeFileState(array $fileState): array
    {
        $normalized = [];
        foreach ($fileState as $path => $value) {
            if (is_string($value)) {
                // Old format: path => sha_string (from pre-fix pulls)
                $normalized[$path] = [
                    'content_hash' => null,
                    'remote_sha' => $value,
                    'commit_sha' => null,
                ];
            } elseif (is_array($value)) {
                // New format: already an array
                $normalized[$path] = [
                    'content_hash' => $value['content_hash'] ?? null,
                    'remote_sha' => $value['remote_sha'] ?? null,
                    'commit_sha' => $value['commit_sha'] ?? null,
                ];
            }
        }

        return $normalized;
    }

    /**
     * Check if remote has any file changes compared to stored state.
     *
     * @param  array<string, mixed>  $storedFileShas
     * @param  array<array{type: string, path: string, sha: string}>  $remoteItems
     */
    public function hasRemoteFileChanges(array $storedFileShas, array $remoteItems): bool
    {
        $normalized = $this->normalizeFileState($storedFileShas);

        $remoteShas = [];
        foreach ($remoteItems as $item) {
            if (($item['type'] ?? null) === 'file') {
                $remoteShas[$item['path']] = $item['sha'];
            }
        }

        // Check for new or changed files on remote
        foreach ($remoteShas as $path => $sha) {
            $stored = $normalized[$path] ?? null;
            if (! $stored || $stored['remote_sha'] !== $sha) {
                return true;
            }
        }

        // Check for files deleted on remote
        foreach ($normalized as $path => $state) {
            if (! isset($remoteShas[$path])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pull collections from remote.
     */
    public function pull(): SyncResult
    {
        $result = new SyncResult;
        $provider = $this->getProvider();

        if (! $provider) {
            $result->errors[] = 'Remote not configured';

            return $result;
        }

        try {
            // List all collection directories
            $items = $provider->listDirectoryRecursive(self::COLLECTIONS_PATH);
        } catch (\Exception $e) {
            $result->errors[] = 'Failed to list remote directories: '.$e->getMessage();

            return $result;
        }

        // Find all collection directories (directories with _collection.yaml)
        $collectionDirs = [];
        foreach ($items as $item) {
            if ($item['type'] === 'file' && str_ends_with($item['path'], '/_collection.yaml')) {
                $dirPath = dirname($item['path']);
                $collectionId = basename($dirPath);
                $collectionDirs[$collectionId] = $dirPath;
            }
        }

        $serializer = app(YamlCollectionSerializer::class);
        $processedCollectionIds = [];
        $logService = app(SessionLogService::class);

        foreach ($collectionDirs as $collectionId => $dirPath) {
            try {
                // Skip if we've already processed this collection ID
                if (in_array($collectionId, $processedCollectionIds)) {
                    continue;
                }
                $processedCollectionIds[] = $collectionId;

                // Get all files in this collection directory
                $files = $provider->getDirectoryTree($dirPath);
                if (empty($files)) {
                    continue;
                }

                // Find _collection.yaml in the file tree
                $collectionFile = null;
                foreach ($files as $file) {
                    if (str_ends_with($file->path, '/_collection.yaml')) {
                        $collectionFile = $file;
                        break;
                    }
                }
                if (! $collectionFile) {
                    continue;
                }

                $localCollection = Collection::find($collectionId);

                if (! $localCollection) {
                    // New collection from remote
                    $collection = $serializer->importFromDirectory($files);

                    $collection->update([
                        'remote_sha' => $collectionFile->sha,
                        'file_shas' => $this->buildFileStateFromRemote($files),
                        'remote_synced_at' => now(),
                        'sync_enabled' => true,
                        'is_dirty' => false,
                    ]);
                    $result->pulled++;
                    $logService->logGitOperation('pull', $collection->name, 'New collection imported from remote');
                } else {
                    // Check if ANY file changed on remote (not just _collection.yaml)
                    $remoteItems = array_map(
                        fn (FileContent $f) => ['type' => 'file', 'path' => $f->path, 'sha' => $f->sha],
                        $files,
                    );

                    if (! $this->hasRemoteFileChanges($localCollection->file_shas ?? [], $remoteItems)) {
                        // No changes on remote
                        continue;
                    }

                    if (! $localCollection->is_dirty) {
                        // Remote wins - update local
                        $serializer->importFromDirectory($files, $localCollection->id);

                        $localCollection->update([
                            'remote_sha' => $collectionFile->sha,
                            'file_shas' => $this->buildFileStateFromRemote($files),
                            'remote_synced_at' => now(),
                            'is_dirty' => false,
                        ]);
                        $result->pulled++;
                        $logService->logGitOperation('pull', $localCollection->name, 'Updated from remote');
                    } else {
                        // Conflict: both sides changed
                        $result->conflicts[] = [
                            'collection_id' => $localCollection->id,
                            'collection_name' => $localCollection->name,
                            'remote_sha' => $collectionFile->sha,
                            'remote_path' => $dirPath,
                        ];
                        $logService->logGitOperation('pull', $localCollection->name, 'Conflict detected - both sides changed', false);
                    }
                }
            } catch (\Exception $e) {
                $result->errors[] = "Error processing collection {$collectionId}: ".$e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Push a single collection to remote.
     * Uses 3-way merge: only detects conflict when same file modified by multiple users.
     *
     * @throws SyncConflictException if real conflicts detected
     */
    public function pushCollection(Collection $collection, bool $sanitize = false): void
    {
        $startTime = microtime(true);
        $debug = config('app.debug');

        if ($debug) {
            \Log::info('[SYNC] pushCollection START');
        }

        $provider = $this->getProvider();
        if (! $provider) {
            throw new \RuntimeException('Remote not configured');
        }

        if ($debug) {
            \Log::info('[SYNC] getProvider: '.round((microtime(true) - $startTime) * 1000).'ms');
        }

        $serializer = app(YamlCollectionSerializer::class);
        if ($sanitize) {
            $serializer = $serializer->withSanitizer(new SensitiveDataScanner);
        }
        $localFiles = $serializer->serializeToDirectory($collection);

        if ($debug) {
            \Log::info('[SYNC] serialize: '.round((microtime(true) - $startTime) * 1000).'ms, files='.count($localFiles));
        }

        $basePath = self::COLLECTIONS_PATH.'/'.$collection->id;

        // Get base state (content hashes + remote SHAs from last sync)
        // Normalize for backward compat with old flat-string format
        $baseState = $this->normalizeFileState($collection->file_shas ?? []);

        // Get current remote state
        $remoteItems = $provider->listDirectoryRecursive($basePath);
        $remoteShas = [];
        foreach ($remoteItems as $item) {
            if ($item['type'] === 'file') {
                $remoteShas[$item['path']] = $item['sha'];
            }
        }

        if ($debug) {
            \Log::info('[SYNC] listDirectoryRecursive: '.round((microtime(true) - $startTime) * 1000).'ms');
        }

        // 3-way merge: detect conflicts per file
        $conflicts = [];
        $filesToPush = [];

        foreach ($localFiles as $relativePath => $content) {
            $fullPath = self::COLLECTIONS_PATH.'/'.$relativePath;
            $localContentHash = hash('sha256', $content);

            // Get base state for this file
            $baseInfo = $baseState[$fullPath] ?? null;
            $baseContentHash = $baseInfo['content_hash'] ?? null;
            $baseRemoteSha = $baseInfo['remote_sha'] ?? null;

            // Current remote SHA
            $remoteSha = $remoteShas[$fullPath] ?? null;

            // Check if local changed from base (compare content hashes)
            $localChanged = $baseContentHash === null || $localContentHash !== $baseContentHash;

            // Check if remote changed from what we knew (compare remote SHAs)
            $remoteChanged = $baseRemoteSha !== null && $remoteSha !== null && $remoteSha !== $baseRemoteSha;

            if ($debug) {
                \Log::debug("[SYNC] File: {$fullPath}, localChanged: ".($localChanged ? 'yes' : 'no').', remoteChanged: '.($remoteChanged ? 'yes' : 'no'));
            }

            if ($remoteChanged && $localChanged) {
                // Both changed the same file = CONFLICT
                $conflicts[] = $fullPath;
            } elseif ($localChanged) {
                // Only we changed = safe to push
                $filesToPush[$fullPath] = $content;
            }
            // If only remote changed or neither changed, we accept remote (no push needed)
        }

        if (! empty($conflicts)) {
            \Log::warning('[SYNC] Conflicts detected: '.implode(', ', $conflicts));

            throw new SyncConflictException($conflicts);
        }

        // Detect orphaned files: exist on remote/baseState but not in local files anymore
        $localFullPaths = [];
        foreach ($localFiles as $relativePath => $content) {
            $localFullPaths[self::COLLECTIONS_PATH.'/'.$relativePath] = true;
        }

        $filesToDelete = [];
        foreach ($remoteShas as $remotePath => $remoteSha) {
            if (! isset($localFullPaths[$remotePath])) {
                $filesToDelete[] = $remotePath;
            }
        }

        if ($debug) {
            \Log::info('[SYNC] conflict check done: '.round((microtime(true) - $startTime) * 1000).'ms, pushing '.count($filesToPush).' files, deleting '.count($filesToDelete).' files');
        }

        if (empty($filesToPush) && empty($filesToDelete)) {
            if ($debug) {
                \Log::info('[SYNC] No files to push or delete, already in sync');
            }
            $collection->update(['is_dirty' => false]);

            return;
        }

        // Commit changed and deleted files in a single atomic commit
        $provider->commitMultipleFiles($filesToPush, "Sync: {$collection->name}", $filesToDelete);

        if ($debug) {
            \Log::info('[SYNC] commitMultipleFiles: '.round((microtime(true) - $startTime) * 1000).'ms');
        }

        // Build new file state locally (no API call needed)
        // Start fresh from local files only — don't inherit orphaned entries from baseState
        $newFileState = [];

        foreach ($localFiles as $relativePath => $content) {
            $fullPath = self::COLLECTIONS_PATH.'/'.$relativePath;
            $contentHash = hash('sha256', $content);
            // Compute blob SHA: SHA-1 of "blob {size}\0{content}"
            $blobSha = sha1('blob '.strlen($content)."\0".$content);

            $newFileState[$fullPath] = [
                'content_hash' => $contentHash,
                'remote_sha' => $blobSha,
                'commit_sha' => null,
            ];
        }

        if ($debug) {
            \Log::info('[SYNC] compute local state: '.round((microtime(true) - $startTime) * 1000).'ms');
        }

        // Store _collection.yaml blob SHA as remote_sha (not the commit SHA)
        $collectionYamlPath = $basePath.'/_collection.yaml';
        $collectionRemoteSha = $newFileState[$collectionYamlPath]['remote_sha'] ?? null;

        $collection->update([
            'remote_sha' => $collectionRemoteSha,
            'file_shas' => $newFileState,
            'remote_synced_at' => now(),
            'is_dirty' => false,
        ]);

        if ($debug) {
            \Log::info('[SYNC] update collection: '.round((microtime(true) - $startTime) * 1000).'ms');
        }

        app(SessionLogService::class)->logGitOperation('push', $collection->name, 'Pushed to remote successfully');

        if ($debug) {
            \Log::info('[SYNC] pushCollection END: '.round((microtime(true) - $startTime) * 1000).'ms TOTAL');
        }
    }

    /**
     * Pull a single collection from remote.
     * Returns true if pulled successfully, false if no changes or not found.
     *
     * @throws SyncConflictException if local is dirty and remote has changed
     */
    public function pullSingleCollection(Collection $collection): bool
    {
        $provider = $this->getProvider();
        if (! $provider) {
            throw new \RuntimeException('Remote not configured');
        }

        $basePath = self::COLLECTIONS_PATH.'/'.$collection->id;

        // Check ALL remote files for changes (not just _collection.yaml)
        $remoteItems = $provider->listDirectoryRecursive($basePath);
        if (empty($remoteItems)) {
            return false;
        }

        if (! $this->hasRemoteFileChanges($collection->file_shas ?? [], $remoteItems)) {
            return false;
        }

        // Remote has changes - check if local is dirty
        if ($collection->is_dirty) {
            throw new SyncConflictException(
                [$basePath],
                "Conflict: local changes exist for '{$collection->name}' and remote has been updated",
            );
        }

        // Fetch full file contents for import
        $files = $provider->getDirectoryTree($basePath);
        if (empty($files)) {
            throw new \RuntimeException('Remote directory is empty');
        }

        $serializer = app(YamlCollectionSerializer::class);
        $serializer->importFromDirectory($files, $collection->id);

        // Find _collection.yaml SHA for remote_sha
        $collectionFileSha = null;
        foreach ($files as $file) {
            if (str_ends_with($file->path, '/_collection.yaml')) {
                $collectionFileSha = $file->sha;
                break;
            }
        }

        $collection->update([
            'remote_sha' => $collectionFileSha,
            'file_shas' => $this->buildFileStateFromRemote($files),
            'remote_synced_at' => now(),
            'is_dirty' => false,
        ]);

        app(SessionLogService::class)->logGitOperation('pull', $collection->name, 'Pulled from remote successfully');

        return true;
    }

    /**
     * Get conflict info for a collection (current remote SHA and path).
     */
    public function getConflictInfo(Collection $collection): array
    {
        $provider = $this->getProvider();
        if (! $provider) {
            throw new \RuntimeException('Remote not configured');
        }

        $basePath = self::COLLECTIONS_PATH.'/'.$collection->id;
        $collectionFilePath = $basePath.'/_collection.yaml';

        $file = $provider->getFile($collectionFilePath);

        return [
            'path' => $basePath,
            'sha' => $file?->sha,
        ];
    }

    /**
     * Push all sync-enabled collections that are dirty or never pushed.
     */
    public function pushAll(): SyncResult
    {
        $result = new SyncResult;
        $workspaceId = app(WorkspaceService::class)->activeId();
        $collections = Collection::syncEnabled()
            ->forWorkspace($workspaceId)
            ->where(function ($query) {
                $query->where('is_dirty', true)
                    ->orWhereNull('remote_sha');
            })
            ->get();

        foreach ($collections as $collection) {
            try {
                $this->pushCollection($collection);
                $result->pushed++;
            } catch (\Exception $e) {
                if ($this->isShaConflict($e)) {
                    $result->conflicts[] = [
                        'collection_id' => $collection->id,
                        'collection_name' => $collection->name,
                    ];
                } else {
                    $result->errors[] = "Failed to push '{$collection->name}': ".$e->getMessage();
                }
            }
        }

        return $result;
    }

    /**
     * Delete a collection from remote.
     */
    public function deleteRemoteCollection(Collection $collection): void
    {
        if (! $collection->remote_sha) {
            return;
        }

        $provider = $this->getProvider();
        if (! $provider) {
            return;
        }

        $basePath = self::COLLECTIONS_PATH.'/'.$collection->id;

        try {
            $provider->deleteDirectory($basePath, "Delete collection: {$collection->name}");
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Force push local version to remote (resolve conflict by keeping local).
     */
    public function forceKeepLocal(Collection $collection, ?string $remoteSha = null): void
    {
        $provider = $this->getProvider();
        if (! $provider) {
            throw new \RuntimeException('Remote not configured');
        }

        $serializer = app(YamlCollectionSerializer::class);
        $files = $serializer->serializeToDirectory($collection);
        $basePath = self::COLLECTIONS_PATH.'/'.$collection->id;

        // Prepare files with full paths
        $filesWithFullPath = [];
        foreach ($files as $relativePath => $content) {
            $filesWithFullPath[self::COLLECTIONS_PATH.'/'.$relativePath] = $content;
        }

        // Detect orphaned remote files to delete
        $remoteItems = $provider->listDirectoryRecursive($basePath);
        $filesToDelete = [];
        foreach ($remoteItems as $item) {
            if ($item['type'] === 'file' && ! isset($filesWithFullPath[$item['path']])) {
                $filesToDelete[] = $item['path'];
            }
        }

        // Commit all files and deletions in a single atomic operation
        $provider->commitMultipleFiles($filesWithFullPath, "Force sync (keep local): {$collection->name}", $filesToDelete);

        // Build file state locally instead of extra API call
        $newFileState = [];
        foreach ($filesWithFullPath as $fullPath => $content) {
            $newFileState[$fullPath] = [
                'content_hash' => hash('sha256', $content),
                'remote_sha' => sha1('blob '.strlen($content)."\0".$content),
                'commit_sha' => null,
            ];
        }

        // Store _collection.yaml blob SHA as remote_sha
        $collectionYamlPath = $basePath.'/_collection.yaml';
        $collectionRemoteSha = $newFileState[$collectionYamlPath]['remote_sha'] ?? null;

        $collection->update([
            'remote_sha' => $collectionRemoteSha,
            'file_shas' => $newFileState,
            'remote_synced_at' => now(),
            'is_dirty' => false,
        ]);
    }

    /**
     * Force pull remote version to local (resolve conflict by keeping remote).
     */
    public function forceKeepRemote(Collection $collection, string $remotePath, string $remoteSha): void
    {
        $provider = $this->getProvider();
        if (! $provider) {
            throw new \RuntimeException('Remote not configured');
        }

        $files = $provider->getDirectoryTree($remotePath);
        if (empty($files)) {
            throw new \RuntimeException('Remote directory not found or empty');
        }

        $serializer = app(YamlCollectionSerializer::class);
        $serializer->importFromDirectory($files, $collection->id);

        $collection->update([
            'remote_sha' => $remoteSha,
            'file_shas' => $this->buildFileStateFromRemote($files),
            'remote_synced_at' => now(),
            'is_dirty' => false,
        ]);
    }

    /**
     * Push a single request file to remote (granular sync).
     * Returns true if pushed successfully, false on failure (marks dirty silently).
     */
    public function pushSingleRequest(Collection $collection, Request $request, bool $sanitize = false): bool
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return false;
        }

        try {
            $serializer = app(YamlCollectionSerializer::class);
            if ($sanitize) {
                $serializer = $serializer->withSanitizer(new SensitiveDataScanner);
            }
            $content = $serializer->serializeRequest($request);

            $filePath = self::COLLECTIONS_PATH.'/'.$collection->id.'/'.$this->buildFolderPath($request).$request->id.'.yaml';

            // Get current file state
            $fileState = $this->normalizeFileState($collection->file_shas ?? []);
            $existingState = $fileState[$filePath] ?? null;
            $remoteSha = $existingState['remote_sha'] ?? null;
            $commitSha = $existingState['commit_sha'] ?? null;

            if ($remoteSha) {
                // File exists - update using the appropriate SHA for the provider
                // GitLab needs commit_sha, GitHub needs blob SHA (remote_sha)
                $shaForUpdate = $commitSha ?? $remoteSha;
                $newSha = $provider->updateFile($filePath, $content, $shaForUpdate, "Update: {$request->name}");
            } else {
                // New file
                $newSha = $provider->createFile($filePath, $content, "Create: {$request->name}");
            }

            // Update only this file's entry in file_shas
            $fileState[$filePath] = [
                'content_hash' => hash('sha256', $content),
                'remote_sha' => $newSha,
                'commit_sha' => null,
            ];

            $collection->update([
                'file_shas' => $fileState,
                'remote_synced_at' => now(),
                'is_dirty' => false,
            ]);

            app(SessionLogService::class)->logGitOperation('push', $collection->name, "Pushed request '{$request->name}'");

            return true;
        } catch (\Exception $e) {
            $statusCode = 0;
            if (method_exists($e, 'getCode')) {
                $statusCode = $e->getCode();
            }
            $message = strtolower($e->getMessage());

            // Treat 409 (GitHub) and 400 (GitLab) as conflicts - mark dirty for full push later
            if ($statusCode === 409 || $statusCode === 400 || str_contains($message, '409') || str_contains($message, '400')) {
                if (config('app.debug')) {
                    \Log::info("[SYNC] Single-file push conflict for {$request->name}, marking collection dirty");
                }
            } else {
                \Log::warning("[SYNC] Single-file push failed for {$request->name}: {$e->getMessage()}");
            }

            if (! $collection->is_dirty) {
                $collection->update(['is_dirty' => true]);
            }

            return false;
        }
    }

    /**
     * Build the folder path prefix for a request within its collection.
     */
    private function buildFolderPath(Request $request): string
    {
        if (! $request->folder_id) {
            return '';
        }

        $path = '';
        $folder = $request->folder;
        $segments = [];

        while ($folder) {
            $segments[] = $folder->id;
            $folder = $folder->parent;
        }

        foreach (array_reverse($segments) as $segment) {
            $path .= $segment.'/';
        }

        return $path;
    }

    public function isShaConflict(\Exception $e): bool
    {
        return $e instanceof SyncConflictException;
    }
}
