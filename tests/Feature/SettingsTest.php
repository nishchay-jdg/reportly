<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('brand-logos'));
        parent::tearDown();
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)->patch('/settings/notifications', [
            'notification_email' => 'ops@example.test',
            'notify_on_first_view' => '1',
            'notify_on_comment' => '1',
        ])->assertRedirect();

        $org->refresh();
        $this->assertSame('ops@example.test', $org->notification_email);
        $this->assertTrue($org->notify_on_first_view);
        $this->assertTrue($org->notify_on_comment);
    }

    public function test_user_can_update_brand_kit_without_a_logo(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)->patch('/settings/brand-kit', [
            'primary_color' => '#16a34a',
            'footer_text' => 'Powered by Acme',
        ])->assertRedirect();

        $brandKit = $org->fresh()->brandKit;
        $this->assertSame('#16a34a', $brandKit->primary_color);
        $this->assertSame('Powered by Acme', $brandKit->footer_text);
    }

    public function test_user_can_upload_and_replace_a_logo(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)->patch('/settings/brand-kit', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $brandKit = $org->fresh()->brandKit;
        $this->assertNotNull($brandKit->logo_path);
        $this->assertFileExists(public_path($brandKit->logo_path));
        $firstPath = $brandKit->logo_path;

        $this->actingAs($user)->patch('/settings/brand-kit', [
            'logo' => UploadedFile::fake()->image('logo2.png'),
        ])->assertRedirect();

        $brandKit->refresh();
        $this->assertNotEquals($firstPath, $brandKit->logo_path);
        $this->assertFileDoesNotExist(public_path($firstPath));
        $this->assertFileExists(public_path($brandKit->logo_path));
    }

    public function test_user_can_remove_the_logo(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user)->patch('/settings/brand-kit', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $path = $org->fresh()->brandKit->logo_path;

        $this->actingAs($user)->patch('/settings/brand-kit', ['remove_logo' => '1'])->assertRedirect();

        $this->assertNull($org->fresh()->brandKit->logo_path);
        $this->assertFileDoesNotExist(public_path($path));
    }
}
