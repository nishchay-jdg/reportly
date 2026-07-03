<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Projects') }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('media.page') }}">
                    <x-secondary-button type="button">Media</x-secondary-button>
                </a>
                <x-secondary-button x-data @click="$dispatch('open-modal', 'new-folder')">+ Folder</x-secondary-button>
                <x-primary-button x-data @click="$dispatch('open-modal', 'new-project')">+ New Project</x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ viewMode: localStorage.getItem('projectsViewMode') || 'grid' }" x-init="$watch('viewMode', v => localStorage.setItem('projectsViewMode', v))">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Search + sort + view toggle -->
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('projects.index') }}" class="flex-1 max-w-sm">
                    <input type="hidden" name="folder" value="{{ $folderFilter }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="tag" value="{{ $tagFilter }}">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        <input
                            type="text" name="q" value="{{ $search }}" placeholder="Search reports by name…"
                            x-on:input.debounce.400ms="$el.form.submit()"
                            class="w-full pl-9 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm"
                        >
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('projects.index') }}">
                        <input type="hidden" name="folder" value="{{ $folderFilter }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                        <input type="hidden" name="tag" value="{{ $tagFilter }}">
                        <select name="sort" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                            <option value="updated_desc" @selected($sort === 'updated_desc')>Recently updated</option>
                            <option value="created_desc" @selected($sort === 'created_desc')>Recently created</option>
                            <option value="name_asc" @selected($sort === 'name_asc')>Name A&ndash;Z</option>
                            <option value="name_desc" @selected($sort === 'name_desc')>Name Z&ndash;A</option>
                        </select>
                    </form>

                    <div class="flex items-center rounded-md border border-gray-300 dark:border-gray-700 overflow-hidden">
                        <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'" class="p-2" title="Grid view">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z" /></svg>
                        </button>
                        <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'" class="p-2 border-l border-gray-300 dark:border-gray-700" title="List view">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Folder filter pills -->
            <div class="flex items-center gap-2 flex-wrap">
                @php
                    $pillBase = 'inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border';
                    $pillActive = 'bg-indigo-600 text-white border-indigo-600';
                    $pillInactive = 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800';
                @endphp
                <a href="{{ route('projects.index', ['q' => $search, 'sort' => $sort, 'tag' => $tagFilter]) }}" class="{{ $pillBase }} {{ $folderFilter === '' ? $pillActive : $pillInactive }}">
                    All <span class="opacity-70">{{ $totalCount }}</span>
                </a>
                <a href="{{ route('projects.index', ['folder' => 'none', 'q' => $search, 'sort' => $sort, 'tag' => $tagFilter]) }}" class="{{ $pillBase }} {{ $folderFilter === 'none' ? $pillActive : $pillInactive }}">
                    Uncategorized <span class="opacity-70">{{ $uncategorizedCount }}</span>
                </a>
                @foreach ($folders as $folder)
                    <a href="{{ route('projects.index', ['folder' => $folder->id, 'q' => $search, 'sort' => $sort, 'tag' => $tagFilter]) }}" class="{{ $pillBase }} {{ (string) $folderFilter === (string) $folder->id ? $pillActive : $pillInactive }}">
                        📁 {{ $folder->name }} <span class="opacity-70">{{ $folder->projects_count }}</span>
                    </a>
                @endforeach
            </div>

            @if ($tags->isNotEmpty())
                <div class="flex items-center gap-2 flex-wrap -mt-2">
                    <span class="text-xs text-gray-400">Tags:</span>
                    @foreach ($tags as $tagOption)
                        <a href="{{ route('projects.index', ['q' => $search, 'sort' => $sort, 'folder' => $folderFilter, 'tag' => $tagFilter === $tagOption ? '' : $tagOption]) }}"
                           class="text-xs px-2.5 py-1 rounded-full border {{ $tagFilter === $tagOption ? 'bg-gray-800 text-white border-gray-800 dark:bg-gray-200 dark:text-gray-900' : 'text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                            #{{ $tagOption }}
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Results -->
            @if ($projects->isEmpty())
                <div class="border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-lg py-12 text-center">
                    @if ($search || $folderFilter || $tagFilter)
                        <p class="text-gray-400 text-sm">No reports match your filters.</p>
                    @else
                        <p class="text-gray-400 text-sm mb-3">No projects yet — create your first client report.</p>
                        <x-primary-button x-data @click="$dispatch('open-modal', 'new-project')">+ New Project</x-primary-button>
                    @endif
                </div>
            @else
                <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($projects as $project)
                        <x-project-card :project="$project" view="grid" />
                    @endforeach
                </div>

                <div x-show="viewMode === 'list'" class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-100 dark:border-gray-800 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
                    @foreach ($projects as $project)
                        <x-project-card :project="$project" view="list" />
                    @endforeach
                </div>

                <div>
                    {{ $projects->onEachSide(1)->links() }}
                </div>
            @endif

        </div>
    </div>

    <x-modal name="new-project" focusable max-width="lg">
        <form method="POST" action="{{ route('projects.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">New Project</h2>
            <p class="text-sm text-gray-500 mt-1">Pick a starting point, then customize it right away.</p>

            <div class="mt-5">
                <x-input-label value="Start from" />
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-1" x-data="{ template: 'blank' }">
                    @foreach (\App\Support\ProjectTemplates::definitions() as $key => $definition)
                        <label class="cursor-pointer border rounded-md p-2.5 text-xs"
                               :class="template === '{{ $key }}' ? 'border-indigo-500 ring-1 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                            <input type="radio" name="template" value="{{ $key }}" x-model="template" class="hidden" @if($key === 'blank') checked @endif>
                            <span class="block font-medium text-gray-800 dark:text-gray-100">{{ $definition['name'] }}</span>
                            <span class="block text-gray-400 mt-0.5">{{ $definition['description'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="modal_project_name" value="Project name" />
                <x-text-input id="modal_project_name" name="name" class="block w-full mt-1" placeholder="June SEO Report" required autofocus />
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <x-input-label for="modal_folder_id" value="Folder (optional)" />
                    <select id="modal_folder_id" name="folder_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                        <option value="">No folder</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="modal_tag" value="Tag (optional)" />
                    <x-text-input id="modal_tag" name="tag" class="block w-full mt-1" placeholder="client-acme" list="existing-tags" />
                    <datalist id="existing-tags">
                        @foreach ($tags as $tagOption)
                            <option value="{{ $tagOption }}">
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" type="button">Cancel</x-secondary-button>
                <x-primary-button type="submit">Create Project</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="new-folder" focusable max-width="sm">
        <form method="POST" action="{{ route('folders.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">New Folder</h2>
            <p class="text-sm text-gray-500 mt-1">Group projects by client or campaign.</p>

            <div class="mt-6">
                <x-input-label for="modal_folder_name" value="Folder name" />
                <x-text-input id="modal_folder_name" name="name" class="block w-full mt-1" placeholder="Client: Acme" required autofocus />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" type="button">Cancel</x-secondary-button>
                <x-primary-button type="submit">Add Folder</x-primary-button>
            </div>
        </form>

        @if ($folders->isNotEmpty())
            <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Existing folders</p>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($folders as $folder)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-700 dark:text-gray-200">📁 {{ $folder->name }} <span class="text-gray-400">({{ $folder->projects_count }})</span></span>
                            <form method="POST" action="{{ route('folders.destroy', $folder) }}" onsubmit="return confirm('Delete this folder? Projects inside will become uncategorized.')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Delete</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-modal>
</x-app-layout>
