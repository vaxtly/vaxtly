{{-- Sync Conflict Modal --}}
@if($showConflictModal && $conflictCollectionId)
    <x-beartropy-ui::modal
        wire:model="showConflictModal"
        styled
        max-width="sm"
    >
        <x-slot:title>
            Sync Conflict
        </x-slot:title>

        <div class="space-y-4">
            <div class="flex items-start gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                <svg class="w-5 h-5 shrink-0 text-amber-500 dark:text-amber-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    <strong>{{ $conflictCollectionName }}</strong> was modified on remote since your last sync. Choose which version to keep.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Keep Local --}}
                <button
                    wire:click="resolveConflictForcePush"
                    class="group p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500/50 transition-all text-left cursor-pointer"
                >
                    <div class="flex items-center gap-2 mb-1.5">
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Keep Local</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Push your version, overwriting remote changes
                    </p>
                </button>

                {{-- Keep Remote --}}
                <button
                    wire:click="resolveConflictPullFirst"
                    class="group p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500/50 transition-all text-left cursor-pointer"
                >
                    <div class="flex items-center gap-2 mb-1.5">
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Keep Remote</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Pull the remote version, discarding local changes
                    </p>
                </button>
            </div>
        </div>

        <x-slot:footer>
            <x-beartropy-ui::button tint wire:click="closeConflictModal" sm>
                Cancel
            </x-beartropy-ui::button>
        </x-slot:footer>
    </x-beartropy-ui::modal>
@endif
