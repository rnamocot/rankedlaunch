<x-layout :title="$product->name.' · '.$product->tagline" :description="Str::limit(strip_tags($product->description), 155)" :image="route('og.product', $product)" :noindex="! $product->isLive()">
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                [
                    '@type' => 'SoftwareApplication',
                    'name' => $product->name,
                    'description' => $product->tagline,
                    'url' => $product->url,
                    'image' => $product->logoUrl(),
                    'applicationCategory' => $product->category?->name,
                    'operatingSystem' => 'Web',
                    'datePublished' => $product->launch_date?->toDateString(),
                    'interactionStatistic' => ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/LikeAction', 'userInteractionCount' => $product->votes_count],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => array_values(array_filter([
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'RankedLaunch', 'item' => route('home')],
                        $product->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $product->category->name, 'item' => route('category', $product->category)] : null,
                        ['@type' => 'ListItem', 'position' => $product->category ? 3 : 2, 'name' => $product->name, 'item' => route('product', $product)],
                    ])),
                ],
            ])),
        ]" />
    </x-slot:head>

    @php
        $shots = $product->screenshotUrls();
        $commentTotal = $comments->sum(fn ($c) => 1 + $c->replies->count());
        $isToday = $product->isLive() && $product->launch_date->isToday();
    @endphp

    <div class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
        <nav class="flex items-center gap-1.5 text-sm text-ink-2" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">Launches</a>
            @if ($product->category)
                <x-icon name="chevron-right" :size="14" class="text-ink-3" />
                <a href="{{ route('category', $product->category) }}" class="hover:text-ink">{{ $product->category->name }}</a>
            @endif
            <x-icon name="chevron-right" :size="14" class="text-ink-3" />
            <span class="truncate text-ink">{{ $product->name }}</span>
        </nav>
    </div>

    @if ($isOwner && ! $product->isLive())
        <div class="mx-auto max-w-6xl px-4 pt-5 sm:px-6">
            <div class="card flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                <x-icon name="clock" :size="18" />
                <span class="font-medium">Preview — only you can see this page.</span>
                <span class="text-ink-2">Status: {{ $product->statusLabel() }}@if($product->launch_date) · Launches {{ $product->launch_date->format('M j, Y') }}@endif</span>
            </div>
        </div>
    @endif

    {{-- Header: identity on the left, actions on the right --}}
    <header class="mx-auto mt-6 flex max-w-6xl flex-col gap-6 px-4 sm:px-6 md:flex-row md:items-center">
        <div class="flex min-w-0 flex-1 items-center gap-5">
            <span class="app-icon size-20 sm:size-24"><img src="{{ $product->logoUrl() }}" alt="{{ $product->name }} logo" width="96" height="96"></span>
            <div class="min-w-0">
                <h1 class="text-[1.75rem] font-bold leading-tight tracking-[-0.03em] sm:text-[2.125rem]">{{ $product->name }}</h1>
                <p class="mt-1 text-[1.0625rem] text-ink-2">{{ $product->tagline }}</p>
                <div class="mt-2.5 flex flex-wrap items-center gap-2">
                    @if ($award)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-ink px-2.5 py-1 text-xs font-semibold text-on-ink"><x-icon name="medal" :size="14" class="text-[var(--accent)]" />{{ $award['label'] }}</span>
                    @elseif ($isToday && $dailyRank)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-ink px-2.5 py-1 text-xs font-semibold text-on-ink">#{{ $dailyRank }} today</span>
                    @endif
                    <span class="meta flex">
                        @if ($product->category)<span>{{ $product->category->name }}</span>@endif
                        @if ($product->isListing())<span>Via {{ $product->sourceLabel() }}</span>@else<span>{{ $product->pricingLabel() }}</span>@endif
                        @if ($product->isFeatured())<span class="font-medium text-ink">Featured</span>@endif
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if ($product->isLive())
                <x-toggle-button :url="route('bookmark', $product)" :active="$isSaved" icon="bookmark" on="Saved" off="Save" :label="'Save '.$product->name" />
            @endif
            <a href="{{ $product->outboundUrl() }}" target="_blank" rel="{{ $product->linkRel() }}" class="btn btn-outline !rounded-full" data-track-click="{{ route('click', $product) }}">
                Visit <x-icon name="arrow-up-right" :size="15" :stroke="2" />
            </a>
            @if ($product->isLive())
                <x-vote-button :product="$product" :voted-ids="$votedIds" class="!h-10 !w-auto !flex-row !gap-1.5 !rounded-full !px-5 text-[0.9375rem]" />
            @endif
        </div>
    </header>

    {{-- Section links --}}
    <nav class="mx-auto mt-8 max-w-6xl px-4 sm:px-6" aria-label="Sections">
        <div class="flex gap-6 border-b border-line text-sm font-medium">
            <a href="#overview" class="-mb-px border-b-2 border-ink pb-3 text-ink">Overview</a>
            @if ($product->isLive())
                <a href="#comments" class="-mb-px border-b-2 border-transparent pb-3 text-ink-2 hover:text-ink">Comments <span class="tabular-nums text-ink-3">{{ $commentTotal }}</span></a>
            @endif
            @if ($related->isNotEmpty())
                <a href="#alternatives" class="-mb-px border-b-2 border-transparent pb-3 text-ink-2 hover:text-ink">Alternatives</a>
            @endif
        </div>
    </nav>

    <div class="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-4 pt-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <article id="overview" class="min-w-0 scroll-mt-24">
            @if ($shots)
                <div data-gallery>
                    <button type="button" class="block w-full cursor-zoom-in" aria-label="Open screenshot">
                        <img data-gallery-main src="{{ $shots[0] }}" alt="{{ $product->name }} screenshot"
                             class="aspect-[16/10] w-full rounded-2xl bg-fill object-cover outline outline-1 -outline-offset-1 outline-line">
                    </button>
                    @if (count($shots) > 1)
                        <div class="mt-3 flex gap-2.5 overflow-x-auto pb-1">
                            @foreach ($shots as $i => $src)
                                <button type="button" data-gallery-thumb="{{ $src }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Screenshot {{ $i + 1 }}"
                                        class="gallery-thumb shrink-0 overflow-hidden rounded-xl">
                                    <img src="{{ $src }}" alt="" loading="lazy" class="aspect-[16/10] w-28 object-cover outline outline-1 -outline-offset-1 outline-line sm:w-32">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div hidden data-lightbox class="fixed inset-0 z-[70] grid place-items-center bg-black/85 p-4" role="dialog" aria-modal="true" aria-label="{{ $product->name }} screenshots">
                        <img src="" alt="{{ $product->name }} screenshot" class="max-h-[88vh] max-w-full rounded-xl object-contain">
                        <button type="button" data-lightbox-close class="absolute top-4 right-4 grid size-10 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Close"><x-icon name="close" :size="20" /></button>
                        @if (count($shots) > 1)
                            <button type="button" data-lightbox-prev class="absolute top-1/2 left-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Previous"><x-icon name="chevron-left" :size="22" /></button>
                            <button type="button" data-lightbox-next class="absolute top-1/2 right-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Next"><x-icon name="chevron-right" :size="22" /></button>
                        @endif
                    </div>
                </div>
            @endif

            <section class="{{ $shots ? 'mt-10' : '' }}">
                <h2 class="group-header !px-0">About {{ $product->name }}</h2>
                <div class="prose-app text-[1rem]">
                    @foreach (preg_split('/\n\s*\n/', trim($product->description)) as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                </div>
            </section>

            @if ($product->isLive())
                <x-comments :product="$product" :comments="$comments" :sort="$commentSort" :voted-ids="$commentVotedIds" />
            @endif

            @if ($related->isNotEmpty())
                <section id="alternatives" class="mt-12 scroll-mt-24">
                    <h2 class="group-header !px-0">
                        <span>{{ $product->name }} alternatives</span>
                        <a href="{{ $related->count() >= 2 ? route('alternatives', $product) : route('category', $product->category) }}">Compare all</a>
                    </h2>
                    <ul class="card overflow-hidden">
                        @foreach ($related as $item)
                            <x-product-row :product="$item" :voted-ids="$votedIds" />
                        @endforeach
                    </ul>
                </section>
            @endif
        </article>

        <aside class="space-y-6 lg:sticky lg:top-20 lg:self-start">
            {{-- Directory listing: imported, so it never ranks; the maker can claim it --}}
            @if ($product->isListing())
                <section class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-2">Directory listing</p>
                    <p class="mt-2 text-sm leading-relaxed">Listed by RankedLaunch from <span class="font-semibold">{{ $product->sourceLabel() }}</span>{{ $product->source_author ? ', shared by '.$product->source_author : '' }}. Listings don’t enter rankings or awards.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($product->source_url)
                            <a href="{{ $product->source_url }}" target="_blank" rel="nofollow noopener" class="btn btn-secondary btn-sm">View the {{ $product->sourceLabel() }} post <x-icon name="arrow-up-right" :size="13" :stroke="2" /></a>
                        @endif
                        <a href="mailto:{{ config('rankedlaunch.contact_email') }}?subject={{ rawurlencode('Claim '.$product->name.' on RankedLaunch') }}&body={{ rawurlencode('Hi! I make '.$product->name.' ('.$product->url.') and would like to claim this listing: '.route('product', $product)) }}" class="btn btn-outline btn-sm">Is this yours? Claim it</a>
                    </div>
                </section>
            @endif

            {{-- Launch results --}}
            @if ($product->isRankable())
                <section class="card p-5">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium text-ink-2">{{ $isToday ? 'Launching today' : 'Launched '.$product->launch_date->format('M j, Y') }}</p>
                            <p class="mt-1 text-[2rem] font-bold leading-none tabular-nums tracking-[-0.03em]">{{ number_format($product->votes_count) }}</p>
                            <p class="mt-1 text-xs text-ink-2">upvotes</p>
                        </div>
                        @if ($isToday)
                            <p class="text-right text-xs text-ink-2">Voting closes in<br><span class="font-semibold tabular-nums text-ink" data-countdown="{{ today('UTC')->addDay()->toIso8601String() }}">--:--:--</span></p>
                        @endif
                    </div>
                    <dl class="mt-5 grid grid-cols-3 gap-2 text-center">
                        @foreach (['Day' => $dailyRank, 'Week' => $weeklyRank, 'Month' => $monthlyRank] as $label => $rank)
                            <div class="rounded-xl bg-fill px-2 py-2.5">
                                <dd class="text-[1.0625rem] font-bold tabular-nums {{ $rank && $rank <= \App\Models\Product::AWARD_TOP ? 'text-ink' : 'text-ink-2' }}">{{ $rank ? '#'.$rank : '–' }}</dd>
                                <dt class="text-[0.6875rem] font-medium text-ink-2">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-3 text-[0.6875rem] leading-relaxed text-ink-3">Top {{ \App\Models\Product::AWARD_TOP }} of each period win an award once it ends.</p>
                </section>
            @endif

            <section>
                <h2 class="group-header">Details</h2>
                <dl class="card overflow-hidden text-sm">
                    @php($rows = array_filter([
                        'Category' => $product->category?->name,
                        'Pricing' => $product->isListing() ? null : $product->pricingLabel(),
                        'Website' => $product->host(),
                        ($product->isListing() ? 'Listed' : 'Launch date') => $product->launch_date?->format('M j, Y'),
                    ]))
                    @foreach ($rows as $label => $value)
                        <div class="list-row flex items-center justify-between gap-4 px-4 py-3">
                            <dt class="text-ink-2">{{ $label }}</dt>
                            <dd class="truncate font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($product->user && ! $product->isListing())
                <section>
                    <h2 class="group-header">Launched by</h2>
                    <a href="{{ $product->user->profileUrl() }}" class="card card-hover block p-4">
                        <span class="flex items-center gap-3">
                            <x-avatar :user="$product->user" class="size-11 text-sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold">{{ $product->user->name }}</span>
                                <span class="block truncate text-xs text-ink-2">{{ '@'.$product->user->username }}</span>
                            </span>
                            <x-icon name="chevron-right" :size="16" class="text-ink-3" />
                        </span>
                        @if ($product->user->bio)
                            <span class="mt-3 block text-sm leading-relaxed text-ink-2">{{ $product->user->bio }}</span>
                        @endif
                        @if ($makerStats)
                            <span class="mt-3 flex gap-4 border-t border-line pt-3 text-xs text-ink-2">
                                <span><span class="font-semibold tabular-nums text-ink">{{ $makerStats['launches'] }}</span> {{ Str::plural('launch', $makerStats['launches']) }}</span>
                                <span><span class="font-semibold tabular-nums text-ink">{{ number_format($makerStats['upvotes']) }}</span> upvotes</span>
                                @if ($product->user->currentStreak() > 1)
                                    <span class="inline-flex items-center gap-0.5"><x-icon name="flame" :size="13" class="text-[var(--accent)]" /><span class="font-semibold tabular-nums text-ink">{{ $product->user->currentStreak() }}</span> day streak</span>
                                @endif
                            </span>
                        @endif
                    </a>
                </section>
            @endif

            @if ($isOwner && $product->isLive())
                <section>
                    <h2 class="group-header">Your badge</h2>
                    <x-badge-picker :product="$product" />
                </section>
            @endif

            @if ($isOwner)
                <a href="{{ route('launch.hub', $product) }}" class="btn btn-outline w-full">Open launch hub</a>
            @endif
        </aside>
    </div>
</x-layout>
