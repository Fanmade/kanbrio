<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'due_date' => null,
            // story_number is assigned atomically by the HasScopedNumber trait.
        ];
    }

    /**
     * Give the story a due date.
     */
    public function dueOn(string $date): static
    {
        return $this->state(fn () => ['due_date' => $date]);
    }
}
