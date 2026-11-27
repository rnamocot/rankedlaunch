@props(['spotlight' => null])
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
            <radialGradient id="hero-glow">
                <stop offset="0" stop-color="var(--accent)" stop-opacity="0.35" />
                <stop offset="1" stop-color="var(--accent)" stop-opacity="0" />
            </radialGradient>
            <mask id="hero-mask"><rect width="{{ $W }}" height="{{ $H }}" fill="url(#hero-edges)" /></mask>
        </defs>

        <g mask="url(#hero-mask)">
            <path d="{{ $area }}" fill="url(#hero-area)" class="hero-area" />
            <path id="hero-graph" d="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2.5"
                  stroke-linejoin="round" stroke-linecap="round" pathLength="1" class="hero-line" />

            @if ($spotlight)
                {{-- A real app rides the graph, with a floating ribbon (Featured / #1 today) --}}
                @php
                    $ribbon = mb_strtoupper($spotlight['label']);
                    $ribbonWidth = 18 + mb_strlen($ribbon) * 7.2;
                @endphp
                <g class="hero-runner">
                    <circle r="30" fill="url(#hero-glow)" />
                    <clipPath id="hero-logo-clip" clipPathUnits="objectBoundingBox"><rect width="1" height="1" rx="0.24" /></clipPath>
                    <rect x="-21" y="-21" width="42" height="42" rx="10" style="fill: var(--surface)" stroke="currentColor" stroke-opacity="0.15" stroke-width="1.5" />
                    <image href="{{ $spotlight['product']->logoUrl() }}" x="-18" y="-18" width="36" height="36" clip-path="url(#hero-logo-clip)" preserveAspectRatio="xMidYMid slice" />
                    <g transform="translate(0 -36)">
                        <rect x="{{ -$ribbonWidth / 2 }}" y="-10" width="{{ $ribbonWidth }}" height="20" rx="10"
                              style="fill: {{ $spotlight['accent'] ? 'var(--accent)' : 'var(--ink)' }}" />
                        <text y="3.6" text-anchor="middle" font-size="10" font-weight="700" letter-spacing="0.8"
                              style="fill: {{ $spotlight['accent'] ? '#fff' : 'var(--on-ink)' }}" font-family="ui-sans-serif, system-ui, sans-serif">{{ $ribbon }}</text>
                    </g>
                    <animateMotion dur="11s" repeatCount="indefinite" calcMode="linear" begin="2.6s" data-motion>
                        <mpath href="#hero-graph" />
                    </animateMotion>
                </g>
            @else
                {{-- Orange tracker travelling along the line: glow, ring, crosshair ticks, core --}}
                <g class="hero-runner">
                    <circle r="18" fill="url(#hero-glow)" />
                    <circle r="9" fill="none" stroke="var(--accent)" stroke-width="1.5" />
                    <path d="M-15 0H-11M11 0H15M0 -15V-11M0 11V15" stroke="var(--accent)" stroke-width="1.5" stroke-linecap="round" />
                    <circle r="3.5" fill="var(--accent)" />
                    <animateMotion dur="9s" repeatCount="indefinite" calcMode="linear" begin="2.6s" data-motion>
                        <mpath href="#hero-graph" />
                    </animateMotion>
                </g>
            @endif

            {{-- #1 at the end of the climb --}}
            <circle cx="{{ $end[0] }}" cy="{{ $end[1] }}" r="10" fill="none" stroke="currentColor" stroke-width="1.5" class="hero-end" />
        </g>
    </svg>

    {{-- Fade into the page below --}}
    <div class="absolute inset-x-0 bottom-0 h-24 bg-linear-to-b from-transparent to-bg"></div>
</div>
