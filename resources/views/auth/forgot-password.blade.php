<x-layout title="Reset password" :noindex="true">
    <div class="mx-auto max-w-sm px-4 pt-16">
        <div class="text-center">
            <x-logo :size="44" :wordmark="false" class="text-ink" />
            <h1 class="title-2 mt-5">Forgot your password?</h1>
            <p class="mt-1.5 text-sm text-ink-2">Enter your email and we’ll send you a reset link.</p>
        </div>
        <div class="card mt-8 p-6">
            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <button class="btn btn-primary btn-lg w-full">Send reset link</button>
            </form>
        </div>
        <p class="mt-6 text-center text-sm text-ink-2"><a href="{{ route('login') }}" class="font-semibold text-ink">Back to sign in</a></p>
    </div>
</x-layout>
