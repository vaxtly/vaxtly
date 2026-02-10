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
