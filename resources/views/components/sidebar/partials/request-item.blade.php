<div
    wire:key="request-{{ $request->id }}"
    @click="Livewire.dispatch('open-request-tab', { requestId: '{{ $request->id }}' })"
    x-sort:item="'{{ $request->id }}'"
    class="group flex items-center gap-2 px-2 py-1.5 cursor-pointer hover:bg-gray-200/50 dark:hover:bg-gray-800/50 rounded transition-colors"
>
    <div x-sort:handle class="cursor-grab active:cursor-grabbing p-0.5 -ml-1 text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 shrink-0" :class="{ '!hidden': !{{ $isDragEnabled ? 'true' : 'false' }} || search }" @click.stop>
        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
        </svg>
    </div>

    @php
        $methodColor = match(strtoupper($request->method)) {
            'GET' => 'text-emerald-600 dark:text-emerald-400',
            'POST' => 'text-blue-600 dark:text-blue-400',
            'PUT' => 'text-amber-600 dark:text-amber-400',
            'PATCH' => 'text-orange-600 dark:text-orange-400',
            'DELETE' => 'text-red-600 dark:text-red-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    @endphp
    <span class="w-9 shrink-0 text-[9px] font-mono font-bold tracking-tighter text-right {{ $methodColor }}">
        {{ strtoupper($request->method) }}
    </span>

    @if($editingId == $request->id && $editingType === 'request')
        <div class="flex-1" @click.stop>
            <x-beartropy-ui::input
                wire:model="editingName"
                wire:keydown.enter="saveRequestEditing"
                wire:keydown.escape="cancelEditing"
                x-init="$nextTick(() => { let i = $el.querySelector('input') || $el; i.focus(); i.select?.(); })"
                @blur="setTimeout(() => { if (!$el.contains(document.activeElement)) $wire.saveRequestEditing() }, 150)"
                sm
                class="w-full h-7 py-0 text-sm"
            />
        </div>
    @else
        <span class="text-[0.8125rem] text-gray-700 dark:text-gray-300 truncate flex-1">{{ $request->name }}</span>
    @endif

    <div class="flex items-center opacity-0 group-hover:opacity-100 transition-all">
        {{-- Rename --}}
        <button
            wire:click.stop="startRequestEditing('{{ $request->id }}')"
            class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer"
            title="Rename request"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
            </svg>
        </button>
        {{-- Duplicate --}}
        <button
            wire:click.stop="duplicateRequest('{{ $request->id }}')"
            class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 cursor-pointer"
            title="Duplicate request"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
            </svg>
        </button>
        {{-- Delete --}}
        <button
            wire:click.stop="deleteRequest('{{ $request->id }}')"
            wire:confirm="Are you sure you want to delete this request?"
            class="p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 cursor-pointer"
            title="Delete request"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>
</div>
