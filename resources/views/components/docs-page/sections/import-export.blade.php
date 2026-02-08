<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Import & Export</h2>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Vaxtly supports importing and exporting data for backup, migration, and interoperability with other tools.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Export</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Export your workspace data (collections, folders, requests, and environments) as a JSON backup file. Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Data</strong> and click <strong class="text-gray-700 dark:text-gray-300">Export</strong>.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Import</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Import data from a Vaxtly backup or from Postman. Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Data</strong> and click <strong class="text-gray-700 dark:text-gray-300">Import</strong>.
</p>

<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 mt-6">Postman Import</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Vaxtly can import from several Postman export formats:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
    <li><strong class="text-gray-700 dark:text-gray-300">Collection exports</strong> — Single collection JSON files exported from Postman.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Environment exports</strong> — Environment JSON files from Postman.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Workspace dumps</strong> — Complete workspace export files.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Archives</strong> — Postman archive files containing multiple collections and environments.</li>
</ul>

<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Imported data is added to the current workspace. Existing collections and environments with the same names are not overwritten.</span>
    </p>
</div>
