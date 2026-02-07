<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use App\Models\Workspace;
use App\Services\PostmanImportService;
use App\Services\WorkspaceService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    // Clean up before each test
    Request::query()->delete();
    Folder::query()->delete();
    Collection::query()->delete();
    Environment::query()->delete();

    // Ensure a workspace exists for the import service
    $workspace = Workspace::factory()->create(['name' => 'Test']);
    set_setting('active.workspace', $workspace->id);
    app(WorkspaceService::class)->clearCache();
});

it('imports a simple postman collection', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Test Collection',
            'description' => 'A test collection',
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

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(1)
        ->and($result['requests'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $collection = Collection::first();
    expect($collection->name)->toBe('Test Collection')
        ->and($collection->description)->toBe('A test collection');

    $request = Request::first();
    expect($request->name)->toBe('Get Users')
        ->and($request->method)->toBe('GET')
        ->and($request->url)->toBe('https://api.example.com/users');
});

it('imports a collection with folders', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Collection With Folders',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'Users',
                'item' => [
                    [
                        'name' => 'List Users',
                        'request' => [
                            'method' => 'GET',
                            'url' => 'https://api.example.com/users',
                        ],
                    ],
                    [
                        'name' => 'Create User',
                        'request' => [
                            'method' => 'POST',
                            'url' => 'https://api.example.com/users',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Root Request',
                'request' => [
                    'method' => 'GET',
                    'url' => 'https://api.example.com/health',
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(1)
        ->and($result['folders'])->toBe(1)
        ->and($result['requests'])->toBe(3)
        ->and($result['errors'])->toBeEmpty();

    $collection = Collection::first();
    $folder = Folder::first();

    expect($folder->name)->toBe('Users')
        ->and($folder->collection_id)->toBe($collection->id)
        ->and($folder->parent_id)->toBeNull();

    // Check requests in folder
    $folderRequests = Request::where('folder_id', $folder->id)->get();
    expect($folderRequests)->toHaveCount(2);

    // Check root request
    $rootRequest = Request::whereNull('folder_id')->first();
    expect($rootRequest->name)->toBe('Root Request');
});

it('imports nested folders', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Nested Folders Collection',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'API',
                'item' => [
                    [
                        'name' => 'v1',
                        'item' => [
                            [
                                'name' => 'Get Data',
                                'request' => [
                                    'method' => 'GET',
                                    'url' => 'https://api.example.com/v1/data',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['folders'])->toBe(2)
        ->and($result['requests'])->toBe(1);

    $parentFolder = Folder::whereNull('parent_id')->first();
    $childFolder = Folder::whereNotNull('parent_id')->first();

    expect($parentFolder->name)->toBe('API')
        ->and($childFolder->name)->toBe('v1')
        ->and($childFolder->parent_id)->toBe($parentFolder->id);

    $request = Request::first();
    expect($request->folder_id)->toBe($childFolder->id);
});

it('imports collection variables', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Collection With Variables',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'variable' => [
            ['key' => 'base_url', 'value' => 'https://api.example.com'],
            ['key' => 'api_key', 'value' => 'secret123', 'disabled' => true],
        ],
        'item' => [],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $collection = Collection::first();
    expect($collection->variables)->toHaveCount(2)
        ->and($collection->variables[0]['key'])->toBe('base_url')
        ->and($collection->variables[0]['enabled'])->toBeTrue()
        ->and($collection->variables[1]['enabled'])->toBeFalse();
});

it('imports request with headers and body', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Request Details',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'Create Item',
                'request' => [
                    'method' => 'POST',
                    'url' => [
                        'raw' => 'https://api.example.com/items?active=true',
                        'protocol' => 'https',
                        'host' => ['api', 'example', 'com'],
                        'path' => ['items'],
                        'query' => [
                            ['key' => 'active', 'value' => 'true'],
                        ],
                    ],
                    'header' => [
                        ['key' => 'Content-Type', 'value' => 'application/json'],
                        ['key' => 'Authorization', 'value' => 'Bearer token', 'disabled' => true],
                    ],
                    'body' => [
                        'mode' => 'raw',
                        'raw' => '{"name": "Test Item"}',
                    ],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $request = Request::first();

    expect($request->method)->toBe('POST')
        ->and($request->headers)->toHaveCount(2)
        ->and($request->headers[0]['key'])->toBe('Content-Type')
        ->and($request->query_params)->toHaveCount(1)
        ->and($request->query_params[0]['key'])->toBe('active')
        ->and($request->body)->toBe('{"name": "Test Item"}')
        ->and($request->body_type)->toBe('json');
});

it('imports postman environment', function () {
    $environmentData = [
        '_postman_variable_scope' => 'environment',
        'name' => 'Production',
        'values' => [
            ['key' => 'base_url', 'value' => 'https://prod.example.com', 'enabled' => true],
            ['key' => 'api_key', 'value' => 'prod-key', 'enabled' => false],
        ],
    ];

    $file = createJsonUploadedFile($environmentData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['environments'])->toBe(1);

    $environment = Environment::first();
    expect($environment->name)->toBe('Production')
        ->and($environment->variables)->toHaveCount(2)
        ->and($environment->is_active)->toBeFalse();
});

it('generates unique names for duplicate collections', function () {
    Collection::create([
        'name' => 'My Collection',
        'order' => 1,
    ]);

    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'My Collection',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $service->import($file);

    expect(Collection::count())->toBe(2);

    $names = Collection::pluck('name')->toArray();
    expect($names)->toContain('My Collection')
        ->and($names)->toContain('My Collection (2)');
});

it('handles invalid json gracefully', function () {
    $file = UploadedFile::fake()->createWithContent('invalid.json', 'not valid json');

    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(0)
        ->and($result['errors'])->not->toBeEmpty();
});

it('handles unknown postman format gracefully', function () {
    $unknownData = [
        'unknown_key' => 'unknown_value',
    ];

    $file = createJsonUploadedFile($unknownData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(0)
        ->and($result['errors'])->toContain('Unknown Postman format');
});

it('imports a workspace data dump with collections and environments', function () {
    $dumpData = [
        'version' => 1,
        'collections' => [
            [
                'id' => 'col-1',
                'name' => 'API Collection',
                'description' => 'My API',
                'variables' => [],
                'folders' => [
                    [
                        'id' => 'folder-1',
                        'name' => 'Users',
                        'folder' => null,
                        'order' => ['req-1', 'req-2'],
                    ],
                ],
                'requests' => [
                    [
                        'id' => 'req-1',
                        'name' => 'List Users',
                        'url' => '{{url}}/api/users',
                        'method' => 'GET',
                        'folder' => 'folder-1',
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => null,
                    ],
                    [
                        'id' => 'req-2',
                        'name' => 'Create User',
                        'url' => '{{url}}/api/users',
                        'method' => 'POST',
                        'folder' => 'folder-1',
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => 'raw',
                        'rawModeData' => '{"name":"Test"}',
                    ],
                    [
                        'id' => 'req-3',
                        'name' => 'Health Check',
                        'url' => '{{url}}/api/health',
                        'method' => 'GET',
                        'folder' => null,
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => null,
                    ],
                ],
            ],
        ],
        'environments' => [
            [
                'id' => 'env-1',
                'name' => 'Local',
                'values' => [
                    ['key' => 'url', 'value' => 'http://localhost:3000', 'enabled' => true],
                    ['key' => 'token', 'value' => '', 'enabled' => true],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($dumpData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(1)
        ->and($result['folders'])->toBe(1)
        ->and($result['requests'])->toBe(3)
        ->and($result['environments'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $collection = Collection::first();
    expect($collection->name)->toBe('API Collection')
        ->and($collection->description)->toBe('My API');

    $folder = Folder::first();
    expect($folder->name)->toBe('Users')
        ->and($folder->collection_id)->toBe($collection->id);

    // Requests in folder
    expect(Request::where('folder_id', $folder->id)->count())->toBe(2);

    // Root request
    $rootRequest = Request::whereNull('folder_id')->first();
    expect($rootRequest->name)->toBe('Health Check');

    // Environment
    $env = Environment::first();
    expect($env->name)->toBe('Local')
        ->and($env->variables)->toHaveCount(2)
        ->and($env->variables[0]['key'])->toBe('url');
});

it('imports dump format with headers and body types', function () {
    $dumpData = [
        'version' => 1,
        'collections' => [
            [
                'id' => 'col-1',
                'name' => 'Headers Test',
                'variables' => [],
                'folders' => [],
                'requests' => [
                    [
                        'id' => 'req-1',
                        'name' => 'Auth Request',
                        'url' => '{{url}}/api/login',
                        'method' => 'POST',
                        'folder' => null,
                        'headerData' => [
                            ['key' => 'Authorization', 'value' => 'Bearer abc123', 'type' => 'default'],
                            ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'default'],
                        ],
                        'queryParams' => [],
                        'dataMode' => 'raw',
                        'rawModeData' => '{"email":"test@test.com","password":"123456"}',
                        'headers' => "Authorization: Bearer abc123\nContent-Type: application/json\n",
                    ],
                ],
            ],
        ],
        'environments' => [],
    ];

    $file = createJsonUploadedFile($dumpData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $request = Request::first();
    expect($request->headers)->toHaveCount(2)
        ->and($request->headers[0]['key'])->toBe('Authorization')
        ->and($request->body)->toBe('{"email":"test@test.com","password":"123456"}')
        ->and($request->body_type)->toBe('json');
});

it('imports dump format with multiple collections', function () {
    $dumpData = [
        'version' => 1,
        'collections' => [
            [
                'id' => 'col-1',
                'name' => 'Collection A',
                'variables' => [],
                'folders' => [],
                'requests' => [
                    [
                        'id' => 'req-1',
                        'name' => 'Request A',
                        'url' => 'https://a.com/api',
                        'method' => 'GET',
                        'folder' => null,
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => null,
                    ],
                ],
            ],
            [
                'id' => 'col-2',
                'name' => 'Collection B',
                'variables' => [],
                'folders' => [],
                'requests' => [
                    [
                        'id' => 'req-2',
                        'name' => 'Request B',
                        'url' => 'https://b.com/api',
                        'method' => 'POST',
                        'folder' => null,
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => null,
                    ],
                ],
            ],
        ],
        'environments' => [
            [
                'id' => 'env-1',
                'name' => 'Env 1',
                'values' => [['key' => 'host', 'value' => 'localhost', 'enabled' => true]],
            ],
            [
                'id' => 'env-2',
                'name' => 'Env 2',
                'values' => [['key' => 'host', 'value' => 'production', 'enabled' => true]],
            ],
        ],
    ];

    $file = createJsonUploadedFile($dumpData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['collections'])->toBe(2)
        ->and($result['requests'])->toBe(2)
        ->and($result['environments'])->toBe(2)
        ->and($result['errors'])->toBeEmpty();

    expect(Collection::pluck('name')->toArray())
        ->toContain('Collection A')
        ->toContain('Collection B');
});

it('imports dump format with nested folders', function () {
    $dumpData = [
        'version' => 1,
        'collections' => [
            [
                'id' => 'col-1',
                'name' => 'Nested Folders',
                'variables' => [],
                'folders' => [
                    [
                        'id' => 'parent-folder',
                        'name' => 'API',
                        'folder' => null,
                        'order' => [],
                    ],
                    [
                        'id' => 'child-folder',
                        'name' => 'v1',
                        'folder' => 'parent-folder',
                        'order' => ['req-1'],
                    ],
                ],
                'requests' => [
                    [
                        'id' => 'req-1',
                        'name' => 'Get Data',
                        'url' => '{{url}}/api/v1/data',
                        'method' => 'GET',
                        'folder' => 'child-folder',
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => null,
                    ],
                ],
            ],
        ],
        'environments' => [],
    ];

    $file = createJsonUploadedFile($dumpData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['folders'])->toBe(2)
        ->and($result['requests'])->toBe(1);

    $parentFolder = Folder::whereNull('parent_id')->first();
    $childFolder = Folder::whereNotNull('parent_id')->first();

    expect($parentFolder->name)->toBe('API')
        ->and($childFolder->name)->toBe('v1')
        ->and($childFolder->parent_id)->toBe($parentFolder->id);

    $request = Request::first();
    expect($request->folder_id)->toBe($childFolder->id);
});

it('stringifies object values instead of storing [object Object]', function () {
    // v2.1 format with object values where strings are expected
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Object Values Collection',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'variable' => [
            ['key' => 'config', 'value' => ['nested' => 'object']],
        ],
        'item' => [
            [
                'name' => 'Request with objects',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/test',
                    'header' => [
                        ['key' => 'X-Custom', 'value' => ['type' => 'bearer', 'token' => 'abc']],
                    ],
                    'body' => [
                        'mode' => 'raw',
                        'raw' => ['key' => 'value'],
                    ],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['errors'])->toBeEmpty();

    $request = Request::first();
    expect($request->headers[0]['value'])->toBe('{"type":"bearer","token":"abc"}')
        ->and($request->headers[0]['value'])->not->toBe('[object Object]')
        ->and($request->body)->toBe('{"key":"value"}');

    $collection = Collection::first();
    expect($collection->variables[0]['value'])->toBe('{"nested":"object"}');
});

it('stringifies object values in dump format', function () {
    $dumpData = [
        'version' => 1,
        'collections' => [
            [
                'id' => 'col-1',
                'name' => 'Dump Objects',
                'variables' => [],
                'folders' => [],
                'requests' => [
                    [
                        'id' => 'req-1',
                        'name' => 'Object URL Request',
                        'url' => ['raw' => 'https://api.example.com/test', 'host' => ['api', 'example', 'com']],
                        'method' => 'POST',
                        'folder' => null,
                        'headerData' => [
                            ['key' => 'Auth', 'value' => ['scheme' => 'Bearer'], 'type' => 'default'],
                        ],
                        'queryParams' => [
                            ['key' => 'filter', 'value' => ['status' => 'active']],
                        ],
                        'dataMode' => 'raw',
                        'rawModeData' => ['email' => 'test@test.com'],
                    ],
                ],
            ],
        ],
        'environments' => [
            [
                'id' => 'env-1',
                'name' => 'Env Objects',
                'values' => [
                    ['key' => 'config', 'value' => ['host' => 'localhost'], 'enabled' => true],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($dumpData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    expect($result['errors'])->toBeEmpty();

    $request = Request::first();
    // URL should be JSON-encoded, not [object Object]
    expect($request->url)->toBeString()
        ->and($request->url)->not->toContain('[object Object]')
        ->and($request->headers[0]['value'])->toBe('{"scheme":"Bearer"}')
        ->and($request->query_params[0]['value'])->toBe('{"status":"active"}')
        ->and($request->body)->toBe('{"email":"test@test.com"}');

    $env = Environment::first();
    expect($env->variables[0]['value'])->toBe('{"host":"localhost"}');
});

it('imports urlencoded body as key-value pairs', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Urlencoded Test',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'Login',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/login',
                    'body' => [
                        'mode' => 'urlencoded',
                        'urlencoded' => [
                            ['key' => 'username', 'value' => 'admin'],
                            ['key' => 'password', 'value' => 'secret'],
                            ['key' => 'disabled_field', 'value' => 'skip', 'disabled' => true],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $request = Request::first();

    expect($request->body_type)->toBe('urlencoded');

    $body = json_decode($request->body, true);
    expect($body)->toBeArray()
        ->toHaveCount(2)
        ->and($body[0])->toBe(['key' => 'username', 'value' => 'admin'])
        ->and($body[1])->toBe(['key' => 'password', 'value' => 'secret']);
});

it('imports formdata body as key-value pairs', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Formdata Test',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'Upload',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/upload',
                    'body' => [
                        'mode' => 'formdata',
                        'formdata' => [
                            ['key' => 'title', 'value' => 'My File', 'type' => 'text'],
                            ['key' => 'file', 'src' => '/path/to/file', 'type' => 'file'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $request = Request::first();

    expect($request->body_type)->toBe('form-data');

    $body = json_decode($request->body, true);
    expect($body)->toBeArray()
        ->toHaveCount(1)
        ->and($body[0])->toBe(['key' => 'title', 'value' => 'My File']);
});

it('imports dump format urlencoded body as key-value pairs', function () {
    $dumpData = [
        'version' => 1,
        'collections' => [
            [
                'id' => 'col-1',
                'name' => 'Dump Urlencoded',
                'variables' => [],
                'folders' => [],
                'requests' => [
                    [
                        'id' => 'req-1',
                        'name' => 'Login',
                        'url' => '{{url}}/login',
                        'method' => 'POST',
                        'folder' => null,
                        'headerData' => [],
                        'queryParams' => [],
                        'dataMode' => 'urlencoded',
                        'data' => [
                            ['key' => 'email', 'value' => 'test@example.com'],
                            ['key' => 'password', 'value' => '123456'],
                        ],
                    ],
                ],
            ],
        ],
        'environments' => [],
    ];

    $file = createJsonUploadedFile($dumpData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $request = Request::first();

    expect($request->body_type)->toBe('urlencoded');

    $body = json_decode($request->body, true);
    expect($body)->toBeArray()
        ->toHaveCount(2)
        ->and($body[0])->toBe(['key' => 'email', 'value' => 'test@example.com'])
        ->and($body[1])->toBe(['key' => 'password', 'value' => '123456']);
});

it('maps raw body type based on postman language option', function () {
    $postmanData = [
        'info' => [
            '_postman_id' => 'test-id',
            'name' => 'Raw Language Test',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'item' => [
            [
                'name' => 'JSON Request',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/json',
                    'body' => [
                        'mode' => 'raw',
                        'raw' => '{"key": "value"}',
                        'options' => ['raw' => ['language' => 'json']],
                    ],
                ],
            ],
            [
                'name' => 'XML Request',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/xml',
                    'body' => [
                        'mode' => 'raw',
                        'raw' => '<root><key>value</key></root>',
                        'options' => ['raw' => ['language' => 'xml']],
                    ],
                ],
            ],
            [
                'name' => 'Text Request',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/text',
                    'body' => [
                        'mode' => 'raw',
                        'raw' => 'plain text body',
                        'options' => ['raw' => ['language' => 'text']],
                    ],
                ],
            ],
            [
                'name' => 'No Language Request',
                'request' => [
                    'method' => 'POST',
                    'url' => 'https://api.example.com/default',
                    'body' => [
                        'mode' => 'raw',
                        'raw' => '{"default": true}',
                    ],
                ],
            ],
        ],
    ];

    $file = createJsonUploadedFile($postmanData);
    $service = new PostmanImportService;
    $result = $service->import($file);

    $requests = Request::orderBy('id')->get();

    expect($requests[0]->body_type)->toBe('json')
        ->and($requests[0]->body)->toBe('{"key": "value"}')
        ->and($requests[1]->body_type)->toBe('raw')
        ->and($requests[1]->body)->toBe('<root><key>value</key></root>')
        ->and($requests[2]->body_type)->toBe('raw')
        ->and($requests[2]->body)->toBe('plain text body')
        ->and($requests[3]->body_type)->toBe('json')
        ->and($requests[3]->body)->toBe('{"default": true}');
});

/**
 * Helper function to create a JSON uploaded file.
 */
function createJsonUploadedFile(array $data): UploadedFile
{
    $json = json_encode($data);
    $tempFile = tempnam(sys_get_temp_dir(), 'postman_test_');
    file_put_contents($tempFile, $json);

    return new UploadedFile(
        $tempFile,
        'postman_export.json',
        'application/json',
        null,
        true
    );
}
