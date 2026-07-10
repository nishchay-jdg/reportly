<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cpanel.host' => 'server.example.com',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'testuser',
            'services.cpanel.api_token' => 'test-token',
            'services.cpanel.repository_root' => '/home/testuser/app',
            'services.cpanel.branch' => 'main',
        ]);
    }

    public function test_regular_user_cannot_trigger_a_deploy(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/admin/deploy')->assertForbidden();
    }

    public function test_platform_admin_can_trigger_a_deploy(): void
    {
        Http::fake([
            '*/execute/VersionControl/update*' => Http::response(['errors' => null, 'data' => []]),
            '*/execute/VersionControlDeployment/create*' => Http::response(['errors' => null, 'data' => ['task_id' => 42]]),
        ]);

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $response = $this->actingAs($admin)->postJson('/admin/deploy');

        $response->assertOk()->assertJson(['task_id' => 42]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'VersionControl/update')
            && $request['repository_root'] === '/home/testuser/app'
            && $request['branch'] === 'main');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'VersionControlDeployment/create')
            && $request->hasHeader('Authorization', 'cpanel testuser:test-token'));
    }

    public function test_deploy_trigger_failure_returns_a_clean_error(): void
    {
        Http::fake([
            '*/execute/VersionControl/update*' => Http::response(['error' => 'bad credentials'], 401),
        ]);

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->postJson('/admin/deploy')->assertStatus(502);
    }

    public function test_status_reports_succeeded(): void
    {
        Http::fake([
            '*/execute/VersionControlDeployment/retrieve*' => Http::response([
                'errors' => null,
                'data' => [
                    ['task_id' => 42, 'timestamps' => ['succeeded' => '2026-01-01 00:00:00', 'failed' => null]],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->getJson('/admin/deploy/status?task_id=42')
            ->assertOk()->assertJson(['status' => 'succeeded']);
    }

    public function test_status_reports_failed(): void
    {
        Http::fake([
            '*/execute/VersionControlDeployment/retrieve*' => Http::response([
                'errors' => null,
                'data' => [
                    ['task_id' => 42, 'timestamps' => ['succeeded' => null, 'failed' => '2026-01-01 00:00:00']],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->getJson('/admin/deploy/status?task_id=42')
            ->assertOk()->assertJson(['status' => 'failed']);
    }

    public function test_status_reports_running_while_task_is_unfinished(): void
    {
        Http::fake([
            '*/execute/VersionControlDeployment/retrieve*' => Http::response([
                'errors' => null,
                'data' => [
                    ['task_id' => 42, 'timestamps' => ['succeeded' => null, 'failed' => null]],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->getJson('/admin/deploy/status?task_id=42')
            ->assertOk()->assertJson(['status' => 'running']);
    }

    public function test_deploy_button_only_shows_when_cpanel_is_configured(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->get('/admin')->assertSee('Deploy latest');

        config(['services.cpanel.host' => null]);

        $this->actingAs($admin)->get('/admin')->assertDontSee('Deploy latest');
    }
}
