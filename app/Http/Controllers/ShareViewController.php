<?php

namespace App\Http\Controllers;

use App\Mail\ReportFirstViewed;
use App\Models\BrandKit;
use App\Models\Project;
use App\Models\Share;
use App\Models\ViewLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ShareViewController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $share = Share::findPublicBySlug($slug);
        $project = Project::query()
            ->withoutGlobalScope('organization')
            ->with('files')
            ->findOrFail($share->project_id);
        $brand = BrandKit::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', $share->organization_id)
            ->first();
        $viewerIsTeamMember = $share->viewerIsTeamMember($request->user());

        abort_unless($share->isAccessible(), 410, 'This link is no longer available.');

        if ($share->visibility === 'password' && ! $this->isUnlocked($request, $share)) {
            return view('share.password', compact('share'));
        }

        $isFirstView = ! $share->viewLogs()->exists();

        ViewLog::create([
            'share_id' => $share->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'viewed_at' => now(),
        ]);

        if ($isFirstView) {
            $this->notifyFirstView($share);
        }

        $pins = $this->pinsPayload($share, $request);

        return view('share.view', compact('share', 'project', 'brand', 'pins', 'viewerIsTeamMember'));
    }

    public function approve(Request $request, string $slug): RedirectResponse
    {
        $share = Share::findPublicBySlug($slug);

        abort_unless($share->isAccessible(), 410);

        $data = $request->validate([
            'status' => ['required', 'in:approved,changes_requested'],
            'name' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $share->update([
            'approval_status' => $data['status'],
            'approved_by_name' => $data['name'],
            'approval_note' => $data['note'] ?? null,
            'approved_at' => now(),
        ]);

        return back()->with('status', $data['status'] === 'approved' ? 'Thanks — marked as approved.' : 'Feedback sent — marked as changes requested.');
    }

    /**
     * JSON snapshot of the current comment threads, polled by the share viewer every few
     * seconds so one viewer's comment shows up for everyone else without a page reload —
     * there's no WebSocket/queue infrastructure available on the target (shared cPanel)
     * hosting, so polling is the deployable way to get "live enough" updates.
     */
    public function comments(Request $request, string $slug): JsonResponse
    {
        $share = Share::findPublicBySlug($slug);

        abort_unless($share->isAccessible(), 410);

        if ($share->visibility === 'password' && ! $this->isUnlocked($request, $share)) {
            abort(403);
        }

        return response()->json($this->pinsPayload($share, $request));
    }

    public function unlock(Request $request, string $slug): RedirectResponse
    {
        $share = Share::findPublicBySlug($slug);

        abort_unless($share->isAccessible(), 410, 'This link is no longer available.');

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $share->password_hash ?? '')) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session(["share_unlocked_{$share->id}" => true]);

        return redirect()->route('share.view', $slug);
    }

    private function notifyFirstView(Share $share): void
    {
        $org = $share->organization;

        if (! $org->notify_on_first_view || ! $org->notification_email) {
            return;
        }

        // Sent synchronously rather than queued: there's no persistent queue worker on the
        // target shared hosting, only cron — and we'd rather risk a slightly slower request
        // than silently drop notifications because nothing ever processed the queue.
        try {
            Mail::to($org->notification_email)->send(new ReportFirstViewed($share));
        } catch (\Throwable $e) {
            Log::warning('Failed to send first-view notification', ['share_id' => $share->id, 'error' => $e->getMessage()]);
        }
    }

    private function isUnlocked(Request $request, Share $share): bool
    {
        return $request->session()->get("share_unlocked_{$share->id}", false);
    }

    private function pinsPayload(Share $share, Request $request): array
    {
        $comments = $share->comments()->whereNotNull('position_x')->whereNull('parent_id')->with('replies')->latest()->get();

        // The viewer's own guest identity cookie, used to compute per-comment "is this mine?"
        // server-side — the raw token itself must never reach the client, since embedding it
        // in the page would let anyone copy another guest's token and spoof their own cookie
        // to delete that guest's comments.
        $guestToken = (string) $request->cookie('guest_uuid');

        return $comments->values()->map(fn ($c) => [
            'id' => $c->id,
            'position_x' => $c->position_x,
            'position_y' => $c->position_y,
            'report_context' => $c->report_context,
            'author' => $c->authorName(),
            'body' => $c->body,
            'created_at' => $c->created_at->diffForHumans(),
            'is_resolved' => $c->is_resolved,
            'is_own' => $c->isOwnedByGuestToken($guestToken),
            'replies' => $c->replies->map(fn ($r) => [
                'id' => $r->id,
                'author' => $r->authorName(),
                'body' => $r->body,
                'created_at' => $r->created_at->diffForHumans(),
                'is_own' => $r->isOwnedByGuestToken($guestToken),
            ])->values(),
        ])->all();
    }
}
