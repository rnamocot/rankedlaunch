@props(['title', 'subtitle' => null])
@php
    $pendingCount = \App\Models\Product::where('status', 'pending')->count();
    $nav = [
        ['Overview', 'admin.index', 'chart', null],
        ['Products', 'admin.products.*', 'grid', $pendingCount ?: null, 'admin.products.index'],
        ['Users', 'admin.users.*', 'users', null, 'admin.users.index'],
        ['Payments', 'admin.payments*', 'medal', null, 'admin.payments'],
        ['Votes', 'admin.votes', 'chevron-up', null],
        ['Subscribers', 'admin.subscribers*', 'send', null, 'admin.subscribers.index'],
        ['Categories', 'admin.categories*', 'pin', null, 'admin.categories'],
        ['Chat', 'admin.chat*', 'send', null, 'admin.chat'],
        ['Activity', 'admin.activity', 'clock', null],
        ['Settings', 'admin.settings*', 'code', null, 'admin.settings'],
        ['System', 'admin.system*', 'shield', null, 'admin.system'],
    ];
@endphp
<x-layout :title="'Admin · '.$title" :noindex="true">
    <div class="mx-auto grid grid-cols-1 max-w-7xl gap-8 px-4 pt-8 sm:px-6 lg:grid-cols-[13rem_minmax(0,1fr)]">
        {{-- Sidebar (desktop) / scrollable tabs (mobile) --}}
        <aside class="lg:sticky lg:top-20 lg:self-start">
            <p class="mb-3 hidden px-3 text-xs font-semibold text-ink-2 lg:block">Admin</p>
            <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-1 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0" aria-label="Admin">
                @foreach ($nav as $item)
                    @php([$label, $pattern, $icon, $badge] = $item)
                    @php($active = request()->routeIs($pattern))
                    <a href="{{ route($item[4] ?? $pattern) }}" @if($active) aria-current="page" @endif
                       class="flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-surface text-ink shadow-[inset_0_0_0_1px_var(--line)]' : 'text-ink-2 hover:bg-fill hover:text-ink' }}">
                        <x-icon :name="$icon" :size="16" />
                        <span>{{ $label }}</span>
                        @if ($badge)
                            <span class="ml-auto min-w-5 rounded-full bg-ink px-1.5 text-center text-[0.6875rem] font-semibold leading-5 text-on-ink tabular-nums">{{ $badge }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 pb-10">
            <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-[1.875rem] font-bold leading-tight tracking-[-0.035em]">{{ $title }}</h1>
                    @if ($subtitle)<p class="mt-1 text-sm text-ink-2">{{ $subtitle }}</p>@endif
                </div>
                {{ $actions ?? '' }}
            </header>

            @if ($errors->any())
                <div class="card mb-6 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
            @endif

            {{ $slot }}
        </div>
    </div>
</x-layout>
