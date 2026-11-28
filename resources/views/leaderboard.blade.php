<x-layout :title="'Maker leaderboard · '.$periods[$period]" description="The makers earning the most upvotes on RankedLaunch, the community members supporting the most launches, and the longest daily streaks.">
    <section class="mx-auto max-w-5xl px-4 pt-12 pb-8 sm:px-6">
        <h1 class="large-title">Makers</h1>
        <p class="mt-3 text-ink-2">Who’s shipping, who’s supporting, and who shows up every day.</p>

        <nav class="segmented mt-6" aria-label="Period">
            @foreach ($periods as $key => $label)
                <a href="{{ route('leaderboard', $key) }}" @if($key === $period) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </section>

    <div class="mx-auto grid max-w-5xl grid-cols-1 gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section data-reveal>
            <h2 class="group-header"><span>Top makers</span><span class="text-[0.8125rem] font-normal text-ink-2">Upvotes on their launches</span></h2>
            <div class="tray">
                <ol class="card overflow-hidden">
                    @forelse ($makers as $i => $maker)
                        <li class="list-row" style="--inset: 6.5rem">
                            <a href="{{ $maker->profileUrl() }}" class="flex items-center gap-4 px-4 py-3.5 hover:bg-surface-2 sm:px-5">
                                <span class="w-6 shrink-0 text-right text-[0.9375rem] font-semibold tabular-nums {{ $i < 3 ? 'text-ink' : 'text-ink-2' }}">{{ $i + 1 }}</span>
                                <x-avatar :user="$maker" class="size-11 text-sm" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span class="truncate text-[0.9375rem] font-semibold">{{ $maker->name }}</span>
                                        @if ($maker->currentStreak() > 1)
                                            <span class="inline-flex items-center gap-0.5 text-xs font-medium text-ink-2" title="Daily streak"><x-icon name="flame" :size="13" class="text-[var(--accent)]" />{{ $maker->currentStreak() }}</span>
                                        @endif
                                    </span>
                                    <span class="block truncate text-sm text-ink-2">{{ '@'.$maker->username }} · {{ $maker->launches }} {{ Str::plural('launch', $maker->launches) }}</span>
                                </span>
                                <span class="text-right">
                                    <span class="block text-[0.9375rem] font-semibold tabular-nums">{{ number_format((int) $maker->earned) }}</span>
                                    <span class="block text-xs text-ink-2">upvotes</span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-6 py-14 text-center">
                            <p class="font-semibold">No launches {{ $period === 'month' ? 'this month' : '' }} yet</p>
                            <p class="mt-1 text-sm text-ink-2">Launch now and lead the board.</p>
                            <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-5">Launch your product</a>
                        </li>
                    @endforelse
                </ol>
            </div>
        </section>

        <aside class="space-y-8">
            <section data-reveal>
                <h2 class="group-header"><span>Top supporters</span><span class="text-[0.8125rem] font-normal text-ink-2">Upvotes given</span></h2>
                <ol class="card overflow-hidden">
                    @forelse ($supporters as $i => $user)
                        <li class="list-row" style="--inset: 3.75rem">
                            <a href="{{ $user->profileUrl() }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-2">
                                <x-avatar :user="$user" class="size-8 text-xs" />
                                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $user->name }}</span>
                                <span class="text-xs font-medium tabular-nums text-ink-2">{{ number_format($user->given) }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-ink-2">No upvotes yet.</li>
                    @endforelse
                </ol>
            </section>

            <section data-reveal>
                <h2 class="group-header"><span>Longest streaks</span><span class="text-[0.8125rem] font-normal text-ink-2">Days in a row</span></h2>
                <ol class="card overflow-hidden">
                    @forelse ($streaks as $user)
                        <li class="list-row" style="--inset: 3.75rem">
                            <a href="{{ $user->profileUrl() }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-2">
                                <x-avatar :user="$user" class="size-8 text-xs" />
                                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $user->name }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold tabular-nums"><x-icon name="flame" :size="14" class="text-[var(--accent)]" />{{ $user->streak_days }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-ink-2">Visit two days in a row to start a streak.</li>
                    @endforelse
                </ol>
                <p class="mt-2 px-1 text-xs text-ink-3">A streak counts each UTC day you visit while signed in.</p>
            </section>
        </aside>
    </div>
</x-layout>
