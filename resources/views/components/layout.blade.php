@props([
    'title' => null,
    'description' => config('rankedlaunch.tagline').' A daily ranked launch board for indie makers and startups.',
    'image' => null,
    'noindex' => false,
])
@php
    // Keep titles under ~60 characters so Google doesn't cut them off.
    $fullTitle = match (true) {
        ! $title => 'RankedLaunch · Launch your product. Climb the ranks.',
        mb_strlen($title) + 15 <= 60 => "$title · RankedLaunch",
        default => $title,
    };
    $nav = [
        ['Today', route('home'), request()->routeIs('home')],
        ['Upcoming', route('upcoming'), request()->routeIs('upcoming')],
        ['Discover', route('discover'), request()->routeIs('discover', 'alternatives', 'category')],
        ['Rankings', route('rankings'), request()->routeIs('rankings')],
        ['Makers', route('leaderboard'), request()->routeIs('leaderboard')],
        ['Launch', route('launch'), request()->routeIs('launch')],
    ];
    if ($paymentsAvailable) {
        $nav[] = ['Pricing', route('pricing'), request()->routeIs('pricing')];
    }
    $me = auth()->user();
    $unreadCount = $me ? $me->unreadNotifications()->count() : 0;
    $recentNotifications = $me ? $me->notifications()->limit(6)->get() : collect();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}{{ request()->integer('page') > 1 ? '?page='.request()->integer('page') : '' }}">
    <link rel="alternate" type="application/rss+xml" title="RankedLaunch launches" href="{{ route('feed') }}">
    <link rel="alternate" type="text/plain" title="LLM guide" href="{{ url('/llms.txt') }}">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    @if ($noindex)<meta name="robots" content="noindex">@endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="RankedLaunch">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $image ?? route('og.site') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@rankedlaunch">

    <meta name="theme-color" content="#f2f2f7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <script>document.documentElement.classList.add('js')</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($plausible = config('rankedlaunch.analytics.plausible_domain'))
        <script defer data-domain="{{ $plausible }}" src="https://plausible.io/js/script.outbound-links.js"></script>
    @elseif (config('rankedlaunch.analytics.umami_src'))
        <script defer src="{{ config('rankedlaunch.analytics.umami_src') }}" data-website-id="{{ config('rankedlaunch.analytics.umami_website_id') }}"></script>
    @endif
    {{ $head ?? '' }}
