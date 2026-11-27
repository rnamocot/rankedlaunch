<x-layout :noindex="$products->isEmpty() && $featured->isEmpty()" :title="'Best '.$category->name.' products'" :description="'Discover the best new '.$category->name.' products, ranked by makers and early adopters on RankedLaunch.'">
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'CollectionPage', 'name' => 'Best '.$category->name.' products', 'url' => route('category', $category)],
                ['@type' => 'ItemList', 'itemListElement' => $products->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $products->firstItem() + $i, 'url' => route('product', $p), 'name' => $p->name])->all()],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'RankedLaunch', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $category->name, 'item' => route('category', $category)],
                ]],
            ],
        ]" />
    </x-slot:head>
    <section class="mx-auto max-w-3xl px-4 pt-12 pb-8 sm:px-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-ink">
            <x-icon name="chevron-left" :size="16" :stroke="2" /> All launches
        </a>
        <h1 class="large-title mt-4">{{ $category->name }}</h1>
        <p class="mt-3 text-ink-2">The best {{ $category->name }} products launched on RankedLaunch, ranked by votes.</p>

        @if ($category->group)<p class="mt-6 text-xs font-semibold text-ink-2">More in {{ $category->group }}</p>@endif
        <nav class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm" aria-label="Categories">
            @foreach ($categories as $c)
                <a href="{{ route('category', $c) }}" class="font-medium {{ $c->is($category) ? 'text-ink underline underline-offset-4' : 'text-ink-2 hover:text-ink' }}">{{ $c->name }}</a>
            @endforeach
        </nav>
    </section>

    <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6">
        @if ($featured->isNotEmpty())
            <section>
                <h2 class="group-header">Featured</h2>
                <ul class="card overflow-hidden">
                    @foreach ($featured as $product)
                        <x-product-row :product="$product" :voted-ids="$votedIds" pinned />
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <h2 class="group-header">Top ranked</h2>
            @if ($products->isNotEmpty())
                <ol class="card overflow-hidden" data-reveal>
                    @foreach ($products as $i => $product)
                        <x-product-row :product="$product" :rank="$products->firstItem() + $i" :voted-ids="$votedIds" />
                    @endforeach
                </ol>
                <div class="mt-6">{{ $products->links() }}</div>
            @else
                <div class="card px-6 py-14 text-center">
                    <p class="font-semibold">No {{ $category->name }} products yet</p>
                    <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-5">Be the first</a>
                </div>
            @endif
        </section>
    </div>
</x-layout>
