{{--
    Homepage hero background: rankings, visualised.
    A soft dot grid, a field of thin bars that rise toward the right (products climbing),
    and one trend line that draws itself once. Ink at very low opacity, so it works
    in light and dark mode and never competes with the headline. Animates once, then rests.
--}}
@php
    // Deterministic "climbing" bars: an upward curve with gentle variation.
    $n = 64;
    $W = 1200;
    $H = 260;
    $gap = 6;
    $bw = ($W - $gap * ($n - 1)) / $n;
    $bars = [];
    $points = [];
    for ($i = 0; $i < $n; $i++) {
        $t = $i / ($n - 1);
        $trend = 0.12 + 0.78 * pow($t, 1.6);                // rises faster toward the right
        $wiggle = 0.10 * sin($i * 1.7) + 0.06 * sin($i * 0.53 + 1.2);
        $h = max(0.05, min(1, $trend + $wiggle)) * $H;
        $x = $i * ($bw + $gap);
        $bars[] = ['x' => round($x, 2), 'h' => round($h, 2), 'd' => round($i * 0.018, 3)];
        $points[] = round($x + $bw / 2, 2).','.round($H - $trend * $H * 1.04, 2);
    }
@endphp
<div aria-hidden="true" class="hero-backdrop pointer-events-none absolute inset-0 -z-10 overflow-hidden">
    {{-- Dot grid, fading out from the centre --}}
    <div class="hero-dots absolute inset-0"></div>

    {{-- Rising bars + trend line along the bottom --}}
    <svg class="absolute inset-x-0 bottom-0 h-[55%] w-full text-ink" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none">
        <g class="hero-bars">
            @foreach ($bars as $b)
                <rect x="{{ $b['x'] }}" y="{{ $H - $b['h'] }}" width="{{ round($bw, 2) }}" height="{{ $b['h'] }}" rx="2"
                      fill="currentColor" style="animation-delay: {{ $b['d'] }}s" />
            @endforeach
        </g>
        <polyline class="hero-trend" points="{{ implode(' ', $points) }}" fill="none" stroke="currentColor"
                  stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" pathLength="1" />
    </svg>

    {{-- Fade the bottom into the page so the content below sits cleanly --}}
    <div class="absolute inset-x-0 bottom-0 h-24 bg-linear-to-b from-transparent to-bg"></div>
</div>
