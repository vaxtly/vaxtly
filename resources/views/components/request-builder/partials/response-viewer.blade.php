{{-- Response Section --}}
<div
    class="{{ $layout === 'columns' ? 'pt-3 px-3 flex flex-col min-h-0 overflow-hidden h-full' : 'flex-1 flex flex-col min-h-0 overflow-hidden' }}"
    @if($layout === 'columns') :style="'width: ' + (100 - leftWidth) + '%'" @endif
>
    <div wire:loading wire:target="sendRequest" class="w-full h-full">
        <div
            class="h-full flex flex-col items-center justify-center text-center"
            x-data="{
                start: 0,
                elapsed: '0.00',
                intervalId: null,
                init() {
                    this.intervalId = setInterval(() => {
                        if (this.$el.offsetParent !== null) {
                            if (this.start === 0) this.start = Date.now();
                            this.elapsed = ((Date.now() - this.start) / 1000).toFixed(2);
                        } else {
                            this.start = 0;
                            this.elapsed = '0.00';
                        }
                    }, 50);
                },
                destroy() {
                    clearInterval(this.intervalId);
                }
            }"
        >
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
            <p class="mt-4 text-gray-600 dark:text-gray-400 font-medium">
                Waiting for response <span class="font-mono text-blue-600 dark:text-blue-400" x-text="elapsed"></span>s
            </p>
        </div>
    </div>

    <div wire:loading.remove wire:target="sendRequest" class="h-full">
        <div x-show="$wire.response !== null || $wire.error" x-cloak class="{{ $layout === 'rows' ? 'border-t border-gray-200 dark:border-gray-700 pt-6 px-6 pb-6 h-full flex flex-col' : 'h-full flex flex-col' }}">

            {{-- Error --}}
            @if($error)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <h3 class="font-semibold text-red-900 dark:text-red-300 mb-1">Error</h3>
                    <p class="text-red-700 dark:text-red-400">{{ $error }}</p>
                </div>
            @endif

            {{-- Success Response --}}
            @if($response !== null)
                <div class="space-y-4 flex-1 flex flex-col min-h-0">
                    {{-- Response Tabs --}}
                    <div x-data="{ activeTab: 'body' }" class="flex-1 flex flex-col min-h-0">
                        {{-- Tab Buttons --}}
                        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-3">
                            <button
                                @click="activeTab = 'body'"
                                :class="activeTab === 'body' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
                            >
                                Body
                            </button>
                            <button
                                @click="activeTab = 'headers'"
                                :class="activeTab === 'headers' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
                            >
                                Headers
                            </button>
                            <button
                                @click="activeTab = 'cookies'"
                                :class="activeTab === 'cookies' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
                            >
                                Cookies
                            </button>
                            <button
                                @click="activeTab = 'render'"
                                :class="activeTab === 'render' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition-colors cursor-pointer"
                            >
                                Render
                            </button>

                            {{-- Status & Duration --}}
                            <div class="ml-auto flex items-center gap-4 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                    <span class="ml-1 font-bold {{ $this->getStatusColor($statusCode) }}">{{ $statusCode }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Time:</span>
                                    <span class="ml-1 font-semibold text-gray-900 dark:text-white">{{ $duration }}ms</span>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Content --}}
                        <div x-show="activeTab === 'body'" x-cloak class="flex-1 min-h-0 flex flex-col h-full pb-4">
                            <div class="h-full" wire:key="response-json">
                                <x-json-editor wire:model="response" readonly class="max-h-full" />
                            </div>
                        </div>

                        <div x-show="activeTab === 'headers'" x-cloak class="flex-1 min-h-0 flex flex-col h-full">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg h-full overflow-auto beartropy-thin-scrollbar">
                                <table class="w-full text-xs">
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($responseHeaders as $key => $values)
                                            <tr>
                                                <td class="px-3 py-2 font-medium text-sky-600 dark:text-sky-400 whitespace-nowrap align-top w-1/3">{{ $key }}</td>
                                                <td class="px-3 py-2 text-orange-700 dark:text-orange-400 break-all">
                                                    @if(is_array($values))
                                                        {{ implode(', ', $values) }}
                                                    @else
                                                        {{ $values }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div x-show="activeTab === 'cookies'" x-cloak class="flex-1 min-h-0 flex flex-col h-full">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg h-full overflow-auto beartropy-thin-scrollbar">
                                @php
                                    $cookies = [];
                                    foreach ($responseHeaders['Set-Cookie'] ?? [] as $cookie) {
                                        $parts = explode('=', explode(';', $cookie)[0], 2);
                                        if (count($parts) === 2) {
                                            $cookies[$parts[0]] = $parts[1];
                                        }
                                    }
                                @endphp
                                @if(count($cookies) > 0)
                                    <table class="w-full text-xs">
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($cookies as $name => $value)
                                                <tr>
                                                    <td class="px-3 py-2 font-medium text-sky-600 dark:text-sky-400 whitespace-nowrap align-top w-1/3">{{ $name }}</td>
                                                    <td class="px-3 py-2 text-orange-700 dark:text-orange-400 break-all">{{ $value }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400 p-3">No cookies in response</p>
                                @endif
                            </div>
                        </div>

                        <div x-show="activeTab === 'render'" x-cloak class="flex-1 min-h-0 flex flex-col h-full">
                            <div class="bg-white dark:bg-gray-800 rounded-lg h-full overflow-hidden border border-gray-200 dark:border-gray-700">
                                <iframe
                                    srcdoc="{{ $response }}"
                                    class="w-full h-full border-0"
                                    sandbox="allow-scripts"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @if($layout === 'columns')
        <div x-show="$wire.response === null && !$wire.error" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-500">
            <p class="text-sm">Response will appear here</p>
        </div>
        @endif
    </div>
</div>
