<x-layout :title="'Change launch date · '.$product->name" :noindex="true">
    @php
        $planName = config("rankedlaunch.plans.{$product->plan}.name");
        $months = collect($options)->groupBy(fn ($o) => $o['date']->format('F Y'));
    @endphp
    <div class="mx-auto max-w-3xl px-4 pt-8 sm:px-6">
        <a href="{{ route('launch.hub', $product) }}" class="inline-flex items-center gap-1 text-sm font-medium text-ink-2 hover:text-ink">
            <x-icon name="chevron-left" :size="16" :stroke="2" /> Launch hub
        </a>

        <div class="intro mt-6">
            <h1 class="large-title">Change launch date</h1>
            <p class="mt-3 text-ink-2">
                {{ $product->name }} launches <span class="font-semibold text-ink">{{ $product->launch_date->format('l, M j') }}</span> on the {{ $planName }} plan.
                @if ($product->plan === 'free')
                    Free launches can move to any open date from the queue onwards. Want an earlier date? <a href="{{ route('submit.plan', $product) }}" class="font-medium text-ink underline underline-offset-2">Upgrade to pick any day</a>.
                @else
                    Pick any open date. Launches go live at 00:00 UTC.
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('launch.reschedule', $product) }}" class="mt-8">
            @csrf
            <div class="card p-4 sm:p-5">
                @foreach ($months as $month => $dates)
                    <p class="mb-3 text-sm font-semibold {{ ! $loop->first ? 'mt-6' : '' }}">{{ $month }}</p>
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
                        @foreach ($dates as $o)
                            @php
                                $value = $o['date']->toDateString();
                                $open = $o['left'] > 0 || $o['current'];
                            @endphp
                            <label class="{{ $open ? 'cursor-pointer' : 'cursor-not-allowed opacity-35' }}">
                                <input type="radio" name="launch_date" value="{{ $value }}" class="peer sr-only" @disabled(! $open) @checked(old('launch_date') ? old('launch_date') === $value : $o['current'])>
                                <span class="flex flex-col items-center rounded-xl bg-fill py-2.5 transition hover:bg-fill-strong peer-checked:bg-ink peer-checked:text-on-ink peer-focus-visible:outline-2 peer-focus-visible:outline-ink">
                                    <span class="text-[0.6875rem] font-medium uppercase tracking-wide opacity-60">{{ $o['date']->format('D') }}</span>
                                    <span class="text-base font-semibold tabular-nums">{{ $o['date']->format('j') }}</span>
                                    <span class="h-3.5 text-[0.625rem] font-medium opacity-70">{{ $o['current'] ? 'Current' : ($o['left'] === 0 ? 'Full' : ($o['left'] <= 3 ? $o['left'].' left' : '')) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
            @error('launch_date')<p class="field-error">{{ $message }}</p>@enderror

            <div class="mt-6 flex items-center justify-between gap-4">
                <p class="text-xs text-ink-2">You can change the date until the day before launch.</p>
                <button class="btn btn-primary btn-lg">Save new date</button>
            </div>
        </form>
    </div>
</x-layout>
