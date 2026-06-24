<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TaskType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskType>
 */
class TaskTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'project_id' => Project::factory(),
            'name' => $name,
            'color' => fake()->randomElement(['sky', 'red', 'zinc', 'amber', 'teal', 'violet']),
            'icon' => 'tag',
            'branch_prefix' => mb_strtolower($name),
            'position' => 0,
        ];
    }
}
