<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Team Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-2 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Notifications -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Notifications</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Where to send an email when a client first opens a report or leaves a comment.
                    </p>

                    <form method="POST" action="{{ route('settings.notifications') }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="notification_email" value="Notification email" />
                            <x-text-input id="notification_email" name="notification_email" type="email" class="block w-full mt-1"
                                value="{{ old('notification_email', $org->notification_email) }}" placeholder="team@yourcompany.com" />
                            <x-input-error :messages="$errors->get('notification_email')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="notify_on_first_view" value="1" id="notify_on_first_view"
                                   @checked(old('notify_on_first_view', $org->notify_on_first_view)) class="rounded border-gray-300">
                            <label for="notify_on_first_view" class="text-sm text-gray-600 dark:text-gray-300">Notify when a client first opens a report</label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="notify_on_comment" value="1" id="notify_on_comment"
                                   @checked(old('notify_on_comment', $org->notify_on_comment)) class="rounded border-gray-300">
                            <label for="notify_on_comment" class="text-sm text-gray-600 dark:text-gray-300">Notify when a client leaves a comment</label>
                        </div>

                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </form>
                </div>
            </div>

            <!-- Brand kit -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Brand kit</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Shown on every shared report — logo, color, and footer text.
                    </p>

                    <form method="POST" action="{{ route('settings.brand-kit') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label value="Logo" />
                            <div class="flex items-center gap-4 mt-1">
                                @if ($brandKit?->logo_path)
                                    <img src="{{ asset($brandKit->logo_path) }}" class="h-10 w-10 rounded object-contain bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700" alt="Current logo">
                                    <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
                                        Remove current logo
                                    </label>
                                @endif
                            </div>
                            <input type="file" name="logo" accept="image/*" class="block w-full mt-2 text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-600 dark:file:text-indigo-300 file:text-sm">
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="primary_color" value="Primary color" />
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="color" name="primary_color" id="primary_color" value="{{ old('primary_color', $brandKit->primary_color ?? '#2563eb') }}" class="h-9 w-12 rounded border border-gray-300 dark:border-gray-700 bg-transparent">
                                </div>
                            </div>
                            <div>
                                <x-input-label for="secondary_color" value="Secondary color" />
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="color" name="secondary_color" id="secondary_color" value="{{ old('secondary_color', $brandKit->secondary_color ?? '#1e293b') }}" class="h-9 w-12 rounded border border-gray-300 dark:border-gray-700 bg-transparent">
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="footer_text" value="Footer text (optional)" />
                            <x-text-input id="footer_text" name="footer_text" type="text" class="block w-full mt-1"
                                value="{{ old('footer_text', $brandKit->footer_text ?? '') }}" placeholder="&copy; Acme Marketing" />
                            <x-input-error :messages="$errors->get('footer_text')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
