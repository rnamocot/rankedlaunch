@props(['products', 'pulse' => ['categories' => [], 'latest' => []]])
{{--
    Homepage banner: today's real top 4, refreshed from /live/today.json every 20s.
    Rows slide to their new rank when the order changes, vote counts pop "+1",
    and a ticker cycles through the latest upvotes (product and time only).
--}}
@php
    $rowRem = 3.75;
@endphp
<div {{ $attributes->merge(['class' => 'mx-auto w-full max-w-lg text-left']) }}
     data-rank-live data-src="{{ route('live.today') }}" data-row-rem="{{ $rowRem }}">
    <div class="tray">
        <div class="flex items-center justify-between gap-3 px-3 pt-1.5 pb-2.5">
            <span class="flex items-center gap-2 text-xs font-semibold">
                <span class="size-2 rounded-full bg-ink"></span>
                Live ranking
            </span>
            <span class="text-xs text-ink-2">Voting closes in
                <span class="font-semibold tabular-nums text-ink" data-countdown="{{ today('UTC')->addDay()->toIso8601String() }}">--:--:--</span>
            </span>
        </div>

        <div class="card relative overflow-hidden" style="height: {{ $products->count() * $rowRem }}rem" data-rows>
            @foreach ($products as $i => $product)
                <a href="{{ route('product', $product) }}" data-row="{{ $product->id }}" data-votes="{{ $product->votes_count }}"
                   class="group absolute inset-x-0 top-0 flex items-center gap-3 bg-surface px-4 transition-transform duration-700 ease-[cubic-bezier(0.2,0.7,0.2,1)] hover:bg-surface-2"
                   style="height: {{ $rowRem }}rem; transform: translateY({{ $i * $rowRem }}rem)">
                    <span data-rank class="w-4 text-sm font-semibold tabular-nums {{ $i < 3 ? 'text-ink' : 'text-ink-2' }}">{{ $i + 1 }}</span>
                    <span class="app-icon size-9"><img data-logo src="{{ $product->logoUrl() }}" alt=""></span>
                    <span class="min-w-0 flex-1">
                        <span data-name class="block truncate text-sm font-semibold">{{ $product->name }}</span>
                        <span class="block truncate text-xs text-ink-2">
                            <span data-category class="font-medium text-ink">{{ $product->category?->name }}</span><span data-sep class="{{ $product->category ? '' : 'hidden' }}"> · </span><span data-tagline>{{ $product->tagline }}</span>
                        </span>
                    </span>
                    <span class="relative flex h-10 w-12 shrink-0 flex-col items-center justify-center rounded-lg text-[0.6875rem] font-semibold tabular-nums ring-1 ring-line">
                        <x-icon name="chevron-up" :size="12" :stroke="2.5" class="-mb-0.5" />
                        <span data-count class="inline-block">{{ $product->votes_count }}</span>
                    </span>
                </a>
            @endforeach
            @for ($i = 1; $i < $products->count(); $i++)
                <span class="pointer-events-none absolute inset-x-4 z-10 h-px bg-line" style="top: {{ $i * $rowRem }}rem"></span>
            @endfor
        </div>

        {{-- Latest upvote ticker (real votes; hidden until there are some) --}}
        <div class="flex h-9 items-center gap-2 overflow-hidden px-3 text-xs text-ink-2 {{ empty($pulse['latest']) ? 'invisible' : '' }}" data-ticker
             data-latest='@json($pulse['latest'])'>
            <x-icon name="chevron-up" :size="13" :stroke="2.5" class="shrink-0 text-ink" />
            <span class="shrink-0">Latest upvote</span>
            <a data-ticker-item href="{{ $pulse['latest'][0]['url'] ?? '#' }}" class="min-w-0 truncate font-semibold text-ink transition-all duration-500 hover:underline">
                {{ $pulse['latest'][0]['name'] ?? '' }}
            </a>
            <span data-ticker-ago class="shrink-0 tabular-nums transition-opacity duration-500">{{ isset($pulse['latest'][0]) ? '· '.$pulse['latest'][0]['ago'] : '' }}</span>
        </div>
    </div>

    {{-- Today's categories --}}
    @if (! empty($pulse['categories']))
        <div class="mt-4 flex flex-wrap justify-center gap-1.5" aria-label="Launching today by category">
            @foreach ($pulse['categories'] as $c)
                <a href="{{ $c['url'] }}" class="chip !h-8 !pl-3 !text-xs">{{ $c['name'] }} <span class="tabular-nums text-ink-3">{{ $c['count'] }}</span></a>
            @endforeach
        </div>
    @endif
</div>
