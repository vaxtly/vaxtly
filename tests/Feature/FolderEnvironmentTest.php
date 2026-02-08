<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Workspace;

it('can add an environment to a folder', function () {
    $folder = Folder::factory()->create();
    $environment = Environment::factory()->create();

    $folder->addEnvironment($environment->id);
    $folder->refresh();

    expect($folder->getEnvironmentIds())->toContain($environment->id);
});

it('does not duplicate environment ids when adding the same one twice', function () {
    $folder = Folder::factory()->create();
    $environment = Environment::factory()->create();

    $folder->addEnvironment($environment->id);
    $folder->addEnvironment($environment->id);
    $folder->refresh();

    expect($folder->getEnvironmentIds())->toHaveCount(1);
});

it('can check if a folder has an environment', function () {
    $folder = Folder::factory()->create();
    $environment = Environment::factory()->create();

    expect($folder->hasEnvironment($environment->id))->toBeFalse();

    $folder->addEnvironment($environment->id);
    $folder->refresh();

    expect($folder->hasEnvironment($environment->id))->toBeTrue();
});

it('can remove an environment from a folder', function () {
    $folder = Folder::factory()->create(['environment_ids' => ['env-1', 'env-2']]);

    $folder->removeEnvironment('env-1');
    $folder->refresh();

    expect($folder->getEnvironmentIds())->toBe(['env-2'])
        ->and($folder->hasEnvironment('env-1'))->toBeFalse();
});

it('clears default environment id when removing that environment', function () {
    $environment = Environment::factory()->create();
    $folder = Folder::factory()->create([
        'environment_ids' => [$environment->id],
        'default_environment_id' => $environment->id,
    ]);

    $folder->removeEnvironment($environment->id);
    $folder->refresh();

    expect($folder->default_environment_id)->toBeNull()
        ->and($folder->getEnvironmentIds())->toBe([]);
});

it('sets environment_ids to null when last environment is removed', function () {
    $environment = Environment::factory()->create();
    $folder = Folder::factory()->create([
        'environment_ids' => [$environment->id],
    ]);

    $folder->removeEnvironment($environment->id);
    $folder->refresh();

    expect($folder->environment_ids)->toBeNull();
});

it('can set a default environment on a folder', function () {
    $environment = Environment::factory()->create();
    $folder = Folder::factory()->create(['environment_ids' => [$environment->id]]);

    $folder->setDefaultEnvironment($environment->id);
    $folder->refresh();

    expect($folder->default_environment_id)->toBe($environment->id);
});

it('auto-adds environment to folder when setting as default', function () {
    $environment = Environment::factory()->create();
    $folder = Folder::factory()->create();

    $folder->setDefaultEnvironment($environment->id);
    $folder->refresh();

    expect($folder->hasEnvironment($environment->id))->toBeTrue()
        ->and($folder->default_environment_id)->toBe($environment->id);
});

it('can clear the default environment on a folder', function () {
    $environment = Environment::factory()->create();
    $folder = Folder::factory()->create([
        'environment_ids' => [$environment->id],
        'default_environment_id' => $environment->id,
    ]);

    $folder->setDefaultEnvironment(null);
    $folder->refresh();

    expect($folder->default_environment_id)->toBeNull()
        ->and($folder->hasEnvironment($environment->id))->toBeTrue();
});

it('cleans up folder references when an environment is deleted', function () {
    $workspace = Workspace::factory()->create();
    $env1 = Environment::factory()->create(['workspace_id' => $workspace->id]);
    $env2 = Environment::factory()->create(['workspace_id' => $workspace->id]);

    $collection = Collection::factory()->create(['workspace_id' => $workspace->id]);
    $folder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'environment_ids' => [$env1->id, $env2->id],
        'default_environment_id' => $env1->id,
    ]);

    $env1->delete();
    $folder->refresh();

    expect($folder->getEnvironmentIds())->toBe([$env2->id])
        ->and($folder->default_environment_id)->toBeNull();
});

