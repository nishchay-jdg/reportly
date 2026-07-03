<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $folders = Folder::whereNull('parent_id')
            ->with(['children', 'projects' => fn ($q) => $q->latest()])
            ->orderBy('name')
            ->get();

        $rootProjects = Project::whereNull('folder_id')->latest()->get();

        return view('projects.index', compact('folders', 'rootProjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:folders,id'],
        ]);

        $project = Project::create([
            'name' => $data['name'],
            'folder_id' => $data['folder_id'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $project->files()->createMany([
            ['filename' => 'index.html', 'type' => 'html', 'sort_order' => 0, 'content' => "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <title>{$project->name}</title>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>{$project->name}</h1>\n  <script src=\"script.js\"></script>\n</body>\n</html>\n"],
            ['filename' => 'style.css', 'type' => 'css', 'sort_order' => 1, 'content' => "body {\n  font-family: sans-serif;\n}\n"],
            ['filename' => 'script.js', 'type' => 'js', 'sort_order' => 2, 'content' => "console.log('ready');\n"],
        ]);

        return redirect()->route('projects.edit', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project): View
    {
        $project->load('files', 'shares');

        return view('projects.edit', compact('project'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }
}
