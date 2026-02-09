{{-- Header Section --}}
<div class="px-3 pt-3 pb-2 space-y-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
    {{-- Breadcrumb Navigation --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
            @php
                $selectedCollection = $collections->firstWhere('id', $selectedCollectionId);
                $collectionName = $selectedCollection?->name ?? 'No Collection';
            @endphp
            <span class="text-gray-500 dark:text-gray-400">{{ $collectionName }}</span>
            @if($folderName)
                <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-500 dark:text-gray-400">{{ $folderName }}</span>
            @endif
            <svg class="w-4 h-4 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="font-medium text-gray-900 dark:text-white">{{ $name ?: 'Untitled Request' }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <x-bt-button tint emerald sm wire:click="saveRequest">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1-2-2h11l5 5v11a2 2 0 0 1-2 2zM17 21v-8H7v8M7 3v5h8"></path>
                    </svg>
                    <span>Save</span>
                </div>
            </x-bt-button>
            <x-bt-button tint sky sm wire:click="pullRequest" wire:loading.attr="disabled" wire:target="pullRequest" title="Pull from remote">
                <x-bt-icon name="cloud-arrow-down" class="w-4 h-4" />
            </x-bt-button>
            <x-bt-button tint sky sm wire:click="pushRequest" wire:loading.attr="disabled" wire:target="pushRequest" title="Save & push to remote">
                <x-bt-icon name="cloud-arrow-up" class="w-4 h-4" />
            </x-bt-button>
        </div>
    </div>

    {{-- URL & Method --}}
    <div
        class="flex items-center w-full bg-white dark:bg-gray-800/80 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm transition-all
               focus-within:ring-2 focus-within:ring-beartropy-500/50 focus-within:border-beartropy-500 active-within:border-beartropy-500"
    >
        {{-- Method Selector --}}
        <div
            wire:key="method-selector-{{ $activeTabId }}"
            x-data="{
                open: false,
                search: '',
                methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                activeMethod: '{{ $method }}',
                get filteredMethods() {
                    if (!this.search) return this.methods;
                    return this.methods.filter(m => m.toLowerCase().includes(this.search.toLowerCase()));
                },
                select(method) {
                    this.activeMethod = method;
                    this.close();
                    $wire.set('method', method);
                },
                close() {
                    this.open = false;
                    this.search = '';
                },
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    }
                },
                getMethodColor(method) {
                    const colors = {
                        'GET': 'text-emerald-600 dark:text-emerald-400',
                        'POST': 'text-blue-600 dark:text-blue-400',
                        'PUT': 'text-amber-600 dark:text-amber-400',
                        'PATCH': 'text-orange-600 dark:text-orange-400',
                        'DELETE': 'text-red-600 dark:text-red-400',
                    };
                    return colors[method] || 'text-gray-600 dark:text-gray-400';
                }
            }"
            @keydown.escape.window="close()"
            class="relative shrink-0"
        >
            <button
                @click="toggle()"
                type="button"
                class="h-10 flex items-center gap-2 px-3 text-sm font-medium transition-colors cursor-pointer w-28 text-left rounded-l-lg
                       focus:outline-none bg-transparent hover:bg-gray-50 dark:hover:bg-gray-700/50"
            >
                <span class="truncate flex-1 font-mono font-bold" :class="getMethodColor(activeMethod)" x-text="activeMethod"></span>
                <svg
                    class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <template x-teleport="body">
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.away="close()"
                    x-anchor.bottom-start.offset.4="$refs.trigger || $root.querySelector('button')"
                    class="fixed z-9999 w-28 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                     <div class="max-h-64 overflow-y-auto beartropy-thin-scrollbar py-1">
                        <template x-for="method in filteredMethods" :key="method">
                            <button
                                @click="select(method)"
                                type="button"
                                class="w-full flex items-center gap-2 px-3 py-2.5 text-xs font-mono font-medium text-left transition-colors cursor-pointer"
                                :class="activeMethod === method
                                    ? 'bg-blue-50 dark:bg-blue-900/30'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <span class="flex-1" :class="getMethodColor(method)" x-text="method"></span>
                                <svg x-show="activeMethod === method" class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Divider --}}
        <div class="w-px h-5 bg-gray-200 dark:bg-gray-700"></div>

        {{-- URL Input --}}
        <div class="flex-1 relative">
            <input
                type="text"
                wire:model="url"
                placeholder="https://api.example.com/endpoint"
                class="w-full h-10 px-3 text-sm bg-transparent border-none
                       text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                       focus:ring-0 focus:outline-none"
            >
        </div>

        {{-- Code Button --}}
        <button
            type="button"
            wire:click="openCodeModal"
            title="Generate code snippet"
            class="h-10 w-10 inline-flex items-center justify-center
                   text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200
                   bg-gray-100 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-700
                   text-sm font-semibold transition-all
                   focus:outline-none cursor-pointer -my-px -mb-px border-l border-l-gray-400 dark:border-l-gray-600"
        >
            <x-bt-icon name="code-bracket" class="w-4 h-4" />
        </button>

        {{-- Send Button --}}
        <button
            type="button"
            wire:click="sendRequest"
            wire:loading.attr="disabled"
            wire:target="sendRequest"
            class="h-10 w-20 inline-flex items-center justify-center gap-1.5
                   text-beartropy-600 dark:text-beartropy-400 hover:bg-beartropy-50 dark:hover:bg-beartropy-900/30
                   text-sm font-semibold rounded-r-lg transition-all
                   focus:outline-none cursor-pointer -mr-px -my-px -mb-px border-l border-l-gray-400 dark:border-l-gray-600
                   disabled:opacity-75 disabled:cursor-wait whitespace-nowrap"
        >
            <div wire:loading.remove wire:target="sendRequest" class="flex items-center gap-1.5">
                <span>Send</span>
                <x-bt-icon name="paper-airplane" class="w-4 h-4" />
            </div>

            <div wire:loading wire:target="sendRequest" class="flex items-center gap-1.5">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </button>
    </div>
</div>
