@props(['plan', 'pricing', 'size' => 'text-[2.5rem]'])
@php
    $now = $pricing->priceFor($plan);
    $regular = $pricing->regularPrice($plan);
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-2']) }}>
    <span class="{{ $size }} font-bold leading-none tracking-[-0.04em]">{{ \App\Services\Pricing::dollars($now) }}</span>
    @if ($now < $regular)
        <s class="text-base font-medium text-ink-3 decoration-[1.5px]">{{ \App\Services\Pricing::dollars($regular) }}</s>
    @endif
</span>
