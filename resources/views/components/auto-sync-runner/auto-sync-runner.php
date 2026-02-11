<?php

use App\Services\RemoteSyncService;
use App\Services\VaultSyncService;
use App\Services\WorkspaceService;
use App\Support\BootLogger;
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
            BootLogger::log('auto-sync: git sync skipped (disabled)');

            return;
        }

        try {
            $syncService = app(RemoteSyncService::class);
            if (! $syncService->isConfigured()) {
                BootLogger::log('auto-sync: git sync skipped (not configured)');

                return;
            }

            BootLogger::log('auto-sync: git pull started');
            $result = $syncService->pull();
            BootLogger::log("auto-sync: git pull done (pulled={$result->pulled})");

            if ($result->pulled > 0) {
                $this->dispatch('collections-updated');
            }
        } catch (\Exception $e) {
            BootLogger::log('auto-sync: git pull failed — '.$e->getMessage());
            $this->toast()->warning('Git sync failed', $e->getMessage());
        }
    }

    private function autoVaultSyncOnStart(): void
    {
        if (! app(WorkspaceService::class)->getSetting('vault.auto_sync', true)) {
            BootLogger::log('auto-sync: vault sync skipped (disabled)');

            return;
        }

        try {
            $vaultService = app(VaultSyncService::class);
            if (! $vaultService->isConfigured()) {
                BootLogger::log('auto-sync: vault sync skipped (not configured)');

                return;
            }

            BootLogger::log('auto-sync: vault pull started');
            $result = $vaultService->pullAll();
            BootLogger::log("auto-sync: vault pull done (created={$result['created']})");

            if ($result['created'] > 0) {
                $this->dispatch('environments-updated');
            }
        } catch (\Exception $e) {
            BootLogger::log('auto-sync: vault pull failed — '.$e->getMessage());
            $this->toast()->warning('Vault sync failed', $e->getMessage());
        }
    }
};
