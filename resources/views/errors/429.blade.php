<x-error-page code="429" title="Slow down a little." message="That was a lot of requests in a short time. Wait a minute, then try again.">
    <div class="mt-8 flex flex-wrap justify-center gap-2.5">
        <a href="{{ route('home') }}" class="btn btn-primary">Home</a>
    </div>
</x-error-page>
