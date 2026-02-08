<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Making Requests</h2>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Creating a Request</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Create a new request from the sidebar <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">+</code> button on a collection or folder. You can also right-click a folder and choose <strong class="text-gray-700 dark:text-gray-300">Add request</strong>. The request opens in a new tab ready for editing.
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
    Enter the full URL or use variables: <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">@{{base_url}}/users</code>. Variables are resolved from the active environment and collection variables before sending. See the <strong class="text-gray-700 dark:text-gray-300">Environments</strong> section for details on variable syntax and resolution order.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Request Configuration</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Use the tabs below the URL bar to configure different parts of the request:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-6">
    <li><strong class="text-gray-700 dark:text-gray-300">Headers</strong> — Key-value pairs sent with the request. Each header has a toggle to enable/disable it without deleting. Common headers like <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Content-Type</code> and <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Accept</code> are suggested as you type.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Query Params</strong> — Key-value pairs auto-appended to the URL as <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">?key=value&key2=value2</code>. Toggle individual params on/off.</li>
    <li>
        <strong class="text-gray-700 dark:text-gray-300">Body</strong> — Choose a body type:
        <ul class="mt-1 ml-4 space-y-1">
            <li><strong class="text-gray-700 dark:text-gray-300">None</strong> — No request body.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">JSON</strong> — Write JSON directly in the editor. Use the auto-format button to prettify. Automatically sets <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Content-Type: application/json</code>.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">Form Data</strong> — Key-value pairs sent as <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">multipart/form-data</code>.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">URL Encoded</strong> — Key-value pairs sent as <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">application/x-www-form-urlencoded</code>.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">Raw</strong> — Free-form text body with any content type.</li>
        </ul>
    </li>
    <li>
        <strong class="text-gray-700 dark:text-gray-300">Auth</strong> — Choose an authentication method:
        <ul class="mt-1 ml-4 space-y-1">
            <li><strong class="text-gray-700 dark:text-gray-300">None</strong> — No authentication.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">Bearer Token</strong> — Sends <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Authorization: Bearer &lt;token&gt;</code>.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">Basic Auth</strong> — Sends username and password as base64-encoded <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Authorization: Basic</code> header.</li>
            <li><strong class="text-gray-700 dark:text-gray-300">API Key</strong> — Sends a custom key-value pair as a header or query parameter.</li>
        </ul>
    </li>
</ul>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    All fields (URL, headers, query params, body, auth values) support <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">@{{variable}}</code> syntax for environment variable substitution.
</p>

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
