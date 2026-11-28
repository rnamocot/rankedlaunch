{{--
    "Happening now": real activity flows into the stream at the top and drops out at the bottom,
    next to live visitor numbers, above a strip of launched apps drifting through.
--}}
@php
    $typeIcons = ['vote' => 'chevron-up', 'comment' => 'message', 'launch' => 'arrow-up-right', 'scheduled' => 'calendar', 'waiting' => 'bell', 'save' => 'bookmark', 'listed' => 'plus'];
    $iconSvgs = collect($typeIcons)->map(fn ($icon) => view('components.icon', ['name' => $icon, 'size' => 14, 'stroke' => 2])->render())->all();
@endphp
<section class="mx-auto mt-24 max-w-6xl px-4 sm:px-6" data-reveal>
    <div class="mx-auto max-w-2xl text-center">
        <p class="inline-flex items-center gap-2 text-sm font-medium text-ink-2"><span class="live-dot" aria-hidden="true"></span> Live on RankedLaunch</p>
        <h2 class="section-title mt-3">Happening right now</h2>
        <p class="mt-3 text-ink-2">Every upvote, comment and launch, the moment it happens. Real activity, nothing staged.</p>
    </div>

    <div class="tray mt-10">
        <div class="grid grid-cols-1 gap-1.5 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
            {{-- Activity stream --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
                    <span class="text-sm font-semibold">Activity</span>
                    <span class="text-xs text-ink-2" data-stream-status>Live</span>
                </div>
                <div class="stream-viewport" data-stream data-url="{{ route('live.activity') }}"
                     data-icons="{{ json_encode($iconSvgs) }}">
                    <ul class="stream-list" data-stream-list>
                        @foreach (array_slice($activity, 0, 6) as $event)
                            <li class="stream-item" data-id="{{ $event['id'] }}">
                                <span class="app-icon size-9 !rounded-[0.6rem]"><img src="{{ $event['logo'] }}" alt="" loading="lazy"></span>
                                <span class="min-w-0 flex-1 truncate text-sm"><span class="font-semibold">{{ $event['product'] }}</span> <span class="text-ink-2">{{ $event['text'] }}</span></span>
                                <time class="shrink-0 text-xs tabular-nums text-ink-3" datetime="{{ $event['at'] }}" data-ago>{{ \Illuminate\Support\Carbon::parse($event['at'])->diffForHumans(short: true) }}</time>
                                <span class="stream-type">{!! $iconSvgs[$event['type']] ?? '' !!}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if (empty($activity))
                        <p class="px-6 py-16 text-center text-sm text-ink-2">Quiet for now. The first upvote of the day shows up here.</p>
                    @endif
                </div>
            </div>

            {{-- Live numbers --}}
            <div class="card flex flex-col justify-between gap-8 p-6">
                <div>
                    <p class="flex items-center gap-2 text-sm font-medium text-ink-2"><span class="live-dot" aria-hidden="true"></span> Here right now</p>
                    <p class="mt-3 text-[4.5rem] font-bold leading-none tabular-nums tracking-[-0.05em]" data-presence="live" data-countup="{{ $heroStats['live'][1] }}">{{ number_format($heroStats['live'][1]) }}</p>
                    <p class="mt-2 text-sm text-ink-2"><span data-presence-word>{{ $heroStats['live'][1] === 1 ? 'person is' : 'people are' }}</span> browsing launches with you.</p>
                </div>
                <dl class="grid grid-cols-2 gap-1.5">
                    @foreach ([['Visitors today', $heroStats['today'][1], 'today'], ['All-time visitors', $siteTotals['visitors'], 'total'], ['Launches', $siteTotals['launches'], null], ['Upvotes given', $siteTotals['upvotes'], null]] as [$label, $value, $presence])
                        <div class="rounded-2xl bg-fill px-4 py-3.5">
                            <dd class="text-[1.375rem] font-bold leading-none tabular-nums tracking-[-0.03em]" @if ($presence) data-presence="{{ $presence }}" @endif data-countup="{{ $value }}">{{ number_format($value) }}</dd>
                            <dt class="mt-1.5 text-xs text-ink-2">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        {{-- Launched apps drifting through --}}
        @if ($logoStrip->count() >= 6)
            @php
                $rows = [$logoStrip->values(), $logoStrip->reverse()->values()];
            @endphp
            <div class="marquee mt-1.5 space-y-2 overflow-hidden rounded-[1rem] bg-surface py-4 outline outline-1 -outline-offset-1 outline-line" aria-label="Products launched on RankedLaunch">
                @foreach ($rows as $r => $items)
                    <div class="marquee-track {{ $r === 1 ? 'marquee-reverse' : '' }}">
                        @for ($copy = 0; $copy < 2; $copy++)
                            <div class="flex shrink-0 gap-2 pr-2" @if ($copy) aria-hidden="true" @endif>
                                @foreach ($items as $p)
                                    <a href="{{ route('product', $p) }}" class="chip !h-10 shrink-0" @if ($copy) tabindex="-1" @endif>
                                        <span class="app-icon size-6 !rounded-[0.4rem]"><img src="{{ $p->logoUrl() }}" alt="" loading="lazy"></span>
                                        {{ $p->name }}
                                        <span class="tabular-nums text-ink-3">{{ number_format($p->votes_count) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
