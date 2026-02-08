<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    /** @use HasFactory<\Database\Factories\FolderFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'collection_id',
        'parent_id',
        'name',
        'order',
        'environment_ids',
        'default_environment_id',
    ];

    protected function casts(): array
    {
        return [
            'environment_ids' => 'array',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('order');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class)->orderBy('order');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
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

    /**
     * Walk up the folder tree to find the closest ancestor (or self) with environment associations.
     */
    public function resolveEnvironmentFolder(): ?self
    {
        if (! empty($this->getEnvironmentIds())) {
            return $this;
        }

        $current = $this->parent;

        while ($current) {
            if (! empty($current->getEnvironmentIds())) {
                return $current;
            }

            $current = $current->parent;
        }

        return null;
    }
}
