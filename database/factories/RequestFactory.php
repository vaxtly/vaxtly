<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => \App\Models\Collection::factory(),
            'name' => fake()->words(3, true),
            'url' => fake()->url(),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'query_params' => [],
            'body' => null,
            'body_type' => 'json',
            'scripts' => null,
            'auth' => null,
            'order' => fake()->numberBetween(0, 100),
        ];
    }

    public function withPreRequestScript(string $requestId): static
    {
        return $this->state(fn () => [
            'scripts' => [
                'pre_request' => [
                    ['action' => 'send_request', 'request_id' => $requestId],
                ],
            ],
        ]);
    }

    public function withPostResponseScript(string $source, string $target): static
    {
        return $this->state(fn () => [
            'scripts' => [
                'post_response' => [
                    ['action' => 'set_variable', 'source' => $source, 'target' => $target, 'scope' => 'collection'],
                ],
            ],
        ]);
    }
}
