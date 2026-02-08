<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">System Log</h2>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    The system log panel at the bottom of the screen shows activity history with two tabs.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Requests Tab</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Shows the last 50 HTTP requests you've sent — including status code, duration, URL, and method. Expand any entry to see the full response body and headers. This provides a quick history of your recent API calls without needing to re-open each request.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">System Tab</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Displays timestamped logs for:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5 mb-6">
    <li><strong class="text-gray-700 dark:text-gray-300">Git sync operations</strong> — Push, pull, enable/disable sync, conflicts detected and resolved.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Vault operations</strong> — Push, pull, enable/disable sync, secret discovery.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Errors</strong> — Detailed error messages when sync or vault operations fail.</li>
</ul>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
    Click the log bar to expand or collapse the panel. Use the <strong class="text-gray-700 dark:text-gray-300">Clear</strong> button to reset log entries. The system log is useful for troubleshooting sync and vault issues — check here first if something isn't working as expected.
</p>
