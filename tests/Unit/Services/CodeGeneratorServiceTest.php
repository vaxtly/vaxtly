<?php

use App\Services\CodeGeneratorService;
use App\Services\VariableSubstitutionService;

beforeEach(function () {
    $this->substitutionService = Mockery::mock(VariableSubstitutionService::class);
    $this->substitutionService->shouldReceive('substitute')
        ->andReturnUsing(fn (string $text) => $text);

    $this->service = new CodeGeneratorService($this->substitutionService);
});

function baseRequestData(array $overrides = []): array
{
    return array_merge([
        'method' => 'GET',
        'url' => 'https://api.example.com/users',
        'headers' => [],
        'queryParams' => [],
        'body' => '',
        'bodyType' => 'json',
        'formData' => [],
        'authType' => 'none',
        'authToken' => '',
        'authUsername' => '',
        'authPassword' => '',
        'apiKeyName' => '',
        'apiKeyValue' => '',
    ], $overrides);
}

describe('generate', function () {
    it('defaults to curl for unknown language', function () {
        $result = $this->service->generate('unknown', baseRequestData());

        expect($result)->toStartWith('curl');
    });
});

describe('curl generation', function () {
    it('generates a simple GET request', function () {
        $result = $this->service->generate('curl', baseRequestData());

        expect($result)
            ->toContain('curl')
            ->toContain("'https://api.example.com/users'")
            ->not->toContain('-X');
    });

    it('generates a POST request with method flag', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'method' => 'POST',
        ]));

        expect($result)->toContain('-X POST');
    });

    it('includes custom headers', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'headers' => [
                ['key' => 'Accept', 'value' => 'application/json'],
            ],
        ]));

        expect($result)->toContain("-H 'Accept: application/json'");
    });

    it('includes JSON body', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'method' => 'POST',
            'body' => '{"name":"test"}',
            'bodyType' => 'json',
        ]));

        expect($result)
            ->toContain("-H 'Content-Type: application/json'")
            ->toContain("-d '{\"name\":\"test\"}'");
    });

    it('includes query params in URL', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'queryParams' => [
                ['key' => 'page', 'value' => '1'],
                ['key' => 'limit', 'value' => '10'],
            ],
        ]));

        expect($result)->toContain('page=1')->toContain('limit=10');
    });

    it('includes bearer auth header', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'authType' => 'bearer',
            'authToken' => 'my-token',
        ]));

        expect($result)->toContain('Authorization: Bearer my-token');
    });

    it('includes basic auth header', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'authType' => 'basic',
            'authUsername' => 'user',
            'authPassword' => 'pass',
        ]));

        $expected = base64_encode('user:pass');
        expect($result)->toContain("Authorization: Basic {$expected}");
    });

    it('includes api-key auth header', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'authType' => 'api-key',
            'apiKeyName' => 'X-Api-Key',
            'apiKeyValue' => 'secret-123',
        ]));

        expect($result)->toContain('X-Api-Key: secret-123');
    });

    it('handles form-data body', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'method' => 'POST',
            'bodyType' => 'form-data',
            'formData' => [
                ['key' => 'name', 'value' => 'John'],
                ['key' => 'email', 'value' => 'john@test.com'],
            ],
        ]));

        expect($result)->toContain("-F 'name=John'")->toContain("-F 'email=john@test.com'");
    });

    it('handles urlencoded body', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'method' => 'POST',
            'bodyType' => 'urlencoded',
            'formData' => [
                ['key' => 'username', 'value' => 'admin'],
            ],
        ]));

        expect($result)->toContain('--data-urlencode');
    });
});

describe('python generation', function () {
    it('generates a simple GET request', function () {
        $result = $this->service->generate('python', baseRequestData());

        expect($result)
            ->toContain('import requests')
            ->toContain('requests.get(')
            ->toContain('print(response.status_code)')
            ->toContain('print(response.json())');
    });

    it('includes headers as a dict', function () {
        $result = $this->service->generate('python', baseRequestData([
            'headers' => [
                ['key' => 'Accept', 'value' => 'application/json'],
            ],
        ]));

        expect($result)
            ->toContain('headers = {')
            ->toContain("'Accept': 'application/json'")
            ->toContain('headers=headers');
    });

    it('includes json payload for POST', function () {
        $result = $this->service->generate('python', baseRequestData([
            'method' => 'POST',
            'body' => '{"name":"test"}',
            'bodyType' => 'json',
        ]));

        expect($result)
            ->toContain('requests.post(')
            ->toContain('json=payload');
    });
});

