<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Folder>
 */
class FolderFactory extends Factory
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
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inFolder(\App\Models\Folder $folder): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_id' => $folder->collection_id,
            'parent_id' => $folder->id,
        ]);
    }
}
