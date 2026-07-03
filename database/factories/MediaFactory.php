<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::uuid().'.png';

        return [
            'uploaded_by' => User::factory(),
            'original_name' => $name,
            'path' => "media/test/{$name}",
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1000, 50000),
        ];
    }
}
