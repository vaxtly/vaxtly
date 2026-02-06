{{-- Help / User Guide Modal --}}
<x-beartropy-ui::modal
    wire:model="showHelpModal"
    styled
    max-width="5xl"
>
    <x-slot:title>
        Vaxtly User Guide
    </x-slot:title>

    <div
        x-data="{ section: 'getting-started' }"
        class="flex gap-0 -mx-6 -mb-2 -mt-2"
    >
        {{-- Left Navigation --}}
        <nav class="w-48 shrink-0 border-r border-gray-200 dark:border-gray-700 py-2 overflow-y-auto beartropy-thin-scrollbar max-h-[70vh]">
            @php
                $navItems = [
                    'getting-started' => ['label' => 'Getting Started', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                    'requests' => ['label' => 'Making Requests', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
                    'collections' => ['label' => 'Collections', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
                    'environments' => ['label' => 'Environments', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                    'scripting' => ['label' => 'Scripting', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>'],
                    'code-snippets' => ['label' => 'Code Snippets', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>'],
                    'remote-sync' => ['label' => 'Remote Sync', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>'],
                    'vault' => ['label' => 'Vault', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'],
                    'workspaces' => ['label' => 'Workspaces', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>'],
                    'system-log' => ['label' => 'System Log', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
                ];
            @endphp

            @foreach($navItems as $key => $item)
                <button
                    @click="section = '{{ $key }}'"
                    type="button"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-left transition-colors cursor-pointer"
                    :class="section === '{{ $key }}'
                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                    <span>{{ $item['label'] }}</span>
                </button>
            @endforeach
        </nav>

        {{-- Right Content --}}
        <div class="flex-1 min-w-0 max-h-[70vh] overflow-y-auto beartropy-thin-scrollbar px-6 py-4">

            {{-- Getting Started --}}
            <div x-show="section === 'getting-started'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Getting Started</h2>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Vaxtly is a self-hosted API testing tool built for developers. It lets you create, organize, and execute HTTP requests — with support for environments, variables, scripting, remote sync, and more.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Quick Tour</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Sidebar</strong> — Browse and manage your collections, folders, requests, and environments.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Tab Bar</strong> — Open multiple requests in tabs. Switch the active environment from the dropdown.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Request Builder</strong> — Configure method, URL, headers, query params, body, and auth.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Response Viewer</strong> — See status code, timing, response body (formatted), and headers.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Layout & Appearance</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                    <li><strong class="text-gray-700 dark:text-gray-300">Layout mode</strong> — Toggle between side-by-side (columns) and stacked (rows) using the layout button in the bottom-left of the request builder.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Dark mode</strong> — Toggle light/dark theme from the icon at the bottom of the sidebar.</li>
                </ul>
            </div>

            {{-- Making Requests --}}
            <div x-show="section === 'requests'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Making Requests</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Creating a Request</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Create a new request from the sidebar <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">+</code> button on a collection, or right-click a folder and choose "New Request". The request opens in a new tab.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">HTTP Methods</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Supported methods: <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">GET</code>,
                    <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">POST</code>,
                    <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">PUT</code>,
                    <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">PATCH</code>,
                    <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">DELETE</code>,
                    <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">HEAD</code>,
                    <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">OPTIONS</code>.
                    Select from the method dropdown next to the URL bar.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">URL & Variables</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Enter the full URL or use variables: <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">@{{baseUrl}}/users</code>. Variables are resolved from the active environment and collection variables before sending.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Request Configuration</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Headers</strong> — Key-value pairs sent with the request. Toggle individual headers on/off.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Query Params</strong> — Key-value pairs auto-appended to the URL as <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">?key=value</code>.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Body</strong> — Choose a type: None, JSON (with auto-format button), Form Data, x-www-form-urlencoded, or Raw.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Auth</strong> — None, Bearer Token, Basic Auth, or API Key. Auth values support variable substitution too.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Response</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    After sending, view the response status code, duration, formatted body (JSON is syntax-highlighted), and response headers. Use the tabs in the response panel to switch between body and headers.
                </p>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Open multiple requests in tabs and switch between them. Each tab preserves its own state independently.</span>
                    </p>
                </div>
            </div>

            {{-- Collections & Folders --}}
            <div x-show="section === 'collections'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Collections & Folders</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">What Are Collections?</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Collections are logical groups of related API requests — think of them like projects. Each collection can hold requests, folders, and its own set of variables.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Managing Collections</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Create</strong> — Click the <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">+</code> button at the top of the sidebar.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Rename / Delete</strong> — Right-click a collection to access the context menu.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Duplicate</strong> — Right-click a request and choose "Duplicate" to clone it.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Folders</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Organize requests into folders with unlimited nesting depth. Create folders from the collection or folder context menu. Drag requests between folders to reorganize.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Collection Variables</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Each collection has its own key-value variables. These are scoped to the collection and take priority over environment variables with the same name. Manage them from the collection settings.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Search & Sort</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2">
                    <li><strong class="text-gray-700 dark:text-gray-300">Search</strong> — Use the search bar at the top of the sidebar to filter across collection names, folder names, request names, and URLs.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Sort</strong> — Sort collections and requests by name (A-Z, Z-A), newest, or oldest.</li>
                </ul>
            </div>

            {{-- Environments & Variables --}}
            <div x-show="section === 'environments'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Environments & Variables</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">What Are Environments?</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Environments are named sets of variables — like "Development", "Staging", or "Production". Switch between them to change all variable values at once.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Managing Variables</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li>Each variable is a key-value pair with an enable/disable toggle.</li>
                    <li>Create or edit environments from the sidebar (switch to Environments view).</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Variable Syntax</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Use <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">@{{variableName}}</code> anywhere — in URLs, headers, query params, body, and auth fields. Variables are resolved before the request is sent.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Active Environment</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Only one environment can be active at a time per workspace. The active environment shows a green dot indicator. Select it from the dropdown in the tab bar.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Environment Lock</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    When a collection has a default environment, it auto-activates every time you open or switch to a request in that collection. Click the lock icon next to the environment selector to prevent this — your manually chosen environment will stay active as you move between requests. Click the lock again to re-enable auto-switching.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Resolution Order</h3>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Collection variables take priority over environment variables. If both define <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs font-mono">baseUrl</code>, the collection value wins.</span>
                    </p>
                </div>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Collection-Environment Association</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Associate environments with a collection using the star icon. The starred (default) environment auto-activates when you open a request from that collection. Associated environments appear in a separate group in the environment selector dropdown.
                </p>
            </div>

            {{-- Scripting --}}
            <div x-show="section === 'scripting'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Scripting</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Pre-Request Scripts</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-3">
                    Run another request automatically before the main one — perfect for chaining auth flows. For example, fetch a token from an auth endpoint and use it in subsequent API calls.
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5 mb-4">
                    <li>Select any request from the same collection as the pre-request.</li>
                    <li>Chains up to <strong class="text-gray-700 dark:text-gray-300">3 levels deep</strong> (request A calls B, B calls C).</li>
                    <li>Circular dependency protection prevents infinite loops.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Post-Response Scripts</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-3">
                    Extract values from responses and save them to collection variables automatically.
                </p>

                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Source Types</p>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                        <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">body.path.to.value</code> — Extract from JSON response body using dot notation.</li>
                        <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">body.data[0].id</code> — Supports array brackets for indexed access.</li>
                        <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">header.Header-Name</code> — Extract a response header value.</li>
                        <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">status</code> — The HTTP status code.</li>
                    </ul>
                </div>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Typical Workflow</h3>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Pre-request fetches a token &rarr; post-response saves it to <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs font-mono">@{{token}}</code> &rarr; your API request uses <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs font-mono">@{{token}}</code> in the Authorization header.</span>
                    </p>
                </div>
            </div>

            {{-- Code Snippets --}}
            <div x-show="section === 'code-snippets'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Code Snippets</h2>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Generate your request as ready-to-use code in multiple languages. The generated code includes all configured headers, auth, body, and query parameters — with variables resolved to their current values.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Supported Languages</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5 mb-4">
                    <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">cURL</code> — Command-line HTTP requests.</li>
                    <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">JavaScript</code> — Using the <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">fetch</code> API.</li>
                    <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Node.js</code> — Using axios.</li>
                    <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Python</code> — Using the requests library.</li>
                    <li><code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">PHP</code> — Using Laravel's Http client.</li>
                </ul>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Access code snippets from the <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">&lt;/&gt;</code> button in the request builder. Use the copy button to copy the snippet to your clipboard.
                </p>
            </div>

            {{-- Remote Sync --}}
            <div x-show="section === 'remote-sync'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Remote Sync (GitHub / GitLab)</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Configuration</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Remote Sync</strong> to configure your git provider. Set the provider (GitHub or GitLab), repository path, personal access token, and branch. Configuration is per-workspace.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Push & Pull</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Push</strong> — Uploads your collections as YAML files to your repository. Each collection becomes a directory with structured YAML files.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Pull</strong> — Downloads collections from your repository. New collections found remotely are created locally.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Auto-sync</strong> — Enable to automatically pull changes when the app starts.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Conflict Resolution</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-3">
                    When both local and remote have changed since the last sync, you'll see a conflict dialog:
                </p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Keep Local</strong> — Force-push your local version, overwriting the remote.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Keep Remote</strong> — Discard local changes and use the remote version.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Sync Status</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Collections show cloud icons indicating sync status. A dirty indicator appears when local changes haven't been pushed yet. You can enable or disable sync per collection from the collection context menu.
                </p>
            </div>

            {{-- Vault --}}
            <div x-show="section === 'vault'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Vault Integration (HashiCorp)</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Configuration</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Vault</strong> to configure your HashiCorp Vault connection. Set the Vault URL, auth method (Token or AppRole), and mount path. Configuration is per-workspace.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Vault-Synced Environments</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    When vault sync is enabled for an environment, its variables are stored in and loaded from Vault rather than the local database. Toggle vault sync in the environment editor.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Operations</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Push</strong> — Upload an environment's variables to Vault.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Pull</strong> — Download variables from Vault into the environment.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Pull All</strong> — Discover all secrets at the mount path and create local environments for each.</li>
                </ul>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Vault responses are cached for 60 seconds to improve performance. Enterprise Vault namespace is supported.</span>
                    </p>
                </div>
            </div>

            {{-- Workspaces --}}
            <div x-show="section === 'workspaces'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Workspaces</h2>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">What Are Workspaces?</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Workspaces are isolated project contexts. Each workspace has its own collections, environments, git sync configuration, and vault configuration — completely independent of other workspaces.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Managing Workspaces</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
                    <li><strong class="text-gray-700 dark:text-gray-300">Workspace Switcher</strong> — Located in the sidebar header. Click to switch between workspaces or create a new one.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Create</strong> — Click "New Workspace" in the switcher dropdown.</li>
                    <li><strong class="text-gray-700 dark:text-gray-300">Rename / Delete</strong> — Access from the workspace context menu.</li>
                </ul>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Switching Workspaces</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Switching workspaces closes all open tabs and reloads collections and environments for the selected workspace.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Use Cases</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5">
                    <li>Separate different projects (e.g., "Backend API", "Payment Service").</li>
                    <li>Isolate team contexts with different git repos and vault configs.</li>
                    <li>Manage different API versions independently.</li>
                </ul>
            </div>

            {{-- System Log --}}
            <div x-show="section === 'system-log'" x-cloak>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">System Log</h2>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    The system log panel at the bottom of the screen shows activity history with two tabs.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Requests Tab</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Shows the last 50 HTTP requests you've sent — including status code, duration, URL, and method. Expand any entry to see the full response body and headers.
                </p>

                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">System Tab</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                    Displays timestamped logs for git sync operations (push, pull, conflicts) and vault operations (push, pull, discovery). Useful for troubleshooting sync issues.
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    Use the <strong class="text-gray-700 dark:text-gray-300">Clear</strong> button to reset log entries. Click the log bar to expand or collapse the panel.
                </p>
            </div>

        </div>
    </div>
</x-beartropy-ui::modal>
