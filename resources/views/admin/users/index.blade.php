<x-admin.shell title="Users" :subtitle="$users->total().' '.Str::plural('user', $users->total())">
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <label class="sr-only" for="q">Search users</label>
        <input id="q" name="q" value="{{ $q }}" placeholder="Search name or email" class="input !h-10 max-w-sm flex-1">
        <button class="btn btn-secondary">Search</button>
    </form>

    <nav class="segmented mt-5" aria-label="Filter">
        @foreach (['' => 'All', 'makers' => 'Makers', 'admins' => 'Admins', 'banned' => 'Banned'] as $key => $label)
            <a href="{{ route('admin.users.index', array_filter(['filter' => $key, 'q' => $q])) }}" @if((string) $filter === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <ul class="card mt-5 overflow-hidden">
        @forelse ($users as $user)
            <li class="list-row" style="--inset: 4rem">
                <a href="{{ route('admin.users.show', $user) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-2">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}" alt="" class="size-9 rounded-full">
                    @else
                        <span class="grid size-9 place-items-center rounded-full bg-fill text-sm font-semibold">{{ $user->initials() }}</span>
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2 font-semibold">
                            <span class="truncate">{{ $user->name }}</span>
                            @if ($user->is_admin)<span class="text-xs font-medium text-ink-2">Admin</span>@endif
                            @if ($user->banned_at)<span class="text-xs font-medium text-danger">Banned</span>@endif
                        </span>
                        <span class="meta flex flex-wrap"><span class="truncate">{{ $user->email }}</span><span>{{ $user->provider ? ucfirst($user->provider) : 'Email' }}</span><span>Joined {{ $user->created_at->format('M j, Y') }}</span></span>
                    </span>
                    <span class="meta hidden shrink-0 tabular-nums sm:flex"><span>{{ $user->products_count }} products</span><span>{{ $user->votes_count }} votes</span></span>
                    <x-icon name="chevron-right" :size="16" class="text-ink-3" />
                </a>
            </li>
        @empty
            <li class="px-6 py-14 text-center text-sm text-ink-2">No users match.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $users->links() }}</div>
</x-admin.shell>
