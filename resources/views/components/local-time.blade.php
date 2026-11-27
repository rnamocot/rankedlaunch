@props(['at', 'format' => 'time', 'suffix' => true])
{{--
    Shows a moment in the visitor's own time zone (e.g. "8:00 AM your time").
    Server renders UTC as a fallback; app.js swaps in the local version.
    format: time ("8:00 AM"), day ("Friday, Nov 28"), daytime ("Fri, Nov 28 · 8:00 AM")
--}}
@php
    $utc = $at->copy()->utc();
    $fallback = match ($format) {
        'day' => $utc->format('l, M j'),
        'daytime' => $utc->format('D, M j · H:i').' UTC',
        default => $utc->format('H:i').' UTC',
    };
@endphp
<time datetime="{{ $utc->toIso8601String() }}" data-local-time="{{ $format }}" @if($suffix) data-local-suffix @endif title="{{ $utc->format('D, M j, H:i') }} UTC" {{ $attributes }}>{{ $fallback }}</time>
