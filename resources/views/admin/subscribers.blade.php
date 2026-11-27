<x-admin.shell title="Subscribers" :subtitle="$active.' active · '.$week.' new this week'">
    <x-slot:actions>
        <a href="{{ route('admin.subscribers') }}" class="btn btn-primary btn-sm">Download CSV</a>
    </x-slot:actions>

    <form method="GET" class="flex gap-2">
        <label class="sr-only" for="q">Search subscribers</label>
        <input id="q" name="q" value="{{ $q }}" placeholder="Search email" class="input !h-10 max-w-sm flex-1">
        <button class="btn btn-secondary">Search</button>
    </form>

    <ul class="card mt-5 overflow-hidden">
        @forelse ($subscribers as $s)
            <li class="list-row flex items-center gap-4 px-4 py-3 text-sm">
                <span class="min-w-0 flex-1 truncate {{ $s->unsubscribed_at ? 'text-ink-3 line-through' : 'font-medium' }}">{{ $s->email }}</span>
                <span class="hidden text-xs text-ink-2 sm:inline">{{ $s->source ?: '—' }}</span>
                <span class="w-24 text-right text-xs text-ink-2 tabular-nums">{{ $s->created_at->format('M j, Y') }}</span>
                <form method="POST" action="{{ route('admin.subscribers.toggle', $s) }}">@csrf<button class="btn btn-outline btn-sm">{{ $s->unsubscribed_at ? 'Resubscribe' : 'Unsubscribe' }}</button></form>
            </li>
        @empty
            <li class="px-6 py-14 text-center text-sm text-ink-2">No subscribers yet.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $subscribers->links() }}</div>
</x-admin.shell>
