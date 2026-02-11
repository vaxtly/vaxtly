<div
    x-data="{
        mode: @js($mode),
        search: '',
        collectionsAllExpanded: false,
        selectedEnvId: @js($selectedEnvironmentId),
    }"
    x-on:collections-expanded-state.window="collectionsAllExpanded = $event.detail.allExpanded"
    x-on:focus-sidebar-search.window="$nextTick(() => { const el = $root.querySelector('[data-sidebar-search] input'); if (el) { el.focus(); el.select(); } })"
    x-on:sidebar-scroll-to.window="$nextTick(() => { const el = $root.querySelector($event.detail.selector); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' }); })"
    x-on:switch-tab.window="
        const type = $event.detail.type || 'request';
        mode = type === 'environment' ? 'environments' : 'collections';
        if (type === 'environment' && $event.detail.environmentId) selectedEnvId = $event.detail.environmentId;
        $wire.focusOnTab($event.detail.tabId, type, $event.detail.requestId || null, $event.detail.environmentId || null);
    "
    class="h-full flex flex-col bg-gray-50 dark:bg-gray-950 border-r border-gray-200 dark:border-gray-800">
    {{-- Workspace Switcher --}}
    <div class="px-3 pt-3 pb-1">
        @include('components.sidebar.partials.workspace-switcher')
    </div>

    {{-- Header --}}
    <div class="p-3 border-b border-gray-200 dark:border-gray-800/50">
        <div class="flex items-center justify-between mb-3">
            {{-- Mode Switcher (Collections / Environments) --}}
            <div x-data="{ modeOpen: false }" @click.away="modeOpen = false" class="relative min-w-0 flex-1">
                <button
                    @click="modeOpen = !modeOpen"
                    type="button"
                    class="w-full flex items-center justify-between gap-2 px-2 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <x-bt-icon x-show="mode === 'collections'" name="folder" class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" />
                        <x-bt-icon x-show="mode === 'environments'" x-cloak name="beaker" class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" />
                        <span x-text="mode === 'collections' ? 'Collections' : 'Environments'" class="truncate"></span>
                    </div>
                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 transition-transform" :class="modeOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="modeOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    style="display: none;"
                    class="absolute left-0 right-0 z-20 mt-1 origin-top rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div class="p-1">
                        <button
                            @click="mode = 'collections'; $wire.switchMode('collections'); modeOpen = false"
                            class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer"
                            :class="mode === 'collections' ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                        >
                            <svg x-show="mode === 'collections'" class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-show="mode !== 'collections'" class="w-3"></span>
                            <x-bt-icon name="folder" class="w-3.5 h-3.5 shrink-0" />
                            <span>Collections</span>
                        </button>
                        <button
                            @click="mode = 'environments'; $wire.switchMode('environments'); modeOpen = false"
                            class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer"
                            :class="mode === 'environments' ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                        >
                            <svg x-show="mode === 'environments'" class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-show="mode !== 'environments'" class="w-3"></span>
                            <x-bt-icon name="beaker" class="w-3.5 h-3.5 shrink-0" />
                            <span>Environments</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-0.5">
                <button
                    x-show="mode === 'collections'"
                    x-cloak
                    @click="$dispatch('toggle-all-collections')"
                    type="button"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer"
                    :title="collectionsAllExpanded ? 'Collapse all' : 'Expand all'"
                >
                    {{-- Collapse: arrows pointing inward --}}
                    <svg x-show="collectionsAllExpanded" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4v5H4M15 4v5h5M4 15h5v5M20 15h-5v5"/>
                    </svg>
                    {{-- Expand: arrows pointing outward --}}
                    <svg x-show="!collectionsAllExpanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                </button>

                <div x-data="{ open: false }" class="relative" @click.away="open = false">
                    <button
                        @click="open = !open"
                        type="button"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                        :title="'Sort ' + (mode === 'collections' ? 'Collections' : 'Environments')"
                    >
                        <x-bt-icon name="arrows-up-down" class="w-4 h-4" />
                    </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     style="display: none;"
                     class="absolute right-0 z-10 mt-1 w-48 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                     role="menu"
                >
                    <div class="p-2 text-xs font-semibold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700/50 mb-1">
                        Sort by
                    </div>
                    <div class="p-1 space-y-0.5">
                        <button wire:click="$set('sort', 'a-z')" @click="open = false" class="w-full flex items-center gap-2 px-2 py-1.5 text-xs rounded-md transition-colors {{ $sort === 'a-z' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}" role="menuitem">
                            <svg class="w-3.5 h-3.5 {{ $sort === 'a-z' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9l3-6 3 6M4 7h4M3 15h6l-6 6h6M14 6v12M10 14l4 4 4-4" />
                            </svg>
                            <span>Name (A-Z)</span>
                            @if($sort === 'a-z')
                                <x-bt-icon name="check" class="w-3.5 h-3.5 ml-auto text-blue-500" />
                            @endif
                        </button>
                        <button wire:click="$set('sort', 'z-a')" @click="open = false" class="w-full flex items-center gap-2 px-2 py-1.5 text-xs rounded-md transition-colors {{ $sort === 'z-a' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}" role="menuitem">
                            <svg class="w-3.5 h-3.5 {{ $sort === 'z-a' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h6l-6 6h6M3 21l3-6 3 6M4 19h4M14 6v12M10 14l4 4 4-4" />
                            </svg>
                            <span>Name (Z-A)</span>
                            @if($sort === 'z-a')
                                <x-bt-icon name="check" class="w-3.5 h-3.5 ml-auto text-blue-500" />
                            @endif
                        </button>
                        <button wire:click="$set('sort', 'newest')" @click="open = false" class="w-full flex items-center gap-2 px-2 py-1.5 text-xs rounded-md transition-colors {{ $sort === 'newest' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}" role="menuitem">
                            <svg class="w-3.5 h-3.5 {{ $sort === 'newest' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0M19 5v14m0 0l-4-4m4 4l4-4" />
                            </svg>
                            <span>Newest First</span>
                            @if($sort === 'newest')
                                <x-bt-icon name="check" class="w-3.5 h-3.5 ml-auto text-blue-500" />
                            @endif
                        </button>
                        <button wire:click="$set('sort', 'oldest')" @click="open = false" class="w-full flex items-center gap-2 px-2 py-1.5 text-xs rounded-md transition-colors {{ $sort === 'oldest' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}" role="menuitem">
                            <svg class="w-3.5 h-3.5 {{ $sort === 'oldest' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0M19 19V5m0 0l-4 4m4-4l4 4" />
                            </svg>
                            <span>Oldest First</span>
                            @if($sort === 'oldest')
                                <x-bt-icon name="check" class="w-3.5 h-3.5 ml-auto text-blue-500" />
                            @endif
                        </button>
                        <button wire:click="$set('sort', 'manual')" @click="open = false" class="w-full flex items-center gap-2 px-2 py-1.5 text-xs rounded-md transition-colors {{ $sort === 'manual' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}" role="menuitem">
                            <svg class="w-3.5 h-3.5 {{ $sort === 'manual' ? 'text-blue-500' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                            </svg>
                            <span>Manual Order</span>
                            @if($sort === 'manual')
                                <x-bt-icon name="check" class="w-3.5 h-3.5 ml-auto text-blue-500" />
                            @endif
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </div>
        <div class="mt-2 flex gap-2">
            <div class="flex-1" data-sidebar-search>
                <x-beartropy-ui::input
                    x-model.debounce.150ms="search"
                    placeholder="Search..."
                    icon-start="magnifying-glass"
                    sm
                />
            </div>


            <x-beartropy-ui::button tint
                wire:click="create"
                primary
                sm
                tint
            >
                <x-bt-icon name="plus" />
            </x-beartropy-ui::button>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-auto beartropy-thin-scrollbar p-2 space-y-1">
        <div x-show="mode === 'collections'" wire:ignore>
            <livewire:sidebar-collections :active-workspace-id="$activeWorkspaceId" />
        </div>
        <div x-show="mode === 'environments'" x-cloak>
            @include('components.sidebar.partials.environments-list')
        </div>
    </div>

    {{-- Footer --}}
    <div class="px-2 py-0.5 border-t flex items-center justify-between border-gray-200 dark:border-gray-800">
        {{-- Mode Switcher --}}
        <div class="flex items-center gap-0.5">
            <button
                @click="mode = 'collections'; $wire.switchMode('collections')"
                class="p-1.5 rounded transition-colors cursor-pointer"
                :class="mode === 'collections' ? 'text-beartropy-600 dark:text-beartropy-400 bg-beartropy-50 dark:bg-beartropy-900/30' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                title="Collections"
            >
                <x-bt-icon name="folder" class="w-4 h-4" />
            </button>
            <button
                @click="mode = 'environments'; $wire.switchMode('environments')"
                class="p-1.5 rounded transition-colors cursor-pointer"
                :class="mode === 'environments' ? 'text-beartropy-600 dark:text-beartropy-400 bg-beartropy-50 dark:bg-beartropy-900/30' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                title="Environments"
            >
                <x-bt-icon name="beaker" class="w-4 h-4" />
            </button>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-1">
            {{-- Settings Button --}}
            <button
                x-on:click="$wire.$dispatch('open-settings')"
                class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                title="Settings"
            >
                <x-bt-icon name="cog-6-tooth" class="w-4 h-4" />
            </button>

            {{-- Layout Toggle --}}
            <button
                x-data="{ layout: '{{ get_setting('requests.layout', 'columns') }}' }"
                x-on:layout-updated.window="layout = $event.detail.layout || (layout === 'rows' ? 'columns' : 'rows')"
                x-on:click="
                    layout = layout === 'rows' ? 'columns' : 'rows';
                    $wire.$dispatch('toggle-layout');
                "
                class="p-1.5 rounded text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                title="Toggle layout"
            >
                <x-bt-icon
                    x-show="layout === 'rows'"
                    name="view-columns"
                    class="w-4 h-4"
                    x-cloak
                />

                <x-bt-icon
                    x-show="layout === 'columns'"
                    name="bars-4"
                    class="w-4 h-4"
                    x-cloak
                />
            </button>

            {{-- Theme Toggle --}}
            <x-bt-toggle-theme class="ml-1">
                <x-slot:iconLight>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="text-gray-500 dark:text-gray-400 hover:text-amber-500 dark:hover:text-amber-400 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                </x-slot:iconLight>
                <x-slot:iconDark>
                    <svg xmlns="http://www.w3.org/2000/svg" class="text-gray-500 dark:text-gray-400 hover:text-sky-500 dark:hover:text-sky-400 transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </x-slot:iconDark>
            </x-bt-toggle-theme>
        </div>
    </div>

    <livewire:app-settings />

    @include('components.sidebar.partials.environment-modal')
    @include('components.sidebar.partials.conflict-modal')
    @include('components.sidebar.partials.sensitive-data-modal')

    @once
    <style>
        /* During drag (SortableJS adds .sorting to body), reveal collapsed containers as drop zones */
        body.sorting .sort-drop-collapsed {
            display: block !important;
            min-height: 8px;
        }
    </style>
    @endonce
</div>
