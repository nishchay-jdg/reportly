<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Reportly') }} — Share reports, not PDFs</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100">

    <nav class="border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">R</span>
                <span class="font-semibold">{{ config('app.name', 'Reportly') }}</span>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                        Go to dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm px-3 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-medium">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="text-sm px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                        Get started
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 pt-20 pb-16 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight">
            Send a live link.<br>Not another PDF.
        </h1>
        <p class="mt-5 text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
            Build client reports, proposals, and pricing pages as real HTML — then share one branded link
            clients can comment on, approve, and revisit. No exports, no attachments.
        </p>

        <div class="mt-8 flex items-center justify-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                    Go to dashboard
                </a>
            @else
                <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                    Create your team
                </a>
                <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900 font-medium">
                    Sign in
                </a>
            @endauth
        </div>

        <div class="mt-20 grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
            <div class="p-5 rounded-lg border border-gray-100 dark:border-gray-800">
                <div class="h-9 w-9 rounded-md bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-300 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <h3 class="font-semibold">Real HTML, not a PDF export</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">A full code editor with autocomplete for HTML, CSS, and JS — plus templates to start from.</p>
            </div>
            <div class="p-5 rounded-lg border border-gray-100 dark:border-gray-800">
                <div class="h-9 w-9 rounded-md bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-300 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                </div>
                <h3 class="font-semibold">Clients comment right on the page</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Click-to-pin feedback, approvals, and sign-off — no more "see attached" email threads.</p>
            </div>
            <div class="p-5 rounded-lg border border-gray-100 dark:border-gray-800">
                <div class="h-9 w-9 rounded-md bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-300 mb-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <h3 class="font-semibold">Share on your terms</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Password-protect a link, set an expiry, and know the moment a client opens it.</p>
            </div>
        </div>
    </main>

    <footer class="border-t border-gray-100 dark:border-gray-800 py-6">
        <p class="text-center text-xs text-gray-400">{{ config('app.name', 'Reportly') }} &copy; {{ date('Y') }}</p>
    </footer>
</body>
</html>
