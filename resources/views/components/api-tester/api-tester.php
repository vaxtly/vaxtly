<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use App\Services\RemoteSyncService;
use App\Services\WorkspaceService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $collections;

    public $selectedCollectionId = null;

    public $selectedRequestId = null;

    public string $viewMode = 'collections';

    public ?string $selectedEnvironmentId = null;

    public array $openTabs = [];

    public ?string $activeTabId = null;

    public bool $showHelpModal = false;

    public bool $envLocked = false;

    public string $activeWorkspaceId = '';

    public function mount(): void
    {
        $this->activeWorkspaceId = app(WorkspaceService::class)->activeId();
        $this->loadCollections();
        $this->autoSyncOnStart();
    }

    private function autoSyncOnStart(): void
    {
        if (! app(WorkspaceService::class)->getSetting('remote.auto_sync')) {
            return;
        }

        $syncService = new RemoteSyncService;
        if (! $syncService->isConfigured()) {
            return;
        }

        try {
            $result = $syncService->pull();
            if ($result->pulled > 0) {
                $this->loadCollections();
                $this->dispatch('collections-updated');
            }
        } catch (\Exception) {
            // Silently fail on auto-sync
        }
    }

    #[On('open-help-modal')]
    public function openHelpModal(): void
    {
        if (config('nativephp-internal.running')) {
            \Native\Desktop\Facades\Window::open('docs')
                ->route('docs')
                ->width(960)
                ->height(700)
                ->minWidth(700)
                ->minHeight(500)
                ->title('Vaxtly User Guide')
                ->hideMenu()
                ->rememberState();

            return;
        }

        $this->showHelpModal = true;
    }

    #[Computed]
    public function environments()
    {
        return Environment::ordered()->forWorkspace($this->activeWorkspaceId)->get();
    }

    #[Computed]
    public function activeEnvironmentId()
    {
        return Environment::active()->forWorkspace($this->activeWorkspaceId)->first()?->id;
    }

    #[Computed]
    public function currentCollectionId(): ?string
    {
        $tab = collect($this->openTabs)->firstWhere('id', $this->activeTabId);

        return $tab['collectionId'] ?? null;
    }

    #[Computed]
    public function currentRequestId(): ?string
    {
        $tab = collect($this->openTabs)->firstWhere('id', $this->activeTabId);

        return $tab['requestId'] ?? null;
    }

    #[Computed]
    public function currentFolderId(): ?string
    {
        $tab = collect($this->openTabs)->firstWhere('id', $this->activeTabId);

        return $tab['folderId'] ?? null;
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'collections' ? 'environments' : 'collections';
    }

    #[On('switch-to-environments')]
    public function switchToEnvironments(): void
    {
        $this->viewMode = 'environments';
    }

    #[On('switch-to-collections')]
    public function switchToCollections(): void
    {
        $this->viewMode = 'collections';
    }

    public function loadCollections(): void
    {
        $this->collections = Collection::with('requests')->forWorkspace($this->activeWorkspaceId)->ordered()->get();
    }

    #[On('request-saved')]
    #[On('collections-updated')]
    public function refreshCollections(): void
    {
        $this->loadCollections();
    }

    #[On('environments-updated')]
    #[On('active-environment-changed')]
    public function refreshEnvironments(): void
    {
        unset($this->environments, $this->activeEnvironmentId);
    }

    public function toggleEnvLock(): void
    {
        $this->envLocked = ! $this->envLocked;
    }

    public function setActiveEnvironment(?string $environmentId): void
    {
        if (! $environmentId) {
            Environment::forWorkspace($this->activeWorkspaceId)->update(['is_active' => false]);
        } else {
            $environment = Environment::find($environmentId);
            $environment?->activate();
        }

        unset($this->environments, $this->activeEnvironmentId);
        $this->dispatch('active-environment-changed');
    }

    public function createCollection(): void
    {
        $collection = Collection::create([
            'name' => 'New Collection',
            'description' => 'Description',
            'order' => Collection::max('order') + 1,
            'workspace_id' => $this->activeWorkspaceId,
        ]);

        $this->loadCollections();
        $this->selectedCollectionId = $collection->id;
        $this->dispatch('collections-updated');
    }

    public function deleteCollection(string $collectionId): void
    {
        $collection = Collection::find($collectionId);

        if ($collection) {
            // Delete from remote if sync was enabled
            if ($collection->sync_enabled && $collection->remote_sha) {
                try {
                    $syncService = new RemoteSyncService;
                    $syncService->deleteRemoteCollection($collection);
                } catch (\Exception) {
                    // Continue with local delete even if remote fails
                }
            }

            $collection->delete();
        }

        $this->loadCollections();
        $this->dispatch('collections-updated');

        if ($this->selectedCollectionId === $collectionId) {
            $this->selectedCollectionId = null;
            $this->selectedRequestId = null;
        }
    }

    public function createRequest(string $collectionId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $request = Request::create([
            'collection_id' => $collectionId,
            'name' => 'New Request',
            'url' => '',
            'method' => 'GET',
            'headers' => [],
            'query_params' => [],
            'body' => '',
            'body_type' => 'none',
            'order' => $collection->requests()->max('order') + 1,
        ]);

        $this->loadCollections();
        $this->selectedCollectionId = $collectionId;
        $this->selectedRequestId = $request->id;
        $this->dispatch('collections-updated');
        $this->openTab($request->id);
    }

    #[On('open-request-tab')]
    public function openTab(string $requestId): void
    {
        // Check if already open
        $existing = collect($this->openTabs)->firstWhere('requestId', $requestId);
        if ($existing) {
            $this->activeTabId = $existing['id'];
            $this->autoActivateEnvironment($existing['collectionId'] ?? null, $existing['folderId'] ?? null);
            $this->dispatch('switch-tab', tabId: $existing['id'], requestId: $requestId);

            return;
        }

        // Load request info for tab display
        $request = Request::find($requestId);
        if (! $request) {
            return;
        }

        $tabId = (string) Str::uuid();
        $this->openTabs[] = [
            'id' => $tabId,
            'requestId' => $requestId,
            'collectionId' => $request->collection_id,
            'folderId' => $request->folder_id,
            'name' => $request->name,
            'method' => $request->method,
        ];
        $this->activeTabId = $tabId;
        $this->autoActivateEnvironment($request->collection_id, $request->folder_id);
        $this->dispatch('switch-tab', tabId: $tabId, requestId: $requestId);
    }

    public function switchTab(string $tabId): void
    {
        $tab = collect($this->openTabs)->firstWhere('id', $tabId);
        if ($tab) {
            $this->activeTabId = $tabId;
            $this->autoActivateEnvironment($tab['collectionId'] ?? null, $tab['folderId'] ?? null);
            $this->dispatch('switch-tab', tabId: $tabId, requestId: $tab['requestId']);
        }
    }

    public function closeTab(string $tabId): void
    {
        $index = collect($this->openTabs)->search(fn ($t) => $t['id'] === $tabId);
        if ($index !== false) {
            array_splice($this->openTabs, $index, 1);
            $this->dispatch('close-tab', tabId: $tabId);

            // Switch to adjacent tab if closing active
            if ($this->activeTabId === $tabId) {
                $newActive = $this->openTabs[$index] ?? $this->openTabs[$index - 1] ?? null;
                $this->activeTabId = $newActive['id'] ?? null;
                if ($newActive) {
                    $this->dispatch('switch-tab', tabId: $newActive['id'], requestId: $newActive['requestId']);
                }
            }
        }
    }

    #[On('tab-name-updated')]
    public function updateTabName(string $requestId, string $name, string $method): void
    {
        foreach ($this->openTabs as &$tab) {
            if ($tab['requestId'] === $requestId) {
                $tab['name'] = $name;
                $tab['method'] = $method;
                break;
            }
        }
    }

    public function autoActivateEnvironment(?string $collectionId, ?string $folderId = null): void
    {
        if ($this->envLocked || ! $collectionId) {
            return;
        }

        $defaultEnvironmentId = null;

        // Check folder tree first
        if ($folderId) {
            $folder = Folder::find($folderId);
            $envFolder = $folder?->resolveEnvironmentFolder();
            if ($envFolder?->default_environment_id) {
                $defaultEnvironmentId = $envFolder->default_environment_id;
            }
        }

        // Fall back to collection default
        if (! $defaultEnvironmentId) {
            $collection = Collection::find($collectionId);
            $defaultEnvironmentId = $collection?->default_environment_id;
        }

        if (! $defaultEnvironmentId) {
            return;
        }

        $environment = Environment::find($defaultEnvironmentId);
        if ($environment) {
            $environment->activate();
            $this->refreshEnvironments();
            $this->dispatch('active-environment-changed');
        }
    }

    #[On('workspace-switched')]
    public function onWorkspaceSwitched(string $workspaceId): void
    {
        $this->activeWorkspaceId = $workspaceId;
        $this->openTabs = [];
        $this->activeTabId = null;
        $this->selectedCollectionId = null;
        $this->selectedRequestId = null;
        $this->selectedEnvironmentId = null;
        $this->envLocked = false;
        $this->loadCollections();
        unset($this->environments, $this->activeEnvironmentId);
    }

    public function addCollectionEnvironment(string $collectionId, string $environmentId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $collection->addEnvironment($environmentId);
        $collection->markDirty();
        $this->loadCollections();
    }

    public function removeCollectionEnvironment(string $collectionId, string $environmentId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $collection->removeEnvironment($environmentId);
        $collection->markDirty();
        $this->loadCollections();
    }

    public function setCollectionDefaultEnvironment(string $collectionId, ?string $environmentId): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $collection->setDefaultEnvironment($environmentId);
        $collection->markDirty();
        $this->loadCollections();
    }
};
