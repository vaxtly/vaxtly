<div class="border-t min-h-[37px] border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
    {{-- Toggle Bar --}}
    <div
        wire:click="$set('isExpanded', true)"
        class="w-full flex items-center justify-between px-4 py-2 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer overflow-hidden"
    >
        <div 
            wire:click.stop="$toggle('isExpanded')"
            class="flex items-center gap-2 shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
        >
            <svg
                class="w-3.5 h-3.5 transition-transform duration-200 {{ $isExpanded ? 'rotate-90' : '' }}"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="font-medium">Logs</span>
            
            {{-- Tab Pills --}}
            <div class="flex items-center gap-1 ml-2">
                <span
                    wire:click.stop="selectTab('requests')"
                    class="px-2 py-0.5 rounded text-[10px] font-medium transition-colors cursor-pointer {{ $activeTab === 'requests' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                >
                    Requests
                    @if(count($this->requestHistory) > 0)
                        <span class="ml-1 text-[9px] opacity-70">{{ count($this->requestHistory) }}</span>
                    @endif
                </span>
                <span
                    wire:click.stop="selectTab('system')"
                    class="px-2 py-0.5 rounded text-[10px] font-medium transition-colors cursor-pointer {{ $activeTab === 'system' ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                >
                    System
                    @if(count($this->systemLogs) > 0)
                        <span class="ml-1 text-[9px] opacity-70">{{ count($this->systemLogs) }}</span>
                    @endif
                </span>
            </div>
        </div>
        
        <div class="flex items-center gap-2 min-w-0">
            {{-- Latest Entry Preview --}}
            <div class="flex items-center gap-3 text-[11px] min-w-0 overflow-hidden">
            @if($activeTab === 'requests' && count($this->requestHistory) > 0)
                @php $latest = $this->requestHistory[0]; @endphp
                <span class="font-mono font-semibold shrink-0 {{ $this->getMethodColor($latest['method']) }}">{{ $latest['method'] }}</span>
                <span class="text-gray-400 dark:text-gray-500 truncate font-mono min-w-0">{{ $latest['url'] }}</span>
                <span class="font-semibold shrink-0 {{ $this->getStatusColor($latest['status_code']) }}">{{ $latest['status_code'] }}</span>
                <span class="text-gray-400 dark:text-gray-500 shrink-0">{{ $latest['executed_at'] }}</span>
            @elseif($activeTab === 'system' && count($this->systemLogs) > 0)
                @php $latest = $this->systemLogs[0]; @endphp
                <span class="font-semibold uppercase shrink-0 {{ $latest['success'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $latest['category'] }}:{{ $latest['type'] }}
                </span>
                <span class="text-gray-400 dark:text-gray-500 truncate min-w-0">{{ $latest['message'] }}</span>
                <span class="text-gray-400 dark:text-gray-500 shrink-0">{{ \Carbon\Carbon::parse($latest['timestamp'])->diffForHumans() }}</span>
            @else
                <span class="text-gray-400 dark:text-gray-500 italic">No logs yet</span>
            @endif
            </div>

            <button
                wire:click.stop="close"
                class="shrink-0 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer"
                title="Close"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Expanded Content --}}
    @if($isExpanded)
        <div class="border-t border-gray-100 dark:border-gray-700/50 max-h-64 overflow-auto beartropy-thin-scrollbar">
            {{-- Request History Tab --}}
            @if($activeTab === 'requests')
                @if(count($this->requestHistory) > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($this->requestHistory as $entry)
                            <div wire:key="history-{{ $entry['id'] }}">
                                <div 
                                    wire:click="toggleHistoryEntry('{{ $entry['id'] }}')"
                                    class="flex items-center gap-3 px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800/30 cursor-pointer transition-colors {{ $expandedHistoryId === $entry['id'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                                >
                                    <svg
                                        class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-200 {{ $expandedHistoryId === $entry['id'] ? 'rotate-90' : '' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="font-mono font-semibold w-14 shrink-0 {{ $this->getMethodColor($entry['method']) }}">{{ $entry['method'] }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 truncate w-28 shrink-0" title="{{ $entry['collection_name'] }} / {{ $entry['request_name'] }}">
                                        {{ $entry['request_name'] }}
                                    </span>
                                    <span class="text-gray-600 dark:text-gray-300 font-mono truncate flex-1 min-w-0">{{ $entry['url'] }}</span>
                                    <span class="font-semibold shrink-0 {{ $this->getStatusColor($entry['status_code']) }}">{{ $entry['status_code'] }}</span>
                                    <span class="text-gray-400 dark:text-gray-500 shrink-0 w-14 text-right">{{ $entry['duration_ms'] }}ms</span>
                                    <span class="text-gray-400 dark:text-gray-500 shrink-0 w-20 text-right" title="{{ $entry['executed_at_full'] }}">{{ $entry['executed_at'] }}</span>
                                    
                                    <div class="flex items-center gap-1">
                                        <button
                                            wire:click.stop="loadHistoryEntry('{{ $entry['id'] }}')"
                                            class="shrink-0 text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 p-1 cursor-pointer"
                                            title="Load this response"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                        <button
                                            wire:click.stop="deleteHistoryEntry('{{ $entry['id'] }}')"
                                            class="shrink-0 text-gray-400 hover:text-red-500 dark:hover:text-red-400 p-1 cursor-pointer"
                                            title="Delete entry"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Expanded Detail --}}
                                @if($expandedHistoryId === $entry['id'] && !empty($expandedHistoryData))
                                    <div x-data="{ detailTab: 'response' }" class="px-4 pb-3 pl-10">
                                        {{-- Detail Tabs --}}
                                        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-2">
                                            <button
                                                @click="detailTab = 'response'"
                                                :class="detailTab === 'response' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                                                class="px-3 py-1 text-[11px] font-medium border-b -mb-px cursor-pointer bg-transparent"
                                            >Response Body</button>
                                            <button
                                                @click="detailTab = 'headers'"
                                                :class="detailTab === 'headers' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                                                class="px-3 py-1 text-[11px] font-medium border-b -mb-px cursor-pointer bg-transparent"
                                            >Headers</button>
                                            <button
                                                @click="detailTab = 'cookies'"
                                                :class="detailTab === 'cookies' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                                                class="px-3 py-1 text-[11px] font-medium border-b -mb-px cursor-pointer bg-transparent"
                                            >Cookies</button>
                                        </div>

                                        <div class="max-h-48 overflow-auto beartropy-thin-scrollbar bg-white dark:bg-gray-900">
                                            {{-- Response Body --}}
                                            <div x-show="detailTab === 'response'" x-cloak>
                                                @if(!empty($expandedHistoryData['response_body']))
                                                    <div class="bg-gray-50 dark:bg-gray-800 rounded p-2">
                                                        <pre class="text-[11px] font-mono leading-tight whitespace-pre-wrap break-all" x-data x-html="$el.parentElement.querySelector('template').innerHTML"></pre>
                                                        <template>{!! $expandedHistoryData['response_body_colorized'] !!}</template>
                                                    </div>
                                                @else
                                                    <p class="text-[11px] text-gray-400 py-2">No response body</p>
                                                @endif
                                            </div>

                                            {{-- Headers --}}
                                            <div x-show="detailTab === 'headers'" x-cloak>
                                                @if(!empty($expandedHistoryData['response_headers']))
                                                    <table class="w-full text-[11px]">
                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                                            @foreach($expandedHistoryData['response_headers'] as $key => $values)
                                                                <tr>
                                                                    <td class="px-2 py-1 font-medium text-purple-600 dark:text-purple-400 whitespace-nowrap align-top w-1/3">{{ $key }}</td>
                                                                    <td class="px-2 py-1 text-green-600 dark:text-green-400 break-all">
                                                                        {{ is_array($values) ? implode(', ', $values) : $values }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <p class="text-[11px] text-gray-400 py-2">No headers</p>
                                                @endif
                                            </div>

                                            {{-- Cookies --}}
                                            <div x-show="detailTab === 'cookies'" x-cloak>
                                                @php
                                                    $historyCookies = [];
                                                    $setCookies = $expandedHistoryData['response_headers']['Set-Cookie'] ?? [];
                                                    if (is_array($setCookies)) {
                                                        foreach ($setCookies as $cookie) {
                                                            $parts = explode('=', explode(';', $cookie)[0], 2);
                                                            if (count($parts) === 2) {
                                                                $historyCookies[$parts[0]] = $parts[1];
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                @if(count($historyCookies) > 0)
                                                    <table class="w-full text-[11px]">
                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                                            @foreach($historyCookies as $cookieName => $cookieValue)
                                                                <tr>
                                                                    <td class="px-2 py-1 font-medium text-purple-600 dark:text-purple-400 whitespace-nowrap w-1/3">{{ $cookieName }}</td>
                                                                    <td class="px-2 py-1 text-green-600 dark:text-green-400 break-all">{{ $cookieValue }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <p class="text-[11px] text-gray-400 py-2">No cookies</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500 p-4 text-center">No request history yet</p>
                @endif
            @endif

            {{-- System Tab --}}
            @if($activeTab === 'system')
                @if(count($this->systemLogs) > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach($this->systemLogs as $log)
                            <div class="flex items-center gap-3 px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                {{-- Status Icon --}}
                                @if($log['success'])
                                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                @endif

                                {{-- Category Badge --}}
                                <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-medium uppercase
                                    {{ $log['category'] === 'git' ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' }}">
                                    {{ $log['category'] }}
                                </span>

                                {{-- Operation Type --}}
                                <span class="shrink-0 font-semibold text-gray-700 dark:text-gray-300 w-12">{{ $log['type'] }}</span>

                                {{-- Target --}}
                                @if($log['target'])
                                    <span class="shrink-0 text-gray-500 dark:text-gray-400 truncate max-w-32">{{ $log['target'] }}</span>
                                @endif

                                {{-- Message --}}
                                <span class="flex-1 text-gray-600 dark:text-gray-300 truncate">{{ $log['message'] }}</span>

                                {{-- Timestamp --}}
                                <span class="text-gray-400 dark:text-gray-500 shrink-0 w-20 text-right">
                                    {{ \Carbon\Carbon::parse($log['timestamp'])->diffForHumans() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Clear Logs Button --}}
                    <div class="px-4 py-2 flex justify-end border-t border-gray-100 dark:border-gray-700/50">
                        <button
                            wire:click="clearSystemLogs"
                            class="text-[11px] text-red-500 hover:text-red-700 dark:hover:text-red-400 cursor-pointer"
                        >
                            Clear System Logs
                        </button>
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500 p-4 text-center">No system logs yet. Git and Vault operations will appear here.</p>
                @endif
            @endif
        </div>
    @endif
</div>
