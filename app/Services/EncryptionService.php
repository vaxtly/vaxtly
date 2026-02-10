<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class EncryptionService
{
    private ?bool $useNative = null;

    public function encrypt(string $value): string
    {
        if ($this->useNativeEncryption()) {
            $result = $this->nativeApiCall('post', 'system/encrypt', ['string' => $value]);
            if ($result?->successful()) {
                return $result->json('result') ?? Crypt::encryptString($value);
            }
        }

        return Crypt::encryptString($value);
    }

    public function decrypt(string $value): string
    {
        if ($this->useNativeEncryption()) {
            $result = $this->nativeApiCall('post', 'system/decrypt', ['string' => $value]);
            if ($result?->successful()) {
                return $result->json('result') ?? Crypt::decryptString($value);
            }
        }

        return Crypt::decryptString($value);
    }

    /**
     * Check if a value is plain JSON (legacy unencrypted data).
     * Encrypted ciphertext is never valid JSON, so successful decode means legacy.
     */
    public function isPlainJson(string $value): bool
    {
        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function useNativeEncryption(): bool
    {
        if ($this->useNative !== null) {
            return $this->useNative;
        }

        if (! config('nativephp-internal.running')) {
            return $this->useNative = false;
        }

        try {
            $response = $this->nativeApiCall('get', 'system/can-encrypt');

            return $this->useNative = (bool) $response?->json('result');
        } catch (\Throwable) {
            return $this->useNative = false;
        }
    }

    /**
     * Direct HTTP call to Electron's internal API with short timeouts
     * to prevent app freeze when Electron isn't ready yet.
     */
    private function nativeApiCall(string $method, string $endpoint, array $data = []): ?Response
    {
        try {
            $request = Http::asJson()
                ->baseUrl(config('nativephp-internal.api_url', ''))
                ->connectTimeout(3)
                ->timeout(10)
                ->withHeaders([
                    'X-NativePHP-Secret' => config('nativephp-internal.secret'),
                ]);

            return $method === 'get'
                ? $request->get($endpoint, $data)
                : $request->post($endpoint, $data);
        } catch (\Throwable) {
            return null;
        }
    }
}
