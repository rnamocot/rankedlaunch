@props(['title', 'series', 'format' => 'int'])
{{--
    Single-series daily bar chart (30 days). One ink color, so no legend: the title names the series.
    Thin bars, 2px gaps, 3px rounded tops anchored to the baseline, recessive axis, hover tooltip per day,
    and a table view for screen readers and exact numbers.
--}}
@php
    $fmt = fn ($v) => $format === 'money' ? \App\Services\Pricing::dollars((int) $v) : number_format($v);
    $values = array_column($series, 'value');
    $max = max(1, ...$values);
    $total = array_sum($values);
    $n = count($series);
    $W = 600; $H = 150; $gap = 2;
    $bw = ($W - $gap * ($n - 1)) / $n;
    $r = min(3, $bw / 2);
    $id = 'chart-'.Str::slug($title);
@endphp
<figure {{ $attributes->merge(['class' => 'card p-5']) }} data-chart>
    <figcaption class="flex items-baseline justify-between gap-3">
        <span class="text-sm font-semibold">{{ $title }}</span>
        <span class="text-xs text-ink-2">Last 30 days</span>
    </figcaption>
    <p class="mt-2 text-[1.625rem] font-bold tabular-nums tracking-[-0.03em]">{{ $fmt($total) }}</p>

    <div class="relative mt-4">
        <svg viewBox="0 0 {{ $W }} {{ $H + 1 }}" class="block h-36 w-full overflow-visible" preserveAspectRatio="none" role="img" aria-label="{{ $title }}, last 30 days, total {{ $fmt($total) }}">
            {{-- Recessive top gridline at the max value --}}
            <line x1="0" x2="{{ $W }}" y1="0.5" y2="0.5" stroke="var(--line)" stroke-dasharray="2 4" vector-effect="non-scaling-stroke" />
            @foreach ($series as $i => $point)
                @php
                    $x = $i * ($bw + $gap);
                    $h = $point['value'] > 0 ? max(3, $point['value'] / $max * ($H - 4)) : 0;
                    $y = $H - $h;
                @endphp
                @if ($h > 0)
                    <path d="M{{ $x }},{{ $H }} L{{ $x }},{{ $y + $r }} Q{{ $x }},{{ $y }} {{ $x + $r }},{{ $y }} L{{ $x + $bw - $r }},{{ $y }} Q{{ $x + $bw }},{{ $y }} {{ $x + $bw }},{{ $y + $r }} L{{ $x + $bw }},{{ $H }} Z"
                          fill="var(--ink)" opacity="{{ $i === $n - 1 ? 1 : 0.78 }}" />
                @endif
                {{-- Full-height hit target, bigger than the mark --}}
                <rect x="{{ $x }}" y="0" width="{{ $bw + $gap }}" height="{{ $H }}" fill="transparent" class="hover:fill-[var(--fill)]"
                      data-tip="{{ $point['date']->format('D, M j') }}: {{ $fmt($point['value']) }}" />
            @endforeach
            <line x1="0" x2="{{ $W }}" y1="{{ $H + 0.5 }}" y2="{{ $H + 0.5 }}" stroke="var(--line)" vector-effect="non-scaling-stroke" />
        </svg>
        <span class="pointer-events-none absolute top-0 left-0 text-[0.6875rem] tabular-nums text-ink-2 -translate-y-full pb-1">{{ $fmt($max) }}</span>
        <div data-chart-tip hidden class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-ink px-2.5 py-1.5 text-xs font-medium text-on-ink shadow-lg"></div>
    </div>
    <div class="mt-2 flex justify-between text-[0.6875rem] text-ink-2">
        <span>{{ $series[0]['date']->format('M j') }}</span>
        <span>{{ $series[intdiv($n, 2)]['date']->format('M j') }}</span>
        <span>Today</span>
    </div>

    <details class="mt-3 text-xs">
        <summary class="cursor-pointer text-ink-2 hover:text-ink">View as table</summary>
        <table class="mt-2 w-full tabular-nums">
            <thead><tr class="text-left text-ink-2"><th class="py-1 font-medium">Day</th><th class="py-1 text-right font-medium">{{ $title }}</th></tr></thead>
            <tbody>
                @foreach (array_reverse($series) as $point)
                    <tr class="border-t border-line"><td class="py-1">{{ $point['date']->format('D, M j') }}</td><td class="py-1 text-right">{{ $fmt($point['value']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
