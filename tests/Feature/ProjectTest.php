<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_project_from_a_template(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'June Report',
            'template' => 'seo-report',
        ]);

        $project = Project::where('name', 'June Report')->firstOrFail();
        $response->assertRedirect(route('projects.edit', $project));
        $this->assertSame($org->id, $project->organization_id);
        $this->assertCount(3, $project->files);
        $this->assertTrue($project->files->contains('filename', 'index.html'));
    }

    public function test_user_cannot_see_another_organizations_project(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherProject = Project::factory()->for($otherOrg)->create();

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)->get("/projects/{$otherProject->id}")->assertNotFound();
    }

    public function test_platform_admin_can_see_any_organizations_project(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherProject = Project::factory()->for($otherOrg)->create();

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->get("/projects/{$otherProject->id}")->assertOk();
    }

    public function test_duplicate_copies_all_files_into_a_new_project(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create(['name' => 'Original']);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/duplicate");

        $copy = Project::where('name', 'Original (copy)')->firstOrFail();
        $response->assertRedirect(route('projects.edit', $copy));
        $this->assertCount($project->files->count(), $copy->files);
        $this->assertNotEquals($project->id, $copy->id);
    }

    public function test_update_sets_name_and_tag(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();

        $this->actingAs($user)->patch("/projects/{$project->id}", [
            'name' => 'Renamed',
            'tag' => 'seo',
        ])->assertRedirect();

        $this->assertSame('Renamed', $project->fresh()->name);
        $this->assertSame('seo', $project->fresh()->tag);
    }

    public function test_destroy_deletes_the_project(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();

        $this->actingAs($user)->delete("/projects/{$project->id}")->assertRedirect(route('projects.index'));

        $this->assertModelMissing($project);
    }
}
