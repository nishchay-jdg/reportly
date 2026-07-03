<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaController extends Controller
{
    // Shared cPanel hosting means real disk space, not S3 — cap it per org so one project
    // can't quietly fill the account's disk quota.
    private const ORG_QUOTA_BYTES = 200 * 1024 * 1024;

    private const MAX_FILE_KB = 5 * 1024;

    private const ALLOWED_TYPES = 'jpg,jpeg,png,gif,webp,svg,mp4,webm,woff,woff2,ttf';

    public function page(): View
    {
        return view('media.index');
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->libraryPayload($request));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_FILE_KB, 'mimes:'.self::ALLOWED_TYPES],
        ]);

        $organizationId = $request->user()->organization_id;
        $file = $request->file('file');

        // Captured before move(): the UploadedFile's temp path stops existing once it's
        // moved, so calling getSize()/getClientMimeType() again afterward throws.
        $size = $file->getSize();
        $mimeType = $file->getClientMimeType();
        $originalName = $file->getClientOriginalName();

        $usedBytes = Media::where('organization_id', $organizationId)->sum('size');
        if ($usedBytes + $size > self::ORG_QUOTA_BYTES) {
            return response()->json([
                'message' => 'Your team has used all of its media storage. Delete unused files to free up space.',
            ], 422);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path("media/{$organizationId}");
        File::ensureDirectoryExists($directory);
        $file->move($directory, $filename);

        $media = Media::create([
            'organization_id' => $organizationId,
            'uploaded_by' => $request->user()->id,
            'original_name' => $originalName,
            'path' => "media/{$organizationId}/{$filename}",
            'mime_type' => $mimeType,
            'size' => $size,
        ]);

        if ($request->wantsJson()) {
            return response()->json($this->libraryPayload($request));
        }

        return back()->with('status', 'File uploaded.');
    }

    public function destroy(Request $request, Media $media): JsonResponse|RedirectResponse
    {
        abort_unless($media->organization_id === $request->user()->organization_id, 403);

        File::delete(public_path($media->path));
        $media->delete();

        if ($request->wantsJson()) {
            return response()->json($this->libraryPayload($request));
        }

        return back()->with('status', 'File deleted.');
    }

    private function libraryPayload(Request $request): array
    {
        $organizationId = $request->user()->organization_id;

        $items = Media::where('organization_id', $organizationId)
            ->latest()
            ->get()
            ->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url(),
                'name' => $m->original_name,
                'mime_type' => $m->mime_type,
                'size' => $m->size,
            ]);

        return [
            'items' => $items,
            'usedBytes' => (int) $items->sum('size'),
            'quotaBytes' => self::ORG_QUOTA_BYTES,
        ];
    }
}
