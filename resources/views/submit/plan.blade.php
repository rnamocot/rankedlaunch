<x-layout title="Choose your launch" :noindex="true">
    <div class="mx-auto max-w-3xl px-4 pt-12 sm:px-6">
        <p class="text-sm font-medium text-ink-2">Step 2 of 2</p>
        <h1 class="large-title mt-2">Pick your launch</h1>

        <div class="card mt-8 flex items-center gap-4 p-4">
            <span class="app-icon size-12"><img src="{{ $product->logoUrl() }}" alt=""></span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ $product->name }}</p>
                <p class="truncate text-sm text-ink-2">{{ $product->tagline }}</p>
            </div>
            <a href="{{ route('submit.edit', $product) }}" class="text-sm font-medium text-ink-2 hover:text-ink">Edit</a>
        </div>

        <x-promo-banner :pricing="$pricing" class="mt-4" />

        <form method="POST" action="{{ route('submit.plan', $product) }}" data-plan-form class="mt-10">
            @csrf

            <h2 class="group-header">Plan</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach ($plans as $key => $plan)
                    <label class="group card relative flex cursor-pointer flex-col p-5 has-[:checked]:!outline-2 has-[:checked]:!outline-ink">
                        <input type="radio" name="plan" value="{{ $key }}" class="peer sr-only" @checked(old('plan', $product->requested_plan ?? 'pro') === $key)>
                        <span class="flex items-center justify-between">
                            <span class="text-sm font-semibold">{{ $plan['name'] }}</span>
                            <span class="grid size-5 place-items-center rounded-full ring-[1.5px] ring-ink-3 group-has-[:checked]:bg-ink group-has-[:checked]:ring-ink">
                                <x-icon name="check" :size="12" :stroke="3" class="text-on-ink opacity-0 group-has-[:checked]:opacity-100" />
                            </span>
                        </span>
                        <x-price :plan="$key" :pricing="$pricing" size="text-2xl" class="mt-3" />
                        <span class="mt-1 text-xs text-ink-2">
                            @if ($key === 'free')
                                Launches {{ $freeDate->format('M j') }} · {{ (int) today()->diffInDays($freeDate) }}-day wait
                            @else
                                @if ($pricing->isPromoPlan($key))
                                    Launch offer · {{ $pricing->promoRemaining() }} left
                                @else
                                    One-time · pick any date
                                @endif
                            @endif
                        </span>
                        <ul class="mt-4 space-y-1.5 text-xs text-ink-2">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex gap-1.5"><x-icon name="check" :size="14" :stroke="2" class="mt-px text-ink" /> {{ $feature }}</li>
                            @endforeach
                        </ul>
                    </label>
                @endforeach
            </div>
            @error('plan')<p class="field-error">{{ $message }}</p>@enderror

            <section data-paid-dates class="mt-10">
                <h2 class="group-header">
                    <span>Launch date</span>
                    <span class="text-xs font-normal text-ink-2">Launches go live at 00:00 UTC</span>
                </h2>
                <div class="card p-4 sm:p-5">
                    @php($months = collect($paidDates)->groupBy(fn ($d) => $d['date']->format('F Y')))
                    @foreach ($months as $month => $dates)
                        <p class="mb-3 text-sm font-semibold {{ ! $loop->first ? 'mt-6' : '' }}">{{ $month }}</p>
                        <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                            @foreach ($dates as $d)
                                @php($value = $d['date']->toDateString())
                                <label data-date class="{{ $d['left'] ? 'cursor-pointer' : 'cursor-not-allowed opacity-35' }}">
                                    <input type="radio" name="launch_date" value="{{ $value }}" data-left="{{ $d['left'] }}" data-pro-left="{{ $d['pro_left'] }}" class="peer sr-only" @disabled(! $d['left']) @checked(old('launch_date', $product->requested_launch_date?->toDateString()) === $value)>
                                    <span class="flex flex-col items-center rounded-xl bg-fill py-2.5 transition peer-checked:bg-ink peer-checked:text-on-ink peer-focus-visible:outline-2 peer-focus-visible:outline-ink hover:bg-fill-strong">
                                        <span class="text-[0.6875rem] font-medium uppercase tracking-wide opacity-60">{{ $d['date']->format('D') }}</span>
                                        <span class="text-base font-semibold tabular-nums">{{ $d['date']->format('j') }}</span>
                                        <span data-date-note class="h-3.5 text-[0.625rem] font-medium opacity-70">{{ $d['left'] === 0 ? 'Full' : ($d['left'] <= 3 ? $d['left'].' left' : '') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                @error('launch_date')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            <div class="mt-10 flex items-center justify-between gap-4">
                <p class="text-xs text-ink-2">Every listing is reviewed before it goes live.</p>
                <button class="btn btn-primary btn-lg" data-plan-submit data-free="Join the free queue" data-pro="Continue to payment" data-featured="Continue to payment">Continue</button>
            </div>
        </form>
    </div>
</x-layout>
