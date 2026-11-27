@props(['pricing'])
{{--
    Scrolling launch-offer bar above the header. Smooth, continuous scroll (no blinking);
    pauses on hover/focus; static and centered for reduced-motion users.
--}}
@php
    $promo = $pricing->promo();
    $plan = config('rankedlaunch.plans.pro.name');
    $now = \App\Services\Pricing::dollars($pricing->priceFor('pro'));
    $was = \App\Services\Pricing::dollars($pricing->regularPrice('pro'));
    $left = $pricing->promoRemaining();
    $items = [
        "<strong>{$promo['label']}</strong> · {$plan} for <strong>{$now}</strong> <s>{$was}</s>",
        "Only <strong>{$left} of {$promo['limit']}</strong> spots left",
        'List today or pick any launch day, <strong>free</strong>',
        "Dofollow backlink for {$now}, one-time",
        'Claim a spot →',
    ];
@endphp
<a href="{{ route('submit') }}" class="promo-marquee group relative block overflow-hidden border-b border-line bg-ink text-on-ink" aria-label="{{ $promo['label'] }}: {{ $plan }} for {{ $now }} instead of {{ $was }}, {{ $left }} spots left. Claim a spot.">
    <div class="promo-track flex w-max items-center py-2 text-[0.8125rem]" aria-hidden="true">
        {{-- Two identical halves so the loop is seamless --}}
        @for ($copy = 0; $copy < 2; $copy++)
            <div class="flex shrink-0 items-center">
                @for ($repeat = 0; $repeat < 2; $repeat++)
                    @foreach ($items as $item)
                        <span class="whitespace-nowrap px-5 opacity-90 [&_s]:opacity-50 [&_strong]:font-semibold">{!! $item !!}</span>
                        <span class="size-1 shrink-0 rounded-full bg-on-ink/40"></span>
                    @endforeach
                @endfor
            </div>
        @endfor
    </div>
</a>
