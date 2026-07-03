<x-guest-layout>
    <div x-data="{ step: 1, email: '{{ old('email') }}' }">
        <!-- Step indicator -->
        <div class="flex items-center gap-2 mb-6">
            <span class="h-2 flex-1 rounded-full" :class="step >= 1 ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-700'"></span>
            <span class="h-2 flex-1 rounded-full" :class="step >= 2 ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-700'"></span>
        </div>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Step 1: account details -->
            <div x-show="step === 1">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Your team &amp; account</h2>

                <div>
                    <x-input-label for="organization_name" :value="__('Company / Team Name')" />
                    <x-text-input id="organization_name" class="block mt-1 w-full" type="text" name="organization_name" :value="old('organization_name')" required autofocus autocomplete="organization" />
                    <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input x-model="email" id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100" href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>
                    <x-primary-button type="button" @click="step = 2">
                        {{ __('Continue') }}
                    </x-primary-button>
                </div>
            </div>

            <!-- Step 2: notification preferences -->
            <div x-show="step === 2" x-cloak>
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Report notifications</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Get an email when a client views a report for the first time or leaves a comment. You can change this anytime.</p>

                <div>
                    <x-input-label for="notification_email" :value="__('Notification email')" />
                    <x-text-input id="notification_email" class="block mt-1 w-full" type="email" name="notification_email" :value="old('notification_email')" x-bind:placeholder="email" autocomplete="email" />
                    <p class="text-xs text-gray-400 mt-1">Leave blank to use your account email above.</p>
                    <x-input-error :messages="$errors->get('notification_email')" class="mt-2" />
                </div>

                <div class="mt-4 space-y-2">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="notify_on_first_view" value="1" checked class="rounded border-gray-300">
                        Notify me when a report is first viewed
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="checkbox" name="notify_on_comment" value="1" checked class="rounded border-gray-300">
                        Notify me when a client leaves a comment
                    </label>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <x-secondary-button type="button" @click="step = 1">
                        {{ __('Back') }}
                    </x-secondary-button>
                    <x-primary-button>
                        {{ __('Create account') }}
                    </x-primary-button>
                </div>
            </div>
        </form>
    </div>
</x-guest-layout>
