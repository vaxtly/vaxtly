@php
    $environments = $this->environments;
    $activeEnvId = $this->activeEnvironmentId;
    $currentCollectionId = $this->currentCollectionId;
    $currentFolderId = $this->currentFolderId;
    $currentCollection = $currentCollectionId ? $this->collections->firstWhere('id', $currentCollectionId) : null;

    // Resolve effective context: folder tree first, then collection
    $effectiveContext = 'none';
    $associatedIds = [];
    $defaultEnvId = null;

    if ($currentFolderId) {
        $contextFolder = \App\Models\Folder::find($currentFolderId)?->resolveEnvironmentFolder();
        if ($contextFolder) {
            $effectiveContext = 'folder';
            $associatedIds = $contextFolder->getEnvironmentIds();
            $defaultEnvId = $contextFolder->default_environment_id;
        }
    }

    if ($effectiveContext === 'none' && $currentCollection) {
        $collectionEnvIds = $currentCollection->getEnvironmentIds();
        if (!empty($collectionEnvIds)) {
            $effectiveContext = 'collection';
            $associatedIds = $collectionEnvIds;
            $defaultEnvId = $currentCollection->default_environment_id;
        } elseif ($currentCollectionId) {
            $effectiveContext = 'collection';
        }
    }
@endphp

<div
    wire:key="env-selector-{{ $activeEnvId }}-{{ $currentCollectionId }}-{{ $currentFolderId }}-{{ $environments->count() }}"
    x-data="{
        open: false,
        search: '',
        showAll: false,
        environments: @js($environments->map(fn($e) => ['id' => $e->id, 'name' => $e->name])->values()),
        activeId: @js($activeEnvId),
        associatedIds: @js($associatedIds),
        defaultEnvId: @js($defaultEnvId),
        effectiveContext: @js($effectiveContext),
        currentCollectionId: @js($currentCollectionId),
        currentFolderId: @js($currentFolderId),
        get filteredAssociated() {
            if (this.associatedIds.length === 0) return [];
            const s = this.search?.toLowerCase() || '';
            return this.environments.filter(e => this.associatedIds.includes(e.id) && (!s || e.name.toLowerCase().includes(s)));
        },
        get filteredOther() {
            if (this.associatedIds.length === 0) {
                if (!this.search) return this.environments;
                const s = this.search.toLowerCase();
                return this.environments.filter(e => e.name.toLowerCase().includes(s));
            }
            const s = this.search?.toLowerCase() || '';
            return this.environments.filter(e => !this.associatedIds.includes(e.id) && (!s || e.name.toLowerCase().includes(s)));
        },
        get hasResults() {
            return this.filteredAssociated.length > 0 || this.filteredOther.length > 0;
        },
        get activeName() {
            const env = this.environments.find(e => e.id === this.activeId);
            return env ? env.name : 'No Environment';
        },
        get contextLabel() {
            return this.effectiveContext === 'folder' ? 'Folder' : 'Collection';
        },
        select(id) {
            this.activeId = id;
            $wire.setActiveEnvironment(id);
            this.close();
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
        }
    }"
    @keydown.escape.window="close()"
    x-on:env-activated.window="activeId = $event.detail.envId"
    class="relative"
