{{-- Workspace Switcher --}}
<div x-data="{ open: false, editing: false }" @click.away="open = false; editing = false" class="relative">
    <button
        @click="open = !open"
        type="button"
        class="w-full flex items-center justify-between gap-2 px-2 py-1.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
    >
        <div class="flex items-center gap-2 min-w-0">
            <svg class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span class="truncate">{{ $this->activeWorkspace?->name ?? 'Workspace' }}</span>
        </div>
        <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        style="display: none;"
        class="absolute left-0 right-0 z-20 mt-1 origin-top rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
    >
        <div class="p-1 max-h-48 overflow-y-auto beartropy-thin-scrollbar">
            @foreach($this->workspaces as $workspace)
                <div
                    wire:key="ws-{{ $workspace->id }}"
                    class="group flex items-center gap-1 rounded-md transition-colors {{ $workspace->id === $activeWorkspaceId ? 'bg-beartropy-50 dark:bg-beartropy-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                >
                    @if($editingWorkspaceId === $workspace->id)
                        <form wire:submit="saveWorkspaceEditing" class="flex-1 flex items-center gap-1 p-1">
                            <input
                                type="text"
                                wire:model="editingWorkspaceName"
                                class="flex-1 text-xs bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-beartropy-500"
                                x-init="$el.focus(); $el.select()"
                                @keydown.escape="$wire.cancelWorkspaceEditing()"
                            />
                            <button type="submit" class="p-0.5 text-green-500 hover:text-green-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </form>
                    @else
                        <button
                            wire:click="switchWorkspace('{{ $workspace->id }}')"
                            @click="open = false"
                            class="flex-1 flex items-center gap-2 px-2 py-1.5 text-xs text-left cursor-pointer {{ $workspace->id === $activeWorkspaceId ? 'text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}"
                        >
                            @if($workspace->id === $activeWorkspaceId)
                                <svg class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <span class="w-3"></span>
                            @endif
                            <span class="truncate">{{ $workspace->name }}</span>
                        </button>

                        {{-- Actions (visible on hover) --}}
                        <div class="hidden group-hover:flex items-center pr-1">
                            <button
                                wire:click.stop="startEditingWorkspace('{{ $workspace->id }}')"
                                class="p-0.5 text-gray-400 hover:text-blue-500 transition-colors cursor-pointer"
                                title="Rename"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            @if($this->workspaces->count() > 1)
                                <button
                                    wire:click.stop="deleteWorkspace('{{ $workspace->id }}')"
                                    wire:confirm="Delete workspace '{{ $workspace->name }}' and all its collections and environments?"
                                    class="p-0.5 text-gray-400 hover:text-red-500 transition-colors cursor-pointer"
                                    title="Delete"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- New Workspace --}}
        <div class="border-t border-gray-100 dark:border-gray-700/50 p-1">
            @if($isCreatingWorkspace)
                <form wire:submit="createWorkspace" class="flex items-center gap-1 p-1">
                    <input
                        type="text"
                        wire:model="newWorkspaceName"
                        placeholder="Workspace name..."
                        class="flex-1 text-xs bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-beartropy-500"
                        x-init="$el.focus()"
                        @keydown.escape="$wire.set('isCreatingWorkspace', false)"
                    />
                    <button type="submit" class="p-0.5 text-green-500 hover:text-green-600 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button type="button" wire:click="$set('isCreatingWorkspace', false)" class="p-0.5 text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </form>
            @else
                <button
                    wire:click="$set('isCreatingWorkspace', true)"
                    class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md transition-colors cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Workspace</span>
                </button>
            @endif
        </div>
    </div>
</div>
