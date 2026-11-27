@props(['products'])
{{--
    Homepage banner: today's real top 4, refreshed from /live/today.json every 20s.
    Rows are absolutely positioned so a change in order animates (the row slides to its new rank).
--}}
@php($rowRem = 3.5)
<div {{ $attributes->merge(['class' => 'tray mx-auto w-full max-w-md text-left']) }}
     data-rank-live data-src="{{ route('live.today') }}" data-row-rem="{{ $rowRem }}">
    <div class="flex items-center justify-between px-3 pt-1.5 pb-2.5">
        <span class="flex items-center gap-2 text-xs font-semibold">
            <span class="size-2 rounded-full bg-ink"></span>
            Live ranking
        </span>
        <a href="{{ route('rankings', 'today') }}" class="text-xs text-ink-2 hover:text-ink">Today · see all</a>
    </div>
    <div class="card relative overflow-hidden" style="height: {{ $products->count() * $rowRem }}rem" data-rows>
        @foreach ($products as $i => $product)
            <a href="{{ route('product', $product) }}" data-row="{{ $product->id }}" data-votes="{{ $product->votes_count }}"
               class="group absolute inset-x-0 top-0 flex h-14 items-center gap-3 bg-surface px-4 transition-transform duration-700 ease-[cubic-bezier(0.2,0.7,0.2,1)] hover:bg-surface-2"
               style="transform: translateY({{ $i * $rowRem }}rem)">
                <span data-rank class="w-4 text-sm font-semibold tabular-nums {{ $i < 3 ? 'text-ink' : 'text-ink-2' }}">{{ $i + 1 }}</span>
                <span class="app-icon size-8"><img data-logo src="{{ $product->logoUrl() }}" alt=""></span>
                <span class="min-w-0 flex-1">
                    <span data-name class="block truncate text-sm font-semibold">{{ $product->name }}</span>
                    <span data-tagline class="block truncate text-xs text-ink-2">{{ $product->tagline }}</span>
                </span>
                <span class="flex h-9 w-12 shrink-0 flex-col items-center justify-center rounded-lg text-[0.6875rem] font-semibold tabular-nums ring-1 ring-line">
                    <x-icon name="chevron-up" :size="12" :stroke="2.5" class="-mb-0.5" />
                    <span data-count class="inline-block">{{ $product->votes_count }}</span>
                </span>
            </a>
        @endforeach
        @for ($i = 1; $i < $products->count(); $i++)
            <span class="pointer-events-none absolute inset-x-4 z-10 h-px bg-line" style="top: {{ $i * $rowRem }}rem"></span>
        @endfor
    </div>
</div>
