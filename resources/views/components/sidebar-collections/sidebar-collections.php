<?php

use App\Exceptions\SyncConflictException;
use App\Models\Collection;
use App\Models\Folder;
use App\Models\Request;
use App\Services\RemoteSyncService;
use App\Services\SensitiveDataScanner;
use App\Services\SessionLogService;
use App\Services\WorkspaceService;
use App\Support\BootLogger;
use Beartropy\Ui\Traits\HasToasts;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    use HasToasts;

    public string $activeWorkspaceId = '';

    public string $sort = 'a-z';

    // Editing state
    public ?string $editingId = null;

    public string $editingName = '';

    public string $editingType = 'collection';

    // Expanded state
    public array $expandedCollections = [];

    public array $expandedFolders = [];

    public function mount(string $activeWorkspaceId): void
    {
        BootLogger::log('sidebar-collections: mount() started');

        $this->activeWorkspaceId = $activeWorkspaceId;
        $this->sort = get_setting('collections.sort', 'a-z');

        $ws = app(WorkspaceService::class);
        $savedCollections = $ws->getSetting('ui.expanded_collections', []);

        if (! empty($savedCollections)) {
            $this->expandedCollections = array_fill_keys($savedCollections, true);
        } else {
            foreach ($this->getCollections() as $collection) {
                $this->expandedCollections[$collection->id] = true;
            }
        }

        $this->expandedFolders = array_fill_keys(
            $ws->getSetting('ui.expanded_folders', []),
            true
        );

        BootLogger::log('sidebar-collections: mount() complete');
    }

    #[Renderless]
    public function persistExpandedState(?array $expandedCollectionIds = null, ?array $expandedFolderIds = null): void
    {
        if ($expandedCollectionIds !== null) {
            $this->expandedCollections = array_fill_keys($expandedCollectionIds, true);
        }
        if ($expandedFolderIds !== null) {
            $this->expandedFolders = array_fill_keys($expandedFolderIds, true);
        }

        $ws = app(WorkspaceService::class);
        $ws->setSetting('ui.expanded_collections', array_keys(array_filter($this->expandedCollections)));
        $ws->setSetting('ui.expanded_folders', array_keys(array_filter($this->expandedFolders)));
    }

    protected function dispatchExpandedSync(): void
    {
        $this->dispatch('sidebar-expanded-sync',
            collections: $this->expandedCollections,
            folders: $this->expandedFolders
        );
    }

    #[On('sidebar-sort-changed')]
    public function onSortChanged(string $sort): void
    {
        $this->sort = $sort;
    }

    // Data fetching
    public function getCollections()
    {
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

    public function buildSearchableText(Collection $collection): string
    {
        $parts = [strtolower($collection->name)];

        foreach ($collection->rootRequests as $request) {
            $parts[] = strtolower($request->name);
            $parts[] = strtolower($request->url ?? '');
        }

        $this->appendFolderSearchText($collection->rootFolders, $parts);

        return implode(' ', array_filter($parts));
    }

    protected function appendFolderSearchText($folders, array &$parts): void
    {
        foreach ($folders as $folder) {
            $parts[] = strtolower($folder->name);

            foreach ($folder->requests as $request) {
                $parts[] = strtolower($request->name);
                $parts[] = strtolower($request->url ?? '');
            }

            $this->appendFolderSearchText($folder->children, $parts);
        }
    }

    // Collection editing
    public function startEditing(string $id): void
    {
        $item = Collection::find($id);
        if ($item) {
            $this->editingId = $id;
            $this->editingName = $item->name;
            $this->editingType = 'collection';
        }
    }

    public function saveEditing(): void
    {
        if (! $this->editingId || $this->editingType !== 'collection') {
            return;
        }

        $this->validate(['editingName' => 'required|string|max:255']);

        $item = Collection::find($this->editingId);
        if ($item) {
            $item->update(['name' => $this->editingName]);
            $item->markDirty();
            $this->dispatch('collections-updated');
        }

        $this->cancelEditing();
    }

    public function cancelEditing(): void
    {
        $this->editingId = null;
        $this->editingName = '';
        $this->editingType = 'collection';
    }

    // Collection CRUD
    #[On('create-collection')]
    public function createCollection(): void
    {
        $collection = Collection::create([
            'name' => 'New Collection',
            'description' => 'Description',
            'order' => Collection::max('order') + 1,
            'workspace_id' => $this->activeWorkspaceId,
        ]);

        $this->expandedCollections[$collection->id] = true;
        $this->persistExpandedState();
        $this->dispatchExpandedSync();
        $this->dispatch('collections-updated');
        $this->startEditing($collection->id);
    }

    public function deleteCollection(string $collectionId): void
    {
        $collection = Collection::find($collectionId);
        if ($collection) {
            if ($collection->sync_enabled && $collection->remote_sha) {
                try {
                    $syncService = app(RemoteSyncService::class);
                    $syncService->deleteRemoteCollection($collection);
                } catch (\Exception $e) {
                    report($e);
                }
            }
            $collection->delete();
        }
        $this->dispatch('collections-updated');
    }

    // Folder CRUD
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

        $this->persistExpandedState();
        $this->dispatchExpandedSync();

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
        $this->persistExpandedState();
        $this->dispatchExpandedSync();
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

    // Request CRUD
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

    #[Renderless]
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
            $this->persistExpandedState();
            $this->dispatchExpandedSync();
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

    // Sync methods
    public function enableSync(string $collectionId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $findings = (new SensitiveDataScanner)->scanCollection($collection);

        if (! empty($findings)) {
            $this->dispatch('open-sensitive-data-modal',
                collectionId: $collectionId,
                collectionName: $collection->name,
                findings: $findings
            );

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
                $syncService = app(RemoteSyncService::class);
                if ($syncService->isConfigured()) {
                    $syncService->deleteRemoteCollection($collection);
                }
            } catch (\Exception $e) {
                report($e);
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
            $syncService = app(RemoteSyncService::class);
            if (! $syncService->isConfigured()) {
                return;
            }

            $syncService->pushCollection($collection);
            $this->dispatch('collections-updated');
            $this->toast()->success('Pushed', $collection->name);
        } catch (\Exception $e) {
            if ($syncService->isShaConflict($e)) {
                $conflictInfo = $syncService->getConflictInfo($collection);
                $this->dispatch('open-conflict-modal',
                    collectionId: $collectionId,
                    collectionName: $collection->name,
                    remoteSha: $conflictInfo['sha'],
                    remotePath: $conflictInfo['path']
                );
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
            $syncService = app(RemoteSyncService::class);
            if (! $syncService->isConfigured()) {
                return;
            }

            if ($syncService->pullSingleCollection($collection)) {
                $this->dispatch('collections-updated');
                $this->toast()->success('Pulled', $collection->name);
            }
        } catch (SyncConflictException) {
            $conflictInfo = (app(RemoteSyncService::class))->getConflictInfo($collection);
            $this->dispatch('open-conflict-modal',
                collectionId: $collectionId,
                collectionName: $collection->name,
                remoteSha: $conflictInfo['sha'],
                remotePath: $conflictInfo['path']
            );
        } catch (\Exception $e) {
            app(SessionLogService::class)->logGitOperation('pull', $collection->name, 'Pull failed: '.$e->getMessage(), false);
            $this->toast()->error('Pull failed', $e->getMessage(), 0);
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

    // Focus/expand for tab switching
    #[On('sidebar-focus-request')]
    public function focusOnRequest(string $requestId): void
    {
        $this->skipRender();
        $request = Request::select(['id', 'collection_id', 'folder_id'])->find($requestId);
        if (! $request) {
            return;
        }

        if ($request->collection_id && empty($this->expandedCollections[$request->collection_id])) {
            $this->expandedCollections[$request->collection_id] = true;
        }

        if ($request->folder_id) {
            $folders = Folder::where('collection_id', $request->collection_id)
                ->pluck('parent_id', 'id');

            $currentId = $request->folder_id;
            while ($currentId) {
                if (empty($this->expandedFolders[$currentId])) {
                    $this->expandedFolders[$currentId] = true;
                }
                $currentId = $folders[$currentId] ?? null;
            }
        }

        $this->persistExpandedState();
        $this->dispatchExpandedSync();
        $this->dispatch('sidebar-scroll-to', selector: "[data-request-id=\"{$requestId}\"]");
    }

    #[On('collections-updated')]
    public function refreshCollections(): void
    {
        // Re-render will fetch fresh data
    }

    #[On('workspace-switched')]
    public function onWorkspaceSwitched(string $workspaceId): void
    {
        $this->activeWorkspaceId = $workspaceId;

        $ws = app(WorkspaceService::class);
        $savedCollections = $ws->getSetting('ui.expanded_collections', []);

        if (! empty($savedCollections)) {
            $this->expandedCollections = array_fill_keys($savedCollections, true);
            $this->expandedFolders = array_fill_keys(
                $ws->getSetting('ui.expanded_folders', []),
                true
            );
        } else {
            $this->expandedCollections = [];
            $this->expandedFolders = [];
            foreach ($this->getCollections() as $collection) {
                $this->expandedCollections[$collection->id] = true;
            }
        }

        $this->dispatchExpandedSync();
    }
};
