<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    /** @use HasFactory<\Database\Factories\RequestFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'collection_id',
        'folder_id',
        'name',
        'url',
        'method',
        'headers',
        'query_params',
        'body',
        'body_type',
        'scripts',
        'auth',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'query_params' => 'array',
            'scripts' => 'array',
            'auth' => 'array',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function getPreRequestScripts(): array
    {
        return $this->scripts['pre_request'] ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getPostResponseScripts(): array
    {
        return $this->scripts['post_response'] ?? [];
    }

    public function hasScripts(): bool
    {
        return ! empty($this->getPreRequestScripts()) || ! empty($this->getPostResponseScripts());
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequestHistory::class);
    }

    public function isGet(): bool
    {
        return strtoupper($this->method) === 'GET';
    }

    public function isPost(): bool
    {
        return strtoupper($this->method) === 'POST';
    }

    public function isPut(): bool
    {
        return strtoupper($this->method) === 'PUT';
    }

    public function isDelete(): bool
    {
        return strtoupper($this->method) === 'DELETE';
    }
}
