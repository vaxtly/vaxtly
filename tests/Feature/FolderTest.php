<?php

use App\Models\Collection;
use App\Models\Folder;
use App\Models\Request;

it('belongs to a collection', function () {
    $collection = Collection::factory()->create();
    $folder = Folder::factory()->create(['collection_id' => $collection->id]);

    expect($folder->collection->id)->toBe($collection->id);
});

it('can have a parent folder', function () {
    $collection = Collection::factory()->create();
    $parentFolder = Folder::factory()->create(['collection_id' => $collection->id]);
    $childFolder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => $parentFolder->id,
    ]);

    expect($childFolder->parent->id)->toBe($parentFolder->id);
});

it('can have child folders', function () {
    $collection = Collection::factory()->create();
    $parentFolder = Folder::factory()->create(['collection_id' => $collection->id]);

    Folder::factory()->count(3)->create([
        'collection_id' => $collection->id,
        'parent_id' => $parentFolder->id,
    ]);

    expect($parentFolder->children)->toHaveCount(3);
});

it('can have requests', function () {
    $collection = Collection::factory()->create();
    $folder = Folder::factory()->create(['collection_id' => $collection->id]);

    Request::factory()->count(2)->create([
        'collection_id' => $collection->id,
        'folder_id' => $folder->id,
    ]);

    expect($folder->requests)->toHaveCount(2);
});

it('scopes to root folders', function () {
    $collection = Collection::factory()->create();
    $rootFolder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => null,
    ]);
    $childFolder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => $rootFolder->id,
    ]);

    $rootFolders = Folder::roots()->get();

    expect($rootFolders)->toHaveCount(1)
        ->and($rootFolders->first()->id)->toBe($rootFolder->id);
});

it('cascades delete to child folders', function () {
    $collection = Collection::factory()->create();
    $parentFolder = Folder::factory()->create(['collection_id' => $collection->id]);
    $childFolder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => $parentFolder->id,
    ]);

    $parentFolder->delete();

    expect(Folder::count())->toBe(0);
});

it('nullifies request folder_id on delete', function () {
    $collection = Collection::factory()->create();
    $folder = Folder::factory()->create(['collection_id' => $collection->id]);
    $request = Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => $folder->id,
    ]);

    $folder->delete();

    $request->refresh();
    expect($request->folder_id)->toBeNull();
});

it('collection has root folders relationship', function () {
    $collection = Collection::factory()->create();
    $rootFolder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => null,
    ]);
    $childFolder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'parent_id' => $rootFolder->id,
    ]);

    expect($collection->rootFolders)->toHaveCount(1)
        ->and($collection->rootFolders->first()->id)->toBe($rootFolder->id);
});

it('collection has root requests relationship', function () {
    $collection = Collection::factory()->create();
    $folder = Folder::factory()->create(['collection_id' => $collection->id]);

    $rootRequest = Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => null,
    ]);
    $folderRequest = Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => $folder->id,
    ]);

    expect($collection->rootRequests)->toHaveCount(1)
        ->and($collection->rootRequests->first()->id)->toBe($rootRequest->id);
});
