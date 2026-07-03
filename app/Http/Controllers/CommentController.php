<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Share;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $share = Share::where('slug', $slug)->firstOrFail();

        abort_unless($share->isAccessible(), 410);

        $isGuest = ! $request->user();

        abort_if($isGuest && ! $share->allow_guest_comments, 403, 'Comments are disabled on this link.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'position_x' => ['nullable', 'numeric', 'between:0,100'],
            'position_y' => ['nullable', 'numeric', 'between:0,100'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email' => ['nullable', 'email', 'max:180'],
        ]);

        Comment::create([
            'share_id' => $share->id,
            'parent_id' => $data['parent_id'] ?? null,
            'author_type' => $isGuest ? 'guest' : 'team',
            'user_id' => $request->user()?->id,
            'guest_name' => $isGuest ? $data['guest_name'] : null,
            'guest_email' => $isGuest ? ($data['guest_email'] ?? null) : null,
            'body' => $data['body'],
            'position_x' => $data['position_x'] ?? null,
            'position_y' => $data['position_y'] ?? null,
        ]);

        return back()->with('status', 'Comment added.');
    }

    public function resolve(Request $request, Comment $comment): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $comment->update(['is_resolved' => ! $comment->is_resolved]);

        return back();
    }
}
