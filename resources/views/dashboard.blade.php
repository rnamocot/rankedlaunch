<x-layout title="Dashboard" :noindex="true">
    <div class="mx-auto max-w-3xl px-4 pt-12 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-ink-2">Signed in as {{ auth()->user()->name }}</p>
                <h1 class="large-title mt-2">Your launches</h1>
            </div>
            <a href="{{ route('submit') }}" class="btn btn-primary"><x-icon name="plus" :size="16" :stroke="2.25" /> New launch</a>
        </div>

        @if ($justPaid)
            <div class="card mt-8 flex items-center gap-3 px-4 py-3 text-sm">
                <x-icon name="check" :size="18" :stroke="2" />
                Thanks! Your payment is being confirmed. Your launch updates here within a minute.
            </div>
        @endif

        <section class="mt-10">
            @if ($products->isNotEmpty())
                <ul class="card overflow-hidden">
                    @foreach ($products as $product)
                        <li class="list-row" style="--inset: 5rem">
                            <div class="flex items-center gap-4 px-4 py-4 sm:px-5">
                                <span class="app-icon size-12"><img src="{{ $product->logoUrl() }}" alt=""></span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('launch.hub', $product) }}" class="truncate font-semibold hover:underline">{{ $product->name }}</a>
                                    <p class="meta mt-0.5 flex flex-wrap">
                                        <span class="font-medium text-ink">{{ $product->statusLabel() }}</span>
                                        <span>{{ config("rankedlaunch.plans.{$product->plan}.name") }}</span>
                                        @if ($product->launch_date)<span>{{ $product->launch_date->format('M j, Y') }}</span>@endif
                                        @if ($product->isLive())<span>{{ $product->votes_count }} votes</span>@endif
                                    </p>
                                    @if ($product->status === 'rejected' && $product->rejection_reason)
                                        <p class="mt-1 text-xs text-danger">{{ $product->rejection_reason }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @if (in_array($product->status, ['draft', 'awaiting_payment']))
                                        <a href="{{ route('submit.plan', $product) }}" class="btn btn-primary btn-sm">Finish</a>
                                    @elseif ($product->status === 'pending' && $product->plan === 'free')
                                        <a href="{{ route('submit.plan', $product) }}" class="btn btn-secondary btn-sm">Skip queue</a>
                                    @endif
                                    <a href="{{ route('launch.hub', $product) }}" class="btn btn-outline btn-sm">Manage</a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="card px-6 py-16 text-center">
                    <x-icon name="chart" :size="28" class="mx-auto text-ink-3" />
                    <p class="mt-4 font-semibold">No launches yet</p>
                    <p class="mt-1 text-sm text-ink-2">Submit your product and pick a launch date.</p>
                    <a href="{{ route('submit') }}" class="btn btn-primary btn-sm mt-6">Submit your product</a>
                </div>
            @endif
        </section>

        <div class="mt-10 flex flex-wrap gap-3">
            @if (auth()->user()->is_admin)
                <a href="{{ route('admin.index') }}" class="btn btn-outline btn-sm"><x-icon name="shield" :size="15" /> Admin</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-secondary btn-sm"><x-icon name="logout" :size="15" /> Sign out</button>
            </form>
        </div>
    </div>
</x-layout>
