<?php

use App\Models\Environment;
use App\Models\Workspace;
use App\Services\VariableSubstitutionService;
use App\Services\WorkspaceService;
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

it('returns database variables for non-vault environments', function () {
    $environment = Environment::factory()->withVariables([
        ['key' => 'API_KEY', 'value' => 'db-value', 'enabled' => true],
    ])->create();

    $variables = $environment->getEffectiveVariables();

    expect($variables)->toHaveCount(1)
        ->and($variables[0]['key'])->toBe('API_KEY')
        ->and($variables[0]['value'])->toBe('db-value');
});

it('returns vault variables for vault-synced environments', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => [
                'data' => [
                    'API_KEY' => 'vault-value',
                ],
            ],
        ]),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->create([
        'name' => 'Production',
        'variables' => [['key' => 'API_KEY', 'value' => 'db-value', 'enabled' => true]],
    ]);

    $variables = $environment->getEffectiveVariables();

    expect($variables)->toHaveCount(1)
        ->and($variables[0]['value'])->toBe('vault-value');
});

it('resolves enabled variables from vault for substitution', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response([
            'data' => [
                'data' => [
                    'baseUrl' => 'https://api.example.com',
                    'apiKey' => 'secret-123',
                ],
            ],
        ]),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->active()->create([
        'name' => 'Production',
        'workspace_id' => $this->workspace->id,
    ]);

    $service = new VariableSubstitutionService;
    $result = $service->substitute('{{baseUrl}}/users?key={{apiKey}}');

    expect($result)->toBe('https://api.example.com/users?key=secret-123');
});

it('handles vault connectivity failure gracefully in substitution', function () {
    Http::fake([
        'https://vault.test/v1/secret/data/vaxtly/production' => Http::response(null, 500),
    ]);

    $environment = Environment::factory()->vaultSynced('vaxtly/production')->active()->create([
        'name' => 'Production',
        'workspace_id' => $this->workspace->id,
    ]);

    $service = new VariableSubstitutionService;
    $result = $service->substitute('{{baseUrl}}/users');

    // Should return the original text when Vault fails
    expect($result)->toBe('{{baseUrl}}/users');
});

it('scopes vault synced environments', function () {
    Environment::factory()->create(['name' => 'Local']);
    Environment::factory()->vaultSynced()->create(['name' => 'Vault Env']);

    $vaultEnvironments = Environment::vaultSynced()->get();

    expect($vaultEnvironments)->toHaveCount(1)
        ->and($vaultEnvironments->first()->name)->toBe('Vault Env');
});

it('gets vault path from override', function () {
    $environment = Environment::factory()->create([
        'name' => 'Production',
        'vault_path' => 'custom/production',
    ]);

    expect($environment->getVaultPath())->toBe('custom/production');
});

it('derives vault path from name when no override', function () {
    $environment = Environment::factory()->create([
        'name' => 'My Production',
        'vault_path' => null,
    ]);

    // Path is now just the slugified name (no basePath prefix)
    expect($environment->getVaultPath())->toBe('my-production');
});
