<?php

use App\Models\Collection;
use App\Models\Folder;
use App\Models\Request;
use App\Models\Workspace;
use Livewire\Livewire;

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    app(\App\Services\WorkspaceService::class)->switchTo($this->workspace->id);
});

it('reorders collections', function () {
    $c1 = Collection::factory()->create(['workspace_id' => $this->workspace->id, 'order' => 0, 'name' => 'Alpha']);
    $c2 = Collection::factory()->create(['workspace_id' => $this->workspace->id, 'order' => 1, 'name' => 'Beta']);
    $c3 = Collection::factory()->create(['workspace_id' => $this->workspace->id, 'order' => 2, 'name' => 'Gamma']);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderCollections', $c3->id, 0);

    expect($c3->fresh()->order)->toBe(0)
        ->and($c1->fresh()->order)->toBe(1)
        ->and($c2->fresh()->order)->toBe(2);
});

it('reorders requests within the same folder', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $folder = Folder::factory()->create(['collection_id' => $collection->id]);

    $r1 = Request::factory()->create(['collection_id' => $collection->id, 'folder_id' => $folder->id, 'order' => 0]);
    $r2 = Request::factory()->create(['collection_id' => $collection->id, 'folder_id' => $folder->id, 'order' => 1]);
    $r3 = Request::factory()->create(['collection_id' => $collection->id, 'folder_id' => $folder->id, 'order' => 2]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderRequests', $r3->id, 0, "folder:{$folder->id}");

    expect($r3->fresh()->order)->toBe(0)
        ->and($r1->fresh()->order)->toBe(1)
        ->and($r2->fresh()->order)->toBe(2);
});

it('moves a request to a different folder', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $folderA = Folder::factory()->create(['collection_id' => $collection->id]);
    $folderB = Folder::factory()->create(['collection_id' => $collection->id]);

    $request = Request::factory()->create(['collection_id' => $collection->id, 'folder_id' => $folderA->id, 'order' => 0]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderRequests', $request->id, 0, "folder:{$folderB->id}");

    expect($request->fresh()->folder_id)->toBe($folderB->id)
        ->and($request->fresh()->collection_id)->toBe($collection->id);
});

it('moves a request to collection root', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $folder = Folder::factory()->create(['collection_id' => $collection->id]);

    $request = Request::factory()->create(['collection_id' => $collection->id, 'folder_id' => $folder->id, 'order' => 0]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderRequests', $request->id, 0, "collection:{$collection->id}");

    expect($request->fresh()->folder_id)->toBeNull()
        ->and($request->fresh()->collection_id)->toBe($collection->id);
});

it('moves a request across collections', function () {
    $collection1 = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $collection2 = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $folder = Folder::factory()->create(['collection_id' => $collection2->id]);

    $request = Request::factory()->create(['collection_id' => $collection1->id, 'folder_id' => null, 'order' => 0]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderRequests', $request->id, 0, "folder:{$folder->id}");

    expect($request->fresh()->collection_id)->toBe($collection2->id)
        ->and($request->fresh()->folder_id)->toBe($folder->id);
});

it('reorders folders within the same parent', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);

    $f1 = Folder::factory()->create(['collection_id' => $collection->id, 'order' => 0]);
    $f2 = Folder::factory()->create(['collection_id' => $collection->id, 'order' => 1]);
    $f3 = Folder::factory()->create(['collection_id' => $collection->id, 'order' => 2]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderFolders', $f3->id, 0, "collection:{$collection->id}");

    expect($f3->fresh()->order)->toBe(0)
        ->and($f1->fresh()->order)->toBe(1)
        ->and($f2->fresh()->order)->toBe(2);
});

it('moves a folder to a different collection', function () {
    $collection1 = Collection::factory()->create(['workspace_id' => $this->workspace->id]);
    $collection2 = Collection::factory()->create(['workspace_id' => $this->workspace->id]);

    $folder = Folder::factory()->create(['collection_id' => $collection1->id]);
    $childFolder = Folder::factory()->create(['collection_id' => $collection1->id, 'parent_id' => $folder->id]);
    $request = Request::factory()->create(['collection_id' => $collection1->id, 'folder_id' => $folder->id, 'order' => 0]);
    $childRequest = Request::factory()->create(['collection_id' => $collection1->id, 'folder_id' => $childFolder->id, 'order' => 0]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderFolders', $folder->id, 0, "collection:{$collection2->id}");

    expect($folder->fresh()->collection_id)->toBe($collection2->id)
        ->and($folder->fresh()->parent_id)->toBeNull()
        ->and($childFolder->fresh()->collection_id)->toBe($collection2->id)
        ->and($request->fresh()->collection_id)->toBe($collection2->id)
        ->and($childRequest->fresh()->collection_id)->toBe($collection2->id);
});

it('moves a folder to a subfolder', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);

    $folderA = Folder::factory()->create(['collection_id' => $collection->id]);
    $folderB = Folder::factory()->create(['collection_id' => $collection->id]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderFolders', $folderA->id, 0, "folder:{$folderB->id}");

    expect($folderA->fresh()->parent_id)->toBe($folderB->id);
});

it('prevents circular nesting when moving a parent into its own child', function () {
    $collection = Collection::factory()->create(['workspace_id' => $this->workspace->id]);

    $parent = Folder::factory()->create(['collection_id' => $collection->id, 'order' => 0]);
    $child = Folder::factory()->create(['collection_id' => $collection->id, 'parent_id' => $parent->id, 'order' => 0]);

    Livewire::test('sidebar')
        ->set('sort', 'manual')
        ->call('reorderFolders', $parent->id, 0, "folder:{$child->id}");

    // Parent should not have moved — still at root
    expect($parent->fresh()->parent_id)->toBeNull();
});

it('uses order column in manual sort mode', function () {
    $collection1 = Collection::factory()->create(['workspace_id' => $this->workspace->id, 'order' => 2, 'name' => 'Alpha']);
    $collection2 = Collection::factory()->create(['workspace_id' => $this->workspace->id, 'order' => 0, 'name' => 'Gamma']);
    $collection3 = Collection::factory()->create(['workspace_id' => $this->workspace->id, 'order' => 1, 'name' => 'Beta']);

    // In manual mode, collections should be ordered by `order` column, not alphabetically
    $ordered = Collection::forWorkspace($this->workspace->id)->orderBy('order')->pluck('id')->toArray();

    expect($ordered)->toBe([
        $collection2->id,
        $collection3->id,
        $collection1->id,
    ]);
});
