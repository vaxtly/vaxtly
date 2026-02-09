{{-- Welcome / Onboarding Modal --}}
<x-beartropy-ui::modal
    wire:model="showWelcomeModal"
    styled
    max-width="3xl"
>
    <div
        x-data="{ step: 1, totalSteps: 5 }"
        class="-mb-2 -mt-2 overflow-hidden"
    >
        {{-- Step 1: Welcome --}}
        <div x-show="step === 1" x-cloak class="px-8 py-6 text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mb-5 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Welcome to Vaxtly</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-md mx-auto mb-4">
                A self-hosted API testing tool built for developers. Create, organize, and execute HTTP requests with environments, scripting, remote sync, and more.
            </p>
            <ul class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm mx-auto text-left space-y-2">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Organize requests into collections and folders</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Variable substitution, scripting, and auth</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Code snippet generation in multiple languages</span>
                </li>
            </ul>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-5">
                Here's a quick look at some key features.
            </p>
        </div>

        {{-- Step 2: Default Environments --}}
        <div x-show="step === 2" x-cloak class="px-8 py-6 text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-5 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Default Environments</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-md mx-auto mb-4">
                Assign a default environment to any collection or folder. When you open a request, the correct environment activates automatically — no manual switching needed.
            </p>
            <ul class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm mx-auto text-left space-y-2 mb-4">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Star an environment in a collection to make it the default</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Folders inherit from parent, or set their own override</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Lock the environment selector to prevent auto-switching</span>
                </li>
            </ul>
        </div>

        {{-- Step 3: Git Sync --}}
        <div x-show="step === 3" x-cloak class="px-8 py-6 text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center mb-5 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Git Sync</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-md mx-auto mb-4">
                Push and pull collections as YAML files to GitHub or GitLab. Keep your API definitions version-controlled and share them across your team.
            </p>
            <ul class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm mx-auto text-left space-y-2 mb-4">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Each collection becomes a structured YAML directory</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Automatic conflict detection with keep-local / keep-remote resolution</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Enable auto-sync to pull changes on app start</span>
                </li>
            </ul>
            <div class="inline-flex items-center gap-2 text-xs text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 rounded-lg px-3 py-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Configure in Settings &gt; Remote Sync
            </div>
        </div>

        {{-- Step 4: Vault Sync --}}
        <div x-show="step === 4" x-cloak class="px-8 py-6 text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center mb-5 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Vault Sync</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-md mx-auto mb-4">
                Store environment variables securely in HashiCorp Vault. Push and pull secrets without exposing them in your repository or local files.
            </p>
            <ul class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm mx-auto text-left space-y-2 mb-4">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-violet-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Push/pull individual environments or discover all secrets at once</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-violet-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Supports Token and AppRole authentication</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-violet-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Enterprise Vault namespace support included</span>
                </li>
            </ul>
            <div class="inline-flex items-center gap-2 text-xs text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 rounded-lg px-3 py-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Configure in Settings &gt; Vault
            </div>
        </div>

        {{-- Step 5: Workspaces --}}
        <div x-show="step === 5" x-cloak class="px-8 py-6 text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center mb-5 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Workspaces</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-md mx-auto mb-4">
                Organize projects into isolated workspaces. Each workspace has its own collections, environments, and sync configuration — completely independent.
            </p>
            <ul class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-sm mx-auto text-left space-y-2 mb-4">
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-pink-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Separate projects like "Backend API" and "Payment Service"</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-pink-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Each workspace has independent git and vault configs</span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 text-pink-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Switch from the workspace selector in the sidebar header</span>
                </li>
            </ul>
        </div>

        {{-- Navigation --}}
        <div class="px-8 pb-6 flex items-center justify-between">
            {{-- Step dots --}}
            <div class="flex items-center gap-1.5">
                <template x-for="i in totalSteps" :key="i">
                    <button
                        @click="step = i"
                        type="button"
                        class="w-2 h-2 rounded-full transition-all cursor-pointer"
                        :class="step === i
                            ? 'bg-blue-500 dark:bg-blue-400 w-4'
                            : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500'"
                    ></button>
                </template>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-2">
                <button
                    x-show="step > 1"
                    @click="step--"
                    type="button"
                    class="px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors cursor-pointer"
                >
                    Back
                </button>

                <button
                    x-show="step < totalSteps"
                    @click="step++"
                    type="button"
                    class="px-4 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors cursor-pointer"
                >
                    Next
                </button>

                <button
                    x-show="step === totalSteps"
                    @click="$wire.set('showWelcomeModal', false)"
                    type="button"
                    class="px-4 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors cursor-pointer"
                >
                    Get Started
                </button>
            </div>
        </div>
    </div>
</x-beartropy-ui::modal>
