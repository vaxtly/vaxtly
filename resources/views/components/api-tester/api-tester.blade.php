<div class="flex h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden">
    {{-- Sidebar --}}
    <div class="w-80 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
        <livewire:sidebar />
    </div>

    {{-- Main Column --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">

        {{-- Content Area (RequestBuilder / EnvironmentEditor) --}}
        <div
            class="flex-1 min-h-0 relative flex flex-col overflow-hidden"
            x-data="{
                get activeTabType() {
                    const tab = $wire.openTabs.find(t => t.id === $wire.activeTabId);
                    return tab?.type || 'request';
                }
            }"
        >
            @include('components.api-tester.partials.tab-bar')

            <div class="flex-1 min-h-0" x-show="$wire.activeTabId && activeTabType === 'request'" x-cloak>
                <livewire:request-builder
                    :initial-active-tab-id="$activeTabId"
                    :initial-request-id="$this->currentRequestId"
                    wire:key="request-builder"
                />
            </div>

            <div class="flex-1 min-h-0" x-show="$wire.activeTabId && activeTabType === 'environment'" x-cloak>
                <livewire:environment-editor wire:key="environment-editor" />
            </div>

            <div class="flex-1 flex items-center justify-center text-gray-400" x-show="!$wire.activeTabId" x-cloak>
                <p>Select a request or environment from the sidebar</p>
            </div>
        </div>

        {{-- System Log Footer --}}
        <div class="shrink-0">
            <livewire:system-log :is-expanded="false" />
        </div>
    </div>

    {{-- Help Modal --}}
    @include('components.api-tester.partials.help-modal')

    {{-- Welcome Modal --}}
    @include('components.api-tester.partials.welcome-modal')
</div>
