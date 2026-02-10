{{-- Request Tabs --}}
<div x-data="{ activeTab: 'params' }">
    {{-- Tab Buttons --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-3">
        <button
            @click="activeTab = 'params'"
            :class="activeTab === 'params' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Params
            <template x-if="$wire.queryParams.filter(p => p.key && (p.enabled ?? true)).length > 0">
                <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full" x-text="$wire.queryParams.filter(p => p.key && (p.enabled ?? true)).length"></span>
            </template>
        </button>
        <button
            @click="activeTab = 'headers'"
            :class="activeTab === 'headers' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Headers
            <template x-if="$wire.headers.filter(h => h.key && (h.enabled ?? true)).length > 0">
                <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full" x-text="$wire.headers.filter(h => h.key && (h.enabled ?? true)).length"></span>
            </template>
        </button>
        <button
            @click="activeTab = 'body'"
            :class="activeTab === 'body' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Body
            <span class="ml-1 w-2 h-2 inline-block bg-green-500 rounded-full" x-show="$wire.body" x-cloak></span>
        </button>
        <button
            @click="activeTab = 'auth'"
            :class="activeTab === 'auth' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Auth
        </button>
        <button
            @click="activeTab = 'scripts'"
            :class="activeTab === 'scripts' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Scripts
            <span class="ml-1 w-2 h-2 inline-block bg-purple-500 rounded-full" x-show="$wire.preRequestScripts.length > 0 || $wire.postResponseScripts.length > 0" x-cloak></span>
        </button>
    </div>

    {{-- Params Tab --}}
    <div x-show="activeTab === 'params'" x-cloak>
        <div class="space-y-2" wire:ignore>
            <template x-for="(param, index) in $wire.queryParams" :key="index">
                <div class="flex gap-2 items-center">
                    <button
                        @click="$wire.queryParams[index].enabled = !(param.enabled ?? true)"
                        type="button"
                        class="w-6 flex items-center justify-center p-0.5 rounded transition-colors cursor-pointer"
                        :class="(param.enabled ?? true) ? 'text-green-500' : 'text-gray-300 dark:text-gray-600'"
                    >
                        <svg x-show="param.enabled ?? true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg x-show="!(param.enabled ?? true)" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="flex-1">
                        <input
                            x-model="$wire.queryParams[index].key"
                            placeholder="Parameter name"
                            :disabled="!(param.enabled ?? true)"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors disabled:opacity-40"
                        >
                    </div>
                    <div class="flex-1 relative">
                        <input
                            x-model="$wire.queryParams[index].value"
                            x-var-highlight="$wire.resolvedVariableNames"
                            placeholder="Parameter value"
                            :disabled="!(param.enabled ?? true)"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors disabled:opacity-40"
                        >
                    </div>
                    <button
                        @click="$wire.removeQueryParam(index)"
                        type="button"
                        class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
            <button
                @click="$wire.addQueryParam()"
                type="button"
                class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
            >
                + Add Parameter
            </button>
        </div>
    </div>

    {{-- Headers Tab --}}
    <div x-show="activeTab === 'headers'" x-cloak>
        <div class="space-y-2" wire:ignore>
            <template x-for="(header, index) in $wire.headers" :key="index">
                <div class="flex gap-2 items-center">
                    <button
                        @click="$wire.headers[index].enabled = !(header.enabled ?? true)"
                        type="button"
                        class="w-6 flex items-center justify-center p-0.5 rounded transition-colors cursor-pointer"
                        :class="(header.enabled ?? true) ? 'text-green-500' : 'text-gray-300 dark:text-gray-600'"
                    >
                        <svg x-show="header.enabled ?? true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg x-show="!(header.enabled ?? true)" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="flex-1">
                        <input
                            x-model="$wire.headers[index].key"
                            placeholder="Header name"
                            :disabled="!(header.enabled ?? true)"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors disabled:opacity-40"
                        >
                    </div>
                    <div class="flex-1 relative">
                        <input
                            x-model="$wire.headers[index].value"
                            x-var-highlight="$wire.resolvedVariableNames"
                            placeholder="Header value"
                            :disabled="!(header.enabled ?? true)"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors disabled:opacity-40"
                        >
                    </div>
                    <button
                        @click="$wire.removeHeader(index)"
                        type="button"
                        class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
            <button
                @click="$wire.addHeader()"
                type="button"
                class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
            >
                + Add Header
            </button>
        </div>
    </div>

    {{-- Body Tab --}}
    <div x-show="activeTab === 'body'" x-cloak>
        <div class="space-y-3"
            x-data="{
                bodyOpen: false,
                bodyTypeLabels: { none: 'None', json: 'JSON', 'form-data': 'Form Data', urlencoded: 'x-www-form-urlencoded', raw: 'Raw' },
                bodyTypeKeys: ['none', 'json', 'form-data', 'urlencoded', 'raw'],
            }"
        >
            <div @click.away="bodyOpen = false" class="relative w-48">
                <button
                    @click="bodyOpen = !bodyOpen"
                    type="button"
                    class="w-full h-[34px] flex items-center justify-between gap-2 px-2 rounded-md text-sm font-medium shadow-sm bg-white dark:bg-gray-800/80 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="truncate" x-text="bodyTypeLabels[$wire.bodyType] || 'None'"></span>
                    </div>
                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 transition-transform" :class="bodyOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="bodyOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    style="display: none;"
                    class="absolute left-0 right-0 z-20 mt-1 origin-top rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div class="p-1">
                        <template x-for="bt in bodyTypeKeys" :key="bt">
                            <button
                                @click="$wire.set('bodyType', bt); bodyOpen = false"
                                type="button"
                                class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer"
                                :class="$wire.bodyType === bt ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <svg x-show="$wire.bodyType === bt" class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-show="$wire.bodyType !== bt" class="w-3"></span>
                                <span x-text="bodyTypeLabels[bt]"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Form Data --}}
            <div x-show="$wire.bodyType === 'form-data' || $wire.bodyType === 'urlencoded'" x-cloak>
                <div class="space-y-2" wire:ignore>
                    <template x-for="(field, index) in $wire.formData" :key="index">
                        <div class="flex gap-2 items-center">
                            <div class="flex-1">
                                <input
                                    x-model="$wire.formData[index].key"
                                    placeholder="Field name"
                                    class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                            </div>
                            <div class="flex-1 relative">
                                <input
                                    x-model="$wire.formData[index].value"
                                    x-var-highlight="$wire.resolvedVariableNames"
                                    placeholder="Field value"
                                    class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                            </div>
                            <button
                                @click="$wire.removeFormDataField(index)"
                                type="button"
                                class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <button
                        @click="$wire.addFormDataField()"
                        type="button"
                        class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
                    >
                        + Add Field
                    </button>
                </div>
            </div>

            {{-- JSON Body --}}
            <div x-show="$wire.bodyType === 'json'" x-cloak wire:key="body-json">
                <x-json-editor wire:model="body" />
            </div>

            {{-- Raw Body --}}
            <div x-show="$wire.bodyType === 'raw'" x-cloak>
                <textarea
                    wire:model="body"
                    rows="10"
                    placeholder="Raw body content"
                    class="w-full py-1.5 px-2 text-sm font-mono rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors resize-y"
                ></textarea>
            </div>

            {{-- No Body --}}
            <p x-show="$wire.bodyType === 'none'" x-cloak class="text-xs text-gray-500 dark:text-gray-400 py-4">This request does not have a body</p>
        </div>
    </div>

    {{-- Auth Tab --}}
    <div x-show="activeTab === 'auth'" x-cloak>
        <div class="space-y-3"
            x-data="{
                authOpen: false,
                authTypeLabels: { none: 'No Auth', bearer: 'Bearer Token', basic: 'Basic Auth', 'api-key': 'API Key' },
                authTypeKeys: ['none', 'bearer', 'basic', 'api-key'],
            }"
        >
            <div @click.away="authOpen = false" class="relative w-48">
                <button
                    @click="authOpen = !authOpen"
                    type="button"
                    class="w-full h-[34px] flex items-center justify-between gap-2 px-2 rounded-md text-sm font-medium shadow-sm bg-white dark:bg-gray-800/80 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="truncate" x-text="authTypeLabels[$wire.authType] || 'No Auth'"></span>
                    </div>
                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 transition-transform" :class="authOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="authOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    style="display: none;"
                    class="absolute left-0 right-0 z-20 mt-1 origin-top rounded-lg bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div class="p-1">
                        <template x-for="at in authTypeKeys" :key="at">
                            <button
                                @click="$wire.set('authType', at); authOpen = false"
                                type="button"
                                class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer"
                                :class="$wire.authType === at ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                            >
                                <svg x-show="$wire.authType === at" class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-show="$wire.authType !== at" class="w-3"></span>
                                <span x-text="authTypeLabels[at]"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Bearer Token --}}
            <div x-show="$wire.authType === 'bearer'" x-cloak>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Token</label>
                <div class="relative">
                    <input
                        wire:model="authToken"
                        x-var-highlight="$wire.resolvedVariableNames"
                        placeholder="Enter bearer token"
                        class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                    >
                </div>
            </div>

            {{-- Basic Auth --}}
            <div x-show="$wire.authType === 'basic'" x-cloak class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Username</label>
                    <div class="relative">
                        <input
                            wire:model="authUsername"
                            x-var-highlight="$wire.resolvedVariableNames"
                            placeholder="Username"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Password</label>
                    <div class="relative">
                        <input
                            wire:model="authPassword"
                            x-var-highlight="$wire.resolvedVariableNames"
                            placeholder="Password"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                </div>
            </div>

            {{-- API Key --}}
            <div x-show="$wire.authType === 'api-key'" x-cloak class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Header Name</label>
                    <input
                        wire:model="apiKeyName"
                        placeholder="X-API-Key"
                        class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                    >
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Value</label>
                    <div class="relative">
                        <input
                            wire:model="apiKeyValue"
                            x-var-highlight="$wire.resolvedVariableNames"
                            placeholder="your-api-key"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                </div>
            </div>

            {{-- No Auth --}}
            <p x-show="$wire.authType === 'none'" x-cloak class="text-xs text-gray-500 dark:text-gray-400 py-4">This request does not use any authorization</p>
        </div>
    </div>

    {{-- Scripts Tab --}}
    <div x-show="activeTab === 'scripts'" x-cloak>
        <div class="space-y-6" x-data="{ sourceTypeLabels: { body: 'Response Body', header: 'Header', status: 'Status Code' } }">
            {{-- Pre-Request Scripts --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Pre-Request</h4>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Run another request before this one executes (e.g., fetch an auth token).</p>

                <div x-show="!$wire.requestId" x-cloak>
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">Save this request first to configure pre-request scripts.</p>
                </div>
                <div x-show="$wire.requestId" x-cloak class="space-y-2" wire:ignore>
                    <template x-for="(script, index) in $wire.preRequestScripts" :key="index">
                        <div class="flex gap-2 items-center">
                            <div class="flex-1">
                                <select
                                    x-model="$wire.preRequestScripts[index].request_id"
                                    class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                                    <option value="">Select a request...</option>
                                    <template x-for="req in $wire.collectionRequests" :key="req.id">
                                        <option :value="req.id" x-text="req.method.toUpperCase() + ' ' + req.name"></option>
                                    </template>
                                </select>
                            </div>
                            <button
                                @click="$wire.removePreRequestScript(index)"
                                type="button"
                                class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <button
                        @click="$wire.addPreRequestScript()"
                        type="button"
                        class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
                    >
                        + Add Pre-Request
                    </button>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            {{-- Post-Response Scripts --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Post-Response</h4>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Extract values from the response and save them as collection variables.</p>

                <div class="space-y-2" wire:ignore>
                    <template x-for="(script, index) in $wire.postResponseScripts" :key="index">
                        <div class="flex gap-2 items-center">
                            <div class="w-40 shrink-0">
                                <select
                                    x-model="$wire.postResponseScripts[index].source_type"
                                    class="w-full h-[34px] px-2 text-xs rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                                    <option value="body">Response Body</option>
                                    <option value="header">Header</option>
                                    <option value="status">Status Code</option>
                                </select>
                            </div>
                            <div class="flex-1" x-show="$wire.postResponseScripts[index]?.source_type !== 'status'">
                                <input
                                    x-model="$wire.postResponseScripts[index].source_path"
                                    :placeholder="$wire.postResponseScripts[index]?.source_type === 'header' ? 'X-Request-Id' : 'data.access_token'"
                                    class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                            </div>
                            <div class="w-36">
                                <div class="relative">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-purple-500 dark:text-purple-400 font-mono pointer-events-none">&#123;&#123;</span>
                                    <input
                                        x-model="$wire.postResponseScripts[index].target"
                                        placeholder="varName"
                                        class="w-full py-1.5 pl-6 pr-6 text-sm font-mono rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                    >
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-purple-500 dark:text-purple-400 font-mono pointer-events-none">&#125;&#125;</span>
                                </div>
                            </div>
                            <button
                                @click="$wire.removePostResponseScript(index)"
                                type="button"
                                class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <button
                        @click="$wire.addPostResponseScript()"
                        type="button"
                        class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
                    >
                        + Add Post-Response
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
