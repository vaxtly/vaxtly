<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use App\Models\Workspace;
use App\Services\DataExportImportService;
use App\Services\PostmanImportService;
use App\Services\WorkspaceService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Request::query()->delete();
    Folder::query()->delete();
    Collection::query()->delete();
    Environment::query()->delete();

    $this->workspace = Workspace::factory()->withRemoteSettings([
        'provider' => 'github',
        'repository' => 'owner/repo',
        'branch' => 'main',
        'auto_sync' => true,
    ])->withVaultSettings([
        'provider' => 'hashicorp',
        'url' => 'https://vault.example.com',
        'auth_method' => 'token',
        'namespace' => 'my-ns',
        'mount' => 'secret/myapp',
    ])->create(['name' => 'Test Workspace']);

    set_setting('active.workspace', $this->workspace->id);
    app(WorkspaceService::class)->clearCache();

    $this->service = new DataExportImportService;
});

// --- Export Tests ---

it('exports all workspace data with correct envelope', function () {
    Collection::factory()->create([
        'name' => 'My API',
        'workspace_id' => $this->workspace->id,
        'variables' => [['key' => 'base_url', 'value' => 'https://api.example.com', 'enabled' => true]],
    ]);

    Environment::factory()->withVariables([
        ['key' => 'token', 'value' => 'secret', 'enabled' => true],
    ])->create([
        'name' => 'Production',
        'workspace_id' => $this->workspace->id,
    ]);

    $result = $this->service->exportAll($this->workspace->id);

    expect($result['vaxtly_export'])->toBeTrue()
        ->and($result['version'])->toBe(1)
        ->and($result['type'])->toBe('all')
        ->and($result['exported_at'])->toBeString()
        ->and($result['data'])->toHaveKeys(['collections', 'environments', 'config']);
});

it('exports collections with folders and requests', function () {
    $collection = Collection::factory()->create([
        'name' => 'Test Collection',
        'description' => 'A test',
        'workspace_id' => $this->workspace->id,
        'variables' => [['key' => 'host', 'value' => 'localhost', 'enabled' => true]],
        'order' => 1,
    ]);

    $folder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'name' => 'Users',
        'order' => 1,
    ]);

    $childFolder = Folder::factory()->inFolder($folder)->create([
        'name' => 'Admin',
        'order' => 1,
    ]);

    Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => $folder->id,
        'name' => 'List Users',
        'method' => 'GET',
        'url' => 'https://api.example.com/users',
        'order' => 1,
    ]);

    Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => $childFolder->id,
        'name' => 'List Admins',
        'method' => 'GET',
        'url' => 'https://api.example.com/admins',
        'order' => 1,
    ]);

    Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => null,
        'name' => 'Health Check',
        'method' => 'GET',
        'url' => 'https://api.example.com/health',
        'order' => 2,
    ]);

    $result = $this->service->exportCollections($this->workspace->id);

    expect($result['type'])->toBe('collections')
        ->and($result['data']['collections'])->toHaveCount(1);

    $col = $result['data']['collections'][0];
    expect($col['name'])->toBe('Test Collection')
        ->and($col['description'])->toBe('A test')
        ->and($col['variables'])->toHaveCount(1)
        ->and($col['folders'])->toHaveCount(1)
        ->and($col['folders'][0]['name'])->toBe('Users')
        ->and($col['folders'][0]['children'])->toHaveCount(1)
        ->and($col['folders'][0]['children'][0]['name'])->toBe('Admin')
        ->and($col['folders'][0]['requests'])->toHaveCount(1)
        ->and($col['folders'][0]['requests'][0]['name'])->toBe('List Users')
        ->and($col['requests'])->toHaveCount(1)
        ->and($col['requests'][0]['name'])->toBe('Health Check');
});

