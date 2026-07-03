<?php

namespace Database\Factories;

use App\Models\Share;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'share_id' => Share::factory(),
            'author_type' => 'guest',
            'guest_name' => fake()->name(),
            'body' => fake()->sentence(),
            'position_x' => fake()->randomFloat(2, 0, 100),
            'position_y' => fake()->randomFloat(2, 0, 100),
            'is_resolved' => false,
        ];
    }
}
