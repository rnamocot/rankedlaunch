<x-layout>
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Organization', '@id' => url('/').'#org', 'name' => 'RankedLaunch', 'url' => url('/'), 'logo' => url('/apple-touch-icon.png'), 'sameAs' => ['https://x.com/rankedlaunch']],
                ['@type' => 'WebSite', '@id' => url('/').'#site', 'name' => 'RankedLaunch', 'url' => url('/'), 'publisher' => ['@id' => url('/').'#org'], 'potentialAction' => ['@type' => 'SearchAction', 'target' => route('discover').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
                ['@type' => 'ItemList', 'name' => 'Products launching today', 'itemListElement' => $today->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => route('product', $p), 'name' => $p->name])->all()],
            ],
        ]" />
    </x-slot:head>
    @php($faces = $today->concat($week)->unique('id')->take(4))

    {{-- Hero: pitch on the left, live rankings dashboard on the right --}}
    <div class="relative isolate">
        <x-hero-backdrop :spotlight="$spotlight" :logos="$today->take(6)" />
        <section class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 px-4 pt-16 pb-14 sm:px-6 sm:pt-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-14">
            <div class="intro text-center lg:text-left">
                <p class="text-sm font-medium text-ink-2">The daily launch board for indie makers</p>
                <h1 class="display mt-3 !text-[clamp(2.5rem,5.6vw,4rem)]">Launch your product.<br><span class="text-ink-3">Climb the ranks.</span></h1>
                <p class="mx-auto mt-5 max-w-lg text-[1.0625rem] leading-relaxed text-ink-2 lg:mx-0">
                    List your app today or pick your launch day. Get upvotes, early users, a backlink that lasts, and a badge to prove your rank.
                </p>
                <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start">
                    <a href="{{ route('submit') }}" class="btn btn-primary btn-lg !h-[3.25rem] max-w-full min-[380px]:!pl-2.5 !pr-5">
                        @if ($faces->isNotEmpty())
                            <span class="hidden -space-x-2 min-[380px]:flex">
                                @foreach ($faces as $face)
                                    <span class="app-icon size-8 !rounded-[0.55rem] ring-2 ring-[#2a2a2c]"><img src="{{ $face->logoUrl() }}" alt=""></span>
                                @endforeach
                            </span>
                        @endif
                        <span class="ml-1">Launch your product</span>
                        <x-icon name="arrow-right" :size="16" :stroke="2" />
                    </a>
                    <a href="{{ route('discover') }}" class="btn btn-outline btn-lg">Explore apps</a>
                </div>
                <p class="mt-3 text-sm text-ink-2">Free to list. No card needed.</p>

                <x-flip-clock :to="today('UTC')->addDay()" label="Today’s voting closes in" class="mt-10 flex flex-col items-center lg:items-start" />

                <dl class="mx-auto mt-10 grid max-w-md grid-cols-3 gap-6 lg:mx-0">
                    @foreach ($heroStats as $label => $value)
                        <div>
                            <dd class="text-[1.75rem] font-bold leading-none tabular-nums tracking-[-0.03em]">{{ number_format($value) }}</dd>
                            <dt class="mt-1.5 text-xs text-ink-2">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div data-reveal>
                {{-- Real rankings once there's enough to rank; otherwise an honest "how it works" demo --}}
                @if ($today->count() >= 3)
                    <x-live-ranking :products="$today->take(5)" :week="$week" :pulse="$pulse" />
                @else
                    <x-rank-demo />
                @endif
            </div>
        </section>
    </div>

    {{-- Feed: today's board, then earlier days. Sidebar: this week, upcoming, top makers, categories. --}}
    <section class="mx-auto grid max-w-6xl grid-cols-1 gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-10">
        <div class="min-w-0" data-feed>
            @php($boardCategories = $today->pluck('category')->filter()->unique('id')->sortBy('name')->values())
            <div data-board>
                <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-1 px-1 pb-3">
                    <h2 class="title-2">Launching today</h2>
                    <span class="text-[0.8125rem] text-ink-2">Voting closes in <span class="font-semibold tabular-nums text-ink" data-countdown="{{ today('UTC')->addDay()->toIso8601String() }}">--:--:--</span></span>
                </div>

                @if ($boardCategories->count() > 1)
                    <div class="flex gap-1.5 overflow-x-auto px-1 pb-3" data-board-filters aria-label="Filter by category">
                        <button type="button" data-filter="" aria-pressed="true" class="chip !h-8 shrink-0 !pl-3 !text-xs aria-pressed:!bg-ink aria-pressed:!text-on-ink">All <span class="tabular-nums opacity-60">{{ $today->count() }}</span></button>
                        @foreach ($boardCategories as $c)
                            <button type="button" data-filter="{{ $c->slug }}" aria-pressed="false" class="chip !h-8 shrink-0 !pl-3 !text-xs aria-pressed:!bg-ink aria-pressed:!text-on-ink">{{ $c->name }} <span class="tabular-nums opacity-60">{{ $today->where('category_id', $c->id)->count() }}</span></button>
                        @endforeach
                    </div>
                @endif

                <div class="tray" data-reveal>
                    <div class="card overflow-hidden">
                        @if ($today->isNotEmpty())
                            <ol>
                                @foreach ($today as $i => $product)
                                    <x-product-row :product="$product" :rank="$i + 1" :voted-ids="$votedIds" />
                                @endforeach
                            </ol>
                            <p data-board-empty hidden class="px-6 py-10 text-center text-sm text-ink-2">No launches in this category today.</p>
                        @else
                            <div class="px-6 py-16 text-center">
                                <p class="font-semibold">New launches go live at <x-local-time :at="today('UTC')->addDay()" /></p>
                                <p class="mt-1 text-sm text-ink-2">Want to be first in line tomorrow?</p>
                                <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-5">Book a launch date</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($yesterday->isNotEmpty())
                @include('partials.feed-day', ['day' => today()->subDay(), 'products' => $yesterday])
            @endif

            <div data-feed-more>
                @if ($nextDay)
                    <button type="button" class="btn btn-outline mt-8 w-full" data-feed-next="{{ route('home.day', $nextDay->toDateString()) }}">
                        Show earlier launches
                    </button>
                @endif
            </div>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start" aria-label="More on RankedLaunch">
            {{-- Top this week --}}
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between px-4 pt-4 pb-2">
                    <h2 class="text-sm font-semibold">Top this week</h2>
                    <a href="{{ route('rankings', 'week') }}" class="text-xs font-medium text-ink-2 hover:text-ink">See all</a>
                </div>
                <ol>
                    @forelse ($week as $i => $product)
                        <li class="list-row" style="--inset: 3.5rem">
                            <a href="{{ route('product', $product) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-2">
                                <span class="w-4 text-sm font-semibold tabular-nums {{ $i < 3 ? 'text-ink' : 'text-ink-3' }}">{{ $i + 1 }}</span>
                                <span class="app-icon size-8"><img src="{{ $product->logoUrl() }}" alt="" loading="lazy"></span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $product->name }}</span>
                                <span class="flex items-center gap-0.5 text-xs font-medium tabular-nums text-ink-2">
                                    <x-icon name="chevron-up" :size="13" :stroke="2.25" />{{ $product->votes_count }}
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 pb-5 pt-2 text-sm text-ink-2">No launches yet this week.</li>
                    @endforelse
                </ol>
                <p class="mx-4 mt-2 mb-4 rounded-xl bg-fill px-3.5 py-3 text-xs leading-relaxed text-ink-2"><span class="font-semibold text-ink">Top 5 win awards.</span> Day, week, month and year, with a badge for your site.</p>
            </section>

            {{-- Coming up --}}
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between px-4 pt-4 pb-2">
                    <h2 class="text-sm font-semibold">Coming up</h2>
                    <a href="{{ route('upcoming') }}" class="text-xs font-medium text-ink-2 hover:text-ink">{{ $upcomingTotal > 0 ? 'All '.$upcomingTotal : 'Upcoming' }}</a>
                </div>
                <ul>
                    @forelse ($upcoming as $product)
                        <li class="list-row flex items-center gap-3 px-4 py-2.5" style="--inset: 3.5rem">
                            <span class="app-icon size-8"><img src="{{ $product->logoUrl() }}" alt="" loading="lazy"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $product->name }}</span>
                                <span class="block truncate text-xs text-ink-2">{{ $product->launch_date->isTomorrow() ? 'Tomorrow' : $product->launch_date->format('D, M j') }}</span>
                            </span>
                            <x-toggle-button :url="route('remind', $product)" :active="isset($remindedIds[$product->id])" icon="bell" on="On" off="Notify" :label="'Notify me when '.$product->name.' launches'" class="toggle-btn-sm" />
                        </li>
                    @empty
                        <li class="px-4 pb-4 pt-1 text-sm text-ink-2">Nothing scheduled yet. <a href="{{ route('submit') }}" class="font-medium text-ink underline decoration-ink-3 underline-offset-4">Book tomorrow</a></li>
                    @endforelse
                </ul>
                <div class="h-2"></div>
            </section>

            {{-- Top makers this month --}}
            @if ($topMakers->isNotEmpty())
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between px-4 pt-4 pb-2">
                        <h2 class="text-sm font-semibold">Top makers this month</h2>
                        <a href="{{ route('leaderboard') }}" class="text-xs font-medium text-ink-2 hover:text-ink">Leaderboard</a>
                    </div>
                    <ol class="pb-2">
                        @foreach ($topMakers as $maker)
                            <li class="list-row" style="--inset: 3.5rem">
                                <a href="{{ $maker->profileUrl() }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-2">
                                    <x-avatar :user="$maker" class="size-8 text-xs" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $maker->name }}</span>
                                        <span class="block truncate text-xs text-ink-2">{{ $maker->launches }} {{ Str::plural('launch', $maker->launches) }}</span>
                                    </span>
                                    <span class="text-xs font-medium tabular-nums text-ink-2">{{ number_format((int) $maker->earned) }} upvotes</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Categories --}}
            @if ($categories->isNotEmpty())
                <section>
                    <h2 class="group-header">Browse categories</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($categories->take(10) as $category)
                            <a href="{{ route('category', $category) }}" class="chip !h-8 !pl-3 !text-xs">{{ $category->name }} <span class="tabular-nums text-ink-3">{{ $category->products_count }}</span></a>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </section>

    <div class="hairline mx-auto mt-20 max-w-6xl"></div>

    <div class="mx-auto mt-20 max-w-3xl space-y-14 px-4 sm:px-6">
        @if ($featured->isNotEmpty())
            <section>
                <h2 class="section-title">Featured launches</h2>
                <p class="mt-3 text-center text-ink-2">
                    Pro launches, shown in rotation{{ $featuredTotal > $featured->count() ? ' ('.$featured->count().' of '.$featuredTotal.' right now)' : '' }}. Rankings below are by votes only.
                </p>
                <div class="tray mt-8" data-reveal>
                    <ul class="card overflow-hidden">
                        @foreach ($featured as $product)
                            <x-product-row :product="$product" :voted-ids="$votedIds" pinned />
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif


    </div>

    <div class="mx-auto mt-20 max-w-3xl px-4 sm:px-6">
        <x-newsletter source="home" />
    </div>

    {{-- Maker CTA --}}
    <section class="mx-auto mt-24 max-w-3xl px-4 text-center sm:px-6" data-reveal>
        <h2 class="section-title">Built something?<br><span class="text-ink-3">Put it on the board.</span></h2>
        <p class="mx-auto mt-4 max-w-md text-ink-2">List it today or pick your launch day, free.@if ($paymentsAvailable) A {{ config('rankedlaunch.plans.pro.name') }} adds a dofollow backlink for {{ \App\Services\Pricing::dollars($pricing->priceFor('pro')) }}, one-time.@else Votes, stats and badges included while we’re in early access.@endif</p>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('submit') }}" class="btn btn-primary btn-lg">Launch your product</a>
            <a href="{{ route('launch') }}" class="btn btn-outline btn-lg">Why launch here</a>
        </div>
    </section>
</x-layout>
