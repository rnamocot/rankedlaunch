<x-layout title="Pricing" :description="$paymentsAvailable ? 'List your product free, today or on any launch day. Add a dofollow backlink or homepage feature with a one-time payment.' : 'RankedLaunch is free while we’re in early access. List your product today or pick any launch day.'">
    @php($faqs = [
        'Can I pick my launch day for free?' => 'Yes. Every plan, including Free, can list today or pick any open day. Each day has a limited number of spots so every product gets seen; full days are greyed out.',
        'What is a dofollow backlink?' => 'A link search engines count toward your site’s authority. Paid launches keep theirs permanently; free launches use nofollow.',
        'Can I list a product that’s already live?' => 'Yes. Any working product can launch here, whether it’s brand new or has been around for years. Many makers use it to reach new users and earn a backlink and badge for an established product.',
        'Is it really one-time?' => 'Yes. You pay once per launch. No renewals, no subscription.',
        'Can my product get rejected?' => 'Only if it’s broken, spam, or not a real product. If a paid launch is rejected, we refund it in full.',
        'Does paying get me a higher rank?' => 'No. Rankings are by votes only, for every plan. That’s what makes a “#1 Product of the Day” badge worth something. Paid plans buy your launch date, a dofollow backlink and visibility: Pro launches are featured on the homepage (in a fair rotation, first on launch day), pinned at the top of their category, and labelled “Featured”.',
        'How do rankings work?' => 'Products are ranked by upvotes against everything launching the same day and week. The top 3 get a winner badge for their site.',
    ])
    @if (! $paymentsAvailable)
        {{-- Free early access: only free plan, no paid FAQs --}}
        @php($plans = array_intersect_key($plans, ['free' => true]))
        @php($faqs = array_diff_key($faqs, array_flip(['What is a dofollow backlink?', 'Is it really one-time?', 'Does paying get me a higher rank?'])) + ['Is it really free?' => 'Yes. While RankedLaunch is in early access, listing, launching, voting, stats and badges are all free. No card needed.'])
        @php($faqs['Can my product get rejected?'] = 'Only if it’s broken, spam, or not a real product. We review every listing before it goes live.')
        @php($faqs['Can I pick my launch day for free?'] = 'Yes. List today or pick any open day. Each day has a limited number of spots so every product gets seen; full days are greyed out.')
    @endif
    <x-slot:head>
        <x-jsonld :data="[
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($a, $q) => ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]])->values()->all(),
        ]" />
    </x-slot:head>
    <section class="intro mx-auto max-w-3xl px-4 pt-14 pb-10 text-center sm:px-6">
        @if ($paymentsAvailable)
            <h1 class="large-title">Pay once. Launch when you want.</h1>
            <p class="mx-auto mt-4 max-w-xl text-[1.0625rem] text-ink-2">No subscription. Every plan lists today or on the day you pick. Paid plans add a backlink that stays, and Pro adds homepage visibility.</p>
        @else
            <h1 class="large-title">Free while we’re in early access.</h1>
            <p class="mx-auto mt-4 max-w-xl text-[1.0625rem] text-ink-2">List your product today or pick any launch day. Votes, launch stats, share kit and badges are all included. No card needed.</p>
        @endif
    </section>

    <div class="mx-auto max-w-5xl px-4 sm:px-6">
        <x-promo-banner :pricing="$pricing" class="mb-6" />
    </div>

    <div class="mx-auto grid grid-cols-1 gap-4 px-4 sm:px-6 {{ count($plans) > 1 ? 'max-w-3xl md:grid-cols-2' : 'max-w-md' }}">
        @foreach ($plans as $key => $plan)
            @php($highlight = $key === 'pro')
            <section data-reveal style="--reveal-i: {{ $loop->index }}" class="card card-hover flex flex-col p-6 {{ $highlight ? '!outline-2 !outline-ink' : '' }}">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-[0.9375rem] font-semibold">{{ $plan['name'] }}</h2>
                    @if ($highlight)<span class="text-xs font-medium text-ink-2">Most picked</span>@endif
                </div>
                <p class="mt-4 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <x-price :plan="$key" :pricing="$pricing" />
                    <span class="text-sm text-ink-2">{{ $plan['price'] ? 'one-time' : 'forever' }}</span>
                </p>
                @if ($pricing->isPromoPlan($key))
                    <p class="mt-2 text-xs font-medium text-ink-2">{{ $pricing->promoRemaining() }} of {{ $pricing->promo()['limit'] }} launch-offer spots left</p>
                @endif
                <ul class="mt-6 flex-1 space-y-3 text-sm">
                    @foreach ($plan['features'] as $feature)
                        <li class="flex gap-2.5"><x-icon name="check" :size="17" :stroke="2" class="mt-px" /> {{ $feature }}</li>
                    @endforeach
                </ul>
                @if ($key !== 'free' && ! $paymentsAvailable)
                    <span class="btn btn-secondary mt-8 w-full cursor-not-allowed opacity-60" aria-disabled="true">Coming soon</span>
                    <p class="mt-2 text-center text-xs text-ink-2">Pro is opening soon. Listing is free in the meantime.</p>
                @else
                    <a href="{{ route('submit') }}" class="btn {{ $highlight ? 'btn-primary' : 'btn-secondary' }} mt-8 w-full">
                        {{ $key === 'free' ? 'Start free' : 'Choose '.$plan['name'] }}
                    </a>
                @endif
            </section>
        @endforeach
    </div>

    <section class="mx-auto mt-20 max-w-3xl px-4 sm:px-6">
        <h2 class="title-2 mb-5">Questions</h2>
        <div class="card overflow-hidden">
            @foreach ($faqs as $q => $a)
                <details class="list-row group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-[0.9375rem] font-medium">
                        {{ $q }}
                        <x-icon name="chevron-right" :size="16" :stroke="2" class="text-ink-3 transition-transform group-open:rotate-90" />
                    </summary>
                    <p class="px-5 pb-4 text-sm leading-relaxed text-ink-2">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </section>
</x-layout>
