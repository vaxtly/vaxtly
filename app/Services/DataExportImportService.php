<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use Illuminate\Support\Facades\DB;

class DataExportImportService
{
    /**
     * Export all workspace data: collections, environments, and config.
     *
     * @return array{vaxtly_export: true, version: int, type: string, exported_at: string, data: array}
     */
    public function exportAll(string $workspaceId): array
    {
        return $this->wrap('all', [
            'collections' => $this->buildCollectionsData($workspaceId),
            'environments' => $this->buildEnvironmentsData($workspaceId),
            'config' => $this->buildConfigData($workspaceId),
        ]);
    }

    /**
     * Export only collections with folders, requests, and variables.
     *
     * @return array{vaxtly_export: true, version: int, type: string, exported_at: string, data: array}
     */
    public function exportCollections(string $workspaceId): array
    {
        return $this->wrap('collections', [
            'collections' => $this->buildCollectionsData($workspaceId),
        ]);
    }

    /**
     * Export only environments with variables.
     *
     * @return array{vaxtly_export: true, version: int, type: string, exported_at: string, data: array}
     */
    public function exportEnvironments(string $workspaceId): array
    {
        return $this->wrap('environments', [
            'environments' => $this->buildEnvironmentsData($workspaceId),
        ]);
    }

    /**
     * Export workspace config (remote + vault settings, tokens masked).
     *
     * @return array{vaxtly_export: true, version: int, type: string, exported_at: string, data: array}
     */
    public function exportConfig(string $workspaceId): array
    {
        return $this->wrap('config', [
            'config' => $this->buildConfigData($workspaceId),
        ]);
    }

    /**
     * Import from a Vaxtly export JSON string.
     *
     * @return array{collections: int, environments: int, config: bool, errors: array<string>}
     */
    public function import(string $json, string $workspaceId): array
    {
        $result = ['collections' => 0, 'environments' => 0, 'config' => false, 'errors' => []];

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['errors'][] = 'Invalid JSON: '.json_last_error_msg();

            return $result;
        }

        if (empty($data['vaxtly_export']) || empty($data['version']) || ! array_key_exists('data', $data)) {
            $result['errors'][] = 'Invalid Vaxtly export format';

            return $result;
        }

        if ($data['version'] > 1) {
            $result['errors'][] = "Unsupported export version: {$data['version']}";

            return $result;
        }

        $exportData = $data['data'];
        $type = $data['type'] ?? 'all';

        if (in_array($type, ['all', 'collections']) && ! empty($exportData['collections'])) {
            $importResult = $this->importCollections($exportData['collections'], $workspaceId);
            $result['collections'] = $importResult['count'];
            $result['errors'] = array_merge($result['errors'], $importResult['errors']);
        }

        if (in_array($type, ['all', 'environments']) && ! empty($exportData['environments'])) {
            $importResult = $this->importEnvironments($exportData['environments'], $workspaceId);
            $result['environments'] = $importResult['count'];
            $result['errors'] = array_merge($result['errors'], $importResult['errors']);
        }

        if (in_array($type, ['all', 'config']) && ! empty($exportData['config'])) {
            $importResult = $this->importConfig($exportData['config'], $workspaceId);
            $result['config'] = $importResult['success'];
            $result['errors'] = array_merge($result['errors'], $importResult['errors']);
        }

