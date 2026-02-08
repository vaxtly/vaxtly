<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Scripting</h2>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Vaxtly supports pre-request and post-response scripts to automate common workflows like fetching tokens, chaining requests, and extracting response data into variables.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Pre-Request Scripts</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-3">
    Run another request automatically before the main one — perfect for chaining auth flows. For example, fetch a token from an auth endpoint and use it in subsequent API calls.
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5 mb-6">
    <li>Select any request from the same collection as the pre-request.</li>
    <li>Chains up to <strong class="text-gray-700 dark:text-gray-300">3 levels deep</strong> (request A calls B, B calls C).</li>
    <li>Circular dependency protection prevents infinite loops.</li>
    <li>The pre-request runs silently — its response is available for post-response extraction but not shown in the main response panel.</li>
</ul>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Post-Response Scripts</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-3">
    Extract values from responses and save them to collection variables automatically. Each script rule defines a <strong class="text-gray-700 dark:text-gray-300">source</strong> (where to extract from) and a <strong class="text-gray-700 dark:text-gray-300">target variable</strong> (where to save it).
</p>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-6">
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
        <span>Pre-request fetches a token &rarr; post-response saves it to <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs font-mono">@{{token}}</code> &rarr; your API request uses <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs font-mono">@{{token}}</code> in the Authorization header. The token is refreshed automatically every time you send the request.</span>
    </p>
</div>
