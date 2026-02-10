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

it('opens an environment tab with correct structure', function () {
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id, 'name' => 'Staging']);

    $component = Livewire::test('api-tester')
        ->call('openEnvironmentTab', $env->id);

    $tabs = $component->get('openTabs');
    expect($tabs)->toHaveCount(1)
        ->and($tabs[0]['type'])->toBe('environment')
        ->and($tabs[0]['environmentId'])->toBe($env->id)
        ->and($tabs[0]['name'])->toBe('Staging');
});

it('deduplicates environment tabs by environmentId', function () {
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id]);

    $component = Livewire::test('api-tester')
        ->call('openEnvironmentTab', $env->id)
        ->call('openEnvironmentTab', $env->id);

    expect($component->get('openTabs'))->toHaveCount(1);
});

it('restores old tabs without type field as request tabs', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);

    // Simulate old format (no type field) saved in workspace settings
    $ws = app(\App\Services\WorkspaceService::class);
    $ws->setSetting('ui.open_tabs', [
        [
            'id' => 'old-tab-1',
            'requestId' => $request->id,
            'collectionId' => $collection->id,
            'folderId' => null,
            'name' => $request->name,
            'method' => $request->method,
        ],
    ]);
    $ws->setSetting('ui.active_tab_id', 'old-tab-1');

    $component = Livewire::test('api-tester');

    $tabs = $component->get('openTabs');
    expect($tabs)->toHaveCount(1)
        ->and($tabs[0]['type'])->toBe('request')
        ->and($tabs[0]['requestId'])->toBe($request->id);
});

it('closes environment tab and switches to adjacent tab', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id]);

    $component = Livewire::test('api-tester')
        ->call('openTab', $request->id)
        ->call('openEnvironmentTab', $env->id);

    $tabs = $component->get('openTabs');
    expect($tabs)->toHaveCount(2);

    // Close the active env tab → should switch to request tab
    $envTabId = $tabs[1]['id'];
    $requestTabId = $tabs[0]['id'];

    $component->call('closeTab', $envTabId);

    expect($component->get('openTabs'))->toHaveCount(1)
        ->and($component->get('activeTabId'))->toBe($requestTabId);
});

it('auto-closes environment tab when environment is deleted', function () {
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id]);

    $component = Livewire::test('api-tester')
        ->call('openEnvironmentTab', $env->id);

    expect($component->get('openTabs'))->toHaveCount(1);

    // Delete the environment and dispatch the event
    $env->delete();
    $component->dispatch('environments-updated');

    expect($component->get('openTabs'))->toHaveCount(0)
        ->and($component->get('activeTabId'))->toBeNull();
});

it('updates environment tab name when environment is renamed', function () {
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id, 'name' => 'Old Name']);

    $component = Livewire::test('api-tester')
        ->call('openEnvironmentTab', $env->id);

    $tabs = $component->get('openTabs');
    expect($tabs[0]['name'])->toBe('Old Name');

    $component->dispatch('environment-name-updated', environmentId: $env->id, name: 'New Name');

    $tabs = $component->get('openTabs');
    expect($tabs[0]['name'])->toBe('New Name');
});

it('supports mixed request and environment tabs', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id]);

    $component = Livewire::test('api-tester')
        ->call('openTab', $request->id)
        ->call('openEnvironmentTab', $env->id);

    $tabs = $component->get('openTabs');
    expect($tabs)->toHaveCount(2)
        ->and($tabs[0]['type'])->toBe('request')
        ->and($tabs[1]['type'])->toBe('environment');
});

it('computes activeTabType correctly', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $request = Request::factory()->create(['collection_id' => $collection->id]);
    $env = Environment::factory()->create(['workspace_id' => $this->workspace->id]);

    $component = Livewire::test('api-tester')
        ->call('openTab', $request->id);

    expect($component->get('activeTabType'))->toBe('request');

    $component->call('openEnvironmentTab', $env->id);

    expect($component->get('activeTabType'))->toBe('environment');
});
