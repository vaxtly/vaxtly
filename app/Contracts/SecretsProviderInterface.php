<?php

namespace App\Contracts;

interface SecretsProviderInterface
{
    /**
     * List secret names under the given base path.
     * If basePath is null, list at the mount root.
     *
     * @return array<string>
     */
    public function listSecrets(?string $basePath = null): array;

    /**
     * Get secret key-value pairs at the given path.
     *
     * @return array<string, string>|null
     */
    public function getSecrets(string $path): ?array;

    /**
     * Write secret key-value pairs to the given path.
     *
     * @param  array<string, string>  $data
     */
    public function putSecrets(string $path, array $data): void;

    /**
     * Delete secrets at the given path.
     */
    public function deleteSecrets(string $path): void;

    /**
     * Test the connection to the secrets provider.
     */
    public function testConnection(): bool;
}
