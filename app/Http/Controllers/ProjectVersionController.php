<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectVersionController extends Controller
{
    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $project->load('files');

        $version = $project->versions()->create([
            'created_by' => $request->user()->id,
            'label' => $data['label'] ?? null,
        ]);

        $version->files()->createMany(
            $project->files->map(fn ($file) => [
                'filename' => $file->filename,
                'type' => $file->type,
                'content' => $file->content,
                'sort_order' => $file->sort_order,
            ])->all()
        );

        return response()->json([
            'id' => $version->id,
            'label' => $version->label,
            'created_at' => $version->created_at->diffForHumans(),
            'author' => $version->creator?->name ?? 'Team member',
        ]);
    }

    public function restore(Request $request, Project $project, ProjectVersion $version): JsonResponse
    {
        abort_unless($version->project_id === $project->id, 404);

        // Safety net: snapshot the current state before overwriting it, so a restore is
        // itself undoable by restoring "just before this restore".
        $safetyVersion = $project->versions()->create([
            'created_by' => $request->user()->id,
            'label' => 'Before restoring '.($version->label ?: $version->created_at->format('M j, g:ia')),
        ]);
        $safetyVersion->files()->createMany(
            $project->files->map(fn ($file) => [
                'filename' => $file->filename,
                'type' => $file->type,
                'content' => $file->content,
                'sort_order' => $file->sort_order,
            ])->all()
        );

        foreach ($version->files as $versionFile) {
            $project->files()->updateOrCreate(
                ['filename' => $versionFile->filename],
                [
                    'type' => $versionFile->type,
                    'content' => $versionFile->content,
                    'sort_order' => $versionFile->sort_order,
                ]
            );
        }

        return response()->json([
            'files' => $project->files()->orderBy('sort_order')->get(['id', 'filename', 'type', 'content']),
        ]);
    }

    public function destroy(Project $project, ProjectVersion $version): JsonResponse
    {
        abort_unless($version->project_id === $project->id, 404);

        $version->delete();

        return response()->json(['deleted' => true]);
    }
}
