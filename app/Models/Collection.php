<?php

namespace App\Models;

use App\Casts\EncryptedArray;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'name',
        'description',
        'variables',
        'order',
        'workspace_id',
        'remote_sha',
        'file_shas',
        'remote_synced_at',
        'is_dirty',
        'sync_enabled',
        'environment_ids',
        'default_environment_id',
    ];

    protected function casts(): array
    {
        return [
            'variables' => EncryptedArray::class,
            'environment_ids' => 'array',
            'file_shas' => 'array',
            'is_dirty' => 'boolean',
            'sync_enabled' => 'boolean',
            'remote_synced_at' => 'datetime',
        ];
    }

    /**
     * Get enabled variables as key-value pairs.
     *
     * @return array<string, string>
     */
    public function getEnabledVariables(): array
    {
        $result = [];
        foreach ($this->variables ?? [] as $variable) {
            if (($variable['enabled'] ?? true) && ! empty($variable['key'])) {
                $result[$variable['key']] = $variable['value'] ?? '';
            }
        }

        return $result;
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class)->orderBy('order');
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class)->orderBy('order');
    }

    public function rootFolders(): HasMany
    {
        return $this->hasMany(Folder::class)->whereNull('parent_id')->orderBy('order');
    }

    public function rootRequests(): HasMany
    {
        return $this->hasMany(Request::class)->whereNull('folder_id')->orderBy('order');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scopeForWorkspace($query, string $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeSyncEnabled($query)
    {
        return $query->where('sync_enabled', true);
    }

    public function scopeDirty($query)
    {
        return $query->where('is_dirty', true);
    }

    public function markDirty(?Request $changedRequest = null, bool $sanitize = false): void
    {
        if (! $this->sync_enabled) {
            return;
        }

        $this->syncToRemote($changedRequest, $sanitize);
    }

    /**
     * Push this collection to the remote immediately.
     * If a specific request is provided and the collection has been pushed before,
     * attempts granular single-file push. Otherwise does a full push.
     * Falls back to marking dirty if the push fails.
     */
    public function syncToRemote(?Request $changedRequest = null, bool $sanitize = false): void
    {
        try {
            $syncService = new \App\Services\RemoteSyncService;
            if ($syncService->isConfigured()) {
                if ($changedRequest && $this->remote_sha) {
                    // Granular single-file push
                    $syncService->pushSingleRequest($this, $changedRequest, $sanitize);
                } else {
                    $syncService->pushCollection($this, $sanitize);
                }

                return;
            }
        } catch (\Exception) {
            // Fall through to mark dirty
        }

        if (! $this->is_dirty) {
            $this->update(['is_dirty' => true]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getEnvironmentIds(): array
    {
        return $this->environment_ids ?? [];
    }

    public function hasEnvironment(string $environmentId): bool
    {
        return in_array($environmentId, $this->getEnvironmentIds());
    }

    public function addEnvironment(string $environmentId): void
    {
        if ($this->hasEnvironment($environmentId)) {
            return;
        }

        $ids = $this->getEnvironmentIds();
        $ids[] = $environmentId;
        $this->update(['environment_ids' => $ids]);
    }

    public function removeEnvironment(string $environmentId): void
    {
        $ids = array_values(array_filter(
            $this->getEnvironmentIds(),
            fn (string $id) => $id !== $environmentId,
        ));

        $updates = ['environment_ids' => $ids ?: null];

        if ($this->default_environment_id === $environmentId) {
            $updates['default_environment_id'] = null;
        }

        $this->update($updates);
    }

    public function setDefaultEnvironment(?string $environmentId): void
    {
        if ($environmentId && ! $this->hasEnvironment($environmentId)) {
            $this->addEnvironment($environmentId);
            $this->refresh();
        }

        $this->update(['default_environment_id' => $environmentId]);
    }

    public function cleanupDeletedEnvironment(string $environmentId): void
    {
        if (! $this->hasEnvironment($environmentId)) {
            return;
        }

        $this->removeEnvironment($environmentId);
    }
}
