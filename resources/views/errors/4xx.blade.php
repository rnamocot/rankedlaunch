<x-error-page :code="$exception->getStatusCode()" title="That didn’t work." :message="$exception->getMessage() ?: 'The request couldn’t be completed. Go back and try again.'">
    <div class="mt-8 flex flex-wrap justify-center gap-2.5">
        <a href="{{ url()->previous() }}" class="btn btn-primary">Go back</a>
        <a href="{{ route('home') }}" class="btn btn-outline">Home</a>
    </div>
</x-error-page>
