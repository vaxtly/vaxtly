<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Request;
use Illuminate\Database\Seeder;

class ScriptDemoSeeder extends Seeder
{
    /**
     * Seed a demo collection showcasing request scripts.
     *
     * Flow:
     *  1. "Login" (POST) sends credentials, post-response extracts json.access_token -> {{token}}
     *  2. "Get Protected Resource" (GET) has pre-request to run "Login" first, uses {{token}} as Bearer auth
     *  3. "Echo Headers" (GET) standalone — post-response extracts a header into a variable
     */
    public function run(): void
    {
        $collection = Collection::create([
            'name' => 'Scripts Demo (httpbin.org)',
            'description' => 'Demonstrates pre-request and post-response scripts using httpbin.org',
            'variables' => [],
            'order' => Collection::max('order') + 1,
        ]);

        // 1) Login request — simulates a token endpoint
        //    httpbin.org/post echoes back whatever JSON we send,
        //    so we send {"access_token":"...", "expires_in":3600} and extract it.
        $loginRequest = Request::create([
            'collection_id' => $collection->id,
            'name' => 'Login (Get Token)',
            'method' => 'POST',
            'url' => 'https://httpbin.org/post',
            'headers' => ['Content-Type' => 'application/json'],
            'query_params' => [],
            'body' => json_encode([
                'access_token' => 'demo-jwt-token-12345',
                'expires_in' => 3600,
            ], JSON_PRETTY_PRINT),
            'body_type' => 'json',
            'order' => 0,
            'scripts' => [
                // After this request completes, extract the token from the echoed response.
                // httpbin.org returns our JSON under the "json" key: { "json": { "access_token": "..." } }
                'post_response' => [
                    [
                        'action' => 'set_variable',
                        'source' => 'body.json.access_token',
                        'target' => 'token',
                        'scope' => 'collection',
                    ],
                    [
                        'action' => 'set_variable',
                        'source' => 'body.json.expires_in',
                        'target' => 'tokenExpiry',
                        'scope' => 'collection',
                    ],
                ],
            ],
        ]);

        // 2) Protected resource — runs Login first, then uses {{token}} as Bearer
        //    httpbin.org/bearer checks for a valid Authorization: Bearer header.
        Request::create([
            'collection_id' => $collection->id,
            'name' => 'Get Protected Resource',
            'method' => 'GET',
            'url' => 'https://httpbin.org/bearer',
            'headers' => [],
            'query_params' => [],
            'body' => null,
            'body_type' => 'none',
            'order' => 1,
            'auth' => [
                'type' => 'bearer',
                'token' => '{{token}}',
            ],
            'scripts' => [
                // Before sending, execute the Login request to ensure {{token}} is set.
                'pre_request' => [
                    ['action' => 'send_request', 'request_id' => $loginRequest->id],
                ],
            ],
        ]);

        // 3) Echo Headers — standalone example of extracting a response header
        //    httpbin.org/response-headers lets you set custom response headers via query params.
        Request::create([
            'collection_id' => $collection->id,
            'name' => 'Echo Headers',
            'method' => 'GET',
            'url' => 'https://httpbin.org/response-headers',
            'headers' => [],
            'query_params' => ['X-Custom-Id' => 'abc-123', 'X-Rate-Limit' => '100'],
            'body' => null,
            'body_type' => 'none',
            'order' => 2,
            'scripts' => [
                'post_response' => [
                    [
                        'action' => 'set_variable',
                        'source' => 'header.X-Custom-Id',
                        'target' => 'customId',
                        'scope' => 'collection',
                    ],
                    [
                        'action' => 'set_variable',
                        'source' => 'status',
                        'target' => 'lastStatus',
                        'scope' => 'collection',
                    ],
                ],
            ],
        ]);

        $this->command->info('Scripts Demo collection created with 3 requests.');
        $this->command->info('');
        $this->command->info('How to test:');
        $this->command->info('  1. Open "Login (Get Token)" and click Send.');
        $this->command->info('     -> Post-response sets {{token}} and {{tokenExpiry}} from the echoed JSON.');
        $this->command->info('');
        $this->command->info('  2. Open "Get Protected Resource" and click Send.');
        $this->command->info('     -> Pre-request auto-runs "Login" first, then {{token}} is used as Bearer auth.');
        $this->command->info('     -> Auth is pre-configured: Bearer {{token}}');
        $this->command->info('');
        $this->command->info('  3. Open "Echo Headers" and click Send.');
        $this->command->info('     -> Post-response extracts the X-Custom-Id header into {{customId}}');
        $this->command->info('        and the status code into {{lastStatus}}.');
    }
}
