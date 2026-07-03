<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Project;
use App\Support\ProjectTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        // Folders with project counts for the filter bar — not their actual project lists,
        // since a folder full of hundreds of reports has no business being fetched just to
        // render a chip. The projects themselves are paginated in the main query below.
        $folders = Folder::whereNull('parent_id')
            ->withCount('projects')
            ->orderBy('name')
            ->get();

        $sort = $request->string('sort', 'updated_desc')->value();
        $folderFilter = $request->string('folder')->value();
        $tagFilter = $request->string('tag')->value();
        $search = $request->string('q')->value();

        $projects = Project::query()
            ->with('folder')
            ->withCount('shares')
            ->when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($folderFilter === 'none', fn ($q) => $q->whereNull('folder_id'))
            ->when($folderFilter && $folderFilter !== 'none', fn ($q) => $q->where('folder_id', $folderFilter))
            ->when($tagFilter, fn ($q) => $q->where('tag', $tagFilter))
            ->when($sort === 'name_asc', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'name_desc', fn ($q) => $q->orderBy('name', 'desc'))
            ->when($sort === 'created_desc', fn ($q) => $q->latest('created_at'))
            ->when($sort === 'updated_desc' || ! $sort, fn ($q) => $q->latest('updated_at'))
            ->paginate(24)
            ->withQueryString();

        // Counts for the "All" / "Uncategorized" filter pills — deliberately not filtered by
        // the current search/folder selection, so the pills always reflect the true totals.
        $totalCount = Project::count();
        $uncategorizedCount = Project::whereNull('folder_id')->count();

        $tags = Project::query()->whereNotNull('tag')->distinct()->orderBy('tag')->pluck('tag');

        return view('projects.index', compact(
            'folders', 'projects', 'sort', 'folderFilter', 'search', 'totalCount', 'uncategorizedCount',
            'tags', 'tagFilter'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'tag' => ['nullable', 'string', 'max:60'],
            'template' => ['nullable', 'string'],
        ]);

        $project = Project::create([
            'name' => $data['name'],
            'folder_id' => $data['folder_id'] ?? null,
            'tag' => $data['tag'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $files = ProjectTemplates::files($data['template'] ?? null, $project->name);

        $project->files()->createMany($files);

        return redirect()->route('projects.edit', $project)->with('status', 'Project created.');
    }

    public function edit(Project $project): View
    {
        $project->load('files', 'shares', 'versions.creator');

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:60'],
        ]);

        $project->update($data);

        return back()->with('status', 'Project updated.');
    }

    public function duplicate(Request $request, Project $project): RedirectResponse
    {
        $project->load('files');

        $copy = Project::create([
            'name' => $project->name.' (copy)',
            'folder_id' => $project->folder_id,
            'tag' => $project->tag,
            'created_by' => $request->user()->id,
        ]);

        $copy->files()->createMany(
            $project->files->map(fn ($file) => [
                'filename' => $file->filename,
                'type' => $file->type,
                'content' => $file->content,
                'sort_order' => $file->sort_order,
            ])->all()
        );

        return redirect()->route('projects.edit', $copy)->with('status', 'Project duplicated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }
}
