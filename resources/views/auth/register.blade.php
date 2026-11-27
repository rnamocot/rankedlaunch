<x-layout title="Create account" :noindex="true">
    <div class="mx-auto max-w-sm px-4 pt-16">
        <div class="text-center">
            <x-logo :size="44" :wordmark="false" class="text-ink" />
            @if (str_contains(session('url.intended', ''), '/submit'))
                <h1 class="title-2 mt-5">Create an account to launch</h1>
                <p class="mt-1.5 text-sm text-ink-2">Takes 10 seconds. Then 2 minutes to submit your product.</p>
            @else
                <h1 class="title-2 mt-5">Create your account</h1>
                <p class="mt-1.5 text-sm text-ink-2">Launch products, vote, and climb the ranks.</p>
            @endif
        </div>

        <div class="card mt-8 p-6">
            @include('auth.social')

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="field-label">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="input">
                    @error('name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="field-label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input">
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="field-label">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                    @error('password')<p class="field-error">{{ $message }}</p>@else<p class="field-hint">At least 8 characters.</p>@enderror
                </div>
                <button class="btn btn-primary btn-lg w-full">Create account</button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-ink-2">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-ink">Sign in</a></p>
    </div>
</x-layout>
