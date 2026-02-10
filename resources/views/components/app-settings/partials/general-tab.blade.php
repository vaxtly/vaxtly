{{-- General Tab --}}
<div class="space-y-4 max-h-[60vh] overflow-y-auto beartropy-thin-scrollbar pr-2">
    {{-- Theme Toggle --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Theme</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Switch between light and dark mode</p>
        </div>
        <x-bt-toggle-theme />
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Request Layout --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Request Layout</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Choose how requests and responses are displayed</p>
        </div>
        <div class="w-32">
            <x-beartropy-ui::select
                wire:model.live="layout"
                :options="['rows' => 'Rows', 'columns' => 'Columns']"
                :clearable="false"
                :searchable="false"
                sm
            />
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Request Timeout --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Request Timeout</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Maximum time to wait for a response (1-300 seconds)</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-20">
                <x-beartropy-ui::input type="number" wire:model.live.debounce.500ms="timeout" min="1" max="300" sm />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">seconds</span>
        </div>
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Verify SSL --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Verify SSL</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Verify SSL certificates for requests</p>
        </div>
        <x-beartropy-ui::toggle wire:model.live="verifySsl" />
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Follow Redirects --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">Follow Redirects</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Automatically follow HTTP redirects</p>
        </div>
        <x-beartropy-ui::toggle wire:model.live="followRedirects" />
    </div>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- App Info --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">About</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vaxtly v{{ config('app.version') }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Built with Laravel & Livewire</p>
    </div>
</div>
