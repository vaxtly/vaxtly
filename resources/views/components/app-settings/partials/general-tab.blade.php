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

    {{-- App Info --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">About</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">API Tester v1.0</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Built with Laravel & Livewire</p>
    </div>
</div>
