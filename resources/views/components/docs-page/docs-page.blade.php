<div
    x-data="{ section: 'getting-started' }"
    class="flex h-screen bg-white dark:bg-gray-900"
>
    {{-- Left Navigation --}}
    <nav class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-700 py-4 overflow-y-auto beartropy-thin-scrollbar bg-gray-50 dark:bg-gray-800/50">
        <div class="px-4 mb-4">
            <h1 class="text-base font-bold text-gray-900 dark:text-white">Vaxtly User Guide</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">v{{ config('app.version', '0.1.0') }}</p>
        </div>

        @php
            $navItems = [
                'getting-started' => ['label' => 'Getting Started', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                'requests' => ['label' => 'Making Requests', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                'collections' => ['label' => 'Collections', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
                'environments' => ['label' => 'Environments', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                'scripting' => ['label' => 'Scripting', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>'],
                'code-snippets' => ['label' => 'Code Snippets', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>'],
                'remote-sync' => ['label' => 'Remote Sync', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>'],
                'vault' => ['label' => 'Vault', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'],
                'import-export' => ['label' => 'Import & Export', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>'],
                'workspaces' => ['label' => 'Workspaces', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>'],
                'system-log' => ['label' => 'System Log', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
            ];
        @endphp

        @foreach($navItems as $key => $item)
            <button
                @click="section = '{{ $key }}'"
                type="button"
                class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-left transition-colors cursor-pointer"
                :class="section === '{{ $key }}'
                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium border-r-2 border-blue-500'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200'"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                <span>{{ $item['label'] }}</span>
            </button>
        @endforeach
    </nav>

    {{-- Right Content --}}
    <div class="flex-1 min-w-0 overflow-y-auto beartropy-thin-scrollbar">
        <div class="max-w-3xl mx-auto px-8 py-6">

            {{-- ============================================================ --}}
            {{-- GETTING STARTED --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'getting-started'" x-cloak>
                @include('components.docs-page.sections.getting-started')
            </div>

            {{-- ============================================================ --}}
            {{-- MAKING REQUESTS --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'requests'" x-cloak>
                @include('components.docs-page.sections.requests')
            </div>

            {{-- ============================================================ --}}
            {{-- COLLECTIONS --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'collections'" x-cloak>
                @include('components.docs-page.sections.collections')
            </div>

            {{-- ============================================================ --}}
            {{-- ENVIRONMENTS --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'environments'" x-cloak>
                @include('components.docs-page.sections.environments')
            </div>

            {{-- ============================================================ --}}
            {{-- SCRIPTING --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'scripting'" x-cloak>
                @include('components.docs-page.sections.scripting')
            </div>

            {{-- ============================================================ --}}
            {{-- CODE SNIPPETS --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'code-snippets'" x-cloak>
                @include('components.docs-page.sections.code-snippets')
            </div>

            {{-- ============================================================ --}}
            {{-- REMOTE SYNC --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'remote-sync'" x-cloak>
                @include('components.docs-page.sections.remote-sync')
            </div>

            {{-- ============================================================ --}}
            {{-- VAULT --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'vault'" x-cloak>
                @include('components.docs-page.sections.vault')
            </div>

            {{-- ============================================================ --}}
            {{-- IMPORT & EXPORT --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'import-export'" x-cloak>
                @include('components.docs-page.sections.import-export')
            </div>

            {{-- ============================================================ --}}
            {{-- WORKSPACES --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'workspaces'" x-cloak>
                @include('components.docs-page.sections.workspaces')
            </div>

            {{-- ============================================================ --}}
            {{-- SYSTEM LOG --}}
            {{-- ============================================================ --}}
            <div x-show="section === 'system-log'" x-cloak>
                @include('components.docs-page.sections.system-log')
            </div>

        </div>
    </div>
</div>
