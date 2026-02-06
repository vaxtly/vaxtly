<div class="h-full flex flex-col min-h-0 overflow-hidden">
    @if($environmentId)
        {{-- El contenedor con scroll --}}
        <div class="flex-1 overflow-auto beartropy-thin-scrollbar">
            <div class="p-6 space-y-4">
                {{-- Environment Name & Actions --}}
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <x-beartropy-ui::input
                            wire:model.blur="name"
                            label="Environment Name"
                            placeholder="My Environment"
                        />
                    </div>
                    @if($isVaultSynced)
                        <span class="mb-1.5 px-2 py-1 text-[10px] font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400 rounded">
                            Vault
                        </span>
                    @endif
                    <x-beartropy-ui::button tint
                        wire:click="toggleActive"
                        class="mb-0.5"
                        :emerald="$isActive"
                        :gray="!$isActive"
                    >
                        @if($isActive)
                            <x-bt-icon name="check-circle" class="w-4 h-4 mr-1" />
                            Active
                        @else
                            <x-bt-icon name="circle-stack" class="w-4 h-4 mr-1" />
                            Set Active
                        @endif
                    </x-beartropy-ui::button>
                </div>

                {{-- Vault Error Banner --}}
                @if(!empty($vaultError))
                    <div class="p-3 rounded-lg text-sm bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <p>{{ $vaultError }}</p>
                        </div>
                    </div>
                @endif

                {{-- Vault Actions Section --}}
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span class="text-sm font-medium text-purple-800 dark:text-purple-300">Vault Integration</span>
                        </div>
                        <x-beartropy-ui::button
                            wire:click="toggleVaultSync"
                            wire:loading.attr="disabled"
                            wire:target="toggleVaultSync"
                            :primary="!$isVaultSynced"
                            :tint="$isVaultSynced"
                            :red="$isVaultSynced"
                            sm
                        >
                            <span wire:loading.remove wire:target="toggleVaultSync">
                                {{ $isVaultSynced ? 'Disable Sync' : 'Enable Sync' }}
                            </span>
                            <span wire:loading wire:target="toggleVaultSync" class="flex items-center gap-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </x-beartropy-ui::button>
                    </div>

                    @if($isVaultSynced)
                        <p class="text-[10px] text-purple-600 dark:text-purple-400 mb-2">
                            Path: <code class="bg-purple-100 dark:bg-purple-800 px-1 rounded">{{ $this->vaultPath }}</code>
                        </p>
                    @endif

                    <div class="flex gap-2">
                        <x-beartropy-ui::button tint wire:click="pullFromVaultOnce" wire:loading.attr="disabled" wire:target="pullFromVaultOnce, pushToVault" sm>
                            <span wire:loading.remove wire:target="pullFromVaultOnce" class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Pull from Vault
                            </span>
                            <span wire:loading wire:target="pullFromVaultOnce" class="flex items-center gap-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Pulling...
                            </span>
                        </x-beartropy-ui::button>

                        <x-beartropy-ui::button tint wire:click="pushToVault" wire:loading.attr="disabled" wire:target="pullFromVaultOnce, pushToVault" sm>
                            <span wire:loading.remove wire:target="pushToVault" class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Push to Vault
                            </span>
                            <span wire:loading wire:target="pushToVault" class="flex items-center gap-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Pushing...
                            </span>
                        </x-beartropy-ui::button>
                    </div>
                </div>

                {{-- Variables Section --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Variables</h3>
                        @if($isVaultSynced)
                            <x-beartropy-ui::button tint wire:click="refreshFromVault" wire:loading.attr="disabled" wire:target="refreshFromVault" sm>
                                <span wire:loading.remove wire:target="refreshFromVault" class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    Refresh from Vault
                                </span>
                                <span wire:loading wire:target="refreshFromVault" class="flex items-center gap-1">
                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Refreshing...
                                </span>
                            </x-beartropy-ui::button>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Use <code class="bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">@{{variableName}}</code> syntax.
                    </p>

                    <div class="space-y-2">
                        @foreach($variables as $index => $variable)
                            <div wire:key="variable-{{ $index }}" class="flex gap-2 items-center">
                                <button wire:click="toggleVariable({{ $index }})" class="w-8 flex items-center justify-center p-1 rounded transition-colors cursor-pointer {{ ($variable['enabled'] ?? true) ? 'text-green-500' : 'text-gray-300 dark:text-gray-600' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($variable['enabled'] ?? true)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        @endif
                                    </svg>
                                </button>
                                <div class="flex-1">
                                    <x-beartropy-ui::input wire:model.live.debounce.500ms="variables.{{ $index }}.key" wire:change="saveEnvironment" placeholder="key" :disabled="!($variable['enabled'] ?? true)" />
                                </div>
                                <div class="flex-1">
                                    <x-beartropy-ui::input wire:model.live.debounce.500ms="variables.{{ $index }}.value" wire:change="saveEnvironment" placeholder="value" :disabled="!($variable['enabled'] ?? true)" />
                                </div>
                                <x-beartropy-ui::button tint wire:click="removeVariable({{ $index }})" red glass class="w-10">
                                    <x-bt-icon name="trash" />
                                </x-beartropy-ui::button>
                            </div>
                        @endforeach
                        <x-beartropy-ui::button tint wire:click="addVariable" link sm>+ Add Variable</x-beartropy-ui::button>
                    </div>
                </div>

                {{-- Usage Example --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mt-6">
                    <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Usage Example</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">@{{baseUrl}}/users</code>
                    </p>
                </div>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="flex-1 flex items-center justify-center min-h-0">
            <div class="text-center text-gray-400 dark:text-gray-500">
                <p class="text-sm">Select an environment to edit</p>
            </div>
        </div>
    @endif
</div>