<x-layout title="Dashboard" :noindex="true">
    @php
        $user = auth()->user();
        $sections = [
            'live' => ['Live today', 'Voting closes at midnight UTC. Share your page now.'],
            'action' => ['Needs your attention', 'Finish these to get them on the board.'],
            'upcoming' => ['Upcoming launches', 'Booked and waiting for launch day.'],
            'past' => ['Past launches', 'Your pages, backlinks and badges stay up.'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl px-4 pt-10 sm:px-6">
        {{-- Header --}}
        <div class="intro flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-ink-2">{{ now()->format('l, F j') }}</p>
                <h1 class="large-title mt-1">Hi, {{ Str::before($user->name, ' ') }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('account') }}" class="btn btn-outline"><x-icon name="user" :size="16" /> Account</a>
                <a href="{{ route('submit') }}" class="btn btn-primary"><x-icon name="plus" :size="16" :stroke="2.25" /> New launch</a>
            </div>
        </div>

        @if ($justPaid)
            <div class="card mt-6 flex items-center gap-3 px-4 py-3 text-sm">
                <x-icon name="check" :size="18" :stroke="2" />
                Thanks! Your payment is being confirmed. Your launch updates here within a minute.
            </div>
        @endif

        @if ($hasProducts)
            {{-- Totals --}}
            <dl class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4" data-reveal>
                @foreach ($totals as $label => $value)
                    <div class="card p-4">
                        <dt class="text-xs text-ink-2">{{ $label }}</dt>
                        <dd class="mt-1 text-[1.625rem] font-bold tabular-nums tracking-[-0.03em]">{{ is_numeric($value) ? number_format($value) : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <div class="space-y-10">
                @if (! $hasProducts)
                    <div class="card px-6 py-16 text-center" data-reveal>
                        <x-icon name="chart" :size="28" class="mx-auto text-ink-3" />
                        <p class="mt-4 font-semibold">No launches yet</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-ink-2">Submit your product, pick a launch date, and this becomes your launch control room: countdown, share kit, live votes and results.</p>
                        <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-6">Submit your product</a>
                    </div>
                @endif

                @foreach ($sections as $key => [$title, $hint])
                    @continue($groups[$key]->isEmpty())
                    <section data-reveal>
                        <h2 class="group-header">
                            <span>{{ $title }} <span class="ml-1 font-normal tabular-nums text-ink-2">{{ $groups[$key]->count() }}</span></span>
                            <span class="hidden text-xs font-normal text-ink-2 sm:inline">{{ $hint }}</span>
                        </h2>
                        <ul class="card overflow-hidden">
                            @foreach ($groups[$key] as $product)
                                @php
                                    $rank = $product->isLive() ? $product->dailyRank() : null;
                                    $days = $product->launch_date ? (int) today()->diffInDays($product->launch_date, false) : null;
                                    $canMove = in_array($product->status, ['pending', 'approved']) && $product->launch_date?->isFuture();
                                @endphp
                                <li class="list-row" style="--inset: 5rem">
                                    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5 md:flex-row md:items-center">
                                        <a href="{{ route('launch.hub', $product) }}" class="flex min-w-0 flex-1 items-center gap-4">
                                            <span class="app-icon size-12"><img src="{{ $product->logoUrl() }}" alt=""></span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold">{{ $product->name }}</span>
                                                <span class="meta flex flex-wrap">
                                                    <span class="font-medium text-ink">{{ $product->statusLabel() }}</span>
                                                    <span>{{ config("rankedlaunch.plans.{$product->plan}.name") }}</span>
                                                    @if ($product->category)<span>{{ $product->category->name }}</span>@endif
                                                    @if ($product->launch_date)
                                                        <span>{{ $product->launch_date->format('M j, Y') }}{{ $days > 0 ? ' · in '.$days.' '.Str::plural('day', $days) : '' }}</span>
                                                    @endif
                                                </span>
                                                @if ($product->status === 'rejected' && $product->rejection_reason)
                                                    <span class="mt-1 block text-xs text-danger">{{ $product->rejection_reason }}</span>
                                                @endif
                                            </span>
                                        </a>

                                        @if ($product->isLive())
                                            <dl class="flex shrink-0 gap-5 text-center tabular-nums md:gap-6">
                                                @foreach (['Rank' => $rank ? '#'.$rank : '—', 'Votes' => $product->votes_count, 'Views' => $product->views_count, 'Visits' => $product->clicks_count] as $l => $v)
                                                    <div><dd class="text-base font-bold">{{ is_numeric($v) ? number_format($v) : $v }}</dd><dt class="text-[0.6875rem] text-ink-2">{{ $l }}</dt></div>
                                                @endforeach
                                            </dl>
                                        @endif

                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            @if (in_array($product->status, ['draft', 'awaiting_payment']))
                                                <a href="{{ route('submit.plan', $product) }}" class="btn btn-primary btn-sm">Finish booking</a>
                                            @elseif (in_array($product->status, ['pending', 'approved']) && $product->plan === 'free' && ! $product->isLive())
                                                <a href="{{ route('submit.plan', $product) }}" class="btn btn-secondary btn-sm">Upgrade</a>
                                            @endif
                                            @if ($canMove)
                                                <a href="{{ route('launch.date', $product) }}" class="btn btn-outline btn-sm" title="Change launch date"><x-icon name="calendar" :size="15" /><span class="sr-only sm:not-sr-only">Date</span></a>
                                            @endif
                                            @if ($product->isLive())
                                                <a href="{{ route('product', $product) }}" class="btn btn-outline btn-sm">View</a>
                                            @endif
                                            <a href="{{ route('launch.hub', $product) }}" class="btn btn-secondary btn-sm">Launch hub</a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            {{-- Side panel --}}
            <aside class="space-y-10">
                <section data-reveal>
                    <h2 class="group-header">Account</h2>
                    <div class="card p-4">
                        <div class="flex items-center gap-3">
                            @if ($user->avatar)
                                <img src="{{ $user->avatar }}" alt="" class="size-10 rounded-full">
                            @else
                                <span class="grid size-10 place-items-center rounded-full bg-fill text-sm font-semibold">{{ $user->initials() }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                                <p class="truncate text-xs text-ink-2">{{ $user->deliverableEmail() ?? 'No email yet' }}</p>
                            </div>
                        </div>
                        @unless ($user->deliverableEmail())
                            <p class="mt-3 text-xs text-ink-2">Add an email so you get launch reminders and receipts.</p>
                        @endunless
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('account') }}" class="btn btn-secondary btn-sm">Settings</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline btn-sm w-full">Sign out</button></form>
                        </div>
                        @if ($user->is_admin)
                            <a href="{{ route('admin.index') }}" class="btn btn-primary btn-sm mt-2 w-full"><x-icon name="shield" :size="15" /> Admin dashboard</a>
                        @endif
                    </div>
                </section>

                <section data-reveal>
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

                <section class="card p-5" data-reveal>
                    <x-icon name="send" :size="20" />
                    <p class="mt-3 text-sm font-semibold">Launch tip</p>
                    <p class="mt-1 text-sm leading-relaxed text-ink-2">Most votes come in the first 3 hours. Post your launch on X and LinkedIn right after 00:00 UTC, and tell your users the day before.</p>
                </section>
            </aside>
        </div>
    </div>
</x-layout>
