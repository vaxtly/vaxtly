<div>
    <x-beartropy-ui::modal
        wire:model="show"
        styled
        max-width="2xl"
    >
        {{-- Tab Navigation --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 -mt-6 mb-4">
            <button
                wire:click="$set('activeTab', 'general')"
                class="cursor-pointer px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'general' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                General
            </button>
            <button
                wire:click="$set('activeTab', 'data')"
                class="cursor-pointer px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'data' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                Data
            </button>
            <button
                wire:click="$set('activeTab', 'remote')"
                class="cursor-pointer px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'remote' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                Remote
            </button>
            <button
                wire:click="$set('activeTab', 'vault')"
                class="cursor-pointer px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'vault' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                Vault
            </button>
        </div>

        @if($activeTab === 'general')
            @include('components.app-settings.partials.general-tab')
        @endif

        @if($activeTab === 'data')
            @include('components.app-settings.partials.data-tab')
        @endif

        @if($activeTab === 'remote')
            @include('components.app-settings.partials.remote-sync-tab')
        @endif

        @if($activeTab === 'vault')
            @include('components.app-settings.partials.vault-tab')
        @endif

        <x-slot:footer>
            <x-beartropy-ui::button tint
                wire:click="close"
                sm
            >
                Close
            </x-beartropy-ui::button>
        </x-slot:footer>
    </x-beartropy-ui::modal>

    {{-- Conflict Resolution Modal --}}
    @if($showConflictModal && !empty($conflicts))
        <x-beartropy-ui::modal
            wire:model="showConflictModal"
            styled
            max-width="md"
        >
            <x-slot:title>
                Sync Conflicts
            </x-slot:title>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    The following collections have been modified both locally and remotely. Choose which version to keep:
                </p>

                @foreach($conflicts as $conflict)
                    <div wire:key="conflict-{{ $conflict['collection_id'] }}" class="p-3 rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20">
                        <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ $conflict['collection_name'] }}</p>
                        <div class="flex gap-2">
                            <x-beartropy-ui::button
                                wire:click="resolveConflict('{{ $conflict['collection_id'] }}', 'local')"
                                primary
                                sm
                            >
                                Keep Local
                            </x-beartropy-ui::button>
                            <x-beartropy-ui::button
                                wire:click="resolveConflict('{{ $conflict['collection_id'] }}', 'remote')"
                                tint
                                sm
                            >
                                Keep Remote
                            </x-beartropy-ui::button>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-slot:footer>
                <x-beartropy-ui::button
                    wire:click="$set('showConflictModal', false)"
                    tint
                    sm
                >
                    Close
                </x-beartropy-ui::button>
            </x-slot:footer>
        </x-beartropy-ui::modal>
    @endif
</div>
