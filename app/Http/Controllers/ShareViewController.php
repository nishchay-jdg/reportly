<?php

namespace App\Http\Controllers;

use App\Models\Share;
use App\Models\ViewLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ShareViewController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $share = Share::where('slug', $slug)->with('project.files', 'project.organization.brandKit')->firstOrFail();

        abort_unless($share->isAccessible(), 410, 'This link is no longer available.');

        if ($share->visibility === 'password' && ! $this->isUnlocked($request, $share)) {
            return view('share.password', compact('share'));
        }

        ViewLog::create([
            'share_id' => $share->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'viewed_at' => now(),
        ]);

        $comments = $share->comments()->whereNull('parent_id')->with('replies')->latest()->get();

        return view('share.view', compact('share', 'comments'));
    }

    public function unlock(Request $request, string $slug): RedirectResponse
    {
        $share = Share::where('slug', $slug)->firstOrFail();

        abort_unless($share->isAccessible(), 410, 'This link is no longer available.');

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $share->password_hash ?? '')) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        session(["share_unlocked_{$share->id}" => true]);

        return redirect()->route('share.view', $slug);
    }

    private function isUnlocked(Request $request, Share $share): bool
    {
        return $request->session()->get("share_unlocked_{$share->id}", false);
    }
}
