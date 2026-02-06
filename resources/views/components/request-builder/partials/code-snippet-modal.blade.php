{{-- Code Snippet Modal --}}
<x-beartropy-ui::modal
    wire:model="showCodeModal"
    styled
    max-width="2xl"
>
    <x-slot:title>
        Code Snippet
    </x-slot:title>

    <div class="space-y-4">
        <div class="w-48">
            <x-beartropy-ui::select
                wire:model.live="codeLanguage"
                :options="[
                    'curl' => 'cURL',
                    'python' => 'Python (requests)',
                    'php' => 'PHP (Laravel Http)',
                    'javascript' => 'JavaScript (fetch)',
                    'node' => 'Node.js (axios)',
                ]"
                :clearable="false"
                :searchable="false"
                sm
            />
        </div>

        <div
            x-data="{ copied: false }"
            class="relative"
        >
            <button
                x-on:click="
                    navigator.clipboard.writeText($refs.codeBlock.textContent);
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
                class="absolute top-2 right-2 p-1.5 rounded-md text-gray-400 hover:text-gray-200 hover:bg-gray-700 transition-colors cursor-pointer z-10"
                title="Copy to clipboard"
            >
                <template x-if="!copied">
                    <x-bt-icon name="clipboard-document" class="w-4 h-4" />
                </template>
                <template x-if="copied">
                    <x-bt-icon name="check" class="w-4 h-4 text-green-400" />
                </template>
            </button>

            <pre x-ref="codeBlock" class="bg-gray-950 text-gray-100 rounded-lg p-4 pr-12 text-sm font-mono overflow-x-auto beartropy-thin-scrollbar max-h-96"><code>{{ $this->getGeneratedCode() }}</code></pre>
        </div>
    </div>

    <x-slot:footer>
        <x-beartropy-ui::button tint wire:click="$set('showCodeModal', false)" sm>
            Close
        </x-beartropy-ui::button>
    </x-slot:footer>
</x-beartropy-ui::modal>
