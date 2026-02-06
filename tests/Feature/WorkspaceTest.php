<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Workspace;
use App\Services\WorkspaceService;

beforeEach(function () {
    Workspace::query()->delete();
});

it('can create a workspace', function () {
    $workspace = Workspace::factory()->create(['name' => 'Work']);

    expect($workspace->name)->toBe('Work')
        ->and($workspace->settings)->toBeArray();
});

it('can rename a workspace', function () {
    $workspace = Workspace::factory()->create(['name' => 'Old Name']);

    $workspace->update(['name' => 'New Name']);

    expect($workspace->fresh()->name)->toBe('New Name');
});

it('deletes workspace and cascades to collections and environments', function () {
    $workspace = Workspace::factory()->create();
    $collection = Collection::factory()->create(['workspace_id' => $workspace->id]);
    $environment = Environment::factory()->create(['workspace_id' => $workspace->id]);

    $workspace->delete();

    expect(Collection::find($collection->id))->toBeNull()
        ->and(Environment::find($environment->id))->toBeNull();
});

it('scopes collections to workspace', function () {
    $ws1 = Workspace::factory()->create();
    $ws2 = Workspace::factory()->create();

    Collection::factory()->count(2)->create(['workspace_id' => $ws1->id]);
    Collection::factory()->count(3)->create(['workspace_id' => $ws2->id]);

    expect(Collection::forWorkspace($ws1->id)->count())->toBe(2)
        ->and(Collection::forWorkspace($ws2->id)->count())->toBe(3);
});

it('scopes environments to workspace', function () {
    $ws1 = Workspace::factory()->create();
    $ws2 = Workspace::factory()->create();

    Environment::factory()->count(2)->create(['workspace_id' => $ws1->id]);
    Environment::factory()->count(3)->create(['workspace_id' => $ws2->id]);

    expect(Environment::forWorkspace($ws1->id)->count())->toBe(2)
        ->and(Environment::forWorkspace($ws2->id)->count())->toBe(3);
});

it('can switch active workspace', function () {
    $ws1 = Workspace::factory()->create();
    $ws2 = Workspace::factory()->create();

    $service = app(WorkspaceService::class);
    set_setting('active.workspace', $ws1->id);
    $service->clearCache();

    expect($service->activeId())->toBe($ws1->id);

    $service->switchTo($ws2->id);

    expect($service->activeId())->toBe($ws2->id)
        ->and(get_setting('active.workspace'))->toBe($ws2->id);
});

it('gets and sets workspace-scoped settings', function () {
    $workspace = Workspace::factory()->create(['settings' => []]);
    set_setting('active.workspace', $workspace->id);

    $service = app(WorkspaceService::class);
    $service->clearCache();

    $service->setSetting('remote.provider', 'github');
    $service->clearCache();

    expect($service->getSetting('remote.provider'))->toBe('github')
        ->and($service->getSetting('remote.token', 'default'))->toBe('default');
});

it('stores settings per workspace independently', function () {
    $ws1 = Workspace::factory()->create(['settings' => ['remote' => ['provider' => 'github']]]);
    $ws2 = Workspace::factory()->create(['settings' => ['remote' => ['provider' => 'gitlab']]]);

    $service = app(WorkspaceService::class);

    $service->switchTo($ws1->id);
    expect($service->getSetting('remote.provider'))->toBe('github');

    $service->switchTo($ws2->id);
    expect($service->getSetting('remote.provider'))->toBe('gitlab');
});

it('falls back to first workspace when active setting is invalid', function () {
    $workspace = Workspace::factory()->create(['order' => 0]);
    set_setting('active.workspace', 'non-existent-id');

    $service = app(WorkspaceService::class);
    $service->clearCache();

    expect($service->activeId())->toBe($workspace->id);
});

it('workspace has ordered scope', function () {
    Workspace::factory()->create(['name' => 'Third', 'order' => 3]);
    Workspace::factory()->create(['name' => 'First', 'order' => 1]);
    Workspace::factory()->create(['name' => 'Second', 'order' => 2]);

    $workspaces = Workspace::ordered()->get();

    expect($workspaces->first()->name)->toBe('First')
        ->and($workspaces->last()->name)->toBe('Third');
});

it('workspace has collections and environments relationships', function () {
    $workspace = Workspace::factory()->create();
    Collection::factory()->count(2)->create(['workspace_id' => $workspace->id]);
    Environment::factory()->count(3)->create(['workspace_id' => $workspace->id]);

    expect($workspace->collections)->toHaveCount(2)
        ->and($workspace->environments)->toHaveCount(3);
});
