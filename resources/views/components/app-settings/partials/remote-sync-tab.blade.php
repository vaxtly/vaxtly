{{-- Remote Tab --}}
<div class="space-y-4 max-h-[60vh] overflow-y-auto beartropy-thin-scrollbar pr-2">
    {{-- Section Header --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">Git Remote Sync</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Sync collections to a GitHub or GitLab repository</p>
        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">Workspace: {{ app(\App\Services\WorkspaceService::class)->active()->name }}</p>
    </div>

    {{-- Provider --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
        <div class="w-full">
            <x-beartropy-ui::select
                wire:model.live="remoteProvider"
                :options="['' => 'Select provider...', 'github' => 'GitHub', 'gitlab' => 'GitLab']"
                :clearable="false"
                :searchable="false"
                sm
            />
        </div>
    </div>

    {{-- Repository --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Repository</label>
        <x-beartropy-ui::input
            wire:model="remoteRepository"
            placeholder="{{ $remoteProvider === 'gitlab' ? 'group/subgroup/project-name' : 'owner/repo-name' }}"
            sm
        />
    </div>

    {{-- Token --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Personal Access Token</label>
        <x-beartropy-ui::input
            wire:model="remoteToken"
            type="password"
            placeholder="ghp_... or glpat-..."
            sm
        />
    </div>

    {{-- Branch --}}
    <div>
        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Branch</label>
        <x-beartropy-ui::input
            wire:model="remoteBranch"
            placeholder="main"
            sm
        />
    </div>

    {{-- Auto-sync toggle --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Auto-sync on start</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Pull remote changes when the app loads</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model.live="remoteAutoSync" class="sr-only peer">
            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-gray-600 peer-checked:bg-blue-500"></div>
        </label>
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Status Message --}}
    @if(!empty($remoteStatus))
        <div class="p-3 rounded-lg text-sm {{ str_contains($remoteStatus, 'successful') || str_contains($remoteStatus, 'saved') ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300' : (str_contains($remoteStatus, 'Error') || str_contains($remoteStatus, 'failed') ? 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300') }}">
            {{ $remoteStatus }}
        </div>
    @endif

    {{-- Sync Result --}}
    @if(!empty($syncResult))
        <div class="p-3 rounded-lg text-sm {{ empty($syncResult['errors'] ?? []) ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' }}">
            @if(isset($syncResult['pulled']) && $syncResult['pulled'] > 0)
                <p>Pulled {{ $syncResult['pulled'] }} collection(s)</p>
            @endif
            @if(isset($syncResult['pushed']) && $syncResult['pushed'] > 0)
                <p>Pushed {{ $syncResult['pushed'] }} collection(s)</p>
            @endif
            @if(isset($syncResult['pulled']) && $syncResult['pulled'] === 0 && isset($syncResult['pushed']) && $syncResult['pushed'] === 0 && empty($syncResult['errors'] ?? []))
                <p>Everything is up to date.</p>
            @endif
            @if(!empty($syncResult['errors'] ?? []))
                <ul class="mt-1 text-xs list-disc list-inside">
                    @foreach($syncResult['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-2">
        <x-beartropy-ui::button
            wire:click="saveRemoteSettings"
            tint
            sm
        >
            Save
        </x-beartropy-ui::button>

        <x-beartropy-ui::button
            wire:click="testConnection"
            wire:loading.attr="disabled"
            wire:target="testConnection"
            tint
            sm
        >
            <span wire:loading.remove wire:target="testConnection">Test Connection</span>
            <span wire:loading wire:target="testConnection" class="flex items-center gap-1">
                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Testing...
            </span>
        </x-beartropy-ui::button>
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Sync Actions --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">Sync Actions</p>
        <div class="flex gap-2">
            <x-beartropy-ui::button
                wire:click="syncNow"
                wire:loading.attr="disabled"
                wire:target="syncNow, pushAll"
                primary
                sm
            >
                <span wire:loading.remove wire:target="syncNow">Pull Now</span>
                <span wire:loading wire:target="syncNow" class="flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Pulling...
                </span>
            </x-beartropy-ui::button>

            <x-beartropy-ui::button
                wire:click="pushAll"
                wire:loading.attr="disabled"
                wire:target="syncNow, pushAll"
                tint
                sm
            >
                <span wire:loading.remove wire:target="pushAll">Push All</span>
                <span wire:loading wire:target="pushAll" class="flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Pushing...
                </span>
            </x-beartropy-ui::button>
        </div>
    </div>

    {{-- Help Text --}}
    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">How it works:</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <li class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                </svg>
                Enable sync per collection via the sidebar toggle
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Pull downloads remote changes, Push uploads local changes
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Conflicts are detected when both sides change
            </li>
        </ul>
    </div>
</div>
