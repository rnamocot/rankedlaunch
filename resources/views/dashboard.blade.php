<x-layout title="Dashboard" :noindex="true">
    @php
        $user = auth()->user();
        $nav = [
            ['Overview', '#overview', 'chart'],
            ['Launches', '#launches', 'grid'],
            ['Your upvotes', '#upvotes', 'chevron-up'],
            ['Account', route('account'), 'user'],
        ];
        $sections = [
            'live' => 'Live today',
            'action' => 'Needs attention',
            'upcoming' => 'Upcoming',
            'past' => 'Past',
        ];
    @endphp

    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 pt-8 sm:px-6 lg:grid-cols-[13rem_minmax(0,1fr)]">
        {{-- Sidebar --}}
        <aside class="lg:sticky lg:top-20 lg:self-start">
            <div class="mb-4 hidden items-center gap-3 px-3 lg:flex">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="" class="size-8 rounded-full">
                @else
                    <span class="grid size-8 place-items-center rounded-full bg-fill text-xs font-semibold">{{ $user->initials() }}</span>
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                    <span class="block text-xs text-ink-2">Maker</span>
                </span>
            </div>
            <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-1 lg:mx-0 lg:flex-col lg:px-0" aria-label="Dashboard">
                @foreach ($nav as [$label, $href, $icon])
                    <a href="{{ $href }}" class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-ink-2 transition-colors hover:bg-fill hover:text-ink {{ $loop->first ? '!bg-surface !text-ink shadow-[inset_0_0_0_1px_var(--line)]' : '' }}">
                        <x-icon :name="$icon" :size="16" /> {{ $label }}
                    </a>
                @endforeach
                @if ($user->is_admin)
                    <a href="{{ route('admin.index') }}" class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-ink-2 hover:bg-fill hover:text-ink"><x-icon name="shield" :size="16" /> Admin</a>
                @endif
            </nav>
            <a href="{{ route('submit') }}" class="btn btn-primary mt-4 hidden w-full lg:inline-flex"><x-icon name="plus" :size="16" :stroke="2.25" /> New launch</a>
        </aside>

        <div class="min-w-0 pb-10">
            {{-- Header --}}
            <header id="overview" class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-ink-2">{{ now()->format('l, F j') }}</p>
                    <h1 class="text-[1.875rem] font-bold leading-tight tracking-[-0.035em]">Hi, {{ Str::before($user->name, ' ') }}</h1>
                </div>
                <a href="{{ route('submit') }}" class="btn btn-primary lg:hidden"><x-icon name="plus" :size="16" :stroke="2.25" /> New launch</a>
            </header>

            @if ($justPaid)
                <div class="card mt-6 flex items-center gap-3 px-4 py-3 text-sm">
                    <x-icon name="check" :size="18" :stroke="2" />
                    Thanks! Your payment is being confirmed. Your launch updates here within a minute.
                </div>
            @endif

            @if (! $hasProducts)
                <div class="card mt-8 px-6 py-16 text-center">
                    <x-icon name="chart" :size="28" class="mx-auto text-ink-3" />
                    <p class="mt-4 font-semibold">No launches yet</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-ink-2">List your app today or pick a launch day. This becomes your launch control room: live votes, traffic and results.</p>
                    <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-6">Submit your product</a>
                </div>
            @else
                {{-- KPI tiles --}}
                <dl class="mt-8 grid grid-cols-2 gap-3 xl:grid-cols-4" data-reveal>
                    @foreach ($kpis as [$label, $value, $trend])
                        <div class="card p-4">
                            <dt class="text-xs text-ink-2">{{ $label }}</dt>
                            <dd class="mt-1 text-[1.75rem] font-bold tabular-nums tracking-[-0.03em]">{{ is_numeric($value) ? number_format($value) : $value }}</dd>
                            @if ($trend)
                                <dd class="mt-0.5 text-xs text-ink-2">
                                    {{ number_format($trend['week']) }} this week
                                    @if ($trend['change'] !== null)
                                        · <span class="font-semibold text-ink">{{ $trend['change'] >= 0 ? '▲' : '▼' }} {{ abs($trend['change']) }}%</span>
                                    @endif
                                </dd>
                            @else
                                <dd class="mt-0.5 text-xs text-ink-2">across all launches</dd>
                            @endif
                        </div>
                    @endforeach
                </dl>

                {{-- Charts --}}
                <div class="mt-6 grid grid-cols-1 gap-3 xl:grid-cols-3" data-reveal>
                    <x-admin.bar-chart title="Upvotes" :series="$series['votes']" />
                    <x-admin.bar-chart title="Page views" :series="$series['views']" />
                    <x-admin.bar-chart title="Visits to your sites" :series="$series['clicks']" />
                </div>

                {{-- Launches table --}}
                <section id="launches" class="mt-10 scroll-mt-24" data-reveal>
                    <h2 class="group-header">
                        <span>Your launches <span class="ml-1 font-normal tabular-nums text-ink-2">{{ $products->count() }}</span></span>
                    </h2>
                    <div class="card overflow-x-auto">
                        <table class="w-full min-w-[46rem] text-sm">
                            <thead>
                                <tr class="text-left text-xs text-ink-2">
                                    <th class="px-4 py-3 font-medium">App</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Launch</th>
                                    <th class="px-4 py-3 text-right font-medium">Rank</th>
                                    <th class="px-4 py-3 text-right font-medium">Votes</th>
                                    <th class="px-4 py-3 text-right font-medium">Views</th>
                                    <th class="px-4 py-3 text-right font-medium">Visits</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sections as $key => $sectionLabel)
                                    @continue($groups[$key]->isEmpty())
                                    <tr class="border-t border-line bg-surface-2">
                                        <td colspan="8" class="px-4 py-1.5 text-[0.6875rem] font-semibold uppercase tracking-wide text-ink-2">{{ $sectionLabel }}</td>
                                    </tr>
                                    @foreach ($groups[$key] as $product)
                                        @php
                                            $live = $product->isLive();
                                            $rank = $live ? $product->dailyRank() : null;
                                            $canMove = in_array($product->status, ['pending', 'approved']) && $product->launch_date?->isFuture();
                                        @endphp
                                        <tr class="border-t border-line align-middle">
                                            <td class="px-4 py-3">
                                                <a href="{{ route('launch.hub', $product) }}" class="flex items-center gap-3">
                                                    <span class="app-icon size-9"><img src="{{ $product->logoUrl() }}" alt=""></span>
                                                    <span class="min-w-0">
                                                        <span class="block max-w-[14rem] truncate font-semibold">{{ $product->name }}</span>
                                                        <span class="block text-xs text-ink-2">{{ config("rankedlaunch.plans.{$product->plan}.name") }}{{ $product->category ? ' · '.$product->category->name : '' }}</span>
                                                    </span>
                                                </a>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-medium">{{ $product->statusLabel() }}</span>
                                                @if ($product->status === 'rejected' && $product->rejection_reason)
                                                    <span class="block max-w-[12rem] truncate text-xs text-danger" title="{{ $product->rejection_reason }}">{{ $product->rejection_reason }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-ink-2">{{ $product->launch_date?->format('M j, Y') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $rank ? '#'.$rank : '—' }}</td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $live ? number_format($product->votes_count) : '—' }}</td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $live ? number_format($product->views_count) : '—' }}</td>
                                            <td class="px-4 py-3 text-right tabular-nums">{{ $live ? number_format($product->clicks_count) : '—' }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-end gap-1.5">
                                                    @if (in_array($product->status, ['draft', 'awaiting_payment']))
                                                        <a href="{{ route('submit.plan', $product) }}" class="btn btn-primary btn-sm">Finish</a>
                                                    @elseif (in_array($product->status, ['pending', 'approved']) && $product->plan === 'free' && ! $live)
                                                        <a href="{{ route('submit.plan', $product) }}" class="btn btn-secondary btn-sm">Upgrade</a>
                                                    @endif
                                                    @if ($canMove)
                                                        <a href="{{ route('launch.date', $product) }}" class="btn btn-outline btn-sm" title="Change launch date" aria-label="Change launch date"><x-icon name="calendar" :size="15" /></a>
                                                    @endif
                                                    @if ($live)
                                                        <a href="{{ route('product', $product) }}" class="btn btn-outline btn-sm" title="View public page" aria-label="View public page"><x-icon name="arrow-up-right" :size="15" :stroke="2" /></a>
                                                    @endif
                                                    <a href="{{ route('launch.hub', $product) }}" class="btn btn-secondary btn-sm">Manage</a>
                                                    @if (in_array($product->status, ['draft', 'awaiting_payment', 'rejected']))
                                                        <details class="relative">
                                                            <summary class="btn btn-outline btn-sm list-none !px-2.5 [&::-webkit-details-marker]:hidden" title="Delete" aria-label="Delete {{ $product->name }}">
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                                            </summary>
                                                            <form method="POST" action="{{ route('launch.destroy', $product) }}" class="card absolute right-0 z-20 mt-2 w-56 space-y-2.5 p-3 text-left">
                                                                @csrf @method('DELETE')
                                                                <input type="hidden" name="confirm" value="1">
                                                                <p class="text-xs text-ink-2">Delete <span class="font-semibold text-ink">{{ $product->name }}</span>? This can’t be undone.</p>
                                                                <button class="btn btn-secondary btn-sm w-full !text-danger">Delete {{ $product->status === 'draft' ? 'draft' : 'listing' }}</button>
                                                            </form>
                                                        </details>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 px-1 text-xs text-ink-2">Edit, reschedule or delete a listing from its <span class="font-medium text-ink">Manage</span> page. Unfinished drafts can be deleted right here.</p>
                </section>
            @endif

            <div class="mt-10 grid grid-cols-1 gap-8 xl:grid-cols-2">
                <section id="upvotes" class="scroll-mt-24" data-reveal>
                    <h2 class="group-header">
                        <span>Your upvotes</span>
                        <a href="{{ route('discover') }}">Discover</a>
                    </h2>
                    <ul class="card overflow-hidden">
                        @forelse ($upvoted as $p)
                            <li class="list-row" style="--inset: 3.5rem">
                                <a href="{{ route('product', $p) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-2">
                                    <span class="app-icon size-8"><img src="{{ $p->logoUrl() }}" alt="" loading="lazy"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $p->name }}</span>
                                        <span class="block truncate text-xs text-ink-2">{{ $p->category?->name }}</span>
                                    </span>
                                </a>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-sm text-ink-2">Products you upvote show up here.</li>
                        @endforelse
                    </ul>
                </section>

                <section data-reveal>
                    <h2 class="group-header">Launch tips</h2>
                    <div class="card space-y-4 p-5 text-sm leading-relaxed text-ink-2">
                        <p><span class="font-semibold text-ink">Go early.</span> Most votes come in the first 3 hours. Post on X and LinkedIn right after you go live at <x-local-time :at="today('UTC')->addDay()" />.</p>
                        <p><span class="font-semibold text-ink">Tell your users the day before.</span> A short email or post with your launch link does more than any ad.</p>
                        <p><span class="font-semibold text-ink">Add your badge.</span> It upgrades itself if you finish top 3 and sends visitors back to your page.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layout>
