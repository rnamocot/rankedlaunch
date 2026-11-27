@props(['source' => 'site'])
<div id="newsletter" {{ $attributes->merge(['class' => 'card p-6 sm:p-8']) }} data-reveal>
    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
        <div class="flex-1">
            <p class="font-semibold tracking-[-0.015em]">The week’s top launches, every Monday</p>
            <p class="mt-1 text-sm text-ink-2">The 10 best new products, in one short email. No spam, unsubscribe anytime.</p>
        </div>
        @if (session('subscribed'))
            <p class="flex items-center gap-2 text-sm font-medium"><x-icon name="check" :size="17" :stroke="2.25" /> You’re subscribed.</p>
        @else
            <form method="POST" action="{{ route('subscribe') }}" class="flex w-full gap-2 sm:w-auto">
                @csrf
                <input type="hidden" name="source" value="{{ $source }}">
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <label for="newsletter-email-{{ $source }}" class="sr-only">Email</label>
                <input id="newsletter-email-{{ $source }}" name="email" type="email" required placeholder="you@company.com" class="input !h-10 min-w-0 flex-1 sm:w-60">
                <button class="btn btn-primary shrink-0">Subscribe</button>
            </form>
        @endif
    </div>
    @error('email')<p class="field-error">{{ $message }}</p>@enderror
</div>
