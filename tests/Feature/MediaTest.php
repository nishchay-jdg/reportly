<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('media'));
        parent::tearDown();
    }

    public function test_user_can_upload_a_file(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->postJson('/media-library', [
            'file' => UploadedFile::fake()->image('logo.png', 100, 100)->size(50),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('media', ['organization_id' => $org->id, 'original_name' => 'logo.png']);
        $media = Media::where('organization_id', $org->id)->firstOrFail();
        $this->assertFileExists(public_path($media->path));
    }

    public function test_upload_is_rejected_when_it_exceeds_the_org_quota(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        // Simulate the org already sitting right at its quota.
        Media::factory()->for($org)->create(['size' => 200 * 1024 * 1024]);

        $response = $this->actingAs($user)->postJson('/media-library', [
            'file' => UploadedFile::fake()->image('logo.png')->size(50),
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_delete_another_organizations_media(): void
    {
        $otherOrg = Organization::factory()->create();
        $media = Media::factory()->for($otherOrg)->create();

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        // Media's BelongsToOrganization scope means route-model binding can't even find
        // another org's row, so this 404s rather than reaching the controller's own check.
        $this->actingAs($user)->deleteJson("/media-library/{$media->id}")->assertNotFound();
        $this->assertModelExists($media);
    }

    public function test_user_can_delete_their_own_organizations_media(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $media = Media::factory()->for($org)->create();
        File::ensureDirectoryExists(public_path(dirname($media->path)));
        File::put(public_path($media->path), 'fake content');

        $this->actingAs($user)->deleteJson("/media-library/{$media->id}")->assertOk();
        $this->assertModelMissing($media);
    }
}
