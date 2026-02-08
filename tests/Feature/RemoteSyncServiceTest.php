<?php

use App\Contracts\GitProviderInterface;
use App\DataTransferObjects\FileContent;
use App\Exceptions\SyncConflictException;
use App\Models\Collection;
use App\Models\Folder;
use App\Models\Request;
use App\Services\RemoteSyncService;

beforeEach(function () {
    $this->syncService = new RemoteSyncService;
});

describe('normalizeFileState', function () {
    it('converts old flat-string format to array format', function () {
        $oldState = [
            'collections/abc/_collection.yaml' => 'sha123',
            'collections/abc/req1.yaml' => 'sha456',
        ];

        $normalized = $this->syncService->normalizeFileState($oldState);

        expect($normalized['collections/abc/_collection.yaml'])
            ->toBe(['content_hash' => null, 'remote_sha' => 'sha123', 'commit_sha' => null])
            ->and($normalized['collections/abc/req1.yaml'])
            ->toBe(['content_hash' => null, 'remote_sha' => 'sha456', 'commit_sha' => null]);
    });

    it('preserves new array format', function () {
        $newState = [
            'collections/abc/_collection.yaml' => [
                'content_hash' => 'hash123',
                'remote_sha' => 'sha123',
                'commit_sha' => 'commit123',
            ],
        ];

        $normalized = $this->syncService->normalizeFileState($newState);

        expect($normalized['collections/abc/_collection.yaml'])
            ->toBe(['content_hash' => 'hash123', 'remote_sha' => 'sha123', 'commit_sha' => 'commit123']);
    });

    it('handles mixed old and new formats', function () {
        $mixedState = [
            'collections/abc/_collection.yaml' => 'sha123',
            'collections/abc/req1.yaml' => [
                'content_hash' => 'hash456',
                'remote_sha' => 'sha456',
                'commit_sha' => null,
            ],
        ];

        $normalized = $this->syncService->normalizeFileState($mixedState);

        expect($normalized)->toHaveCount(2)
            ->and($normalized['collections/abc/_collection.yaml']['content_hash'])->toBeNull()
            ->and($normalized['collections/abc/_collection.yaml']['remote_sha'])->toBe('sha123')
            ->and($normalized['collections/abc/req1.yaml']['content_hash'])->toBe('hash456');
    });

    it('handles empty state', function () {
        expect($this->syncService->normalizeFileState([]))->toBe([]);
    });
});

describe('buildFileStateFromRemote', function () {
    it('builds consistent state from FileContent objects', function () {
        $files = [
            new FileContent(
                path: 'collections/abc/_collection.yaml',
                content: 'name: Test',
                sha: 'blobsha123',
                commitSha: 'commitsha456',
            ),
            new FileContent(
                path: 'collections/abc/req1.yaml',
                content: 'name: Request 1',
                sha: 'blobsha789',
            ),
        ];

        $state = $this->syncService->buildFileStateFromRemote($files);

        expect($state)->toHaveCount(2)
            ->and($state['collections/abc/_collection.yaml'])->toBe([
                'content_hash' => hash('sha256', 'name: Test'),
                'remote_sha' => 'blobsha123',
                'commit_sha' => 'commitsha456',
            ])
            ->and($state['collections/abc/req1.yaml']['commit_sha'])->toBeNull();
    });
});

