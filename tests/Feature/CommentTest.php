<?php

namespace Tests\Feature;

use App\Mail\NewCommentPosted;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function shareWithOrg(): Share
    {
        $org = Organization::factory()->create();
        $project = Project::factory()->for($org)->create();

        return Share::factory()->for($project)->create();
    }

    public function test_guest_can_post_a_comment(): void
    {
        $share = $this->shareWithOrg();

        $response = $this->post("/r/{$share->slug}/comments", [
            'body' => 'Looks great',
            'guest_name' => 'Jane Client',
            'position_x' => 10,
            'position_y' => 20,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', ['share_id' => $share->id, 'body' => 'Looks great', 'author_type' => 'guest']);
    }

    public function test_guest_comment_is_blocked_when_share_disallows_it(): void
    {
        $share = $this->shareWithOrg();
        $share->update(['allow_guest_comments' => false]);

        $this->post("/r/{$share->slug}/comments", ['body' => 'hi', 'guest_name' => 'Jane'])
            ->assertForbidden();
    }

    public function test_guest_comment_notifies_org_when_configured(): void
    {
        Mail::fake();

        $share = $this->shareWithOrg();
        $share->organization->update(['notification_email' => 'team@example.test', 'notify_on_comment' => true]);

        $this->post("/r/{$share->slug}/comments", ['body' => 'hi', 'guest_name' => 'Jane']);

        Mail::assertSent(NewCommentPosted::class);
    }

    public function test_guest_can_only_delete_their_own_comment(): void
    {
        $share = $this->shareWithOrg();

        // First guest posts a comment, picking up a guest_uuid cookie.
        $this->post("/r/{$share->slug}/comments", ['body' => 'mine', 'guest_name' => 'Jane']);
        $comment = Comment::where('body', 'mine')->firstOrFail();
        $ownCookie = $comment->guest_token;

        // A different guest (different/no cookie) cannot delete it.
        $this->withCookie('guest_uuid', 'someone-elses-token')
            ->delete("/comments/{$comment->id}")
            ->assertForbidden();
        $this->assertModelExists($comment);

        // The original guest, with the matching cookie, can.
        $this->withCookie('guest_uuid', $ownCookie)
            ->delete("/comments/{$comment->id}")
            ->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_team_member_can_delete_any_comment_in_their_org(): void
    {
        $share = $this->shareWithOrg();
        $comment = Comment::factory()->create(['share_id' => $share->id, 'author_type' => 'guest', 'guest_name' => 'Jane']);

        $user = User::factory()->create(['organization_id' => $share->organization_id]);

        $this->actingAs($user)->delete("/comments/{$comment->id}")->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_team_member_from_another_org_cannot_delete_the_comment(): void
    {
        $share = $this->shareWithOrg();
        $comment = Comment::factory()->create(['share_id' => $share->id, 'author_type' => 'guest', 'guest_name' => 'Jane']);

        $outsider = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

        $this->actingAs($outsider)->delete("/comments/{$comment->id}")->assertForbidden();
        $this->assertModelExists($comment);
    }

    public function test_team_member_can_toggle_resolve(): void
    {
        $share = $this->shareWithOrg();
        $comment = Comment::factory()->create(['share_id' => $share->id, 'author_type' => 'guest', 'guest_name' => 'Jane']);
        $user = User::factory()->create(['organization_id' => $share->organization_id]);

        $this->actingAs($user)->post("/comments/{$comment->id}/resolve")->assertRedirect();
        $this->assertTrue($comment->fresh()->is_resolved);

        $this->actingAs($user)->post("/comments/{$comment->id}/resolve")->assertRedirect();
        $this->assertFalse($comment->fresh()->is_resolved);
    }

    public function test_a_reply_reopens_a_resolved_thread(): void
    {
        $share = $this->shareWithOrg();
        $comment = Comment::factory()->create(['share_id' => $share->id, 'author_type' => 'guest', 'guest_name' => 'Jane', 'is_resolved' => true]);

        $this->post("/r/{$share->slug}/comments", [
            'body' => 'following up',
            'guest_name' => 'Jane',
            'parent_id' => $comment->id,
        ])->assertRedirect();

        $this->assertFalse($comment->fresh()->is_resolved);
    }
}
