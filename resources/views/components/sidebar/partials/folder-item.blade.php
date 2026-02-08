<div wire:key="folder-{{ $folder->id }}" x-sort:item="'{{ $folder->id }}'">
    {{-- Folder Header --}}
    <div x-data="{ menuOpen: false }"
         class="group flex items-center justify-between px-2 py-1.5 cursor-pointer hover:bg-gray-200/50 dark:hover:bg-gray-800/50 rounded transition-colors"
         wire:click="toggleFolder('{{ $folder->id }}')"
         @contextmenu.prevent="menuOpen = true">
        <div class="flex items-center gap-1.5 min-w-0 flex-1">
            <div x-sort:handle class="{{ $isDragEnabled ? '' : 'hidden' }} cursor-grab active:cursor-grabbing p-0.5 -ml-1 text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 shrink-0" @click.stop>
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                </svg>
            </div>

            <svg class="w-3 h-3 text-gray-400 dark:text-gray-500 transition-transform shrink-0 {{ $expandedFolders[$folder->id] ?? false ? 'rotate-90' : '' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
            </svg>

            @if($editingId == $folder->id)
                <div class="flex-1" @click.stop>
                    <x-beartropy-ui::input
                        wire:model="editingName"
                        wire:keydown.enter="saveFolderEditing"
                        wire:keydown.escape="cancelEditing"
                        x-init="$nextTick(() => { let i = $el.querySelector('input') || $el; i.focus(); i.select?.(); })"
                        @blur="setTimeout(() => { if (!$el.contains(document.activeElement)) $wire.saveFolderEditing() }, 150)"
                        sm
                        class="w-full h-7 py-0 text-sm"
                    />
                </div>
            @else
                <span class="text-sm font-base text-gray-700 dark:text-gray-300 truncate">{{ $folder->name }}</span>
                @if(!empty($folder->getEnvironmentIds()))
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0" title="Has environment associations"></span>
                @endif
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
                title="Folder actions"
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
                        @click="menuOpen = false; $wire.startFolderEditing('{{ $folder->id }}')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Rename
                    </button>

                    {{-- Add subfolder --}}
                    <button
                        @click="menuOpen = false; $wire.createFolder('{{ $collectionId }}', '{{ $folder->id }}')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        Add subfolder
                    </button>

                    {{-- Add request --}}
                    <button
                        @click="menuOpen = false; $wire.createRequest('{{ $collectionId }}', '{{ $folder->id }}')"
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
                        @click="menuOpen = false; $wire.openEnvironmentModal('{{ $folder->id }}', 'folder')"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Set environments
                    </button>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                    {{-- Delete --}}
                    <button
                        @click="menuOpen = false"
                        wire:click="deleteFolder('{{ $folder->id }}')"
                        wire:confirm="Are you sure you want to delete this folder and all its contents?"
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

    {{-- Folder Contents (always rendered for drag-and-drop; hidden when collapsed) --}}
    @php $isFolderExpanded = $expandedFolders[$folder->id] ?? false; @endphp
    <div class="ml-4 {{ $isFolderExpanded ? '' : 'hidden sort-drop-collapsed' }}">
        {{-- Child folders container --}}
        <div wire:key="ffolders-{{ $folder->id }}" x-sort="$wire.reorderFolders($item, $position, 'folder:{{ $folder->id }}')" x-sort:group="folders" class="min-h-[4px]">
            @foreach($folder->children as $child)
                @include('components.sidebar.partials.folder-item', ['folder' => $child, 'collectionId' => $collectionId, 'isDragEnabled' => $isDragEnabled])
            @endforeach
        </div>

        {{-- Requests container --}}
        <div wire:key="frequests-{{ $folder->id }}" x-sort="$wire.reorderRequests($item, $position, 'folder:{{ $folder->id }}')" x-sort:group="requests" class="min-h-[4px]">
            @foreach($folder->requests as $request)
                @include('components.sidebar.partials.request-item', ['request' => $request, 'isDragEnabled' => $isDragEnabled])
            @endforeach
        </div>

        @if($isFolderExpanded && $folder->children->isEmpty() && $folder->requests->isEmpty())
            <div class="px-2 py-1.5 text-xs text-gray-400 dark:text-gray-500">
                Empty folder
            </div>
        @endif
    </div>
</div>
