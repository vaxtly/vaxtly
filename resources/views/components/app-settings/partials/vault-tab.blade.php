{{-- Vault Tab --}}
<div class="space-y-4 max-h-[60vh] overflow-y-auto beartropy-thin-scrollbar pr-2">
    {{-- Section Header --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">Vault Integration</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Sync environment variables with HashiCorp Vault</p>
        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">Workspace: {{ app(\App\Services\WorkspaceService::class)->active()->name }}</p>
    </div>

    {{-- Provider --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
        <div class="w-full">
            <x-beartropy-ui::select
                wire:model.live="vaultProvider"
                :options="['' => 'Select provider...', 'hashicorp' => 'HashiCorp Vault']"
                :clearable="false"
                :searchable="false"
                sm
            />
        </div>
    </div>

    {{-- Vault URL --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Vault URL</label>
        <x-beartropy-ui::input
            wire:model="vaultUrl"
            placeholder="https://vault.example.com"
            sm
        />
    </div>

    {{-- Auth Method --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Auth Method</label>
        <div class="w-full">
            <x-beartropy-ui::select
                wire:model.live="vaultAuthMethod"
                :options="['token' => 'Token', 'approle' => 'AppRole']"
                :clearable="false"
                :searchable="false"
                sm
            />
        </div>
    </div>

    {{-- Token (shown when auth method is token) --}}
    @if($vaultAuthMethod === 'token')
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Token</label>
            <x-beartropy-ui::input
                wire:model="vaultToken"
                type="password"
                placeholder="hvs...."
                sm
            />
        </div>
    @endif

    {{-- AppRole credentials (shown when auth method is approle) --}}
    @if($vaultAuthMethod === 'approle')
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Role ID</label>
            <x-beartropy-ui::input
                wire:model="vaultRoleId"
                placeholder="role-id"
                sm
            />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Secret ID</label>
            <x-beartropy-ui::input
                wire:model="vaultSecretId"
                type="password"
                placeholder="secret-id"
                sm
            />
        </div>
    @endif

    {{-- Namespace --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Namespace <span class="text-gray-400">(optional, enterprise only)</span></label>
        <x-beartropy-ui::input
            wire:model="vaultNamespace"
            placeholder="admin/team"
            sm
        />
    </div>

    {{-- Engine Full Path --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Engine Full Path</label>
        <x-beartropy-ui::input
            wire:model="vaultMount"
            placeholder="secret/myapp"
            sm
        />
        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">Full path to KV engine. Secrets stored at: {engine_path}/data/{environment_name}</p>
    </div>

    {{-- Verify SSL --}}
    <div class="flex items-center justify-between">
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Verify SSL</label>
            <p class="text-[10px] text-gray-400 dark:text-gray-500">Disable for self-signed certificates</p>
        </div>
        <x-beartropy-ui::toggle wire:model.live="vaultVerifySsl" sm />
    </div>

    {{-- Auto-sync toggle --}}
    <div class="flex items-center justify-between">
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Auto-sync on start</label>
            <p class="text-[10px] text-gray-400 dark:text-gray-500">Pull vault environments when the app loads</p>
        </div>
        <x-beartropy-ui::toggle wire:model.live="vaultAutoSync" sm />
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Status Message --}}
    @if(!empty($vaultStatus))
        <div class="p-3 rounded-lg text-sm {{ str_contains($vaultStatus, 'successful') || str_contains($vaultStatus, 'saved') ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300' : (str_contains($vaultStatus, 'Error') || str_contains($vaultStatus, 'failed') ? 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300') }}">
            {{ $vaultStatus }}
        </div>
    @endif

    {{-- Vault Sync Result --}}
    @if(!empty($vaultSyncResult))
        <div class="p-3 rounded-lg text-sm {{ empty($vaultSyncResult['errors'] ?? []) ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' }}">
            @if(isset($vaultSyncResult['created']) && $vaultSyncResult['created'] > 0)
                <p>Pulled {{ $vaultSyncResult['created'] }} environment(s) from Vault</p>
            @endif
            @if(isset($vaultSyncResult['pushed']) && $vaultSyncResult['pushed'] > 0)
                <p>Pushed {{ $vaultSyncResult['pushed'] }} environment(s) to Vault</p>
            @endif
            @if((($vaultSyncResult['created'] ?? 0) === 0) && (($vaultSyncResult['pushed'] ?? 0) === 0) && empty($vaultSyncResult['errors'] ?? []))
                <p>Everything is up to date.</p>
            @endif
            @if(!empty($vaultSyncResult['errors'] ?? []))
                <ul class="mt-1 text-xs list-disc list-inside">
                    @foreach($vaultSyncResult['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-2">
        <x-beartropy-ui::button
            wire:click="saveVaultSettings"
            tint
            sm
        >
            Save
        </x-beartropy-ui::button>

        <x-beartropy-ui::button
            wire:click="testVaultConnection"
            wire:loading.attr="disabled"
            wire:target="testVaultConnection"
            tint
            sm
        >
            <span wire:loading.remove wire:target="testVaultConnection">Test Connection</span>
            <span wire:loading wire:target="testVaultConnection" class="flex items-center gap-1">
                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Testing...
            </span>
        </x-beartropy-ui::button>
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Sync Actions --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">Sync Actions</p>
        <div class="flex gap-2">
            <x-beartropy-ui::button
                wire:click="pullFromVault"
                wire:loading.attr="disabled"
                wire:target="pullFromVault, pushAllToVault"
                primary
                sm
            >
                <span wire:loading.remove wire:target="pullFromVault">Pull from Vault</span>
                <span wire:loading wire:target="pullFromVault" class="flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Pulling...
                </span>
            </x-beartropy-ui::button>

            <x-beartropy-ui::button
                wire:click="pushAllToVault"
                wire:loading.attr="disabled"
                wire:target="pullFromVault, pushAllToVault"
                tint
                sm
            >
                <span wire:loading.remove wire:target="pushAllToVault">Push All to Vault</span>
                <span wire:loading wire:target="pushAllToVault" class="flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Pushing...
                </span>
            </x-beartropy-ui::button>
        </div>
    </div>

    {{-- Help Text --}}
    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">How it works:</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <li class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Vault-synced environments store variables in Vault instead of the database
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Pull discovers secrets in Vault and creates matching environments
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                </svg>
                Push uploads vault-synced environment variables to Vault
            </li>
        </ul>
    </div>
</div>
