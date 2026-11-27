<x-layout title="Go live" :noindex="true">
    @php
        $review = config('rankedlaunch.review_listings');
        $selectedPlan = old('plan', $product->requested_plan ?? ($product->plan !== 'free' ? $product->plan : 'free'));
        $selectedWhen = old('when', $product->launch_date && ! $product->launch_date->isToday() ? 'date' : 'now');
        $selectedDate = old('launch_date', ($product->requested_launch_date ?? $product->launch_date)?->toDateString());
        $months = collect($days)->groupBy(fn ($d) => $d['date']->format('F Y'));
    @endphp

    <div class="mx-auto max-w-3xl px-4 pt-12 sm:px-6">
        <p class="text-sm font-medium text-ink-2">Step 2 of 2</p>
        <h1 class="large-title mt-2">When should it go live?</h1>

        <div class="card mt-8 flex items-center gap-4 p-4">
            <span class="app-icon size-12"><img src="{{ $product->logoUrl() }}" alt=""></span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ $product->name }}</p>
                <p class="truncate text-sm text-ink-2">{{ $product->tagline }}</p>
            </div>
            <a href="{{ route('submit.edit', $product) }}" class="text-sm font-medium text-ink-2 hover:text-ink">Edit</a>
        </div>

        <form method="POST" action="{{ route('submit.plan', $product) }}" data-plan-form data-dates='@json($dates)' class="mt-10 space-y-10">
            @csrf

            {{-- 1. When --}}
            <section>
                <h2 class="group-header">Timing</h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ([
                        'now' => ['List it now', 'Already live? It goes on today’s board'.($review ? ' as soon as it’s approved.' : ' right away.'), 'arrow-up-right'],
                        'date' => ['Schedule a launch', 'Pick a launch day to build up to. Free, any open day.', 'calendar'],
                    ] as $value => [$title, $text, $icon])
                        <label class="group card relative flex cursor-pointer gap-4 p-5 has-[:checked]:!outline-2 has-[:checked]:!outline-ink">
                            <input type="radio" name="when" value="{{ $value }}" class="peer sr-only" @checked($selectedWhen === $value)>
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-fill group-has-[:checked]:bg-ink group-has-[:checked]:text-on-ink"><x-icon :name="$icon" :size="18" /></span>
                            <span>
                                <span class="block font-semibold">{{ $title }}</span>
                                <span class="mt-0.5 block text-sm text-ink-2">{{ $text }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div data-date-picker class="mt-4">
                    <div class="card p-4 sm:p-5">
                        <p class="mb-4 text-xs text-ink-2">Launches go live at <x-local-time :at="today('UTC')->addDay()" />. Greyed-out days are full for the plan you pick below.</p>
                        @foreach ($months as $month => $list)
                            <p class="mb-3 text-sm font-semibold {{ ! $loop->first ? 'mt-6' : '' }}">{{ $month }}</p>
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                                @foreach ($list as $d)
                                    @continue($d['date']->isToday())
                                    @php
                                        $value = $d['date']->toDateString();
                                    @endphp
                                    <label data-date class="cursor-pointer">
                                        <input type="radio" name="launch_date" value="{{ $value }}" class="peer sr-only" @checked($selectedDate === $value)>
                                        <span class="flex flex-col items-center rounded-xl bg-fill py-2.5 transition hover:bg-fill-strong peer-checked:bg-ink peer-checked:text-on-ink peer-focus-visible:outline-2 peer-focus-visible:outline-ink">
                                            <span class="text-[0.6875rem] font-medium uppercase tracking-wide opacity-60">{{ $d['date']->format('D') }}</span>
                                            <span class="text-base font-semibold tabular-nums">{{ $d['date']->format('j') }}</span>
                                            <span data-date-note class="h-3.5 text-[0.625rem] font-medium opacity-70"></span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                @error('launch_date')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            @if ($paymentsAvailable)
            {{-- 2. Plan --}}
            <section>
                <h2 class="group-header">Plan</h2>
                <x-promo-banner :pricing="$pricing" class="mb-3" />
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($plans as $key => $plan)
                        @php($soon = $key !== 'free' && ! $paymentsAvailable)
                        <label class="group card relative flex flex-col p-5 has-[:checked]:!outline-2 has-[:checked]:!outline-ink {{ $soon ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                            <input type="radio" name="plan" value="{{ $key }}" class="peer sr-only" @checked($selectedPlan === $key && ! $soon) @disabled($soon)>
                            @if ($soon)
                                <span class="absolute top-4 right-4 text-[0.6875rem] font-semibold uppercase tracking-wide text-ink-2">Coming soon</span>
                            @endif
                            <span class="flex items-center justify-between">
                                <span class="text-sm font-semibold">{{ $plan['name'] }}</span>
                                <span class="grid size-5 place-items-center rounded-full ring-[1.5px] ring-ink-3 group-has-[:checked]:bg-ink group-has-[:checked]:ring-ink">
                                    <x-icon name="check" :size="12" :stroke="3" class="text-on-ink opacity-0 group-has-[:checked]:opacity-100" />
                                </span>
                            </span>
                            <x-price :plan="$key" :pricing="$pricing" size="text-2xl" class="mt-3" />
                            <span class="mt-1 text-xs text-ink-2">{{ $key === 'free' ? 'Free forever' : 'One-time' }}{{ $pricing->isPromoPlan($key) ? ' · launch offer, '.$pricing->promoRemaining().' left' : '' }}</span>
                            <ul class="mt-4 space-y-1.5 text-xs text-ink-2">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex gap-1.5"><x-icon name="check" :size="14" :stroke="2" class="mt-px shrink-0 text-ink" /> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        </label>
                    @endforeach
                </div>
                @error('plan')<p class="field-error">{{ $message }}</p>@enderror
            </section>
            @else
                {{-- Free early access: every listing is free, nothing to choose --}}
                <input type="hidden" name="plan" value="free">
                <p class="card flex items-center gap-3 px-4 py-3 text-sm text-ink-2"><x-icon name="check" :size="17" :stroke="2" class="text-ink" /> Free while RankedLaunch is in early access: votes, stats, share kit and badges included.</p>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-ink-2">
                    {{ $review ? 'Every listing gets a quick review before it appears.' : 'Your listing appears as soon as you confirm.' }}
                    @if ($product->status === 'draft')
                        <button type="submit" form="discard-draft" class="ml-1 font-medium text-ink underline underline-offset-2 hover:text-danger">Discard this draft</button>
                    @endif
                </p>
                <button class="btn btn-primary btn-lg" data-plan-submit data-free="List it" data-pro="Continue to payment">Continue</button>
            </div>
        </form>

        @if ($product->status === 'draft')
            <form id="discard-draft" method="POST" action="{{ route('launch.destroy', $product) }}" hidden>
                @csrf @method('DELETE')
                <input type="hidden" name="confirm" value="1">
            </form>
        @endif
    </div>
</x-layout>
