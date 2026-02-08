{{-- Environment Association Modal --}}
@if($showEnvironmentModal && $environmentModalTargetId)
    @php
        $isFolder = $environmentModalTargetType === 'folder';
        $modalTarget = $isFolder
            ? \App\Models\Folder::find($environmentModalTargetId)
            : \App\Models\Collection::find($environmentModalTargetId);
        $allEnvironments = \App\Models\Environment::forWorkspace($this->activeWorkspaceId)->orderByRaw('LOWER(name) ASC')->get();
        $targetLabel = $isFolder ? 'folder' : 'collection';
    @endphp
    @if($modalTarget)
        <x-beartropy-ui::modal
            wire:model="showEnvironmentModal"
            styled
            max-width="sm"
        >
            <x-slot:title>
                Set Environments
            </x-slot:title>

            <div
                x-data="{
                    search: '',
                    environments: @js($allEnvironments->map(fn($e) => ['id' => $e->id, 'name' => $e->name])->values()),
                    associatedIds: @js($modalTarget->getEnvironmentIds()),
                    defaultEnvId: @js($modalTarget->default_environment_id),
                    targetId: @js($modalTarget->id),
                    targetType: @js($environmentModalTargetType),
                    get filtered() {
                        if (!this.search) return this.environments;
                        const s = this.search.toLowerCase();
                        return this.environments.filter(e => e.name.toLowerCase().includes(s));
                    },
                    isAssociated(id) {
                        return this.associatedIds.includes(id);
                    },
                    toggle(id) {
                        if (this.isAssociated(id)) {
                            this.associatedIds = this.associatedIds.filter(i => i !== id);
                            if (this.defaultEnvId === id) this.defaultEnvId = null;
                        } else {
                            this.associatedIds.push(id);
                        }
                        if (this.targetType === 'folder') {
                            $wire.toggleFolderEnvironment(this.targetId, id);
                        } else {
                            $wire.toggleCollectionEnvironment(this.targetId, id);
                        }
                    },
                    toggleDefault(id) {
                        if (this.defaultEnvId === id) {
                            this.defaultEnvId = null;
                        } else {
                            this.defaultEnvId = id;
                            if (!this.isAssociated(id)) {
                                this.associatedIds.push(id);
                            }
                        }
                        if (this.targetType === 'folder') {
                            $wire.setFolderDefaultEnvironment(this.targetId, id);
                        } else {
                            $wire.setCollectionDefaultEnvironment(this.targetId, id);
                        }
                    },
                    get defaultEnvName() {
                        if (!this.defaultEnvId) return null;
                        const env = this.environments.find(e => e.id === this.defaultEnvId);
                        return env ? env.name : null;
                    }
                }"
                class="space-y-3"
            >
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Select environments for <span class="font-medium text-gray-700 dark:text-gray-300">{{ $modalTarget->name }}</span>
                    <span class="text-gray-400 dark:text-gray-500">({{ $targetLabel }})</span>
                </p>

                @if($allEnvironments->isEmpty())
                    <div class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">
                        No environments yet. Create one from the Environments panel.
                    </div>
                @else
                    {{-- Search --}}
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            x-model="search"
                            x-init="$nextTick(() => $el.focus())"
                            type="text"
                            placeholder="Search environments..."
                            class="w-full pl-8 pr-3 py-1.5 text-sm bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-md
                                   text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500"
                        >
                    </div>

                    {{-- Environment list --}}
                    <div class="space-y-0.5 max-h-64 overflow-y-auto beartropy-thin-scrollbar">
                        <template x-for="env in filtered" :key="env.id">
                            <div
                                class="flex items-center gap-2 px-2 py-2 rounded-md transition-colors cursor-pointer group"
                                :class="isAssociated(env.id) ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800'"
                                @click="toggle(env.id)"
                            >
                                {{-- Checkbox --}}
                                <span
                                    class="shrink-0 w-4 h-4 rounded border flex items-center justify-center transition-colors"
                                    :class="isAssociated(env.id)
                                        ? 'bg-blue-500 border-blue-500 text-white'
                                        : 'border-gray-300 dark:border-gray-600'"
                                >
                                    <svg x-show="isAssociated(env.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>

                                {{-- Env name --}}
                                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate" x-text="env.name"></span>

                                {{-- Default star (only for associated envs) --}}
                                <button
                                    x-show="isAssociated(env.id)"
                                    @click.stop="toggleDefault(env.id)"
                                    class="shrink-0 cursor-pointer transition-colors"
                                    :class="defaultEnvId === env.id ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600 opacity-0 group-hover:opacity-100 hover:text-amber-400'"
                                    :title="defaultEnvId === env.id ? 'Remove as default' : 'Set as default (auto-activates when opening requests)'"
                                >
                                    <svg class="w-4 h-4" :fill="defaultEnvId === env.id ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        {{-- No results --}}
                        <div x-show="filtered.length === 0 && search" class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">
                            No environments match "<span x-text="search"></span>"
                        </div>
                    </div>

                    {{-- Default env hint --}}
                    <template x-if="defaultEnvName">
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-1">
                            <svg class="w-3 h-3 text-amber-400" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            <span><strong x-text="defaultEnvName"></strong> auto-activates when opening requests</span>
                        </p>
                    </template>
                @endif
            </div>

            <x-slot:footer>
                <x-beartropy-ui::button tint wire:click="closeEnvironmentModal" sm>
                    Done
                </x-beartropy-ui::button>
            </x-slot:footer>
        </x-beartropy-ui::modal>
    @endif
@endif
