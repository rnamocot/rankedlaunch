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

    {{-- Hero --}}
    <div class="relative isolate">
        <x-hero-backdrop />
    <section class="intro mx-auto max-w-4xl px-4 pt-20 pb-12 text-center sm:px-6 sm:pt-24">
        <h1 class="display">Launch your product.<br><span class="text-ink-3">Climb the ranks.</span></h1>
        <p class="mx-auto mt-6 max-w-xl text-[1.125rem] leading-relaxed text-ink-2">
            A daily launch board where makers get early users, a backlink that lasts, and a badge to prove it.
        </p>
        <div class="mt-9 flex flex-col items-center gap-3">
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
            <p class="text-sm text-ink-2">Free to launch. No card needed.</p>
        </div>
        {{-- Real top 4 once there's enough to rank; otherwise an honest "how it works" demo --}}
        @if ($today->count() >= 3)
            <x-live-ranking :products="$today->take(4)" :pulse="$pulse" class="mt-12" />
        @else
            <x-rank-demo class="mt-12" />
        @endif
    </section>
    </div>

    @if ($pricing->promoActive())
        <div class="mx-auto max-w-2xl px-4 pb-14 sm:px-6">
            <x-promo-banner :pricing="$pricing">
                <a href="{{ route('submit') }}" class="btn btn-primary btn-sm shrink-0">Claim a spot</a>
            </x-promo-banner>
        </div>
    @endif

    {{-- Showcase: today's board inside a tray --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="tray" data-reveal>
            <div class="flex flex-wrap items-center justify-between gap-3 px-3 pt-2 pb-3 sm:px-4">
                <div class="flex items-center gap-2.5">
                    <x-logo :size="22" :wordmark="false" class="text-ink" />
                    <span class="text-sm font-semibold">Launching today</span>
                    <span class="text-sm text-ink-3">{{ now()->format('M j') }}</span>
                </div>
                <span class="text-[0.8125rem] text-ink-2">Voting closes in <span class="font-semibold tabular-nums text-ink" data-countdown="{{ today('UTC')->addDay()->toIso8601String() }}">--:--:--</span></span>
            </div>

            <div class="grid grid-cols-1 gap-1.5 lg:grid-cols-[minmax(0,1fr)_19rem]">
                <div class="card overflow-hidden">
                    @if ($today->isNotEmpty())
                        <ol>
                            @foreach ($today as $i => $product)
                                <x-product-row :product="$product" :rank="$i + 1" :voted-ids="$votedIds" />
                            @endforeach
                        </ol>
                    @else
                        <div class="px-6 py-16 text-center">
                            <p class="font-semibold">Today’s launches go live at midnight UTC</p>
                            <p class="mt-1 text-sm text-ink-2">Want to be first in line tomorrow?</p>
                            <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-5">Book a launch date</a>
                        </div>
                    @endif
                </div>

                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between px-4 pt-4 pb-2">
                        <span class="text-sm font-semibold">Top this week</span>
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
                            <li class="px-4 py-6 text-center text-sm text-ink-2">No launches yet this week.</li>
                        @endforelse
                    </ol>
                    <div class="m-4 mt-3 rounded-xl bg-fill p-4">
                        <p class="text-sm font-semibold">Top 3 get a winner badge</p>
                        <p class="mt-1 text-xs leading-relaxed text-ink-2">Weekly winners get “#1 Product of the Week” for their site.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Search + category chips --}}
    <section class="mx-auto mt-12 max-w-4xl px-4 text-center sm:px-6" data-reveal>
        <form method="GET" action="{{ route('discover') }}" class="relative mx-auto mb-8 max-w-lg" role="search">
            <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-2" />
            <label for="home-search" class="sr-only">Search tools</label>
            <input id="home-search" name="q" placeholder="Search {{ number_format(\App\Models\Product::live()->count()) }} tools" class="input !h-12 !rounded-xl bg-surface !pl-11 !pr-24 shadow-[inset_0_0_0_1px_var(--line)]">
            <button class="btn btn-primary btn-sm absolute top-1/2 right-2 -translate-y-1/2">Search</button>
        </form>
        <p class="text-sm text-ink-2">Browse by category</p>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
            @foreach ($categories as $category)
                <a href="{{ route('category', $category) }}" class="chip !pl-3.5">
                    {{ $category->name }} <span class="tabular-nums text-ink-3">{{ $category->products_count }}</span>
                </a>
            @endforeach
            <a href="{{ route('discover') }}" class="chip !pl-3.5 font-semibold">All categories <x-icon name="arrow-right" :size="13" :stroke="2" /></a>
        </div>
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

        @if ($yesterday->isNotEmpty())
            <section>
                <h2 class="section-title">Yesterday’s winners</h2>
                <p class="mt-3 text-center text-ink-2">Final ranking from {{ today()->subDay()->format('l, M j') }}.</p>
                <div class="tray mt-8" data-reveal>
                    <ol class="card overflow-hidden">
                        @foreach ($yesterday as $i => $product)
                            <x-product-row :product="$product" :rank="$i + 1" :voted-ids="$votedIds" />
                        @endforeach
                    </ol>
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
        <p class="mx-auto mt-4 max-w-md text-ink-2">Free launches wait about {{ (int) ceil(config('rankedlaunch.free_wait_days') / 7) }} weeks. A {{ config('rankedlaunch.plans.pro.name') }} picks any date and adds a dofollow backlink for {{ \App\Services\Pricing::dollars($pricing->priceFor('pro')) }}, one-time.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('submit') }}" class="btn btn-primary btn-lg">Launch your product</a>
            <a href="{{ route('launch') }}" class="btn btn-outline btn-lg">Why launch here</a>
        </div>
    </section>
</x-layout>
