<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('share-unlock');
        RateLimiter::clear('guest-comments');
        RateLimiter::clear('agreement-sign');
        parent::tearDown();
    }

    private function share(array $attrs = []): Share
    {
        $org = Organization::factory()->create();
        $project = Project::factory()->for($org)->create();

        return Share::factory()->for($project)->create($attrs);
    }

    public function test_repeated_wrong_password_guesses_are_throttled(): void
    {
        $share = $this->share(['visibility' => 'password', 'password_hash' => bcrypt('correct')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post("/r/{$share->slug}/unlock", ['password' => 'wrong'])
                ->assertSessionHasErrors('password');
        }

        $this->post("/r/{$share->slug}/unlock", ['password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_a_correct_password_still_works_before_the_limit_is_hit(): void
    {
        $share = $this->share(['visibility' => 'password', 'password_hash' => bcrypt('correct')]);

        $this->post("/r/{$share->slug}/unlock", ['password' => 'correct'])
            ->assertRedirect(route('share.view', $share->slug));
    }

    public function test_repeated_guest_comments_are_throttled(): void
    {
        $share = $this->share(['allow_guest_comments' => true]);

        for ($i = 0; $i < 10; $i++) {
            $this->post("/r/{$share->slug}/comments", ['body' => "comment {$i}", 'guest_name' => 'Jane'])
                ->assertRedirect();
        }

        $this->post("/r/{$share->slug}/comments", ['body' => 'one too many', 'guest_name' => 'Jane'])
            ->assertStatus(429);
    }

    public function test_repeated_agreement_signing_attempts_are_throttled(): void
    {
        $share = $this->share();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/r/{$share->slug}/agreement", ['agree_terms' => false])
                ->assertStatus(422);
        }

        $this->postJson("/r/{$share->slug}/agreement", ['agree_terms' => false])
            ->assertStatus(429);
    }

    public function test_throttling_is_scoped_per_share_not_global(): void
    {
        $shareA = $this->share(['visibility' => 'password', 'password_hash' => bcrypt('correct')]);
        $shareB = $this->share(['visibility' => 'password', 'password_hash' => bcrypt('correct')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post("/r/{$shareA->slug}/unlock", ['password' => 'wrong']);
        }
        $this->post("/r/{$shareA->slug}/unlock", ['password' => 'wrong'])->assertStatus(429);

        // A different share, same visitor/IP, isn't affected by shareA's exhausted limit.
        $this->post("/r/{$shareB->slug}/unlock", ['password' => 'correct'])
            ->assertRedirect(route('share.view', $shareB->slug));
    }
}
