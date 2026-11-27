<x-error-page code="419" title="This page expired." message="For your security, forms expire after a while. Go back, refresh the page and try again. Nothing was lost on our side.">
    <div class="mt-8 flex flex-wrap justify-center gap-2.5">
        <a href="{{ url()->previous() }}" class="btn btn-primary">Go back</a>
        <a href="{{ route('home') }}" class="btn btn-outline">Home</a>
    </div>
</x-error-page>
