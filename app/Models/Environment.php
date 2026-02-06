<?php

namespace App\Models;

use App\Services\VaultSyncService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Environment extends Model
{
    /** @use HasFactory<\Database\Factories\EnvironmentFactory> */
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::deleting(function (Environment $environment) {
            Collection::whereNotNull('environment_ids')
                ->where('workspace_id', $environment->workspace_id)
                ->get()
                ->each(fn (Collection $collection) => $collection->cleanupDeletedEnvironment($environment->id));

            if ($environment->vault_synced) {
                try {
                    $vaultService = new VaultSyncService;
                    if ($vaultService->isConfigured()) {
                        $vaultService->deleteSecrets($environment);
                    }
                } catch (\Exception) {
                    // Continue with local delete even if Vault delete fails
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'variables',
        'is_active',
        'order',
        'workspace_id',
        'vault_synced',
        'vault_path',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'vault_synced' => 'boolean',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVaultSynced($query)
    {
        return $query->where('vault_synced', true);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scopeForWorkspace($query, string $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Get the Vault path for this environment.
     * Returns just the environment slug - the mount path is handled by the provider.
     */
    public function getVaultPath(): string
    {
        if ($this->vault_path) {
            return $this->vault_path;
        }

        return Str::slug($this->name);
    }

    /**
     * Get effective variables, fetching from Vault if vault_synced.
     *
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    public function getEffectiveVariables(): array
    {
        if (! $this->vault_synced) {
            return $this->variables ?? [];
        }

        $vaultService = new VaultSyncService;

        return $vaultService->fetchVariables($this);
    }

    /**
     * Get enabled variables as key-value pairs.
     *
     * @return array<string, string>
     */
    public function getEnabledVariables(): array
    {
        $result = [];
        $variables = $this->getEffectiveVariables();

        foreach ($variables as $variable) {
            if (($variable['enabled'] ?? true) && ! empty($variable['key'])) {
                $result[$variable['key']] = $variable['value'] ?? '';
            }
        }

        return $result;
    }

    public function activate(): void
    {
        self::where('workspace_id', $this->workspace_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
