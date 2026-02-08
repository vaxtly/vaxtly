<h2 class="text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 mb-4">Remote Sync (GitHub / GitLab)</h2>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Vaxtly can synchronize your collections to a GitHub or GitLab repository, enabling team collaboration and version-controlled backups. Each collection is stored as a directory of YAML files in your repository.
</p>

{{-- Overview --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">What You Can Do</h3>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1.5 mb-6">
    <li><strong class="text-gray-700 dark:text-gray-300">Back up</strong> collections to a remote repository.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Share</strong> collections across devices or with team members.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Track changes</strong> over time with Git history.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Restore</strong> collections from remote if local data is lost.</li>
</ul>

{{-- Setting Up GitHub --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 mt-6">Setting Up GitHub</h3>

<h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">1. Create a Repository</h4>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Create a new GitHub repository (public or private). You can leave it empty — Vaxtly will create the initial files on first push.
</p>

<h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">2. Create a Personal Access Token</h4>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Vaxtly needs a token to read and write files in your repository. You have two options:
</p>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Option A: Fine-Grained Token (Recommended)</p>
    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
        Fine-grained tokens give minimal, scoped access to specific repositories.
    </p>
    <ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 ml-4 list-decimal">
        <li>Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Developer settings &gt; Personal access tokens &gt; Fine-grained tokens</strong></li>
        <li>Click <strong class="text-gray-700 dark:text-gray-300">Generate new token</strong></li>
        <li>Set <strong class="text-gray-700 dark:text-gray-300">Repository access</strong> to "Only select repositories" and pick your Vaxtly repository</li>
        <li>Under <strong class="text-gray-700 dark:text-gray-300">Repository permissions</strong>, set <strong class="text-gray-700 dark:text-gray-300">Contents: Read and write</strong></li>
        <li>Click <strong class="text-gray-700 dark:text-gray-300">Generate token</strong> and copy it (starts with <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">github_pat_</code>)</li>
    </ol>
</div>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-6">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Option B: Classic Token</p>
    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
        Classic tokens grant broader access but are simpler to set up.
    </p>
    <ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 ml-4 list-decimal">
        <li>Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Developer settings &gt; Personal access tokens &gt; Tokens (classic)</strong></li>
        <li>Click <strong class="text-gray-700 dark:text-gray-300">Generate new token (classic)</strong></li>
        <li>Check the <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">repo</code> scope (Full control of private repositories)</li>
        <li>Click <strong class="text-gray-700 dark:text-gray-300">Generate token</strong> and copy it (starts with <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">ghp_</code>)</li>
    </ol>
</div>

<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mb-6">
    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 px-3 py-2 border-b border-gray-200 dark:border-gray-700">GitHub Token Permissions</p>
    <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">Contents (read/write)</td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Read and write YAML collection files</td>
            </tr>
        </tbody>
    </table>
    <p class="text-xs text-gray-500 dark:text-gray-400 px-3 py-2 border-t border-gray-200 dark:border-gray-700">
        The token does <strong>not</strong> need access to issues, pull requests, actions, or any other GitHub features.
    </p>
</div>

{{-- Setting Up GitLab --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Setting Up GitLab</h3>

<h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">1. Create a Project</h4>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
    Create a new GitLab project (blank is fine). Note the full project path — for example: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">my-group/my-project</code> or <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">my-group/sub-group/my-project</code>.
</p>

<h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">2. Create a Personal Access Token</h4>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li>Go to <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Access Tokens</strong> (in your user settings, not the project)</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Add new token</strong></li>
    <li>Check the <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">api</code> scope</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Create personal access token</strong> and copy it (starts with <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">glpat-</code>)</li>
</ol>

<div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-6">
    <p class="text-sm text-amber-800 dark:text-amber-300 flex items-start gap-2">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <span><strong>Why <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded text-xs font-mono">api</code> scope?</strong> GitLab's <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded text-xs font-mono">api</code> scope is needed because Vaxtly uses the Commits API to create atomic multi-file commits. The more limited <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded text-xs font-mono">read_repository</code> / <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded text-xs font-mono">write_repository</code> scopes don't cover this endpoint.</span>
    </p>
</div>

{{-- Configuring Vaxtly --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Configuring Vaxtly</h3>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li>Open <strong class="text-gray-700 dark:text-gray-300">Settings</strong> (gear icon or <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs font-mono">Cmd/Ctrl + ,</kbd>)</li>
    <li>Go to the <strong class="text-gray-700 dark:text-gray-300">Remote Sync</strong> tab</li>
    <li>Set <strong class="text-gray-700 dark:text-gray-300">Provider</strong> (GitHub or GitLab)</li>
    <li>Set <strong class="text-gray-700 dark:text-gray-300">Repository</strong>: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">owner/repo-name</code> for GitHub, <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">group/project-name</code> for GitLab</li>
    <li>Paste your <strong class="text-gray-700 dark:text-gray-300">Token</strong></li>
    <li>Set the <strong class="text-gray-700 dark:text-gray-300">Branch</strong> (default: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono text-pink-600 dark:text-pink-400">main</code>)</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Test Connection</strong> to verify everything works</li>
    <li>Click <strong class="text-gray-700 dark:text-gray-300">Save</strong></li>
</ol>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Configuration is per-workspace. Each workspace can have its own git provider, repository, and token.
</p>

{{-- Enabling Sync --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Enabling Sync on a Collection</h3>
<ol class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-1 mb-4 ml-4 list-decimal">
    <li>Right-click a collection (or click the 3-dot menu)</li>
    <li>Go to <strong class="text-gray-700 dark:text-gray-300">Sync &gt; Enable sync</strong></li>
    <li>The collection will immediately push to your repository</li>
</ol>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    Once enabled, a cloud icon next to the collection name indicates sync status. A yellow indicator appears when local changes haven't been pushed yet.
</p>

{{-- Push & Pull --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Pushing and Pulling</h3>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-4">
    <li><strong class="text-gray-700 dark:text-gray-300">Manual Push/Pull</strong> — Right-click a synced collection and use <strong class="text-gray-700 dark:text-gray-300">Sync &gt; Push</strong> or <strong class="text-gray-700 dark:text-gray-300">Sync &gt; Pull</strong>.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Granular Sync</strong> — When you save a request in a synced collection, Vaxtly automatically pushes just that request file (single-file push). This is faster and creates smaller commits.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Push All / Pull All</strong> — In <strong class="text-gray-700 dark:text-gray-300">Settings &gt; Remote Sync</strong>, use <strong class="text-gray-700 dark:text-gray-300">Pull Now</strong> or <strong class="text-gray-700 dark:text-gray-300">Push All</strong> for bulk operations.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Auto-sync on startup</strong> — Enable in Settings to automatically pull remote changes when the app starts.</li>
</ul>

{{-- How Sync Works --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">How Sync Works</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-2">
    Vaxtly converts collections into a directory of YAML files. The repository structure looks like:
</p>
<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 mb-4">
    <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono leading-relaxed">collections/
  {collection-id}/
    _collection.yaml      # Collection metadata, variables
    _manifest.yaml        # Ordering of root-level items
    {request-id}.yaml     # Root-level requests
    {folder-id}/
      _folder.yaml        # Folder metadata, env settings
      _manifest.yaml      # Ordering of folder items
      {request-id}.yaml   # Requests in this folder</pre>
</div>

<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
    All file changes in a push are committed atomically — either all files update or none do. This prevents partial updates that could leave the repository in an inconsistent state.
</p>

{{-- Conflict Resolution --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Conflict Resolution</h3>
<p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-3">
    A conflict occurs when both local and remote have changed since the last sync. When detected, Vaxtly shows a resolution dialog:
</p>
<ul class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed space-y-2 mb-6">
    <li><strong class="text-gray-700 dark:text-gray-300">Keep Local (Force Push)</strong> — Overwrites the remote version with your local data. Use when your local version is the correct one.</li>
    <li><strong class="text-gray-700 dark:text-gray-300">Keep Remote (Force Pull)</strong> — Overwrites your local data with the remote version. Use when the remote is more up to date.</li>
</ul>

{{-- Troubleshooting --}}
<h3 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">Troubleshooting</h3>
<div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
    <div class="p-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">"Connection failed" on test</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5 ml-3 list-disc">
            <li>Verify the repository path is correct (case-sensitive)</li>
            <li>Check the token hasn't expired</li>
            <li>For GitHub: ensure Contents read/write permission on the specific repository</li>
            <li>For GitLab: ensure the token has the <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">api</code> scope</li>
        </ul>
    </div>
    <div class="p-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Push succeeds but no files appear</p>
        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5 ml-3 list-disc">
            <li>Check you're looking at the correct branch</li>
            <li>Look for the <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">collections/</code> directory in the repository root</li>
        </ul>
    </div>
    <div class="p-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Token expired</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Generate a new token and update it in Settings &gt; Remote Sync.</p>
    </div>
</div>