it('exports environments with variables', function () {
    Environment::factory()->withVariables([
        ['key' => 'api_key', 'value' => 'secret123', 'enabled' => true],
        ['key' => 'debug', 'value' => 'true', 'enabled' => false],
    ])->create([
        'name' => 'Staging',
        'workspace_id' => $this->workspace->id,
        'is_active' => true,
        'order' => 1,
    ]);

    $result = $this->service->exportEnvironments($this->workspace->id);

    expect($result['type'])->toBe('environments')
        ->and($result['data']['environments'])->toHaveCount(1);

    $env = $result['data']['environments'][0];
    expect($env['name'])->toBe('Staging')
        ->and($env['is_active'])->toBeTrue()
        ->and($env['variables'])->toHaveCount(2)
        ->and($env['variables'][0]['key'])->toBe('api_key');
});

it('skips variable values for vault-synced environments', function () {
    Environment::factory()->vaultSynced('my/path')->withVariables([
        ['key' => 'secret', 'value' => 'should-not-export', 'enabled' => true],
    ])->create([
        'name' => 'Vault Env',
        'workspace_id' => $this->workspace->id,
    ]);

    $result = $this->service->exportEnvironments($this->workspace->id);

    $env = $result['data']['environments'][0];
    expect($env['vault_synced'])->toBeTrue()
        ->and($env['vault_path'])->toBe('my/path')
        ->and($env['variables'])->toBeEmpty();
});

it('exports config without tokens', function () {
    $result = $this->service->exportConfig($this->workspace->id);

    expect($result['type'])->toBe('config');

    $config = $result['data']['config'];
    expect($config['remote']['provider'])->toBe('github')
        ->and($config['remote']['repository'])->toBe('owner/repo')
        ->and($config['remote']['branch'])->toBe('main')
        ->and($config['remote']['auto_sync'])->toBeTrue()
        ->and($config['remote'])->not->toHaveKey('token')
        ->and($config['vault']['provider'])->toBe('hashicorp')
        ->and($config['vault']['url'])->toBe('https://vault.example.com')
        ->and($config['vault']['verify_ssl'])->toBeBool()
        ->and($config['vault']['auto_sync'])->toBeBool()
        ->and($config['vault'])->not->toHaveKey('token');
});

it('exports request details including scripts and auth', function () {
    $collection = Collection::factory()->create([
        'workspace_id' => $this->workspace->id,
    ]);

    Request::factory()->create([
        'collection_id' => $collection->id,
        'name' => 'Auth Request',
        'method' => 'POST',
        'url' => 'https://api.example.com/login',
        'headers' => [['key' => 'Content-Type', 'value' => 'application/json', 'enabled' => true]],
        'query_params' => [['key' => 'version', 'value' => '2', 'enabled' => true]],
        'body' => '{"email":"test@test.com"}',
        'body_type' => 'json',
        'scripts' => ['pre_request' => [['action' => 'send_request', 'request_id' => 'abc']]],
        'auth' => ['type' => 'bearer', 'token' => '{{token}}'],
        'order' => 1,
    ]);

    $result = $this->service->exportCollections($this->workspace->id);
    $req = $result['data']['collections'][0]['requests'][0];

    expect($req['name'])->toBe('Auth Request')
        ->and($req['method'])->toBe('POST')
        ->and($req['headers'])->toHaveCount(1)
        ->and($req['query_params'])->toHaveCount(1)
        ->and($req['body'])->toBe('{"email":"test@test.com"}')
        ->and($req['body_type'])->toBe('json')
        ->and($req['scripts']['pre_request'])->toHaveCount(1)
        ->and($req['auth']['type'])->toBe('bearer');
});

// --- Import Tests ---

