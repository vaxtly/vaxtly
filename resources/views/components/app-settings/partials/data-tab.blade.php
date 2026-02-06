{{-- Data Tab --}}
<div class="space-y-4 max-h-[60vh] overflow-y-auto beartropy-thin-scrollbar pr-2">

    {{-- Export Section --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">Export Data</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Download your workspace data as a JSON file</p>
    </div>

    {{-- Export Status Message --}}
    @if(!empty($exportStatus))
        <div class="p-3 rounded-lg text-sm {{ $exportStatus['type'] === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}">
            <div class="flex items-center gap-2">
                @if($exportStatus['type'] === 'success')
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                @endif
                <p>{{ $exportStatus['message'] }}</p>
            </div>
        </div>
    @endif

    {{-- Export Type Selector --}}
    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'collections' => 'Collections', 'environments' => 'Environments', 'config' => 'Config'] as $value => $label)
            <label class="relative cursor-pointer">
                <input
                    type="radio"
                    wire:model="exportType"
                    value="{{ $value }}"
                    class="peer sr-only"
                />
                <div class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                    peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700
                    dark:peer-checked:bg-blue-900/30 dark:peer-checked:border-blue-400 dark:peer-checked:text-blue-300
                    border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400
                    hover:border-gray-400 dark:hover:border-gray-500">
                    {{ $label }}
                </div>
            </label>
        @endforeach
    </div>

    {{-- Export Button --}}
    <x-beartropy-ui::button
        wire:click="exportData"
        wire:loading.attr="disabled"
        wire:target="exportData"
        primary
        sm
    >
        <span wire:loading.remove wire:target="exportData">Export</span>
        <span wire:loading wire:target="exportData" class="flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Exporting...
        </span>
    </x-beartropy-ui::button>

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Import Section Header --}}
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-white">Import Data</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Import from a Vaxtly export or Postman file</p>
    </div>

    {{-- Import Status Message --}}
    @if(!empty($importStatus))
        <div class="p-3 rounded-lg text-sm {{ $importStatus['type'] === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300' : ($importStatus['type'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300') }}">
            <div class="flex items-start gap-2">
                @if($importStatus['type'] === 'success')
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                @elseif($importStatus['type'] === 'warning')
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                @endif
                <div>
                    <p>{{ $importStatus['message'] }}</p>
                    @if(!empty($importStatus['errors']))
                        <ul class="mt-1 text-xs list-disc list-inside opacity-75">
                            @foreach($importStatus['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- File Upload --}}
    <div
        x-data="{ isDragging: false }"
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="isDragging = false"
        class="relative"
    >
        <label
            class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
            :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 bg-gray-50 dark:bg-gray-800'"
        >
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                @if($importFile)
                    <svg class="w-8 h-8 mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ $importFile->getClientOriginalName() }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($importFile->getSize() / 1024, 1) }} KB</p>
                @else
                    <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-medium">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">.json or .zip (max 10MB)</p>
                @endif
            </div>
            <input
                type="file"
                wire:model="importFile"
                accept=".json,.zip,application/json,application/zip"
                class="hidden"
            />
        </label>

        {{-- Loading overlay --}}
        <div wire:loading wire:target="importFile" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center rounded-lg">
            <svg class="w-6 h-6 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    @error('importFile')
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    {{-- Import Actions --}}
    @if($importFile)
        <div class="flex gap-2">
            <x-beartropy-ui::button
                wire:click="importData"
                wire:loading.attr="disabled"
                wire:target="importData"
                primary
                sm
            >
                <span wire:loading.remove wire:target="importData">Import</span>
                <span wire:loading wire:target="importData" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Importing...
                </span>
            </x-beartropy-ui::button>
            <x-beartropy-ui::button
                wire:click="resetImport"
                tint
                sm
            >
                Cancel
            </x-beartropy-ui::button>
        </div>
    @endif

    {{-- Divider --}}
    <div class="border-t border-gray-200 dark:border-gray-700"></div>

    {{-- Help Text --}}
    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Supported formats:</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <li class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 9h-2v6h2v-6zm0-4h-2v2h2V7z"/>
                </svg>
                Vaxtly export (.json)
            </li>
            <li class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 9h-2v6h2v-6zm0-4h-2v2h2V7z"/>
                </svg>
                Postman collection/environment (.json)
            </li>
            <li class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-purple-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 9h-2v6h2v-6zm0-4h-2v2h2V7z"/>
                </svg>
                Postman workspace data dump (.json)
            </li>
            <li class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6 10H6v-2h8v2zm4-4H6v-2h12v2z"/>
                </svg>
                Postman "Export Data" archive (.zip)
            </li>
        </ul>
    </div>
</div>
