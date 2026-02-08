<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Native\Desktop\Facades\System;

class EncryptionService
{
    private ?bool $useNative = null;

    public function encrypt(string $value): string
    {
        if ($this->useNativeEncryption()) {
            return System::encrypt($value);
        }

        return Crypt::encryptString($value);
    }

    public function decrypt(string $value): string
    {
        if ($this->useNativeEncryption()) {
            return System::decrypt($value);
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
            return $this->useNative = System::canEncrypt();
        } catch (\Throwable) {
            return $this->useNative = false;
        }

        return $this->useNative = false;
    }
}
