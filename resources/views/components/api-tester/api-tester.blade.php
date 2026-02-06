<div class="flex h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden">
    {{-- Sidebar --}}
    <div class="w-80 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
        <livewire:sidebar :mode="$viewMode" wire:model="selectedEnvironmentId" />
    </div>

    {{-- Main Column --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        
        {{-- Contenedor del Contenido (RequestBuilder / Editor) --}}
        <div class="flex-1 min-h-0 relative flex flex-col overflow-hidden">
            @if($viewMode === 'collections')
                @include('components.api-tester.partials.tab-bar')

                @if($activeTabId)
                    {{-- ESTE DIV ES VITAL: permite que el hijo se encoja --}}
                    <div class="flex-1 min-h-0">
                        <livewire:request-builder
                            :initial-active-tab-id="$activeTabId"
                            :initial-request-id="$this->currentRequestId"
                            wire:key="request-builder"
                        />
                    </div>
                @else
                    <div class="flex-1 flex items-center justify-center text-gray-400">
                        <p>Select a request from the sidebar</p>
                    </div>
                @endif
            @else
                <div class="flex-1 min-h-0">
                    <livewire:environment-editor :environmentId="$selectedEnvironmentId" />
                </div>
            @endif
        </div>

        {{-- System Log Footer (Ahora SIEMPRE será empujado) --}}
        <div class="shrink-0">
            <livewire:system-log :is-expanded="false" />
        </div>
    </div>

    {{-- Help Modal --}}
    @include('components.api-tester.partials.help-modal')
</div>