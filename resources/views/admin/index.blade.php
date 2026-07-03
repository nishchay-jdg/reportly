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
</x-app-layout>
