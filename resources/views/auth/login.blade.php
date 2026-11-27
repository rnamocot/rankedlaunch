<x-layout title="Sign in" :noindex="true">
    <div class="mx-auto max-w-sm px-4 pt-16">
        <div class="text-center">
            <x-logo :size="44" :wordmark="false" class="text-ink" />
            <h1 class="title-2 mt-5">Welcome back</h1>
            <p class="mt-1.5 text-sm text-ink-2">Sign in to vote and launch your products.</p>
        </div>

        <div class="card mt-8 p-6">
            @include('auth.social')

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="flex items-baseline justify-between">
                        <label for="password" class="field-label">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-ink-2 hover:text-ink">Forgot password?</a>
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
                </div>
                <button class="btn btn-primary btn-lg w-full">Sign in</button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-ink-2">New here? <a href="{{ route('register') }}" class="font-semibold text-ink">Create an account</a></p>
    </div>
</x-layout>
