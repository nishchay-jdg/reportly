<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    private function projectOwner(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();

        return [$user, $project];
    }

    public function test_user_can_create_a_public_share_link(): void
    {
        [$user, $project] = $this->projectOwner();

        $response = $this->actingAs($user)->post("/projects/{$project->id}/shares", [
            'visibility' => 'public',
            'allow_guest_comments' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shares', ['project_id' => $project->id, 'visibility' => 'public']);
    }

    public function test_json_request_gets_a_json_validation_error_not_a_redirect(): void
    {
        // Regression test: bootstrap/app.php's shouldRenderJsonWhen used to only check
        // request()->is('api/*'), so a JSON-requesting client hitting a web route got an
        // HTML redirect on validation failure instead of a 422 — breaking the share form's
        // fetch() call, which tried to res.json() an HTML page and silently failed.
        [$user, $project] = $this->projectOwner();

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post("/projects/{$project->id}/shares", [
                'visibility' => 'password',
                'password' => 'ab', // shorter than the 4-char minimum
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_json_request_gets_json_on_success(): void
    {
        [$user, $project] = $this->projectOwner();

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post("/projects/{$project->id}/shares", ['visibility' => 'public']);

        $response->assertOk()->assertJsonStructure(['slug', 'url', 'visibility']);
    }

    public function test_password_protected_share_requires_correct_password(): void
    {
        [$user, $project] = $this->projectOwner();
        $share = Share::factory()->for($project)->passwordProtected('correct-horse')->create();

        $this->get("/r/{$share->slug}")->assertOk()->assertViewIs('share.password');

        $this->post("/r/{$share->slug}/unlock", ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->post("/r/{$share->slug}/unlock", ['password' => 'correct-horse'])
            ->assertRedirect(route('share.view', $share->slug));
    }

    public function test_expired_share_is_not_accessible(): void
    {
        [$user, $project] = $this->projectOwner();
        $share = Share::factory()->for($project)->create(['expires_at' => now()->subDay()]);

        $this->get("/r/{$share->slug}")->assertStatus(410);
    }

    public function test_public_share_is_still_accessible_when_authenticated_to_another_organization(): void
    {
        [$user, $project] = $this->projectOwner();
        $share = Share::factory()->for($project)->create();

        $outsiderOrg = Organization::factory()->create();
        $outsider = User::factory()->create(['organization_id' => $outsiderOrg->id]);

        $this->actingAs($outsider)
            ->get("/r/{$share->slug}")
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_inactive_share_is_not_accessible(): void
    {
        [$user, $project] = $this->projectOwner();
        $share = Share::factory()->for($project)->create(['is_active' => false]);

        $this->get("/r/{$share->slug}")->assertStatus(410);
    }

    public function test_destroy_removes_the_share(): void
    {
        [$user, $project] = $this->projectOwner();
        $share = Share::factory()->for($project)->create();

        $this->actingAs($user)->delete("/projects/{$project->id}/shares/{$share->id}")->assertRedirect();

        $this->assertModelMissing($share);
    }
}
