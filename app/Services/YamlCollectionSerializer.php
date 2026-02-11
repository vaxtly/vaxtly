<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Yaml\Yaml;

class YamlCollectionSerializer
{
    private const COLLECTION_FILE = '_collection.yaml';

    private const FOLDER_FILE = '_folder.yaml';

    private const MANIFEST_FILE = '_manifest.yaml';

    protected ?SensitiveDataScanner $sanitizer = null;

    /**
     * Return a clone with the sanitizer set.
     */
    public function withSanitizer(SensitiveDataScanner $sanitizer): static
    {
        $clone = clone $this;
        $clone->sanitizer = $sanitizer;

        return $clone;
    }

    /**
     * Serialize a collection to a directory structure.
     * Returns an array of path => content pairs.
     *
     * @return array<string, string>
     */
    public function serializeToDirectory(Collection $collection): array
    {
        $collection->load([
            'rootFolders.children.children.requests',
            'rootFolders.children.requests',
            'rootFolders.requests',
            'rootRequests',
        ]);

        $files = [];
        $basePath = $collection->id;

        // Collection metadata
        $environmentIds = $collection->getEnvironmentIds();
        $collectionData = [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'variables' => $collection->variables ?? [],
            'environment_ids' => $environmentIds,
            'default_environment_id' => $collection->default_environment_id,
        ];

        $hints = $this->buildEnvironmentHints($environmentIds);
        if (! empty($hints)) {
            $collectionData['environment_hints'] = $hints;
        }

        if ($this->sanitizer) {
            $collectionData = $this->sanitizer->sanitizeCollectionData($collectionData);
        }

        $files[$basePath.'/'.self::COLLECTION_FILE] = $this->toYaml($collectionData);

        // Root level manifest
        $rootManifest = $this->buildManifest(
            $collection->rootFolders,
            $collection->rootRequests
        );
        $files[$basePath.'/'.self::MANIFEST_FILE] = $this->toYaml(['items' => $rootManifest]);

        // Root level requests
        foreach ($collection->rootRequests as $request) {
            $files[$basePath.'/'.$request->id.'.yaml'] = $this->serializeRequest($request);
        }

        // Folders and their contents (recursive)
        foreach ($collection->rootFolders as $folder) {
            $this->serializeFolderRecursive($folder, $basePath, $files);
        }

        return $files;
    }

    /**
     * Recursively serialize a folder and its contents.
     *
     * @param  array<string, string>  $files
     */
    private function serializeFolderRecursive(Folder $folder, string $parentPath, array &$files, int $depth = 0): void
    {
        if ($depth > 20) {
            throw new \RuntimeException('Folder nesting depth exceeded maximum of 20 levels');
        }

        $folderPath = $parentPath.'/'.$folder->id;

        // Folder metadata
        $folderMeta = [
            'id' => $folder->id,
            'name' => $folder->name,
        ];
        $folderEnvIds = $folder->getEnvironmentIds();
        if (! empty($folderEnvIds)) {
            $folderMeta['environment_ids'] = $folderEnvIds;
            $folderMeta['default_environment_id'] = $folder->default_environment_id;

            $folderHints = $this->buildEnvironmentHints($folderEnvIds);
            if (! empty($folderHints)) {
                $folderMeta['environment_hints'] = $folderHints;
            }
        }
        $files[$folderPath.'/'.self::FOLDER_FILE] = $this->toYaml($folderMeta);

        // Folder manifest
        $manifest = $this->buildManifest($folder->children, $folder->requests);
        $files[$folderPath.'/'.self::MANIFEST_FILE] = $this->toYaml(['items' => $manifest]);

        // Requests in this folder
        foreach ($folder->requests as $request) {
            $files[$folderPath.'/'.$request->id.'.yaml'] = $this->serializeRequest($request);
        }

        // Nested folders
        foreach ($folder->children as $childFolder) {
            $this->serializeFolderRecursive($childFolder, $folderPath, $files, $depth + 1);
        }
    }

