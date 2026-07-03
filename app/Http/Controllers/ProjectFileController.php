<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectFileController extends Controller
{
    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:html,css,js'],
        ]);

        $file = $project->files()->create([
            ...$data,
            'content' => '',
            'sort_order' => $project->files()->max('sort_order') + 1,
        ]);

        return response()->json($file);
    }

    public function update(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        abort_unless($file->project_id === $project->id, 404);

        $data = $request->validate([
            'content' => ['nullable', 'string'],
        ]);

        $file->update($data);

        return response()->json(['saved' => true, 'updated_at' => $file->updated_at]);
    }

    public function destroy(Project $project, ProjectFile $file): JsonResponse
    {
        abort_unless($file->project_id === $project->id, 404);

        $file->delete();

        return response()->json(['deleted' => true]);
    }
}
