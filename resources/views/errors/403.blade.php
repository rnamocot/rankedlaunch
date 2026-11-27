<x-error-page code="403" title="This page isn’t yours to see." :message="$exception?->getMessage() ?: 'You don’t have access to this page. If you think that’s a mistake, sign in with the right account or contact us.'">
    <div class="mt-8 flex flex-wrap justify-center gap-2.5">
        <a href="{{ route('home') }}" class="btn btn-primary">Go home</a>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-outline">Your launches</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline">Sign in</a>
        @endauth
    </div>
</x-error-page>
