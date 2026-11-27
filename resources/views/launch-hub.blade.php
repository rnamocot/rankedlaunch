<x-layout :title="$product->name.' · Launch'" :noindex="true">
    @php
        $live = $product->isLive();
        $launchDay = $product->launch_date;
        $dayOver = $launchDay && $launchDay->lt(today());
        $publicUrl = route('product', $product);
        $isFree = $product->plan === 'free';
        $status = $product->status;

        // Timeline: done | current | upcoming | failed
        $reviewState = match ($status) {
            'approved' => 'done',
            'rejected' => 'failed',
            'pending' => 'current',
            default => 'upcoming',
        };
        $steps = [
            ['Submitted', $product->created_at->format('M j, Y'), 'done'],
            [
                'Launch date booked',
                $status === 'awaiting_payment' ? 'Waiting for payment' : ($launchDay ? $launchDay->format('l, M j').' · '.config("rankedlaunch.plans.{$product->plan}.name") : 'Pick a plan to book a date'),
                in_array($status, ['draft', 'awaiting_payment']) ? 'current' : 'done',
            ],
            [
                'Review',
                match ($reviewState) {
                    'done' => 'Approved. You’re on the board.',
                    'failed' => $product->rejection_reason ?: 'Not approved.',
                    'current' => 'A real person checks every listing, usually within 24 hours.',
                    default => 'Starts once your date is booked.',
                },
                $reviewState,
            ],
            [
                'Launch day',
                $launchDay
                    ? ($live && ! $dayOver ? 'You’re live today. Share your page and collect votes.' : ($dayOver ? 'Launched '.$launchDay->format('M j') : 'Goes live on '.$launchDay->format('M j').($daysUntilLaunch > 0 ? " · in {$daysUntilLaunch} ".Str::plural('day', $daysUntilLaunch) : '')))
                    : 'Not scheduled yet',
                $dayOver ? 'done' : ($live ? 'current' : 'upcoming'),
            ],
            [
                'Results',
                $dayOver ? "Finished #{$dailyRank} of the day".($weeklyRank ? " · #{$weeklyRank} of the week" : '') : 'Rank, votes and traffic appear here.',
                $dayOver ? 'done' : 'upcoming',
            ],
        ];

        $shareLive = "{$product->name} is live on RankedLaunch today.\n\n{$product->tagline}\n\nIf you have a second, I'd love your feedback and an upvote:\n{$publicUrl}";
        $sharePre = $launchDay ? "I'm launching {$product->name} on RankedLaunch on {$launchDay->format('l, M j')}.\n\n{$product->tagline}\n\nTake a look: {$product->url}" : null;
        // Best placement wins: weekly top 3 beats daily top 3.
        $weekOver = $launchDay && $launchDay->copy()->endOfWeek()->isPast();
        $winLabel = match (true) {
            $weekOver && $weeklyRank && $weeklyRank <= 3 => "#{$weeklyRank} Product of the Week",
            $dayOver && $dailyRank && $dailyRank <= 3 => "#{$dailyRank} Product of the Day",
            default => null,
        };
        $shareWon = $winLabel ? "{$product->name} finished {$winLabel} on RankedLaunch.\n\nThank you to everyone who checked it out and voted.\n{$publicUrl}" : null;
        $shares = array_filter([
            'won' => $shareWon ? ['Share your win: '.$winLabel, $shareWon, $publicUrl] : null,
            'pre' => $sharePre && ! $live ? ['Before launch, to build anticipation', $sharePre, $product->url] : null,
            'live' => ['On launch day', $shareLive, $publicUrl],
        ]);
    @endphp

    <div class="mx-auto max-w-5xl px-4 pt-8 sm:px-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-ink">
            <x-icon name="chevron-left" :size="16" :stroke="2" /> All launches
        </a>

        <header class="mt-6 flex flex-wrap items-center gap-5">
            <span class="app-icon size-16"><img src="{{ $product->logoUrl() }}" alt=""></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-[1.75rem] font-bold leading-tight tracking-[-0.03em]">{{ $product->name }}</h1>
                <p class="meta mt-1 flex flex-wrap">
                    <span class="font-semibold text-ink">{{ $product->statusLabel() }}</span>
                    <span>{{ config("rankedlaunch.plans.{$product->plan}.name") }}</span>
                    @if ($launchDay)<span>{{ $launchDay->format('M j, Y') }}</span>@endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ $publicUrl }}" class="btn btn-outline btn-sm">{{ $live ? 'Public page' : 'Preview' }} <x-icon name="arrow-up-right" :size="14" :stroke="2" /></a>
                @if ($canReschedule)
                    <a href="{{ route('launch.date', $product) }}" class="btn btn-secondary btn-sm"><x-icon name="calendar" :size="15" /> Change date</a>
                @endif
                @unless ($live)
                    <a href="{{ route('submit.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                @endunless
            </div>
        </header>
    </div>

    <div class="mx-auto mt-10 grid grid-cols-1 max-w-5xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="space-y-10">
            {{-- Countdown --}}
            @if ($launchDay && ! $live && in_array($status, ['pending', 'approved']))
                <section class="card px-6 py-6 text-center">
                    <p class="text-sm font-medium text-ink-2">Your launch starts in</p>
                    <p class="mt-2 text-[2.25rem] font-bold tabular-nums tracking-[-0.03em]" data-countdown="{{ $launchDay->copy()->startOfDay()->utc()->toIso8601String() }}" data-countdown-days>
                        {{ $daysUntilLaunch }} {{ Str::plural('day', $daysUntilLaunch) }}
                    </p>
                    <p class="mt-1 text-sm text-ink-2">{{ $launchDay->format('l, F j') }} · <x-local-time :at="\Illuminate\Support\Carbon::parse($launchDay->toDateString(), 'UTC')" /></p>
                    @if ($canReschedule)
                        <a href="{{ route('launch.date', $product) }}" class="mt-3 inline-block text-sm font-medium text-ink underline underline-offset-4">Change launch date</a>
                    @endif
                </section>
            @endif

            {{-- Results --}}
            @if ($live)
                <section>
                    <h2 class="group-header">{{ $dayOver ? 'Results' : 'Live now' }}</h2>
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([
                            'Upvotes' => number_format($product->votes_count),
                            ($dayOver ? 'Final rank' : 'Rank today') => $dailyRank ? '#'.$dailyRank : '—',
                            'Page views' => number_format($product->views_count),
                            'Visits to your site' => number_format($product->clicks_count),
                        ] as $label => $value)
                            <div class="card p-4">
                                <dd class="text-[1.625rem] font-bold tabular-nums tracking-[-0.03em]">{{ $value }}</dd>
                                <dt class="mt-0.5 text-xs text-ink-2">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>
                    @if ($weeklyRank)
                        <p class="mt-3 px-1 text-sm text-ink-2">#{{ $weeklyRank }} this week so far.{{ $weeklyRank > 3 ? ' The top 3 of the week get a winner badge.' : ' You’re in the top 3. Keep sharing to hold your spot.' }}</p>
                    @endif
                </section>
            @endif

            {{-- Upgrade: backlink and visibility (dates are free on every plan) --}}
            @if ($paymentsAvailable && $isFree && in_array($status, ['pending', 'approved']) && ! $dayOver)
                <section class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <p class="font-semibold">Make this launch help your SEO</p>
                        <p class="mt-0.5 text-sm text-ink-2">
                            Pro turns your link into a dofollow backlink for good, and features you on the homepage, your category and Discover for 7 days.
                            @if ($pricing->promoActive())
                                <span class="font-medium text-ink">{{ \App\Services\Pricing::dollars($pricing->priceFor('pro')) }} instead of {{ \App\Services\Pricing::dollars($pricing->regularPrice('pro')) }}, {{ $pricing->promoRemaining() }} launch-offer spots left.</span>
                            @else
                                One-time {{ \App\Services\Pricing::dollars($pricing->priceFor('pro')) }}.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('submit.plan', $product) }}" class="btn btn-primary shrink-0">Upgrade</a>
                </section>
            @endif

            {{-- What happens next --}}
            <section>
                <h2 class="group-header">What happens next</h2>
                <ol class="card overflow-hidden">
                    @foreach ($steps as [$title, $detail, $state])
                        <li class="list-row flex gap-4 px-5 py-4" style="--inset: 3.75rem">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full
                                {{ $state === 'done' ? 'bg-ink text-on-ink' : '' }}
                                {{ $state === 'current' ? 'ring-2 ring-ink' : '' }}
                                {{ $state === 'upcoming' ? 'ring-1 ring-line' : '' }}
                                {{ $state === 'failed' ? 'ring-2 ring-danger text-danger' : '' }}">
                                @if ($state === 'done')
                                    <x-icon name="check" :size="13" :stroke="3" />
                                @elseif ($state === 'current')
                                    <x-icon name="dot" :size="12" />
                                @elseif ($state === 'failed')
                                    <x-icon name="close" :size="12" :stroke="2.5" />
                                @endif
                            </span>
                            <span>
                                <span class="block font-semibold tracking-[-0.01em] {{ $state === 'upcoming' ? 'text-ink-2' : '' }}">{{ $title }}</span>
                                <span class="mt-0.5 block text-sm {{ $state === 'failed' ? 'text-danger' : 'text-ink-2' }}">{{ $detail }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                @if (in_array($status, ['draft', 'awaiting_payment']))
                    <a href="{{ route('submit.plan', $product) }}" class="btn btn-primary mt-4">Finish booking your date</a>
                @endif
            </section>

            {{-- Share kit --}}
            <section>
                <h2 class="group-header">Share kit</h2>
                <div class="space-y-4">
                    @foreach ($shares as $key => [$label, $text, $link])
                        <div class="card p-5">
                            <p class="text-sm font-semibold">{{ $label }}</p>
                            <textarea id="share-{{ $key }}" readonly rows="7" class="input mt-3 resize-none !text-sm">{{ $text }}</textarea>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="https://x.com/intent/post?text={{ urlencode($text) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><x-brand name="x" :size="14" /> Post on X</a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($link) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-brand name="linkedin" :size="14" /> Share on LinkedIn</a>
                                <button type="button" data-copy="share-{{ $key }}" class="btn btn-secondary btn-sm">Copy text</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-10">
            {{-- Checklist --}}
            <section>
                <h2 class="group-header">Launch-day checklist</h2>
                <div class="card overflow-hidden text-sm">
                    @foreach ([
                        'Before' => [
                            [$product->screenshots ? true : false, 'Add 3–4 screenshots', $live ? null : route('submit.edit', $product)],
                            [null, 'Write your launch post (use the share kit)', null],
                            [null, 'Tell your users and waitlist the date', null],
                        ],
                        'On the day' => [
                            [null, 'Post on X and LinkedIn in the morning', null],
                            [null, 'Share it in Show HN, r/SideProject, Indie Hackers', null],
                            [null, 'Reply to every comment and DM', null],
                        ],
                        'After' => [
                            [null, 'Add your badge to your site', null],
                            [null, 'Share your result and thank people', null],
                        ],
                    ] as $phase => $items)
                        <p class="px-4 pt-4 pb-1 text-xs font-semibold text-ink-2">{{ $phase }}</p>
                        @foreach ($items as [$done, $text, $href])
                            <div class="flex items-start gap-3 px-4 py-2">
                                <span class="mt-0.5 grid size-[1.125rem] shrink-0 place-items-center rounded-md {{ $done ? 'bg-ink text-on-ink' : 'ring-1 ring-ink-3' }}">
                                    @if ($done)<x-icon name="check" :size="11" :stroke="3" />@endif
                                </span>
                                @if ($href && ! $done)
                                    <a href="{{ $href }}" class="underline decoration-ink-3 underline-offset-2 hover:decoration-ink">{{ $text }}</a>
                                @else
                                    <span class="{{ $done ? 'text-ink-2 line-through' : '' }}">{{ $text }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                    <div class="h-3"></div>
                </div>
            </section>

            {{-- Share image --}}
            <section>
                <h2 class="group-header">Share image</h2>
                <div class="card p-4">
                    <img src="{{ route('og.product', $product) }}" alt="Share image preview" class="w-full rounded-lg outline outline-1 -outline-offset-1 outline-line" loading="lazy">
                    <p class="mt-3 text-xs leading-relaxed text-ink-2">Shows up automatically when your page is shared on X, LinkedIn, Slack and iMessage. It updates with your rank.</p>
                    <a href="{{ route('og.product', $product) }}" download="{{ $product->slug }}-rankedlaunch.png" class="btn btn-secondary btn-sm mt-3 w-full">Download image</a>
                </div>
            </section>

            {{-- Badge --}}
            <section>
                <h2 class="group-header">Your badge</h2>
                @if ($live)
                    <x-badge-picker :product="$product" />
                    @if ($isFree)
                        <p class="mt-2 px-1 text-xs text-ink-2">Free launches link with nofollow. Upgrade for a dofollow backlink.</p>
                    @endif
                @else
                    <div class="card p-4">
                        <p class="text-sm text-ink-2">Unlocks on launch day. Finish in the top 3 and it upgrades to a winner badge automatically.</p>
                    </div>
                @endif
            </section>

            {{-- Delete listing --}}
            <section id="delete">
                <h2 class="group-header">Danger zone</h2>
                <form method="POST" action="{{ route('launch.destroy', $product) }}" class="card space-y-3 p-4">
                    @csrf @method('DELETE')
                    <p class="text-sm text-ink-2">Deleting removes {{ $product->name }}’s page, votes, stats and images for good.
                        @if ($product->plan !== 'free' && $product->isLive())
                            Paid launches that already went live aren’t refunded.
                        @endif
                    </p>
                    <label class="flex items-start gap-2 text-xs text-ink-2"><input type="checkbox" name="confirm" value="1" class="mt-0.5 accent-[var(--ink)]"> I understand this can’t be undone.</label>
                    @error('confirm')<p class="field-error">{{ $message }}</p>@enderror
                    <button class="btn btn-secondary btn-sm w-full !text-danger">Delete this listing</button>
                </form>
            </section>

            {{-- Other places --}}
            <section>
                <h2 class="group-header">Launch everywhere</h2>
                <ul class="card overflow-hidden text-sm">
                    @foreach ([
                        ['Product Hunt', 'https://www.producthunt.com/', 'The biggest launch day. Plan it for a separate date.'],
                        ['Hacker News · Show HN', 'https://news.ycombinator.com/showhn.html', 'Best for dev tools. Plain, honest title.'],
                        ['Indie Hackers', 'https://www.indiehackers.com/', 'Share the story behind it, not just the link.'],
                        ['r/SideProject', 'https://www.reddit.com/r/SideProject/', 'Friendly to launches. Read the rules first.'],
                        ['r/SaaS', 'https://www.reddit.com/r/SaaS/', 'Good for B2B products and pricing feedback.'],
                    ] as [$name, $href, $tip])
                        <li class="list-row">
                            <a href="{{ $href }}" target="_blank" rel="noopener nofollow" class="flex items-start justify-between gap-3 px-4 py-3 hover:bg-surface-2">
                                <span>
                                    <span class="block font-medium">{{ $name }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-2">{{ $tip }}</span>
                                </span>
                                <x-icon name="arrow-up-right" :size="14" :stroke="2" class="mt-1 text-ink-3" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
</x-layout>
