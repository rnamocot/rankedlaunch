<x-admin.shell title="Products" :subtitle="$products->total().' '.Str::plural('product', $products->total())">
    @php
        $tabs = ['' => 'All', 'pending' => 'Review', 'scheduled' => 'Scheduled', 'live' => 'Live', 'awaiting_payment' => 'Awaiting payment', 'draft' => 'Drafts', 'rejected' => 'Rejected'];
    @endphp

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <label class="sr-only" for="q">Search products</label>
        <input id="q" name="q" value="{{ $q }}" placeholder="Search name, website or tagline" class="input !h-10 max-w-sm flex-1">
        <select name="plan" class="input !h-10 !w-auto" onchange="this.form.submit()">
            <option value="">All plans</option>
            @foreach (config('rankedlaunch.plans') as $key => $p)
                <option value="{{ $key }}" @selected($plan === $key)>{{ $p['name'] }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary">Search</button>
    </form>

    <nav class="segmented mt-5 max-w-full overflow-x-auto" aria-label="Status">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.products.index', array_filter(['status' => $key, 'q' => $q, 'plan' => $plan])) }}" @if((string) $status === $key) aria-current="page" @endif>
                {{ $label }}@if($key && in_array($key, \App\Http\Controllers\Admin\ProductController::STATUSES) && ($counts[$key] ?? 0)) <span class="tabular-nums text-ink-2">{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </nav>

    <ul class="card mt-5 overflow-hidden">
        @forelse ($products as $product)
            <li class="list-row" style="--inset: 4.5rem">
                <div class="flex flex-col gap-3 px-4 py-3.5 md:flex-row md:items-center">
                    <a href="{{ route('admin.products.edit', $product) }}" class="flex min-w-0 flex-1 items-center gap-4">
                        <span class="app-icon size-10"><img src="{{ $product->logoUrl() }}" alt="" loading="lazy"></span>
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{ $product->name }}</span>
                            <span class="meta flex flex-wrap">
                                <span class="font-medium text-ink">{{ $product->statusLabel() }}</span>
                                <span>{{ config("rankedlaunch.plans.{$product->plan}.name") }}</span>
                                <span>{{ $product->launch_date?->format('M j, Y') ?? 'No date' }}</span>
                                <span>{{ $product->votes_count }} votes</span>
                                <span class="truncate">{{ $product->user?->email }}</span>
                                @if ($product->isFeatured())<span>Featured</span>@endif
                            </span>
                        </span>
                    </a>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($product->status === 'pending')
                            <form method="POST" action="{{ route('admin.approve', $product) }}">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
                            <form method="POST" action="{{ route('admin.reject', $product) }}" class="flex gap-2">
                                @csrf
                                <input name="reason" placeholder="Reason" aria-label="Rejection reason" class="input !h-8 w-32 !text-xs">
                                <button class="btn btn-secondary btn-sm">Reject</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline btn-sm">Manage</a>
                    </div>
                </div>
            </li>
        @empty
            <li class="px-6 py-14 text-center text-sm text-ink-2">No products match.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $products->links() }}</div>
</x-admin.shell>
