<div
    class="flex items-center justify-between gap-2 h-10 pl-2 pr-2 bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800"
    x-data="{
        methodColor(method) {
            return {
                'GET': 'text-emerald-600 dark:text-emerald-400',
                'POST': 'text-blue-600 dark:text-blue-400',
                'PUT': 'text-amber-600 dark:text-amber-400',
                'PATCH': 'text-orange-600 dark:text-orange-400',
                'DELETE': 'text-red-600 dark:text-red-400',
            }[(method || '').toUpperCase()] || 'text-gray-600 dark:text-gray-400';
        },
        ctxMenu: { open: false, x: 0, y: 0, tabId: null, pinned: false },
        openCtxMenu(e, tab) {
            this.ctxMenu = { open: true, x: e.clientX, y: e.clientY, tabId: tab.id, pinned: !!tab.pinned };
        },
        closeCtxMenu() {
            this.ctxMenu.open = false;
        },
        lastPinnedIndex() {
            let last = -1;
            $wire.openTabs.forEach((t, i) => { if (t.pinned) last = i; });
            return last;
        }
    }"
    @click.away="closeCtxMenu()"
    @keydown.escape.window="closeCtxMenu()"
>
    {{-- Tabs --}}
    <div class="flex items-end gap-1 overflow-x-auto overflow-y-hidden beartropy-thin-scrollbar min-w-0 flex-1 pt-1.5" wire:ignore>
        <template x-for="(tab, index) in $wire.openTabs" :key="tab.id">
            <div class="flex items-end">
                <div
                    @click="Livewire.dispatch('switch-tab', { tabId: tab.id, type: tab.type || 'request', requestId: tab.requestId || null, environmentId: tab.environmentId || null })"
                    @auxclick.prevent="if ($event.button === 1 && !tab.pinned) $wire.closeTab(tab.id)"
                    @contextmenu.prevent="openCtxMenu($event, tab)"
                    class="group relative flex items-center gap-2 px-3 py-2 text-xs font-medium cursor-pointer transition-all rounded-t-lg select-none mr-1 min-w-30 max-w-50 border-t border-l border-r"
                    :class="$wire.activeTabId === tab.id
                        ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-200 dark:border-gray-700 shadow-sm z-10 -mb-px pb-2.5'
                        : 'bg-transparent border-gray-200/50 dark:border-gray-700/50 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-200/50 dark:hover:bg-gray-800/50'"
                >
                    {{-- Pin icon --}}
                    <template x-if="tab.pinned">
                        <svg class="w-3 h-3 text-gray-400 dark:text-gray-500 shrink-0" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.342 1.174-.646 1.479-.126.125-.25.224-.354.298v4.431l.078.048c.203.127.476.314.751.555C12.36 7.775 13 8.527 13 9.5a.5.5 0 0 1-.5.5h-4v4.5a.5.5 0 0 1-1 0V10h-4A.5.5 0 0 1 3 9.5c0-.973.64-1.725 1.17-2.189A6 6 0 0 1 5 6.708V2.277a3 3 0 0 1-.354-.298C4.342 1.674 4 1.179 4 .5a.5.5 0 0 1 .146-.354"/>
                        </svg>
                    </template>

                    <template x-if="(tab.type || 'request') === 'request'">
                        <span class="font-mono font-bold tracking-tighter" :class="methodColor(tab.method)" x-text="(tab.method || '').toUpperCase()"></span>
                    </template>
                    <template x-if="tab.type === 'environment'">
                        <span class="text-purple-500 dark:text-purple-400 flex items-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </span>
                    </template>

                    <span class="truncate flex-1" :class="$wire.activeTabId === tab.id ? 'font-semibold' : ''" x-text="tab.name || 'Untitled'"></span>

                    <button
                        x-show="!tab.pinned"
                        @click.stop="$wire.closeTab(tab.id)"
                        class="p-0.5 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer shrink-0"
                        title="Close tab"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <div x-show="$wire.activeTabId === tab.id" class="absolute bottom-0 left-0 right-0 h-px bg-white dark:bg-gray-800 translate-y-px"></div>
                </div>

                {{-- Separator after last pinned tab --}}
                <template x-if="tab.pinned && index === lastPinnedIndex() && index < $wire.openTabs.length - 1 && !$wire.openTabs[index + 1]?.pinned">
                    <div class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-0.5 shrink-0 self-center"></div>
                </template>
            </div>
        </template>
    </div>

    {{-- Context Menu --}}
    <div
        x-show="ctxMenu.open"
        x-cloak
        @click.away="closeCtxMenu()"
        :style="`position: fixed; left: ${ctxMenu.x}px; top: ${ctxMenu.y}px;`"
        class="z-50 min-w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-1 text-xs"
    >
        <template x-if="!ctxMenu.pinned">
            <button @click="$wire.pinTab(ctxMenu.tabId); closeCtxMenu()" class="w-full text-left px-3 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-2 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16"><path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.342 1.174-.646 1.479-.126.125-.25.224-.354.298v4.431l.078.048c.203.127.476.314.751.555C12.36 7.775 13 8.527 13 9.5a.5.5 0 0 1-.5.5h-4v4.5a.5.5 0 0 1-1 0V10h-4A.5.5 0 0 1 3 9.5c0-.973.64-1.725 1.17-2.189A6 6 0 0 1 5 6.708V2.277a3 3 0 0 1-.354-.298C4.342 1.674 4 1.179 4 .5a.5.5 0 0 1 .146-.354"/></svg>
                Pin Tab
            </button>
        </template>
        <template x-if="ctxMenu.pinned">
            <button @click="$wire.unpinTab(ctxMenu.tabId); closeCtxMenu()" class="w-full text-left px-3 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-2 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 16 16"><path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.342 1.174-.646 1.479-.126.125-.25.224-.354.298v4.431l.078.048c.203.127.476.314.751.555C12.36 7.775 13 8.527 13 9.5a.5.5 0 0 1-.5.5h-4v4.5a.5.5 0 0 1-1 0V10h-4A.5.5 0 0 1 3 9.5c0-.973.64-1.725 1.17-2.189A6 6 0 0 1 5 6.708V2.277a3 3 0 0 1-.354-.298C4.342 1.674 4 1.179 4 .5a.5.5 0 0 1 .146-.354"/></svg>
                Unpin Tab
            </button>
        </template>

        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

        <template x-if="!ctxMenu.pinned">
            <button @click="$wire.closeTab(ctxMenu.tabId); closeCtxMenu()" class="w-full text-left px-3 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-2 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Close Tab
            </button>
        </template>
        <button @click="$wire.closeOtherTabs(ctxMenu.tabId); closeCtxMenu()" class="w-full text-left px-3 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-2 cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Close Other Tabs
        </button>
    </div>

    {{-- Environment Selector + Help --}}
    <div class="flex items-center gap-1.5">
        @include('components.api-tester.partials.environment-selector')

        <button
            @click="$dispatch('open-welcome-modal')"
            type="button"
            class="flex items-center justify-center w-7 h-7 rounded-md border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors cursor-pointer"
            title="Welcome Guide"
        >
            <x-bt-icon name="sparkles" class="w-4 h-4" />
        </button>

        <button
            @click="$dispatch('open-help-modal')"
            type="button"
            class="flex items-center justify-center w-7 h-7 rounded-md border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors cursor-pointer text-xs font-bold"
            title="User Guide"
        >
            ?
        </button>
    </div>
</div>
