<x-error-page code="404" title="This page didn’t make the board." message="It may have moved, or the product hasn’t launched yet. Try a search, or start from today’s launches.">
    <form method="GET" action="{{ route('discover') }}" class="relative mt-8 w-full max-w-sm" role="search">
        <x-icon name="search" :size="17" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-ink-2" />
        <label for="notfound-search" class="sr-only">Search tools</label>
        <input id="notfound-search" name="q" placeholder="Search tools" class="input !h-11 bg-surface !pl-10 shadow-[inset_0_0_0_1px_var(--line)]">
    </form>
    <div class="mt-5 flex flex-wrap justify-center gap-2.5">
        <a href="{{ route('home') }}" class="btn btn-primary">Today’s launches</a>
        <a href="{{ route('rankings', 'week') }}" class="btn btn-outline">Top this week</a>
    </div>
</x-error-page>
