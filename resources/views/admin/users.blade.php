<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                {{ __('Admin — Users') }}
            </h2>
            <a href="{{ route('admin.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                &larr; Organizations
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
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Organization</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Platform admin</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $user->organization?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $user->role }}</td>
                                <td class="px-4 py-3">
                                    @if ($user->is_platform_admin)
                                        <span class="text-[11px] font-medium bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded">Yes</span>
                                    @else
                                        <span class="text-gray-400">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.toggle-platform-admin', $user) }}" onsubmit="return confirm('{{ $user->is_platform_admin ? 'Remove' : 'Grant' }} platform admin access for {{ $user->name }}?')">
                                            @csrf
                                            <button class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $user->is_platform_admin ? 'Revoke' : 'Grant admin' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No users yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
