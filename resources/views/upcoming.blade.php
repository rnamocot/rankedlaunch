<x-layout title="Upcoming launches" description="Products launching soon on RankedLaunch. See what's coming up and get notified the day each one goes live.">
    <section class="mx-auto max-w-3xl px-4 pt-12 pb-8 sm:px-6">
        <h1 class="large-title">Upcoming</h1>
        <p class="mt-3 text-ink-2">Launches scheduled for the coming days. Tap <span class="font-medium text-ink">Notify</span> and we’ll tell you when one goes live, so you can be among the first to try it and vote.</p>
    </section>

    <div class="mx-auto max-w-3xl space-y-10 px-4 sm:px-6">
        @forelse ($days as $date => $products)
            @php($day = \Illuminate\Support\Carbon::parse($date))
            <section data-reveal>
                <h2 class="group-header">
                    <span>{{ $day->isTomorrow() ? 'Tomorrow' : $day->format('l') }} <span class="font-normal text-ink-2">· {{ $day->format('M j') }}</span></span>
                    <span class="text-[0.8125rem] font-normal text-ink-2">Goes live at <x-local-time :at="$day->copy()->utc()->startOfDay()" /></span>
                </h2>
                <div class="tray">
                    <ul class="card overflow-hidden">
                        @foreach ($products as $product)
                            <li class="list-row flex items-center gap-4 px-4 py-3.5 sm:px-5" style="--inset: 5rem">
                                <span class="app-icon size-12 sm:size-14"><img src="{{ $product->logoUrl() }}" alt="" loading="lazy" width="56" height="56"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[0.9375rem] font-semibold tracking-[-0.015em]">{{ $product->name }}</span>
                                    <span class="mt-0.5 block truncate text-sm text-ink-2">{{ $product->tagline }}</span>
                                    <span class="meta mt-1 flex">
                                        @if ($product->category)<span>{{ $product->category->name }}</span>@endif
                                        <span>{{ $product->pricingLabel() }}</span>
                                        @if ($product->launch_reminders_count)<span>{{ $product->launch_reminders_count }} waiting</span>@endif
                                    </span>
                                </span>
                                <x-toggle-button :url="route('remind', $product)" :active="isset($remindedIds[$product->id])" icon="bell" on="Notifying" off="Notify me" :label="'Notify me when '.$product->name.' launches'" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @empty
            <div class="card px-6 py-14 text-center">
                <p class="font-semibold">Nothing scheduled yet</p>
                <p class="mt-1 text-sm text-ink-2">Pick a date now and you’ll be the first thing people see here.</p>
                <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-5">Schedule your launch</a>
            </div>
        @endforelse

        <div class="card flex flex-wrap items-center justify-between gap-4 p-5" data-reveal>
            <div>
                <p class="font-semibold">Launching something soon?</p>
                <p class="mt-0.5 text-sm text-ink-2">Book any open day. Your page goes live at the start of that day.</p>
            </div>
            <a href="{{ route('submit') }}" class="btn btn-primary btn-sm">Pick your launch day</a>
        </div>
    </div>
</x-layout>
