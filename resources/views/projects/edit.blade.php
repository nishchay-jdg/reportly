<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $project->name }}
            </h2>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project permanently?')">
                @csrf @method('DELETE')
                <button class="text-sm text-red-500 hover:underline">Delete project</button>
            </form>
        </div>
    </x-slot>

    <div class="py-6" x-data="projectEditor({
            projectId: {{ $project->id }},
            files: {{ $project->files->map(fn($f) => ['id' => $f->id, 'filename' => $f->filename, 'type' => $f->type, 'content' => $f->content])->toJson() }},
            saveFileUrlBase: '{{ url('/projects/'.$project->id.'/files') }}',
            csrf: '{{ csrf_token() }}',
        })" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-2 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Editor + Preview -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-4 py-2">
                    <div class="flex gap-1 overflow-x-auto">
                        <template x-for="(file, idx) in files" :key="file.id">
                            <button
                                @click="switchFile(idx)"
                                class="px-3 py-1.5 text-sm rounded-t-md whitespace-nowrap"
                                :class="idx === activeIndex ? 'bg-gray-100 dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                x-text="file.filename"
                            ></button>
                        </template>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400" x-text="status"></span>
                        <button @click="addFile()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Add file</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 h-[560px]">
                    <div id="monaco-editor" class="border-r border-gray-100 dark:border-gray-700 h-full"></div>
                    <iframe x-ref="preview" class="w-full h-full bg-white" sandbox="allow-scripts"></iframe>
                </div>
            </div>

            <!-- Share links -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 dark:text-gray-200 mb-4">Share Links</h3>

                <form method="POST" action="{{ route('shares.store', $project) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end mb-6">
                    @csrf
                    <div>
                        <x-input-label value="Custom slug (optional)" />
                        <x-text-input name="slug" class="block w-full mt-1" placeholder="acme-june-report" />
                    </div>
                    <div>
                        <x-input-label value="Visibility" />
                        <select name="visibility" x-data @change="$el.nextElementSibling.classList.toggle('hidden', $el.value !== 'password')" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm">
                            <option value="public">Public</option>
                            <option value="password">Password protected</option>
                        </select>
                        <x-text-input name="password" type="password" class="hidden mt-2 block w-full" placeholder="Password" />
                    </div>
                    <div>
                        <x-input-label value="Expires at (optional)" />
                        <input type="datetime-local" name="expires_at" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="allow_guest_comments" value="1" checked id="allow_guest_comments" class="rounded border-gray-300">
                        <label for="allow_guest_comments" class="text-sm text-gray-600 dark:text-gray-300">Allow client comments</label>
                    </div>
                    <x-primary-button>{{ __('Generate Link') }}</x-primary-button>
                </form>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($project->shares as $share)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <a href="{{ route('share.view', $share->slug) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium truncate block">
                                    {{ url('/r/'.$share->slug) }}
                                </a>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ ucfirst($share->visibility) }}
                                    @if ($share->expires_at) &middot; expires {{ $share->expires_at->format('M j, Y g:ia') }} @endif
                                    @unless ($share->is_active) &middot; <span class="text-red-500">disabled</span> @endunless
                                    &middot; {{ $share->viewLogs()->count() }} views
                                </div>
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

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js"></script>
    <script>
        function projectEditor(config) {
            return {
                projectId: config.projectId,
                files: config.files,
                activeIndex: 0,
                editor: null,
                monacoInstance: null,
                status: '',
                saveTimer: null,

                init() {
                    require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs' } });
                    require(['vs/editor/editor.main'], (monaco) => {
                        this.monacoInstance = monaco;
                        this.editor = monaco.editor.create(document.getElementById('monaco-editor'), {
                            value: this.files[0]?.content ?? '',
                            language: this.languageFor(this.files[0]?.type),
                            theme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'vs-dark' : 'vs',
                            automaticLayout: true,
                            minimap: { enabled: false },
                            fontSize: 13,
                        });

                        this.editor.onDidChangeModelContent(() => {
                            this.files[this.activeIndex].content = this.editor.getValue();
                            this.updatePreview();
                            this.queueSave();
                        });

                        this.updatePreview();
                    });
                },

                languageFor(type) {
                    return { html: 'html', css: 'css', js: 'javascript' }[type] ?? 'plaintext';
                },

                switchFile(idx) {
                    this.activeIndex = idx;
                    this.editor.setValue(this.files[idx].content ?? '');
                    this.monacoInstance.editor.setModelLanguage(this.editor.getModel(), this.languageFor(this.files[idx].type));
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

                queueSave() {
                    this.status = 'Editing…';
                    clearTimeout(this.saveTimer);
                    this.saveTimer = setTimeout(() => this.save(), 700);
                },

                save() {
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

                    const doc = html
                        .replace('</head>', `<style>${css}<\/style></head>`)
                        .replace('</body>', `<script>${js}<\/script></body>`);

                    this.$refs.preview.srcdoc = doc;
                },
            };
        }
    </script>
</x-app-layout>
