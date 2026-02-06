<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Workspace;

it('can add an environment to a collection', function () {
    $collection = Collection::factory()->create();
    $environment = Environment::factory()->create();

    $collection->addEnvironment($environment->id);

    $collection->refresh();
    expect($collection->getEnvironmentIds())->toContain($environment->id);
});

it('does not duplicate environment ids when adding the same one twice', function () {
    $collection = Collection::factory()->create();
    $environment = Environment::factory()->create();

    $collection->addEnvironment($environment->id);
    $collection->addEnvironment($environment->id);

    $collection->refresh();
    expect($collection->getEnvironmentIds())->toHaveCount(1);
});

it('can check if a collection has an environment', function () {
    $collection = Collection::factory()->create();
    $environment = Environment::factory()->create();

    expect($collection->hasEnvironment($environment->id))->toBeFalse();

    $collection->addEnvironment($environment->id);
    $collection->refresh();

    expect($collection->hasEnvironment($environment->id))->toBeTrue();
});

it('can remove an environment from a collection', function () {
    $collection = Collection::factory()->create(['environment_ids' => ['env-1', 'env-2']]);

    $collection->removeEnvironment('env-1');
    $collection->refresh();

    expect($collection->getEnvironmentIds())->toBe(['env-2'])
        ->and($collection->hasEnvironment('env-1'))->toBeFalse();
});

it('clears default environment id when removing that environment', function () {
    $environment = Environment::factory()->create();
    $collection = Collection::factory()->create([
        'environment_ids' => [$environment->id],
        'default_environment_id' => $environment->id,
    ]);

    $collection->removeEnvironment($environment->id);
    $collection->refresh();

    expect($collection->default_environment_id)->toBeNull()
        ->and($collection->getEnvironmentIds())->toBe([]);
});

it('sets environment_ids to null when last environment is removed', function () {
    $environment = Environment::factory()->create();
    $collection = Collection::factory()->create([
        'environment_ids' => [$environment->id],
    ]);

    $collection->removeEnvironment($environment->id);
    $collection->refresh();

    expect($collection->environment_ids)->toBeNull();
});

it('can set a default environment', function () {
    $environment = Environment::factory()->create();
    $collection = Collection::factory()->create(['environment_ids' => [$environment->id]]);

    $collection->setDefaultEnvironment($environment->id);
    $collection->refresh();

    expect($collection->default_environment_id)->toBe($environment->id);
});

it('auto-adds environment to collection when setting as default', function () {
    $environment = Environment::factory()->create();
    $collection = Collection::factory()->create();

    $collection->setDefaultEnvironment($environment->id);
    $collection->refresh();

    expect($collection->hasEnvironment($environment->id))->toBeTrue()
        ->and($collection->default_environment_id)->toBe($environment->id);
});

it('can clear the default environment', function () {
    $environment = Environment::factory()->create();
    $collection = Collection::factory()->create([
        'environment_ids' => [$environment->id],
        'default_environment_id' => $environment->id,
    ]);

    $collection->setDefaultEnvironment(null);
    $collection->refresh();

    expect($collection->default_environment_id)->toBeNull()
        ->and($collection->hasEnvironment($environment->id))->toBeTrue();
});

it('cleans up collection references when an environment is deleted', function () {
    $workspace = Workspace::factory()->create();
    $env1 = Environment::factory()->create(['workspace_id' => $workspace->id]);
    $env2 = Environment::factory()->create(['workspace_id' => $workspace->id]);

    $collection = Collection::factory()->create([
        'workspace_id' => $workspace->id,
        'environment_ids' => [$env1->id, $env2->id],
        'default_environment_id' => $env1->id,
    ]);

    $env1->delete();
    $collection->refresh();

    expect($collection->getEnvironmentIds())->toBe([$env2->id])
        ->and($collection->default_environment_id)->toBeNull();
});

it('does not affect collections without the deleted environment', function () {
    $workspace = Workspace::factory()->create();
    $env1 = Environment::factory()->create(['workspace_id' => $workspace->id]);
    $env2 = Environment::factory()->create(['workspace_id' => $workspace->id]);

    $collection = Collection::factory()->create([
        'workspace_id' => $workspace->id,
        'environment_ids' => [$env2->id],
        'default_environment_id' => $env2->id,
    ]);

    $env1->delete();
    $collection->refresh();

    expect($collection->getEnvironmentIds())->toBe([$env2->id])
        ->and($collection->default_environment_id)->toBe($env2->id);
});

it('returns empty array for getEnvironmentIds when null', function () {
    $collection = Collection::factory()->create();

    expect($collection->getEnvironmentIds())->toBe([]);
});

it('serializes environment association fields', function () {
    $env = Environment::factory()->create();
    $collection = Collection::factory()->create([
        'environment_ids' => [$env->id],
        'default_environment_id' => $env->id,
    ]);

    $serializer = new \App\Services\CollectionSerializer;
    $data = $serializer->serialize($collection);

    expect($data['environment_ids'])->toBe([$env->id])
        ->and($data['default_environment_id'])->toBe($env->id);
});

it('validates environment ids on import and discards non-existent ones', function () {
    $workspace = Workspace::factory()->create();
    set_setting('active.workspace', $workspace->id);
    app(\App\Services\WorkspaceService::class)->clearCache();

    $env = Environment::factory()->create(['workspace_id' => $workspace->id]);
    $fakeId = (string) \Illuminate\Support\Str::uuid();

    $serializer = new \App\Services\CollectionSerializer;
    $collection = $serializer->importFromRemote([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Test Collection',
        'description' => 'Test',
        'variables' => [],
        'environment_ids' => [$env->id, $fakeId],
        'default_environment_id' => $env->id,
        'folders' => [],
        'requests' => [],
    ]);

    expect($collection->getEnvironmentIds())->toBe([$env->id])
        ->and($collection->default_environment_id)->toBe($env->id);
});

it('clears default environment id on import when it references non-existent env', function () {
    $workspace = Workspace::factory()->create();
    set_setting('active.workspace', $workspace->id);
    app(\App\Services\WorkspaceService::class)->clearCache();

    $fakeId = (string) \Illuminate\Support\Str::uuid();

    $serializer = new \App\Services\CollectionSerializer;
    $collection = $serializer->importFromRemote([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'name' => 'Test Collection',
        'description' => 'Test',
        'variables' => [],
        'environment_ids' => [$fakeId],
        'default_environment_id' => $fakeId,
        'folders' => [],
        'requests' => [],
    ]);

    expect($collection->environment_ids)->toBeNull()
        ->and($collection->default_environment_id)->toBeNull();
});
