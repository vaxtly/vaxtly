<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestHistory extends Model
{
    /** @use HasFactory<\Database\Factories\RequestHistoryFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'request_id',
        'method',
        'url',
        'status_code',
        'response_body',
        'response_headers',
        'duration_ms',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'response_headers' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
}
