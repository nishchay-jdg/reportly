<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Admin — Organizations') }}
            </h2>
            <a href="{{ route('admin.users') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                View all users &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-800 dark:text-green-200 mb-6">
                    {{ session('status') }}
                </div>
            @endif

            @if ($deployConfigured)
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-lg p-5 mb-6"
                     x-data="deployPanel({
                        storeUrl: '{{ route('admin.deploy.store') }}',
                        statusUrl: '{{ route('admin.deploy.status') }}',
                        csrf: '{{ csrf_token() }}',
                     })">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-800 dark:text-gray-100">Deploy</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5" x-text="statusText"></p>
                        </div>
                        <button
                            @click="deploy()"
                            :disabled="state === 'running'"
                            class="inline-flex items-center gap-2 text-sm px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-medium"
                        >
                            <svg x-show="state === 'running'" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="state === 'running' ? 'Deploying…' : 'Deploy latest'"></span>
                        </button>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 py-3">Organization</th>
                            <th class="px-4 py-3">Users</th>
                            <th class="px-4 py-3">Projects</th>
                            <th class="px-4 py-3">Shares</th>
                            <th class="px-4 py-3">Notification email</th>
                            <th class="px-4 py-3">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($organizations as $org)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $org->name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $org->users_count }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $org->projects_count }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $org->shares_count }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $org->notification_email ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $org->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No organizations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $organizations->links() }}
            </div>
        </div>
    </div>

    <script>
        function deployPanel(config) {
            return {
                state: 'idle', // idle | running | succeeded | failed
                statusText: 'Pulls the latest main branch and runs migrations/cache clears.',

                async deploy() {
                    this.state = 'running';
                    this.statusText = 'Pulling latest and starting deployment…';

                    try {
                        const res = await fetch(config.storeUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        });
                        const data = await res.json();

                        if (!res.ok || !data.task_id) {
                            this.state = 'failed';
                            this.statusText = data.message ?? 'Could not start the deployment.';
                            return;
                        }

                        this.poll(config, data.task_id);
                    } catch (e) {
                        this.state = 'failed';
                        this.statusText = 'Network error — could not reach the server.';
                    }
                },

                async poll(config, taskId) {
                    const start = Date.now();
                    const maxMs = 5 * 60 * 1000;

                    const tick = async () => {
                        if (Date.now() - start > maxMs) {
                            this.state = 'failed';
                            this.statusText = 'Timed out waiting for cPanel — check the Git Version Control page directly.';
                            return;
                        }

                        const res = await fetch(`${config.statusUrl}?task_id=${taskId}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();

                        if (data.status === 'succeeded') {
                            this.state = 'succeeded';
                            this.statusText = 'Deployed successfully.';
                        } else if (data.status === 'failed') {
                            this.state = 'failed';
                            this.statusText = 'Deployment failed — check cPanel\'s Git Version Control page for details.';
                        } else {
                            setTimeout(tick, 2000);
                        }
                    };

                    tick();
                },
            };
        }
    </script>
</x-app-layout>
