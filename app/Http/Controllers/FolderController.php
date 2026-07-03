<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
        ]);

        Folder::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Folder created.');
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder->update($data);

        return back()->with('status', 'Folder renamed.');
    }

    public function destroy(Folder $folder): RedirectResponse
    {
        $folder->delete();

        return back()->with('status', 'Folder deleted.');
    }
}
