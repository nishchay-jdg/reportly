<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Projects') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <div class="flex items-center justify-between gap-4">
                    <form method="POST" action="{{ route('projects.store') }}" class="flex items-end gap-3 flex-1">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="name" value="New project name" />
                            <x-text-input id="name" name="name" class="block w-full mt-1" placeholder="June SEO Report" required />
                        </div>
                        <select name="folder_id" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                            <option value="">No folder</option>
                            @foreach ($folders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                        <x-primary-button>{{ __('Create Project') }}</x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('folders.store') }}" class="flex items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="folder_name" value="New folder" />
                            <x-text-input id="folder_name" name="name" class="block w-full mt-1" placeholder="Client: Acme" required />
                        </div>
                        <x-secondary-button type="submit">{{ __('Add Folder') }}</x-secondary-button>
                    </form>
                </div>
            </div>

            @foreach ($folders as $folder)
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-800 dark:text-gray-200">📁 {{ $folder->name }}</h3>
                        <form method="POST" action="{{ route('folders.destroy', $folder) }}" onsubmit="return confirm('Delete this folder? Projects inside will become uncategorized.')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline">Delete folder</button>
                        </form>
                    </div>

                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($folder->projects as $project)
                            <li class="py-3 flex items-center justify-between">
                                <a href="{{ route('projects.edit', $project) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                    {{ $project->name }}
                                </a>
                                <span class="text-xs text-gray-400">{{ $project->updated_at->diffForHumans() }}</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-gray-400">No projects yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach

            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 dark:text-gray-200">Uncategorized</h3>
                <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($rootProjects as $project)
                        <li class="py-3 flex items-center justify-between">
                            <a href="{{ route('projects.edit', $project) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                {{ $project->name }}
                            </a>
                            <span class="text-xs text-gray-400">{{ $project->updated_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-400">No projects yet.</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
