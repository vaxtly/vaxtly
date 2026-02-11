<?php

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Request;
use App\Services\ScriptExecutionService;
use App\Services\VariableSubstitutionService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new ScriptExecutionService;
    $this->substitutionService = app(VariableSubstitutionService::class);
});

describe('extractValue', function () {
    it('extracts a body path value', function () {
        $body = json_encode(['access_token' => 'abc123']);

        $result = $this->service->extractValue('body.access_token', 200, $body, []);

        expect($result)->toBe('abc123');
    });

    it('extracts a nested body path value', function () {
        $body = json_encode(['data' => ['token' => 'nested-value']]);

        $result = $this->service->extractValue('body.data.token', 200, $body, []);

        expect($result)->toBe('nested-value');
    });

    it('extracts an array index value', function () {
        $body = json_encode(['items' => [['id' => 1], ['id' => 2]]]);

        $result = $this->service->extractValue('body.items[1].id', 200, $body, []);

        expect($result)->toBe('2');
    });

    it('returns null for missing body path', function () {
        $body = json_encode(['foo' => 'bar']);

        $result = $this->service->extractValue('body.missing.path', 200, $body, []);

        expect($result)->toBeNull();
    });

    it('returns null for non-JSON body', function () {
        $result = $this->service->extractValue('body.key', 200, 'not json', []);

        expect($result)->toBeNull();
    });

    it('extracts a header value case-insensitively', function () {
        $headers = ['X-Request-Id' => ['req-123']];

        $result = $this->service->extractValue('header.x-request-id', 200, null, $headers);

        expect($result)->toBe('req-123');
    });

    it('extracts the status code', function () {
        $result = $this->service->extractValue('status', 201, null, []);

        expect($result)->toBe('201');
    });

    it('returns null for missing header', function () {
        $result = $this->service->extractValue('header.X-Missing', 200, null, []);

        expect($result)->toBeNull();
    });
});

describe('extractJsonPath', function () {
    it('extracts a simple key', function () {
        $result = $this->service->extractJsonPath(['name' => 'John'], 'name');

        expect($result)->toBe('John');
    });

    it('extracts a nested key', function () {
        $result = $this->service->extractJsonPath(['a' => ['b' => ['c' => 'deep']]], 'a.b.c');

        expect($result)->toBe('deep');
    });

    it('extracts with array brackets', function () {
        $result = $this->service->extractJsonPath(['items' => ['first', 'second']], 'items[1]');

        expect($result)->toBe('second');
    });

    it('handles mixed dot and bracket notation', function () {
        $data = ['data' => ['users' => [['name' => 'Alice'], ['name' => 'Bob']]]];

        $result = $this->service->extractJsonPath($data, 'data.users[1].name');

        expect($result)->toBe('Bob');
    });

    it('returns null for invalid path', function () {
        $result = $this->service->extractJsonPath(['foo' => 'bar'], 'baz.qux');

        expect($result)->toBeNull();
    });

    it('returns JSON for array values', function () {
        $data = ['items' => [1, 2, 3]];

        $result = $this->service->extractJsonPath($data, 'items');

        expect($result)->toBe('[1,2,3]');
    });
});

describe('setCollectionVariable', function () {
    it('adds a new variable', function () {
        $collection = Collection::factory()->create(['variables' => []]);

        $this->service->setCollectionVariable($collection->id, 'token', 'abc123');

        $collection->refresh();
        expect($collection->variables)->toHaveCount(1)
            ->and($collection->variables[0])->toMatchArray([
                'key' => 'token',
                'value' => 'abc123',
                'enabled' => true,
            ]);
    });

    it('updates an existing variable', function () {
        $collection = Collection::factory()->create([
            'variables' => [
                ['key' => 'token', 'value' => 'old-value', 'enabled' => true],
            ],
        ]);

        $this->service->setCollectionVariable($collection->id, 'token', 'new-value');

        $collection->refresh();
        expect($collection->variables)->toHaveCount(1)
            ->and($collection->variables[0]['value'])->toBe('new-value');
    });

    it('preserves other variables when updating', function () {
        $collection = Collection::factory()->create([
            'variables' => [
                ['key' => 'baseUrl', 'value' => 'http://api.test', 'enabled' => true],
                ['key' => 'token', 'value' => 'old', 'enabled' => true],
            ],
        ]);

        $this->service->setCollectionVariable($collection->id, 'token', 'new');

        $collection->refresh();
        expect($collection->variables)->toHaveCount(2)
            ->and($collection->variables[0]['value'])->toBe('http://api.test')
            ->and($collection->variables[1]['value'])->toBe('new');
    });
});

