<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\TagSynonym;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TagSynonym>
 */
class TagSynonymFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tag_id' => Tag::factory(),
            'name' => fake()->unique()->word(),
        ];
    }
}
