<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(0, 100),
            'settings' => [],
        ];
    }

    public function withRemoteSettings(array $settings = []): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], [
                'remote' => array_merge([
                    'provider' => 'github',
                    'repository' => 'owner/repo',
                    'token' => 'test-token',
                    'branch' => 'main',
                    'auto_sync' => false,
                ], $settings),
            ]),
        ]);
    }

    public function withVaultSettings(array $settings = []): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], [
                'vault' => array_merge([
                    'provider' => 'hashicorp',
                    'url' => 'https://vault.example.com',
                    'auth_method' => 'token',
                    'token' => 'test-token',
                    'role_id' => '',
                    'secret_id' => '',
                    'namespace' => '',
                    'mount' => 'secret',
                ], $settings),
            ]),
        ]);
    }
}