it('imports collections from a vaxtly export', function () {
    $export = [
        'vaxtly_export' => true,
        'version' => 1,
        'type' => 'collections',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'collections' => [
                [
                    'name' => 'Imported API',
                    'description' => 'An imported collection',
                    'variables' => [['key' => 'base', 'value' => 'https://api.com', 'enabled' => true]],
                    'order' => 1,
                    'folders' => [
                        [
                            'name' => 'Users',
                            'order' => 1,
                            'children' => [
                                [
                                    'name' => 'Admin',
                                    'order' => 1,
                                    'children' => [],
                                    'requests' => [
                                        [
                                            'name' => 'List Admins',
                                            'method' => 'GET',
                                            'url' => 'https://api.com/admins',
                                            'headers' => [],
                                            'query_params' => [],
                                            'body' => null,
                                            'body_type' => 'none',
                                            'scripts' => null,
                                            'auth' => null,
                                            'order' => 1,
                                        ],
                                    ],
                                ],
                            ],
                            'requests' => [
                                [
                                    'name' => 'List Users',
                                    'method' => 'GET',
                                    'url' => 'https://api.com/users',
                                    'headers' => [],
                                    'query_params' => [],
                                    'body' => null,
                                    'body_type' => 'none',
                                    'scripts' => null,
                                    'auth' => null,
                                    'order' => 1,
                                ],
                            ],
                        ],
                    ],
                    'requests' => [
                        [
                            'name' => 'Health',
                            'method' => 'GET',
                            'url' => 'https://api.com/health',
                            'headers' => [],
                            'query_params' => [],
                            'body' => null,
                            'body_type' => 'none',
                            'scripts' => null,
                            'auth' => null,
                            'order' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $json = json_encode($export);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['collections'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $collection = Collection::where('workspace_id', $this->workspace->id)->first();
    expect($collection->name)->toBe('Imported API')
        ->and($collection->description)->toBe('An imported collection')
        ->and($collection->variables)->toHaveCount(1);

    expect(Folder::count())->toBe(2);
    expect(Request::count())->toBe(3);

    $rootFolder = Folder::whereNull('parent_id')->first();
    expect($rootFolder->name)->toBe('Users');

    $childFolder = Folder::whereNotNull('parent_id')->first();
    expect($childFolder->name)->toBe('Admin')
        ->and($childFolder->parent_id)->toBe($rootFolder->id);
});

it('imports environments from a vaxtly export', function () {
    $export = [
        'vaxtly_export' => true,
        'version' => 1,
        'type' => 'environments',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'environments' => [
                [
                    'name' => 'Production',
                    'order' => 1,
                    'is_active' => true,
                    'vault_synced' => false,
                    'vault_path' => null,
                    'variables' => [
                        ['key' => 'url', 'value' => 'https://prod.com', 'enabled' => true],
                    ],
                ],
                [
                    'name' => 'Vault Env',
                    'order' => 2,
                    'is_active' => false,
                    'vault_synced' => true,
                    'vault_path' => 'prod/secrets',
                    'variables' => [],
                ],
            ],
        ],
    ];

    $json = json_encode($export);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['environments'])->toBe(2)
        ->and($result['errors'])->toBeEmpty();

    $envs = Environment::where('workspace_id', $this->workspace->id)->ordered()->get();
    expect($envs)->toHaveCount(2);

    // Imported environments should never be active
    expect($envs[0]->name)->toBe('Production')
        ->and($envs[0]->is_active)->toBeFalse()
        ->and($envs[0]->variables)->toHaveCount(1);

    expect($envs[1]->name)->toBe('Vault Env')
        ->and($envs[1]->vault_synced)->toBeTrue()
        ->and($envs[1]->vault_path)->toBe('prod/secrets');
});

it('imports config from a vaxtly export', function () {
    $export = [
        'vaxtly_export' => true,
        'version' => 1,
        'type' => 'config',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'config' => [
                'remote' => [
                    'provider' => 'gitlab',
                    'repository' => 'group/project',
                    'branch' => 'develop',
                    'auto_sync' => false,
                ],
                'vault' => [
                    'provider' => 'hashicorp',
                    'url' => 'https://new-vault.com',
                    'auth_method' => 'approle',
                    'namespace' => 'new-ns',
                    'mount' => 'kv/data',
                    'verify_ssl' => false,
                    'auto_sync' => false,
                ],
            ],
        ],
    ];

    $json = json_encode($export);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['config'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();

    $ws = app(WorkspaceService::class);
    expect($ws->getSetting('remote.provider'))->toBe('gitlab')
        ->and($ws->getSetting('remote.repository'))->toBe('group/project')
        ->and($ws->getSetting('remote.branch'))->toBe('develop')
        ->and($ws->getSetting('vault.url'))->toBe('https://new-vault.com')
        ->and($ws->getSetting('vault.auth_method'))->toBe('approle')
        ->and($ws->getSetting('vault.verify_ssl'))->toBeFalsy()
        ->and($ws->getSetting('vault.auto_sync'))->toBe('0');
});

it('imports all data types from a full export', function () {
    $export = [
        'vaxtly_export' => true,
        'version' => 1,
        'type' => 'all',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'collections' => [
                [
                    'name' => 'Full Export Collection',
                    'description' => null,
                    'variables' => [],
                    'order' => 1,
                    'folders' => [],
                    'requests' => [
                        [
                            'name' => 'Ping',
                            'method' => 'GET',
                            'url' => 'https://api.com/ping',
                            'headers' => [],
                            'query_params' => [],
                            'body' => null,
                            'body_type' => 'none',
                            'scripts' => null,
                            'auth' => null,
                            'order' => 1,
                        ],
                    ],
                ],
            ],
            'environments' => [
                [
                    'name' => 'Dev',
                    'order' => 1,
                    'is_active' => false,
                    'vault_synced' => false,
                    'vault_path' => null,
                    'variables' => [['key' => 'env', 'value' => 'dev', 'enabled' => true]],
                ],
            ],
            'config' => [
                'remote' => [
                    'provider' => 'github',
                    'repository' => 'new/repo',
                    'branch' => 'main',
                    'auto_sync' => true,
                ],
                'vault' => [
                    'provider' => 'hashicorp',
                    'url' => 'https://vault.dev',
                    'auth_method' => 'token',
                    'namespace' => '',
                    'mount' => 'secret',
                ],
            ],
        ],
    ];

    $json = json_encode($export);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['collections'])->toBe(1)
        ->and($result['environments'])->toBe(1)
        ->and($result['config'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

it('generates unique names on duplicate import', function () {
    Collection::factory()->create([
        'name' => 'My API',
        'workspace_id' => $this->workspace->id,
    ]);

    Environment::factory()->create([
        'name' => 'Production',
        'workspace_id' => $this->workspace->id,
    ]);

    $export = [
        'vaxtly_export' => true,
        'version' => 1,
        'type' => 'all',
        'exported_at' => now()->toIso8601String(),
        'data' => [
            'collections' => [
                ['name' => 'My API', 'description' => null, 'variables' => [], 'order' => 1, 'folders' => [], 'requests' => []],
            ],
            'environments' => [
                ['name' => 'Production', 'order' => 1, 'is_active' => false, 'vault_synced' => false, 'vault_path' => null, 'variables' => []],
            ],
        ],
    ];

    $json = json_encode($export);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['collections'])->toBe(1)
        ->and($result['environments'])->toBe(1);

    expect(Collection::where('name', 'My API (2)')->exists())->toBeTrue();
    expect(Environment::where('name', 'Production (2)')->exists())->toBeTrue();
});

it('handles invalid json gracefully', function () {
    $result = $this->service->import('not valid json', $this->workspace->id);

    expect($result['collections'])->toBe(0)
        ->and($result['environments'])->toBe(0)
        ->and($result['config'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

it('rejects non-vaxtly format', function () {
    $json = json_encode(['some' => 'data']);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['errors'])->toContain('Invalid Vaxtly export format');
});

it('rejects unsupported version', function () {
    $json = json_encode([
        'vaxtly_export' => true,
        'version' => 99,
        'type' => 'all',
        'data' => [],
    ]);
    $result = $this->service->import($json, $this->workspace->id);

    expect($result['errors'])->toContain('Unsupported export version: 99');
});

// --- Round-Trip Test ---

it('round-trips export then import into fresh workspace', function () {
    // Create source data
    $collection = Collection::factory()->create([
        'name' => 'Round Trip Collection',
        'description' => 'Testing round trip',
        'workspace_id' => $this->workspace->id,
        'variables' => [['key' => 'api_key', 'value' => 'abc123', 'enabled' => true]],
        'order' => 1,
    ]);

    $folder = Folder::factory()->create([
        'collection_id' => $collection->id,
        'name' => 'Auth',
        'order' => 1,
    ]);

    Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => $folder->id,
        'name' => 'Login',
        'method' => 'POST',
        'url' => 'https://api.example.com/login',
        'headers' => [['key' => 'Content-Type', 'value' => 'application/json', 'enabled' => true]],
        'query_params' => [],
        'body' => '{"email":"test@test.com"}',
        'body_type' => 'json',
        'order' => 1,
    ]);

    Request::factory()->create([
        'collection_id' => $collection->id,
        'folder_id' => null,
        'name' => 'Health',
        'method' => 'GET',
        'url' => 'https://api.example.com/health',
        'order' => 2,
    ]);

    Environment::factory()->withVariables([
        ['key' => 'base_url', 'value' => 'https://api.example.com', 'enabled' => true],
    ])->create([
        'name' => 'Staging',
        'workspace_id' => $this->workspace->id,
        'order' => 1,
    ]);

    // Export
    $exported = $this->service->exportAll($this->workspace->id);
    $json = json_encode($exported);

    // Create a new workspace and import
    $newWorkspace = Workspace::factory()->create(['name' => 'New Workspace']);

    // Clean original data
    Request::query()->delete();
    Folder::query()->delete();
    Collection::query()->delete();
    Environment::query()->delete();

    $result = $this->service->import($json, $newWorkspace->id);

    expect($result['collections'])->toBe(1)
        ->and($result['environments'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    // Verify collection
    $importedCollection = Collection::where('workspace_id', $newWorkspace->id)->first();
    expect($importedCollection->name)->toBe('Round Trip Collection')
        ->and($importedCollection->description)->toBe('Testing round trip')
        ->and($importedCollection->variables)->toHaveCount(1)
        ->and($importedCollection->variables[0]['key'])->toBe('api_key');

    // Verify folder
    $importedFolder = Folder::where('collection_id', $importedCollection->id)->first();
    expect($importedFolder->name)->toBe('Auth');

    // Verify requests
    $folderRequest = Request::where('folder_id', $importedFolder->id)->first();
    expect($folderRequest->name)->toBe('Login')
        ->and($folderRequest->method)->toBe('POST')
        ->and($folderRequest->body)->toBe('{"email":"test@test.com"}');

    $rootRequest = Request::where('collection_id', $importedCollection->id)->whereNull('folder_id')->first();
    expect($rootRequest->name)->toBe('Health');

    // Verify environment
    $importedEnv = Environment::where('workspace_id', $newWorkspace->id)->first();
    expect($importedEnv->name)->toBe('Staging')
        ->and($importedEnv->variables)->toHaveCount(1)
        ->and($importedEnv->variables[0]['key'])->toBe('base_url');
});

// --- Backward Compatibility: Postman imports still work ---

it('postman import still works via importData in livewire component', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Postman Collection',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'Get Users',
                'request' => [
                    'method' => 'GET',
                    'url' => 'https://api.example.com/users',
                ],
            ],
        ],
    ];

    // Verify that PostmanImportService still handles the file correctly
    $json = json_encode($postmanData);
    $tempFile = tempnam(sys_get_temp_dir(), 'postman_test_');
    file_put_contents($tempFile, $json);

    $file = new UploadedFile($tempFile, 'postman.json', 'application/json', null, true);

    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(1)
        ->and($result['requests'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();
});

it('does not include git sync state in collection export', function () {
    Collection::factory()->create([
        'name' => 'Synced Collection',
        'workspace_id' => $this->workspace->id,
        'remote_sha' => 'abc123sha',
        'file_shas' => ['path' => ['content_hash' => 'x', 'remote_sha' => 'y']],
        'is_dirty' => true,
        'sync_enabled' => true,
    ]);

    $result = $this->service->exportCollections($this->workspace->id);
    $col = $result['data']['collections'][0];

    expect($col)->not->toHaveKey('remote_sha')
        ->and($col)->not->toHaveKey('file_shas')
        ->and($col)->not->toHaveKey('is_dirty')
        ->and($col)->not->toHaveKey('sync_enabled');
});
