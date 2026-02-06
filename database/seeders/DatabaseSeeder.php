<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample collections with requests
        $jsonPlaceholder = \App\Models\Collection::factory()->create([
            'name' => 'JSONPlaceholder API',
            'description' => 'Sample requests for JSONPlaceholder API',
            'order' => 1,
        ]);

        \App\Models\Request::factory()->create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'Get All Posts',
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
            'query_params' => [],
            'body' => null,
            'order' => 1,
        ]);

        \App\Models\Request::factory()->create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'Get Single Post',
            'url' => 'https://jsonplaceholder.typicode.com/posts/1',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
            'query_params' => [],
            'body' => null,
            'order' => 2,
        ]);

        \App\Models\Request::factory()->create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'Create Post',
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'query_params' => [],
            'body' => json_encode(['title' => 'foo', 'body' => 'bar', 'userId' => 1]),
            'body_type' => 'json',
            'order' => 3,
        ]);

        $httpbin = \App\Models\Collection::factory()->create([
            'name' => 'HTTPBin Tests',
            'description' => 'Various HTTP testing endpoints',
            'order' => 2,
        ]);

        \App\Models\Request::factory()->create([
            'collection_id' => $httpbin->id,
            'name' => 'GET Request',
            'url' => 'https://httpbin.org/get',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
            'query_params' => ['test' => 'value'],
            'body' => null,
            'order' => 1,
        ]);

        \App\Models\Request::factory()->create([
            'collection_id' => $httpbin->id,
            'name' => 'POST JSON',
            'url' => 'https://httpbin.org/post',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'query_params' => [],
            'body' => json_encode(['key' => 'value']),
            'body_type' => 'json',
            'order' => 2,
        ]);
    }
}
