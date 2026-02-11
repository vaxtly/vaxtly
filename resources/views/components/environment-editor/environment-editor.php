<?php

use App\Models\Environment;
use App\Services\VaultSyncService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public ?string $environmentId = null;

    public ?string $activeTabId = null;

    public array $tabStates = [];

    public string $name = '';

    /**
     * Original name when environment was loaded, used to detect renames.
     */
    public string $originalName = '';

    public array $variables = [];

    public bool $isActive = false;

    public bool $isVaultSynced = false;

    public string $vaultError = '';

    public function mount(): void
    {
        //
    }

    #[On('workspace-switched')]
    public function onWorkspaceSwitched(): void
    {
        $this->environmentId = null;
        $this->name = '';
        $this->originalName = '';
        $this->variables = [];
        $this->isActive = false;
        $this->isVaultSynced = false;
        $this->vaultError = '';
    }

    #[On('switch-tab')]
    public function switchTab(string $tabId, string $type = 'request', ?string $requestId = null, ?string $environmentId = null): void
    {
        // Save current tab state before switching away
        if ($this->activeTabId && $this->environmentId) {
            $this->tabStates[$this->activeTabId] = $this->getCurrentState();
        }

        // Not an environment tab — nothing more to do here
        if ($type !== 'environment' || ! $environmentId) {
            return;
        }

        $this->activeTabId = $tabId;

        // Check if we have cached state for this tab
        if (isset($this->tabStates[$tabId])) {
            $this->restoreState($this->tabStates[$tabId]);
        } else {
            $this->loadEnvironment($environmentId);
        }
    }

    #[On('close-tab')]
    public function closeTab(string $tabId): void
    {
        unset($this->tabStates[$tabId]);
    }

    private function getCurrentState(): array
    {
        return [
            'environmentId' => $this->environmentId,
            'name' => $this->name,
            'originalName' => $this->originalName,
            'variables' => $this->variables,
            'isActive' => $this->isActive,
            'isVaultSynced' => $this->isVaultSynced,
            'vaultError' => $this->vaultError,
        ];
    }

    private function restoreState(array $state): void
    {
        foreach ($state as $key => $value) {
            $this->$key = $value;
        }
    }

    public function loadEnvironment(string $environmentId): void
    {
        $environment = Environment::find($environmentId);

        if (! $environment) {
            return;
        }

        $this->environmentId = $environment->id;
        $this->name = $environment->name;
        $this->originalName = $environment->name;
        $this->isActive = $environment->is_active;
        $this->isVaultSynced = $environment->vault_synced;
        $this->vaultError = '';

        if ($environment->vault_synced) {
            try {
                $this->variables = $environment->getEffectiveVariables();
            } catch (\Exception $e) {
                $this->vaultError = 'Failed to load variables from Vault: '.$e->getMessage();
                $this->variables = [];
            }
        } else {
            $this->variables = $environment->variables ?? [];
        }

        if (empty($this->variables)) {
            $this->variables = [['key' => '', 'value' => '', 'enabled' => true]];
        }
    }

    public function addVariable(): void
    {
        $this->variables[] = ['key' => '', 'value' => '', 'enabled' => true];
    }

    public function removeVariable(int $index): void
    {
        unset($this->variables[$index]);
        $this->variables = array_values($this->variables);

        if (empty($this->variables)) {
            $this->variables = [['key' => '', 'value' => '', 'enabled' => true]];
        }

        $this->saveEnvironment();
    }

    public function toggleVariable(int $index): void
    {
        $this->variables[$index]['enabled'] = ! ($this->variables[$index]['enabled'] ?? true);
        $this->saveEnvironment();
    }

    public function toggleActive(): void
    {
        if (! $this->environmentId) {
            return;
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment) {
            return;
        }

        if ($this->isActive) {
            $environment->deactivate();
            $this->isActive = false;
        } else {
            $environment->activate();
            $this->isActive = true;
        }

        $this->dispatch('environments-updated');
        $this->dispatch('active-environment-changed');
    }

    public function saveEnvironment(): void
    {
        if (! $this->environmentId) {
            return;
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment) {
            return;
        }

        $filteredVariables = array_values(array_filter($this->variables, function ($var) {
            return ! empty($var['key']);
        }));

        if ($environment->vault_synced) {
            try {
                $vaultService = app(VaultSyncService::class);

                // Check if name changed - migrate secrets in Vault
                $nameChanged = $this->originalName !== $this->name && ! empty($this->originalName);
                if ($nameChanged) {
                    $oldPath = $environment->vault_path ?? $vaultService->buildPath($environment);
                    $newPath = \Illuminate\Support\Str::slug($this->name);

                    if ($oldPath !== $newPath) {
                        $vaultService->migrateEnvironment($environment, $oldPath, $newPath);
                        $environment->vault_path = $newPath;
                    }
                }

                // Push variables to Vault
                $vaultService->pushVariables($environment, $filteredVariables);
                $this->vaultError = '';
            } catch (\Exception $e) {
                $this->vaultError = 'Failed to save variables to Vault: '.$e->getMessage();

                return;
            }

            // For vault-synced envs, don't store variables in DB (clear them)
            $environment->update([
                'name' => $this->name,
                'vault_path' => $environment->vault_path,
                'variables' => [],
            ]);

            $this->originalName = $this->name;
        } else {
            $environment->update([
                'name' => $this->name,
                'variables' => $filteredVariables,
            ]);

            $this->originalName = $this->name;
        }

        $this->dispatch('environments-updated');
        $this->dispatch('environment-name-updated', environmentId: $this->environmentId, name: $this->name);
    }

    public function refreshFromVault(): void
    {
        if (! $this->environmentId) {
            return;
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment || ! $environment->vault_synced) {
            return;
        }

        $vaultService = app(VaultSyncService::class);
        $vaultService->clearCache($environment);

        $this->vaultError = '';

        try {
            $this->variables = $environment->getEffectiveVariables();
        } catch (\Exception $e) {
            $this->vaultError = 'Failed to refresh from Vault: '.$e->getMessage();
            $this->variables = [];
        }

        if (empty($this->variables)) {
            $this->variables = [['key' => '', 'value' => '', 'enabled' => true]];
        }
    }

    public function updatedName(): void
    {
        $this->saveEnvironment();
    }

    public function updatedVariables(): void
    {
        $this->saveEnvironment();
    }

    /**
     * Get the computed Vault path for the current environment.
     */
    public function getVaultPathProperty(): string
    {
        if (! $this->environmentId) {
            return '';
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment) {
            return '';
        }

        $vaultService = app(VaultSyncService::class);

        return $vaultService->buildPath($environment);
    }

    /**
     * Push current environment variables to Vault (one-time push).
     */
    public function pushToVault(): void
    {
        if (! $this->environmentId) {
            return;
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment) {
            return;
        }

        try {
            $vaultService = app(VaultSyncService::class);
            if (! $vaultService->isConfigured()) {
                $this->vaultError = 'Vault is not configured. Please configure it in Settings > Vault.';

                return;
            }

            $filteredVariables = array_values(array_filter($this->variables, fn ($var) => ! empty($var['key'])));
            $vaultService->pushVariables($environment, $filteredVariables);
            $this->vaultError = '';
            $this->dispatch('vault-push-success');
        } catch (\Exception $e) {
            $this->vaultError = 'Failed to push variables to Vault: '.$e->getMessage();
        }
    }

    /**
     * Pull variables from Vault for this environment (one-time pull).
     */
    public function pullFromVaultOnce(): void
    {
        if (! $this->environmentId) {
            return;
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment) {
            return;
        }

        try {
            $vaultService = app(VaultSyncService::class);
            if (! $vaultService->isConfigured()) {
                $this->vaultError = 'Vault is not configured. Please configure it in Settings > Vault.';

                return;
            }

            $vaultService->clearCache($environment);
            $path = $vaultService->buildPath($environment);
            $provider = $vaultService->getProvider();
            $secrets = $provider->getSecrets($path);

            if ($secrets === null) {
                $this->vaultError = "No secrets found at path: {$path}";
                $this->variables = [['key' => '', 'value' => '', 'enabled' => true]];

                return;
            }

            $this->variables = [];
            foreach ($secrets as $key => $value) {
                $this->variables[] = [
                    'key' => $key,
                    'value' => (string) $value,
                    'enabled' => true,
                ];
            }

            if (empty($this->variables)) {
                $this->variables = [['key' => '', 'value' => '', 'enabled' => true]];
            }

            $this->vaultError = '';
            $this->dispatch('vault-pull-success');
        } catch (\Exception $e) {
            $this->vaultError = 'Failed to pull variables from Vault: '.$e->getMessage();
        }
    }

    /**
     * Toggle Vault sync on/off for this environment.
     */
    public function toggleVaultSync(): void
    {
        if (! $this->environmentId) {
            return;
        }

        $environment = Environment::find($this->environmentId);
        if (! $environment) {
            return;
        }

        try {
            $vaultService = app(VaultSyncService::class);
            if (! $vaultService->isConfigured()) {
                $this->vaultError = 'Vault is not configured. Please configure it in Settings > Vault.';

                return;
            }

            if ($this->isVaultSynced) {
                // Disable Vault sync
                $environment->update([
                    'vault_synced' => false,
                    'vault_path' => null,
                ]);
                $this->isVaultSynced = false;
            } else {
                // Enable Vault sync
                $path = $vaultService->buildPath($environment);

                // Push current variables to Vault before clearing from DB
                $filteredVariables = array_values(array_filter($this->variables, fn ($var) => ! empty($var['key'])));
                if (! empty($filteredVariables)) {
                    $vaultService->pushVariables($environment, $filteredVariables);
                }

                // Clear variables from DB since they're now in Vault
                $environment->update([
                    'vault_synced' => true,
                    'vault_path' => $path,
                    'variables' => [],
                ]);
                $this->isVaultSynced = true;
            }

            $this->vaultError = '';
            $this->dispatch('environments-updated');
        } catch (\Exception $e) {
            $this->vaultError = 'Failed to toggle Vault sync: '.$e->getMessage();
        }
    }
};
