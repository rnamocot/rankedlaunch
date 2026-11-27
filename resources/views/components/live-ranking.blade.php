@props(['products', 'week' => null, 'pulse' => ['categories' => [], 'latest' => []]])
{{--
    Hero dashboard panel: today's real top 5 (live, refreshed from /live/today.json every 20s)
    and this week's top 5, as tabs. Rows slide to their new rank, vote counts pop "+1",
    a thin bar shows each app's share of the leader's votes, and a ticker cycles the
    latest upvotes (product and time only).
--}}
@php
    $rowRem = 3.5;
    $max = max(1, (int) $products->max('votes_count'));
    $week ??= collect();
    $weekMax = max(1, (int) $week->max('votes_count'));
@endphp
<div {{ $attributes->merge(['class' => 'w-full text-left']) }}
     data-rank-live data-src="{{ route('live.today') }}" data-row-rem="{{ $rowRem }}">
    <div class="tray shadow-[0_24px_60px_-30px_rgba(0,0,0,0.25)]">
        {{-- Window bar --}}
        <div class="flex items-center gap-3 px-3 pt-1.5 pb-2.5">
            <span class="flex gap-1.5" aria-hidden="true">
                <span class="size-2.5 rounded-full bg-ink/15"></span><span class="size-2.5 rounded-full bg-ink/15"></span><span class="size-2.5 rounded-full bg-ink/15"></span>
            </span>
            <span class="flex-1 truncate text-center text-[0.6875rem] font-medium text-ink-2">rankedlaunch.com/rankings</span>
            <span class="w-10"></span>
        </div>

        <div class="card overflow-hidden">
            {{-- Panel header: title + tabs --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="size-2 rounded-full bg-ink"></span>
                    <span class="text-sm font-semibold">Top launches</span>
                </div>
                <div class="segmented !p-0.5" role="tablist" aria-label="Period">
                    <button type="button" role="tab" data-tab="today" aria-selected="true" class="rounded-md px-3 py-1 text-xs font-semibold aria-selected:bg-surface aria-selected:shadow-sm aria-[selected=false]:text-ink-2">Today</button>
                    <button type="button" role="tab" data-tab="week" aria-selected="false" class="rounded-md px-3 py-1 text-xs font-semibold aria-selected:bg-surface aria-selected:shadow-sm aria-[selected=false]:text-ink-2">This week</button>
                </div>
            </div>

            {{-- Today (live) --}}
            <div data-panel="today">
                <div class="relative overflow-hidden" style="height: {{ $products->count() * $rowRem }}rem" data-rows>
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
                            {{-- Share of the leader's votes --}}
                            <span data-share class="absolute bottom-0 left-0 h-0.5 bg-ink/20 transition-[width] duration-700" style="width: {{ round($product->votes_count / $max * 100) }}%"></span>
                        </a>
                    @endforeach
                    @for ($i = 1; $i < $products->count(); $i++)
                        <span class="pointer-events-none absolute inset-x-4 z-10 h-px bg-line" style="top: {{ $i * $rowRem }}rem"></span>
                    @endfor
                </div>
            </div>

            {{-- This week --}}
            <ol data-panel="week" hidden>
                @forelse ($week as $i => $product)
                    <li class="list-row" style="--inset: 4.25rem">
                        <a href="{{ route('product', $product) }}" class="relative flex items-center gap-3 px-4 hover:bg-surface-2" style="height: {{ $rowRem }}rem">
                            <span class="w-4 text-sm font-semibold tabular-nums {{ $i < 3 ? 'text-ink' : 'text-ink-2' }}">{{ $i + 1 }}</span>
                            <span class="app-icon size-9"><img src="{{ $product->logoUrl() }}" alt="" loading="lazy"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ $product->name }}</span>
                                <span class="block truncate text-xs text-ink-2"><span class="font-medium text-ink">{{ $product->category?->name }}</span> · {{ $product->launch_date->format('D') }}</span>
                            </span>
                            <span class="flex h-10 w-12 shrink-0 flex-col items-center justify-center rounded-lg text-[0.6875rem] font-semibold tabular-nums ring-1 ring-line">
                                <x-icon name="chevron-up" :size="12" :stroke="2.5" class="-mb-0.5" />{{ $product->votes_count }}
                            </span>
                            <span class="absolute bottom-0 left-0 h-0.5 bg-ink/20" style="width: {{ round($product->votes_count / $weekMax * 100) }}%"></span>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-ink-2">No launches yet this week.</li>
                @endforelse
            </ol>

            {{-- Footer: countdown + latest upvote --}}
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t border-line px-4 py-2.5 text-xs text-ink-2">
                <span>Voting closes in <span class="font-semibold tabular-nums text-ink" data-countdown="{{ today('UTC')->addDay()->toIso8601String() }}">--:--:--</span></span>
                <span class="flex min-w-0 items-center gap-1.5 {{ empty($pulse['latest']) ? 'invisible' : '' }}" data-ticker data-latest='@json($pulse['latest'])'>
                    <x-icon name="chevron-up" :size="12" :stroke="2.5" class="shrink-0 text-ink" />
                    <a data-ticker-item href="{{ $pulse['latest'][0]['url'] ?? '#' }}" class="min-w-0 truncate font-semibold text-ink transition-all duration-500 hover:underline">{{ $pulse['latest'][0]['name'] ?? '' }}</a>
                    <span data-ticker-ago class="shrink-0 tabular-nums transition-opacity duration-500">{{ isset($pulse['latest'][0]) ? '· '.$pulse['latest'][0]['ago'] : '' }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Today's categories --}}
    @if (! empty($pulse['categories']))
        <div class="mt-4 flex flex-wrap gap-1.5" aria-label="Launching today by category">
            @foreach ($pulse['categories'] as $c)
                <a href="{{ $c['url'] }}" class="chip !h-8 !pl-3 !text-xs">{{ $c['name'] }} <span class="tabular-nums text-ink-3">{{ $c['count'] }}</span></a>
            @endforeach
        </div>
    @endif
</div>
