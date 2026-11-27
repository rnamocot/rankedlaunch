@php
    $year = now()->year;
    $title = "Best {$product->name} alternatives in {$year}";
@endphp
<x-layout :title="$title"
          :description="'Looking for an alternative to '.$product->name.'? Compare '.$alternatives->count().' '.($product->category?->name ?? 'similar').' tools ranked by real users on RankedLaunch.'">
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'ItemList', 'name' => $title, 'itemListElement' => $alternatives->values()->map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => route('product', $p), 'name' => $p->name])->all()],
                ['@type' => 'BreadcrumbList', 'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'RankedLaunch', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $product->name, 'item' => route('product', $product)],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Alternatives', 'item' => route('alternatives', $product)],
                ]],
            ],
        ]" />
    </x-slot:head>

    <section class="intro mx-auto max-w-3xl px-4 pt-12 pb-8 sm:px-6">
        <a href="{{ route('product', $product) }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-ink">
            <x-icon name="chevron-left" :size="16" :stroke="2" /> {{ $product->name }}
        </a>
        <h1 class="large-title mt-4">{{ $product->name }} alternatives</h1>
        <p class="mt-3 text-ink-2">{{ $alternatives->count() }} {{ $product->category?->name ?? 'similar' }} tools people compare with {{ $product->name }}, ranked by upvotes from real users.</p>
    </section>

    <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6">
        <div class="card flex items-center gap-4 p-4">
            <span class="app-icon size-12"><img src="{{ $product->logoUrl() }}" alt=""></span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">{{ $product->name }}</p>
                <p class="truncate text-sm text-ink-2">{{ $product->tagline }}</p>
            </div>
            <a href="{{ route('product', $product) }}" class="btn btn-outline btn-sm">View</a>
        </div>

        <div class="tray" data-reveal>
            <ol class="card overflow-hidden">
                @foreach ($alternatives as $i => $alt)
                    <x-product-row :product="$alt" :rank="$i + 1" :voted-ids="$votedIds" />
                @endforeach
            </ol>
        </div>

        <div class="card p-6 text-center">
            <p class="font-semibold">Built a {{ $product->name }} alternative?</p>
            <p class="mt-1 text-sm text-ink-2">Launch it here and get in front of people comparing tools.</p>
            <a href="{{ route('launch') }}" class="btn btn-primary btn-sm mt-5">Launch your product</a>
        </div>
    </div>
</x-layout>
