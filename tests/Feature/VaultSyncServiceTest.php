<?php

use App\Models\Environment;
use App\Models\Workspace;
use App\Services\VaultSyncService;
use App\Services\WorkspaceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    Environment::query()->delete();

    $workspace = Workspace::factory()->create([
        'settings' => [
            'vault' => [
                'provider' => 'hashicorp',
                'url' => 'https://vault.test',
                'auth_method' => 'token',
                'token' => 'test-token',
                'role_id' => '',
                'secret_id' => '',
                'namespace' => '',
                'mount' => 'secret',
            ],
        ],
    ]);
    set_setting('active.workspace', $workspace->id);
    app(WorkspaceService::class)->clearCache();
    $this->workspace = $workspace;
});

it('reports configured when all settings are present', function () {
    $service = new VaultSyncService;

    expect($service->isConfigured())->toBeTrue();
});

it('reports not configured when provider is missing', function () {
    $this->workspace->setSetting('vault.provider', '');
    app(WorkspaceService::class)->clearCache();

    $service = new VaultSyncService;

    expect($service->isConfigured())->toBeFalse();
});

it('reports not configured when url is missing', function () {
    $this->workspace->setSetting('vault.url', '');
    app(WorkspaceService::class)->clearCache();

    $service = new VaultSyncService;

    expect($service->isConfigured())->toBeFalse();
});

it('reports not configured when token is missing for token auth', function () {
    $this->workspace->setSetting('vault.token', '');
    app(WorkspaceService::class)->clearCache();

    $service = new VaultSyncService;

    expect($service->isConfigured())->toBeFalse();
});

it('builds path from environment name when no vault_path override', function () {
    $environment = Environment::factory()->create(['name' => 'Production API']);

    $service = new VaultSyncService;
    $path = $service->buildPath($environment);

    // Path is now just the slugified name (no basePath prefix)
    expect($path)->toBe('production-api');
});

it('uses vault_path override when set', function () {
    $environment = Environment::factory()->create([
        'name' => 'Production',
        'vault_path' => 'custom/path/prod',
    ]);

    $service = new VaultSyncService;
    $path = $service->buildPath($environment);

    expect($path)->toBe('custom/path/prod');
});

it('fetches variables from vault and converts format', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => [
                'data' => [
                    'API_KEY' => 'secret-key',
                    'DB_HOST' => 'localhost',
                ],
            ],
        ]),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->create(['name' => 'Production']);

    $service = new VaultSyncService;
    $variables = $service->fetchVariables($environment);

    expect($variables)->toHaveCount(2)
        ->and($variables[0])->toBe(['key' => 'API_KEY', 'value' => 'secret-key', 'enabled' => true])
        ->and($variables[1])->toBe(['key' => 'DB_HOST', 'value' => 'localhost', 'enabled' => true]);
});

it('caches fetched variables', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => [
                'data' => ['KEY' => 'value'],
            ],
        ]),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->create(['name' => 'Production']);

    $service = new VaultSyncService;
    $service->fetchVariables($environment);
    $service->fetchVariables($environment);

    Http::assertSentCount(1);
});

it('clears cache on push', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => ['version' => 1],
        ]),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->create(['name' => 'Production']);

    Cache::put("vault_secrets_{$environment->id}", ['cached' => true], 60);

    $service = new VaultSyncService;
    $service->pushVariables($environment, [
        ['key' => 'API_KEY', 'value' => 'new-value', 'enabled' => true],
    ]);

    expect(Cache::has("vault_secrets_{$environment->id}"))->toBeFalse();
});

it('pushes only enabled variables to vault', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => ['version' => 1],
        ]),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->create(['name' => 'Production']);

    $service = new VaultSyncService;
    $service->pushVariables($environment, [
        ['key' => 'API_KEY', 'value' => 'included', 'enabled' => true],
        ['key' => 'DISABLED_KEY', 'value' => 'excluded', 'enabled' => false],
        ['key' => '', 'value' => 'empty-key', 'enabled' => true],
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://vault.test/v1/secret/data/vaxtly/production'
            && $request['data'] === ['API_KEY' => 'included'];
    });
});

it('pulls all environments from vault', function () {
    Http::fake([
        // Now lists at mount root (no basePath)
        'https://vault.test/v1/secret/metadata' => Http::response([
            'data' => [
                'keys' => ['production', 'staging'],
            ],
        ]),
    ]);

    $service = new VaultSyncService;
    $result = $service->pullAll();

    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toBeEmpty();

    expect(Environment::where('vault_synced', true)->count())->toBe(2);

    // vault_path is now just the slug (no basePath prefix)
    $production = Environment::where('vault_path', 'production')->first();
    expect($production)->not->toBeNull()
        ->and($production->vault_synced)->toBeTrue()
        ->and($production->name)->toBe('Production');
});

it('does not duplicate already tracked environments on pull', function () {
    Http::fake([
        // Now lists at mount root (no basePath)
        'https://vault.test/v1/secret/metadata' => Http::response([
            'data' => [
                'keys' => ['production'],
            ],
        ]),
    ]);

    // vault_path is now just the slug
    Environment::factory()->vaultSynced('production')->create([
        'name' => 'Production',
        'workspace_id' => $this->workspace->id,
    ]);

    $service = new VaultSyncService;
    $result = $service->pullAll();

    expect($result['created'])->toBe(0);
    expect(Environment::where('vault_synced', true)->count())->toBe(1);
});

it('migrates secrets when environment is renamed', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/old-name' => Http::response([
            'data' => [
                'data' => ['KEY' => 'value'],
            ],
        ]),
        'https://vault.test/v1/secret/data/vaxtly/new-name' => Http::response([
            'data' => ['version' => 1],
        ]),
        'https://vault.test/v1/secret/metadata/vaxtly/old-name' => Http::response(null, 204),
    ]);

    $environment = Environment::factory()->vaultSynced()->create(['name' => 'New Name']);

    $service = new VaultSyncService;
    $service->migrateEnvironment($environment, 'vaxtly/old-name', 'vaxtly/new-name');

    Http::assertSent(function ($request) {
        if ($request->url() === 'https://vault.test/v1/secret/data/vaxtly/old-name' && $request->method() === 'GET') {
            return true;
        }
        if ($request->url() === 'https://vault.test/v1/secret/data/vaxtly/new-name' && $request->method() === 'POST') {
            return $request['data'] === ['KEY' => 'value'];
        }
        if ($request->url() === 'https://vault.test/v1/secret/metadata/vaxtly/old-name' && $request->method() === 'DELETE') {
            return true;
        }

        return false;
    });
});