describe('php generation', function () {
    it('generates a simple GET request', function () {
        $result = $this->service->generate('php', baseRequestData());

        expect($result)
            ->toContain('use Illuminate\\Support\\Facades\\Http;')
            ->toContain('Http::get(')
            ->toContain('$response->status()')
            ->toContain('$response->json()');
    });

    it('includes withHeaders for custom headers', function () {
        $result = $this->service->generate('php', baseRequestData([
            'headers' => [
                ['key' => 'Accept', 'value' => 'application/json'],
            ],
        ]));

        expect($result)->toContain('withHeaders([')->toContain("'Accept' => 'application/json'");
    });

    it('uses asJson for json body type', function () {
        $result = $this->service->generate('php', baseRequestData([
            'method' => 'POST',
            'body' => '{"key":"value"}',
            'bodyType' => 'json',
        ]));

        expect($result)->toContain('asJson()')->toContain('->post(');
    });

    it('uses asForm for urlencoded body type', function () {
        $result = $this->service->generate('php', baseRequestData([
            'method' => 'POST',
            'bodyType' => 'urlencoded',
            'formData' => [
                ['key' => 'field', 'value' => 'val'],
            ],
        ]));

        expect($result)->toContain('asForm()');
    });
});

describe('javascript generation', function () {
    it('generates a simple GET request', function () {
        $result = $this->service->generate('javascript', baseRequestData());

        expect($result)
            ->toContain('await fetch(')
            ->toContain("method: 'GET'")
            ->toContain('await response.json()')
            ->toContain('console.log(response.status, data)');
    });

    it('includes body for POST', function () {
        $result = $this->service->generate('javascript', baseRequestData([
            'method' => 'POST',
            'body' => '{"name":"test"}',
            'bodyType' => 'json',
        ]));

        expect($result)->toContain('body: JSON.stringify(');
    });
});

describe('node generation', function () {
    it('generates a simple GET request', function () {
        $result = $this->service->generate('node', baseRequestData());

        expect($result)
            ->toContain("import axios from 'axios'")
            ->toContain('axios.get(')
            ->toContain('console.log(response.status, response.data)');
    });

    it('includes body and headers for POST', function () {
        $result = $this->service->generate('node', baseRequestData([
            'method' => 'POST',
            'body' => '{"name":"test"}',
            'bodyType' => 'json',
            'headers' => [
                ['key' => 'Content-Type', 'value' => 'application/json'],
            ],
        ]));

        expect($result)
            ->toContain('axios.post(')
            ->toContain("'Content-Type': 'application/json'");
    });
});

describe('variable substitution', function () {
    it('passes values through the substitution service', function () {
        $mockService = Mockery::mock(VariableSubstitutionService::class);
        $mockService->shouldReceive('substitute')
            ->andReturnUsing(fn (string $text) => str_replace('{{baseUrl}}', 'https://resolved.com', $text));

        $service = new CodeGeneratorService($mockService, 'collection-123');

        $result = $service->generate('curl', baseRequestData([
            'url' => '{{baseUrl}}/api/users',
        ]));

        expect($result)->toContain('https://resolved.com/api/users');
    });
});

describe('empty data handling', function () {
    it('skips headers with empty keys', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'headers' => [
                ['key' => '', 'value' => 'ignored'],
                ['key' => 'Valid', 'value' => 'kept'],
            ],
        ]));

        expect($result)->toContain('Valid: kept')->not->toContain('ignored');
    });

    it('skips query params with empty keys', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'queryParams' => [
                ['key' => '', 'value' => 'ignored'],
            ],
        ]));

        expect($result)->not->toContain('?')->not->toContain('ignored');
    });

    it('skips disabled headers', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'headers' => [
                ['key' => 'Active', 'value' => 'yes', 'enabled' => true],
                ['key' => 'Disabled', 'value' => 'no', 'enabled' => false],
            ],
        ]));

        expect($result)->toContain('Active: yes')->not->toContain('Disabled');
    });

    it('skips disabled query params', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'queryParams' => [
                ['key' => 'active', 'value' => '1', 'enabled' => true],
                ['key' => 'disabled', 'value' => '0', 'enabled' => false],
            ],
        ]));

        expect($result)->toContain('active=1')->not->toContain('disabled');
    });

    it('treats headers without enabled key as enabled', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'headers' => [
                ['key' => 'Legacy', 'value' => 'header'],
            ],
        ]));

        expect($result)->toContain('Legacy: header');
    });

    it('returns no body when body is empty', function () {
        $result = $this->service->generate('curl', baseRequestData([
            'method' => 'POST',
            'body' => '',
            'bodyType' => 'json',
        ]));

        expect($result)->not->toContain('-d');
    });
});
