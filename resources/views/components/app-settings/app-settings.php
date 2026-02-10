<?php

use App\Models\Environment;
use App\Services\DataExportImportService;
use App\Services\PostmanImportService;
use App\Services\RemoteSyncService;
use App\Services\VaultSyncService;
use App\Services\WorkspaceService;
use Beartropy\Ui\Traits\HasToasts;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use HasToasts;
    use WithFileUploads;

    public $show = false;

    public $layout;

    public int $timeout = 30;

    public bool $verifySsl = false;

    public bool $followRedirects = true;

    public string $activeTab = 'general';

    #[Validate('required|file|mimes:json,zip|max:10240')]
    public $importFile;

    public array $importStatus = [];

    public bool $isImporting = false;

    // Export properties
    public string $exportType = 'all';

    public bool $isExporting = false;

    public array $exportStatus = [];

    // Remote sync properties
    public string $remoteProvider = '';

    public string $remoteRepository = '';

    public string $remoteToken = '';

    public string $remoteBranch = 'main';

    public bool $remoteAutoSync = true;

    public string $remoteStatus = '';

    public bool $isTesting = false;

    public bool $isSyncing = false;

    public array $syncResult = [];

    public array $conflicts = [];

    public bool $showConflictModal = false;

    // Vault properties
    public string $vaultProvider = '';

    public string $vaultUrl = '';

    public string $vaultAuthMethod = 'token';

    public string $vaultToken = '';

    public string $vaultRoleId = '';

    public string $vaultSecretId = '';

    public string $vaultNamespace = '';

    public string $vaultMount = 'secret';

    public bool $vaultVerifySsl = true;

    public bool $vaultAutoSync = true;

    public string $vaultStatus = '';

    public bool $isVaultTesting = false;

    public bool $isVaultSyncing = false;

    public array $vaultSyncResult = [];

    public function mount(): void
    {
        $this->layout = get_setting('requests.layout', 'columns');
        $this->timeout = (int) get_setting('requests.timeout', 30);
        $this->verifySsl = (bool) get_setting('requests.verify_ssl', false);
        $this->followRedirects = (bool) get_setting('requests.follow_redirects', true);
        $this->loadRemoteSettings();
        $this->loadVaultSettings();
    }

    private function loadRemoteSettings(): void
    {
        $ws = app(WorkspaceService::class);
        $this->remoteProvider = $ws->getSetting('remote.provider', '') ?? '';
        $this->remoteRepository = $ws->getSetting('remote.repository', '') ?? '';
        $this->remoteToken = $ws->getSetting('remote.token', '') ?? '';
        $this->remoteBranch = $ws->getSetting('remote.branch', 'main') ?? 'main';
        $this->remoteAutoSync = (bool) $ws->getSetting('remote.auto_sync', true);
    }

    private function loadVaultSettings(): void
    {
        $ws = app(WorkspaceService::class);
        $this->vaultProvider = $ws->getSetting('vault.provider', '') ?? '';
        $this->vaultUrl = $ws->getSetting('vault.url', '') ?? '';
        $this->vaultAuthMethod = $ws->getSetting('vault.auth_method', 'token') ?? 'token';
        $this->vaultToken = $ws->getSetting('vault.token', '') ?? '';
        $this->vaultRoleId = $ws->getSetting('vault.role_id', '') ?? '';
        $this->vaultSecretId = $ws->getSetting('vault.secret_id', '') ?? '';
        $this->vaultNamespace = $ws->getSetting('vault.namespace', '') ?? '';
        // Mount contains full engine path (e.g., 'secret/myapp')
        $this->vaultMount = $ws->getSetting('vault.mount', 'secret') ?? 'secret';
        $this->vaultVerifySsl = $ws->getSetting('vault.verify_ssl', true);
        $this->vaultAutoSync = (bool) $ws->getSetting('vault.auto_sync', true);
    }

    public function updatedLayout($value): void
    {
        set_setting('requests.layout', $value);
        $this->dispatch('layout-updated', layout: $value);
    }

    public function updatedTimeout($value): void
    {
        $this->timeout = max(1, min(300, (int) $value));
        set_setting('requests.timeout', $this->timeout);
    }

    public function updatedVerifySsl($value): void
    {
        set_setting('requests.verify_ssl', (bool) $value);
    }

    public function updatedFollowRedirects($value): void
    {
        set_setting('requests.follow_redirects', (bool) $value);
    }

    #[On('open-settings')]
    #[On('native:App\Events\OpenSettingsRequested')]
    public function open(): void
    {
        $this->show = true;
    }

    #[On('toggle-layout')]
    public function toggleLayout(): void
    {
        $this->layout = $this->layout === 'rows' ? 'columns' : 'rows';
        set_setting('requests.layout', $this->layout);
        $this->dispatch('layout-updated', layout: $this->layout);
    }

    public function importData(): void
    {
        $this->validate();

        $this->isImporting = true;
        $this->importStatus = [];

        try {
            // Detect if this is a Vaxtly export by peeking at the JSON
            $isVaxtly = false;
            $extension = strtolower($this->importFile->getClientOriginalExtension());

            if ($extension === 'json') {
                $content = file_get_contents($this->importFile->getPathname());
                $peek = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && ! empty($peek['vaxtly_export'])) {
                    $isVaxtly = true;
                }
            }

            if ($isVaxtly) {
                $workspaceId = app(WorkspaceService::class)->activeId();
                $service = new DataExportImportService;
                $result = $service->import($content, $workspaceId);

                $parts = [];
                if ($result['collections'] > 0) {
                    $parts[] = "{$result['collections']} collection(s)";
                }
                if ($result['environments'] > 0) {
                    $parts[] = "{$result['environments']} environment(s)";
                }
                if ($result['config']) {
                    $parts[] = 'workspace config';
                }

                if (! empty($parts)) {
                    $this->importStatus = [
                        'type' => 'success',
                        'message' => 'Imported '.implode(', ', $parts),
                    ];
                } else {
                    $this->importStatus = [
                        'type' => 'warning',
                        'message' => 'No items were imported. The file may be empty.',
                    ];
                }

                if (! empty($result['errors'])) {
                    $this->importStatus['errors'] = $result['errors'];
                }
            } else {
                $service = new PostmanImportService;
                $result = $service->import($this->importFile);

                $parts = [];
                if ($result['collections'] > 0) {
                    $parts[] = "{$result['collections']} collection(s)";
                }
                if ($result['folders'] > 0) {
                    $parts[] = "{$result['folders']} folder(s)";
                }
                if ($result['requests'] > 0) {
                    $parts[] = "{$result['requests']} request(s)";
                }
                if ($result['environments'] > 0) {
                    $parts[] = "{$result['environments']} environment(s)";
                }

                if (! empty($parts)) {
                    $this->importStatus = [
                        'type' => 'success',
                        'message' => 'Imported '.implode(', ', $parts),
                    ];
                } else {
                    $this->importStatus = [
                        'type' => 'warning',
                        'message' => 'No items were imported. The file may be empty or in an unsupported format.',
                    ];
                }

                if (! empty($result['errors'])) {
                    $this->importStatus['errors'] = $result['errors'];
                }
            }

            $this->dispatch('collections-updated');
            $this->dispatch('environments-updated');

            if (($this->importStatus['type'] ?? '') === 'success') {
                $this->toast()->success('Import complete', $this->importStatus['message']);
            } elseif (($this->importStatus['type'] ?? '') === 'warning') {
                $this->toast()->warning('Import', $this->importStatus['message']);
            }
        } catch (\Exception $e) {
            $this->importStatus = [
                'type' => 'error',
                'message' => 'Import failed: '.$e->getMessage(),
            ];
            $this->toast()->error('Import failed', $e->getMessage(), 0);
        } finally {
            $this->isImporting = false;
            $this->importFile = null;
        }
    }

    public function exportData()
    {
        $this->isExporting = true;
        $this->exportStatus = [];

        try {
            $workspaceId = app(WorkspaceService::class)->activeId();
            $service = new DataExportImportService;

            $data = match ($this->exportType) {
                'collections' => $service->exportCollections($workspaceId),
                'environments' => $service->exportEnvironments($workspaceId),
                'config' => $service->exportConfig($workspaceId),
                default => $service->exportAll($workspaceId),
            };

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $filename = 'vaxtly-export-'.$this->exportType.'-'.now()->format('Y-m-d-His').'.json';

            $this->exportStatus = [
                'type' => 'success',
                'message' => 'Export complete!',
            ];

            return response()->streamDownload(function () use ($json) {
                echo $json;
            }, $filename, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            $this->exportStatus = [
                'type' => 'error',
                'message' => 'Export failed: '.$e->getMessage(),
            ];
        } finally {
            $this->isExporting = false;
        }
    }

    public function resetImport(): void
    {
        $this->importFile = null;
        $this->importStatus = [];
    }

    public function resetExport(): void
    {
        $this->exportStatus = [];
    }

    // Remote sync methods
    public function saveRemoteSettings(): void
    {
        $ws = app(WorkspaceService::class);
        $ws->setSetting('remote.provider', $this->remoteProvider);
        $ws->setSetting('remote.repository', $this->remoteRepository);
        $ws->setSetting('remote.token', $this->remoteToken);
        $ws->setSetting('remote.branch', $this->remoteBranch ?: 'main');
        $ws->setSetting('remote.auto_sync', $this->remoteAutoSync ? '1' : '0');

        $this->remoteStatus = 'Settings saved.';
    }

    public function testConnection(): void
    {
        $this->isTesting = true;
        $this->remoteStatus = '';

        try {
            $this->saveRemoteSettings();

            $service = new RemoteSyncService;
            if ($service->testConnection()) {
                $this->remoteStatus = 'Connection successful!';
            } else {
                $this->remoteStatus = 'Connection failed. Check your credentials and repository.';
            }
        } catch (\Exception $e) {
            $this->remoteStatus = 'Error: '.$e->getMessage();
        } finally {
            $this->isTesting = false;
        }
    }

    public function syncNow(): void
    {
        $this->isSyncing = true;
        $this->syncResult = [];
        $this->conflicts = [];

        try {
            $this->saveRemoteSettings();

            $service = new RemoteSyncService;
            $result = $service->pull();

            $this->syncResult = [
                'pulled' => $result->pulled,
                'errors' => $result->errors,
            ];

            if (! empty($result->conflicts)) {
                $this->conflicts = $result->conflicts;
                $this->showConflictModal = true;
            }

            if ($result->pulled > 0) {
                $this->dispatch('collections-updated');
                $this->toast()->success('Pull complete', $result->pulled.' collection(s) pulled');
            } elseif (empty($result->conflicts) && empty($result->errors)) {
                $this->toast()->info('Already up to date');
            }
        } catch (\Exception $e) {
            $this->syncResult = ['errors' => [$e->getMessage()]];
            $this->toast()->error('Pull failed', $e->getMessage(), 0);
        } finally {
            $this->isSyncing = false;
        }
    }

    public function pushAll(): void
    {
        $this->isSyncing = true;
        $this->syncResult = [];

        try {
            $service = new RemoteSyncService;
            $result = $service->pushAll();

            $this->syncResult = [
                'pushed' => $result->pushed,
                'errors' => $result->errors,
            ];

            if (! empty($result->conflicts)) {
                $this->conflicts = array_merge($this->conflicts, $result->conflicts);
                $this->showConflictModal = true;
            }

            if ($result->pushed > 0) {
                $this->dispatch('collections-updated');
                $this->toast()->success('Push complete', $result->pushed.' collection(s) pushed');
            } elseif (empty($result->conflicts) && empty($result->errors)) {
                $this->toast()->info('Nothing to push');
            }
        } catch (\Exception $e) {
            $this->syncResult = ['errors' => [$e->getMessage()]];
            $this->toast()->error('Push failed', $e->getMessage(), 0);
        } finally {
            $this->isSyncing = false;
        }
    }

    public function resolveConflict(string $collectionId, string $choice): void
    {
        $service = new RemoteSyncService;
        $collection = \App\Models\Collection::find($collectionId);

        if (! $collection) {
            return;
        }

        $conflict = collect($this->conflicts)->firstWhere('collection_id', $collectionId);

        try {
            if ($choice === 'local') {
                $service->forceKeepLocal($collection, $conflict['remote_sha'] ?? null);
            } elseif ($choice === 'remote' && $conflict) {
                $service->forceKeepRemote($collection, $conflict['remote_path'], $conflict['remote_sha']);
            }

            // Remove resolved conflict
            $this->conflicts = array_values(
                array_filter($this->conflicts, fn ($c) => $c['collection_id'] !== $collectionId)
            );

            if (empty($this->conflicts)) {
                $this->showConflictModal = false;
            }

            $this->dispatch('collections-updated');
            $this->toast()->success('Conflict resolved', $collection->name);
        } catch (\Exception $e) {
            $this->syncResult = ['errors' => [$e->getMessage()]];
            $this->toast()->error('Conflict resolution failed', $e->getMessage(), 0);
        }
    }

    // Vault methods
    public function saveVaultSettings(): void
    {
        $ws = app(WorkspaceService::class);
        $ws->setSetting('vault.provider', $this->vaultProvider);
        $ws->setSetting('vault.url', $this->vaultUrl);
        $ws->setSetting('vault.auth_method', $this->vaultAuthMethod);
        $ws->setSetting('vault.token', $this->vaultToken);
        $ws->setSetting('vault.role_id', $this->vaultRoleId);
        $ws->setSetting('vault.secret_id', $this->vaultSecretId);
        $ws->setSetting('vault.namespace', $this->vaultNamespace);
        // Mount contains full engine path (e.g., 'secret/myapp')
        $ws->setSetting('vault.mount', $this->vaultMount ?: 'secret');
        $ws->setSetting('vault.verify_ssl', $this->vaultVerifySsl);
        $ws->setSetting('vault.auto_sync', $this->vaultAutoSync ? '1' : '0');

        $this->vaultStatus = 'Settings saved.';
    }

    public function testVaultConnection(): void
    {
        $this->isVaultTesting = true;
        $this->vaultStatus = '';

        try {
            $this->saveVaultSettings();

            $service = new VaultSyncService;
            if ($service->testConnection()) {
                $this->vaultStatus = 'Connection successful!';
            } else {
                $this->vaultStatus = 'Connection failed. Check your Vault URL and credentials.';
            }
        } catch (\Exception $e) {
            $this->vaultStatus = 'Error: '.$e->getMessage();
        } finally {
            $this->isVaultTesting = false;
        }
    }

    public function pullFromVault(): void
    {
        $this->isVaultSyncing = true;
        $this->vaultSyncResult = [];

        try {
            $this->saveVaultSettings();

            $service = new VaultSyncService;
            $result = $service->pullAll();

            $this->vaultSyncResult = $result;

            if ($result['created'] > 0) {
                $this->dispatch('environments-updated');
                $this->toast()->success('Vault pull complete', $result['created'].' environment(s) pulled');
            } elseif (empty($result['errors'])) {
                $this->toast()->info('Vault already up to date');
            }
        } catch (\Exception $e) {
            $this->vaultSyncResult = ['created' => 0, 'errors' => [$e->getMessage()]];
            $this->toast()->error('Vault pull failed', $e->getMessage(), 0);
        } finally {
            $this->isVaultSyncing = false;
        }
    }

    public function pushAllToVault(): void
    {
        $this->isVaultSyncing = true;
        $this->vaultSyncResult = [];

        try {
            $this->saveVaultSettings();

            $service = new VaultSyncService;
            $pushed = 0;
            $errors = [];

            $workspaceId = app(WorkspaceService::class)->activeId();
            $environments = Environment::where('vault_synced', true)->forWorkspace($workspaceId)->get();
            foreach ($environments as $environment) {
                try {
                    $variables = $environment->variables ?? [];
                    $service->pushVariables($environment, $variables);
                    $pushed++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to push '{$environment->name}': ".$e->getMessage();
                }
            }

            $this->vaultSyncResult = ['pushed' => $pushed, 'errors' => $errors];

            if ($pushed > 0) {
                $this->dispatch('environments-updated');
                $this->toast()->success('Vault push complete', $pushed.' environment(s) pushed');
            } elseif (empty($errors)) {
                $this->toast()->info('Nothing to push to Vault');
            }
        } catch (\Exception $e) {
            $this->vaultSyncResult = ['pushed' => 0, 'errors' => [$e->getMessage()]];
            $this->toast()->error('Vault push failed', $e->getMessage(), 0);
        } finally {
            $this->isVaultSyncing = false;
        }
    }

    #[On('workspace-switched')]
    public function onWorkspaceSwitched(): void
    {
        app(WorkspaceService::class)->clearCache();
        $this->loadRemoteSettings();
        $this->loadVaultSettings();
        $this->remoteStatus = '';
        $this->syncResult = [];
        $this->vaultStatus = '';
        $this->vaultSyncResult = [];
        $this->exportStatus = [];
    }

    public function close(): void
    {
        $this->show = false;
        $this->activeTab = 'general';
        $this->resetImport();
        $this->resetExport();
        $this->remoteStatus = '';
        $this->syncResult = [];
        $this->vaultStatus = '';
        $this->vaultSyncResult = [];
    }
};
