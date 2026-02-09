<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Request;
use App\Models\Workspace;
use Livewire\Livewire;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    app(\App\Services\WorkspaceService::class)->switchTo($this->workspace->id);
});

it('returns resolved variable names from active environment', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'baseUrl', 'value' => 'https://api.test', 'enabled' => true],
            ['key' => 'token', 'value' => 'abc123', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);

    Livewire::test('request-builder')
        ->call('loadRequest', $request->id)
        ->assertSet('resolvedVariableNames', ['baseUrl', 'token']);
});

it('includes collection variables in resolved names', function () {
    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
        'variables' => [
            ['key' => 'collectionVar', 'value' => 'test', 'enabled' => true],
        ],
    ]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);

    Livewire::test('request-builder')
        ->call('loadRequest', $request->id)
        ->assertSet('resolvedVariableNames', ['collectionVar']);
});

it('merges environment and collection variables', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'envVar', 'value' => 'env-value', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
        'variables' => [
            ['key' => 'colVar', 'value' => 'col-value', 'enabled' => true],
        ],
    ]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);

    Livewire::test('request-builder')
        ->call('loadRequest', $request->id)
        ->assertSet('resolvedVariableNames', ['envVar', 'colVar']);
});

it('excludes disabled variables from resolved names', function () {
    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'enabled', 'value' => 'yes', 'enabled' => true],
            ['key' => 'disabled', 'value' => 'no', 'enabled' => false],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);

    Livewire::test('request-builder')
        ->call('loadRequest', $request->id)
        ->assertSet('resolvedVariableNames', ['enabled']);
});

it('refreshes resolved variable names on active environment change', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);

    $component = Livewire::test('request-builder')
        ->call('loadRequest', $request->id)
        ->assertSet('resolvedVariableNames', []);

    Environment::factory()
        ->active()
        ->withVariables([
            ['key' => 'newVar', 'value' => 'val', 'enabled' => true],
        ])
        ->create(['workspace_id' => $this->workspace->id]);

    $component
        ->dispatch('active-environment-changed')
        ->assertSet('resolvedVariableNames', ['newVar']);
});
