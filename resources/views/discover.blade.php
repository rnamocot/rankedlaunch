@php
    $activeCategory = $categories->firstWhere('slug', $filters['category'] ?? null);
    $heading = $activeCategory ? $activeCategory->name.' tools' : 'Discover tools';
@endphp
<x-layout :title="$filters['q'] ? 'Search: '.$filters['q'] : 'Discover the best new software tools'"
          description="Search and filter the best new SaaS, AI and developer tools from indie makers. Free, freemium and paid, ranked by real users."
          :noindex="filled($filters['q'])">
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $heading,
            'itemListElement' => $products->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $products->firstItem() + $i, 'url' => route('product', $p), 'name' => $p->name])->all(),
        ]" />
    </x-slot:head>

    <section class="intro mx-auto max-w-3xl px-4 pt-14 pb-8 text-center sm:px-6">
        <h1 class="large-title">Find the right tool.</h1>
        <p class="mx-auto mt-3 max-w-lg text-ink-2">New software from independent makers, ranked by the people who use it.</p>

        <form method="GET" action="{{ route('discover') }}" class="mx-auto mt-8 max-w-xl" role="search">
            <div class="relative">
                <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-2" />
                <label for="q" class="sr-only">Search tools</label>
                <input id="q" name="q" value="{{ $filters['q'] }}" @if(! $filters['q'] && ! request()->hasAny(['category', 'pricing', 'page'])) autofocus @endif placeholder="Try “invoices”, “analytics” or “email”" class="input !h-12 !rounded-xl !pl-11 !text-base">
            </div>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <x-category-select name="category" by="slug" :categories="$categories" :value="$filters['category'] ?? null" placeholder="All categories" :placeholder-selectable="true"
                                   class="!h-9 !w-auto max-w-[12rem] !text-sm" aria-label="Category" onchange="this.form.submit()" />
                <select name="pricing" class="input !h-9 !w-auto !text-sm" aria-label="Pricing" onchange="this.form.submit()">
                    <option value="">Any price</option>
                    @foreach (\App\Models\Product::PRICING_TYPES as $k => $l)<option value="{{ $k }}" @selected(($filters['pricing'] ?? null) === $k)>{{ $l }}</option>@endforeach
                </select>
                <select name="sort" class="input !h-9 !w-auto !text-sm" aria-label="Sort" onchange="this.form.submit()">
                    @foreach ($sorts as $k => $l)<option value="{{ $k }}" @selected($filters['sort'] === $k)>{{ $l }}</option>@endforeach
                </select>
                <button class="btn btn-primary btn-sm !h-9">Search</button>
            </div>
        </form>
    </section>

    <div class="mx-auto max-w-5xl px-4 sm:px-6">
        <p class="mb-4 px-1 text-sm text-ink-2">
            {{ number_format($products->total()) }} {{ Str::plural('tool', $products->total()) }}{{ $filters['q'] ? ' for “'.$filters['q'].'”' : '' }}
            @if (array_filter(\Illuminate\Support\Arr::only($filters, ['q', 'category', 'pricing'])))
                · <a href="{{ route('discover') }}" class="font-medium text-ink underline underline-offset-2">Clear filters</a>
            @endif
        </p>

        @if ($products->isNotEmpty())
            <div class="tray grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
                @foreach ($products as $product)
                    <a href="{{ route('product', $product) }}" class="card card-hover group flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <span class="app-icon size-12"><img src="{{ $product->logoUrl() }}" alt="" loading="lazy"></span>
                            <span class="flex items-center gap-0.5 text-xs font-semibold tabular-nums text-ink-2"><x-icon name="chevron-up" :size="13" :stroke="2.25" />{{ $product->votes_count }}</span>
                        </div>
                        <p class="mt-4 font-semibold tracking-[-0.015em]">{{ $product->name }}</p>
                        <p class="mt-1 line-clamp-2 flex-1 text-sm text-ink-2">{{ $product->tagline }}</p>
                        <p class="meta mt-3 flex"><span>{{ $product->category?->name }}</span><span>{{ $product->isListing() ? 'Via '.$product->sourceLabel() : $product->pricingLabel() }}</span></p>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $products->links() }}</div>
        @else
            <div class="card px-6 py-14 text-center">
                <p class="font-semibold">No tools match yet</p>
                <p class="mt-1 text-sm text-ink-2">Try a broader search, or browse a category.</p>
                <a href="{{ route('discover') }}" class="btn btn-secondary btn-sm mt-5">See all tools</a>
            </div>
        @endif

        <div class="mt-16"><x-newsletter source="discover" /></div>
    </div>
</x-layout>
