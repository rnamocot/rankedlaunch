{{--
    Homepage hero background: one ranking graph. A single line climbs from #5 to #1,
    with a soft area under it and a dot that travels along the line. No grid.
    Ink at low opacity, so it works in light and dark mode and never competes with the headline.
--}}
@php
    $W = 1200;
    $H = 640;
    $x0 = 40; $x1 = 1160; $top = 300; $bottom = 600;
    $ranks = [5, 5, 4, 4, 4, 3, 3, 2, 2, 2, 1, 1];
    $n = count($ranks);
    $rankY = fn ($r) => $top + ($r - 1) * (($bottom - $top) / 4);
    $pts = [];
    foreach ($ranks as $i => $r) {
        $pts[] = [round($x0 + $i * (($x1 - $x0) / ($n - 1)), 1), round($rankY($r), 1)];
    }
    $line = 'M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $pts));
    $area = $line.' L'.end($pts)[0].' '.$H.' L'.$pts[0][0].' '.$H.' Z';
    $end = end($pts);
@endphp
<div aria-hidden="true" class="hero-backdrop pointer-events-none absolute inset-0 -z-10 overflow-hidden">
    <svg class="absolute inset-0 h-full w-full text-ink" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="xMidYMax slice">
        <defs>
            <linearGradient id="hero-area" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0" stop-color="currentColor" stop-opacity="0.07" />
                <stop offset="1" stop-color="currentColor" stop-opacity="0" />
            </linearGradient>
            <linearGradient id="hero-edges" x1="0" x2="1" y1="0" y2="0">
                <stop offset="0" stop-color="#fff" stop-opacity="0" />
                <stop offset="0.1" stop-color="#fff" stop-opacity="1" />
                <stop offset="0.9" stop-color="#fff" stop-opacity="1" />
                <stop offset="1" stop-color="#fff" stop-opacity="0" />
            </linearGradient>
            <mask id="hero-mask"><rect width="{{ $W }}" height="{{ $H }}" fill="url(#hero-edges)" /></mask>
        </defs>

        <g mask="url(#hero-mask)">
            <path d="{{ $area }}" fill="url(#hero-area)" class="hero-area" />
            <path id="hero-graph" d="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2.5"
                  stroke-linejoin="round" stroke-linecap="round" pathLength="1" class="hero-line" />

            {{-- Dot travelling along the line --}}
            <circle r="5" fill="currentColor" class="hero-runner">
                <animateMotion dur="9s" repeatCount="indefinite" calcMode="linear" begin="2.6s" data-motion>
                    <mpath href="#hero-graph" />
                </animateMotion>
            </circle>

            {{-- #1 at the end of the climb --}}
            <circle cx="{{ $end[0] }}" cy="{{ $end[1] }}" r="10" fill="none" stroke="currentColor" stroke-width="1.5" class="hero-end" />
        </g>
    </svg>

    {{-- Fade into the page below --}}
    <div class="absolute inset-x-0 bottom-0 h-24 bg-linear-to-b from-transparent to-bg"></div>
</div>
