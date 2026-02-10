<?php

namespace App\Services\SecretsProviders;

use App\Contracts\SecretsProviderInterface;
use App\Services\SessionLogService;
use Illuminate\Support\Facades\Http;

class HashiCorpVaultProvider implements SecretsProviderInterface
{
    private string $token;

    /**
     * Namespace used only for AppRole authentication.
     * Data operations (get/put/list/delete secrets) do NOT use namespace header.
     */
    private ?string $authNamespace;

    public function __construct(
        private string $url,
        string $token,
        ?string $namespace,
        private string $mount,
        private string $authMethod = 'token',
        private ?string $roleId = null,
        private ?string $secretId = null,
        private bool $verifySsl = true,
    ) {
        $this->authNamespace = $namespace;
        $this->token = $authMethod === 'approle'
            ? $this->loginWithAppRole()
            : $token;
    }

    /**
     * @return array<string>
     */
    public function listSecrets(?string $basePath = null): array
    {
        $basePath = $basePath ? trim($basePath, '/') : null;
        $url = $basePath
            ? "{$this->url}/v1/{$this->mount}/metadata/{$basePath}"
            : "{$this->url}/v1/{$this->mount}/metadata";

        $response = $this->request()
            ->send('LIST', $url);

        if ($response->status() === 404) {
            app(SessionLogService::class)->logVaultOperation('list', $basePath ?? '/', 'No secrets found');

            return [];
        }

        $response->throw();

        $keys = $response->json('data.keys', []);
        app(SessionLogService::class)->logVaultOperation('list', $basePath ?? '/', 'Listed '.count($keys).' secret(s)');

        return $keys;
    }

    /**
     * @return array<string, string>|null
     */
    public function getSecrets(string $path): ?array
    {
        $response = $this->request()
            ->get("{$this->url}/v1/{$this->mount}/data/{$path}");

        if ($response->status() === 404) {
            app(SessionLogService::class)->logVaultOperation('get', $path, 'Secret not found', false);

            return null;
        }

        $response->throw();

        $data = $response->json('data.data');
        app(SessionLogService::class)->logVaultOperation('get', $path, 'Retrieved '.count($data ?? []).' key(s)');

        return $data;
    }

    /**
     * @param  array<string, string>  $data
     */
    public function putSecrets(string $path, array $data): void
    {
        $response = $this->request()
            ->post("{$this->url}/v1/{$this->mount}/data/{$path}", [
                'data' => $data,
            ]);

        $response->throw();

        app(SessionLogService::class)->logVaultOperation('put', $path, 'Saved '.count($data).' key(s)');
    }

    public function deleteSecrets(string $path): void
    {
        $response = $this->request()
            ->delete("{$this->url}/v1/{$this->mount}/metadata/{$path}");

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    public function testConnection(): bool
    {
        if ($this->authMethod === 'token') {
            try {
                $response = $this->request()->get("{$this->url}/v1/auth/token/lookup-self");

                return $response->successful();
            } catch (\Exception $e) {
                return false;
            }
        }

        // For AppRole, test the login endpoint with namespace if configured
        $request = Http::connectTimeout(5)->timeout(30);

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        if ($this->authNamespace) {
            $request = $request->withHeaders([
                'X-Vault-Namespace' => $this->authNamespace,
            ]);
        }

        $response = $request->post("{$this->url}/v1/auth/approle/login", [
            'role_id' => $this->roleId,
            'secret_id' => $this->secretId,
        ]);

        return $response->successful();
    }

    private function loginWithAppRole(): string
    {
        $request = Http::connectTimeout(5)->timeout(30);

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        // Only add namespace header for authentication if configured
        if ($this->authNamespace) {
            $request = $request->withHeaders([
                'X-Vault-Namespace' => $this->authNamespace,
            ]);
        }

        $response = $request->post("{$this->url}/v1/auth/approle/login", [
            'role_id' => $this->roleId,
            'secret_id' => $this->secretId,
        ]);

        $response->throw();

        return $response->json('auth.client_token');
    }

    /**
     * Create a request for data operations.
     * Note: Namespace header is NOT sent for data operations,
     * only for authentication. The mount path should contain
     * the full engine path.
     */
    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::withHeaders([
            'X-Vault-Token' => $this->token,
        ])->connectTimeout(5)->timeout(30);

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }
}
