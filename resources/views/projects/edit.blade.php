<x-app-layout>
    <div
        x-data="projectEditor({
            projectId: {{ $project->id }},
            files: {{ $project->files->map(fn($f) => ['id' => $f->id, 'filename' => $f->filename, 'type' => $f->type, 'content' => $f->content])->toJson() }},
            saveFileUrlBase: '{{ url('/projects/'.$project->id.'/files') }}',
            csrf: '{{ csrf_token() }}',
        })"
        @keydown.window="if (($event.metaKey || $event.ctrlKey) && $event.key === 's') { $event.preventDefault(); save(); }"
        @insert-media.window="insertMediaUrl($event.detail.url)"
        @restore-files.window="restoreFiles($event.detail.files)"
        class="flex flex-col h-[calc(100vh-4rem)]"
    >
        <!-- Toolbar -->
        <div class="flex items-center justify-between gap-4 px-4 py-2.5 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('projects.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0" title="Back to projects">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                </a>

                <form method="POST" action="{{ route('projects.update', $project) }}" class="min-w-0 flex items-center gap-2">
                    @csrf @method('PATCH')
                    <input
                        type="text" name="name" value="{{ $project->name }}"
                        onchange="this.form.submit()"
                        class="font-medium text-gray-800 dark:text-gray-100 bg-transparent border-0 border-b border-transparent hover:border-gray-300 dark:hover:border-gray-700 focus:border-indigo-400 focus:ring-0 px-0 py-0.5 w-40 sm:w-56 truncate"
                    >
                    <input
                        type="text" name="tag" value="{{ $project->tag }}" placeholder="+ tag"
                        onchange="this.form.submit()"
                        class="text-xs text-gray-400 bg-transparent border-0 border-b border-transparent hover:border-gray-300 dark:hover:border-gray-700 focus:border-indigo-400 focus:ring-0 px-0 py-0.5 w-20 hidden sm:block"
                    >
                </form>

                <span class="text-xs text-gray-400 hidden sm:inline" x-text="status"></span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button @click="save()" :disabled="status === 'Saving…'" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-6 0V3h6v4m-6 0h6" /></svg>
                    Save
                </button>
                <button x-data @click="$dispatch('open-modal', 'version-history')" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    History
                </button>
                <button x-data @click="$dispatch('open-modal', 'media-library')" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h16M4 4h16v16H4V4z" /></svg>
                    Media
                </button>
                <button x-data @click="$dispatch('open-modal', 'share-panel')" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342a4 4 0 100-2.684m0 2.684a4 4 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a4 4 0 105.367-5.367 4 4 0 00-5.367 5.367zm0 9.316a4 4 0 105.367 5.367 4 4 0 00-5.367-5.367z" /></svg>
                    Share
                    @if ($project->shares->where('is_active', true)->isNotEmpty())
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    @endif
                </button>
                <div class="relative" x-data="{ menuOpen: false }">
                    <button @click="menuOpen = !menuOpen" class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 100-4 2 2 0 000 4zM10 12a2 2 0 100-4 2 2 0 000 4zM10 18a2 2 0 100-4 2 2 0 000 4z" /></svg>
                    </button>
                    <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false" class="absolute right-0 mt-1 w-40 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg py-1 text-sm z-30">
                        <form method="POST" action="{{ route('projects.duplicate', $project) }}">
                            @csrf
                            <button class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">Duplicate</button>
                        </form>
                        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project permanently?')">
                            @csrf @method('DELETE')
                            <button class="w-full text-left px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 text-red-500">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="px-4 py-2 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm">
                {{ session('status') }}
            </div>
        @endif

        <!-- IDE body -->
        <div class="flex flex-1 min-h-0">
            <!-- File rail -->
            <div class="w-44 shrink-0 border-r border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60 flex flex-col">
                <div class="flex-1 overflow-y-auto py-2">
                    <template x-for="(file, idx) in files" :key="file.id">
                        <button
                            @click="switchFile(idx)"
                            class="w-full flex items-center gap-2 px-3 py-1.5 text-sm text-left truncate"
                            :class="idx === activeIndex ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/60'"
                        >
                            <span class="text-[10px] font-mono font-bold w-8 text-center shrink-0 rounded px-1 py-0.5"
                                  :class="{
                                    'bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300': file.type === 'html',
                                    'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300': file.type === 'css',
                                    'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-300': file.type === 'js',
                                  }"
                                  x-text="file.type.toUpperCase()"
                            ></span>
                            <span class="truncate" x-text="file.filename"></span>
                        </button>
                    </template>
                </div>
                <button @click="addFile()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline px-3 py-2.5 border-t border-gray-200 dark:border-gray-800 text-left">
                    + Add file
                </button>
            </div>

            <!-- Editor -->
            <div id="code-editor" class="flex-1 min-w-0 border-r border-gray-200 dark:border-gray-800 overflow-hidden"></div>

            <!-- Preview -->
            <div class="w-1/2 shrink-0 flex flex-col bg-white dark:bg-gray-950">
                <div class="flex items-center justify-between px-3 py-1.5 border-b border-gray-200 dark:border-gray-800 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span>Preview</span>
                    <button @click="updatePreview()" class="hover:text-gray-700 dark:hover:text-gray-200" title="Refresh preview">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                </div>
                <iframe x-ref="preview" class="w-full flex-1 bg-white" sandbox="allow-scripts"></iframe>
            </div>
        </div>
    </div>

    <x-modal name="share-panel" focusable max-width="xl">
        <div class="p-6" x-data="shareForm({
            storeUrl: '{{ route('shares.store', $project) }}',
            csrf: '{{ csrf_token() }}',
        })">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Share this report</h2>

            <!-- Just-created confirmation -->
            <div x-show="created" x-cloak class="mb-6 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200 mb-2">Link created!</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly :value="created?.url" class="flex-1 text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 bg-white">
                    <button @click="copyCreated()" class="shrink-0 text-sm px-3 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                    <button @click="dismissCreated()" class="shrink-0 text-sm px-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300">Done</button>
                </div>
            </div>

            <form @submit.prevent="submit($event)" x-show="!created" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <x-input-label value="Custom slug (optional)" />
                    <x-text-input name="slug" class="block w-full mt-1" placeholder="acme-june-report" />
                </div>
                <div x-data="{ needsPassword: false }">
                    <x-input-label value="Visibility" />
                    <select name="visibility" @change="needsPassword = $event.target.value === 'password'" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                        <option value="public">Public — anyone with the link</option>
                        <option value="password">Password protected</option>
                    </select>
                    <x-text-input x-show="needsPassword" name="password" type="password" class="mt-2 block w-full" placeholder="Password" />
                </div>
                <div>
                    <x-input-label value="Expires at (optional)" />
                    <input type="datetime-local" name="expires_at" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                </div>
                <div class="flex items-center gap-2 self-end pb-2">
                    <input type="checkbox" name="allow_guest_comments" value="1" checked id="allow_guest_comments" class="rounded border-gray-300">
                    <label for="allow_guest_comments" class="text-sm text-gray-600 dark:text-gray-300">Allow client comments</label>
                </div>
                <div class="sm:col-span-2">
                    <x-primary-button type="submit">Generate Link</x-primary-button>
                    <span x-show="error" x-text="error" class="text-sm text-red-500 ml-3"></span>
                </div>
            </form>

            <div class="divide-y divide-gray-100 dark:divide-gray-800 border-t border-gray-100 dark:border-gray-800 -mx-6 px-6">
                @forelse ($project->shares as $share)
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <a href="{{ route('share.view', $share->slug) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium truncate block text-sm">
                                {{ url('/r/'.$share->slug) }}
                            </a>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ ucfirst($share->visibility) }}
                                @if ($share->expires_at) &middot; expires {{ $share->expires_at->format('M j, Y g:ia') }} @endif
                                @unless ($share->is_active) &middot; <span class="text-red-500">disabled</span> @endunless
                                &middot; {{ $share->viewLogs()->count() }} views
                                @if ($share->approval_status === 'approved')
                                    &middot; <span class="text-green-600 dark:text-green-400">✓ Approved by {{ $share->approved_by_name }}</span>
                                @elseif ($share->approval_status === 'changes_requested')
                                    &middot; <span class="text-amber-600 dark:text-amber-400">Changes requested by {{ $share->approved_by_name }}</span>
                                @endif
                            </div>
                            @if ($share->approval_note)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">&ldquo;{{ $share->approval_note }}&rdquo;</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('shares.destroy', [$project, $share]) }}" onsubmit="return confirm('Revoke this link?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline whitespace-nowrap">Revoke</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 py-3">No share links yet.</p>
                @endforelse
            </div>
        </div>
    </x-modal>

    <x-modal name="media-library" focusable max-width="2xl">
        <div class="p-6" x-data="mediaLibrary({
            indexUrl: '{{ route('media.index') }}',
            storeUrl: '{{ route('media.store') }}',
            destroyUrlBase: '{{ url('/media-library') }}',
            csrf: '{{ csrf_token() }}',
        })" x-init="load()">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Media library</h2>
                <label class="cursor-pointer inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                    <input type="file" class="hidden" :disabled="uploading" @change="upload($event.target.files[0]); $event.target.value = ''" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.webm,.woff,.woff2,.ttf">
                </label>
            </div>

            <p class="text-xs text-gray-400 mb-4">Shared across every project on your team — upload once, reuse everywhere. Images, video, and fonts up to 5MB.</p>

            <div class="mb-4">
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full bg-indigo-500" :style="`width:${quotaPercent()}%`"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1" x-text="`${formatSize(usedBytes)} of ${formatSize(quotaBytes)} used`"></p>
            </div>

            <p x-show="error" x-text="error" class="text-sm text-red-500 mb-3"></p>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 max-h-96 overflow-y-auto">
                <template x-for="item in items" :key="item.id">
                    <div class="group relative border border-gray-100 dark:border-gray-800 rounded-lg overflow-hidden">
                        <div class="aspect-square bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
                            <template x-if="item.mime_type.startsWith('image/')">
                                <img :src="item.url" :alt="item.name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!item.mime_type.startsWith('image/')">
                                <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7l10 5-10 5z" /></svg>
                            </template>
                        </div>
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1.5 p-1">
                            <button @click="insert(item)" class="text-[11px] px-2 py-1 rounded bg-white/90 hover:bg-white text-gray-800 font-medium w-full">Insert</button>
                            <button @click="copy(item)" class="text-[11px] px-2 py-1 rounded bg-white/90 hover:bg-white text-gray-800 font-medium w-full">
                                <span x-text="copiedId === item.id ? 'Copied!' : 'Copy URL'"></span>
                            </button>
                            <button @click="remove(item)" class="text-[11px] px-2 py-1 rounded bg-white/90 hover:bg-red-50 text-red-600 font-medium w-full">Delete</button>
                        </div>
                        <p class="text-[10px] text-gray-400 truncate px-1 py-0.5" x-text="item.name"></p>
                    </div>
                </template>

                <template x-if="items.length === 0">
                    <p class="col-span-full text-sm text-gray-400 py-8 text-center">No media uploaded yet.</p>
                </template>
            </div>
        </div>
    </x-modal>

    <x-modal name="version-history" focusable max-width="lg">
        <div class="p-6" x-data="versionHistory({
            storeUrl: '{{ route('projects.versions.store', $project) }}',
            restoreUrlBase: '{{ url('/projects/'.$project->id.'/versions') }}',
            csrf: '{{ csrf_token() }}',
            initialVersions: {{ Illuminate\Support\Js::from($project->versions->map(fn ($v) => [
                'id' => $v->id,
                'label' => $v->label,
                'created_at' => $v->created_at->diffForHumans(),
                'author' => $v->creator?->name ?? 'Team member',
            ])) }},
        })">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Version history</h2>
                <button @click="saveSnapshot()" :disabled="saving" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white">
                    <span x-text="saving ? 'Saving…' : 'Save current version'"></span>
                </button>
            </div>
            <p class="text-xs text-gray-400 mb-4">Snapshots of this project's files you can name and roll back to. Restoring auto-saves a backup of what you had first.</p>

            <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-96 overflow-y-auto">
                <template x-for="version in versions" :key="version.id">
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="version.label || 'Unlabeled version'"></p>
                            <p class="text-xs text-gray-400" x-text="`${version.author} · ${version.created_at}`"></p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button @click="restore(version)" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Restore</button>
                            <button @click="remove(version)" class="text-xs text-red-500 hover:underline">Delete</button>
                        </div>
                    </div>
                </template>

                <template x-if="versions.length === 0">
                    <p class="text-sm text-gray-400 py-8 text-center">No saved versions yet.</p>
                </template>
            </div>
        </div>
    </x-modal>

    <style>
        #code-editor .cm-editor { height: 100%; font-size: 13px; }
        #code-editor .cm-scroller { overflow: auto; font-family: ui-monospace, monospace; }
    </style>
    <script>
        function mediaLibrary(config) {
            return {
                items: [],
                usedBytes: 0,
                quotaBytes: 1,
                uploading: false,
                error: null,
                copiedId: null,

                async load() {
                    const res = await fetch(config.indexUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.items = data.items;
                    this.usedBytes = data.usedBytes;
                    this.quotaBytes = data.quotaBytes;
                },

                async upload(file) {
                    if (!file) return;
                    this.uploading = true;
                    this.error = null;
                    const formData = new FormData();
                    formData.append('file', file);

                    const res = await fetch(config.storeUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: formData,
                    });

                    if (!res.ok) {
                        const data = await res.json().catch(() => null);
                        this.error = data?.message ?? 'Could not upload that file.';
                        this.uploading = false;
                        return;
                    }

                    const data = await res.json();
                    this.items = data.items;
                    this.usedBytes = data.usedBytes;
                    this.quotaBytes = data.quotaBytes;
                    this.uploading = false;
                },

                async remove(item) {
                    if (!confirm(`Delete "${item.name}"? Any project already using it will show a broken link.`)) return;

                    const res = await fetch(`${config.destroyUrlBase}/${item.id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.items = data.items;
                    this.usedBytes = data.usedBytes;
                    this.quotaBytes = data.quotaBytes;
                },

                insert(item) {
                    this.$dispatch('insert-media', { url: item.url });
                },

                copy(item) {
                    navigator.clipboard.writeText(item.url);
                    this.copiedId = item.id;
                    setTimeout(() => this.copiedId = null, 1500);
                },

                quotaPercent() {
                    return Math.min(100, (this.usedBytes / this.quotaBytes) * 100);
                },

                formatSize(bytes) {
                    if (bytes < 1024) return `${bytes} B`;
                    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
                    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
                },
            };
        }

        function shareForm(config) {
            return {
                created: null,
                copied: false,
                error: null,

                async submit(e) {
                    this.error = null;
                    const form = e.target;
                    const formData = new FormData(form);

                    const res = await fetch(config.storeUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: formData,
                    });

                    if (!res.ok) {
                        const data = await res.json().catch(() => null);
                        this.error = data?.message ?? 'Could not create the link. Please check the form.';
                        return;
                    }

                    this.created = await res.json();
                    form.reset();
                },

                copyCreated() {
                    if (!this.created) return;
                    navigator.clipboard.writeText(this.created.url);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 1500);
                },

                dismissCreated() {
                    // Reload so the newly created link shows up in the list below.
                    location.reload();
                },
            };
        }

        function versionHistory(config) {
            return {
                versions: config.initialVersions,
                saving: false,

                async saveSnapshot() {
                    const label = prompt('Name this version (optional):');
                    if (label === null) return; // cancelled

                    this.saving = true;
                    const res = await fetch(config.storeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: JSON.stringify({ label: label || null }),
                    });
                    this.saving = false;
                    if (!res.ok) { alert('Could not save a version.'); return; }
                    const version = await res.json();
                    this.versions.unshift(version);
                },

                async restore(version) {
                    if (!confirm(`Restore "${version.label || 'this version'}"? Your current files will be backed up first.`)) return;

                    const res = await fetch(`${config.restoreUrlBase}/${version.id}/restore`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    });
                    if (!res.ok) { alert('Could not restore that version.'); return; }
                    const data = await res.json();
                    window.dispatchEvent(new CustomEvent('restore-files', { detail: { files: data.files } }));
                    alert('Restored. A backup of your previous files was saved as a new version.');
                },

                async remove(version) {
                    if (!confirm('Delete this saved version? This cannot be undone.')) return;

                    const res = await fetch(`${config.restoreUrlBase}/${version.id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    });
                    if (!res.ok) return;
                    this.versions = this.versions.filter(v => v.id !== version.id);
                },
            };
        }

        function projectEditor(config) {
            return {
                projectId: config.projectId,
                files: config.files,
                activeIndex: 0,
                view: null,
                cmModules: null,
                langCompartment: null,
                status: '',
                saveTimer: null,

                async init() {
                    try {
                        // codemirror@6.0.1 internally resolves to @codemirror/state@6.7.0, @codemirror/view@6.43.4
                        // and @codemirror/language@6.12.4. Every other CodeMirror package pulled from esm.sh
                        // must be pinned to those exact versions via `deps=`, otherwise esm.sh serves a
                        // different instance of one of them — extensions then throw "Unrecognized extension
                        // value", or (worse, silently) the theme's syntax highlighting never actually attaches.
                        const DEPS = '@codemirror/state@6.7.0,@codemirror/view@6.43.4,@codemirror/language@6.12.4';
                        const [core, langHtml, langCss, langJs, theme, state] = await Promise.all([
                            import(`https://esm.sh/codemirror@6.0.1?deps=${DEPS}`),
                            import(`https://esm.sh/@codemirror/lang-html@6.4.9?deps=${DEPS}`),
                            import(`https://esm.sh/@codemirror/lang-css@6.3.1?deps=${DEPS}`),
                            import(`https://esm.sh/@codemirror/lang-javascript@6.2.2?deps=${DEPS}`),
                            import(`https://esm.sh/@codemirror/theme-one-dark@6.1.2?deps=${DEPS}`),
                            import('https://esm.sh/@codemirror/state@6.7.0'),
                        ]);

                        this.cmModules = {
                            EditorView: core.EditorView,
                            basicSetup: core.basicSetup,
                            languages: { html: langHtml.html, css: langCss.css, js: langJs.javascript },
                            oneDark: theme.oneDark,
                            Compartment: state.Compartment,
                        };
                        this.langCompartment = new state.Compartment();

                        const isDark = document.documentElement.classList.contains('dark');
                        const firstFile = this.files[0];

                        this.view = new core.EditorView({
                            doc: firstFile?.content ?? '',
                            extensions: [
                                core.basicSetup,
                                this.langCompartment.of(this.languageExtension(firstFile?.type)),
                                isDark ? theme.oneDark : [],
                                core.EditorView.updateListener.of((update) => {
                                    if (update.docChanged) {
                                        this.files[this.activeIndex].content = update.state.doc.toString();
                                        this.updatePreview();
                                        this.queueSave();
                                    }
                                }),
                                core.EditorView.domEventHandlers({
                                    keydown: (event) => {
                                        if ((event.metaKey || event.ctrlKey) && event.key === 's') {
                                            event.preventDefault();
                                            this.save();
                                            return true;
                                        }
                                        return false;
                                    },
                                }),
                            ],
                            parent: document.getElementById('code-editor'),
                        });

                        this.updatePreview();
                    } catch (e) {
                        console.error('Editor failed to load:', e);
                        this.status = 'Editor failed to load — check your connection and reload.';
                    }
                },

                languageExtension(type) {
                    const fn = this.cmModules.languages[type === 'js' ? 'js' : type];
                    return fn ? fn() : [];
                },

                switchFile(idx) {
                    this.activeIndex = idx;
                    const file = this.files[idx];
                    this.view.dispatch({
                        changes: { from: 0, to: this.view.state.doc.length, insert: file.content ?? '' },
                        effects: this.langCompartment.reconfigure(this.languageExtension(file.type)),
                    });
                },

                addFile() {
                    const filename = prompt('Filename (e.g. about.html, theme.css, helpers.js)');
                    if (!filename) return;
                    const ext = filename.split('.').pop();
                    const type = { html: 'html', css: 'css', js: 'js' }[ext];
                    if (!type) { alert('Only .html, .css, .js files are supported.'); return; }

                    fetch(`${config.saveFileUrlBase}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: JSON.stringify({ filename, type }),
                    })
                        .then(r => r.json())
                        .then(file => {
                            this.files.push(file);
                            this.switchFile(this.files.length - 1);
                        });
                },

                insertMediaUrl(url) {
                    if (!this.view) return;
                    const file = this.files[this.activeIndex];
                    const snippet = file.type === 'html' ? `<img src="${url}" alt="">` : `url('${url}')`;
                    const pos = this.view.state.selection.main.head;
                    this.view.dispatch({ changes: { from: pos, insert: snippet } });
                    this.view.focus();
                },

                restoreFiles(restoredFiles) {
                    // Restoring a version can add/remove files (e.g. rolling back to before a
                    // file existed), so replace the whole array rather than patching in place.
                    this.files = restoredFiles;
                    this.activeIndex = Math.min(this.activeIndex, this.files.length - 1);

                    if (this.view) {
                        const file = this.files[this.activeIndex];
                        this.view.dispatch({
                            changes: { from: 0, to: this.view.state.doc.length, insert: file?.content ?? '' },
                            effects: this.langCompartment.reconfigure(this.languageExtension(file?.type)),
                        });
                    }

                    this.updatePreview();
                },

                queueSave() {
                    this.status = 'Editing…';
                    clearTimeout(this.saveTimer);
                    this.saveTimer = setTimeout(() => this.save(), 700);
                },

                save() {
                    clearTimeout(this.saveTimer);
                    const file = this.files[this.activeIndex];
                    this.status = 'Saving…';
                    fetch(`${config.saveFileUrlBase}/${file.id}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: JSON.stringify({ content: file.content }),
                    })
                        .then(() => { this.status = 'Saved'; setTimeout(() => this.status = '', 1500); });
                },

                updatePreview() {
                    const html = this.files.find(f => f.type === 'html')?.content ?? '';
                    const css = this.files.filter(f => f.type === 'css').map(f => f.content).join('\n');
                    const js = this.files.filter(f => f.type === 'js').map(f => f.content).join('\n');

                    // Not every project's HTML has <head>/<body> tags (e.g. bare snippets/fragments),
                    // so fall back to prepending/appending the CSS/JS when those anchors are missing.
                    let doc = html.includes('</head>')
                        ? html.replace('</head>', `<style>${css}<\/style></head>`)
                        : `<style>${css}<\/style>` + html;

                    doc = doc.includes('</body>')
                        ? doc.replace('</body>', `<script>${js}<\/script></body>`)
                        : doc + `<script>${js}<\/script>`;

                    this.$refs.preview.srcdoc = doc;
                },
            };
        }
    </script>
</x-app-layout>
