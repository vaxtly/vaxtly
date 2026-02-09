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
            @if(count(array_filter($queryParams, fn($p) => !empty($p['key']))) > 0)
                <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full">
                    {{ count(array_filter($queryParams, fn($p) => !empty($p['key']))) }}
                </span>
            @endif
        </button>
        <button
            @click="activeTab = 'headers'"
            :class="activeTab === 'headers' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Headers
            @if(count(array_filter($headers, fn($h) => !empty($h['key']))) > 0)
                <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full">
                    {{ count(array_filter($headers, fn($h) => !empty($h['key']))) }}
                </span>
            @endif
        </button>
        <button
            @click="activeTab = 'body'"
            :class="activeTab === 'body' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
        >
            Body
            @if(!empty($body))
                <span class="ml-1 w-2 h-2 inline-block bg-green-500 rounded-full"></span>
            @endif
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
            @if(count($preRequestScripts) > 0 || count($postResponseScripts) > 0)
                <span class="ml-1 w-2 h-2 inline-block bg-purple-500 rounded-full"></span>
            @endif
        </button>
    </div>

    {{-- Params Tab --}}
    <div x-show="activeTab === 'params'" x-cloak>
        <div class="space-y-2">
            @foreach($queryParams as $index => $param)
                <div wire:key="param-{{ $index }}" class="flex gap-2 items-center">
                    <div class="flex-1">
                        <input
                            wire:model="queryParams.{{ $index }}.key"
                            placeholder="Parameter name"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                    <div class="flex-1">
                        <input
                            wire:model="queryParams.{{ $index }}.value"
                            placeholder="Parameter value"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                    <button
                        wire:click="removeQueryParam({{ $index }})"
                        type="button"
                        class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            @endforeach
            <button
                wire:click="addQueryParam"
                type="button"
                class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
            >
                + Add Parameter
            </button>
        </div>
    </div>

    {{-- Headers Tab --}}
    <div x-show="activeTab === 'headers'" x-cloak>
        <div class="space-y-2">
            @foreach($headers as $index => $header)
                <div wire:key="header-{{ $index }}" class="flex gap-2 items-center">
                    <div class="flex-1">
                        <input
                            wire:model="headers.{{ $index }}.key"
                            placeholder="Header name"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                    <div class="flex-1">
                        <input
                            wire:model="headers.{{ $index }}.value"
                            placeholder="Header value"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                    <button
                        wire:click="removeHeader({{ $index }})"
                        type="button"
                        class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            @endforeach
            <button
                wire:click="addHeader"
                type="button"
                class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
            >
                + Add Header
            </button>
        </div>
    </div>

    {{-- Body Tab --}}
    <div x-show="activeTab === 'body'" x-cloak>
        <div class="space-y-3">
            @php
                $bodyTypes = [
                    'none' => 'None',
                    'json' => 'JSON',
                    'form-data' => 'Form Data',
                    'urlencoded' => 'x-www-form-urlencoded',
                    'raw' => 'Raw',
                ];
            @endphp
            <div x-data="{ bodyOpen: false }" @click.away="bodyOpen = false" class="relative w-48">
                <button
                    @click="bodyOpen = !bodyOpen"
                    type="button"
                    class="w-full h-[34px] flex items-center justify-between gap-2 px-2 rounded-md text-sm font-medium shadow-sm bg-white dark:bg-gray-800/80 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="truncate">{{ $bodyTypes[$bodyType] ?? 'None' }}</span>
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
                        @foreach($bodyTypes as $value => $label)
                            <button
                                wire:click="$set('bodyType', '{{ $value }}')"
                                @click="bodyOpen = false"
                                class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer {{ $bodyType === $value ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                            >
                                @if($bodyType === $value)
                                    <svg class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <span class="w-3"></span>
                                @endif
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @if($bodyType === 'form-data' || $bodyType === 'urlencoded')
                <div class="space-y-2" wire:key="body-form-data">
                    @foreach($formData as $index => $field)
                        <div wire:key="form-data-{{ $index }}" class="flex gap-2 items-center">
                            <div class="flex-1">
                                <input
                                    wire:model="formData.{{ $index }}.key"
                                    placeholder="Field name"
                                    class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                            </div>
                            <div class="flex-1">
                                <input
                                    wire:model="formData.{{ $index }}.value"
                                    placeholder="Field value"
                                    class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                >
                            </div>
                            <button
                                wire:click="removeFormDataField({{ $index }})"
                                type="button"
                                class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                    <button
                        wire:click="addFormDataField"
                        type="button"
                        class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
                    >
                        + Add Field
                    </button>
                </div>
            @elseif($bodyType === 'json')
                <div wire:key="body-json-{{ $requestId ?? 'new' }}">
                    <x-json-editor wire:model="body" />
                </div>
            @elseif($bodyType === 'raw')
                <div wire:key="body-raw">
                    <textarea
                        wire:model="body"
                        rows="10"
                        placeholder="Raw body content"
                        class="w-full py-1.5 px-2 text-sm font-mono rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors resize-y"
                    ></textarea>
                </div>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400 py-4" wire:key="body-none">This request does not have a body</p>
            @endif
        </div>
    </div>

    {{-- Auth Tab --}}
    <div x-show="activeTab === 'auth'" x-cloak>
        <div class="space-y-3">
            @php
                $authTypes = [
                    'none' => 'No Auth',
                    'bearer' => 'Bearer Token',
                    'basic' => 'Basic Auth',
                    'api-key' => 'API Key',
                ];
            @endphp
            <div x-data="{ authOpen: false }" @click.away="authOpen = false" class="relative w-48">
                <button
                    @click="authOpen = !authOpen"
                    type="button"
                    class="w-full h-[34px] flex items-center justify-between gap-2 px-2 rounded-md text-sm font-medium shadow-sm bg-white dark:bg-gray-800/80 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="truncate">{{ $authTypes[$authType] ?? 'No Auth' }}</span>
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
                        @foreach($authTypes as $value => $label)
                            <button
                                wire:click="$set('authType', '{{ $value }}')"
                                @click="authOpen = false"
                                class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer {{ $authType === $value ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                            >
                                @if($authType === $value)
                                    <svg class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <span class="w-3"></span>
                                @endif
                                <span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @if($authType === 'bearer')
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Token</label>
                    <input
                        wire:model="authToken"
                        placeholder="Enter bearer token"
                        class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                    >
                </div>
            @elseif($authType === 'basic')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Username</label>
                        <input
                            wire:model="authUsername"
                            placeholder="Username"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Password</label>
                        <input
                            wire:model="authPassword"
                            type="password"
                            placeholder="Password"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                </div>
            @elseif($authType === 'api-key')
                <div class="grid grid-cols-2 gap-4">
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
                        <input
                            wire:model="apiKeyValue"
                            placeholder="your-api-key"
                            class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                        >
                    </div>
                </div>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400 py-4">This request does not use any authorization</p>
            @endif
        </div>
    </div>

    {{-- Scripts Tab --}}
    <div x-show="activeTab === 'scripts'" x-cloak>
        <div class="space-y-6">
            {{-- Pre-Request Scripts --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Pre-Request</h4>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Run another request before this one executes (e.g., fetch an auth token).</p>

                @if(!$requestId)
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">Save this request first to configure pre-request scripts.</p>
                @else
                    <div class="space-y-2">
                        @php
                            $requestOptions = [];
                            foreach ($collectionRequests as $r) {
                                $requestOptions[$r['id']] = strtoupper($r['method']) . ' ' . $r['name'];
                            }
                        @endphp
                        @foreach($preRequestScripts as $index => $script)
                            <div wire:key="pre-script-{{ $index }}" class="flex gap-2 items-center">
                                <div class="flex-1">
                                    <x-beartropy-ui::select
                                        wire:model="preRequestScripts.{{ $index }}.request_id"
                                        :options="$requestOptions"
                                        placeholder="Select a request..."
                                        :clearable="false"
                                        :searchable="true"
                                        sm
                                    />
                                </div>
                                <button
                                    wire:click="removePreRequestScript({{ $index }})"
                                    type="button"
                                    class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                        <button
                            wire:click="addPreRequestScript"
                            type="button"
                            class="text-sm text-beartropy-600 dark:text-beartropy-400 hover:text-beartropy-700 dark:hover:text-beartropy-300 cursor-pointer transition-colors"
                        >
                            + Add Pre-Request
                        </button>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            {{-- Post-Response Scripts --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Post-Response</h4>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Extract values from the response and save them as collection variables.</p>

                <div class="space-y-2">
                    @foreach($postResponseScripts as $index => $script)
                        @php
                            $sourceTypes = ['body' => 'Response Body', 'header' => 'Header', 'status' => 'Status Code'];
                            $currentSourceType = $postResponseScripts[$index]['source_type'] ?? 'body';
                        @endphp
                        <div wire:key="post-script-{{ $index }}" class="flex gap-2 items-center">
                            <div x-data="{ stOpen: false }" @click.away="stOpen = false" class="relative w-40 shrink-0">
                                <button
                                    @click="stOpen = !stOpen"
                                    type="button"
                                    class="w-full h-[34px] flex items-center justify-between gap-1.5 px-2 rounded-md text-sm font-medium shadow-sm bg-white dark:bg-gray-800/80 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                >
                                    <span class="truncate text-xs">{{ $sourceTypes[$currentSourceType] ?? 'Response Body' }}</span>
                                    <svg class="w-3 h-3 shrink-0 text-gray-400 transition-transform" :class="stOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div
                                    x-show="stOpen"
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
                                        @foreach($sourceTypes as $value => $label)
                                            <button
                                                wire:click="$set('postResponseScripts.{{ $index }}.source_type', '{{ $value }}')"
                                                @click="stOpen = false"
                                                class="w-full flex items-center gap-2 px-2 py-1.5 text-xs text-left rounded-md transition-colors cursor-pointer {{ $currentSourceType === $value ? 'bg-beartropy-50 dark:bg-beartropy-900/30 text-beartropy-700 dark:text-beartropy-300 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                                            >
                                                @if($currentSourceType === $value)
                                                    <svg class="w-3 h-3 shrink-0 text-beartropy-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                @else
                                                    <span class="w-3"></span>
                                                @endif
                                                <span>{{ $label }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @php $sourceType = $postResponseScripts[$index]['source_type'] ?? 'body'; @endphp
                            @if($sourceType !== 'status')
                                <div class="flex-1">
                                    <input
                                        wire:model="postResponseScripts.{{ $index }}.source_path"
                                        placeholder="{{ $sourceType === 'header' ? 'X-Request-Id' : 'data.access_token' }}"
                                        class="w-full py-1.5 px-2 text-sm rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                    >
                                </div>
                            @endif
                            <div class="w-36">
                                <div class="relative">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-purple-500 dark:text-purple-400 font-mono pointer-events-none">&#123;&#123;</span>
                                    <input
                                        wire:model="postResponseScripts.{{ $index }}.target"
                                        placeholder="varName"
                                        class="w-full py-1.5 pl-6 pr-6 text-sm font-mono rounded-md border shadow-sm bg-white dark:bg-gray-800/80 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-beartropy-500/30 focus:border-beartropy-500 transition-colors"
                                    >
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-purple-500 dark:text-purple-400 font-mono pointer-events-none">&#125;&#125;</span>
                                </div>
                            </div>
                            <button
                                wire:click="removePostResponseScript({{ $index }})"
                                type="button"
                                class="flex items-center justify-center h-[34px] px-1.5 rounded-md text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                    <button
                        wire:click="addPostResponseScript"
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