</head>
<body class="min-h-screen font-sans" @auth data-user-tz="{{ auth()->user()->timezone }}" data-tz-url="{{ route('account.timezone') }}" @endauth>
    @if ($pricing->promoActive() && ! request()->routeIs('checkout*', 'admin*'))
        <x-promo-marquee :pricing="$pricing" />
    @endif
    <header class="nav-bar sticky top-0 z-40">
        <div class="mx-auto grid h-15 max-w-6xl grid-cols-[1fr_auto_1fr] items-center gap-6 px-4 sm:px-6">
            <a href="{{ route('home') }}" class="justify-self-start text-ink" aria-label="RankedLaunch home">
                <x-logo />
            </a>

            <nav class="hidden items-center gap-0.5 xl:flex">
                @foreach ($nav as [$label, $href, $active])
                    <a href="{{ $href }}" @if($active) aria-current="page" @endif
                       class="whitespace-nowrap rounded-full px-2.5 py-1.5 text-sm font-medium {{ $active ? 'text-ink' : 'text-ink-2 hover:text-ink' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="col-start-3 flex items-center justify-end gap-3">
                <a href="{{ route('discover') }}" data-search-open class="grid size-9 place-items-center rounded-full text-ink-2 transition-colors hover:bg-fill hover:text-ink" aria-label="Search" title="Search (Ctrl+K)">
                    <x-icon name="search" :size="18" />
                </a>
                @auth
                    <div class="relative" data-popover>
                        <button type="button" class="relative grid size-9 place-items-center rounded-full text-ink-2 transition-colors hover:bg-fill hover:text-ink" data-popover-toggle data-read-url="{{ $unreadCount ? route('notifications.read') : '' }}" aria-expanded="false" aria-label="Notifications{{ $unreadCount ? ' ('.$unreadCount.' unread)' : '' }}">
                            <x-icon name="bell" :size="18" />
                            @if ($unreadCount)
                                <span data-unread class="absolute top-1 right-1 grid h-4 min-w-4 place-items-center rounded-full bg-[var(--accent)] px-1 text-[0.625rem] font-bold leading-none text-white tabular-nums">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </button>
                        <div hidden data-popover-panel class="card absolute top-11 -right-2 z-50 w-[min(22rem,calc(100vw-2rem))] overflow-hidden !shadow-xl">
                            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                                <span class="text-sm font-semibold">Notifications</span>
                                <a href="{{ route('notifications') }}" class="text-xs font-medium text-ink-2 hover:text-ink">See all</a>
                            </div>
                            @forelse ($recentNotifications as $n)
                                @include('partials.notification', ['n' => $n, 'compact' => true])
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-ink-2">Nothing yet. Replies, comments and upvotes on your launches show up here.</p>
                            @endforelse
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="hidden items-center sm:flex" aria-label="Your dashboard">
                        @if (auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="" class="size-8 rounded-full outline outline-1 -outline-offset-1 outline-line">
                        @else
                            <span class="grid size-8 place-items-center rounded-full bg-fill text-xs font-semibold">{{ auth()->user()->initials() }}</span>
                        @endif
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden whitespace-nowrap text-sm font-medium text-ink hover:text-ink-2 sm:inline">Sign in</a>
                @endauth
                <a href="{{ route('submit') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">Launch your product</a>
                <button type="button" class="grid size-9 place-items-center rounded-full text-ink xl:hidden" data-menu-toggle="mobile-menu" aria-expanded="false" aria-label="Menu">
                    <x-icon name="menu" :size="20" />
                </button>
            </div>
        </div>

        <div id="mobile-menu" hidden class="border-t border-line px-4 pb-4 xl:hidden">
            <form method="GET" action="{{ route('discover') }}" class="relative pt-4" role="search">
                <x-icon name="search" :size="16" class="pointer-events-none absolute top-[1.625rem] left-3.5 text-ink-2" />
                <label for="mobile-search" class="sr-only">Search tools</label>
                <input id="mobile-search" name="q" placeholder="Search tools" class="input !h-10 !pl-10">
            </form>
            <nav class="flex flex-col py-2">
                @foreach ($nav as [$label, $href, $active])
                    <a href="{{ $href }}" class="py-2.5 text-[0.9375rem] font-medium {{ $active ? 'text-ink' : 'text-ink-2' }}">{{ $label }}</a>
                @endforeach
                @auth
                    <a href="{{ route('dashboard') }}" class="py-2.5 text-[0.9375rem] font-medium text-ink-2">Dashboard</a>
                    <a href="{{ route('notifications') }}" class="py-2.5 text-[0.9375rem] font-medium text-ink-2">Notifications @if ($unreadCount)<span class="tabular-nums text-[var(--accent)]">{{ $unreadCount }}</span>@endif</a>
                @endauth
            </nav>
            <div class="flex gap-2">
                <a href="{{ route('submit') }}" class="btn btn-primary flex-1">Submit your product</a>
                @guest<a href="{{ route('login') }}" class="btn btn-secondary">Sign in</a>@endguest
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto max-w-6xl px-4 pt-5 sm:px-6">
            <div class="card flex items-center gap-3 px-4 py-3 text-sm font-medium">
                <x-icon name="check" :size="18" :stroke="2" />
                {{ session('status') }}
            </div>
        </div>
    @endif

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-line">
        <div class="mx-auto grid grid-cols-1 max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <x-logo />
                <p class="mt-3 max-w-xs text-sm text-ink-2">A daily ranked launch board for indie makers. Get seen, get votes, get a backlink that lasts.</p>
            </div>
            <div>
                <p class="text-sm font-semibold">Discover</p>
                <ul class="mt-3 space-y-2 text-sm text-ink-2">
                    <li><a class="hover:text-ink" href="{{ route('home') }}">Today’s launches</a></li>
                    <li><a class="hover:text-ink" href="{{ route('upcoming') }}">Upcoming launches</a></li>
                    <li><a class="hover:text-ink" href="{{ route('leaderboard') }}">Maker leaderboard</a></li>
                    <li><a class="hover:text-ink" href="{{ route('discover') }}">Discover tools</a></li>
                    <li><a class="hover:text-ink" href="{{ route('rankings', 'week') }}">Top this week</a></li>
                    <li><a class="hover:text-ink" href="{{ route('rankings', 'month') }}">Top this month</a></li>
                    <li><a class="hover:text-ink" href="{{ route('rankings', 'all-time') }}">All time</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold">Makers</p>
                <ul class="mt-3 space-y-2 text-sm text-ink-2">
                    <li><a class="hover:text-ink" href="{{ route('launch') }}">Why launch here</a></li>
                    <li><a class="hover:text-ink" href="{{ route('how-it-works') }}">How launches work</a></li>
                    <li><a class="hover:text-ink" href="{{ route('submit') }}">Submit a product</a></li>
                    <li><a class="hover:text-ink" href="{{ route('pricing') }}">Pricing</a></li>
                    <li><a class="hover:text-ink" href="{{ route('dashboard') }}">Dashboard</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold">Follow</p>
                <ul class="mt-3 space-y-2 text-sm text-ink-2">
                    <li><a class="inline-flex items-center gap-2 hover:text-ink" href="https://x.com/rankedlaunch" rel="noopener" target="_blank"><x-brand name="x" :size="14" /> @rankedlaunch</a></li>
                    <li><a class="hover:text-ink" href="{{ route('feed') }}">RSS feed</a></li>
                    <li><a class="hover:text-ink" href="{{ route('about') }}">About</a></li>
                </ul>
            </div>
        </div>
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-5 gap-y-2 px-4 pb-10 text-xs text-ink-2 sm:px-6">
            <span>© {{ date('Y') }} RankedLaunch</span>
            <a class="hover:text-ink" href="{{ route('terms') }}">Terms</a>
            <a class="hover:text-ink" href="{{ route('privacy') }}">Privacy</a>
            <a class="hover:text-ink" href="{{ route('refunds') }}">Refunds</a>
            <a class="hover:text-ink" href="mailto:{{ config('rankedlaunch.contact_email') }}">Contact</a>
        </div>
    </footer>
    <x-search-palette />
    @if (config('rankedlaunch.chat_enabled') && ! request()->routeIs('admin*', 'checkout*') && request()->route())
        <x-chat-widget />
    @endif
</body>
</html>
