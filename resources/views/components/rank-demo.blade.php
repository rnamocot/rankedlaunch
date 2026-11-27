{{--
    Hero illustration: "Your product" climbs a live leaderboard from #4 to #1.
    Other rows are anonymous placeholders on purpose: no real product gets invented numbers.
    Animated by [data-rank-demo] in app.js; reduced-motion users see the final state.
--}}
@php
    $rows = [
        ['id' => 'a', 'votes' => 148, 'w' => 'w-24', 'shade' => 'bg-ink/80'],
        ['id' => 'b', 'votes' => 131, 'w' => 'w-32', 'shade' => 'bg-ink/55'],
        ['id' => 'c', 'votes' => 117, 'w' => 'w-20', 'shade' => 'bg-ink/35'],
        ['id' => 'you', 'votes' => 96, 'w' => '', 'shade' => ''],
    ];
@endphp
<div {{ $attributes->merge(['class' => 'tray mx-auto w-full max-w-md text-left']) }} data-rank-demo aria-hidden="true">
    <div class="flex items-center justify-between px-3 pt-1.5 pb-2.5">
        <span class="flex items-center gap-2 text-xs font-semibold">
            <span class="size-2 rounded-full bg-ink"></span>
            Live ranking
        </span>
        <span class="text-xs text-ink-2">Today</span>
    </div>
    <div class="card relative overflow-hidden" style="height: {{ count($rows) * 3.5 }}rem">
        @foreach ($rows as $i => $row)
            @php($you = $row['id'] === 'you')
            <div data-row="{{ $row['id'] }}" data-votes="{{ $row['votes'] }}"
                 class="absolute inset-x-0 top-0 flex h-14 items-center gap-3 px-4 transition-transform duration-700 ease-[cubic-bezier(0.2,0.7,0.2,1)] {{ $you ? 'z-10 bg-surface' : '' }}"
                 style="transform: translateY({{ $i * 3.5 }}rem)">
                <span data-rank class="w-4 text-sm font-semibold tabular-nums {{ $you ? 'text-ink' : 'text-ink-3' }}">{{ $i + 1 }}</span>
                @if ($you)
                    <x-logo :size="32" :wordmark="false" class="text-ink" />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold">Your product</span>
                        <span data-badge class="block text-xs text-ink-2 transition-opacity duration-500">Launching today</span>
                    </span>
                @else
                    <span class="size-8 shrink-0 rounded-[0.55rem] {{ $row['shade'] }}"></span>
                    <span class="min-w-0 flex-1 space-y-1.5">
                        <span class="block h-2.5 {{ $row['w'] }} rounded-full bg-fill-strong"></span>
                        <span class="block h-2 w-36 max-w-full rounded-full bg-fill"></span>
                    </span>
                @endif
                <span class="flex h-9 w-12 shrink-0 flex-col items-center justify-center rounded-lg text-[0.6875rem] font-semibold tabular-nums {{ $you ? 'bg-ink text-on-ink' : 'ring-1 ring-line text-ink-2' }}">
                    <x-icon name="chevron-up" :size="12" :stroke="2.5" class="-mb-0.5" />
                    <span data-count class="inline-block">{{ $row['votes'] }}</span>
                </span>
            </div>
        @endforeach
        {{-- hairlines between rows --}}
        @for ($i = 1; $i < count($rows); $i++)
            <span class="absolute inset-x-4 h-px bg-line" style="top: {{ $i * 3.5 }}rem"></span>
        @endfor
    </div>
</div>
