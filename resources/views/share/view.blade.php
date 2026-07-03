@php
    $brand = $share->project->organization->brandKit;
    $html = $share->project->files->firstWhere('type', 'html')?->content ?? '';
    $css = $share->project->files->where('type', 'css')->pluck('content')->join("\n");
    $js = $share->project->files->where('type', 'js')->pluck('content')->join("\n");
    $doc = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
    $doc = str_replace('</body>', '<script>'.$js.'</script></body>', $doc);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $share->project->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        :root { --brand-primary: {{ $brand->primary_color ?? '#2563eb' }}; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen" x-data="shareViewer({
        shareSlug: '{{ $share->slug }}',
        commentUrl: '{{ route('share.comments.store', $share->slug) }}',
        csrf: '{{ csrf_token() }}',
        allowComments: {{ $share->allow_guest_comments ? 'true' : 'false' }},
        initialPins: {{ $comments->whereNotNull('position_x')->values()->map(fn($c) => ['id' => $c->id, 'position_x' => $c->position_x, 'position_y' => $c->position_y])->toJson() }},
    })">

    <header class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            @if ($brand?->logo_path)
                <img src="{{ asset('storage/'.$brand->logo_path) }}" class="h-7" alt="logo">
            @endif
            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $share->project->name }}</span>
        </div>
        <button @click="commentMode = !commentMode" x-show="allowComments"
                class="text-sm px-3 py-1.5 rounded-md border"
                :class="commentMode ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600'">
            <span x-text="commentMode ? 'Exit comment mode' : '💬 Add comment'"></span>
        </button>
    </header>

    <main class="max-w-6xl mx-auto p-6">
        <div class="relative bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden" :class="{ 'cursor-crosshair': commentMode }" @click="handleClick($event)">
            <iframe srcdoc="{{ $doc }}" class="w-full border-0" x-bind:style="commentMode ? 'height:75vh;pointer-events:none' : 'height:75vh;pointer-events:auto'" sandbox="allow-scripts"></iframe>

            <template x-for="pin in pins" :key="pin.id">
                <div class="absolute w-5 h-5 -ml-2.5 -mt-2.5 bg-indigo-600 text-white text-xs rounded-full flex items-center justify-center shadow"
                     :style="`left:${pin.position_x}%; top:${pin.position_y}%`">
                    💬
                </div>
            </template>
        </div>

        <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="font-medium text-gray-800 dark:text-gray-100 mb-4">Comments</h3>
            <div class="space-y-4">
                @forelse ($comments as $comment)
                    <div class="border border-gray-100 dark:border-gray-700 rounded-md p-3">
                        <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                            <span>{{ $comment->authorName() }} &middot; {{ $comment->created_at->diffForHumans() }}</span>
                            @auth
                                <form method="POST" action="{{ route('comments.resolve', $comment) }}">
                                    @csrf
                                    <button class="text-indigo-500 hover:underline">{{ $comment->is_resolved ? 'Reopen' : 'Resolve' }}</button>
                                </form>
                            @endauth
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ $comment->body }}</p>
                        @if ($comment->is_resolved)
                            <span class="inline-block mt-1 text-xs text-green-600">Resolved</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No comments yet.</p>
                @endforelse
            </div>

            @if ($share->allow_guest_comments)
                <form method="POST" action="{{ route('share.comments.store', $share->slug) }}" class="mt-6 space-y-3">
                    @csrf
                    @guest
                        <input type="text" name="guest_name" placeholder="Your name" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                        <input type="email" name="guest_email" placeholder="Email (optional)" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    @endguest
                    <textarea name="body" rows="2" placeholder="Leave a comment…" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm"></textarea>
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md">Post Comment</button>
                </form>
            @endif
        </div>

        @if ($brand?->footer_text)
            <p class="text-center text-xs text-gray-400 mt-6">{{ $brand->footer_text }}</p>
        @endif
    </main>

    <script>
        function shareViewer(config) {
            return {
                commentMode: false,
                pins: config.initialPins,
                allowComments: config.allowComments,
                handleClick(e) {
                    if (!this.commentMode) return;
                    const rect = e.currentTarget.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) * 100;
                    const y = ((e.clientY - rect.top) / rect.height) * 100;
                    const body = prompt('Comment:');
                    if (!body) return;
                    const name = config.allowComments ? (localStorage.getItem('guest_name') || prompt('Your name:')) : null;
                    if (name) localStorage.setItem('guest_name', name);

                    fetch(config.commentUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        body: JSON.stringify({ body, position_x: x, position_y: y, guest_name: name }),
                    }).then(() => location.reload());
                },
            };
        }
    </script>
</body>
</html>