describe('executePreRequestScripts', function () {
    it('is a no-op when scripts are empty', function () {
        $request = Request::factory()->create(['scripts' => null]);

        $result = $this->service->executePreRequestScripts($request, $this->substitutionService);

        expect($result)->toBeTrue();
    });

    it('throws on circular dependency', function () {
        $collection = Collection::factory()->create();
        $requestA = Request::factory()->for($collection)->create();
        $requestB = Request::factory()->for($collection)->withPreRequestScript($requestA->id)->create();
        $requestA->update([
            'scripts' => [
                'pre_request' => [['action' => 'send_request', 'request_id' => $requestB->id]],
            ],
        ]);

        Http::fake(['*' => Http::response('{}', 200)]);

        expect(fn () => $this->service->executePreRequestScripts($requestA, $this->substitutionService))
            ->toThrow(RuntimeException::class, 'Circular dependency');
    });

    it('throws when max depth is exceeded', function () {
        $collection = Collection::factory()->create();

        // Create a chain of 5 requests: 1 → 2 → 3 → 4 → 5 (exceeds max depth of 3)
        $request5 = Request::factory()->for($collection)->create(['url' => 'https://api.test/5']);
        $request4 = Request::factory()->for($collection)->withPreRequestScript($request5->id)->create(['url' => 'https://api.test/4']);
        $request3 = Request::factory()->for($collection)->withPreRequestScript($request4->id)->create(['url' => 'https://api.test/3']);
        $request2 = Request::factory()->for($collection)->withPreRequestScript($request3->id)->create(['url' => 'https://api.test/2']);
        $request1 = Request::factory()->for($collection)->withPreRequestScript($request2->id)->create(['url' => 'https://api.test/1']);

        Http::fake(['*' => Http::response('{}', 200)]);

        expect(fn () => $this->service->executePreRequestScripts($request1, $this->substitutionService))
            ->toThrow(RuntimeException::class, 'Maximum script chain depth');
    });

    it('throws when dependent request is not found', function () {
        $collection = Collection::factory()->create();
        $request = Request::factory()->for($collection)->withPreRequestScript('non-existent-uuid')->create();

        expect(fn () => $this->service->executePreRequestScripts($request, $this->substitutionService))
            ->toThrow(RuntimeException::class, 'not found');
    });
});

describe('executePostResponseScripts', function () {
    it('is a no-op when scripts are empty', function () {
        $request = Request::factory()->create(['scripts' => null]);

        // Should not throw
        $this->service->executePostResponseScripts($request, 200, '{}', []);

        expect(true)->toBeTrue();
    });

    it('sets a variable from body', function () {
        $collection = Collection::factory()->create(['variables' => []]);
        $request = Request::factory()
            ->for($collection)
            ->withPostResponseScript('body.access_token', 'token')
            ->create();

        $body = json_encode(['access_token' => 'my-token-123']);

        $this->service->executePostResponseScripts($request, 200, $body, []);

        $collection->refresh();
        expect($collection->getEnabledVariables())->toHaveKey('token', 'my-token-123');
    });

    it('sets multiple variables', function () {
        $collection = Collection::factory()->create(['variables' => []]);
        $request = Request::factory()->for($collection)->create([
            'scripts' => [
                'post_response' => [
                    ['action' => 'set_variable', 'source' => 'body.token', 'target' => 'authToken', 'scope' => 'collection'],
                    ['action' => 'set_variable', 'source' => 'header.X-Request-Id', 'target' => 'reqId', 'scope' => 'collection'],
                ],
            ],
        ]);

        $body = json_encode(['token' => 'tok-abc']);
        $headers = ['X-Request-Id' => ['rid-123']];

        $this->service->executePostResponseScripts($request, 200, $body, $headers);

        $collection->refresh();
        $vars = $collection->getEnabledVariables();
        expect($vars)->toHaveKey('authToken', 'tok-abc')
            ->and($vars)->toHaveKey('reqId', 'rid-123');
    });

    it('skips missing path without error', function () {
        $collection = Collection::factory()->create(['variables' => []]);
        $request = Request::factory()
            ->for($collection)
            ->withPostResponseScript('body.nonexistent', 'var')
            ->create();

        $this->service->executePostResponseScripts($request, 200, '{}', []);

        $collection->refresh();
        expect($collection->variables)->toBeEmpty();
    });
});

