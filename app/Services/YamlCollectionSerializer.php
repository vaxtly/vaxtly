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
        $files[$basePath.'/'.self::COLLECTION_FILE] = $this->toYaml([
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'variables' => $collection->variables ?? [],
            'environment_ids' => $collection->getEnvironmentIds(),
            'default_environment_id' => $collection->default_environment_id,
        ]);

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
    private function serializeFolderRecursive(Folder $folder, string $parentPath, array &$files): void
    {
        $folderPath = $parentPath.'/'.$folder->id;

        // Folder metadata
        $files[$folderPath.'/'.self::FOLDER_FILE] = $this->toYaml([
            'id' => $folder->id,
            'name' => $folder->name,
        ]);

        // Folder manifest
        $manifest = $this->buildManifest($folder->children, $folder->requests);
        $files[$folderPath.'/'.self::MANIFEST_FILE] = $this->toYaml(['items' => $manifest]);

        // Requests in this folder
        foreach ($folder->requests as $request) {
            $files[$folderPath.'/'.$request->id.'.yaml'] = $this->serializeRequest($request);
        }

        // Nested folders
        foreach ($folder->children as $childFolder) {
            $this->serializeFolderRecursive($childFolder, $folderPath, $files);
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
        int $order
    ): void {
        $folderFilePath = $folderBasePath.'/'.self::FOLDER_FILE;

        if (! isset($filesByPath[$folderFilePath])) {
            return;
        }

        $folderData = Yaml::parse($filesByPath[$folderFilePath]);

        $folder = Folder::create([
            'id' => $folderData['id'],
            'collection_id' => $collection->id,
            'parent_id' => $parentId,
            'name' => $folderData['name'],
            'order' => $order,
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
                    $childOrder++
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
     * Validate environment IDs from remote data, discarding any that don't exist locally.
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

        $existingIds = Environment::whereIn('id', $remoteIds)->pluck('id')->all();

        return [
            'environment_ids' => $existingIds ?: null,
            'default_environment_id' => in_array($defaultId, $existingIds) ? $defaultId : null,
        ];
    }
}
