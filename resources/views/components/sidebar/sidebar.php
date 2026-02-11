<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Workspace;
use App\Services\RemoteSyncService;
use App\Services\VaultSyncService;
use App\Services\WorkspaceService;
use App\Support\BootLogger;
use Beartropy\Ui\Traits\HasToasts;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    use HasToasts;

    // Mode: 'collections' or 'environments'
    public string $mode = 'collections';

    // Sort: 'manual', 'a-z', 'z-a', 'oldest', 'newest'
    public string $sort = 'manual';

    // Environment editing
    public ?string $editingId = null;

    public string $editingName = '';

    public string $editingType = 'environment';

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

    // Sensitive data warning modal
    public bool $showSensitiveDataModal = false;

    public ?string $sensitiveDataCollectionId = null;

    public ?string $sensitiveDataCollectionName = null;

    public array $sensitiveDataFindings = [];

    // Environments-specific
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
        BootLogger::log('sidebar: mount() started');

        $this->activeWorkspaceId = app(WorkspaceService::class)->activeId();
        $this->updateSortForMode();

        BootLogger::log('sidebar: mount() complete');
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->updateSortForMode();
        $this->skipRender();
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
            $this->dispatch('sidebar-sort-changed', sort: $value);
        } else {
            set_setting('environments.sort', $value);
        }
    }

    public function create(): void
    {
        if ($this->mode === 'collections') {
            $this->dispatch('create-collection');
            $this->skipRender();
        } else {
            $this->createEnvironment();
        }
    }

    public function focusOnTab(string $tabId, string $type = 'request', ?string $requestId = null, ?string $environmentId = null): void
    {
        $targetMode = $type === 'environment' ? 'environments' : 'collections';
        $this->mode = $targetMode;
        $this->updateSortForMode();

        if ($type === 'request' && $requestId) {
            $this->skipRender();
            $this->dispatch('sidebar-focus-request', requestId: $requestId);

            return;
        }

        if ($type === 'environment' && $environmentId) {
            $this->selectedEnvironmentId = $environmentId;
            $this->dispatch('sidebar-scroll-to', selector: "[data-environment-id=\"{$environmentId}\"]");
        }
    }

    // Environments data
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

    #[On('environments-updated')]
    public function refreshEnvironments(): void
    {
        // Re-render will fetch fresh data
    }

    // Environment editing
    public function startEditing(string $id): void
    {
        $item = Environment::find($id);
        if ($item) {
            $this->editingId = $id;
            $this->editingName = $item->name;
            $this->editingType = 'environment';
        }
    }

    public function saveEditing(): void
    {
        if (! $this->editingId) {
            return;
        }

        $this->validate(['editingName' => 'required|string|max:255']);

        $item = Environment::find($this->editingId);
        if ($item) {
            if ($item->vault_synced && $item->name !== $this->editingName) {
                try {
                    $vaultService = app(VaultSyncService::class);
                    if ($vaultService->isConfigured()) {
                        $oldPath = $vaultService->buildPath($item);
                        $item->update(['name' => $this->editingName]);
                        $newPath = $vaultService->buildPath($item);
                        $vaultService->migrateEnvironment($item, $oldPath, $newPath);
                    } else {
                        $item->update(['name' => $this->editingName]);
                    }
                } catch (\Exception $e) {
                    report($e);
                    $item->update(['name' => $this->editingName]);
                }
            } else {
                $item->update(['name' => $this->editingName]);
            }
            $this->dispatch('environments-updated');
        }

        $this->cancelEditing();
    }

    public function cancelEditing(): void
    {
        $this->editingId = null;
        $this->editingName = '';
        $this->editingType = 'environment';
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
        $this->dispatch('open-environment-tab', environmentId: $environment->id);
        $this->startEditing($environment->id);
    }

    #[Renderless]
    public function selectEnvironment(string $environmentId): void
    {
        $this->selectedEnvironmentId = $environmentId;
        $this->dispatch('open-environment-tab', environmentId: $environmentId);
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
                $vaultService = app(VaultSyncService::class);
                if ($vaultService->isConfigured()) {
                    $vaultService->deleteSecrets($environment);
                }
            } catch (\Exception $e) {
                report($e);
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

    // Conflict modal (opened by child events)
    #[On('open-conflict-modal')]
    public function openConflictModal(string $collectionId, string $collectionName, string $remoteSha, string $remotePath): void
    {
        $this->conflictCollectionId = $collectionId;
        $this->conflictCollectionName = $collectionName;
        $this->conflictRemoteSha = $remoteSha;
        $this->conflictRemotePath = $remotePath;
        $this->showConflictModal = true;
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
            $syncService = app(RemoteSyncService::class);
            $syncService->forceKeepLocal($collection, $this->conflictRemoteSha);
            $this->dispatch('collections-updated');
            $this->toast()->success('Conflict resolved', 'Kept local version of '.$collection->name);
        } catch (\Exception $e) {
            app(\App\Services\SessionLogService::class)->logGitOperation('push', $collection->name, 'Force push failed: '.$e->getMessage(), false);
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
            $syncService = app(RemoteSyncService::class);
            $syncService->forceKeepRemote($collection, $this->conflictRemotePath, $this->conflictRemoteSha);
            $this->dispatch('collections-updated');
            $this->toast()->success('Conflict resolved', 'Pulled remote version of '.$collection->name);
        } catch (\Exception $e) {
            app(\App\Services\SessionLogService::class)->logGitOperation('pull', $collection->name, 'Force pull failed: '.$e->getMessage(), false);
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

    // Sensitive data modal (opened by child events)
    #[On('open-sensitive-data-modal')]
    public function openSensitiveDataModal(string $collectionId, string $collectionName, array $findings): void
    {
        $this->sensitiveDataCollectionId = $collectionId;
        $this->sensitiveDataCollectionName = $collectionName;
        $this->sensitiveDataFindings = $findings;
        $this->showSensitiveDataModal = true;
    }

    public function confirmEnableSyncWithSensitiveData(): void
    {
        if (! $this->sensitiveDataCollectionId) {
            $this->closeSensitiveDataModal();

            return;
        }

        $collection = Collection::find($this->sensitiveDataCollectionId);
        if (! $collection) {
            $this->closeSensitiveDataModal();

            return;
        }

        $this->closeSensitiveDataModal();
        $collection->update(['sync_enabled' => true]);
        $collection->syncToRemote();
        $this->dispatch('collections-updated');
        $this->toast()->success('Sync enabled', $collection->name);
    }

    public function confirmEnableSyncSanitized(): void
    {
        if (! $this->sensitiveDataCollectionId) {
            $this->closeSensitiveDataModal();

            return;
        }

        $collection = Collection::find($this->sensitiveDataCollectionId);
        if (! $collection) {
            $this->closeSensitiveDataModal();

            return;
        }

        $this->closeSensitiveDataModal();
        $collection->update(['sync_enabled' => true]);
        $collection->syncToRemote(sanitize: true);
        $this->dispatch('collections-updated');
        $this->toast()->success('Sync enabled', $collection->name);
    }

    public function closeSensitiveDataModal(): void
    {
        $this->showSensitiveDataModal = false;
        $this->sensitiveDataCollectionId = null;
        $this->sensitiveDataCollectionName = null;
        $this->sensitiveDataFindings = [];
    }

    // Environment association modal methods
    #[Computed]
    public function environmentModalTarget()
    {
        if (! $this->environmentModalTargetId) {
            return null;
        }

        return $this->environmentModalTargetType === 'folder'
            ? Folder::find($this->environmentModalTargetId)
            : Collection::find($this->environmentModalTargetId);
    }

    #[Computed]
    public function allEnvironmentsForModal()
    {
        return Environment::forWorkspace($this->activeWorkspaceId)
            ->orderByRaw('LOWER(name) ASC')
            ->get();
    }

    public function openEnvironmentModal(string $targetId, string $type = 'collection'): void
    {
        $this->environmentModalTargetType = $type;
        $this->environmentModalTargetId = $targetId;
        $this->showEnvironmentModal = true;
        unset($this->environmentModalTarget, $this->allEnvironmentsForModal);
    }

    public function closeEnvironmentModal(): void
    {
        $this->showEnvironmentModal = false;
        $this->environmentModalTargetType = 'collection';
        $this->environmentModalTargetId = null;
        unset($this->environmentModalTarget, $this->allEnvironmentsForModal);
    }

    #[On('open-env-modal-for-context')]
    public function openEnvModalForContext(string $type, string $id): void
    {
        $this->openEnvironmentModal($id, $type);
    }

    #[Renderless]
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

    #[Renderless]
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

    #[Renderless]
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

    #[Renderless]
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
        $this->selectedEnvironmentId = null;
        $this->showWorkspaceDropdown = false;

        unset($this->workspaces, $this->activeWorkspace);

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
