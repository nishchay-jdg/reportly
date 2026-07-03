<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShareFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'created_by' => User::factory(),
            'slug' => Str::random(10),
            'visibility' => 'public',
            'is_active' => true,
            'allow_guest_comments' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function ($share) {
            if (! $share->organization_id) {
                $share->organization_id = Project::find($share->project_id)?->organization_id;
            }
        });
    }

    public function passwordProtected(string $password = 'secret123'): static
    {
        return $this->state(fn () => [
            'visibility' => 'password',
            'password_hash' => bcrypt($password),
        ]);
    }
}
