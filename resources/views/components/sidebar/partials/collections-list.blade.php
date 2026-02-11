@php $isDragEnabled = $sort === 'manual'; @endphp

<div x-sort="$wire.reorderCollections($item, $position)">
@foreach($this->getCollections() as $collection)
    <div wire:key="collection-{{ $collection->id }}"
         x-sort:item="'{{ $collection->id }}'"
         data-collection-id="{{ $collection->id }}"
         data-search-text="{{ $this->buildSearchableText($collection) }}"
         x-show="!search || $el.dataset.searchText.includes(search.toLowerCase())"
    >
        {{-- Collection Header --}}
        <div x-data="{ menuOpen: false }"
             class="group flex items-center justify-between px-2 py-1.5 cursor-pointer hover:bg-gray-200/50 dark:hover:bg-gray-800/50 rounded transition-colors"
             @click="expandedCollections['{{ $collection->id }}'] = !expandedCollections['{{ $collection->id }}']; persistExpanded()"
             @contextmenu.prevent="$dispatch('close-sidebar-menus'); menuOpen = true"
             @close-sidebar-menus.window="menuOpen = false">
            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                <div x-sort:handle class="cursor-grab active:cursor-grabbing p-0.5 -ml-1 text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 shrink-0" :class="{ '!hidden': !{{ $isDragEnabled ? 'true' : 'false' }} || search }" @click.stop>
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                    </svg>
                </div>

                <svg class="w-3 h-3 text-gray-400 dark:text-gray-500 transition-transform shrink-0"
                     :class="{ 'rotate-90': expandedCollections['{{ $collection->id }}'] }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>

                @if($editingId == $collection->id && $editingType === 'collection')
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
                    @if($collection->sync_enabled)
                        <svg
                            class="w-3 h-3 shrink-0 {{ $collection->is_dirty ? 'text-yellow-500' : 'text-green-500' }}"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            title="{{ $collection->is_dirty ? 'Sync enabled · changes pending push' : 'Sync enabled · up to date' }}"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    @endif
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $collection->name }}</span>
                    @php
                        $totalItems = $collection->rootFolders->count() + $collection->rootRequests->count();
                    @endphp
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 shrink-0">({{ $totalItems }})</span>
                @endif
            </div>

            {{-- 3-dot menu --}}
            <div
                class="relative transition-opacity"
                :class="menuOpen ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                @click.stop
            >
                <button
                    @click="menuOpen = !menuOpen"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors cursor-pointer rounded hover:bg-gray-200 dark:hover:bg-gray-700"
                    title="Collection actions"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                    </svg>
                </button>

                {{-- Dropdown menu --}}
                <template x-teleport="body">
                    <div
                        x-show="menuOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        @click.away="menuOpen = false"
                        x-anchor.bottom-end.offset.4="$root.querySelector('button')"
                        class="fixed z-[9999] w-44 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1"
                    >
                    {{-- Rename --}}
                    <button
                        @click="menuOpen = false; $wire.startEditing('{{ $collection->id }}')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Rename
                    </button>

                    {{-- Add folder --}}
                    <button
                        @click="menuOpen = false; $wire.createFolder('{{ $collection->id }}')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        Add folder
                    </button>

                    {{-- Add request --}}
                    <button
                        @click="menuOpen = false; $wire.createRequest('{{ $collection->id }}')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add request
                    </button>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                    {{-- Set environments --}}
                    <button
                        @click="menuOpen = false; $parent.openEnvironmentModal('{{ $collection->id }}', 'collection')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Set environments
                    </button>

                    {{-- Sync submenu --}}
                    <div x-data="{ syncOpen: false }" class="relative" @mouseleave="syncOpen = false" @mouseenter="syncOpen = true">
                        <button
                            @click="syncOpen = !syncOpen"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 {{ $collection->sync_enabled ? 'text-green-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            <span class="flex-1 text-left">Sync</span>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        {{-- Sync submenu items --}}
                        <div
                            x-show="syncOpen"
                            x-transition:enter="transition ease-out duration-75"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="absolute left-full top-0 -ml-1 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 pl-1"
                        >
                            @if($collection->sync_enabled)
                                {{-- Disable sync --}}
                                <button
                                    x-data
                                    @click="
                                        menuOpen = false; syncOpen = false;
                                        if (confirm('Delete this collection from git?')) {
                                            $wire.disableSync('{{ $collection->id }}', true);
                                        } else {
                                            $wire.disableSync('{{ $collection->id }}', false);
                                        }
                                    "
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    Disable sync
                                </button>

                                {{-- Pull --}}
                                <button
                                    @click="menuOpen = false; syncOpen = false; $wire.pullSingleCollection('{{ $collection->id }}')"
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </svg>
                                    Pull
                                </button>

                                {{-- Push --}}
                                <button
                                    @click="menuOpen = false; syncOpen = false; $wire.pushSingleCollection('{{ $collection->id }}')"
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </svg>
                                    Push
                                </button>
                            @else
                                {{-- Enable sync --}}
                                <button
                                    @click="menuOpen = false; syncOpen = false; $wire.enableSync('{{ $collection->id }}')"
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Enable sync
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                    {{-- Delete --}}
                    <button
                        @click="menuOpen = false"
                        wire:click="deleteCollection('{{ $collection->id }}')"
                        wire:confirm="Are you sure you want to delete this collection and all its contents?"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete
                    </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Collection Contents (always rendered for drag-and-drop; hidden when collapsed) --}}
        <div class="ml-4" :class="{ 'hidden sort-drop-collapsed': !expandedCollections['{{ $collection->id }}'] }">
            {{-- Folders container --}}
            <div wire:key="cfolders-{{ $collection->id }}" x-sort="$wire.reorderFolders($item, $position, 'collection:{{ $collection->id }}')" x-sort:group="folders" class="min-h-[4px]">
                @foreach($collection->rootFolders as $folder)
                    @include('components.sidebar.partials.folder-item', ['folder' => $folder, 'collectionId' => $collection->id, 'isDragEnabled' => $isDragEnabled])
                @endforeach
            </div>

            {{-- Requests container --}}
            <div wire:key="crequests-{{ $collection->id }}" x-sort="$wire.reorderRequests($item, $position, 'collection:{{ $collection->id }}')" x-sort:group="requests" class="min-h-[4px]">
                @foreach($collection->rootRequests as $request)
                    @include('components.sidebar.partials.request-item', ['request' => $request, 'isDragEnabled' => $isDragEnabled])
                @endforeach
            </div>

            @if($collection->rootFolders->isEmpty() && $collection->rootRequests->isEmpty())
                <div class="px-2 py-1.5 text-xs text-gray-400 dark:text-gray-500">
                    No items yet
                </div>
            @endif
        </div>
    </div>
@endforeach

@if($this->getCollections()->isEmpty())
    <div class="text-center text-gray-400 dark:text-gray-500 py-6">
        <p class="text-xs">No collections yet</p>
        <p class="text-[10px] mt-1">Click "New Collection" to start</p>
    </div>
@endif

{{-- Alpine-driven "no results" message --}}
<div
    x-show="search && ![...$el.parentElement.querySelectorAll('[data-collection-id]')].some(el => el.dataset.searchText.includes(search.toLowerCase()))"
    x-cloak
    class="text-center text-gray-400 dark:text-gray-500 py-6"
>
    <p class="text-xs">No results</p>
</div>
</div>
