@props(['product', 'rank' => null, 'votedIds' => [], 'pinned' => false, 'hidden' => false])
<li @if ($hidden) hidden data-more @endif class="list-row group" data-category="{{ $product->category?->slug }}" style="--inset: {{ $rank ? '7.25rem' : '5rem' }}">
    <div class="flex items-center gap-4 px-4 py-3.5 sm:px-5">
        @if ($rank)
            <span class="w-6 shrink-0 text-right text-[0.9375rem] font-semibold tabular-nums {{ $rank <= 3 ? 'text-ink' : 'text-ink-2' }}">{{ $rank }}</span>
        @endif

        <a href="{{ route('product', $product) }}" class="flex min-w-0 flex-1 items-center gap-4">
            <span class="app-icon size-12 sm:size-14">
                <img src="{{ $product->logoUrl() }}" alt="" loading="lazy" width="56" height="56">
            </span>
            <span class="min-w-0">
                <span class="flex items-center gap-1.5">
                    <span class="truncate text-[0.9375rem] font-semibold tracking-[-0.015em] group-hover:underline group-hover:decoration-ink-3 group-hover:underline-offset-4">{{ $product->name }}</span>
                    @if ($pinned)
                        <x-icon name="pin" :size="14" class="text-ink-3" />
                    @endif
                </span>
                <span class="mt-0.5 block truncate text-sm text-ink-2">{{ $product->tagline }}</span>
                <span class="meta mt-1 hidden sm:flex">
                    @if ($product->category)<span>{{ $product->category->name }}</span>@endif
                    @unless ($product->isListing())<span>{{ $product->pricingLabel() }}</span>@endunless
                    @if ($product->comments_count)<span class="inline-flex items-center gap-1"><x-icon name="message" :size="12" />{{ $product->comments_count }}</span>@endif
                    @if ($product->isListing())<span>Via {{ $product->sourceLabel() }}</span>@endif
                    @if ($pinned || $product->isFeatured())<span class="font-medium text-ink">Featured</span>@endif
                </span>
            </span>
        </a>

        <x-vote-button :product="$product" :voted-ids="$votedIds" />
    </div>
</li>
