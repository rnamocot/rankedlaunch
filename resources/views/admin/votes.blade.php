<x-admin.shell title="Votes" subtitle="Spot vote rings and fake accounts before they distort the rankings.">
    <section>
        <h2 class="group-header">Recent launches: where votes come from</h2>
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[36rem] text-sm">
                <thead><tr class="text-left text-xs text-ink-2"><th class="px-4 py-3 font-medium">Product</th><th class="px-4 py-3 text-right font-medium">Votes</th><th class="px-4 py-3 text-right font-medium">From same-day accounts</th><th class="px-4 py-3"></th></tr></thead>
                <tbody>
                    @forelse ($products as $row)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-medium">{{ $row['product']->name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $row['total'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $row['share'] >= 40 && $row['total'] >= 10 ? 'font-semibold text-danger' : '' }}">{{ $row['fresh'] }} ({{ $row['share'] }}%)</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('admin.products.edit', $row['product']) }}" class="text-xs font-medium text-ink-2 hover:text-ink">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-ink-2">No recent launches.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-2 px-1 text-xs text-ink-2">Over 40% from accounts created the same day is a common sign of a vote ring.</p>
    </section>

    <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-2">
        @foreach (['Fast voters (15+ votes in 24h)' => [$fastVoters, 'recent_votes'], 'Brand-new accounts that voted' => [$newAccountVoters, 'votes_count']] as $title => [$list, $countField])
            <section>
                <h2 class="group-header">{{ $title }}</h2>
                <ul class="card overflow-hidden">
                    @forelse ($list as $u)
                        <li class="list-row flex items-center gap-3 px-4 py-3 text-sm">
                            <a href="{{ route('admin.users.show', $u) }}" class="min-w-0 flex-1">
                                <span class="block truncate font-semibold">{{ $u->name }}</span>
                                <span class="block truncate text-xs text-ink-2">{{ $u->email }} · joined {{ $u->created_at->diffForHumans() }}</span>
                            </a>
                            <span class="shrink-0 font-semibold tabular-nums">{{ $u->{$countField} }}</span>
                            <form method="POST" action="{{ route('admin.users.ban', $u) }}">
                                @csrf
                                <input type="hidden" name="remove_votes" value="1">
                                <input type="hidden" name="reason" value="Vote manipulation">
                                <button class="btn btn-secondary btn-sm !text-danger">Ban</button>
                            </form>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-ink-2">None. Looks clean.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>

    <section class="mt-8">
        <h2 class="group-header">Latest votes</h2>
        <ul class="card overflow-hidden text-sm">
            @forelse ($recent as $vote)
                <li class="list-row flex items-center gap-4 px-4 py-2.5">
                    <span class="w-28 shrink-0 text-xs text-ink-2 tabular-nums">{{ $vote->created_at->format('M j, H:i:s') }}</span>
                    <a href="{{ $vote->user ? route('admin.users.show', $vote->user) : '#' }}" class="min-w-0 flex-1 truncate hover:underline">{{ $vote->user?->name }}</a>
                    <span class="truncate text-ink-2">{{ $vote->product?->name }}</span>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-ink-2">No votes yet.</li>
            @endforelse
        </ul>
    </section>
</x-admin.shell>