        return $result;
    }

    /**
     * @return array{count: int, errors: array<string>}
     */
    private function importCollections(array $collections, string $workspaceId): array
    {
        $count = 0;
        $errors = [];

        foreach ($collections as $collectionData) {
            DB::beginTransaction();

            try {
                $collection = Collection::create([
                    'name' => $this->generateUniqueCollectionName($collectionData['name'] ?? 'Imported Collection'),
                    'description' => $collectionData['description'] ?? null,
                    'variables' => $collectionData['variables'] ?? [],
                    'order' => Collection::forWorkspace($workspaceId)->max('order') + 1,
                    'workspace_id' => $workspaceId,
                ]);

                $this->importFolders($collectionData['folders'] ?? [], $collection, null);
                $this->importRequests($collectionData['requests'] ?? [], $collection, null);

                DB::commit();
                $count++;
            } catch (\Exception $e) {
                DB::rollBack();
                $name = $collectionData['name'] ?? 'Unknown';
                $errors[] = "Failed to import collection '{$name}': ".$e->getMessage();
            }
        }

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * Recursively import folders preserving hierarchy.
     */
    private function importFolders(array $folders, Collection $collection, ?Folder $parent): void
    {
        $order = 0;

        foreach ($folders as $folderData) {
            $order++;

            $folder = Folder::create([
                'collection_id' => $collection->id,
                'parent_id' => $parent?->id,
                'name' => $folderData['name'] ?? 'Unnamed Folder',
                'order' => $folderData['order'] ?? $order,
            ]);

            $this->importFolders($folderData['children'] ?? [], $collection, $folder);
            $this->importRequests($folderData['requests'] ?? [], $collection, $folder);
        }
    }

    /**
     * Import requests into a collection, optionally under a folder.
     */
    private function importRequests(array $requests, Collection $collection, ?Folder $folder): void
    {
        $order = 0;

        foreach ($requests as $requestData) {
            $order++;

            Request::create([
                'collection_id' => $collection->id,
                'folder_id' => $folder?->id,
                'name' => $requestData['name'] ?? 'Unnamed Request',
                'method' => $requestData['method'] ?? 'GET',
                'url' => $requestData['url'] ?? '',
                'headers' => $requestData['headers'] ?? [],
                'query_params' => $requestData['query_params'] ?? [],
                'body' => $requestData['body'] ?? null,
                'body_type' => $requestData['body_type'] ?? 'none',
                'scripts' => $requestData['scripts'] ?? null,
                'auth' => $requestData['auth'] ?? null,
                'order' => $requestData['order'] ?? $order,
            ]);
        }
    }

    /**
     * @return array{count: int, errors: array<string>}
     */
    private function importEnvironments(array $environments, string $workspaceId): array
    {
        $count = 0;
        $errors = [];

        foreach ($environments as $envData) {
            try {
                Environment::create([
                    'name' => $this->generateUniqueEnvironmentName($envData['name'] ?? 'Imported Environment'),
                    'variables' => $envData['variables'] ?? [],
                    'is_active' => false,
                    'order' => Environment::forWorkspace($workspaceId)->max('order') + 1,
                    'workspace_id' => $workspaceId,
                    'vault_synced' => $envData['vault_synced'] ?? false,
                    'vault_path' => $envData['vault_path'] ?? null,
                ]);

                $count++;
            } catch (\Exception $e) {
                $name = $envData['name'] ?? 'Unknown';
                $errors[] = "Failed to import environment '{$name}': ".$e->getMessage();
            }
        }

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * @return array{success: bool, errors: array<string>}
     */
    private function importConfig(array $config, string $workspaceId): array
    {
        $errors = [];

        try {
            $ws = app(WorkspaceService::class);

            if (! empty($config['remote'])) {
                $remote = $config['remote'];
                if (! empty($remote['provider'])) {
                    $ws->setSetting('remote.provider', $remote['provider']);
                }
                if (! empty($remote['repository'])) {
                    $ws->setSetting('remote.repository', $remote['repository']);
                }
                if (isset($remote['branch'])) {
                    $ws->setSetting('remote.branch', $remote['branch']);
                }
                if (isset($remote['auto_sync'])) {
                    $ws->setSetting('remote.auto_sync', $remote['auto_sync'] ? '1' : '0');
                }
            }

            if (! empty($config['vault'])) {
                $vault = $config['vault'];
                if (! empty($vault['provider'])) {
                    $ws->setSetting('vault.provider', $vault['provider']);
                }
                if (! empty($vault['url'])) {
                    $ws->setSetting('vault.url', $vault['url']);
                }
                if (isset($vault['auth_method'])) {
                    $ws->setSetting('vault.auth_method', $vault['auth_method']);
                }
                if (isset($vault['namespace'])) {
                    $ws->setSetting('vault.namespace', $vault['namespace']);
                }
                if (isset($vault['mount'])) {
                    $ws->setSetting('vault.mount', $vault['mount']);
                }
            }

            return ['success' => true, 'errors' => $errors];
        } catch (\Exception $e) {
            $errors[] = 'Failed to import config: '.$e->getMessage();

            return ['success' => false, 'errors' => $errors];
        }
    }

    /**
     * Build the collections export data for a workspace.
     *
     * @return array<int, array>
     */
    private function buildCollectionsData(string $workspaceId): array
    {
        $collections = Collection::forWorkspace($workspaceId)
            ->ordered()
            ->with(['folders', 'requests'])
            ->get();

        return $collections->map(function (Collection $collection) {
            return [
                'name' => $collection->name,
                'description' => $collection->description,
                'order' => $collection->order,
                'variables' => $collection->variables ?? [],
                'folders' => $this->buildFoldersTree($collection),
                'requests' => $this->buildRequestsData(
                    $collection->requests()->whereNull('folder_id')->orderBy('order')->get()
                ),
            ];
        })->toArray();
    }

    /**
     * Build nested folder tree for a collection.
     *
     * @return array<int, array>
     */
    private function buildFoldersTree(Collection $collection, ?string $parentId = null): array
    {
        $folders = $collection->folders()
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->get();

        return $folders->map(function (Folder $folder) use ($collection) {
            return [
                'name' => $folder->name,
                'order' => $folder->order,
                'children' => $this->buildFoldersTree($collection, $folder->id),
                'requests' => $this->buildRequestsData($folder->requests()->orderBy('order')->get()),
            ];
        })->toArray();
    }

    /**
     * Build request data array from a collection of requests.
     *
     * @return array<int, array>
     */
    private function buildRequestsData($requests): array
    {
        return $requests->map(function (Request $request) {
            return [
                'name' => $request->name,
                'method' => $request->method,
                'url' => $request->url,
                'headers' => $request->headers ?? [],
                'query_params' => $request->query_params ?? [],
                'body' => $request->body,
                'body_type' => $request->body_type,
                'scripts' => $request->scripts,
                'auth' => $request->auth,
                'order' => $request->order,
            ];
        })->toArray();
    }

    /**
     * Build the environments export data for a workspace.
     *
     * @return array<int, array>
     */
    private function buildEnvironmentsData(string $workspaceId): array
    {
        $environments = Environment::forWorkspace($workspaceId)
            ->ordered()
            ->get();

        return $environments->map(function (Environment $environment) {
            $data = [
                'name' => $environment->name,
                'order' => $environment->order,
                'is_active' => $environment->is_active,
                'vault_synced' => $environment->vault_synced ?? false,
                'vault_path' => $environment->vault_path,
            ];

            // Skip variable values for vault-synced environments
            if ($environment->vault_synced) {
                $data['variables'] = [];
            } else {
                $data['variables'] = $environment->variables ?? [];
            }

            return $data;
        })->toArray();
    }

    /**
     * Build the config export data for a workspace, masking tokens.
     *
     * @return array{remote: array, vault: array}
     */
    private function buildConfigData(string $workspaceId): array
    {
        $ws = app(WorkspaceService::class);

        return [
            'remote' => [
                'provider' => $ws->getSetting('remote.provider', ''),
                'repository' => $ws->getSetting('remote.repository', ''),
                'branch' => $ws->getSetting('remote.branch', 'main'),
                'auto_sync' => (bool) $ws->getSetting('remote.auto_sync', false),
            ],
            'vault' => [
                'provider' => $ws->getSetting('vault.provider', ''),
                'url' => $ws->getSetting('vault.url', ''),
                'auth_method' => $ws->getSetting('vault.auth_method', 'token'),
                'namespace' => $ws->getSetting('vault.namespace', ''),
                'mount' => $ws->getSetting('vault.mount', 'secret'),
            ],
        ];
    }

    /**
     * @return array{vaxtly_export: true, version: int, type: string, exported_at: string, data: array}
     */
    private function wrap(string $type, array $data): array
    {
        return [
            'vaxtly_export' => true,
            'version' => 1,
            'type' => $type,
            'exported_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    private function generateUniqueCollectionName(string $baseName): string
    {
        $name = $baseName;
        $counter = 1;

        while (Collection::where('name', $name)->exists()) {
            $counter++;
            $name = "{$baseName} ({$counter})";
        }

        return $name;
    }

    private function generateUniqueEnvironmentName(string $baseName): string
    {
        $name = $baseName;
        $counter = 1;

        while (Environment::where('name', $name)->exists()) {
            $counter++;
            $name = "{$baseName} ({$counter})";
        }

        return $name;
    }
}
