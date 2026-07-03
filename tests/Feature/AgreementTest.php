<?php

namespace Tests\Feature;

use App\Mail\AgreementSigned;
use App\Models\AgreementSignature;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgreementTest extends TestCase
{
    use RefreshDatabase;

    private function share(): Share
    {
        $org = Organization::factory()->create();
        $project = Project::factory()->for($org)->create();

        return Share::factory()->for($project)->create();
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Jane Client',
            'email' => 'jane@example.com',
            'company_name' => 'Client Co',
            'signature_text' => 'Jane Client',
            'terms_url' => 'https://example.com/terms',
            'agree_terms' => true,
        ];
    }

    public function test_check_returns_unsigned_for_a_fresh_share(): void
    {
        $share = $this->share();

        $this->getJson("/r/{$share->slug}/agreement")
            ->assertOk()
            ->assertJson(['signed' => false]);
    }

    public function test_signing_persists_a_record_and_sends_emails(): void
    {
        Mail::fake();

        $share = $this->share();
        $share->organization->update(['notification_email' => 'team@example.test']);

        $response = $this->postJson("/r/{$share->slug}/agreement", $this->validPayload());

        $response->assertOk()->assertJson(['signed' => true, 'full_name' => 'Jane Client']);
        $this->assertDatabaseHas('agreement_signatures', ['share_id' => $share->id, 'email' => 'jane@example.com']);
        Mail::assertSent(AgreementSigned::class, 2);
    }

    public function test_signing_without_agreeing_to_terms_fails_validation(): void
    {
        $share = $this->share();

        $this->postJson("/r/{$share->slug}/agreement", [...$this->validPayload(), 'agree_terms' => false])
            ->assertStatus(422)
            ->assertJsonValidationErrors('agree_terms');
    }

    public function test_a_second_signature_on_the_same_share_is_rejected(): void
    {
        $share = $this->share();

        $this->postJson("/r/{$share->slug}/agreement", $this->validPayload())->assertOk();

        $response = $this->postJson("/r/{$share->slug}/agreement", [
            ...$this->validPayload(),
            'full_name' => 'A Different Person',
        ]);

        $response->assertStatus(409)->assertJson(['signed' => true, 'full_name' => 'Jane Client']);
        $this->assertSame(1, AgreementSignature::where('share_id', $share->id)->count());
    }

    public function test_check_reflects_the_signed_state_after_signing(): void
    {
        $share = $this->share();
        $this->postJson("/r/{$share->slug}/agreement", $this->validPayload());

        $this->getJson("/r/{$share->slug}/agreement")
            ->assertOk()
            ->assertJson(['signed' => true, 'full_name' => 'Jane Client']);
    }

    public function test_password_protected_share_requires_unlock_before_signing(): void
    {
        $org = Organization::factory()->create();
        $project = Project::factory()->for($org)->create();
        $share = Share::factory()->for($project)->passwordProtected()->create();

        $this->getJson("/r/{$share->slug}/agreement")->assertForbidden();
        $this->postJson("/r/{$share->slug}/agreement", $this->validPayload())->assertForbidden();
    }

    public function test_expired_share_cannot_be_signed(): void
    {
        $share = $this->share();
        $share->update(['expires_at' => now()->subDay()]);

        $this->postJson("/r/{$share->slug}/agreement", $this->validPayload())->assertStatus(410);
    }
}
