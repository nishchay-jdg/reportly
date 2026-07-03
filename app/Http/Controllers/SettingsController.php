<?php

namespace App\Http\Controllers;

use App\Models\BrandKit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $org = $request->user()->organization;
        $org->loadMissing('brandKit');

        return view('settings.edit', ['org' => $org, 'brandKit' => $org->brandKit]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notification_email' => ['nullable', 'email', 'max:180'],
            'notify_on_first_view' => ['sometimes', 'boolean'],
            'notify_on_comment' => ['sometimes', 'boolean'],
        ]);

        $request->user()->organization->update([
            'notification_email' => $data['notification_email'] ?? null,
            'notify_on_first_view' => $request->boolean('notify_on_first_view'),
            'notify_on_comment' => $request->boolean('notify_on_comment'),
        ]);

        return back()->with('status', 'Notification settings updated.');
    }

    public function updateBrandKit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'font_family' => ['nullable', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $org = $request->user()->organization;

        // Queried fresh rather than via $org->brandKit — that relation may already be
        // cached as null from an earlier access in this same request (e.g. a view
        // composer), which would otherwise make this always take the "create" branch
        // and insert a duplicate row on every save after the first.
        $brandKit = BrandKit::withoutGlobalScopes()->firstOrNew(['organization_id' => $org->id]);

        // Stored directly under public/, same as the media library — not the storage/
        // disk + symlink, which doesn't reliably survive a git-based cPanel deploy.
        if ($request->hasFile('logo')) {
            if ($brandKit->logo_path) {
                File::delete(public_path($brandKit->logo_path));
            }

            $file = $request->file('logo');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $directory = public_path("brand-logos/{$org->id}");
            File::ensureDirectoryExists($directory);
            $file->move($directory, $filename);
            $data['logo_path'] = "brand-logos/{$org->id}/{$filename}";
        } elseif ($request->boolean('remove_logo') && $brandKit->logo_path) {
            File::delete(public_path($brandKit->logo_path));
            $data['logo_path'] = null;
        }

        $brandKit->fill([
            'organization_id' => $org->id,
            'name' => $data['name'] ?? $brandKit->name ?? $org->name,
            'primary_color' => $data['primary_color'] ?? $brandKit->primary_color ?? '#2563eb',
            'secondary_color' => $data['secondary_color'] ?? $brandKit->secondary_color ?? '#1e293b',
            'font_family' => $brandKit->font_family ?? 'Inter',
            'footer_text' => $data['footer_text'] ?? null,
            // array_key_exists, not ??  — a removed logo sets $data['logo_path'] to an
            // explicit null, which ?? would treat the same as "not provided" and silently
            // keep the old path instead of clearing it.
            'logo_path' => array_key_exists('logo_path', $data) ? $data['logo_path'] : $brandKit->logo_path,
        ])->save();

        return back()->with('status', 'Brand kit updated.');
    }
}
