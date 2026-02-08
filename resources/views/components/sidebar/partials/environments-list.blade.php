@forelse($this->filteredEnvironments as $environment)
    <div
        wire:key="environment-{{ $environment->id }}"
        wire:click="selectEnvironment('{{ $environment->id }}')"
        class="group flex items-center justify-between px-2 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-colors {{ $selectedEnvironmentId === $environment->id ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}"
    >
        <div class="flex items-center gap-2 min-w-0 flex-1">
            {{-- Active Indicator --}}
            <button
                wire:click.stop="toggleActive('{{ $environment->id }}')"
                class="shrink-0 p-0.5 rounded-full transition-colors cursor-pointer {{ $environment->is_active ? 'text-green-500 hover:text-green-600' : 'text-gray-300 dark:text-gray-600 hover:text-gray-400 dark:hover:text-gray-500' }}"
                title="{{ $environment->is_active ? 'Deactivate environment' : 'Activate environment' }}"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                </svg>
            </button>

            @if($editingId == $environment->id)
                <div class="flex-1" @click.stop>
                    <x-beartropy-ui::input
                        wire:model="editingName"
                        wire:keydown.enter="saveEditing"
                        wire:keydown.escape="cancelEditing"
                        x-init="$nextTick(() => { let i = $el.querySelector('input') || $el; i.focus(); i.select?.(); })"
                        @blur="setTimeout(() => { if (!$el.contains(document.activeElement)) $wire.saveEditing() }, 150)"
                        sm
                        class="w-full h-7 py-0 text-sm"
                    />
                </div>
            @else
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $environment->name }}</span>
                @if($environment->vault_synced)
                    <span class="px-1.5 py-0.5 text-[10px] font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400 rounded">
                        Vault
                    </span>
                @endif
                @if($environment->is_active)
                    <span class="px-1.5 py-0.5 text-[10px] font-medium bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400 rounded">
                        Active
                    </span>
                @endif
            @endif
        </div>
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            @include('components.sidebar.partials.item-actions', [
                'itemId' => $environment->id,
                'itemType' => 'environment',
                'showAddRequest' => false,
            ])
        </div>
    </div>
@empty
    <div class="text-center text-gray-400 dark:text-gray-500 py-6">
        @if($search)
            <p class="text-xs">No results for "{{ $search }}"</p>
        @else
            <p class="text-xs">No environments yet</p>
            <p class="text-[10px] mt-1">Click + to create one</p>
        @endif
    </div>
@endforelse