describe('hasRemoteFileChanges', function () {
    it('returns false when no changes', function () {
        $stored = [
            'collections/abc/_collection.yaml' => [
                'content_hash' => 'hash1',
                'remote_sha' => 'sha1',
                'commit_sha' => null,
            ],
            'collections/abc/req1.yaml' => [
                'content_hash' => 'hash2',
                'remote_sha' => 'sha2',
                'commit_sha' => null,
            ],
        ];

        $remoteItems = [
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
            ['type' => 'file', 'path' => 'collections/abc/req1.yaml', 'sha' => 'sha2'],
        ];

        expect($this->syncService->hasRemoteFileChanges($stored, $remoteItems))->toBeFalse();
    });

    it('detects changed request file even when _collection.yaml unchanged', function () {
        $stored = [
            'collections/abc/_collection.yaml' => [
                'content_hash' => 'hash1',
                'remote_sha' => 'sha1',
                'commit_sha' => null,
            ],
            'collections/abc/req1.yaml' => [
                'content_hash' => 'hash2',
                'remote_sha' => 'sha2',
                'commit_sha' => null,
            ],
        ];

        $remoteItems = [
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
            ['type' => 'file', 'path' => 'collections/abc/req1.yaml', 'sha' => 'sha2_changed'],
        ];

        expect($this->syncService->hasRemoteFileChanges($stored, $remoteItems))->toBeTrue();
    });

    it('detects new file on remote', function () {
        $stored = [
            'collections/abc/_collection.yaml' => [
                'content_hash' => 'hash1',
                'remote_sha' => 'sha1',
                'commit_sha' => null,
            ],
        ];

        $remoteItems = [
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
            ['type' => 'file', 'path' => 'collections/abc/new_req.yaml', 'sha' => 'sha_new'],
        ];

        expect($this->syncService->hasRemoteFileChanges($stored, $remoteItems))->toBeTrue();
    });

    it('detects deleted file on remote', function () {
        $stored = [
            'collections/abc/_collection.yaml' => [
                'content_hash' => 'hash1',
                'remote_sha' => 'sha1',
                'commit_sha' => null,
            ],
            'collections/abc/deleted_req.yaml' => [
                'content_hash' => 'hash2',
                'remote_sha' => 'sha2',
                'commit_sha' => null,
            ],
        ];

        $remoteItems = [
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
        ];

        expect($this->syncService->hasRemoteFileChanges($stored, $remoteItems))->toBeTrue();
    });

    it('handles empty stored state (first sync)', function () {
        $remoteItems = [
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
        ];

        expect($this->syncService->hasRemoteFileChanges([], $remoteItems))->toBeTrue();
    });

    it('works with old flat-string format in stored state', function () {
        $stored = [
            'collections/abc/_collection.yaml' => 'sha1',
            'collections/abc/req1.yaml' => 'sha2',
        ];

        $remoteItems = [
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
            ['type' => 'file', 'path' => 'collections/abc/req1.yaml', 'sha' => 'sha2'],
        ];

        expect($this->syncService->hasRemoteFileChanges($stored, $remoteItems))->toBeFalse();
    });

    it('ignores directory entries in remote items', function () {
        $stored = [
            'collections/abc/_collection.yaml' => [
                'content_hash' => 'hash1',
                'remote_sha' => 'sha1',
                'commit_sha' => null,
            ],
        ];

        $remoteItems = [
            ['type' => 'dir', 'path' => 'collections/abc', 'sha' => 'tree_sha'],
            ['type' => 'file', 'path' => 'collections/abc/_collection.yaml', 'sha' => 'sha1'],
        ];

        expect($this->syncService->hasRemoteFileChanges($stored, $remoteItems))->toBeFalse();
    });
});

describe('isShaConflict', function () {
    it('returns true for SyncConflictException', function () {
        $exception = new SyncConflictException(['file.yaml']);

        expect($this->syncService->isShaConflict($exception))->toBeTrue();
    });

    it('returns false for RuntimeException', function () {
        $exception = new RuntimeException('Remote not configured');

        expect($this->syncService->isShaConflict($exception))->toBeFalse();
    });

    it('returns false for generic exception with 409 in message', function () {
        // The old code would match this - the new code should NOT
        $exception = new RuntimeException('HTTP 409 conflict');

        expect($this->syncService->isShaConflict($exception))->toBeFalse();
    });
});

