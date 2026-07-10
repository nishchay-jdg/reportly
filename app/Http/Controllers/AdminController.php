<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $organizations = Organization::withCount(['users', 'projects', 'shares'])
            ->orderBy('name')
            ->paginate(20);

        $deployConfigured = filled(config('services.cpanel.host'))
            && filled(config('services.cpanel.username'))
            && filled(config('services.cpanel.api_token'))
            && filled(config('services.cpanel.repository_root'));

        return view('admin.index', compact('organizations', 'deployConfigured'));
    }

    public function users(): View
    {
        $users = User::with('organization')
            ->orderBy('name')
            ->paginate(30);

        return view('admin.users', compact('users'));
    }

    public function togglePlatformAdmin(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, "You can't change your own platform admin status.");

        $user->update(['is_platform_admin' => ! $user->is_platform_admin]);

        return back()->with('status', $user->is_platform_admin
            ? "{$user->name} is now a platform admin."
            : "{$user->name} is no longer a platform admin.");
    }
}
