<x-admin.shell title="Overview" :subtitle="now()->format('l, F j').' · everything at a glance'">
    @php
        $levels = [
            'action' => ['Needs action', 'dot'],
            'watch' => ['Worth watching', 'eye'],
            'good' => ['Going well', 'check'],
        ];
        $stages = $flow['stages'];
        $top = max(1, $stages[0][1]);
    @endphp

    {{-- Intelligence --}}
    <section class="tray" data-reveal>
        <div class="flex flex-wrap items-center justify-between gap-2 px-3 pt-2 pb-3">
            <div class="flex items-center gap-2">
                <x-logo :size="20" :wordmark="false" class="text-ink" />
                <h2 class="text-sm font-semibold">Intelligence</h2>
            </div>
            <span class="text-xs text-ink-2">Updated {{ now()->format('H:i') }}</span>
        </div>

        <div class="grid grid-cols-1 gap-1.5 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
            {{-- Insights --}}
            <div class="card overflow-hidden">
                <p class="px-5 pt-4 pb-1 text-xs font-semibold text-ink-2">Insights</p>
                <ul>
                    @foreach ($insights as $insight)
                        @php
                            [$levelLabel, $levelIcon] = $levels[$insight['level']];
                        @endphp
                        <li class="list-row flex gap-3.5 px-5 py-3.5" style="--inset: 3.25rem">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full {{ $insight['level'] === 'action' ? 'bg-ink text-on-ink' : 'ring-1 ring-line text-ink' }}">
                                <x-icon :name="$levelIcon" :size="$insight['level'] === 'action' ? 10 : 13" :stroke="2.25" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[0.6875rem] font-semibold uppercase tracking-wide text-ink-2">{{ $levelLabel }}</p>
                                <p class="mt-0.5 font-semibold tracking-[-0.01em]">{{ $insight['title'] }}</p>
                                <p class="mt-0.5 text-sm text-ink-2">{{ $insight['detail'] }}</p>
                            </div>
                            @if ($insight['url'])
                                <a href="{{ $insight['url'] }}" class="btn btn-secondary btn-sm shrink-0 self-center">{{ $insight['label'] }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Launch flow --}}
            <div class="card p-5">
                <div class="flex items-baseline justify-between">
                    <p class="text-xs font-semibold text-ink-2">Launch flow</p>
                    <p class="text-xs text-ink-2">Last {{ $flow['days'] }} days</p>
                </div>
                <ol class="mt-4">
                    @foreach ($stages as $i => [$label, $count, $help])
                        @php
                            $prev = $i > 0 ? $stages[$i - 1][1] : null;
                            // Only a real funnel step when it can't exceed 100% (one maker can submit several products).
                            $rate = $prev && $count <= $prev ? round($count / $prev * 100) : null;
                        @endphp
                        @if ($i > 0)
                            <li class="flex items-center gap-3 py-1 pl-3" aria-hidden="true">
                                <span class="h-5 w-px bg-line"></span>
                                <span class="text-[0.6875rem] font-medium tabular-nums text-ink-2">{{ $rate !== null ? $rate.'% continue' : '' }}</span>
                            </li>
                        @endif
                        <li class="grid grid-cols-[6.5rem_minmax(0,1fr)_3rem] items-center gap-3" title="{{ $help }}">
                            <span class="text-sm font-medium">{{ $label }}</span>
                            <span class="h-2.5 overflow-hidden rounded-full bg-fill">
                                <span data-grow class="block h-full rounded-full bg-ink" style="width: {{ $count ? max(2, round($count / $top * 100)) : 0 }}%; opacity: {{ 1 - $i * 0.1 }}"></span>
                            </span>
                            <span class="text-right text-sm font-semibold tabular-nums">{{ number_format($count) }}</span>
                        </li>
                        @if ($label === 'Booked')
                            <li class="mt-1.5 grid grid-cols-[6.5rem_minmax(0,1fr)] gap-3 text-xs text-ink-2">
                                <span></span>
                                <span>Free {{ $flow['split']['free'] }} · Paid {{ $flow['split']['paid'] }}{{ ($flow['split']['free'] + $flow['split']['paid']) ? ' ('.round($flow['split']['paid'] / ($flow['split']['free'] + $flow['split']['paid']) * 100).'% paid)' : '' }}</span>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- KPIs --}}
    <dl class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4" data-reveal>
        @foreach ($kpis as $kpi)
            @php
                [$label, $value, $sub] = $kpi;
            @endphp
            <a href="{{ $kpi[3] ?? '#' }}" class="card card-hover block p-4 {{ isset($kpi[3]) ? '' : 'pointer-events-none' }}">
                <dt class="text-xs text-ink-2">{{ $label }}</dt>
                <dd class="mt-1 text-[1.5rem] font-bold tabular-nums tracking-[-0.03em]">{{ $value }}</dd>
                <dd class="text-xs text-ink-2">{{ $sub }}</dd>
            </a>
        @endforeach
    </dl>

    {{-- Charts --}}
    <div class="mt-8 grid grid-cols-1 gap-3 xl:grid-cols-3" data-reveal>
        @foreach ($charts as $title => [$series, $format])
            <x-admin.bar-chart :title="$title" :series="$series" :format="$format" />
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-8 xl:grid-cols-2">
        {{-- Review queue --}}
        <section data-reveal>
            <h2 class="group-header">
                <span>Waiting for review</span>
                <a href="{{ route('admin.products.index', ['status' => 'pending']) }}">See all</a>
            </h2>
            <ul class="card overflow-hidden">
                @forelse ($pending as $product)
                    <li class="list-row flex items-center gap-3 px-4 py-3" style="--inset: 4rem">
                        <span class="app-icon size-9"><img src="{{ $product->logoUrl() }}" alt=""></span>
                        <a href="{{ route('admin.products.edit', $product) }}" class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $product->name }}</span>
                            <span class="meta flex"><span>{{ config("rankedlaunch.plans.{$product->plan}.name") }}</span><span>{{ $product->launch_date?->format('M j') }}</span></span>
                        </a>
                        <form method="POST" action="{{ route('admin.approve', $product) }}">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-ink-2">Nothing to review.</li>
                @endforelse
            </ul>
        </section>

        {{-- Live today --}}
        <section data-reveal>
            <h2 class="group-header">
                <span>Live today</span>
                <a href="{{ route('home') }}">View board</a>
            </h2>
            <ol class="card overflow-hidden">
                @forelse ($today as $i => $product)
                    <li class="list-row flex items-center gap-3 px-4 py-3" style="--inset: 4.5rem">
                        <span class="w-4 text-sm font-semibold tabular-nums">{{ $i + 1 }}</span>
                        <span class="app-icon size-9"><img src="{{ $product->logoUrl() }}" alt=""></span>
                        <a href="{{ route('admin.products.edit', $product) }}" class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $product->name }}</a>
                        <span class="meta flex shrink-0 tabular-nums"><span>{{ $product->votes_count }} votes</span><span>{{ $product->views_count }} views</span><span>{{ $product->clicks_count }} clicks</span></span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-ink-2">No launches today.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- Calendar --}}
    <section class="mt-8" data-reveal>
        <h2 class="group-header">
            <span>Next 14 days</span>
            <span class="flex items-center gap-3 text-xs font-normal text-ink-2">
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-ink"></span>Paid</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-ink-3"></span>Free</span>
            </span>
        </h2>
        <div class="card grid grid-cols-7 gap-px overflow-hidden bg-line p-0 sm:grid-cols-14">
            @foreach ($calendar as $day)
                @php
                    $paidCap = max(1, config('rankedlaunch.paid_slots_per_day'));
                    $freeCap = max(1, config('rankedlaunch.free_slots_per_day'));
                @endphp
                <div class="bg-surface p-2.5 text-center" title="{{ $day['date']->format('D, M j') }}: {{ $day['paid'] }}/{{ $paidCap }} paid, {{ $day['free'] }}/{{ $freeCap }} free">
                    <p class="text-[0.625rem] font-medium uppercase text-ink-2">{{ $day['date']->format('D') }}</p>
                    <p class="text-sm font-semibold tabular-nums">{{ $day['date']->format('j') }}</p>
                    <div class="mt-2 space-y-1">
                        <div class="h-1 overflow-hidden rounded-full bg-fill"><div class="h-full rounded-full bg-ink" style="width: {{ min(100, $day['paid'] / $paidCap * 100) }}%"></div></div>
                        <div class="h-1 overflow-hidden rounded-full bg-fill"><div class="h-full rounded-full bg-ink-3" style="width: {{ min(100, $day['free'] / $freeCap * 100) }}%"></div></div>
                    </div>
                    <p class="mt-1.5 text-[0.625rem] tabular-nums text-ink-2">{{ $day['paid'] + $day['free'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Activity --}}
    <section class="mt-8" data-reveal>
        <h2 class="group-header">
            <span>Recent activity</span>
            <a href="{{ route('admin.activity') }}">See all</a>
        </h2>
        @include('admin.partials.activity-list', ['logs' => $activity])
    </section>
</x-admin.shell>
