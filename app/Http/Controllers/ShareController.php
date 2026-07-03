<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Share;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShareController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('shares', 'slug')],
            'visibility' => ['required', 'in:public,password'],
            'password' => ['nullable', 'required_if:visibility,password', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'allow_guest_comments' => ['sometimes', 'boolean'],
        ]);

        Share::create([
            'project_id' => $project->id,
            'created_by' => $request->user()->id,
            'slug' => $data['slug'] ?: $this->uniqueSlug($project->name),
            'visibility' => $data['visibility'],
            'password_hash' => $data['visibility'] === 'password' ? Hash::make($data['password']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'allow_guest_comments' => $request->boolean('allow_guest_comments'),
        ]);

        return back()->with('status', 'Share link created.');
    }

    public function update(Request $request, Project $project, Share $share): RedirectResponse
    {
        abort_unless($share->project_id === $project->id, 404);

        $data = $request->validate([
            'visibility' => ['required', 'in:public,password'],
            'password' => ['nullable', 'required_if:visibility,password', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'allow_guest_comments' => ['sometimes', 'boolean'],
        ]);

        $share->update([
            'visibility' => $data['visibility'],
            'password_hash' => $data['visibility'] === 'password'
                ? ($data['password'] ? Hash::make($data['password']) : $share->password_hash)
                : null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'allow_guest_comments' => $request->boolean('allow_guest_comments'),
        ]);

        return back()->with('status', 'Share link updated.');
    }

    public function destroy(Project $project, Share $share): RedirectResponse
    {
        abort_unless($share->project_id === $project->id, 404);

        $share->delete();

        return back()->with('status', 'Share link revoked.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'report';
        $slug = $base.'-'.Str::lower(Str::random(6));

        while (Share::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }
}