>
    <div class="flex items-center gap-1">
        {{-- Lock Button --}}
        <button
            wire:click="toggleEnvLock"
            type="button"
            class="flex items-center justify-center w-7 h-7 rounded-md border transition-colors cursor-pointer
                   focus:outline-none focus:ring-2 focus:ring-blue-500/50
                   {{ $this->envLocked
                       ? 'bg-amber-50 dark:bg-amber-900/30 border-amber-300 dark:border-amber-600 text-amber-600 dark:text-amber-400'
                       : 'bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300' }}"
            title="{{ $this->envLocked ? 'Unlock environment (allow auto-switch)' : 'Lock environment (prevent auto-switch)' }}"
        >
            @if($this->envLocked)
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            @else
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
            @endif
        </button>

        {{-- Trigger Button --}}
        <button
            x-ref="trigger"
            @click="toggle()"
            type="button"
            class="flex items-center gap-2 px-2.5 py-1.5 text-xs font-medium rounded-md border transition-colors cursor-pointer
                   bg-white dark:bg-gray-800/80
                   border-gray-300 dark:border-gray-600
                   text-gray-700 dark:text-gray-300
                   hover:bg-gray-50 dark:hover:bg-gray-700
                   focus:outline-none focus:ring-2 focus:ring-blue-500/50 min-w-60 justify-between"
        >
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="truncate max-w-56" x-text="activeName"></span>
            <svg
                class="w-4 h-4 text-gray-400 shrink-0 transition-transform"
                :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- Dropdown (teleported to body) --}}
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
            x-anchor.bottom-end.offset.4="$refs.trigger || $root.querySelector('button')"
            class="fixed z-[9999] w-72 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            x-ref="dropdown"
        >
            {{-- Search --}}
            <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        x-ref="searchInput"
                        x-model="search"
                        type="text"
                        placeholder="Search environments..."
                        class="w-full pl-8 pr-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-md
                               text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                               focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
                    >
                </div>
            </div>

            {{-- Options --}}
            <div class="max-h-64 overflow-y-auto beartropy-thin-scrollbar">
                {{-- No Environment option --}}
                <button
                    @click="select('')"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                    :class="!activeId
                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                >
                    <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span>No Environment</span>
                    <svg x-show="!activeId" class="w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>

                {{-- Scenario A: Context has associated envs --}}
                <template x-if="associatedIds.length > 0">
                    <div>
                        <div class="px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500" x-text="contextLabel"></div>
                        <template x-for="env in filteredAssociated" :key="'assoc-' + env.id">
                            <button
                                @click="select(env.id)"
                                type="button"
                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                                :class="activeId === env.id
                                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="truncate" x-text="env.name"></span>
                                <template x-if="defaultEnvId === env.id">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-amber-400" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </template>
                                <svg x-show="activeId === env.id" class="w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>

                        {{-- Show all toggle --}}
                        <template x-if="filteredOther.length > 0">
                            <div>
                                <button
                                    @click="showAll = !showAll"
                                    type="button"
                                    class="w-full px-3 py-1.5 text-[11px] text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors cursor-pointer text-left"
                                >
                                    <span x-text="showAll ? 'Hide others' : 'Show all environments'"></span>
                                </button>

                                {{-- Other environments (when expanded) --}}
                                <template x-if="showAll">
                                    <div>
                                        <div class="border-t border-gray-100 dark:border-gray-700"></div>
                                        <template x-for="env in filteredOther" :key="'other-' + env.id">
                                            <button
                                                @click="select(env.id)"
                                                type="button"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                                                :class="activeId === env.id
                                                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                                            >
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                <span class="truncate" x-text="env.name"></span>
                                                <svg x-show="activeId === env.id" class="w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Scenario B: Context exists but no envs assigned --}}
                <template x-if="effectiveContext !== 'none' && associatedIds.length === 0 && currentCollectionId">
                    <div>
                        <button
                            @click="$wire.dispatch('open-env-modal-for-context', { type: currentFolderId ? 'folder' : 'collection', id: currentFolderId || currentCollectionId }); close()"
                            type="button"
                            class="w-full flex items-center gap-2 px-3 py-2.5 text-xs text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span x-text="'Add environments to this ' + contextLabel.toLowerCase()"></span>
                        </button>

                        {{-- Still show all environments --}}
                        <div class="border-t border-gray-100 dark:border-gray-700"></div>
                        <template x-for="env in filteredOther" :key="'other-' + env.id">
                            <button
                                @click="select(env.id)"
                                type="button"
                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                                :class="activeId === env.id
                                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="truncate" x-text="env.name"></span>
                                <svg x-show="activeId === env.id" class="w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Scenario C: No context (no active tab) --}}
                <template x-if="effectiveContext === 'none' || !currentCollectionId">
                    <div>
                        <template x-for="env in filteredOther" :key="'all-' + env.id">
                            <button
                                @click="select(env.id)"
                                type="button"
                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                                :class="activeId === env.id
                                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="truncate" x-text="env.name"></span>
                                <svg x-show="activeId === env.id" class="w-4 h-4 ml-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Empty state --}}
                <div x-show="!hasResults && search" class="px-3 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                    No environments match "<span x-text="search"></span>"
                </div>
            </div>
        </div>
    </template>
</div>
