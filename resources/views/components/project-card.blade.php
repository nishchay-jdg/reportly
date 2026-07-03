@props(['project', 'view' => 'grid'])

@if ($view === 'list')
    <a href="{{ route('projects.edit', $project) }}" class="group flex items-center gap-4 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h4 class="font-medium text-gray-800 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate">
                    {{ $project->name }}
                </h4>
                @if ($project->tag)
                    <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400">#{{ $project->tag }}</span>
                @endif
                @if ($project->shares_count > 0)
                    <span class="shrink-0 text-[11px] font-medium bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded">
                        Shared
                    </span>
                @endif
            </div>
            @if ($project->folder)
                <p class="text-xs text-gray-400 truncate">in {{ $project->folder->name }}</p>
            @endif
        </div>
        <div class="hidden sm:flex items-center gap-1.5 shrink-0">
            <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300">HTML</span>
            <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">CSS</span>
            <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-300">JS</span>
        </div>
        <p class="text-xs text-gray-400 shrink-0 w-32 text-right">Updated {{ $project->updated_at->diffForHumans() }}</p>
    </a>
@else
    <a href="{{ route('projects.edit', $project) }}" class="group block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-4 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-sm transition">
        <div class="flex items-start justify-between">
            <h4 class="font-medium text-gray-800 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate pr-2">
                {{ $project->name }}
            </h4>
            @if ($project->shares_count > 0)
                <span class="shrink-0 text-[11px] font-medium bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded">
                    Shared
                </span>
            @endif
        </div>

        @if ($project->folder)
            <p class="text-xs text-gray-400 truncate mt-0.5">in {{ $project->folder->name }}</p>
        @endif

        <div class="mt-3 flex items-center gap-1.5 flex-wrap">
            <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300">HTML</span>
            <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">CSS</span>
            <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-300">JS</span>
            @if ($project->tag)
                <span class="text-[10px] text-gray-400">#{{ $project->tag }}</span>
            @endif
        </div>

        <p class="mt-3 text-xs text-gray-400">Updated {{ $project->updated_at->diffForHumans() }}</p>
    </a>
@endif
