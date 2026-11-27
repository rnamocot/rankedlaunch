<x-admin.shell :title="$user->name" :subtitle="$user->email.' · joined '.$user->created_at->format('M j, Y').' · '.($user->provider ? ucfirst($user->provider) : 'Email').' sign-in'">
    @php($self = $user->is(auth()->user()))

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-8">
            @if ($user->banned_at)
                <div class="card px-4 py-3 text-sm"><span class="font-semibold text-danger">Banned</span> {{ $user->banned_at->diffForHumans() }}{{ $user->ban_reason ? ': '.$user->ban_reason : '' }}</div>
            @endif

            <dl class="grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach (['Products' => $user->products_count, 'Votes cast' => $user->votes_count, 'Votes in last hour' => $votesLastHour, 'Paid' => \App\Services\Pricing::dollars((int) $payments->where('status', 'paid')->sum('amount'))] as $l => $v)
                    <div class="card p-4"><dt class="text-xs text-ink-2">{{ $l }}</dt><dd class="mt-1 text-xl font-bold tabular-nums">{{ $v }}</dd></div>
                @endforeach
            </dl>

            <section>
                <h2 class="group-header">Products</h2>
                <ul class="card overflow-hidden">
                    @forelse ($products as $product)
                        <li class="list-row" style="--inset: 4rem">
                            <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-2">
                                <span class="app-icon size-9"><img src="{{ $product->logoUrl() }}" alt=""></span>
                                <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $product->name }}</span>
                                <span class="meta flex"><span>{{ $product->statusLabel() }}</span><span>{{ $product->launch_date?->format('M j') }}</span></span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-ink-2">No products.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="group-header">Recent votes</h2>
                <ul class="card overflow-hidden text-sm">
                    @forelse ($recentVotes as $vote)
                        <li class="list-row flex justify-between px-4 py-2.5"><span class="truncate">{{ $vote->product?->name ?? 'Deleted product' }}</span><span class="shrink-0 text-xs text-ink-2 tabular-nums">{{ $vote->created_at->format('M j, H:i:s') }}</span></li>
                    @empty
                        <li class="px-4 py-6 text-center text-ink-2">No votes.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="group-header">Activity</h2>
                @include('admin.partials.activity-list', ['logs' => $activity])
            </section>
        </div>

        <aside class="space-y-8">
            <section>
                <h2 class="group-header">Controls</h2>
                @if ($self)
                    <p class="card p-4 text-sm text-ink-2">This is your account. Ban, admin and delete controls are disabled for yourself.</p>
                @else
                    <div class="card space-y-4 p-4">
                        <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="space-y-2">
                            @csrf
                            @if ($user->banned_at)
                                <button class="btn btn-primary btn-sm w-full">Unban</button>
                            @else
                                <input name="reason" placeholder="Reason (internal)" aria-label="Ban reason" class="input !h-9 !text-sm">
                                <label class="flex items-center gap-2 text-xs text-ink-2"><input type="checkbox" name="remove_votes" value="1" checked class="accent-[var(--ink)]"> Also remove all their votes</label>
                                <button class="btn btn-secondary btn-sm w-full !text-danger">Ban user</button>
                            @endif
                        </form>
                        <div class="hairline"></div>
                        <form method="POST" action="{{ route('admin.users.remove-votes', $user) }}">@csrf<button class="btn btn-outline btn-sm w-full" @disabled(! $user->votes_count)>Remove all {{ $user->votes_count }} votes</button></form>
                        <form method="POST" action="{{ route('admin.users.admin', $user) }}">@csrf<button class="btn btn-outline btn-sm w-full">{{ $user->is_admin ? 'Remove admin access' : 'Make admin' }}</button></form>
                    </div>
                @endif
            </section>

            @if ($payments->isNotEmpty())
                <section>
                    <h2 class="group-header">Payments</h2>
                    <ul class="card overflow-hidden text-sm">
                        @foreach ($payments as $pay)
                            <li class="list-row flex justify-between px-4 py-2.5"><span>{{ \App\Services\Pricing::dollars($pay->amount) }} · {{ $pay->plan }}</span><span class="text-ink-2">{{ $pay->status }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @unless ($self)
                <section>
                    <h2 class="group-header">Danger zone</h2>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="card space-y-3 p-4">
                        @csrf @method('DELETE')
                        <label class="flex items-start gap-2 text-xs text-ink-2"><input type="checkbox" name="confirm" value="1" class="mt-0.5 accent-[var(--ink)]"> Permanently delete this user, their products, votes and payment records. This can’t be undone.</label>
                        <button class="btn btn-secondary btn-sm w-full !text-danger">Delete user</button>
                    </form>
                </section>
            @endunless
        </aside>
    </div>
</x-admin.shell>
