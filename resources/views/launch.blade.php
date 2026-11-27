<x-layout title="Launch your product" description="Launch your SaaS or app on RankedLaunch. Get early users, a dofollow backlink, a permanent page that ranks on Google, and a winner badge. One-time payment.">
    @php
        $proPrice = \App\Services\Pricing::dollars($pricing->priceFor('pro'));
    @endphp

    {{-- Hero --}}
    <section class="intro mx-auto max-w-4xl px-4 pt-16 pb-12 text-center sm:px-6 sm:pt-20">
        <p class="text-sm font-medium text-ink-2">For makers and founders</p>
        <h1 class="display mx-auto mt-4 max-w-3xl">Your product deserves<br class="hidden sm:block"> more than a quiet launch.</h1>
        <p class="mx-auto mt-5 max-w-xl text-[1.0625rem] leading-relaxed text-ink-2">
            RankedLaunch puts your product in front of early adopters, gives it a page that ranks on Google, and a backlink that keeps working after launch day.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('submit') }}" class="btn btn-primary btn-lg">Launch your product <x-icon name="arrow-right" :size="16" :stroke="2" /></a>
            <a href="{{ route('how-it-works') }}" class="btn btn-outline btn-lg">How launches work</a>
        </div>
        <p class="mt-5 text-sm text-ink-2">
            Already live? <span class="font-semibold text-ink">List it today.</span> Launching soon? <span class="font-semibold text-ink">Pick any day, free.</span>
        </p>
    </section>

    @if ($recent->isNotEmpty())
        <section class="mx-auto max-w-4xl px-4 sm:px-6">
            <p class="text-center text-xs font-medium text-ink-3">Recently launched</p>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                @foreach ($recent as $item)
                    <a href="{{ route('product', $item) }}" title="{{ $item->name }}" class="app-icon size-11 transition hover:-translate-y-0.5">
                        <img src="{{ $item->logoUrl() }}" alt="{{ $item->name }}" loading="lazy">
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mx-auto mt-14 max-w-4xl px-4 sm:px-6">
        <x-promo-banner :pricing="$pricing">
            <a href="{{ route('submit') }}" class="btn btn-primary btn-sm shrink-0">Claim a spot</a>
        </x-promo-banner>
    </div>

    {{-- Why launch here --}}
    <section class="mx-auto mt-28 max-w-5xl px-4 sm:px-6">
        <h2 class="section-title" data-reveal>What a launch here gets you</h2>
        <p class="mx-auto mt-2 max-w-lg text-center text-ink-2">Not just a listing. Everything you need to turn launch day into users, links and proof.</p>

        <div class="tray mt-10 grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
            @foreach ([
                ['users', 'Early users who try things', 'Your launch sits on the homepage, in daily and weekly rankings, and in its category, where makers and early adopters browse.'],
                ['link', 'A backlink that lasts', 'Paid launches get a dofollow link from a page Google indexes. It helps your domain authority long after launch day.'],
                ['search', 'A page that ranks', 'Your product page has clean SEO, structured data and a fast load. People searching for your product name or category find it.'],
                ['medal', 'Proof you can show', 'Finish top 5 of the day, week, month or year and get a “Product of the Day / Week / Month / Year” badge for your site. Social proof that converts.'],
                ['chart', 'Real launch numbers', 'See page views, clicks to your site, votes and your rank. Know exactly what your launch did.'],
                ['send', 'A launch-day kit', 'Ready-to-post messages for X and LinkedIn, a launch-day checklist, and where else to share. No guessing.'],
            ] as [$icon, $title, $text])
                <div class="card card-hover p-6">
                    <span class="grid size-10 place-items-center rounded-xl bg-fill"><x-icon :name="$icon" :size="20" /></span>
                    <h3 class="mt-4 font-semibold tracking-[-0.015em]">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-2">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto mt-24 max-w-3xl px-4 sm:px-6">
        <h2 class="section-title" data-reveal>How it works</h2>
        <div class="tray mt-10" data-reveal><ol class="card overflow-hidden">
            @foreach ([
                ['Submit in two minutes', 'Name, link, tagline, logo. Screenshots if you have them.'],
                ['List now or pick a day', 'Already live? Go on today’s board. Launching soon? Choose any open day. Both are free.'],
                ['We review it', 'A real person checks every listing, usually within 24 hours. Spam never makes the board.'],
                ['Launch day', 'You go live at the start of your launch day. Share your page, collect votes, climb the ranks.'],
                ['Keep the results', 'Your page, backlink and badge stay up for good. The stats keep counting.'],
            ] as $i => [$title, $text])
                <li class="list-row flex gap-4 px-5 py-4" style="--inset: 4rem">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-on-ink tabular-nums">{{ $i + 1 }}</span>
                    <span>
                        <span class="block font-semibold tracking-[-0.01em]">{{ $title }}</span>
                        <span class="mt-0.5 block text-sm text-ink-2">{{ $text }}</span>
                    </span>
                </li>
            @endforeach
        </ol></div>
    </section>

    @if ($paymentsAvailable)
    {{-- Free vs Pro --}}
    <section class="mx-auto mt-24 max-w-3xl px-4 sm:px-6">
        <h2 class="section-title" data-reveal>Free or paid?</h2>
        <p class="mx-auto mt-2 max-w-md text-center text-ink-2">Both launch on the day you choose. Pro adds a backlink that helps your SEO and puts you in front of every visitor for a week.</p>

        <div class="tray mt-10" data-reveal><div class="card overflow-hidden text-sm">
            <div class="grid grid-cols-[1fr_5.5rem_5.5rem] items-center px-5 py-3 text-xs font-semibold text-ink-2 sm:grid-cols-[1fr_8rem_8rem]">
                <span></span><span class="text-center">Free</span><span class="text-center text-ink">{{ config('rankedlaunch.plans.pro.name') }} · {{ $proPrice }}</span>
            </div>
            @foreach ([
                ['List today or pick any day', true, true],
                ['Ranked on the daily board', true, true],
                ['Launch stats, share kit and badges', true, true],
                ['Dofollow backlink (SEO value)', false, true],
                ['Featured on the homepage for 7 days', false, true],
                ['Pinned at the top of your category', false, true],
                ['Shown first in Discover', false, true],
                ['Priority review', false, true],
            ] as [$label, $free, $pro])
                <div class="list-row grid grid-cols-[1fr_5.5rem_5.5rem] items-center px-5 py-3 sm:grid-cols-[1fr_8rem_8rem]" style="--inset: 1.25rem">
                    <span class="font-medium">{{ $label }}</span>
                    @foreach ([$free, $pro] as $value)
                        <span class="flex justify-center text-center {{ $loop->last ? 'font-semibold' : 'text-ink-2' }}">
                            @if ($value === true)
                                <x-icon name="check" :size="17" :stroke="2.25" />
                            @elseif ($value === false)
                                <x-icon name="minus" :size="17" class="text-ink-3" />
                            @else
                                {{ $value }}
                            @endif
                        </span>
                    @endforeach
                </div>
            @endforeach
        </div></div>
    </section>
    @else
        <section class="mx-auto mt-24 max-w-3xl px-4 sm:px-6">
            <h2 class="section-title" data-reveal>Everything is free right now</h2>
            <p class="mx-auto mt-2 max-w-md text-center text-ink-2">RankedLaunch is in early access. Every launch gets the full kit, no card needed.</p>
            <div class="tray mt-10" data-reveal><ul class="card overflow-hidden text-sm">
                @foreach (['List today or pick any launch day', 'Ranked on the daily and weekly boards', 'A permanent page that search engines can index', 'Launch stats: votes, page views, visits to your site', 'Share kit, share image and launch-day checklist', 'Launch and winner badges for your site'] as $item)
                    <li class="list-row flex items-center gap-3 px-5 py-3" style="--inset: 3.25rem"><x-icon name="check" :size="17" :stroke="2.25" /> <span class="font-medium">{{ $item }}</span></li>
                @endforeach
            </ul></div>
        </section>
    @endif

    @if ($showStats)
        <section class="mx-auto mt-24 max-w-4xl px-4 sm:px-6">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    'Products launched' => $stats['products'],
                    'Makers' => $stats['makers'],
                    'Upvotes cast' => $stats['votes'],
                    'Visits sent to makers' => $stats['clicks'],
                ] as $label => $value)
                    <div class="card p-5 text-center">
                        <dd class="text-[1.75rem] font-bold tabular-nums tracking-[-0.03em]">{{ number_format($value) }}</dd>
                        <dt class="mt-0.5 text-xs text-ink-2">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Final CTA --}}
    <section class="mx-auto mt-24 max-w-3xl px-4 sm:px-6">
        <div class="card px-6 py-12 text-center sm:px-12" data-reveal>
            <x-logo :size="40" :wordmark="false" class="text-ink" />
            <h2 class="section-title mt-5">Ready when you are.</h2>
            <p class="mx-auto mt-2 max-w-md text-ink-2">Two minutes to submit. Your page, your date, your launch.</p>
            <a href="{{ route('submit') }}" class="btn btn-primary btn-lg mt-7">Launch your product <x-icon name="arrow-right" :size="16" :stroke="2" /></a>
            @if ($pricing->promoActive())
                <p class="mt-4 text-sm text-ink-2">A {{ config('rankedlaunch.plans.pro.name') }} is {{ $proPrice }} for the next {{ $pricing->promoRemaining() }} makers, then {{ \App\Services\Pricing::dollars($pricing->regularPrice('pro')) }}.</p>
            @endif
        </div>
    </section>
</x-layout>
