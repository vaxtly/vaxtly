<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use Illuminate\Support\Facades\DB;

class CollectionSerializer
{
    /**
     * Serialize a collection to a portable array structure.
     *
     * @return array<string, mixed>
     */
    public function serialize(Collection $collection): array
    {
        $collection->load([
            'rootFolders.children.children.requests',
            'rootFolders.children.requests',
            'rootFolders.requests',
            'rootRequests',
        ]);

        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'variables' => $collection->variables ?? [],
            'environment_ids' => $collection->getEnvironmentIds(),
            'default_environment_id' => $collection->default_environment_id,
            'folders' => $collection->rootFolders->map(fn (Folder $folder) => $this->serializeFolder($folder))->toArray(),
            'requests' => $collection->rootRequests->map(fn (Request $request) => $this->serializeRequest($request))->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFolder(Folder $folder): array
    {
        return [
            'id' => $folder->id,
            'name' => $folder->name,
            'order' => $folder->order,
            'environment_ids' => $folder->getEnvironmentIds(),
            'default_environment_id' => $folder->default_environment_id,
            'children' => $folder->children->map(fn (Folder $child) => $this->serializeFolder($child))->toArray(),
            'requests' => $folder->requests->map(fn (Request $request) => $this->serializeRequest($request))->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRequest(Request $request): array
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
            'order' => $request->order,
        ];

        if ($request->scripts) {
            $data['scripts'] = $request->scripts;
        }

        if ($request->auth) {
            $data['auth'] = $request->auth;
        }

        return $data;
    }

    /**
     * Import a collection from remote data, creating or updating in the database.
     */
    public function importFromRemote(array $data, ?string $existingCollectionId = null): Collection
    {
        return DB::transaction(function () use ($data, $existingCollectionId) {
            $environmentFields = $this->validateEnvironmentIds($data);

            if ($existingCollectionId) {
                $collection = Collection::findOrFail($existingCollectionId);
                $collection->update([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'variables' => $data['variables'] ?? [],
                    ...$environmentFields,
                ]);

                // Remove existing folders and requests, then re-create
                $collection->folders()->delete();
                $collection->requests()->delete();
            } else {
                $collection = Collection::create([
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'variables' => $data['variables'] ?? [],
                    ...$environmentFields,
                    'order' => Collection::max('order') + 1,
                    'sync_enabled' => true,
                    'workspace_id' => app(WorkspaceService::class)->activeId(),
                ]);
            }

            // Re-create folders and requests from the data
            foreach ($data['folders'] ?? [] as $folderData) {
                $this->importFolder($folderData, $collection, null);
            }

            foreach ($data['requests'] ?? [] as $requestData) {
                $this->importRequest($requestData, $collection, null);
            }

            return $collection->fresh();
        });
    }

    private function importFolder(array $data, Collection $collection, ?string $parentId): void
    {
        $folderEnvFields = $this->validateEnvironmentIds($data);

        $folder = Folder::create([
            'id' => $data['id'],
            'collection_id' => $collection->id,
            'parent_id' => $parentId,
            'name' => $data['name'],
            'order' => $data['order'] ?? 0,
            ...$folderEnvFields,
        ]);

        foreach ($data['children'] ?? [] as $childData) {
            $this->importFolder($childData, $collection, $folder->id);
        }

        foreach ($data['requests'] ?? [] as $requestData) {
            $this->importRequest($requestData, $collection, $folder->id);
        }
    }

    private function importRequest(array $data, Collection $collection, ?string $folderId): void
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
            'order' => $data['order'] ?? 0,
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
