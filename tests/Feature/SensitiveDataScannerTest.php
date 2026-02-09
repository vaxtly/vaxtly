<?php

use App\Models\Collection;
use App\Models\Request;
use App\Services\SensitiveDataScanner;

beforeEach(function () {
    $this->scanner = new SensitiveDataScanner;
});

it('detects plain-text bearer token', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Auth Request',
        'auth' => ['type' => 'bearer', 'token' => 'sk-live-abc123xyz'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('auth')
        ->and($findings[0]['key'])->toBe('bearer token')
        ->and($findings[0]['request_name'])->toBe('Auth Request');
});

it('detects plain-text basic auth password', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Basic Request',
        'auth' => ['type' => 'basic', 'username' => 'user', 'password' => 'supersecret'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('auth')
        ->and($findings[0]['key'])->toBe('basic password');
});

it('detects plain-text api-key value', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'API Key Request',
        'auth' => ['type' => 'api-key', 'api_key_name' => 'X-Api-Key', 'api_key_value' => 'key-12345'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('auth')
        ->and($findings[0]['key'])->toBe('api-key value');
});

it('detects sensitive headers with plain-text values in flat format', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Header Request',
        'headers' => [
            'Authorization' => 'Bearer sk-live-123',
            'Content-Type' => 'application/json',
        ],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('header')
        ->and($findings[0]['key'])->toBe('Authorization');
});

it('detects sensitive headers with plain-text values in structured format', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Structured Header',
        'headers' => [
            ['key' => 'X-Api-Key', 'value' => 'my-secret-key'],
            ['key' => 'Accept', 'value' => 'application/json'],
        ],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('header')
        ->and($findings[0]['key'])->toBe('X-Api-Key');
});

it('detects sensitive query params with plain-text values', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Param Request',
        'query_params' => [
            'api_key' => 'abc123',
            'page' => '1',
        ],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('param')
        ->and($findings[0]['key'])->toBe('api_key');
});

it('detects sensitive query params in structured format', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Structured Param',
        'query_params' => [
            ['key' => 'token', 'value' => 'tok_live_123'],
            ['key' => 'format', 'value' => 'json'],
        ],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('param')
        ->and($findings[0]['key'])->toBe('token');
});

