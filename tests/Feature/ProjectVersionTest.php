<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_snapshot_a_project(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();

        $response = $this->actingAs($user)
            ->postJson("/projects/{$project->id}/versions", ['label' => 'Before redesign']);

        $response->assertOk()->assertJsonPath('label', 'Before redesign');
        $this->assertSame(1, $project->versions()->count());
        $this->assertCount($project->files->count(), $project->versions()->first()->files);
    }

    public function test_restore_overwrites_current_files_and_backs_up_first(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();

        // Snapshot the original state.
        $version = $this->actingAs($user)
            ->postJson("/projects/{$project->id}/versions")
            ->json();

        // Change a file after the snapshot.
        $htmlFile = $project->files()->where('type', 'html')->first();
        $htmlFile->update(['content' => '<h1>Changed</h1>']);

        $response = $this->actingAs($user)
            ->postJson("/projects/{$project->id}/versions/{$version['id']}/restore");

        $response->assertOk();
        $this->assertSame('<h1>Test</h1>', $htmlFile->fresh()->content);

        // A safety-backup version of the pre-restore ("Changed") state should now exist.
        $this->assertSame(2, $project->versions()->count());
        $backup = $project->versions()->where('id', '!=', $version['id'])->first();
        $this->assertStringContainsString('Before restoring', $backup->label);
        $this->assertSame('<h1>Changed</h1>', $backup->files->firstWhere('filename', 'index.html')->content);
    }

    public function test_cannot_restore_a_version_belonging_to_another_project(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();
        $otherProject = Project::factory()->for($org)->create();

        $version = $this->actingAs($user)
            ->postJson("/projects/{$otherProject->id}/versions")
            ->json();

        $this->actingAs($user)
            ->postJson("/projects/{$project->id}/versions/{$version['id']}/restore")
            ->assertNotFound();
    }

    public function test_destroy_removes_the_version(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $project = Project::factory()->for($org)->create();

        $version = $this->actingAs($user)->postJson("/projects/{$project->id}/versions")->json();

        $this->actingAs($user)
            ->deleteJson("/projects/{$project->id}/versions/{$version['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('project_versions', ['id' => $version['id']]);
    }
}
