<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protected Report</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-sm w-full bg-white dark:bg-gray-800 shadow rounded-lg p-8">
        <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Password required</h1>
        <p class="text-sm text-gray-500 mb-6">This report is protected. Enter the password to view it.</p>

        @if ($errors->any())
            <p class="text-sm text-red-500 mb-4">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('share.unlock', $share->slug) }}">
            @csrf
            <input type="password" name="password" placeholder="Password" required autofocus
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 mb-4">
            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-md py-2 text-sm font-medium">
                View Report
            </button>
        </form>
    </div>
</body>
</html>
