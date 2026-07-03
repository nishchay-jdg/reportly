@php
    $brand = $share->project->organization->brandKit;
    $doc = $share->project->renderPreviewHtml(reportHeight: true);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $share->project->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --brand-primary: {{ $brand->primary_color ?? '#2563eb' }}; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen" x-data="shareViewer({
        shareSlug: '{{ $share->slug }}',
        commentUrl: '{{ route('share.comments.store', $share->slug) }}',
        commentsIndexUrl: '{{ route('share.comments.index', $share->slug) }}',
        resolveUrlBase: '{{ url('/comments') }}',
        agreementShowUrl: '{{ route('share.agreement.show', $share->slug) }}',
        agreementStoreUrl: '{{ route('share.agreement.store', $share->slug) }}',
        shareViewUrl: '{{ route('share.view', $share->slug) }}',
        csrf: '{{ csrf_token() }}',
        allowComments: {{ $share->allow_guest_comments ? 'true' : 'false' }},
        isTeamMember: {{ auth()->check() ? 'true' : 'false' }},
        currentUserName: {{ auth()->check() ? json_encode(auth()->user()->name) : 'null' }},
        initialPins: {{ Illuminate\Support\Js::from($pins) }},
    })">

    <header class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            @if ($brand?->logo_path)
                <img src="{{ asset('storage/'.$brand->logo_path) }}" class="h-7" alt="logo">
            @endif
            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $share->project->name }}</span>
        </div>

        <div class="relative" x-data="{ open: false }">
            @if ($share->approval_status === 'approved')
                <button @click="open = !open" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Approved
                </button>
            @elseif ($share->approval_status === 'changes_requested')
                <button @click="open = !open" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    Changes requested
                </button>
            @else
                <button @click="open = !open" class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
                    Review &amp; approve
                </button>
            @endif

            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-xl p-4 z-30">
                @if ($share->approval_status !== 'pending')
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        Last update: <strong>{{ $share->approved_by_name }}</strong> &middot; {{ $share->approved_at?->diffForHumans() }}
                        @if ($share->approval_note)
                            <br><span class="italic">&ldquo;{{ $share->approval_note }}&rdquo;</span>
                        @endif
                    </p>
                @endif
                <form method="POST" action="{{ route('share.approve', $share->slug) }}" class="space-y-2">
                    @csrf
                    <input type="text" name="name" placeholder="Your name" required
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <textarea name="note" rows="2" placeholder="Note (optional)"
                              class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"></textarea>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" name="status" value="approved" class="flex-1 text-sm px-3 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white">Approve</button>
                        <button type="submit" name="status" value="changes_requested" class="flex-1 text-sm px-3 py-2 rounded-md bg-amber-500 hover:bg-amber-600 text-white">Request changes</button>
                    </div>
                </form>
            </div>
        </div>
    </header>

    <main class="w-full p-6 pb-28">
        <div id="report-canvas" class="relative bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
             :class="{ 'cursor-comment': tool === 'comment' }"
             @click="handleClick($event)">
            <iframe x-ref="reportFrame" srcdoc="{{ $doc }}" class="w-full border-0 block"
                    x-bind:style="`height:${frameHeight}px; pointer-events:${tool === 'select' ? 'auto' : 'none'}`"
                    sandbox="allow-scripts"></iframe>

            <template x-for="pin in pins" :key="pin.id">
                <div class="absolute w-7 h-7 -ml-3.5 -mt-3.5 rounded-full flex items-center justify-center shadow-md ring-2 cursor-pointer"
                     :class="openThreadId === pin.id ? 'ring-indigo-500' : 'ring-white dark:ring-gray-800'"
                     :style="`left:${pin.position_x}%; top:${pin.position_y}%; background-color:${pin.is_resolved ? '#9ca3af' : avatarColor(pin.author)}`"
                     :title="pin.author"
                     @click.stop="openThread(pin)">
                    <span x-text="initials(pin.author)" x-show="!pin.is_resolved"></span>
                    <svg x-show="pin.is_resolved" class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </div>
            </template>

            <!-- Inline new-comment composer bubble -->
            <div x-show="draft" x-cloak
                 class="absolute z-10 bg-gray-900 text-white rounded-2xl shadow-xl p-2 w-72"
                 :style="draft ? popoverStyle(draft.x, draft.y) : ''"
                 @click.outside="draft = null" @click.stop
            >
                <div class="flex items-center gap-2">
                    <input x-ref="draftInput" x-model="draftBody" @keydown.enter="submitDraft()" @keydown.escape="draft = null"
                           type="text" placeholder="Add a comment" autofocus :disabled="submitting"
                           class="flex-1 bg-transparent border-0 focus:ring-0 text-sm placeholder-gray-400 text-white disabled:opacity-50">
                    <button @click="submitDraft()" :disabled="submitting" class="shrink-0 h-8 w-8 rounded-full bg-gray-700 hover:bg-indigo-600 disabled:opacity-50 flex items-center justify-center">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    </button>
                </div>
                <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-400 px-2 pb-1"></p>
            </div>

            <!-- Thread popover -->
            <template x-if="openThread_">
                <div x-cloak
                     class="absolute z-20 bg-gray-900 text-white rounded-xl shadow-2xl w-80"
                     :style="popoverStyle(openThread_.position_x, openThread_.position_y)"
                     @click.outside="openThreadId = null" @click.stop>

                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
                        <span class="font-medium text-sm">Comment</span>
                        <div class="flex items-center gap-1">
                            <div class="relative" x-data="{ menuOpen: false }">
                                <button @click="menuOpen = !menuOpen" class="h-7 w-7 rounded-full hover:bg-gray-800 flex items-center justify-center">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 100-4 2 2 0 000 4zM10 12a2 2 0 100-4 2 2 0 000 4zM10 18a2 2 0 100-4 2 2 0 000 4z" /></svg>
                                </button>
                                <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false"
                                     class="absolute right-0 mt-1 w-40 bg-gray-800 rounded-lg shadow-xl py-1 text-sm z-30">
                                    <button @click="copyLink(openThread_.id); menuOpen = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-700">Copy link</button>
                                </div>
                            </div>
                            <template x-if="isTeamMember">
                                <button @click="toggleResolve(openThread_.id)" class="h-7 w-7 rounded-full hover:bg-gray-800 flex items-center justify-center" :title="openThread_.is_resolved ? 'Reopen' : 'Mark as resolved'">
                                    <svg class="h-4 w-4" :class="openThread_.is_resolved ? 'text-green-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                            </template>
                            <button @click="openThreadId = null" class="h-7 w-7 rounded-full hover:bg-gray-800 flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="max-h-64 overflow-y-auto px-4 py-3 space-y-3">
                        <div class="flex gap-2.5 group">
                            <div class="shrink-0 h-7 w-7 rounded-full flex items-center justify-center text-[11px] font-semibold" :style="`background-color:${avatarColor(openThread_.author)}`" x-text="initials(openThread_.author)"></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm"><span class="font-semibold" x-text="openThread_.author"></span> <span class="text-gray-400 text-xs" x-text="openThread_.created_at"></span></div>
                                <p class="text-sm text-gray-200 break-words" x-text="openThread_.body"></p>
                            </div>
                            <template x-if="isTeamMember || openThread_.is_own">
                                <button @click="deleteComment(openThread_.id, true)" class="shrink-0 opacity-0 group-hover:opacity-100 h-6 w-6 rounded hover:bg-gray-800 flex items-center justify-center text-gray-400 hover:text-red-400" title="Delete comment">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" /></svg>
                                </button>
                            </template>
                        </div>

                        <template x-for="reply in openThread_.replies" :key="reply.id">
                            <div class="flex gap-2.5 pl-2 group">
                                <div class="shrink-0 h-6 w-6 rounded-full flex items-center justify-center text-[10px] font-semibold" :style="`background-color:${avatarColor(reply.author)}`" x-text="initials(reply.author)"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm"><span class="font-semibold" x-text="reply.author"></span> <span class="text-gray-400 text-xs" x-text="reply.created_at"></span></div>
                                    <p class="text-sm text-gray-200 break-words" x-text="reply.body"></p>
                                </div>
                                <template x-if="isTeamMember || reply.is_own">
                                    <button @click="deleteComment(reply.id, false)" class="shrink-0 opacity-0 group-hover:opacity-100 h-6 w-6 rounded hover:bg-gray-800 flex items-center justify-center text-gray-400 hover:text-red-400" title="Delete reply">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" /></svg>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-400 px-4 pt-2"></p>
                    <div class="flex items-center gap-2 px-3 py-3 border-t border-gray-700">
                        <div class="shrink-0 h-7 w-7 rounded-full flex items-center justify-center text-[11px] font-semibold bg-gray-700" x-text="initials(currentUserName || guestName())"></div>
                        <input x-model="replyBody" @keydown.enter="submitReply()" type="text" placeholder="Reply" :disabled="submitting"
                               class="flex-1 bg-gray-800 border-0 rounded-full px-3 py-1.5 text-sm placeholder-gray-400 text-white focus:ring-1 focus:ring-indigo-500 disabled:opacity-50">
                        <button @click="submitReply()" :disabled="submitting" class="shrink-0 h-8 w-8 rounded-full bg-gray-700 hover:bg-indigo-600 disabled:opacity-50 flex items-center justify-center">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Figma-style floating tool switcher -->
        <div x-show="allowComments" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white rounded-full shadow-xl px-2 py-2 flex items-center gap-1 z-20">
            <button @click="setTool('select')" class="h-9 w-9 rounded-full flex items-center justify-center" :class="tool === 'select' ? 'bg-indigo-600' : 'hover:bg-gray-800'" title="Select">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4l7.07 16.97 2.51-7.39 7.39-2.51z" /></svg>
            </button>
            <button @click="setTool('comment')" class="h-9 w-9 rounded-full flex items-center justify-center" :class="tool === 'comment' ? 'bg-indigo-600' : 'hover:bg-gray-800'" title="Comment">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </button>
        </div>

        <div x-show="tool === 'comment'" x-cloak class="fixed top-20 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-3 py-1.5 rounded-full shadow-lg z-20">
            Click anywhere on the report to leave a comment
        </div>

        @if ($brand?->footer_text)
            <p class="text-center text-xs text-gray-400 mt-6">{{ $brand->footer_text }}</p>
        @endif
    </main>

    <script>
        function shareViewer(config) {
            return {
                tool: 'select',
                pins: config.initialPins,
                allowComments: config.allowComments,
                draft: null,
                draftBody: '',
                frameHeight: 400,
                submitting: false,
                errorMessage: null,

                openThreadId: null,
                replyBody: '',
                isTeamMember: config.isTeamMember,
                currentUserName: config.currentUserName,

                init() {
                    window.addEventListener('message', async (e) => {
                        if (e.source !== this.$refs.reportFrame?.contentWindow) return;

                        if (e.data?.reportToolHeight) {
                            this.frameHeight = e.data.reportToolHeight;
                        }

                        const msg = e.data?.reportlyAgreement;
                        if (msg) {
                            const result = msg.type === 'submit'
                                ? await this.submitAgreement(msg.payload)
                                : await this.checkAgreement();
                            e.source.postMessage({ reportlyAgreementResult: { id: msg.id, result } }, '*');
                        }
                    });

                    // No websockets/queue infrastructure on the target hosting, so polling is
                    // how another viewer's comments/replies/resolves show up without the page
                    // needing a manual refresh. 5s keeps it feeling responsive without hammering
                    // the server; refreshPins() is also called immediately after our own actions.
                    setInterval(() => this.refreshPins(), 5000);
                },

                async refreshPins() {
                    try {
                        const res = await fetch(config.commentsIndexUrl, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        this.pins = await res.json();
                        // If the thread we had open was just deleted (by us or someone else),
                        // openThread_ naturally resolves to null and the popover closes itself.
                    } catch (e) {
                        // Offline or a transient network blip — just try again on the next tick.
                    }
                },

                async checkAgreement() {
                    try {
                        const res = await fetch(config.agreementShowUrl, { headers: { 'Accept': 'application/json' } });
                        return await res.json();
                    } catch (e) {
                        return { signed: false };
                    }
                },

                async submitAgreement(payload) {
                    try {
                        const res = await fetch(config.agreementStoreUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json().catch(() => null);
                        if (!res.ok) {
                            return { signed: data?.signed ?? false, error: data?.message ?? 'Could not submit — please try again.' };
                        }
                        return data;
                    } catch (e) {
                        return { signed: false, error: 'Network error — please try again.' };
                    }
                },

                get openThread_() {
                    return this.pins.find(p => p.id === this.openThreadId) ?? null;
                },

                setTool(tool) {
                    this.tool = this.tool === tool ? 'select' : tool;
                    this.draft = null;
                    this.openThreadId = null;
                },

                handleClick(e) {
                    if (this.tool !== 'comment') return;
                    this.openThreadId = null;
                    const rect = e.currentTarget.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) * 100;
                    const y = ((e.clientY - rect.top) / rect.height) * 100;
                    this.draft = { x, y };
                    this.draftBody = '';
                    this.errorMessage = null;
                    this.$nextTick(() => this.$refs.draftInput?.focus());
                },

                openThread(pin) {
                    this.draft = null;
                    this.replyBody = '';
                    this.errorMessage = null;
                    this.openThreadId = this.openThreadId === pin.id ? null : pin.id;
                },

                guestName() {
                    return localStorage.getItem('guest_name');
                },

                resolveAuthorName() {
                    if (this.isTeamMember) return null;
                    const name = localStorage.getItem('guest_name') || prompt('Your name:');
                    if (name) localStorage.setItem('guest_name', name);
                    return name;
                },

                async postComment(payload) {
                    this.submitting = true;
                    this.errorMessage = null;
                    try {
                        const res = await fetch(config.commentUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                            body: JSON.stringify(payload),
                        });
                        if (!res.ok) {
                            const data = await res.json().catch(() => null);
                            this.errorMessage = data?.message ?? 'Could not post that — please try again.';
                            return false;
                        }
                        await this.refreshPins();
                        return true;
                    } catch (e) {
                        this.errorMessage = 'Network error — please try again.';
                        return false;
                    } finally {
                        this.submitting = false;
                    }
                },

                async submitDraft() {
                    const body = this.draftBody.trim();
                    if (!body || !this.draft) return;

                    const name = this.resolveAuthorName();
                    if (!this.isTeamMember && !name) return;

                    const ok = await this.postComment({ body, position_x: this.draft.x, position_y: this.draft.y, guest_name: name });
                    if (ok) {
                        this.draft = null;
                        this.draftBody = '';
                    }
                },

                async submitReply() {
                    const body = this.replyBody.trim();
                    if (!body || !this.openThread_) return;

                    const name = this.resolveAuthorName();
                    if (!this.isTeamMember && !name) return;

                    // The thread's parent might have been deleted by someone else a moment ago —
                    // the server re-validates parent_id and we surface that instead of pretending
                    // the reply went through.
                    const ok = await this.postComment({ body, parent_id: this.openThread_.id, guest_name: name });
                    if (ok) this.replyBody = '';
                },

                toggleResolve(id) {
                    fetch(`${config.resolveUrlBase}/${id}/resolve`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    }).then(() => this.refreshPins());
                },

                deleteComment(id, isRoot) {
                    const message = isRoot
                        ? 'Delete this comment and all its replies?'
                        : 'Delete this reply?';
                    if (!confirm(message)) return;

                    fetch(`${config.resolveUrlBase}/${id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                    }).then(() => this.refreshPins());
                },

                copyLink(id) {
                    navigator.clipboard.writeText(`${config.shareViewUrl}#comment-${id}`);
                },

                initials(name) {
                    if (!name) return '?';
                    return name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
                },

                avatarColor(name) {
                    let hash = 0;
                    for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
                    return `hsl(${hash % 360}, 65%, 45%)`;
                },

                // Anchors the popover from whichever side keeps it inside the canvas: flips to
                // open leftward/upward once the pin is past the midpoint, instead of always
                // anchoring from the pin's top-left (which pushes it off-screen near edges).
                popoverStyle(x, y) {
                    const h = x > 50 ? `right: calc(${100 - x}% + 16px);` : `left: calc(${x}% + 16px);`;
                    const v = y > 50 ? `bottom: calc(${100 - y}% + 16px);` : `top: calc(${y}% + 16px);`;
                    return h + v;
                },
            };
        }
    </script>
</body>
</html>
