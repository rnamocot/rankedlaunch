<x-layout :title="$maker->name.' (@'.$maker->username.')'" :description="($maker->bio ? $maker->bio.' · ' : '').$maker->name.' is a maker on RankedLaunch with '.$launches->count().' '.Str::plural('launch', $launches->count()).' and '.number_format($stats['Upvotes']).' upvotes. See their products, awards and results.'" :noindex="$launches->isEmpty()">
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $maker->name,
                'alternateName' => '@'.$maker->username,
                'description' => $maker->bio,
                'url' => $maker->profileUrl(),
                'sameAs' => array_values(array_filter([$maker->website, $maker->x_handle ? 'https://x.com/'.$maker->x_handle : null])),
            ]),
        ]" />
    </x-slot:head>

    <section class="intro mx-auto max-w-4xl px-4 pt-14 sm:px-6">
        <div class="flex flex-col items-center gap-5 text-center sm:flex-row sm:items-center sm:text-left">
            @if ($maker->avatar)
                <img src="{{ $maker->avatar }}" alt="" class="size-20 rounded-full outline outline-1 -outline-offset-1 outline-line">
            @else
                <span class="grid size-20 place-items-center rounded-full bg-fill text-2xl font-semibold">{{ $maker->initials() }}</span>
            @endif
            <div class="min-w-0">
                <h1 class="text-[2rem] font-bold leading-tight tracking-[-0.03em]">{{ $maker->name }}</h1>
                <p class="text-ink-2">{{ '@'.$maker->username }} · Maker since {{ $maker->created_at->format('M Y') }}</p>
                @if ($maker->bio)<p class="mt-2 max-w-xl">{{ $maker->bio }}</p>@endif
                <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @if ($maker->website)
                        <a href="{{ $maker->website }}" target="_blank" rel="nofollow noopener" class="btn btn-outline btn-sm"><x-icon name="link" :size="15" /> {{ Str::of(parse_url($maker->website, PHP_URL_HOST))->replaceStart('www.', '') }}</a>
                    @endif
                    @if ($maker->x_handle)
                        <a href="https://x.com/{{ $maker->x_handle }}" target="_blank" rel="nofollow noopener" class="btn btn-outline btn-sm"><x-brand name="x" :size="14" /> {{ '@'.$maker->x_handle }}</a>
                    @endif
                    @if (auth()->id() === $maker->id)
                        <a href="{{ route('account') }}" class="btn btn-secondary btn-sm">Edit profile</a>
                    @endif
                </div>
            </div>
        </div>

        <dl class="mt-10 grid grid-cols-3 gap-3">
            @foreach ($stats as $label => $value)
                <div class="card p-4 text-center">
                    <dd class="text-[1.75rem] font-bold tabular-nums tracking-[-0.03em]">{{ number_format($value) }}</dd>
                    <dt class="text-xs text-ink-2">{{ $label }}</dt>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="mx-auto mt-10 max-w-4xl space-y-10 px-4 sm:px-6">
        @if ($awards->isNotEmpty())
            <section data-reveal>
                <h2 class="group-header">Awards</h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($awards as $a)
                        <a href="{{ route('product', $a['product']) }}" class="card card-hover flex items-center gap-3 p-4">
                            <x-icon name="medal" :size="22" class="text-[var(--accent)]" />
                            <span class="min-w-0">
                                <span class="block font-semibold">{{ $a['award']['label'] }}</span>
                                <span class="block truncate text-sm text-ink-2">{{ $a['product']->name }} · {{ $a['product']->launch_date->format('M j, Y') }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section data-reveal>
            <h2 class="group-header">Launches</h2>
            @if ($launches->isNotEmpty())
                <div class="tray">
                    <ul class="card overflow-hidden">
                        @foreach ($launches as $product)
                            <x-product-row :product="$product" :voted-ids="$votedIds" />
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="card px-6 py-12 text-center text-sm text-ink-2">No launches yet.</div>
            @endif
        </section>
    </div>
</x-layout>
