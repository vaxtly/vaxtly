<?php

namespace App\Services;

use App\Models\Workspace;

class WorkspaceService
{
    private ?Workspace $activeWorkspace = null;

    /**
     * Get the currently active workspace (cached per request).
     */
    public function active(): Workspace
    {
        if ($this->activeWorkspace) {
            return $this->activeWorkspace;
        }

        $workspaceId = get_setting('active.workspace');

        if ($workspaceId) {
            $this->activeWorkspace = Workspace::find($workspaceId);
        }

        if (! $this->activeWorkspace) {
            $this->activeWorkspace = Workspace::ordered()->first();

            if ($this->activeWorkspace) {
                set_setting('active.workspace', $this->activeWorkspace->id);
            }
        }

        return $this->activeWorkspace;
    }

    /**
     * Get the active workspace ID.
     */
    public function activeId(): string
    {
        return $this->active()->id;
    }

    /**
     * Switch to a different workspace.
     */
    public function switchTo(string $workspaceId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $this->activeWorkspace = $workspace;
        set_setting('active.workspace', $workspace->id);
    }

    /**
     * Get a workspace-scoped setting from the active workspace.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->active()->getSetting($key, $default);
    }

    /**
     * Set a workspace-scoped setting on the active workspace.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $this->active()->setSetting($key, $value);
    }

    /**
     * Clear the cached active workspace (useful after switching).
     */
    public function clearCache(): void
    {
        $this->activeWorkspace = null;
    }
}
