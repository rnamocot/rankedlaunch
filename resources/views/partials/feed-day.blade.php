{{-- One earlier launch day in the homepage feed: top five shown, the rest behind "Show all". --}}
@php($label = $day->isYesterday() ? 'Yesterday' : $day->format('l, M j'))
<section class="mt-10" data-feed-day>
    <h2 class="group-header">
        <span>{{ $label }}’s top launches</span>
        <span class="text-[0.8125rem] font-normal text-ink-2">{{ $products->count() }} {{ Str::plural('launch', $products->count()) }}</span>
    </h2>
    <div class="tray">
        <ol class="card overflow-hidden">
            @foreach ($products as $i => $product)
                <x-product-row :product="$product" :rank="$i + 1" :voted-ids="$votedIds" :hidden="$i >= 5" />
            @endforeach
        </ol>
    </div>
    @if ($products->count() > 5)
        <button type="button" class="mt-2 w-full rounded-xl py-2 text-sm font-medium text-ink-2 hover:bg-fill hover:text-ink" data-show-all>
            Show all {{ $products->count() }} from {{ $day->isYesterday() ? 'yesterday' : $day->format('M j') }}
        </button>
    @endif
</section>
