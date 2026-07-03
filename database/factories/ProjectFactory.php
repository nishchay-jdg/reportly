<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by' => User::factory(),
            'name' => fake()->words(3, true),
            'tag' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($project) {
            $project->files()->createMany([
                ['filename' => 'index.html', 'type' => 'html', 'sort_order' => 0, 'content' => '<h1>Test</h1>'],
                ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => 'h1 { color: red; }'],
                ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => 'console.log(1);'],
            ]);
        });
    }
}