describe('pullSingleCollection', function () {
    it('throws SyncConflictException when local is dirty and remote changed', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'is_dirty' => true,
            'remote_sha' => 'old_sha',
        ]);

        $collection->update([
            'file_shas' => [
                "collections/{$collection->id}/_collection.yaml" => [
                    'content_hash' => 'hash1',
                    'remote_sha' => 'old_blob_sha',
                    'commit_sha' => null,
                ],
            ],
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn([
                ['type' => 'file', 'path' => "collections/{$collection->id}/_collection.yaml", 'sha' => 'new_blob_sha'],
            ]);

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        expect(fn () => $this->syncService->pullSingleCollection($collection))
            ->toThrow(SyncConflictException::class);
    });

    it('returns false when all remote file SHAs match stored state', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'is_dirty' => false,
            'remote_sha' => 'sha1',
        ]);

        $collection->update([
            'file_shas' => [
                "collections/{$collection->id}/_collection.yaml" => [
                    'content_hash' => 'hash1',
                    'remote_sha' => 'sha1',
                    'commit_sha' => null,
                ],
                "collections/{$collection->id}/req1.yaml" => [
                    'content_hash' => 'hash2',
                    'remote_sha' => 'sha2',
                    'commit_sha' => null,
                ],
            ],
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn([
                ['type' => 'file', 'path' => "collections/{$collection->id}/_collection.yaml", 'sha' => 'sha1'],
                ['type' => 'file', 'path' => "collections/{$collection->id}/req1.yaml", 'sha' => 'sha2'],
            ]);

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        expect($this->syncService->pullSingleCollection($collection))->toBeFalse();
    });

    it('detects changes when only request files changed on remote', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'is_dirty' => false,
            'remote_sha' => 'collection_sha',
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Test Request',
            'method' => 'GET',
            'url' => 'https://example.com',
        ]);

        $collection->update([
            'file_shas' => [
                "collections/{$collection->id}/_collection.yaml" => [
                    'content_hash' => 'hash1',
                    'remote_sha' => 'collection_sha',
                    'commit_sha' => null,
                ],
                "collections/{$collection->id}/{$request->id}.yaml" => [
                    'content_hash' => 'hash2',
                    'remote_sha' => 'old_req_sha',
                    'commit_sha' => null,
                ],
            ],
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        // _collection.yaml SHA unchanged, but request SHA changed
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn([
                ['type' => 'file', 'path' => "collections/{$collection->id}/_collection.yaml", 'sha' => 'collection_sha'],
                ['type' => 'file', 'path' => "collections/{$collection->id}/{$request->id}.yaml", 'sha' => 'new_req_sha'],
            ]);

        // Should proceed to fetch full tree for import
        $mockProvider->shouldReceive('getDirectoryTree')
            ->once()
            ->andReturn([
                new FileContent(
                    path: "collections/{$collection->id}/_collection.yaml",
                    content: "id: {$collection->id}\nname: {$collection->name}\ndescription: ~\nvariables: []\nenvironment_ids: []\ndefault_environment_id: ~\n",
                    sha: 'collection_sha',
                ),
                new FileContent(
                    path: "collections/{$collection->id}/{$request->id}.yaml",
                    content: "id: {$request->id}\nname: Updated Request\nmethod: GET\nurl: 'https://example.com'\nheaders: []\nquery_params: []\nbody: ''\nbody_type: none\n",
                    sha: 'new_req_sha',
                ),
            ]);

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        expect($this->syncService->pullSingleCollection($collection))->toBeTrue();

        $collection->refresh();
        expect($collection->is_dirty)->toBeFalse()
            ->and($collection->file_shas["collections/{$collection->id}/{$request->id}.yaml"]['remote_sha'])->toBe('new_req_sha');
    });
});

