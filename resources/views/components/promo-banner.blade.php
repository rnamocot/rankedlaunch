@props(['pricing'])
@if ($pricing->promoActive())
    @php
        $promo = $pricing->promo();
        $planName = config("rankedlaunch.plans.{$promo['plan']}.name");
        $left = $pricing->promoRemaining();
        $taken = $promo['limit'] - $left;
    @endphp
    <div data-reveal {{ $attributes->merge(['class' => 'card flex flex-col gap-4 p-5 sm:flex-row sm:items-center']) }}>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold">{{ $promo['label'] }}: {{ $planName }} for {{ \App\Services\Pricing::dollars($promo['price']) }} <span class="font-normal text-ink-3 line-through">{{ \App\Services\Pricing::dollars($pricing->regularPrice($promo['plan'])) }}</span></p>
            <p class="mt-0.5 text-sm text-ink-2">For the first {{ $promo['limit'] }} makers only. After that, it goes back to the regular price.</p>
            <div class="mt-3 flex items-center gap-3">
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-fill" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $promo['limit'] }}" aria-valuenow="{{ $taken }}" aria-label="Launch offer spots claimed">
                    <div data-grow class="h-full rounded-full bg-ink" style="width: {{ max(2, round($taken / $promo['limit'] * 100)) }}%"></div>
                </div>
                <span class="shrink-0 text-xs font-semibold tabular-nums">{{ $left }} of {{ $promo['limit'] }} left</span>
            </div>
        </div>
        {{ $slot }}
    </div>
@endif