describe('mirrorToActiveEnvironment', function () {
    it('updates matching variable in active environment', function () {
        $collection = Collection::factory()->create(['variables' => []]);
        $environment = Environment::factory()->create([
            'workspace_id' => $collection->workspace_id,
            'is_active' => true,
            'variables' => [
                ['key' => 'token', 'value' => 'old-env-value', 'enabled' => true],
            ],
        ]);

        $request = Request::factory()
            ->for($collection)
            ->withPostResponseScript('body.access_token', 'token')
            ->create();

        $body = json_encode(['access_token' => 'new-token']);
        $this->service->executePostResponseScripts($request, 200, $body, []);

        $environment->refresh();
        expect($environment->variables[0]['value'])->toBe('new-token');
    });

    it('does not add new variable to environment', function () {
        $collection = Collection::factory()->create(['variables' => []]);
        $environment = Environment::factory()->create([
            'workspace_id' => $collection->workspace_id,
            'is_active' => true,
            'variables' => [
                ['key' => 'other_var', 'value' => 'unchanged', 'enabled' => true],
            ],
        ]);

        $request = Request::factory()
            ->for($collection)
            ->withPostResponseScript('body.token', 'token')
            ->create();

        $body = json_encode(['token' => 'new-value']);
        $this->service->executePostResponseScripts($request, 200, $body, []);

        $environment->refresh();
        expect($environment->variables)->toHaveCount(1)
            ->and($environment->variables[0]['key'])->toBe('other_var')
            ->and($environment->variables[0]['value'])->toBe('unchanged');
    });

    it('ignores inactive environments', function () {
        $collection = Collection::factory()->create(['variables' => []]);
        $environment = Environment::factory()->create([
            'workspace_id' => $collection->workspace_id,
            'is_active' => false,
            'variables' => [
                ['key' => 'token', 'value' => 'should-not-change', 'enabled' => true],
            ],
        ]);

        $request = Request::factory()
            ->for($collection)
            ->withPostResponseScript('body.token', 'token')
            ->create();

        $body = json_encode(['token' => 'new-value']);
        $this->service->executePostResponseScripts($request, 200, $body, []);

        $environment->refresh();
        expect($environment->variables[0]['value'])->toBe('should-not-change');
    });
});

describe('integration', function () {
    it('executes full chain: pre-request runs token endpoint, post-response extracts token', function () {
        $collection = Collection::factory()->create(['variables' => []]);

        // Token request: returns access_token in body
        $tokenRequest = Request::factory()->for($collection)->create([
            'name' => 'Get Token',
            'method' => 'POST',
            'url' => 'https://auth.test/token',
            'scripts' => [
                'post_response' => [
                    ['action' => 'set_variable', 'source' => 'body.access_token', 'target' => 'token', 'scope' => 'collection'],
                ],
            ],
        ]);

        // API request: runs token request first
        $apiRequest = Request::factory()->for($collection)->create([
            'name' => 'Create Entry',
            'method' => 'POST',
            'url' => 'https://api.test/entries',
            'scripts' => [
                'pre_request' => [
                    ['action' => 'send_request', 'request_id' => $tokenRequest->id],
                ],
            ],
        ]);

        Http::fake([
            'https://auth.test/token' => Http::response(['access_token' => 'test-jwt-token'], 200),
            'https://api.test/entries' => Http::response(['id' => 1], 201),
        ]);

        // Execute pre-request scripts for the API request
        $this->service->executePreRequestScripts($apiRequest, $this->substitutionService);

        // Token should now be set as a collection variable
        $collection->refresh();
        expect($collection->getEnabledVariables())->toHaveKey('token', 'test-jwt-token');

        // Verify the token request was actually called
        Http::assertSent(fn ($req) => $req->url() === 'https://auth.test/token');
    });
});

describe('Request model helpers', function () {
    it('returns pre-request scripts', function () {
        $request = Request::factory()->create([
            'scripts' => [
                'pre_request' => [['action' => 'send_request', 'request_id' => 'some-id']],
            ],
        ]);

        expect($request->getPreRequestScripts())->toHaveCount(1);
    });

    it('returns post-response scripts', function () {
        $request = Request::factory()->withPostResponseScript('body.token', 'myVar')->create();

        expect($request->getPostResponseScripts())->toHaveCount(1);
    });

    it('returns empty arrays for null scripts', function () {
        $request = Request::factory()->create(['scripts' => null]);

        expect($request->getPreRequestScripts())->toBeEmpty()
            ->and($request->getPostResponseScripts())->toBeEmpty();
    });

    it('hasScripts returns true when scripts exist', function () {
        $request = Request::factory()->withPostResponseScript('body.token', 'myVar')->create();

        expect($request->hasScripts())->toBeTrue();
    });

    it('hasScripts returns false when no scripts', function () {
        $request = Request::factory()->create(['scripts' => null]);

        expect($request->hasScripts())->toBeFalse();
    });
});
