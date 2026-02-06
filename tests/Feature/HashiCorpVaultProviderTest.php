<?php

use App\Services\SecretsProviders\HashiCorpVaultProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('lists secrets at a base path', function () {
    Http::fake([
        'https://vault.test/v1/secret/metadata/vaxtly' => Http::response([
            'data' => [
                'keys' => ['production', 'staging'],
            ],
        ]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $result = $provider->listSecrets('vaxtly');

    expect($result)->toBe(['production', 'staging']);
});

it('returns empty array when listing secrets returns 404', function () {
    Http::fake([
        'https://vault.test/v1/secret/metadata/vaxtly' => Http::response(null, 404),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $result = $provider->listSecrets('vaxtly');

    expect($result)->toBe([]);
});

it('gets secrets at a path', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => [
                'data' => [
                    'API_KEY' => 'secret-key',
                    'DB_HOST' => 'localhost',
                ],
                'metadata' => ['version' => 1],
            ],
        ]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $result = $provider->getSecrets('vaxtly/production');

    expect($result)->toBe([
        'API_KEY' => 'secret-key',
        'DB_HOST' => 'localhost',
    ]);
});

it('returns null when getting secrets that do not exist', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/nonexistent' => Http::response(null, 404),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $result = $provider->getSecrets('vaxtly/nonexistent');

    expect($result)->toBeNull();
});

it('puts secrets at a path', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => ['version' => 2],
        ]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $provider->putSecrets('vaxtly/production', [
        'API_KEY' => 'new-key',
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://vault.test/v1/secret/data/vaxtly/production'
            && $request->method() === 'POST'
            && $request['data']['API_KEY'] === 'new-key';
    });
});

it('deletes secrets at a path', function () {
    Http::fake([
        'https://vault.test/v1/secret/metadata/vaxtly/production' => Http::response(null, 204),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $provider->deleteSecrets('vaxtly/production');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://vault.test/v1/secret/metadata/vaxtly/production'
            && $request->method() === 'DELETE';
    });
});

it('handles 404 on delete gracefully', function () {
    Http::fake([
        'https://vault.test/v1/secret/metadata/vaxtly/production' => Http::response(null, 404),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    $provider->deleteSecrets('vaxtly/production');

    // Should not throw
    expect(true)->toBeTrue();
});

it('tests connection via token lookup endpoint', function () {
    Http::fake([
        'https://vault.test/v1/auth/token/lookup-self' => Http::response(['data' => ['id' => 'test-token']]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    expect($provider->testConnection())->toBeTrue();
});

it('returns false when token lookup fails', function () {
    Http::fake([
        'https://vault.test/v1/auth/token/lookup-self' => Http::response(null, 403),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'test-token',
        namespace: null,
        mount: 'secret',
    );

    expect($provider->testConnection())->toBeFalse();
});

it('sends vault token header on requests', function () {
    Http::fake([
        'https://vault.test/v1/auth/token/lookup-self' => Http::response(['data' => ['id' => 'test-token']]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: 'my-secret-token',
        namespace: null,
        mount: 'secret',
    );

    $provider->testConnection();

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Vault-Token', 'my-secret-token');
    });
});

it('uses namespace header only for approle auth not data operations', function () {
    Http::fake([
        'https://vault.test/v1/auth/approle/login' => Http::response([
            'auth' => [
                'client_token' => 'approle-token',
            ],
        ]),
        'https://vault.test/v1/secret/data/test-path' => Http::response([
            'data' => ['data' => ['KEY' => 'value']],
        ]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: '',
        namespace: 'admin/team',
        mount: 'secret',
        authMethod: 'approle',
        roleId: 'my-role-id',
        secretId: 'my-secret-id',
    );

    // Perform a data operation
    $provider->getSecrets('test-path');

    // Auth request should have namespace
    Http::assertSent(function ($request) {
        if ($request->url() === 'https://vault.test/v1/auth/approle/login') {
            return $request->hasHeader('X-Vault-Namespace', 'admin/team');
        }
        return true;
    });

    // Data request should NOT have namespace
    Http::assertSent(function ($request) {
        if ($request->url() === 'https://vault.test/v1/secret/data/test-path') {
            return ! $request->hasHeader('X-Vault-Namespace');
        }
        return true;
    });
});

it('authenticates with approle', function () {
    Http::fake([
        'https://vault.test/v1/auth/approle/login' => Http::response([
            'auth' => [
                'client_token' => 'approle-token',
            ],
        ]),
        'https://vault.test/v1/sys/health' => Http::response(['initialized' => true]),
    ]);

    $provider = new HashiCorpVaultProvider(
        url: 'https://vault.test',
        token: '',
        namespace: null,
        mount: 'secret',
        authMethod: 'approle',
        roleId: 'my-role-id',
        secretId: 'my-secret-id',
    );

    $provider->testConnection();

    Http::assertSent(function ($request) {
        if ($request->url() === 'https://vault.test/v1/auth/approle/login') {
            return $request['role_id'] === 'my-role-id'
                && $request['secret_id'] === 'my-secret-id';
        }

        return $request->hasHeader('X-Vault-Token', 'approle-token');
    });
});
