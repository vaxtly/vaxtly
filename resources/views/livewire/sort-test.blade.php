<?php

use Livewire\Component;

new class extends Component {
    public array $groupA = [
        ['id' => '1', 'name' => 'Alpha'],
        ['id' => '2', 'name' => 'Beta'],
    ];

    public array $groupB = [
        ['id' => '3', 'name' => 'Gamma'],
        ['id' => '4', 'name' => 'Delta'],
    ];

    public array $uuidItems = [
        ['id' => '019c32f0-37b6-72cf-a052-7ebed3a0b5a5', 'name' => 'UUID-Alpha'],
        ['id' => '019c32f0-4a21-7def-b123-8cdef1234567', 'name' => 'UUID-Beta'],
        ['id' => '019c32f0-5b32-7abc-c234-9defa2345678', 'name' => 'UUID-Gamma'],
    ];

    public bool $dragEnabled = true;

    public function handleSort(string $id, int $position): void
    {
        logger("Sort called: id=$id, position=$position");
    }

    public function toggleDrag(): void
    {
        $this->dragEnabled = !$this->dragEnabled;
    }
};

?>

<div class="p-8 max-w-md mx-auto space-y-8">
    <h2 class="text-xl font-bold">Sort diagnostic</h2>

    <button wire:click="toggleDrag" class="px-4 py-2 bg-blue-500 text-white rounded">
        Handles: {{ $dragEnabled ? 'VISIBLE' : 'HIDDEN' }}
    </button>

    {{-- Test 1: wire:sort with simple numeric IDs (known working) --}}
    <div>
        <h3 class="font-bold mb-2">Test 1: wire:sort + simple IDs (baseline)</h3>
        <ul wire:sort="handleSort" class="space-y-2">
            @foreach(array_merge($groupA, $groupB) as $item)
                <li wire:key="t1-{{ $item['id'] }}" wire:sort:item="{{ $item['id'] }}"
                    class="p-3 bg-white border rounded shadow flex items-center gap-2">
                    <div wire:sort:handle class="{{ $dragEnabled ? '' : 'hidden' }} cursor-grab active:cursor-grabbing text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                        </svg>
                    </div>
                    {{ $item['name'] }}
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Test 2: wire:sort with UUID IDs (unquoted - expect failure) --}}
    <div>
        <h3 class="font-bold mb-2">Test 2: wire:sort + UUID IDs (unquoted)</h3>
        <ul wire:sort="handleSort" class="space-y-2">
            @foreach($uuidItems as $item)
                <li wire:key="t2-{{ $item['id'] }}" wire:sort:item="{{ $item['id'] }}"
                    class="p-3 bg-yellow-50 border rounded shadow flex items-center gap-2">
                    <div wire:sort:handle class="{{ $dragEnabled ? '' : 'hidden' }} cursor-grab active:cursor-grabbing text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                        </svg>
                    </div>
                    {{ $item['name'] }} <span class="text-xs text-gray-400">({{ $item['id'] }})</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Test 3: Native x-sort with UUID IDs (quoted - like sidebar now uses) --}}
    <div>
        <h3 class="font-bold mb-2">Test 3: x-sort (native Alpine) + UUID IDs (quoted)</h3>
        <ul x-sort="$wire.handleSort($item, $position)" class="space-y-2">
            @foreach($uuidItems as $item)
                <li wire:key="t3-{{ $item['id'] }}" x-sort:item="'{{ $item['id'] }}'"
                    class="p-3 bg-green-50 border rounded shadow flex items-center gap-2">
                    <div x-sort:handle class="{{ $dragEnabled ? '' : 'hidden' }} cursor-grab active:cursor-grabbing text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/>
                        </svg>
                    </div>
                    {{ $item['name'] }} <span class="text-xs text-gray-400">({{ $item['id'] }})</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
