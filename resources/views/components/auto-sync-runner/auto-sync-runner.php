<?php

use App\Services\RemoteSyncService;
use App\Services\VaultSyncService;
use App\Services\WorkspaceService;
use Beartropy\Ui\Traits\HasToasts;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use HasToasts;

    #[On('run-auto-sync')]
    public function runAutoSync(): void
    {
        $this->autoSyncOnStart();
        $this->autoVaultSyncOnStart();
    }

    private function autoSyncOnStart(): void
    {
        if (! app(WorkspaceService::class)->getSetting('remote.auto_sync')) {
            return;
        }

        try {
            $syncService = new RemoteSyncService;
            if (! $syncService->isConfigured()) {
                return;
            }

            $result = $syncService->pull();
            if ($result->pulled > 0) {
                $this->dispatch('collections-updated');
            }
        } catch (\Exception $e) {
            $this->toast()->warning('Git sync failed', $e->getMessage());
        }
    }

    private function autoVaultSyncOnStart(): void
    {
        if (! app(WorkspaceService::class)->getSetting('vault.auto_sync', true)) {
            return;
        }

        try {
            $vaultService = new VaultSyncService;
            if (! $vaultService->isConfigured()) {
                return;
            }

            $result = $vaultService->pullAll();

            if ($result['created'] > 0) {
                $this->dispatch('environments-updated');
            }
        } catch (\Exception $e) {
            $this->toast()->warning('Vault sync failed', $e->getMessage());
        }
    }
};
