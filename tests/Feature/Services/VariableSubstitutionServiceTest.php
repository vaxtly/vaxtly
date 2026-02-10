<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Workspace;
use App\Services\VariableSubstitutionService;
use App\Services\WorkspaceService;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    app(WorkspaceService::class)->switchTo($this->workspace->id);
});

it('resolves nested variables within variable values', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'host', 'value' => 'api.example.com', 'enabled' => true],
            ['key' => 'port', 'value' => '8080', 'enabled' => true],
            ['key' => 'api_prefix', 'value' => 'api', 'enabled' => true],
            ['key' => 'api_version', 'value' => 'v2', 'enabled' => true],
            ['key' => 'url', 'value' => '{{host}}:{{port}}/{{api_prefix}}/{{api_version}}', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $service = new VariableSubstitutionService;

    expect($service->substitute('{{url}}'))
        ->toBe('api.example.com:8080/api/v2');
});

it('resolves multi-level nested variables', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'scheme', 'value' => 'https', 'enabled' => true],
            ['key' => 'domain', 'value' => 'example.com', 'enabled' => true],
            ['key' => 'base', 'value' => '{{scheme}}://{{domain}}', 'enabled' => true],
            ['key' => 'url', 'value' => '{{base}}/api', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $service = new VariableSubstitutionService;

    expect($service->substitute('{{url}}'))
        ->toBe('https://example.com/api');
});

it('leaves unresolved variables intact', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'url', 'value' => '{{host}}/api', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $service = new VariableSubstitutionService;

    expect($service->substitute('{{url}}'))
        ->toBe('{{host}}/api');
});

it('handles circular references gracefully without infinite loop', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'a', 'value' => '{{b}}', 'enabled' => true],
            ['key' => 'b', 'value' => '{{a}}', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $service = new VariableSubstitutionService;

    // Should not hang — stops after max depth and returns whatever state it reached
    $result = $service->substitute('{{a}}');
    expect($result)->toBeString();
});

it('resolves nested variables across environment and collection', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'host', 'value' => 'api.example.com', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
        'variables' => [
            ['key' => 'url', 'value' => '{{host}}/data', 'enabled' => true],
        ],
    ]);

    $service = new VariableSubstitutionService;

    expect($service->substitute('{{url}}', $collection->id))
        ->toBe('api.example.com/data');
});

it('returns variables with source from environment', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'host', 'value' => 'api.example.com', 'enabled' => true],
            ['key' => 'token', 'value' => 'abc123', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id, 'name' => 'Production']);

    $service = new VariableSubstitutionService;
    $result = $service->getResolvedVariablesWithSource();

    expect($result)->toHaveKeys(['host', 'token'])
        ->and($result['host'])->toBe(['value' => 'api.example.com', 'source' => 'Env: Production'])
        ->and($result['token'])->toBe(['value' => 'abc123', 'source' => 'Env: Production']);
});

it('returns collection variables with Collection source', function () {
    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
        'variables' => [
            ['key' => 'baseUrl', 'value' => 'https://local', 'enabled' => true],
        ],
    ]);

    $service = new VariableSubstitutionService;
    $result = $service->getResolvedVariablesWithSource($collection->id);

    expect($result['baseUrl'])->toBe(['value' => 'https://local', 'source' => 'Collection']);
});

it('collection variables override environment variables in source', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'host', 'value' => 'env-host.com', 'enabled' => true],
            ['key' => 'token', 'value' => 'env-token', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id, 'name' => 'Staging']);

    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
        'variables' => [
            ['key' => 'host', 'value' => 'col-host.com', 'enabled' => true],
        ],
    ]);

    $service = new VariableSubstitutionService;
    $result = $service->getResolvedVariablesWithSource($collection->id);

    expect($result['host'])->toBe(['value' => 'col-host.com', 'source' => 'Collection'])
        ->and($result['token'])->toBe(['value' => 'env-token', 'source' => 'Env: Staging']);
});

it('resolves nested variable references in source values', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'host', 'value' => 'api.example.com', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id, 'name' => 'Production']);

    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
        'variables' => [
            ['key' => 'url', 'value' => '{{host}}/data', 'enabled' => true],
        ],
    ]);

    $service = new VariableSubstitutionService;
    $result = $service->getResolvedVariablesWithSource($collection->id);

    expect($result['url'])->toBe(['value' => 'api.example.com/data', 'source' => 'Collection'])
        ->and($result['host'])->toBe(['value' => 'api.example.com', 'source' => 'Env: Production']);
});
