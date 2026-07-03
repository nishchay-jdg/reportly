<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Media Library') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-lg p-6"
                 x-data="mediaLibrary({
                    indexUrl: '{{ route('media.index') }}',
                    storeUrl: '{{ route('media.store') }}',
                    destroyUrlBase: '{{ url('/media-library') }}',
                    csrf: '{{ csrf_token() }}',
                 })" x-init="load()">

                <div class="flex items-center justify-between mb-1">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Shared across every project on your team — upload once, reuse everywhere.</p>
                    </div>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span x-text="uploading ? 'Uploading…' : 'Upload'"></span>
                        <input type="file" class="hidden" :disabled="uploading" @change="upload($event.target.files[0]); $event.target.value = ''" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.webm,.woff,.woff2,.ttf">
                    </label>
                </div>

                <div class="my-4">
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full bg-indigo-500" :style="`width:${quotaPercent()}%`"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1" x-text="`${formatSize(usedBytes)} of ${formatSize(quotaBytes)} used`"></p>
                </div>

                <p x-show="error" x-text="error" class="text-sm text-red-500 mb-3"></p>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
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
        </div>
    </div>

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
    </script>
</x-app-layout>
