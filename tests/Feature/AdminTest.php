<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_platform_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_platform_admin_can_toggle_another_users_admin_status(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $target = User::factory()->create(['is_platform_admin' => false]);

        $this->actingAs($admin)
            ->post("/admin/users/{$target->id}/toggle-platform-admin")
            ->assertRedirect();

        $this->assertTrue($target->fresh()->is_platform_admin);
    }

    public function test_admin_cannot_change_their_own_platform_admin_status(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)
            ->post("/admin/users/{$admin->id}/toggle-platform-admin")
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_platform_admin);
    }
}
