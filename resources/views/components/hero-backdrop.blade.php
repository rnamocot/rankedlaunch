@props(['spotlight' => null, 'logos' => []])
{{--
    Homepage hero background: a drifting particle network. Dots link with thin lines
    when close (a few are orange accents); real app logos float through it as larger
    nodes, and a featured Pro app carries an orange ribbon. Drawn on a canvas by
    [data-particles] in app.js; pauses off-screen; a still frame for reduced motion.
--}}
@php
    $nodes = collect($logos)->map(fn ($p) => ['logo' => $p->logoUrl(), 'label' => null])->all();
    if ($spotlight) {
        // The spotlight app leads, with its ribbon (FEATURED / #1 TODAY).
        $nodes = array_values(array_filter($nodes, fn ($n) => $n['logo'] !== $spotlight['product']->logoUrl()));
        array_unshift($nodes, [
            'logo' => $spotlight['product']->logoUrl(),
            'label' => mb_strtoupper($spotlight['label']),
            'accent' => $spotlight['accent'],
        ]);
    }
    $nodes = array_slice($nodes, 0, 6);
@endphp
<div aria-hidden="true" class="hero-backdrop pointer-events-none absolute inset-0 -z-10 overflow-hidden">
    <canvas data-particles data-nodes='@json($nodes)' class="hero-particles absolute inset-0 h-full w-full"></canvas>
    {{-- Fade into the page below --}}
    <div class="absolute inset-x-0 bottom-0 h-24 bg-linear-to-b from-transparent to-bg"></div>
</div>
