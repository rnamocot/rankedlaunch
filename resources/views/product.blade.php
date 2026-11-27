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

    @php($badgeHtml = '<a href="'.route('product', $product).'" target="_blank"><img src="'.route('badge', $product).'" alt="'.e($product->name).' on RankedLaunch" width="220" height="54"></a>')

    <div class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-ink">
            <x-icon name="chevron-left" :size="16" :stroke="2" /> Back
        </a>
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

    <div class="mx-auto grid grid-cols-1 max-w-6xl gap-10 px-4 pt-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <article>
            <header class="flex flex-col gap-6 sm:flex-row sm:items-start">
                <span class="app-icon size-20 sm:size-24"><img src="{{ $product->logoUrl() }}" alt="{{ $product->name }} logo" width="96" height="96"></span>
                <div class="min-w-0 flex-1">
                    <h1 class="text-[2rem] font-bold leading-tight tracking-[-0.03em]">{{ $product->name }}</h1>
                    <p class="mt-1 text-lg text-ink-2">{{ $product->tagline }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-2.5">
                        <a href="{{ $product->outboundUrl() }}" target="_blank" rel="{{ $product->linkRel() }}" class="btn btn-primary" data-track-click="{{ route('click', $product) }}">
                            Visit {{ $product->host() }} <x-icon name="arrow-up-right" :size="15" :stroke="2" />
                        </a>
                        @if ($product->isLive())
                            <x-vote-button :product="$product" :voted-ids="$votedIds" class="!h-10 !w-auto !flex-row !gap-1.5 !rounded-full px-4" />
                        @endif
                    </div>
                </div>
            </header>

            @if ($product->screenshotUrls())
                <div class="-mx-4 mt-10 flex snap-x gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                    @foreach ($product->screenshotUrls() as $src)
                        <img src="{{ $src }}" alt="{{ $product->name }} screenshot" loading="lazy"
                             class="aspect-[16/10] w-[85%] shrink-0 snap-start rounded-2xl object-cover outline outline-1 -outline-offset-1 outline-line sm:w-[30rem]">
                    @endforeach
                </div>
            @endif

            <section class="mt-10">
                <h2 class="group-header !px-0">About {{ $product->name }}</h2>
                <div class="prose-app text-[1rem]">
                    @foreach (preg_split('/\n\s*\n/', trim($product->description)) as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                </div>
            </section>

            @if ($product->isLive())
                <x-comments :product="$product" :comments="$comments" />
            @endif

            @if ($related->isNotEmpty())
                <section class="mt-12">
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

        <aside class="space-y-8">
            <section>
                <h2 class="group-header">Details</h2>
                <dl class="card overflow-hidden text-sm">
                    @php($rows = array_filter([
                        'Launched' => $product->launch_date?->format('M j, Y'),
                        'Rank that day' => $dailyRank ? '#'.$dailyRank : null,
                        'Rank that week' => $weeklyRank ? '#'.$weeklyRank : null,
                        'Upvotes' => $product->isLive() ? number_format($product->votes_count) : null,
                        'Category' => $product->category?->name,
                        'Pricing' => $product->pricingLabel(),
                        'Website' => $product->host(),
                    ]))
                    @foreach ($rows as $label => $value)
                        <div class="list-row flex items-center justify-between gap-4 px-4 py-3">
                            <dt class="text-ink-2">{{ $label }}</dt>
                            <dd class="truncate font-medium tabular-nums">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @if ($product->user)
                <section>
                    <h2 class="group-header">Maker</h2>
                    <a href="{{ $product->user->profileUrl() }}" class="card card-hover flex items-center gap-3 px-4 py-3">
                        @if ($product->user->avatar)
                            <img src="{{ $product->user->avatar }}" alt="" class="size-9 rounded-full">
                        @else
                            <span class="grid size-9 place-items-center rounded-full bg-fill text-sm font-semibold">{{ $product->user->initials() }}</span>
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $product->user->name }}</span>
                            <span class="block truncate text-xs text-ink-2">{{ '@'.$product->user->username }}</span>
                        </span>
                        <x-icon name="chevron-right" :size="16" class="text-ink-3" />
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
