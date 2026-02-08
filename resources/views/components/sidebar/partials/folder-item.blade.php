<div wire:key="folder-{{ $folder->id }}">
    {{-- Folder Header --}}
    <div class="group flex items-center justify-between px-2 py-1.5 cursor-pointer hover:bg-gray-200/50 dark:hover:bg-gray-800/50 rounded transition-colors"
         wire:click="toggleFolder('{{ $folder->id }}')">
        <div class="flex items-center gap-1.5 min-w-0 flex-1">
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
                        @blur="setTimeout(() => { if (!$el.contains(document.activeElement)) $wire.cancelEditing() }, 150)"
                        sm
                        class="w-full h-7 py-0 text-sm"
                    />
                </div>
            @else
                <span class="text-sm font-base text-gray-700 dark:text-gray-300 truncate">{{ $folder->name }}</span>
            @endif
        </div>
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
            {{-- Rename --}}
            <button
                wire:click.stop="startFolderEditing('{{ $folder->id }}')"
                class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer"
                title="Rename folder"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
            </button>
            {{-- Add subfolder --}}
            <button
                wire:click.stop="createFolder('{{ $collectionId }}', '{{ $folder->id }}')"
                class="p-1 text-gray-400 hover:text-yellow-500 dark:hover:text-yellow-400 transition-colors cursor-pointer"
                title="Add subfolder"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                </svg>
            </button>
            {{-- Add request --}}
            <button
                wire:click.stop="createRequest('{{ $collectionId }}', '{{ $folder->id }}')"
                class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer"
                title="Add request"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
            {{-- Delete --}}
            <button
                wire:click.stop="deleteFolder('{{ $folder->id }}')"
                wire:confirm="Are you sure you want to delete this folder and all its contents?"
                class="p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors cursor-pointer"
                title="Delete folder"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- Folder Contents (when expanded) --}}
    @if($expandedFolders[$folder->id] ?? false)
        <div class="ml-4">
            {{-- Child folders (recursive) --}}
            @foreach($folder->children as $child)
                @include('components.sidebar.partials.folder-item', ['folder' => $child, 'collectionId' => $collectionId])
            @endforeach

            {{-- Requests in this folder --}}
            @foreach($folder->requests as $request)
                @include('components.sidebar.partials.request-item', ['request' => $request])
            @endforeach

            @if($folder->children->isEmpty() && $folder->requests->isEmpty())
                <div class="px-2 py-1.5 text-xs text-gray-400 dark:text-gray-500">
                    Empty folder
                </div>
            @endif
        </div>
    @endif
</div>