it('does not affect folders without the deleted environment', function () {
    $workspace = Workspace::factory()->create();
    $env1 = Environment::factory()->create(['workspace_id' => $workspace->id]);
    $env2 = Environment::factory()->create(['workspace_id' => $workspace->id]);

    $collection = Collection::factory()->create(['workspace_id' => $workspace->id]);
    $folder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'environment_ids' => [$env2->id],
        'default_environment_id' => $env2->id,
    ]);

    $env1->delete();
    $folder->refresh();

    expect($folder->getEnvironmentIds())->toBe([$env2->id])
        ->and($folder->default_environment_id)->toBe($env2->id);
});

it('returns empty array for getEnvironmentIds when null', function () {
    $folder = Folder::factory()->create();

    expect($folder->getEnvironmentIds())->toBe([]);
});

it('resolves self when folder has environment ids', function () {
    $env = Environment::factory()->create();
    $folder = Folder::factory()->create([
        'environment_ids' => [$env->id],
    ]);

    $resolved = $folder->resolveEnvironmentFolder();

    expect($resolved->id)->toBe($folder->id);
});

it('resolves parent when folder has no env ids but parent does', function () {
    $env = Environment::factory()->create();
    $parent = Folder::factory()->create([
        'environment_ids' => [$env->id],
    ]);
    $child = Folder::factory()->inFolder($parent)->create();

    $resolved = $child->resolveEnvironmentFolder();

    expect($resolved->id)->toBe($parent->id);
});

it('resolves grandparent when walking up tree', function () {
    $env = Environment::factory()->create();
    $grandparent = Folder::factory()->create([
        'environment_ids' => [$env->id],
    ]);
    $parent = Folder::factory()->inFolder($grandparent)->create();
    $child = Folder::factory()->inFolder($parent)->create();

    $resolved = $child->resolveEnvironmentFolder();

    expect($resolved->id)->toBe($grandparent->id);
});

it('returns null when no folder in tree has environment ids', function () {
    $parent = Folder::factory()->create();
    $child = Folder::factory()->inFolder($parent)->create();

    $resolved = $child->resolveEnvironmentFolder();

    expect($resolved)->toBeNull();
});

it('serializes folder env fields in CollectionSerializer', function () {
    $env = Environment::factory()->create();
    $collection = Collection::factory()->create();
    $folder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => null,
        'environment_ids' => [$env->id],
        'default_environment_id' => $env->id,
    ]);

    $serializer = new \App\Services\CollectionSerializer;
    $data = $serializer->serialize($collection);

    expect($data['folders'][0]['environment_ids'])->toBe([$env->id])
        ->and($data['folders'][0]['default_environment_id'])->toBe($env->id);
});

it('round-trips folder env fields through CollectionSerializer', function () {
    $workspace = Workspace::factory()->create();
    set_setting('active.workspace', $workspace->id);
    app(\App\Services\WorkspaceService::class)->clearCache();

    $env = Environment::factory()->create(['workspace_id' => $workspace->id]);
    $collection = Collection::factory()->create(['workspace_id' => $workspace->id]);
    $folder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => null,
        'environment_ids' => [$env->id],
        'default_environment_id' => $env->id,
    ]);

    $serializer = new \App\Services\CollectionSerializer;
    $data = $serializer->serialize($collection);

    $imported = $serializer->importFromRemote($data, $collection->id);
    $importedFolder = $imported->folders()->first();

    expect($importedFolder->getEnvironmentIds())->toBe([$env->id])
        ->and($importedFolder->default_environment_id)->toBe($env->id);
});

it('uses factory withEnvironments state', function () {
    $folder = Folder::factory()->withEnvironments(['env-1', 'env-2'])->create();

    expect($folder->getEnvironmentIds())->toBe(['env-1', 'env-2']);
});

it('uses factory withDefaultEnvironment state', function () {
    $folder = Folder::factory()
        ->withEnvironments(['env-1'])
        ->withDefaultEnvironment('env-1')
        ->create();

    expect($folder->default_environment_id)->toBe('env-1');
});
