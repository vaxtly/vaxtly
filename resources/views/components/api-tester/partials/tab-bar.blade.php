<div class="flex items-center justify-between gap-2 h-10 pl-2 pr-2 bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
    {{-- Tabs --}}
    <div class="flex items-end gap-1 overflow-x-auto overflow-y-hidden beartropy-thin-scrollbar min-w-0 flex-1 pt-1.5">
        @foreach($openTabs as $tab)
            @php
                $isActive = $activeTabId === $tab['id'];
                $methodColor = match(strtoupper($tab['method'])) {
                    'GET' => 'text-emerald-600 dark:text-emerald-400',
                    'POST' => 'text-blue-600 dark:text-blue-400',
                    'PUT' => 'text-amber-600 dark:text-amber-400',
                    'PATCH' => 'text-orange-600 dark:text-orange-400',
                    'DELETE' => 'text-red-600 dark:text-red-400',
                    default => 'text-gray-600 dark:text-gray-400',
                };
            @endphp
            <div
                wire:key="tab-{{ $tab['id'] }}"
                @click="Livewire.dispatch('switch-tab', { tabId: '{{ $tab['id'] }}', requestId: '{{ $tab['requestId'] }}' })"
                @auxclick.prevent="if ($event.button === 1) $wire.closeTab('{{ $tab['id'] }}')"
                class="group relative flex items-center gap-2 px-3 py-2 text-xs font-medium cursor-pointer transition-all rounded-t-lg select-none mr-1 min-w-30 max-w-50 border-t border-l border-r
                {{ $isActive 
                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-700 shadow-sm z-10 -mb-px pb-2.5' 
                    : 'bg-transparent border-gray-200/50 dark:border-gray-700/50 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-200/50 dark:hover:bg-gray-800/50' 
                }}"
            >
                <span class="font-mono font-bold tracking-tighter {{ $methodColor }}">
                    {{ strtoupper($tab['method']) }}
                </span>
                
                <span class="truncate flex-1 {{ $isActive ? 'font-semibold' : '' }}">{{ $tab['name'] ?: 'Untitled' }}</span>
                
                <button
                    wire:click.stop="closeTab('{{ $tab['id'] }}')"
                    class="p-0.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer shrink-0"
                    title="Close tab"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                {{-- Access Indicator if needed, or just bottom border hiding --}}
                @if($isActive)
                   <div class="absolute bottom-0 left-0 right-0 h-px bg-white dark:bg-gray-800 translate-y-px"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Environment Selector + Help --}}
    <div class="flex items-center gap-1.5">
        @include('components.api-tester.partials.environment-selector')

        <button
            @click="$dispatch('open-help-modal')"
            type="button"
            class="flex items-center justify-center w-7 h-7 rounded-md border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors cursor-pointer text-xs font-bold"
            title="User Guide"
        >
            ?
        </button>
    </div>
</div>