it('detects plain-text collection variable values', function () {
    $collection = Collection::factory()->create([
        'variables' => [
            ['key' => 'api_key', 'value' => 'sk-real-key-123', 'enabled' => true],
            ['key' => 'base_url', 'value' => 'https://api.example.com', 'enabled' => true],
        ],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('variable')
        ->and($findings[0]['key'])->toBe('api_key');
});

it('ignores values with variable references', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'auth' => ['type' => 'bearer', 'token' => '{{auth_token}}'],
        'headers' => ['Authorization' => '{{auth_header}}'],
        'query_params' => ['api_key' => '{{my_key}}'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('ignores non-sensitive header and query param keys', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'text/html',
            'X-Custom-Header' => 'some-value',
        ],
        'query_params' => [
            'page' => '1',
            'per_page' => '25',
            'sort' => 'name',
        ],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('returns empty for clean collection', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'auth' => null,
        'headers' => ['Content-Type' => 'application/json'],
        'query_params' => [],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('returns empty for collection with no requests', function () {
    $collection = Collection::factory()->create();

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('masks values correctly', function () {
    expect($this->scanner->maskValue('sk-live-abc123xyz'))
        ->toBe('sk-l********')
        ->and($this->scanner->maskValue('ab'))
        ->toBe('ab')
        ->and($this->scanner->maskValue('abcde'))
        ->toBe('abcd*');
});

it('identifies variable references correctly', function () {
    expect($this->scanner->isVariableReference('{{token}}'))->toBeTrue()
        ->and($this->scanner->isVariableReference('Bearer {{token}}'))->toBeTrue()
        ->and($this->scanner->isVariableReference('plain-value'))->toBeFalse()
        ->and($this->scanner->isVariableReference(''))->toBeFalse();
});

it('ignores auth type none', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'auth' => ['type' => 'none'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('detects multiple findings across requests', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Request A',
        'auth' => ['type' => 'bearer', 'token' => 'token-abc'],
    ]);
    Request::factory()->for($collection)->create([
        'name' => 'Request B',
        'headers' => ['Authorization' => 'Basic dXNlcjpwYXNz'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(2);
});

it('detects sensitive form-data body fields', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Form Request',
        'body_type' => 'form-data',
        'body' => json_encode([
            ['key' => 'client_id', 'value' => 'my-client-id'],
            ['key' => 'client_secret', 'value' => 'super-secret'],
            ['key' => 'grant_type', 'value' => 'client_credentials'],
        ]),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(2)
        ->and($findings[0]['source'])->toBe('body')
        ->and($findings[0]['key'])->toBe('client_id')
        ->and($findings[1]['key'])->toBe('client_secret');
});

it('detects sensitive urlencoded body fields', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Urlencoded Request',
        'body_type' => 'urlencoded',
        'body' => json_encode([
            ['key' => 'password', 'value' => 'hunter2'],
        ]),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('body')
        ->and($findings[0]['key'])->toBe('password');
});

it('detects sensitive keys in JSON body', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'JSON Request',
        'body_type' => 'json',
        'body' => json_encode([
            'username' => 'admin',
            'password' => 'hunter2',
            'remember' => true,
        ]),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(1)
        ->and($findings[0]['source'])->toBe('body')
        ->and($findings[0]['key'])->toBe('password');
});

it('detects sensitive keys in nested JSON body', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'name' => 'Nested JSON',
        'body_type' => 'json',
        'body' => json_encode([
            'data' => [
                'credentials' => [
                    'client_secret' => 'top-secret',
                    'client_id' => 'my-app',
                ],
            ],
            'name' => 'test',
        ]),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(2);
    $keys = array_column($findings, 'key');
    expect($keys)->toContain('client_secret')
        ->toContain('client_id');
});

it('ignores non-sensitive keys in JSON body', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'body_type' => 'json',
        'body' => json_encode(['name' => 'John', 'email' => 'john@example.com']),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('ignores variable references in JSON body', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'body_type' => 'json',
        'body' => json_encode(['password' => '{{user_password}}']),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('ignores non-JSON raw body', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'body_type' => 'raw',
        'body' => 'password=secret&token=abc123',
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('ignores body fields with variable references', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'body_type' => 'form-data',
        'body' => json_encode([
            ['key' => 'client_secret', 'value' => '{{my_secret}}'],
        ]),
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toBeEmpty();
});

it('is case-insensitive for key matching', function () {
    $collection = Collection::factory()->create();
    Request::factory()->for($collection)->create([
        'headers' => ['AUTHORIZATION' => 'Bearer secret123'],
        'query_params' => ['API_KEY' => 'key123'],
    ]);

    $findings = $this->scanner->scanCollection($collection);

    expect($findings)->toHaveCount(2);
});

// --- scanRequest() tests ---

it('scanRequest returns findings for a single request', function () {
    $collection = Collection::factory()->create();
    $request = Request::factory()->for($collection)->create([
        'name' => 'Test Request',
        'auth' => ['type' => 'bearer', 'token' => 'sk-live-abc123'],
        'headers' => ['X-Api-Key' => 'my-key-value'],
    ]);

    $findings = $this->scanner->scanRequest($request);

    expect($findings)->toHaveCount(2)
        ->and(collect($findings)->pluck('source')->all())->toContain('auth', 'header');
});

// --- sanitizeRequestData() tests ---

it('sanitizeRequestData blanks bearer token', function () {
    $data = [
        'id' => 'test-id',
        'name' => 'Test',
        'auth' => ['type' => 'bearer', 'token' => 'sk-live-secret'],
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['auth']['token'])->toBe('')
        ->and($result['auth']['type'])->toBe('bearer');
});

it('sanitizeRequestData blanks basic auth password', function () {
    $data = [
        'auth' => ['type' => 'basic', 'username' => 'admin', 'password' => 'supersecret'],
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['auth']['password'])->toBe('')
        ->and($result['auth']['username'])->toBe('admin');
});

it('sanitizeRequestData blanks api-key value', function () {
    $data = [
        'auth' => ['type' => 'api-key', 'api_key_name' => 'X-Key', 'api_key_value' => 'secret-val'],
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['auth']['api_key_value'])->toBe('')
        ->and($result['auth']['api_key_name'])->toBe('X-Key');
});

it('sanitizeRequestData blanks sensitive headers in flat format', function () {
    $data = [
        'headers' => [
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json',
        ],
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['headers']['Authorization'])->toBe('')
        ->and($result['headers']['Content-Type'])->toBe('application/json');
});

it('sanitizeRequestData blanks sensitive headers in structured format', function () {
    $data = [
        'headers' => [
            ['key' => 'X-Api-Key', 'value' => 'secret-key'],
            ['key' => 'Accept', 'value' => 'application/json'],
        ],
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['headers'][0]['value'])->toBe('')
        ->and($result['headers'][1]['value'])->toBe('application/json');
});

it('sanitizeRequestData blanks sensitive query params', function () {
    $data = [
        'query_params' => [
            'api_key' => 'my-key',
            'page' => '1',
        ],
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['query_params']['api_key'])->toBe('')
        ->and($result['query_params']['page'])->toBe('1');
});

it('sanitizeRequestData blanks sensitive form-data body fields', function () {
    $data = [
        'body_type' => 'form-data',
        'body' => json_encode([
            ['key' => 'client_secret', 'value' => 'top-secret'],
            ['key' => 'grant_type', 'value' => 'client_credentials'],
        ]),
    ];

    $result = $this->scanner->sanitizeRequestData($data);
    $decoded = json_decode($result['body'], true);

    expect($decoded[0]['value'])->toBe('')
        ->and($decoded[1]['value'])->toBe('client_credentials');
});

it('sanitizeRequestData blanks sensitive JSON body fields', function () {
    $data = [
        'body_type' => 'json',
        'body' => json_encode([
            'username' => 'admin',
            'password' => 'hunter2',
            'remember' => true,
        ]),
    ];

    $result = $this->scanner->sanitizeRequestData($data);
    $decoded = json_decode($result['body'], true);

    expect($decoded['password'])->toBe('')
        ->and($decoded['username'])->toBe('admin');
});

it('sanitizeRequestData blanks nested JSON body fields', function () {
    $data = [
        'body_type' => 'json',
        'body' => json_encode([
            'data' => [
                'credentials' => [
                    'client_secret' => 'top-secret',
                    'client_id' => 'my-app',
                ],
            ],
            'name' => 'test',
        ]),
    ];

    $result = $this->scanner->sanitizeRequestData($data);
    $decoded = json_decode($result['body'], true);

    expect($decoded['data']['credentials']['client_secret'])->toBe('')
        ->and($decoded['data']['credentials']['client_id'])->toBe('')
        ->and($decoded['name'])->toBe('test');
});

it('sanitizeRequestData preserves variable references', function () {
    $data = [
        'auth' => ['type' => 'bearer', 'token' => '{{auth_token}}'],
        'headers' => ['Authorization' => '{{auth_header}}'],
        'query_params' => ['api_key' => '{{my_key}}'],
        'body_type' => 'json',
        'body' => json_encode(['password' => '{{user_password}}']),
    ];

    $result = $this->scanner->sanitizeRequestData($data);

    expect($result['auth']['token'])->toBe('{{auth_token}}')
        ->and($result['headers']['Authorization'])->toBe('{{auth_header}}')
        ->and($result['query_params']['api_key'])->toBe('{{my_key}}');

    $decoded = json_decode($result['body'], true);
    expect($decoded['password'])->toBe('{{user_password}}');
});

// --- sanitizeCollectionData() tests ---

it('sanitizeCollectionData blanks sensitive variable values', function () {
    $data = [
        'id' => 'col-1',
        'name' => 'My Collection',
        'variables' => [
            ['key' => 'api_key', 'value' => 'sk-real-key-123', 'enabled' => true],
            ['key' => 'base_url', 'value' => 'https://api.example.com', 'enabled' => true],
        ],
    ];

    $result = $this->scanner->sanitizeCollectionData($data);

    expect($result['variables'][0]['value'])->toBe('')
        ->and($result['variables'][1]['value'])->toBe('https://api.example.com')
        ->and($result['name'])->toBe('My Collection');
});

it('sanitizeCollectionData preserves variable references', function () {
    $data = [
        'variables' => [
            ['key' => 'api_key', 'value' => '{{env_key}}', 'enabled' => true],
            ['key' => 'token', 'value' => '{{env_token}}', 'enabled' => true],
        ],
    ];

    $result = $this->scanner->sanitizeCollectionData($data);

    expect($result['variables'][0]['value'])->toBe('{{env_key}}')
        ->and($result['variables'][1]['value'])->toBe('{{env_token}}');
});

it('sanitizeCollectionData handles empty variables', function () {
    $data = [
        'id' => 'col-1',
        'variables' => [],
    ];

    $result = $this->scanner->sanitizeCollectionData($data);

    expect($result['variables'])->toBe([]);
});
