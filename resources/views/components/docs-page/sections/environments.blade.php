<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Environments & Variables</h2>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Environments let you define sets of variables that can be swapped in and out of your requests. Use them to switch between production, staging, and development APIs without editing each request individually.
</p>

{{-- What Are Environments --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">What Are Environments?</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    An environment is a named set of key-value variables. For example:
</p>
<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300 font-semibold">Environment</th>
                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300 font-semibold">base_url</th>
                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300 font-semibold">api_key</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Production</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 font-mono text-xs">https://api.example.com</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 font-mono text-xs">pk_live_xxx</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Staging</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 font-mono text-xs">https://staging.api.example.com</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 font-mono text-xs">pk_test_xxx</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Only one environment can be active at a time (per workspace). When active, its variables are available for substitution in all requests.
</p>

{{-- Managing Variables --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Managing Variables</h3>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-6">
    <li>Each variable has a <strong class="text-gray-700 dark:text-gray-300">key</strong>, <strong class="text-gray-700 dark:text-gray-300">value</strong>, and <strong class="text-gray-700 dark:text-gray-300">enabled toggle</strong>.</li>
    <li>Create or edit environments from the sidebar (switch to <strong class="text-gray-700 dark:text-gray-300">Environments</strong> view).</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">+ Add Variable</strong> or start typing in the empty row at the bottom.</li>
    <li>Toggle a variable off to temporarily disable it without deleting — disabled variables are not substituted in requests.</li>
    <li>Changes are auto-saved with a short debounce (500ms).</li>
</ul>

{{-- Variable Syntax --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Variable Syntax</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Use <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">@{{variable_name}}</code> anywhere in your requests. This works in:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-6 ml-4">
    <li>URL — <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">@{{base_url}}/users</code></li>
    <li>Headers — <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">Authorization: Bearer @{{api_key}}</code></li>
    <li>Query parameters (keys and values)</li>
    <li>Request body (JSON, form data, raw)</li>
    <li>Auth fields (bearer token, basic auth username/password, API key value)</li>
</ul>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Variables are resolved at the moment you send the request, using the currently active environment.
</p>

{{-- Active Environment --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Activating an Environment</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    There are several ways to activate an environment:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-6">
    <li><strong class="text-gray-700 dark:text-gray-300">Environment selector</strong> (top-right of the tab bar) — Click and pick an environment from the dropdown.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Environments panel</strong> — Click the radio/toggle button next to an environment in the sidebar.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Auto-activation</strong> — Set a default environment on a collection or folder (see below).</li>
</ul>

{{-- Collection Environments --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Collection Environments</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    You can associate specific environments with a collection. This serves two purposes:
</p>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-2 ml-4 list-decimal">
    <li><strong class="text-gray-700 dark:text-gray-300">Organization</strong> — The environment selector shows associated environments first, making it faster to pick the right one.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Auto-activation</strong> — Set a default environment that activates automatically when you open a request from that collection.</li>
</ol>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    To set up collection environments:
</p>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-6 ml-4 list-decimal">
    <li>Right-click a collection (or click the 3-dot menu).</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Set environments</strong>.</li>
    <li>Check the environments you want to associate.</li>
    <li>Click the star icon next to one to make it the <strong class="text-gray-700 dark:text-gray-300">default</strong> (it auto-activates).</li>
</ol>

{{-- Folder Environments --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Folder Environments</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Folders can have their own environment associations, independent of the collection. This is useful when a collection contains folders that target different APIs or services.
</p>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Example: A "Salesforce" collection with two folders</p>
    <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td class="py-1.5 text-gray-600 dark:text-gray-400 font-medium">Salesforce B2B/</td>
                <td class="py-1.5 text-gray-600 dark:text-gray-400">Salesforce B2B Production</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 dark:text-gray-400 font-medium">Salesforce B2C/</td>
                <td class="py-1.5 text-gray-600 dark:text-gray-400">Salesforce B2C Staging</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    When you open a request in the "Salesforce B2B" folder, the "Salesforce B2B Production" environment activates automatically. Switch to a request in "Salesforce B2C" and the environment switches too.
</p>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    To set up folder environments:
</p>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li>Right-click a folder (or click the 3-dot menu).</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Set environments</strong>.</li>
    <li>Check the environments you want to associate.</li>
    <li>Click the star icon to set a default.</li>
</ol>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    A small green dot appears next to folders that have environment associations.
</p>

{{-- Environment Inheritance --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Environment Inheritance</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Folder environments follow an inheritance model. When you open a request, Vaxtly looks for environment settings in this order:
</p>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li><strong class="text-gray-700 dark:text-gray-300">Request's folder</strong> — Does this folder have environment associations?</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Parent folder</strong> — Walk up the folder tree, checking each ancestor.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Collection</strong> — Fall back to the collection's environment settings.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">None</strong> — No auto-activation if nothing is configured.</li>
</ol>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    The first folder in the tree (starting from the request's folder and walking up) that has environment associations wins. This means you can set environments on a top-level folder and all its subfolders will inherit that setting, unless a subfolder overrides it with its own associations.
</p>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-6">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Example with nesting</p>
    <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono leading-relaxed">My Collection (default: Production)
  Auth/                          (no envs — inherits from collection)
    Login                        → activates "Production"
    Register                     → activates "Production"
  Payments/                      (default: Stripe Staging)
    Charges/                     (no envs — inherits from Payments/)
      Create Charge              → activates "Stripe Staging"
      List Charges               → activates "Stripe Staging"
    Refunds/                     (default: Stripe Production)
      Create Refund              → activates "Stripe Production"
  Users/                         (no envs — inherits from collection)
    Get User                     → activates "Production"</pre>
</div>

{{-- Auto-Activation --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Auto-Activation</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    When you open a request tab or switch between tabs, Vaxtly automatically activates the appropriate environment based on the folder/collection hierarchy described above. Auto-activation happens when:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4">
    <li>You open a new request tab</li>
    <li>You switch to an existing tab</li>
    <li>You click a request in the sidebar</li>
</ul>

{{-- Environment Lock --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Environment Lock</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    The lock button (padlock icon) next to the environment selector prevents auto-activation. When locked, switching between request tabs does <strong class="text-gray-700 dark:text-gray-300">not</strong> change the active environment. You can still manually change the environment using the selector. The lock button turns amber to indicate it's engaged. Use this when you want to test multiple requests against the same environment regardless of their folder/collection settings.
</p>

{{-- Context-Aware Selector --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Context-Aware Selector</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    The environment selector dropdown adapts based on the currently active request:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-6">
    <li><strong class="text-gray-700 dark:text-gray-300">With associated environments</strong> — The dropdown shows "No Environment", then a grouped section (labeled "Folder" or "Collection") with the associated environments (default starred), and a "Show all environments" toggle to reveal the rest.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">No environments associated</strong> — The dropdown shows "No Environment", an "Add environments" button to open the association modal, and all environments listed below.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">No request open</strong> — All environments are listed in a flat list.</li>
</ul>

{{-- Resolution Order --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Variable Resolution Order</h3>
<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
    <p class="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>When resolving <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs font-mono">@{{variable}}</code>, environment variables take priority over collection variables. If both define the same key, the environment value wins.</span>
    </p>
</div>
