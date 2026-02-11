{{-- Rename button --}}
<button
    wire:click.stop="startEditing('{{ $itemId }}')"
    class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer"
    title="Rename {{ $itemType }}"
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
    </svg>
</button>

@if($showAddRequest ?? false)
    {{-- Add request button (collections only) --}}
    <button
        wire:click.stop="createRequest('{{ $itemId }}')"
        class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer"
        title="Add request"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
    </button>
@else
    {{-- Duplicate button (environments only) --}}
    <button
        wire:click.stop="duplicate{{ ucfirst($itemType) }}('{{ $itemId }}')"
        class="p-1 text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 cursor-pointer"
        title="Duplicate {{ $itemType }}"
    >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
        </svg>
    </button>
@endif

{{-- Delete button --}}
@if($itemType === 'collection')
    <button
        wire:click.stop="deleteCollection('{{ $itemId }}')"
        wire:confirm="Are you sure you want to delete this {{ $itemType }} and all its requests?"
        class="p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors cursor-pointer"
        title="Delete {{ $itemType }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
    </button>
@else
    <button
        wire:click.stop="deleteEnvironment('{{ $itemId }}')"
        wire:confirm="Are you sure you want to delete this {{ $itemType }}?"
        class="p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors cursor-pointer"
        title="Delete {{ $itemType }}"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
    </button>
@endif
