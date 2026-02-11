<div
    class="flex-1 min-h-0 h-full flex flex-col overflow-hidden"
    x-on:send-request.window="sendHttpRequest()"
    x-data="{
        isSending: false,
        abortController: null,
        async sendHttpRequest() {
            if (this.isSending) return;
            this.isSending = true;

            let config;
            try {
                config = await $wire.prepareRequest();
            } catch (e) {
                this.isSending = false;
                return;
            }

            if (!config) {
                this.isSending = false;
                return;
            }

            this.abortController = new AbortController();
            const startTime = Date.now();

            try {
                const response = await fetch('{{ route('internal.proxy-request') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'text/plain',
                    },
                    body: JSON.stringify(config),
                    signal: this.abortController.signal,
                });

                const text = await response.text();
                const duration = Math.round(Date.now() - startTime);
                const data = JSON.parse(text.trim());

                if (data.error) {
                    await $wire.setRequestError(data.error);
                } else {
                    await $wire.processResponse(data.status, data.body, data.headers, duration);
                }
            } catch (e) {
                if (e.name !== 'AbortError') {
                    await $wire.setRequestError(e.message);
                }
            } finally {
                this.isSending = false;
                this.abortController = null;
            }
        },
        cancelHttpRequest() {
            if (this.abortController) {
                this.abortController.abort();
                this.abortController = null;
            }
            this.isSending = false;
        }
    }"
>

    @include('components.request-builder.partials.url-bar')

    {{-- Main Content --}}
    <div
        class="flex-1 overflow-hidden min-h-0 {{ $layout === 'columns' ? 'flex flex-row' : 'flex flex-col' }}"
        x-data="{
            isDragging: false,
            leftWidth: 45,
            startDrag(e) {
                this.isDragging = true;
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
            },
            onDrag(e) {
                if (!this.isDragging) return;
                const rect = this.$el.getBoundingClientRect();
                const x = e.clientX - rect.left;
                let newWidth = (x / rect.width) * 100;
                this.leftWidth = Math.max(20, Math.min(80, newWidth));
            },
            stopDrag() {
                this.isDragging = false;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            }
        }"
        @if($layout === 'columns')
        @mousemove.window="onDrag($event)"
        @mouseup.window="stopDrag()"
        @endif
    >
        {{-- Request Section --}}
        <div
            class="pt-3 px-3 space-y-4 overflow-auto beartropy-thin-scrollbar {{ $layout === 'columns' ? 'h-full' : 'max-h-[50vh] shrink-0' }}"
            @if($layout === 'columns') :style="'width: ' + leftWidth + '%'" @endif
        >
            @include('components.request-builder.partials.request-tabs')
        </div>

        {{-- Draggable Divider --}}
        @if($layout === 'columns')
        <div
            class="w-1 bg-gray-200 dark:bg-gray-700 hover:bg-blue-500 dark:hover:bg-blue-500 cursor-col-resize transition-colors shrink-0"
            :class="isDragging ? 'bg-blue-500 dark:bg-blue-500' : ''"
            @mousedown.prevent="startDrag($event)"
        ></div>
        @endif

        @include('components.request-builder.partials.response-viewer')
    </div>

    @include('components.request-builder.partials.code-snippet-modal')

    @include('components.request-builder.partials.sync-sensitive-modal')
</div>
