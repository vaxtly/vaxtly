<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Vault Integration (HashiCorp)</h2>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Vaxtly integrates with HashiCorp Vault to securely store environment variables (API keys, tokens, passwords) outside your local database. When Vault sync is enabled for an environment, variables are stored in Vault and fetched on demand — they never persist in your local database.
</p>

{{-- How It Works --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">How It Works</h3>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-6 ml-4 list-decimal">
    <li>You configure a Vault connection in Settings</li>
    <li>You enable Vault sync on specific environments</li>
    <li>Vaxtly pushes current variables to Vault and clears them from the local database</li>
    <li>When you use the environment, Vaxtly fetches variables from Vault on demand</li>
    <li>Variables are cached for 60 seconds to avoid excessive API calls</li>
    <li>When you edit variables, Vaxtly writes them back to Vault on save</li>
</ol>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    The local database only stores a <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">vault_synced = true</code> flag and the Vault path — the actual secret values live exclusively in Vault.
</p>

{{-- Prerequisites --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Prerequisites</h3>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-6">
    <li>A running HashiCorp Vault instance (self-hosted or HCP Vault)</li>
    <li>A KV v2 secrets engine enabled (this is the default <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">secret/</code> engine in most installations)</li>
    <li>An authentication method: Token or AppRole</li>
</ul>

{{-- Authentication Methods --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Authentication Methods</h3>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Token Authentication</p>
    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
        The simplest method. Use a Vault token directly (typically starts with <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">hvs.</code>).
    </p>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        Generate with: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">vault token create -policy=vaxtly-policy</code>
    </p>
</div>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-6">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">AppRole Authentication</p>
    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
        Better for automated/production use. Uses a Role ID + Secret ID pair that exchanges for a short-lived token.
    </p>
    <ol class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5 ml-4 list-decimal">
        <li>Enable AppRole: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">vault auth enable approle</code></li>
        <li>Create role: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">vault write auth/approle/role/vaxtly token_policies="vaxtly-policy"</code></li>
        <li>Get Role ID: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">vault read auth/approle/role/vaxtly/role-id</code></li>
        <li>Get Secret ID: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">vault write -f auth/approle/role/vaxtly/secret-id</code></li>
    </ol>
</div>

{{-- Vault Policies --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Required Vault Policies</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Your token or AppRole needs these capabilities (replace <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">secret</code> with your engine path if different):
</p>
<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-6">
    <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono leading-relaxed"># Read and write secrets
path "secret/data/*" {
  capabilities = ["create", "read", "update", "delete"]
}

# Delete secret metadata (environment deletion)
path "secret/metadata/*" {
  capabilities = ["delete", "list"]
}

# List all secrets (Pull from Vault discovery)
path "secret/metadata" {
  capabilities = ["list"]
}

# AppRole login (only if using AppRole auth)
path "auth/approle/login" {
  capabilities = ["create", "update"]
}</pre>
</div>

{{-- Configuring Vaxtly --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Configuring Vaxtly</h3>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li>Open <strong class="text-gray-700 dark:text-gray-300">Settings</strong> (gear icon or <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs font-mono">Cmd/Ctrl + ,</kbd>)</li>
    <li>Go to the <strong class="text-gray-700 dark:text-gray-300">Vault</strong> tab</li>
    <li>Set <strong class="text-gray-700 dark:text-gray-300">Provider</strong>: HashiCorp Vault</li>
    <li>Set <strong class="text-gray-700 dark:text-gray-300">Vault URL</strong>: your Vault server address (e.g., <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">https://vault.example.com</code>)</li>
    <li>Choose <strong class="text-gray-700 dark:text-gray-300">Auth Method</strong>: Token or AppRole</li>
    <li>Fill in credentials (token, or role ID + secret ID)</li>
    <li>Set <strong class="text-gray-700 dark:text-gray-300">Engine Full Path</strong>: the KV v2 mount path (default: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">secret</code>)</li>
    <li>Optionally set <strong class="text-gray-700 dark:text-gray-300">Namespace</strong> (Enterprise only, used for AppRole auth)</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Test Connection</strong> to verify</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Save</strong></li>
</ol>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 px-3 py-2 border-b border-gray-200 dark:border-gray-700">Common Engine Paths</p>
    <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">Default installation</td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 font-mono text-xs">secret</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">Custom mount</td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 font-mono text-xs">kv</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">Namespaced/scoped</td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 font-mono text-xs">secret/team-api</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Enabling Vault Sync --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Enabling Vault Sync on an Environment</h3>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li>Select an environment in the Environments panel</li>
    <li>Find the <strong class="text-gray-700 dark:text-gray-300">Vault Integration</strong> section in the editor</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Enable Sync</strong></li>
</ol>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    When you enable sync: variables are pushed to Vault, cleared from local database, and the environment gets a <strong class="text-gray-700 dark:text-gray-300">Vault</strong> badge. Only enabled variables with non-empty keys are pushed.
</p>

{{-- Vault Paths --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Vault Paths</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Each environment maps to a Vault path derived from its name (URL-safe slug):
</p>
<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
    <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Production</td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 font-mono text-xs">secret/data/production</td>
            </tr>
            <tr>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">My Dev Environment</td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 font-mono text-xs">secret/data/my-dev-environment</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Pull from Vault --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Pull from Vault</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    In <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Vault</strong>, click <strong class="text-gray-700 dark:text-gray-300">Pull from Vault</strong> to discover secrets already stored in your engine. Vaxtly lists all secrets at the engine root and creates local environments for each one not already tracked. This is useful when setting up on a new device, or when a team member added secrets you don't have locally.
</p>

{{-- Operations --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Operations</h3>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
    <li><strong class="text-gray-700 dark:text-gray-300">Push to Vault</strong> — Upload variables. Happens automatically on save for synced environments.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Pull from Vault</strong> — Download variables from Vault (per-environment).</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Refresh from Vault</strong> — Bypass the 60-second cache and force a fresh fetch.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Rename</strong> — Vaxtly automatically migrates secrets when you rename a Vault-synced environment (reads old path, writes new path, deletes old).</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Delete</strong> — Attempts to delete the secrets from Vault when you delete the environment.</li>
</ul>

{{-- Disabling Sync --}}
<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-6">
    <p class="text-sm text-amber-800 dark:text-amber-300 flex items-start gap-2">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <span><strong>Disabling Vault sync</strong> does <strong>not</strong> copy variables back to your local database. The environment will have no variables after disabling. Note down or export variables before disabling if you need to keep them. The secrets remain in Vault.</span>
    </p>
</div>

{{-- Troubleshooting --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Troubleshooting</h3>
<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
    <div class="p-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">"Connection failed"</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5 ml-3 list-disc">
            <li>Verify the Vault URL is correct and reachable</li>
            <li>Check the token hasn't expired</li>
            <li>For AppRole: verify both Role ID and Secret ID are correct</li>
        </ul>
    </div>
    <div class="p-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Variables not loading</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5 ml-3 list-disc">
            <li>Click Refresh from Vault to bypass cache</li>
            <li>Verify the environment's Vault path matches an existing secret</li>
            <li>Check the token has <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">read</code> capability on the path</li>
        </ul>
    </div>
    <div class="p-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">"Permission denied" errors</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Your token lacks the necessary Vault policy. See the "Required Vault Policies" section above.</p>
    </div>
</div>
