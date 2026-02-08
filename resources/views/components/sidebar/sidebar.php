<?php

use App\Exceptions\SyncConflictException;
use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use App\Models\Workspace;
use App\Services\RemoteSyncService;
use App\Services\SessionLogService;
use App\Services\VaultSyncService;
use App\Services\WorkspaceService;
use App\Traits\HttpColorHelper;
use Beartropy\Ui\Traits\HasToasts;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use HasToasts;
    use HttpColorHelper;

    // Mode: 'collections' or 'environments'
    public string $mode = 'collections';

    // Sort: 'manual', 'a-z', 'z-a', 'oldest', 'newest'
    public string $sort = 'manual';

    // Shared properties
    public string $search = '';

    public ?string $editingId = null;

    public string $editingName = '';

    public string $editingType = 'collection';

    // Collections-specific
    public array $expandedCollections = [];

    public array $expandedFolders = [];

    // Environment association modal
    public bool $showEnvironmentModal = false;

    public string $environmentModalTargetType = 'collection';

    public ?string $environmentModalTargetId = null;

    // Sync conflict modal
    public bool $showConflictModal = false;

    public ?string $conflictCollectionId = null;

    public ?string $conflictCollectionName = null;

    public ?string $conflictRemoteSha = null;

    public ?string $conflictRemotePath = null;

    // Environments-specific
    #[Modelable]
    public ?string $selectedEnvironmentId = null;

    // Workspace properties
    public string $activeWorkspaceId = '';

    public bool $showWorkspaceDropdown = false;

    public bool $isCreatingWorkspace = false;

    public string $newWorkspaceName = '';

    public ?string $editingWorkspaceId = null;

    public string $editingWorkspaceName = '';

    public function mount(): void
    {
        $this->activeWorkspaceId = app(WorkspaceService::class)->activeId();

        // Initialize sort based on current mode
        $this->updateSortForMode();

        if ($this->mode === 'collections') {
            foreach ($this->getCollections() as $collection) {
                $this->expandedCollections[$collection->id] = true;
            }
        }
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->updateSortForMode();

        // Dispatch event for parent if needed (mirroring previous behavior)
        if ($mode === 'collections') {
            $this->dispatch('switch-to-collections');
        } else {
            $this->dispatch('switch-to-environments');
        }
    }

    protected function updateSortForMode(): void
    {
        if ($this->mode === 'collections') {
            $this->sort = get_setting('collections.sort', 'a-z');
        } else {
            $this->sort = get_setting('environments.sort', 'a-z');
        }
    }

    public function updatedSort(string $value): void
    {
        if ($this->mode === 'collections') {
            set_setting('collections.sort', $value);
        } else {
            set_setting('environments.sort', $value);
        }
    }

    // Data fetching methods
    public function getCollections()
    {
        // Only select columns needed for sidebar display — skip heavy/encrypted
        // fields like variables, auth, headers, body, scripts, query_params
        $requestColumns = ['id', 'name', 'method', 'url', 'collection_id', 'folder_id', 'order'];
        $folderColumns = ['id', 'name', 'collection_id', 'parent_id', 'order', 'environment_ids'];

        $query = Collection::select([
            'id', 'name', 'order', 'workspace_id', 'sync_enabled', 'is_dirty',
            'environment_ids', 'default_environment_id', 'created_at',
        ])->with([
            'rootFolders' => fn ($q) => $q->select($folderColumns),
            'rootFolders.children' => fn ($q) => $q->select($folderColumns),
            'rootFolders.children.children' => fn ($q) => $q->select($folderColumns),
            'rootFolders.children.children.requests' => fn ($q) => $q->select($requestColumns),
            'rootFolders.children.requests' => fn ($q) => $q->select($requestColumns),
            'rootFolders.requests' => fn ($q) => $q->select($requestColumns),
            'rootRequests' => fn ($q) => $q->select($requestColumns),
        ])->forWorkspace($this->activeWorkspaceId);

        return match ($this->sort) {
            'manual' => $query->orderBy('order')->get(),
            'z-a' => $query->orderByRaw('LOWER(name) DESC')->get(),
            'oldest' => $query->orderBy('created_at', 'asc')->get(),
            'newest' => $query->orderBy('created_at', 'desc')->get(),
            default => $query->orderByRaw('LOWER(name) ASC')->get(), // 'a-z'
        };
    }

    public function getEnvironments()
    {
        $query = Environment::forWorkspace($this->activeWorkspaceId);

        return match ($this->sort) {
            'manual' => $query->orderBy('order')->get(),
            'z-a' => $query->orderByRaw('LOWER(name) DESC')->get(),
            'oldest' => $query->orderBy('created_at', 'asc')->get(),
            'newest' => $query->orderBy('created_at', 'desc')->get(),
            default => $query->orderByRaw('LOWER(name) ASC')->get(), // 'a-z'
        };
    }

    // Event listeners
    #[On('collections-updated')]
    public function refreshCollections(): void
    {
        // Re-render will fetch fresh data
    }

    #[On('environments-updated')]
    public function refreshEnvironments(): void
    {
        // Re-render will fetch fresh data
    }

    // Computed properties for filtered lists
    #[Computed]
    public function items()
    {
        return $this->mode === 'collections'
            ? $this->filteredCollections()
            : $this->filteredEnvironments();
    }

    #[Computed]
    public function filteredCollections()
    {
        $collections = $this->getCollections();

        if (empty($this->search)) {
            return $collections;
        }

        $search = strtolower($this->search);

        return $collections->filter(function ($collection) use ($search) {
            if (str_contains(strtolower($collection->name), $search)) {
                return true;
            }

            // Check root requests
            foreach ($collection->rootRequests as $request) {
                if (str_contains(strtolower($request->name), $search) ||
                    str_contains(strtolower($request->url ?? ''), $search)) {
                    return true;
                }
            }

            // Check folders recursively
            return $this->folderMatchesSearch($collection->rootFolders, $search);
        });
    }

    private function folderMatchesSearch($folders, string $search): bool
    {
        foreach ($folders as $folder) {
            if (str_contains(strtolower($folder->name), $search)) {
                return true;
            }

            foreach ($folder->requests as $request) {
                if (str_contains(strtolower($request->name), $search) ||
                    str_contains(strtolower($request->url ?? ''), $search)) {
                    return true;
                }
            }

            if ($this->folderMatchesSearch($folder->children, $search)) {
                return true;
            }
        }

        return false;
    }

    #[Computed]
    public function filteredEnvironments()
    {
        $environments = $this->getEnvironments();

        if (empty($this->search)) {
            return $environments;
        }

        $search = strtolower($this->search);

        return $environments->filter(function ($environment) use ($search) {
            return str_contains(strtolower($environment->name), $search);
        });
    }

    // Shared editing methods
    public function startEditing(string $id): void
    {
        if ($this->mode === 'collections') {
            $item = Collection::find($id);
            $this->editingType = 'collection';
        } else {
            $item = Environment::find($id);
            $this->editingType = 'environment';
        }

        if ($item) {
            $this->editingId = $id;
            $this->editingName = $item->name;
        }
    }

    public function enableSync(string $collectionId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $collection->update(['sync_enabled' => true]);
        $collection->syncToRemote();
        $this->dispatch('collections-updated');
        $this->toast()->success('Sync enabled', $collection->name);
    }

    public function disableSync(string $collectionId, bool $deleteRemote = false): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        if ($deleteRemote && $collection->remote_sha) {
            try {
                $syncService = new RemoteSyncService;
                if ($syncService->isConfigured()) {
                    $syncService->deleteRemoteCollection($collection);
                }
            } catch (\Exception) {
                // Continue with local disable even if remote delete fails
            }
        }

        $collection->update([
            'sync_enabled' => false,
            'is_dirty' => false,
        ]);

        $this->dispatch('collections-updated');
    }

    #[On('push-collection')]
    public function pushSingleCollection(string $collectionId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection || ! $collection->sync_enabled) {
            return;
        }

        try {
            $syncService = new RemoteSyncService;
            if (! $syncService->isConfigured()) {
                return;
            }

            $syncService->pushCollection($collection);
            $this->dispatch('collections-updated');
            $this->toast()->success('Pushed', $collection->name);
        } catch (\Exception $e) {
            if ($syncService->isShaConflict($e)) {
                $conflictInfo = $syncService->getConflictInfo($collection);
                $this->conflictCollectionId = $collectionId;
                $this->conflictCollectionName = $collection->name;
                $this->conflictRemoteSha = $conflictInfo['sha'];
                $this->conflictRemotePath = $conflictInfo['path'];
                $this->showConflictModal = true;
            } else {
                app(SessionLogService::class)->logGitOperation('push', $collection->name, 'Push failed: '.$e->getMessage(), false);
                $this->toast()->error('Push failed', $e->getMessage(), 0);
            }
        }
    }

    #[On('pull-collection')]
    public function pullSingleCollection(string $collectionId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection || ! $collection->sync_enabled) {
            return;
        }

        try {
            $syncService = new RemoteSyncService;
            if (! $syncService->isConfigured()) {
                return;
            }

            if ($syncService->pullSingleCollection($collection)) {
                $this->dispatch('collections-updated');
                $this->toast()->success('Pulled', $collection->name);
            }
        } catch (SyncConflictException) {
            $conflictInfo = (new RemoteSyncService)->getConflictInfo($collection);
            $this->conflictCollectionId = $collectionId;
            $this->conflictCollectionName = $collection->name;
            $this->conflictRemoteSha = $conflictInfo['sha'];
            $this->conflictRemotePath = $conflictInfo['path'];
            $this->showConflictModal = true;
        } catch (\Exception $e) {
            app(SessionLogService::class)->logGitOperation('pull', $collection->name, 'Pull failed: '.$e->getMessage(), false);
            $this->toast()->error('Pull failed', $e->getMessage(), 0);
        }
    }

    public function resolveConflictForcePush(): void
    {
        if (! $this->conflictCollectionId || ! $this->conflictRemoteSha) {
            $this->closeConflictModal();

            return;
        }

        $collection = Collection::find($this->conflictCollectionId);
        if (! $collection) {
            $this->closeConflictModal();

            return;
        }

        try {
            $syncService = new RemoteSyncService;
            $syncService->forceKeepLocal($collection, $this->conflictRemoteSha);
            $this->dispatch('collections-updated');
            $this->toast()->success('Conflict resolved', 'Kept local version of '.$collection->name);
        } catch (\Exception $e) {
            app(SessionLogService::class)->logGitOperation('push', $collection->name, 'Force push failed: '.$e->getMessage(), false);
            $this->toast()->error('Conflict resolution failed', $e->getMessage(), 0);
        }

        $this->closeConflictModal();
    }

    public function resolveConflictPullFirst(): void
    {
        if (! $this->conflictCollectionId || ! $this->conflictRemotePath || ! $this->conflictRemoteSha) {
            $this->closeConflictModal();

            return;
        }

        $collection = Collection::find($this->conflictCollectionId);
        if (! $collection) {
            $this->closeConflictModal();

            return;
        }

        try {
            $syncService = new RemoteSyncService;
            $syncService->forceKeepRemote($collection, $this->conflictRemotePath, $this->conflictRemoteSha);
            $this->dispatch('collections-updated');
            $this->toast()->success('Conflict resolved', 'Pulled remote version of '.$collection->name);
        } catch (\Exception $e) {
            app(SessionLogService::class)->logGitOperation('pull', $collection->name, 'Force pull failed: '.$e->getMessage(), false);
            $this->toast()->error('Conflict resolution failed', $e->getMessage(), 0);
        }

        $this->closeConflictModal();
    }

    public function closeConflictModal(): void
    {
        $this->showConflictModal = false;
        $this->conflictCollectionId = null;
        $this->conflictCollectionName = null;
        $this->conflictRemoteSha = null;
        $this->conflictRemotePath = null;
    }

    public function saveEditing(): void
    {
        if (! $this->editingId) {
            return;
        }

        $this->validate(['editingName' => 'required|string|max:255']);

        if ($this->mode === 'collections') {
            $item = Collection::find($this->editingId);
            if ($item) {
                $item->update(['name' => $this->editingName]);
                $item->markDirty();
                $this->dispatch('collections-updated');
            }
        } else {
            $item = Environment::find($this->editingId);
            if ($item) {
                if ($item->vault_synced && $item->name !== $this->editingName) {
                    try {
                        $vaultService = new VaultSyncService;
                        if ($vaultService->isConfigured()) {
                            $oldPath = $vaultService->buildPath($item);
                            $item->update(['name' => $this->editingName]);
                            $newPath = $vaultService->buildPath($item);
                            $vaultService->migrateEnvironment($item, $oldPath, $newPath);
                        } else {
                            $item->update(['name' => $this->editingName]);
                        }
                    } catch (\Exception) {
                        $item->update(['name' => $this->editingName]);
                    }
                } else {
                    $item->update(['name' => $this->editingName]);
                }
                $this->dispatch('environments-updated');
            }
        }

        $this->cancelEditing();
    }

    public function cancelEditing(): void
    {
        $this->editingId = null;
        $this->editingName = '';
        $this->editingType = 'collection';
    }

    // Shared create method
    public function create(): void
    {
        if ($this->mode === 'collections') {
            $this->createCollection();
        } else {
            $this->createEnvironment();
        }
    }

    // Collections-specific methods
    public function createCollection(): void
    {
        $collection = Collection::create([
            'name' => 'New Collection',
            'description' => 'Description',
            'order' => Collection::max('order') + 1,
            'workspace_id' => $this->activeWorkspaceId,
        ]);

        $this->expandedCollections[$collection->id] = true;
        $this->dispatch('collections-updated');
        $this->startEditing($collection->id);
    }

    public function toggleAllCollections(): void
    {
        $allExpanded = collect($this->filteredCollections)
            ->every(fn ($c) => $this->expandedCollections[$c->id] ?? false);

        if ($allExpanded) {
            $this->expandedCollections = [];
            $this->expandedFolders = [];
        } else {
            foreach ($this->filteredCollections as $collection) {
                $this->expandedCollections[$collection->id] = true;
            }
        }
    }

    public function createFolder(string $collectionId, ?string $parentId = null): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $maxOrder = Folder::where('collection_id', $collectionId)
            ->where('parent_id', $parentId)
            ->max('order') ?? 0;

        $folder = Folder::create([
            'collection_id' => $collectionId,
            'parent_id' => $parentId,
            'name' => 'New Folder',
            'order' => $maxOrder + 1,
        ]);

        $this->expandedFolders[$folder->id] = true;
        if ($parentId) {
            $this->expandedFolders[$parentId] = true;
        }

        $collection->markDirty();
        $this->dispatch('collections-updated');
        $this->startFolderEditing($folder->id);
    }

    public function deleteFolder(string $folderId): void
    {
        $folder = Folder::find($folderId);
        if ($folder) {
            $collection = $folder->collection;
            $folder->delete();
            $collection?->markDirty();
        }
        unset($this->expandedFolders[$folderId]);
        $this->dispatch('collections-updated');
    }

    public function startFolderEditing(string $folderId): void
    {
        $folder = Folder::find($folderId);
        if ($folder) {
            $this->editingId = $folderId;
            $this->editingName = $folder->name;
            $this->editingType = 'folder';
        }
    }

    public function saveFolderEditing(): void
    {
        if (! $this->editingId || $this->editingType !== 'folder') {
            return;
        }

        $this->validate(['editingName' => 'required|string|max:255']);

        $folder = Folder::find($this->editingId);
        if ($folder) {
            $folder->update(['name' => $this->editingName]);
            $folder->collection?->markDirty();
            $this->dispatch('collections-updated');
        }

        $this->cancelEditing();
    }

    public function startRequestEditing(string $requestId): void
    {
        $request = Request::find($requestId);
        if ($request) {
            $this->editingId = $requestId;
            $this->editingName = $request->name;
            $this->editingType = 'request';
        }
    }

    public function saveRequestEditing(): void
    {
        if (! $this->editingId || $this->editingType !== 'request') {
            return;
        }

        $this->validate(['editingName' => 'required|string|max:255']);

        $request = Request::find($this->editingId);
        if ($request) {
            $request->update(['name' => $this->editingName]);
            $request->collection?->markDirty($request);
            $this->dispatch('collections-updated');
        }

        $this->cancelEditing();
    }

    public function selectRequest(string $requestId): void
    {
        $this->dispatch('open-request-tab', requestId: $requestId);
    }

    public function createRequest(string $collectionId, ?string $folderId = null): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $maxOrder = Request::where('collection_id', $collectionId)
            ->where('folder_id', $folderId)
            ->max('order') ?? 0;

        $request = Request::create([
            'collection_id' => $collectionId,
            'folder_id' => $folderId,
            'name' => 'New Request',
            'url' => '',
            'method' => 'GET',
            'headers' => [],
            'query_params' => [],
            'body' => '',
            'body_type' => 'none',
            'order' => $maxOrder + 1,
        ]);

        $collection->markDirty();

        if ($folderId) {
            $this->expandedFolders[$folderId] = true;
        }

        $this->dispatch('open-request-tab', requestId: $request->id);
    }

    public function duplicateRequest(string $requestId): void
    {
        $request = Request::find($requestId);
        if (! $request) {
            return;
        }

        $newRequest = $request->replicate();
        $newRequest->name = $request->name.' copy';
        $newRequest->order = Request::where('collection_id', $request->collection_id)->max('order') + 1;
        $newRequest->save();

        $request->collection?->markDirty();
        $this->dispatch('collections-updated');
    }

    public function deleteRequest(string $requestId): void
    {
        $request = Request::find($requestId);
        if ($request) {
            $collection = $request->collection;
            $request->delete();
            $collection?->markDirty();
        }
    }

    // Drag-and-drop reorder methods
    public function reorderCollections(string $id, int $position): void
    {
        if ($this->sort !== 'manual') {
            return;
        }

        DB::transaction(function () use ($id, $position) {
            $siblings = Collection::forWorkspace($this->activeWorkspaceId)
                ->orderBy('order')
                ->pluck('id')
                ->toArray();

            $oldIndex = array_search($id, $siblings);
            if ($oldIndex === false) {
                return;
            }

            array_splice($siblings, $oldIndex, 1);
            array_splice($siblings, $position, 0, [$id]);

            foreach ($siblings as $index => $siblingId) {
                Collection::where('id', $siblingId)->update(['order' => $index]);
            }
        });
    }

    public function reorderFolders(string $id, int $position, ?string $containerId = null): void
    {
        if ($this->sort !== 'manual') {
            return;
        }

        $folder = Folder::find($id);
        if (! $folder) {
            return;
        }

        if ($containerId === null) {
            $containerId = $folder->parent_id
                ? "folder:{$folder->parent_id}"
                : "collection:{$folder->collection_id}";
        }

        [$containerType, $containerUuid] = explode(':', $containerId, 2);

        if ($containerType === 'collection') {
            $newCollectionId = $containerUuid;
            $newParentId = null;
        } else {
            $parentFolder = Folder::find($containerUuid);
            if (! $parentFolder) {
                return;
            }

            if ($this->isDescendantOf($containerUuid, $id)) {
                return;
            }

            $newCollectionId = $parentFolder->collection_id;
            $newParentId = $parentFolder->id;
        }

        $oldCollectionId = $folder->collection_id;

        DB::transaction(function () use ($folder, $position, $newCollectionId, $newParentId, $oldCollectionId) {
            $folder->update([
                'collection_id' => $newCollectionId,
                'parent_id' => $newParentId,
            ]);

            if ($oldCollectionId !== $newCollectionId) {
                $this->updateDescendantCollectionIds($folder, $newCollectionId);
            }

            $siblings = Folder::where('collection_id', $newCollectionId)
                ->where('parent_id', $newParentId)
                ->orderBy('order')
                ->pluck('id')
                ->toArray();

            $oldIndex = array_search($folder->id, $siblings);
            if ($oldIndex !== false) {
                array_splice($siblings, $oldIndex, 1);
            }
            array_splice($siblings, $position, 0, [$folder->id]);

            foreach ($siblings as $index => $siblingId) {
                Folder::where('id', $siblingId)->update(['order' => $index]);
            }

            Collection::find($newCollectionId)?->markDirty();
            if ($oldCollectionId !== $newCollectionId) {
                Collection::find($oldCollectionId)?->markDirty();
            }
        });

        $this->dispatch('collections-updated');
    }

    public function reorderRequests(string $id, int $position, ?string $containerId = null): void
    {
        if ($this->sort !== 'manual') {
            return;
        }

        $request = Request::find($id);
        if (! $request) {
            return;
        }

        if ($containerId === null) {
            $containerId = $request->folder_id
                ? "folder:{$request->folder_id}"
                : "collection:{$request->collection_id}";
        }

        [$containerType, $containerUuid] = explode(':', $containerId, 2);

        if ($containerType === 'collection') {
            $newCollectionId = $containerUuid;
            $newFolderId = null;
        } else {
            $parentFolder = Folder::find($containerUuid);
            if (! $parentFolder) {
                return;
            }
            $newCollectionId = $parentFolder->collection_id;
            $newFolderId = $parentFolder->id;
        }

        $oldCollectionId = $request->collection_id;

        DB::transaction(function () use ($request, $position, $newCollectionId, $newFolderId, $oldCollectionId) {
            $request->update([
                'collection_id' => $newCollectionId,
                'folder_id' => $newFolderId,
            ]);

            $siblings = Request::where('collection_id', $newCollectionId)
                ->where('folder_id', $newFolderId)
                ->orderBy('order')
                ->pluck('id')
                ->toArray();

            $oldIndex = array_search($request->id, $siblings);
            if ($oldIndex !== false) {
                array_splice($siblings, $oldIndex, 1);
            }
            array_splice($siblings, $position, 0, [$request->id]);

            foreach ($siblings as $index => $siblingId) {
                Request::where('id', $siblingId)->update(['order' => $index]);
            }

            Collection::find($newCollectionId)?->markDirty();
            if ($oldCollectionId !== $newCollectionId) {
                Collection::find($oldCollectionId)?->markDirty();
            }
        });

        $this->dispatch('collections-updated');
    }

    protected function isDescendantOf(string $potentialDescendantId, string $ancestorId): bool
    {
        $current = Folder::find($potentialDescendantId);

        while ($current) {
            if ($current->parent_id === $ancestorId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    protected function updateDescendantCollectionIds(Folder $folder, string $newCollectionId): void
    {
        foreach ($folder->children as $child) {
            $child->update(['collection_id' => $newCollectionId]);
            Request::where('folder_id', $child->id)->update(['collection_id' => $newCollectionId]);
            $this->updateDescendantCollectionIds($child, $newCollectionId);
        }

        Request::where('folder_id', $folder->id)->update(['collection_id' => $newCollectionId]);
    }

    // Environment association modal methods
    public function openEnvironmentModal(string $targetId, string $type = 'collection'): void
    {
        $this->environmentModalTargetType = $type;
        $this->environmentModalTargetId = $targetId;
        $this->showEnvironmentModal = true;
    }

    public function closeEnvironmentModal(): void
    {
        $this->showEnvironmentModal = false;
        $this->environmentModalTargetType = 'collection';
        $this->environmentModalTargetId = null;
    }

    #[On('open-env-modal-for-context')]
    public function openEnvModalForContext(string $type, string $id): void
    {
        $this->openEnvironmentModal($id, $type);
    }

    public function toggleCollectionEnvironment(string $collectionId, string $environmentId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        if ($collection->hasEnvironment($environmentId)) {
            $collection->removeEnvironment($environmentId);
        } else {
            $collection->addEnvironment($environmentId);
        }

        $collection->markDirty();
    }

    public function setCollectionDefaultEnvironment(string $collectionId, ?string $environmentId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $currentDefault = $collection->default_environment_id;
        $collection->setDefaultEnvironment($currentDefault === $environmentId ? null : $environmentId);
        $collection->markDirty();
    }

    public function toggleFolderEnvironment(string $folderId, string $environmentId): void
    {
        $folder = Folder::find($folderId);
        if (! $folder) {
            return;
        }

        if ($folder->hasEnvironment($environmentId)) {
            $folder->removeEnvironment($environmentId);
        } else {
            $folder->addEnvironment($environmentId);
        }

        $folder->collection?->markDirty();
    }

    public function setFolderDefaultEnvironment(string $folderId, ?string $environmentId): void
    {
        $folder = Folder::find($folderId);
        if (! $folder) {
            return;
        }

        $currentDefault = $folder->default_environment_id;
        $folder->setDefaultEnvironment($currentDefault === $environmentId ? null : $environmentId);
        $folder->collection?->markDirty();
    }

    // Environments-specific methods
    public function createEnvironment(): void
    {
        $environment = Environment::create([
            'name' => 'New Environment',
            'variables' => [],
            'is_active' => false,
            'order' => Environment::max('order') + 1,
            'workspace_id' => $this->activeWorkspaceId,
        ]);

        $this->selectedEnvironmentId = $environment->id;
        $this->dispatch('environments-updated');
        $this->dispatch('environment-selected', environmentId: $environment->id);
        $this->startEditing($environment->id);
    }

    public function selectEnvironment(string $environmentId): void
    {
        $this->selectedEnvironmentId = $environmentId;
        $this->dispatch('environment-selected', environmentId: $environmentId);
    }

    public function toggleActive(string $environmentId): void
    {
        $environment = Environment::find($environmentId);
        if (! $environment) {
            return;
        }

        if ($environment->is_active) {
            $environment->deactivate();
        } else {
            $environment->activate();
        }

        $this->dispatch('environments-updated');
        $this->dispatch('active-environment-changed');
    }

    public function deleteEnvironment(string $environmentId): void
    {
        $environment = Environment::find($environmentId);
        if (! $environment) {
            return;
        }

        if ($environment->vault_synced) {
            try {
                $vaultService = new VaultSyncService;
                if ($vaultService->isConfigured()) {
                    $vaultService->deleteSecrets($environment);
                }
            } catch (\Exception) {
                // Continue with local delete even if Vault delete fails
            }
        }

        $environment->delete();
        $this->dispatch('environments-updated');

        if ($this->selectedEnvironmentId === $environmentId) {
            $this->selectedEnvironmentId = null;
        }
    }

    public function duplicateEnvironment(string $environmentId): void
    {
        $environment = Environment::find($environmentId);
        if (! $environment) {
            return;
        }

        $newEnvironment = $environment->replicate();
        $newEnvironment->name = $environment->name.' copy';
        $newEnvironment->is_active = false;
        $newEnvironment->order = Environment::max('order') + 1;
        $newEnvironment->save();

        $this->dispatch('environments-updated');
    }

    // Workspace methods
    #[Computed]
    public function workspaces()
    {
        return Workspace::ordered()->get();
    }

    #[Computed]
    public function activeWorkspace()
    {
        return Workspace::find($this->activeWorkspaceId);
    }

    public function switchWorkspace(string $workspaceId): void
    {
        if ($workspaceId === $this->activeWorkspaceId) {
            return;
        }

        app(WorkspaceService::class)->switchTo($workspaceId);
        $this->activeWorkspaceId = $workspaceId;
        $this->expandedCollections = [];
        $this->expandedFolders = [];
        $this->search = '';
        $this->selectedEnvironmentId = null;
        $this->showWorkspaceDropdown = false;

        // Deactivate environments in old workspace, handled by scoping
        // Load fresh collections and expand them
        foreach ($this->getCollections() as $collection) {
            $this->expandedCollections[$collection->id] = true;
        }

        unset($this->workspaces, $this->activeWorkspace, $this->filteredCollections, $this->filteredEnvironments, $this->items);

        $this->dispatch('workspace-switched', workspaceId: $workspaceId);
        $this->dispatch('collections-updated');
        $this->dispatch('environments-updated');
    }

    public function createWorkspace(): void
    {
        if (empty(trim($this->newWorkspaceName))) {
            return;
        }

        $workspace = Workspace::create([
            'name' => trim($this->newWorkspaceName),
            'order' => Workspace::max('order') + 1,
            'settings' => [],
        ]);

        $this->newWorkspaceName = '';
        $this->isCreatingWorkspace = false;
        unset($this->workspaces);

        $this->switchWorkspace($workspace->id);
    }

    public function startEditingWorkspace(string $id): void
    {
        $workspace = Workspace::find($id);
        if ($workspace) {
            $this->editingWorkspaceId = $id;
            $this->editingWorkspaceName = $workspace->name;
        }
    }

    public function saveWorkspaceEditing(): void
    {
        if (! $this->editingWorkspaceId || empty(trim($this->editingWorkspaceName))) {
            return;
        }

        $workspace = Workspace::find($this->editingWorkspaceId);
        if ($workspace) {
            $workspace->update(['name' => trim($this->editingWorkspaceName)]);
        }

        $this->editingWorkspaceId = null;
        $this->editingWorkspaceName = '';
        unset($this->workspaces, $this->activeWorkspace);
    }

    public function cancelWorkspaceEditing(): void
    {
        $this->editingWorkspaceId = null;
        $this->editingWorkspaceName = '';
    }

    public function deleteWorkspace(string $id): void
    {
        if (Workspace::count() <= 1) {
            return;
        }

        $workspace = Workspace::find($id);
        if (! $workspace) {
            return;
        }

        $wasActive = $id === $this->activeWorkspaceId;
        $workspace->delete();

        unset($this->workspaces, $this->activeWorkspace);

        if ($wasActive) {
            $nextWorkspace = Workspace::ordered()->first();
            if ($nextWorkspace) {
                $this->switchWorkspace($nextWorkspace->id);
            }
        }
    }
};
