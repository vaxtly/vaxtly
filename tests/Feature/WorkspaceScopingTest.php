<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Workspace;
use App\Services\WorkspaceService;

it('environment activate only deactivates within same workspace', function () {
    $ws1 = Workspace::factory()->create();
    $ws2 = Workspace::factory()->create();

    $env1 = Environment::factory()->active()->create(['workspace_id' => $ws1->id]);
    $env2 = Environment::factory()->create(['workspace_id' => $ws1->id]);
    $env3 = Environment::factory()->active()->create(['workspace_id' => $ws2->id]);

    // Activate env2 in ws1
    $env2->activate();

    expect($env1->fresh()->is_active)->toBeFalse()
        ->and($env2->fresh()->is_active)->toBeTrue()
        ->and($env3->fresh()->is_active)->toBeTrue(); // ws2 env unaffected
});

it('environment deletion only cleans up collections in same workspace', function () {
    $ws1 = Workspace::factory()->create();
    $ws2 = Workspace::factory()->create();

    $env = Environment::factory()->create(['workspace_id' => $ws1->id]);

    $collection1 = Collection::factory()->create([
        'workspace_id' => $ws1->id,
        'environment_ids' => [$env->id],
    ]);

    $collection2 = Collection::factory()->create([
        'workspace_id' => $ws2->id,
        'environment_ids' => [$env->id],
    ]);

    $env->delete();

    // Collection in same workspace should be cleaned up
    expect($collection1->fresh()->getEnvironmentIds())->not->toContain($env->id);

    // Collection in different workspace should NOT be cleaned up
    expect($collection2->fresh()->getEnvironmentIds())->toContain($env->id);
});

it('postman import assigns workspace_id to collections', function () {
    $workspace = Workspace::factory()->create();
    set_setting('active.workspace', $workspace->id);
    app(WorkspaceService::class)->clearCache();

    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Imported Collection',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [],
    ];

    $file = makeUploadedJson($postmanData);
    $service = new \App\Services\PostmanImportService;
    $service->import($file);

    $collection = Collection::where('name', 'Imported Collection')->first();
    expect($collection)->not->toBeNull()
        ->and($collection->workspace_id)->toBe($workspace->id);
});

it('postman import assigns workspace_id to environments', function () {
    $workspace = Workspace::factory()->create();
    set_setting('active.workspace', $workspace->id);
    app(WorkspaceService::class)->clearCache();

    $postmanData = [
        '_postman_variable_scope' => 'environment',
        'name' => 'Test Env',
        'values' => [
            ['key' => 'API_KEY', 'value' => 'secret', 'enabled' => true],
        ],
    ];

    $file = makeUploadedJson($postmanData);
    $service = new \App\Services\PostmanImportService;
    $service->import($file);

    $environment = Environment::where('name', 'Test Env')->first();
    expect($environment)->not->toBeNull()
        ->and($environment->workspace_id)->toBe($workspace->id);
});

it('variable substitution uses active environment from current workspace', function () {
    $ws1 = Workspace::factory()->create();
    $ws2 = Workspace::factory()->create();

    Environment::factory()->active()->create([
        'workspace_id' => $ws1->id,
        'variables' => [
            ['key' => 'HOST', 'value' => 'ws1.example.com', 'enabled' => true],
        ],
    ]);

    Environment::factory()->active()->create([
        'workspace_id' => $ws2->id,
        'variables' => [
            ['key' => 'HOST', 'value' => 'ws2.example.com', 'enabled' => true],
        ],
    ]);

    $service = new \App\Services\VariableSubstitutionService;
    $wsService = app(WorkspaceService::class);

    // Switch to ws1
    $wsService->switchTo($ws1->id);
    expect($service->substitute('https://{{HOST}}/api'))->toBe('https://ws1.example.com/api');

    // Switch to ws2
    $wsService->switchTo($ws2->id);
    expect($service->substitute('https://{{HOST}}/api'))->toBe('https://ws2.example.com/api');
});

it('collection serializer assigns workspace_id on new import', function () {
    $workspace = Workspace::factory()->create();
    set_setting('active.workspace', $workspace->id);
    app(WorkspaceService::class)->clearCache();

    $serializer = new \App\Services\CollectionSerializer;
    $collection = $serializer->importFromRemote([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Remote Collection',
        'description' => 'Test',
        'variables' => [],
        'environment_ids' => [],
        'default_environment_id' => null,
        'folders' => [],
        'requests' => [],
    ]);

    expect($collection->workspace_id)->toBe($workspace->id);
});

function makeUploadedJson(array $data): \Illuminate\Http\UploadedFile
{
    $tempFile = tempnam(sys_get_temp_dir(), 'ws_test_');
    file_put_contents($tempFile, json_encode($data));

    return new \Illuminate\Http\UploadedFile(
        $tempFile,
        'test.json',
        'application/json',
        null,
        true
    );
}
