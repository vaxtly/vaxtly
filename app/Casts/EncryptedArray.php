<?php

namespace App\Casts;

use App\Services\EncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<array<mixed>|null, array<mixed>|null>
 */
class EncryptedArray implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<mixed>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $encryption = app(EncryptionService::class);

        if ($encryption->isPlainJson($value)) {
            return json_decode($value, true);
        }

        try {
            $decrypted = $encryption->decrypt($value);

            return json_decode($decrypted, true);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $json = json_encode($value);

        return app(EncryptionService::class)->encrypt($json);
    }
}
