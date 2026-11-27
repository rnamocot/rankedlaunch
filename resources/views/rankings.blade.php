<x-layout :title="'Top products · '.$periods[$period]" :description="'The best new SaaS, AI and developer tools '.($period === 'all-time' ? 'of all time' : strtolower($periods[$period])).', ranked by upvotes from makers and early adopters on RankedLaunch.'">
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Top products · '.$periods[$period],
            'itemListElement' => $products->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $products->firstItem() + $i, 'url' => route('product', $p), 'name' => $p->name])->all(),
        ]" />
    </x-slot:head>

    <section class="mx-auto max-w-3xl px-4 pt-12 pb-8 sm:px-6">
        <h1 class="large-title">Rankings</h1>
        <p class="mt-3 text-ink-2">Ranked by upvotes from the community. Ties go to the earlier launch.</p>

        <nav class="segmented mt-6 max-w-full overflow-x-auto" aria-label="Period">
            @foreach ($periods as $key => $label)
                <a href="{{ route('rankings', $key) }}" @if($key === $period) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </section>

    <div class="mx-auto max-w-3xl px-4 sm:px-6">
        @if ($products->isNotEmpty())
            <ol class="card overflow-hidden" data-reveal>
                @foreach ($products as $i => $product)
                    <x-product-row :product="$product" :rank="$products->firstItem() + $i" :voted-ids="$votedIds" />
                @endforeach
            </ol>
            <div class="mt-6">{{ $products->links() }}</div>
        @else
            <div class="card px-6 py-14 text-center">
                <p class="font-semibold">Nothing ranked yet</p>
                <p class="mt-1 text-sm text-ink-2">Be the first launch of this {{ $period === 'today' ? 'day' : str_replace('all-time', 'board', $period) }}.</p>
                <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-5">Submit your product</a>
            </div>
        @endif
    </div>
</x-layout>
