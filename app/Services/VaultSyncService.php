<?php

namespace App\Services;

use App\Contracts\SecretsProviderInterface;
use App\Enums\SecretsProvider;
use App\Models\Environment;
use App\Services\SecretsProviders\HashiCorpVaultProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VaultSyncService
{
    private ?SecretsProviderInterface $provider = null;

    public function getProvider(): ?SecretsProviderInterface
    {
        if ($this->provider) {
            return $this->provider;
        }

        $ws = app(WorkspaceService::class);
        $providerType = $ws->getSetting('vault.provider');
        $url = $ws->getSetting('vault.url');
        $authMethod = $ws->getSetting('vault.auth_method', 'token');
        $token = $ws->getSetting('vault.token', '');
        $roleId = $ws->getSetting('vault.role_id');
        $secretId = $ws->getSetting('vault.secret_id');
        $namespace = $ws->getSetting('vault.namespace');
        // Mount contains the full engine path (e.g., 'secret/myapp')
        $mount = $ws->getSetting('vault.mount', 'secret');

        if (! $providerType || ! $url) {
            return null;
        }

        if ($authMethod === 'token' && empty($token)) {
            return null;
        }

        if ($authMethod === 'approle' && (empty($roleId) || empty($secretId))) {
            return null;
        }

        $verifySsl = $ws->getSetting('vault.verify_ssl', true);

        $this->provider = match ($providerType) {
            SecretsProvider::HashiCorpVault->value => new HashiCorpVaultProvider(
                url: rtrim($url, '/'),
                token: $token,
                namespace: $namespace ?: null,
                mount: $mount,
                authMethod: $authMethod,
                roleId: $roleId,
                secretId: $secretId,
                verifySsl: $verifySsl,
            ),
            default => null,
        };

        return $this->provider;
    }

    public function isConfigured(): bool
    {
        return $this->getProvider() !== null;
    }

    public function testConnection(): bool
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return false;
        }

        return $provider->testConnection();
    }

    /**
     * Build the Vault path for an environment.
     * Returns just the environment slug - the mount path is handled separately.
     */
    public function buildPath(Environment $environment): string
    {
        if ($environment->vault_path) {
            return $environment->vault_path;
        }

        return Str::slug($environment->name);
    }

    /**
     * Fetch variables from Vault for an environment.
     *
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    public function fetchVariables(Environment $environment): array
    {
        $cacheKey = "vaulta_secrets_{$environment->id}";

        return Cache::remember($cacheKey, 60, function () use ($environment) {
            $provider = $this->getProvider();
            if (! $provider) {
                throw new \RuntimeException('Vault not configured');
            }

            $path = $this->buildPath($environment);
            $secrets = $provider->getSecrets($path);

            if ($secrets === null) {
                return [];
            }

            $variables = [];
            foreach ($secrets as $key => $value) {
                $variables[] = [
                    'key' => $key,
                    'value' => (string) $value,
                    'enabled' => true,
                ];
            }

            return $variables;
        });
    }

    /**
     * Push variables to Vault for an environment.
     *
     * @param  array<array{key: string, value: string, enabled: bool}>  $variables
     */
    public function pushVariables(Environment $environment, array $variables): void
    {
        $provider = $this->getProvider();
        if (! $provider) {
            throw new \RuntimeException('Vault not configured');
        }

        $data = [];
        foreach ($variables as $variable) {
            if (! empty($variable['key']) && ($variable['enabled'] ?? true)) {
                $data[$variable['key']] = $variable['value'] ?? '';
            }
        }

        $path = $this->buildPath($environment);
        $provider->putSecrets($path, $data);
        $this->clearCache($environment);
    }

    /**
     * Delete secrets from Vault for an environment.
     */
    public function deleteSecrets(Environment $environment): void
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return;
        }

        try {
            $path = $this->buildPath($environment);
            $provider->deleteSecrets($path);
        } catch (\Exception) {
            // Gracefully handle if already deleted
        }

        $this->clearCache($environment);
    }

    /**
     * Pull all secrets from Vault and create environment records for untracked paths.
     *
     * @return array{created: int, errors: array<string>}
     */
    public function pullAll(): array
    {
        $result = ['created' => 0, 'errors' => []];

        $provider = $this->getProvider();
        if (! $provider) {
            $result['errors'][] = 'Vault not configured';

            return $result;
        }

        try {
            // List secrets at the mount root (mount contains full engine path)
            $secretNames = $provider->listSecrets();
        } catch (\Exception $e) {
            $result['errors'][] = 'Failed to list secrets: '.$e->getMessage();

            return $result;
        }

        $workspaceId = app(WorkspaceService::class)->activeId();
        $existingPaths = Environment::where('vault_synced', true)
            ->forWorkspace($workspaceId)
            ->get()
            ->mapWithKeys(fn (Environment $env) => [$this->buildPath($env) => $env])
            ->all();

        foreach ($secretNames as $name) {
            $name = rtrim($name, '/');
            $fullPath = $name;

            if (isset($existingPaths[$fullPath])) {
                continue;
            }

            try {
                Environment::create([
                    'name' => Str::title(str_replace(['-', '_'], ' ', $name)),
                    'variables' => [],
                    'is_active' => false,
                    'order' => Environment::max('order') + 1,
                    'workspace_id' => $workspaceId,
                    'vault_synced' => true,
                    'vault_path' => $name,
                ]);
                $result['created']++;
            } catch (\Exception $e) {
                $result['errors'][] = "Failed to create environment for '{$name}': ".$e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Migrate secrets when an environment is renamed.
     */
    public function migrateEnvironment(Environment $environment, string $oldPath, string $newPath): void
    {
        if ($oldPath === $newPath) {
            return;
        }

        $provider = $this->getProvider();
        if (! $provider) {
            return;
        }

        $secrets = $provider->getSecrets($oldPath);
        if ($secrets !== null) {
            $provider->putSecrets($newPath, $secrets);
            $provider->deleteSecrets($oldPath);
        }

        $this->clearCache($environment);
    }

    /**
     * Clear cached secrets for an environment.
     */
    public function clearCache(Environment $environment): void
    {
        Cache::forget("vault_secrets_{$environment->id}");
    }
}