    /**
     * Build a manifest array for ordering.
     *
     * @return array<array{type: string, id: string}>
     */
    private function buildManifest($folders, $requests): array
    {
        $items = [];

        // Merge folders and requests, then sort by order
        $allItems = [];

        foreach ($folders as $folder) {
            $allItems[] = ['type' => 'folder', 'id' => $folder->id, 'order' => $folder->order];
        }

        foreach ($requests as $request) {
            $allItems[] = ['type' => 'request', 'id' => $request->id, 'order' => $request->order];
        }

        // Sort by order
        usort($allItems, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        // Remove order from output
        foreach ($allItems as $item) {
            $items[] = [
                'type' => $item['type'],
                'id' => $item['id'],
            ];
        }

        return $items;
    }

    /**
     * Serialize a request to YAML content.
     */
    public function serializeRequest(Request $request): string
    {
        $data = [
            'id' => $request->id,
            'name' => $request->name,
            'method' => $request->method,
            'url' => $request->url,
            'headers' => $request->headers ?? [],
            'query_params' => $request->query_params ?? [],
            'body' => $request->body,
            'body_type' => $request->body_type,
        ];

        if ($request->scripts) {
            $data['scripts'] = $request->scripts;
        }

        if ($request->auth) {
            $data['auth'] = $request->auth;
        }

        if ($this->sanitizer) {
            $data = $this->sanitizer->sanitizeRequestData($data);
        }

        return $this->toYaml($data);
    }

    /**
     * Convert array to YAML string.
     */
    private function toYaml(array $data): string
    {
        return Yaml::dump($data, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }

    /**
     * Import a collection from directory file contents.
     *
     * @param  array<\App\DataTransferObjects\FileContent>  $files
     */
    public function importFromDirectory(array $files, ?string $existingCollectionId = null): Collection
    {
        return DB::transaction(function () use ($files, $existingCollectionId) {
            // Organize files by path
            $filesByPath = [];
            foreach ($files as $file) {
                $filesByPath[$file->path] = $file->content;
            }

            // Find the collection file to get the base path and collection data
            $collectionFile = null;
            $basePath = null;

            foreach ($filesByPath as $path => $content) {
                if (str_ends_with($path, '/'.self::COLLECTION_FILE)) {
                    $collectionFile = $content;
                    $basePath = dirname($path);
                    break;
                }
            }

            if (! $collectionFile || ! $basePath) {
                throw new \RuntimeException('Collection file not found');
            }

            $collectionData = Yaml::parse($collectionFile);
            $environmentFields = $this->validateEnvironmentIds($collectionData);

            // Create or update collection
            if ($existingCollectionId) {
                $collection = Collection::findOrFail($existingCollectionId);
                $collection->update([
                    'name' => $collectionData['name'],
                    'description' => $collectionData['description'] ?? null,
                    'variables' => $collectionData['variables'] ?? [],
                    ...$environmentFields,
                ]);

                // Remove existing folders and requests, then re-create
                $collection->folders()->delete();
                $collection->requests()->delete();
            } else {
                $collection = Collection::create([
                    'id' => $collectionData['id'],
                    'name' => $collectionData['name'],
                    'description' => $collectionData['description'] ?? null,
                    'variables' => $collectionData['variables'] ?? [],
                    ...$environmentFields,
                    'order' => Collection::max('order') + 1,
                    'sync_enabled' => true,
                    'workspace_id' => app(WorkspaceService::class)->activeId(),
                ]);
            }

            // Parse root manifest
            $rootManifestPath = $basePath.'/'.self::MANIFEST_FILE;
            $rootManifest = isset($filesByPath[$rootManifestPath])
                ? Yaml::parse($filesByPath[$rootManifestPath])
                : ['items' => []];

            // Import items based on manifest ordering
            $order = 0;
            foreach ($rootManifest['items'] ?? [] as $item) {
                if ($item['type'] === 'request') {
                    $requestPath = $basePath.'/'.$item['id'].'.yaml';
                    if (isset($filesByPath[$requestPath])) {
                        $this->importRequest(
                            Yaml::parse($filesByPath[$requestPath]),
                            $collection,
                            null,
                            $order++
                        );
                    }
                } elseif ($item['type'] === 'folder') {
                    $folderBasePath = $basePath.'/'.$item['id'];
                    $this->importFolderRecursive(
                        $folderBasePath,
                        $filesByPath,
                        $collection,
                        null,
                        $order++
                    );
                }
            }

            return $collection->fresh();
        });
    }

    /**
     * Recursively import a folder and its contents.
     *
     * @param  array<string, string>  $filesByPath
     */
    private function importFolderRecursive(
        string $folderBasePath,
        array $filesByPath,
        Collection $collection,
        ?string $parentId,
        int $order,
        int $depth = 0
    ): void {
        if ($depth > 20) {
            throw new \RuntimeException('Folder nesting depth exceeded maximum of 20 levels');
        }

        $folderFilePath = $folderBasePath.'/'.self::FOLDER_FILE;

        if (! isset($filesByPath[$folderFilePath])) {
            return;
        }

        $folderData = Yaml::parse($filesByPath[$folderFilePath]);
        $folderEnvFields = $this->validateEnvironmentIds($folderData);

        $folder = Folder::create([
            'id' => $folderData['id'],
            'collection_id' => $collection->id,
            'parent_id' => $parentId,
            'name' => $folderData['name'],
            'order' => $order,
            ...$folderEnvFields,
        ]);

        // Parse folder manifest
        $manifestPath = $folderBasePath.'/'.self::MANIFEST_FILE;
        $manifest = isset($filesByPath[$manifestPath])
            ? Yaml::parse($filesByPath[$manifestPath])
            : ['items' => []];

        // Import items based on manifest ordering
        $childOrder = 0;
        foreach ($manifest['items'] ?? [] as $item) {
            if ($item['type'] === 'request') {
                $requestPath = $folderBasePath.'/'.$item['id'].'.yaml';
                if (isset($filesByPath[$requestPath])) {
                    $this->importRequest(
                        Yaml::parse($filesByPath[$requestPath]),
                        $collection,
                        $folder->id,
                        $childOrder++
                    );
                }
            } elseif ($item['type'] === 'folder') {
                $childFolderPath = $folderBasePath.'/'.$item['id'];
                $this->importFolderRecursive(
                    $childFolderPath,
                    $filesByPath,
                    $collection,
                    $folder->id,
                    $childOrder++,
                    $depth + 1
                );
            }
        }
    }

    /**
     * Import a request from parsed YAML data.
     */
    private function importRequest(array $data, Collection $collection, ?string $folderId, int $order): void
    {
        Request::create([
            'id' => $data['id'],
            'collection_id' => $collection->id,
            'folder_id' => $folderId,
            'name' => $data['name'],
            'method' => $data['method'] ?? 'GET',
            'url' => $data['url'] ?? '',
            'headers' => $data['headers'] ?? [],
            'query_params' => $data['query_params'] ?? [],
            'body' => $data['body'] ?? '',
            'body_type' => $data['body_type'] ?? 'none',
            'scripts' => $data['scripts'] ?? null,
            'auth' => $data['auth'] ?? null,
            'order' => $order,
        ]);
    }

    /**
     * Build environment hints mapping vault-synced environment UUIDs to their vault_path.
     *
     * @param  array<int, string>  $environmentIds
     * @return array<string, array{vault_path: string}>
     */
    private function buildEnvironmentHints(array $environmentIds): array
    {
        if (empty($environmentIds)) {
            return [];
        }

        return Environment::whereIn('id', $environmentIds)
            ->where('vault_synced', true)
            ->get()
            ->mapWithKeys(fn (Environment $env) => [
                $env->id => ['vault_path' => $env->getVaultPath()],
            ])
            ->all();
    }

    /**
     * Validate environment IDs from remote data, discarding any that don't exist locally.
     * Falls back to vault_path hints to resolve IDs for vault-synced environments
     * that were created on a different machine.
     *
     * @return array{environment_ids: array<int, string>|null, default_environment_id: string|null}
     */
    private function validateEnvironmentIds(array $data): array
    {
        $remoteIds = $data['environment_ids'] ?? [];
        $defaultId = $data['default_environment_id'] ?? null;

        if (empty($remoteIds)) {
            return [
                'environment_ids' => null,
                'default_environment_id' => null,
            ];
        }

        // First pass: direct UUID lookup
        $existingIds = Environment::whereIn('id', $remoteIds)->pluck('id')->all();
        $unmatchedIds = array_diff($remoteIds, $existingIds);

        // Second pass: resolve unmatched IDs via vault_path hints
        $idMapping = []; // remote UUID => local UUID
        $hints = $data['environment_hints'] ?? [];

        if (! empty($unmatchedIds) && ! empty($hints)) {
            $hintPaths = [];
            foreach ($unmatchedIds as $remoteId) {
                if (isset($hints[$remoteId]['vault_path'])) {
                    $hintPaths[$hints[$remoteId]['vault_path']] = $remoteId;
                }
            }

            if (! empty($hintPaths)) {
                $workspaceId = app(WorkspaceService::class)->activeId();
                $localVaultEnvs = Environment::where('workspace_id', $workspaceId)
                    ->where('vault_synced', true)
                    ->get();

                foreach ($localVaultEnvs as $localEnv) {
                    $vaultPath = $localEnv->getVaultPath();
                    if (isset($hintPaths[$vaultPath])) {
                        $remoteId = $hintPaths[$vaultPath];
                        $idMapping[$remoteId] = $localEnv->id;
                        $existingIds[] = $localEnv->id;
                        unset($hintPaths[$vaultPath]);
                    }
                }
            }
        }

        // Resolve default_environment_id through the mapping
        $resolvedDefaultId = $defaultId;
        if ($defaultId && isset($idMapping[$defaultId])) {
            $resolvedDefaultId = $idMapping[$defaultId];
        }

        return [
            'environment_ids' => $existingIds ?: null,
            'default_environment_id' => in_array($resolvedDefaultId, $existingIds) ? $resolvedDefaultId : null,
        ];
    }
}
