<x-layout title="Page not found" :noindex="true">
    <section class="intro mx-auto max-w-xl px-4 pt-24 text-center sm:px-6">
        <p class="text-sm font-semibold tabular-nums text-ink-2">404</p>
        <h1 class="large-title mt-3">This page didn’t make the board.</h1>
        <p class="mt-4 text-ink-2">It may have moved, or the product hasn’t launched yet.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('home') }}" class="btn btn-primary btn-lg">See today’s launches</a>
            <a href="{{ route('rankings', 'week') }}" class="btn btn-outline btn-lg">Top this week</a>
        </div>
    </section>
</x-layout>
