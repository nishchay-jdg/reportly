<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'notification_email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'notify_on_comment' => ['sometimes', 'boolean'],
            'notify_on_first_view' => ['sometimes', 'boolean'],
        ]);

        $user = DB::transaction(function () use ($request) {
            $organization = Organization::create([
                'name' => $request->organization_name,
                'slug' => $this->uniqueOrganizationSlug($request->organization_name),
                // Defaults to the org-admin's own address so notifications work out of the
                // box even if this step is skipped — it can be changed later in settings.
                'notification_email' => $request->notification_email ?: $request->email,
                'notify_on_comment' => $request->boolean('notify_on_comment', true),
                'notify_on_first_view' => $request->boolean('notify_on_first_view', true),
            ]);

            return User::create([
                'organization_id' => $organization->id,
                'role' => 'org_admin',
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function uniqueOrganizationSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $suffix = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
