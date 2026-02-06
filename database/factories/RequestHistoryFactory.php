<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RequestHistory>
 */
class RequestHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_id' => \App\Models\Request::factory(),
            'status_code' => fake()->randomElement([200, 201, 400, 404, 500]),
            'response_body' => json_encode(['message' => fake()->sentence()]),
            'response_headers' => [
                'Content-Type' => 'application/json',
            ],
            'duration_ms' => fake()->numberBetween(50, 2000),
            'executed_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
