<x-layout title="How launches work" description="How launching on RankedLaunch works: listing and review, launch day timing, fair voting, daily to yearly rankings, awards and badges.">
    @php
        $review = config('rankedlaunch.review_listings');
        $sections = [
            ['calendar', 'Listing and launch day', [
                'List an app that’s already live today, or schedule a launch on any open day. Listing is free.',
                'Launches go live at <strong><time datetime="'.today('UTC')->addDay()->toIso8601String().'" data-local-time="time" data-local-suffix>00:00 UTC</time></strong>, and voting runs for 24 hours.',
                'Each day has a limited number of spots ('.config('rankedlaunch.free_slots_per_day').' free) so every product gets seen. Full days are greyed out when you pick a date.',
                'You can move your launch date until the day before, or delete a listing at any time from your dashboard.',
            ]],
            ['shield', 'Review', [
                $review
                    ? 'A person reviews every listing before it goes live. We check that the website works, that it’s a real product, and that it isn’t spam, a scam or adult content.'
                    : 'Listings go live right away. We still check every new listing and remove anything broken, spammy, scammy or adult.',
                'If a listing isn’t approved, you’ll get an email with the reason.',
            ]],
            ['chevron-up', 'Voting', [
                'You need an account to vote, and each account gets one vote per product. You can take a vote back.',
                'Rankings are by votes only. Paying never changes a product’s rank.',
                'Fake accounts, vote rings and bought votes are detected. Those votes are removed and the accounts are banned.',
            ]],
            ['chart', 'Rankings', [
                'Products are ranked against everything launching the same day, week, month and year.',
                'Ties go to the product that launched first.',
                'You can browse every leaderboard on the <a href="'.route('rankings', 'week').'">Rankings</a> page.',
            ]],
            ['medal', 'Awards and badges', [
                'The <strong>top '.\App\Models\Product::AWARD_TOP.'</strong> of each day, week, month and year earn an award: “#1 Product of the Day”, “#2 Product of the Week”, “#1 Product of the Month”, and so on.',
                'Awards are given once the period ends, so nobody wears a title they might still lose.',
                'Every launch gets an embeddable badge for its website. It upgrades itself to the best award the product earns.',
            ]],
        ];
        if ($paymentsAvailable) {
            $sections[] = ['pin', 'Featured (Pro)', [
                'Pro launches are featured on the homepage for '.config('rankedlaunch.plans.pro.featured_days', 7).' days in a fair rotation, pinned at the top of their category and shown first in Discover. They’re always labelled “Featured”.',
                'Featured placement is separate from the rankings: Pro launches compete on votes like everyone else.',
            ]];
        }
    @endphp

    <section class="intro mx-auto max-w-3xl px-4 pt-14 pb-10 text-center sm:px-6">
        <h1 class="large-title">How launches work</h1>
        <p class="mx-auto mt-4 max-w-xl text-[1.0625rem] text-ink-2">The rules behind the board: when you go live, how votes count, and how awards are earned.</p>
    </section>

    <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6">
        @foreach ($sections as [$icon, $title, $points])
            <section class="card p-6" data-reveal>
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-fill"><x-icon :name="$icon" :size="19" /></span>
                    <h2 class="text-lg font-semibold tracking-[-0.015em]">{{ $title }}</h2>
                </div>
                <ul class="mt-4 space-y-2.5 text-[0.9375rem] leading-relaxed text-ink-2 [&_a]:text-ink [&_a]:underline [&_a]:underline-offset-2 [&_strong]:font-semibold [&_strong]:text-ink">
                    @foreach ($points as $point)
                        <li class="flex gap-2.5"><x-icon name="check" :size="16" :stroke="2.25" class="mt-1 shrink-0 text-ink" /> <span>{!! $point !!}</span></li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <div class="card p-6 text-center" data-reveal>
            <p class="font-semibold">Ready to launch?</p>
            <p class="mt-1 text-sm text-ink-2">List today or pick your launch day. It takes two minutes.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2.5">
                <a href="{{ route('submit') }}" class="btn btn-primary">Launch your product</a>
                <a href="{{ route('launch') }}" class="btn btn-outline">Why launch here</a>
            </div>
        </div>
    </div>
</x-layout>
