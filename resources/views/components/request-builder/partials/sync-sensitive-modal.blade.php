{{-- Sync Sensitive Data Warning Modal --}}
@if($showSyncSensitiveModal && $pendingSyncFindings)
    <x-beartropy-ui::modal
        wire:model="showSyncSensitiveModal"
        styled
        max-width="md"
    >
        <x-slot:title>
            Sensitive Data Detected
        </x-slot:title>

        <div class="space-y-4">
            {{-- Warning Banner --}}
            <div class="flex items-start gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <svg class="w-5 h-5 shrink-0 text-amber-500 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    This request contains values that look like credentials. Synced data is stored as <strong>readable YAML</strong> in your Git repository.
                </p>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                Consider using <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">@{{variable}}</code> references instead of plain-text values.
            </p>

            {{-- Findings List --}}
            <div class="max-h-64 overflow-y-auto beartropy-thin-scrollbar space-y-1">
                @foreach($pendingSyncFindings as $finding)
                    <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 text-xs">
                        @php
                            $badgeColors = match($finding['source']) {
                                'auth' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                'header' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'param' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                'body' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                            };
                        @endphp
                        <span class="shrink-0 px-1.5 py-0.5 rounded font-medium {{ $badgeColors }}">
                            {{ $finding['source'] }}
                        </span>
                        <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $finding['key'] }}</span>
                        <span class="text-gray-400 dark:text-gray-500 font-mono truncate ml-auto">{{ $finding['masked_value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-end gap-2">
                <x-beartropy-ui::button tint wire:click="skipSync" sm>
                    Skip Sync
                </x-beartropy-ui::button>
                <x-beartropy-ui::button tint wire:click="confirmSyncAsIs" sm>
                    Sync as-is
                </x-beartropy-ui::button>
                <x-beartropy-ui::button
                    wire:click="confirmSyncWithoutValues"
                    sm
                    class="!bg-amber-500 hover:!bg-amber-600 !text-white !border-amber-500 hover:!border-amber-600"
                >
                    Sync without values
                </x-beartropy-ui::button>
            </div>
        </x-slot:footer>
    </x-beartropy-ui::modal>
@endif
