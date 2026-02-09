@props(['readonly' => false, 'label' => null])

<div 
    x-data="{ 
        content: @entangle($attributes->wire('model')),
        editor: null,
        isValid: true,
        isReadOnly: {{ $readonly ? 'true' : 'false' }},
        copied: false,
        
        init() {
            // Formateo inicial SOLO para el modo respuesta
            let initialContent = this.content || '';
            if (this.isReadOnly) {
                initialContent = this.autoFormat(initialContent);
            }

            const isDark = document.documentElement.classList.contains('dark');
            if (this.isReadOnly) {
                this.editor = window.setupJsonViewer(
                    this.$refs.editorContainer,
                    initialContent,
                    null,
                    isDark
                );
            } else {
                this.editor = window.setupJsonEditor(
                    this.$refs.editorContainer,
                    initialContent,
                    (value) => {
                        this.content = value;
                        this.validate();
                    },
                    isDark
                );
            }

            this.observer = new MutationObserver(() => {
                this.editor.updateTheme(document.documentElement.classList.contains('dark'));
            });
            this.observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            this.$watch('content', value => {
                // Formateo automático al vuelo SOLO para readonly
                let displayValue = this.isReadOnly ? this.autoFormat(value) : value;
                
                const current = this.editor.state.doc.toString();
                if (displayValue !== current) {
                    this.editor.dispatch({
                        changes: { from: 0, to: current.length, insert: displayValue || '' }
                    });
                }
                this.validate();
            });
        },

        autoFormat(value) {
            if (!value) return '';
            try {
                const obj = typeof value === 'string' ? JSON.parse(value) : value;
                return JSON.stringify(obj, null, 4);
            } catch (e) {
                return value; // Si no es JSON, mostrar tal cual
            }
        },

        validate() {
            try {
                if (!this.content || this.content.trim() === '') { this.isValid = true; return; }
                JSON.parse(this.content);
                this.isValid = true;
            } catch (e) { this.isValid = false; }
        },

        formatJson() {
            try {
                const obj = JSON.parse(this.content);
                this.content = JSON.stringify(obj, null, 4);
                this.isValid = true;
            } catch (e) {
                alert('JSON inválido');
            }
        },

        async copyToClipboard() {
            if (!this.content) return;
            await navigator.clipboard.writeText(this.content);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        },

        destroy() {
            this.observer?.disconnect();
            this.editor?.destroy();
            this.observer = null;
            this.editor = null;
        }
    }"
    {{ $attributes->merge(['class' => 'flex flex-col space-y-2']) }}
>
    <div class="flex justify-between items-center px-1">
        <label class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ $label ?? ($readonly ? 'Response Body' : 'Request Body') }}
        </label>
        
        <div class="flex items-center space-x-2">

            <x-bt-button xs @click="copyToClipboard()" outline>
                <span x-show="!copied" class="flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg> COPY
                </span>
                <span x-show="copied" x-cloak class="text-green-500 flex items-center italic">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> COPIED!
                </span>
            </x-bt-button>

            @if(!$readonly)
                <x-bt-button xs label="{ } Format" @click="formatJson()" outline indigo />
            @endif
        </div>
    </div>

    <div 
        x-ref="editorContainer" 
        wire:ignore 
        :class="[
            !isValid ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-300 dark:border-gray-700',
            isReadOnly ? 'bg-gray-50 dark:bg-black/20' : 'bg-white dark:bg-[#0f0f0f]'
        ]"
        class="w-full border rounded-lg overflow-auto beartropy-thin-scrollbar transition-all duration-200 shadow-inner min-h-0"
    ></div>
</div>