describe('pushSingleRequest', function () {
    it('creates a new file when no existing state', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'existing_sha',
            'file_shas' => [],
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Test Request',
            'method' => 'GET',
            'url' => 'https://example.com',
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('createFile')
            ->once()
            ->andReturn('new_blob_sha');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        $result = $this->syncService->pushSingleRequest($collection, $request);

        expect($result)->toBeTrue();

        $collection->refresh();
        $filePath = "collections/{$collection->id}/{$request->id}.yaml";
        expect($collection->file_shas[$filePath])->not->toBeNull()
            ->and($collection->file_shas[$filePath]['remote_sha'])->toBe('new_blob_sha')
            ->and($collection->is_dirty)->toBeFalse();
    });

    it('updates an existing file using stored remote_sha', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'existing_sha',
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Test Request',
            'method' => 'POST',
            'url' => 'https://example.com/api',
        ]);

        $filePath = "collections/{$collection->id}/{$request->id}.yaml";
        $collection->update([
            'file_shas' => [
                $filePath => [
                    'content_hash' => 'old_hash',
                    'remote_sha' => 'old_blob_sha',
                    'commit_sha' => null,
                ],
            ],
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('updateFile')
            ->once()
            ->withArgs(function ($path, $content, $sha) {
                return $sha === 'old_blob_sha';
            })
            ->andReturn('updated_blob_sha');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        $result = $this->syncService->pushSingleRequest($collection, $request);

        expect($result)->toBeTrue();

        $collection->refresh();
        expect($collection->file_shas[$filePath]['remote_sha'])->toBe('updated_blob_sha');
    });

    it('uses commit_sha for GitLab when available', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'existing_sha',
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'GitLab Request',
        ]);

        $filePath = "collections/{$collection->id}/{$request->id}.yaml";
        $collection->update([
            'file_shas' => [
                $filePath => [
                    'content_hash' => 'old_hash',
                    'remote_sha' => 'blob_sha',
                    'commit_sha' => 'gitlab_commit_sha',
                ],
            ],
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('updateFile')
            ->once()
            ->withArgs(function ($path, $content, $sha) {
                return $sha === 'gitlab_commit_sha';
            })
            ->andReturn('new_blob_sha');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        expect($this->syncService->pushSingleRequest($collection, $request))->toBeTrue();
    });

    it('marks collection dirty on conflict (409)', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'existing_sha',
            'is_dirty' => false,
            'file_shas' => [],
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('createFile')
            ->once()
            ->andThrow(new RuntimeException('HTTP 409 conflict', 409));

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        $result = $this->syncService->pushSingleRequest($collection, $request);

        expect($result)->toBeFalse();

        $collection->refresh();
        expect($collection->is_dirty)->toBeTrue();
    });

    it('builds correct path for request in folder', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'existing_sha',
            'file_shas' => [],
        ]);

        $folder = Folder::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'folder_id' => $folder->id,
            'name' => 'Nested Request',
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('createFile')
            ->once()
            ->withArgs(function ($path) use ($collection, $folder, $request) {
                $expected = "collections/{$collection->id}/{$folder->id}/{$request->id}.yaml";

                return $path === $expected;
            })
            ->andReturn('new_sha');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        expect($this->syncService->pushSingleRequest($collection, $request))->toBeTrue();
    });
});

