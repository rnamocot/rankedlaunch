<x-layout title="Choose a new password" :noindex="true">
    <div class="mx-auto max-w-sm px-4 pt-16">
        <div class="text-center">
            <x-logo :size="44" :wordmark="false" class="text-ink" />
            <h1 class="title-2 mt-5">Choose a new password</h1>
        </div>
        <div class="card mt-8 p-6">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="field-label">New password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                    @error('password')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">At least 8 characters.</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="field-label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                </div>
                <button class="btn btn-primary btn-lg w-full">Update password</button>
            </form>
        </div>
    </div>
</x-layout>
