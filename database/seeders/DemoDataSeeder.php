<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Collection;
use App\Models\Folder;
use App\Models\Request;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Collection with Subfolders (ReqRes API)
        $reqRes = Collection::create([
            'name' => 'ReqRes API',
            'description' => 'A hosted REST-API ready to respond to your AJAX requests',
            'order' => 1,
        ]);

        // Folder: Users
        $usersFolder = Folder::create([
            'collection_id' => $reqRes->id,
            'name' => 'Users',
            'order' => 1,
        ]);

        Request::create([
            'collection_id' => $reqRes->id,
            'folder_id' => $usersFolder->id,
            'name' => 'List Users',
            'url' => 'https://reqres.in/api/users',
            'method' => 'GET',
            'query_params' => ['page' => '2'],
            'order' => 1,
        ]);

        Request::create([
            'collection_id' => $reqRes->id,
            'folder_id' => $usersFolder->id,
            'name' => 'Single User',
            'url' => 'https://reqres.in/api/users/2',
            'method' => 'GET',
            'order' => 2,
        ]);

        // Folder: Auth
        $authFolder = Folder::create([
            'collection_id' => $reqRes->id,
            'name' => 'Auth',
            'order' => 2,
        ]);

        Request::create([
            'collection_id' => $reqRes->id,
            'folder_id' => $authFolder->id,
            'name' => 'Register',
            'url' => 'https://reqres.in/api/register',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['email' => 'eve.holt@reqres.in', 'password' => 'pistol']),
            'body_type' => 'json',
            'order' => 1,
        ]);

        Request::create([
            'collection_id' => $reqRes->id,
            'folder_id' => $authFolder->id,
            'name' => 'Login',
            'url' => 'https://reqres.in/api/login',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['email' => 'eve.holt@reqres.in', 'password' => 'cityslicka']),
            'body_type' => 'json',
            'order' => 2,
        ]);


        // 2. Collection without Subfolders (JSONPlaceholder)
        $jsonPlaceholder = Collection::create([
            'name' => 'JSONPlaceholder',
            'description' => 'Free fake API for testing and prototyping',
            'order' => 2,
        ]);

        Request::create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'List Posts',
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'method' => 'GET',
            'order' => 1,
        ]);

        Request::create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'Create Post',
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json; charset=UTF-8'],
            'body' => json_encode(['title' => 'foo', 'body' => 'bar', 'userId' => 1]),
            'body_type' => 'json',
            'order' => 2,
        ]);

        Request::create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'Update Post',
            'url' => 'https://jsonplaceholder.typicode.com/posts/1',
            'method' => 'PUT',
            'headers' => ['Content-Type' => 'application/json; charset=UTF-8'],
            'body' => json_encode(['id' => 1, 'title' => 'foo', 'body' => 'bar', 'userId' => 1]),
            'body_type' => 'json',
            'order' => 3,
        ]);

        Request::create([
            'collection_id' => $jsonPlaceholder->id,
            'name' => 'Delete Post',
            'url' => 'https://jsonplaceholder.typicode.com/posts/1',
            'method' => 'DELETE',
            'order' => 4,
        ]);


        // 3. Collection without Subfolders (HTTPBin)
        $httpBin = Collection::create([
            'name' => 'HTTPBin',
            'description' => 'A simple HTTP Request & Response Service',
            'order' => 3,
        ]);

        Request::create([
            'collection_id' => $httpBin->id,
            'name' => 'Get IP',
            'url' => 'https://httpbin.org/ip',
            'method' => 'GET',
            'order' => 1,
        ]);

        Request::create([
            'collection_id' => $httpBin->id,
            'name' => 'Post Data',
            'url' => 'https://httpbin.org/post',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['message' => 'Hello World']),
            'body_type' => 'json',
            'order' => 2,
        ]);

        Request::create([
            'collection_id' => $httpBin->id,
            'name' => 'Status 404',
            'url' => 'https://httpbin.org/status/404',
            'method' => 'GET',
            'order' => 3,
        ]);
    }
}