describe('pushCollection', function () {
    it('throws SyncConflictException on file conflicts', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'old_sha',
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Test',
            'method' => 'GET',
            'url' => 'https://example.com',
        ]);

        // Serialize to get a real file path
        $serializer = new \App\Services\YamlCollectionSerializer;
        $localFiles = $serializer->serializeToDirectory($collection);

        // Build base state that has different content_hash than current (= local changed)
        // AND set remote_sha to something different from what the mock returns (= remote changed)
        $baseState = [];
        foreach ($localFiles as $relativePath => $content) {
            $fullPath = "collections/{$relativePath}";
            $baseState[$fullPath] = [
                'content_hash' => 'different_old_hash',
                'remote_sha' => 'old_remote_sha',
                'commit_sha' => null,
            ];
        }

        $collection->update(['file_shas' => $baseState]);

        // Mock provider: remote SHAs are different from base = remote changed
        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn(collect($localFiles)->keys()->map(fn ($rel) => [
                'type' => 'file',
                'path' => "collections/{$rel}",
                'sha' => 'new_remote_sha',
            ])->values()->toArray());

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        expect(fn () => $this->syncService->pushCollection($collection))
            ->toThrow(SyncConflictException::class);
    });

    it('deletes orphaned files when items are moved out of collection', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'remote_sha' => 'old_sha',
        ]);

        // Create a single request (the collection currently has only this)
        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Remaining Request',
            'method' => 'GET',
            'url' => 'https://example.com',
        ]);

        // Base state has 2 request files (one was moved away since last sync)
        $movedRequestPath = "collections/{$collection->id}/moved-request-id.yaml";
        $collectionYamlPath = "collections/{$collection->id}/_collection.yaml";
        $remainingRequestPath = "collections/{$collection->id}/{$request->id}.yaml";

        // Serialize to get real content hashes
        $serializer = new \App\Services\YamlCollectionSerializer;
        $localFiles = $serializer->serializeToDirectory($collection);

        // Build base state that matches current local for existing files
        $baseState = [];
        foreach ($localFiles as $relativePath => $content) {
            $fullPath = "collections/{$relativePath}";
            $baseState[$fullPath] = [
                'content_hash' => hash('sha256', $content),
                'remote_sha' => sha1('blob '.strlen($content)."\0".$content),
                'commit_sha' => null,
            ];
        }
        // Add the "moved away" request to base state
        $baseState[$movedRequestPath] = [
            'content_hash' => 'old_hash',
            'remote_sha' => 'old_moved_sha',
            'commit_sha' => null,
        ];

        $collection->update(['file_shas' => $baseState]);

        // Remote still has the moved file
        $remoteItems = [
            ['type' => 'file', 'path' => $collectionYamlPath, 'sha' => $baseState[$collectionYamlPath]['remote_sha']],
            ['type' => 'file', 'path' => $remainingRequestPath, 'sha' => $baseState[$remainingRequestPath]['remote_sha']],
            ['type' => 'file', 'path' => $movedRequestPath, 'sha' => 'old_moved_sha'],
        ];

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn($remoteItems);

        // Verify commitMultipleFiles receives the delete path
        $mockProvider->shouldReceive('commitMultipleFiles')
            ->once()
            ->withArgs(function ($files, $message, $deletePaths) use ($movedRequestPath) {
                return in_array($movedRequestPath, $deletePaths);
            })
            ->andReturn('new_commit_sha');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        $this->syncService->pushCollection($collection);

        $collection->refresh();

        // Orphaned file should be removed from file_shas
        expect($collection->file_shas)->not->toHaveKey($movedRequestPath)
            ->and($collection->file_shas)->toHaveKey($remainingRequestPath)
            ->and($collection->file_shas)->toHaveKey($collectionYamlPath);
    });

    it('cleans file_shas to only contain current local files', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'file_shas' => [],
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Test',
            'method' => 'GET',
            'url' => 'https://example.com',
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn([]);
        $mockProvider->shouldReceive('commitMultipleFiles')
            ->once()
            ->andReturn('commit_sha');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        $this->syncService->pushCollection($collection);

        $collection->refresh();

        // file_shas should only have entries for files that actually exist
        $serializer = new \App\Services\YamlCollectionSerializer;
        $localFiles = $serializer->serializeToDirectory($collection);
        $expectedPaths = array_map(
            fn ($rel) => "collections/{$rel}",
            array_keys($localFiles),
        );

        expect(array_keys($collection->file_shas))->toEqualCanonicalizing($expectedPaths);
    });

    it('stores blob SHA as remote_sha instead of commit SHA', function () {
        $collection = Collection::factory()->create([
            'sync_enabled' => true,
            'file_shas' => [],
        ]);

        $request = Request::factory()->create([
            'collection_id' => $collection->id,
            'name' => 'Test',
            'method' => 'GET',
            'url' => 'https://example.com',
        ]);

        $mockProvider = Mockery::mock(GitProviderInterface::class);
        $mockProvider->shouldReceive('listDirectoryRecursive')
            ->once()
            ->andReturn([]);
        $mockProvider->shouldReceive('commitMultipleFiles')
            ->once()
            ->andReturn('commit_sha_abc123');

        $reflection = new ReflectionProperty(RemoteSyncService::class, 'provider');
        $reflection->setAccessible(true);
        $reflection->setValue($this->syncService, $mockProvider);

        $this->syncService->pushCollection($collection);

        $collection->refresh();

        // remote_sha should be the _collection.yaml blob SHA, NOT the commit SHA
        expect($collection->remote_sha)->not->toBe('commit_sha_abc123');

        // Verify it's a valid SHA-1 hash (40 hex chars)
        expect($collection->remote_sha)->toMatch('/^[0-9a-f]{40}$/');
    });
});
