@props(['product', 'votedIds' => []])
@php($voted = isset($votedIds[$product->id]))
<button type="button"
    {{ $attributes->merge(['class' => 'vote']) }}
    data-vote="{{ $product->slug }}"
    data-url="{{ route('vote', $product) }}"
    @guest data-login="{{ route('login') }}" @endguest
    aria-pressed="{{ $voted ? 'true' : 'false' }}"
    aria-label="Upvote {{ $product->name }}">
    <x-icon name="chevron-up" :size="18" :stroke="2.25" class="-mb-0.5" />
    <span data-count>{{ number_format($product->votes_count) }}</span>
</button>
