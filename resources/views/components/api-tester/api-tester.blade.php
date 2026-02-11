<div
    class="flex h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden"
    x-data="{
        sidebarVisible: true,
        handleShortcut(e) {
            const mod = e.metaKey || e.ctrlKey;
            const tag = (e.target.tagName || '').toLowerCase();
            const inInput = (tag === 'input' || tag === 'textarea' || e.target.isContentEditable);

            {{-- Ctrl/Cmd+Enter — send request (works in inputs) --}}
            if (mod && e.key === 'Enter') {
                e.preventDefault();
                $dispatch('send-request');
                return;
            }

            {{-- Ctrl/Cmd+S — save request (works in inputs) --}}
            if (mod && e.key === 's') {
                e.preventDefault();
                Livewire.dispatch('save-request');
                return;
            }

            {{-- Skip remaining shortcuts when typing in inputs --}}
            if (inInput) return;

            {{-- Ctrl/Cmd+N — new request --}}
            if (mod && e.key === 'n') {
                e.preventDefault();
                Livewire.dispatch('shortcut-new-request');
                return;
            }

            {{-- Ctrl/Cmd+W — close active tab (skip pinned) --}}
            if (mod && e.key === 'w') {
                e.preventDefault();
                if ($wire.activeTabId) {
                    const tab = $wire.openTabs.find(t => t.id === $wire.activeTabId);
                    if (tab && !tab.pinned) $wire.closeTab($wire.activeTabId);
                }
                return;
            }

            {{-- Ctrl+PageDown — next tab --}}
            if (e.ctrlKey && e.key === 'PageDown') {
                e.preventDefault();
                const tabs = $wire.openTabs;
                if (tabs.length < 2) return;
                const idx = tabs.findIndex(t => t.id === $wire.activeTabId);
                const next = tabs[(idx + 1) % tabs.length];
                Livewire.dispatch('switch-tab', { tabId: next.id, type: next.type || 'request', requestId: next.requestId || null, environmentId: next.environmentId || null });
                return;
            }

            {{-- Ctrl+PageUp — previous tab --}}
            if (e.ctrlKey && e.key === 'PageUp') {
                e.preventDefault();
                const tabs = $wire.openTabs;
                if (tabs.length < 2) return;
                const idx = tabs.findIndex(t => t.id === $wire.activeTabId);
                const prev = tabs[(idx - 1 + tabs.length) % tabs.length];
                Livewire.dispatch('switch-tab', { tabId: prev.id, type: prev.type || 'request', requestId: prev.requestId || null, environmentId: prev.environmentId || null });
                return;
            }

            {{-- Ctrl/Cmd+L — focus URL bar --}}
            if (mod && e.key === 'l') {
                e.preventDefault();
                document.querySelector('[data-url-input]')?.focus();
                return;
            }

            {{-- Ctrl/Cmd+P — focus sidebar search --}}
            if (mod && e.key === 'p') {
                e.preventDefault();
                if (!this.sidebarVisible) this.sidebarVisible = true;
                $dispatch('focus-sidebar-search');
                return;
            }

            {{-- Ctrl/Cmd+B — toggle sidebar --}}
            if (mod && e.key === 'b') {
                e.preventDefault();
                this.sidebarVisible = !this.sidebarVisible;
                return;
            }

            {{-- Ctrl/Cmd+E — cycle environment --}}
            if (mod && e.key === 'e') {
                e.preventDefault();
                Livewire.dispatch('shortcut-cycle-environment');
                return;
            }

            {{-- F1 — help --}}
            if (e.key === 'F1') {
                e.preventDefault();
                Livewire.dispatch('open-help-modal');
                return;
            }
        }
    }"
    x-on:keydown.window="handleShortcut($event)"
    x-init="$nextTick(() => Livewire.dispatch('run-auto-sync'))"
>
    {{-- Invisible auto-sync runner (own Livewire pipeline, won't block UI) --}}
    <livewire:auto-sync-runner />

    {{-- Sidebar --}}
    <div
        x-show="sidebarVisible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-4"
        class="w-80 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0"
    >
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
