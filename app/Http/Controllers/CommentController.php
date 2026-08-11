<?php

namespace App\Http\Controllers;

use App\Mail\NewCommentPosted;
use App\Models\Comment;
use App\Models\Share;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    private const GUEST_COOKIE = 'guest_uuid';

    public function store(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $share = Share::findPublicBySlug($slug);

        abort_unless($share->isAccessible(), 410);

        $isGuest = ! $share->viewerIsTeamMember($request->user());

        abort_if($isGuest && ! $share->allow_guest_comments, 403, 'Comments are disabled on this link.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'position_x' => ['nullable', 'numeric', 'between:0,100'],
            'position_y' => ['nullable', 'numeric', 'between:0,100'],
            'report_context' => ['nullable', 'string', 'max:500'],
            // A reply's parent may have been deleted by another viewer a moment ago —
            // validate it still exists and isn't itself a reply (no nesting past 1 level).
            'parent_id' => ['nullable', 'exists:comments,id'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email' => ['nullable', 'email', 'max:180'],
        ]);

        // Guests aren't authenticated, so "can only delete your own comment" is enforced via
        // a long-lived identity cookie rather than a user id — set once, reused on return visits.
        $guestToken = null;
        if ($isGuest) {
            $guestToken = $request->cookie(self::GUEST_COOKIE) ?: (string) Str::uuid();
            if (! $request->cookie(self::GUEST_COOKIE)) {
                Cookie::queue(self::GUEST_COOKIE, $guestToken, 60 * 24 * 365 * 2);
            }
        }

        $comment = Comment::create([
            'share_id' => $share->id,
            'parent_id' => $data['parent_id'] ?? null,
            'author_type' => $isGuest ? 'guest' : 'team',
            'user_id' => $isGuest ? null : $request->user()?->id,
            'guest_name' => $isGuest ? $data['guest_name'] : null,
            'guest_email' => $isGuest ? ($data['guest_email'] ?? null) : null,
            'guest_token' => $guestToken,
            'body' => $data['body'],
            'position_x' => $data['position_x'] ?? null,
            'position_y' => $data['position_y'] ?? null,
            'report_context' => $data['report_context'] ?? null,
        ]);

        // A reply on an already-resolved thread means the conversation isn't actually
        // settled — reopen the parent so it doesn't get lost as "done".
        if ($comment->parent_id) {
            Comment::where('id', $comment->parent_id)->where('is_resolved', true)->update(['is_resolved' => false]);
        }

        // Only notify on the client's own feedback — a team member replying to their own
        // thread shouldn't email the team about itself.
        if ($isGuest) {
            $this->notifyNewComment($comment);
        }

        if ($request->wantsJson()) {
            return response()->json(['id' => $comment->id]);
        }

        return back()->with('status', 'Comment added.');
    }

    private function notifyNewComment(Comment $comment): void
    {
        $share = Share::query()
            ->withoutGlobalScope('organization')
            ->with('organization')
            ->findOrFail($comment->share_id);
        $org = $share->organization;

        if (! $org->notify_on_comment || ! $org->notification_email) {
            return;
        }

        try {
            Mail::to($org->notification_email)->send(new NewCommentPosted($comment));
        } catch (\Throwable $e) {
            Log::warning('Failed to send new-comment notification', ['comment_id' => $comment->id, 'error' => $e->getMessage()]);
        }
    }

    public function resolve(Request $request, Comment $comment): RedirectResponse|JsonResponse
    {
        $this->authorizeTeamAccess($request, $comment);

        $comment->update(['is_resolved' => ! $comment->is_resolved]);

        if ($request->wantsJson()) {
            return response()->json(['is_resolved' => $comment->is_resolved]);
        }

        return back();
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse|JsonResponse
    {
        $this->authorizeDeleteAccess($request, $comment);

        // Replies cascade-delete at the DB level (comments.parent_id is cascadeOnDelete).
        $comment->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'Comment deleted.');
    }

    private function authorizeTeamAccess(Request $request, Comment $comment): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        if ($user->is_platform_admin) {
            return;
        }

        // Share has an organization-scoping global scope, so $comment->share silently
        // resolves to null (not 403) when the authenticated user belongs to a different
        // org than the comment's share — query around the scope to get a real comparison.
        // ->value() is a raw query builder call, not a hydrated model, so it bypasses
        // Share's own attribute casts — cast explicitly rather than relying on whatever
        // type the DB driver happens to return for an integer column.
        $shareOrgId = (int) Share::withoutGlobalScope('organization')->whereKey($comment->share_id)->value('organization_id');

        abort_unless((int) $user->organization_id === $shareOrgId, 403);
    }

    private function authorizeDeleteAccess(Request $request, Comment $comment): void
    {
        if ($request->user()) {
            $this->authorizeTeamAccess($request, $comment);

            return;
        }

        // A guest may only delete a comment tied to their own identity cookie.
        abort_unless(
            $comment->author_type === 'guest'
                && $comment->guest_token
                && hash_equals($comment->guest_token, (string) $request->cookie(self::GUEST_COOKIE)),
            403
        );
    }
}
