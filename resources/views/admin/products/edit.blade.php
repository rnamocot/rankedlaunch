<x-admin.shell :title="$product->name" :subtitle="$product->statusLabel().' · '.config('rankedlaunch.plans.'.$product->plan.'.name').' · by '.($product->user?->name ?? 'deleted user')">
    <x-slot:actions>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('product', $product) }}" target="_blank" class="btn btn-outline btn-sm">Public page <x-icon name="arrow-up-right" :size="14" :stroke="2" /></a>
            <a href="{{ route('launch.hub', $product) }}" class="btn btn-outline btn-sm">Maker’s hub</a>
            @if ($product->status !== 'approved')
                <form method="POST" action="{{ route('admin.approve', $product) }}">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
            @endif
        </div>
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
        {{-- Edit form --}}
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-8">
            @csrf @method('PUT')

            <section>
                <h2 class="group-header">Listing</h2>
                <div class="card space-y-5 p-5">
                    <div class="flex items-center gap-4">
                        <span class="app-icon size-16"><img src="{{ $product->logoUrl() }}" alt=""></span>
                        <div class="min-w-0 flex-1">
                            <label for="logo" class="field-label">Replace logo</label>
                            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="w-full max-w-full text-sm text-ink-2 file:mr-3 file:rounded-lg file:border-0 file:bg-fill file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-ink">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div><label class="field-label" for="name">Name</label><input id="name" name="name" value="{{ old('name', $product->name) }}" class="input" required></div>
                        <div><label class="field-label" for="slug">URL slug</label><input id="slug" name="slug" value="{{ old('slug', $product->slug) }}" class="input" required><p class="field-hint">rankedlaunch.com/apps/{{ $product->slug }}</p></div>
                        <div class="sm:col-span-2"><label class="field-label" for="url">Website</label><input id="url" name="url" type="url" value="{{ old('url', $product->url) }}" class="input" required></div>
                        <div class="sm:col-span-2"><label class="field-label" for="tagline">Tagline</label><input id="tagline" name="tagline" value="{{ old('tagline', $product->tagline) }}" class="input" required></div>
                        <div class="sm:col-span-2"><label class="field-label" for="description">Description</label><textarea id="description" name="description" rows="6" class="input" required>{{ old('description', $product->description) }}</textarea></div>
                        <div>
                            <label class="field-label" for="category_id">Category</label>
                            <x-category-select id="category_id" :categories="$categories" :value="$product->category_id" placeholder="None" :placeholder-selectable="true" />
                        </div>
                        <div>
                            <label class="field-label" for="pricing_type">Pricing model</label>
                            <select id="pricing_type" name="pricing_type" class="input">
                                @foreach (\App\Models\Product::PRICING_TYPES as $k => $l)<option value="{{ $k }}" @selected($product->pricing_type === $k)>{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="group-header">Launch</h2>
                <div class="card grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="status">Status</label>
                        <select id="status" name="status" class="input">
                            @foreach (\App\Http\Controllers\Admin\ProductController::STATUSES as $s)<option value="{{ $s }}" @selected($product->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="plan">Plan</label>
                        <select id="plan" name="plan" class="input">
                            @foreach (config('rankedlaunch.plans') as $k => $p)<option value="{{ $k }}" @selected($product->plan === $k)>{{ $p['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="field-label" for="launch_date">Launch date</label><input id="launch_date" name="launch_date" type="date" value="{{ old('launch_date', $product->launch_date?->toDateString()) }}" class="input"></div>
                    <div><label class="field-label" for="featured_until">Featured until</label><input id="featured_until" name="featured_until" type="date" value="{{ old('featured_until', $product->featured_until?->toDateString()) }}" class="input"><p class="field-hint">Empty = not featured.</p></div>
                    <label class="flex items-center gap-3 sm:col-span-2">
                        <input type="hidden" name="is_dofollow" value="0">
                        <input type="checkbox" name="is_dofollow" value="1" @checked($product->is_dofollow) class="size-4 accent-[var(--ink)]">
                        <span class="text-sm font-medium">Dofollow backlink</span>
                    </label>
                    <div class="sm:col-span-2"><label class="field-label" for="rejection_reason">Rejection reason</label><input id="rejection_reason" name="rejection_reason" value="{{ old('rejection_reason', $product->rejection_reason) }}" class="input" placeholder="Shown to the maker if rejected"></div>
                </div>
            </section>

            <div class="flex justify-end"><button class="btn btn-primary btn-lg">Save changes</button></div>
        </form>

        {{-- Side panel --}}
        <aside class="space-y-8">
            <section>
                <h2 class="group-header">Performance</h2>
                <dl class="card overflow-hidden text-sm">
                    @foreach ([
                        'Votes' => number_format($product->votes_count),
                        'Votes from new accounts' => $newAccountVotes,
                        'Page views' => number_format($product->views_count),
                        'Clicks to site' => number_format($product->clicks_count),
                        'Rank that day' => $product->dailyRank() ? '#'.$product->dailyRank() : '—',
                    ] as $l => $v)
                        <div class="list-row flex justify-between px-4 py-2.5"><dt class="text-ink-2">{{ $l }}</dt><dd class="font-semibold tabular-nums">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                <form method="POST" action="{{ route('admin.products.recount', $product) }}" class="mt-2">@csrf<button class="btn btn-secondary btn-sm w-full">Recount votes</button></form>
            </section>

            <section>
                <h2 class="group-header">Quick actions</h2>
                <div class="card space-y-3 p-4">
                    <form method="POST" action="{{ route('admin.feature', $product) }}">@csrf<button class="btn btn-outline btn-sm w-full">{{ $product->isFeatured() ? 'Unfeature' : 'Feature for 7 days' }}</button></form>
                    <form method="POST" action="{{ route('admin.reject', $product) }}" class="flex gap-2">
                        @csrf
                        <input name="reason" placeholder="Reject reason" aria-label="Rejection reason" class="input !h-8 flex-1 !text-xs">
                        <button class="btn btn-secondary btn-sm">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('admin.products.email', $product) }}" class="flex gap-2">
                        @csrf
                        <select name="type" aria-label="Email type" class="input !h-8 flex-1 !text-xs">
                            @foreach (\App\Mail\LaunchEmail::TYPES as $t)<option value="{{ $t }}">{{ ucfirst($t) }} email</option>@endforeach
                        </select>
                        <button class="btn btn-secondary btn-sm">Send</button>
                    </form>
                </div>
            </section>

            <section>
                <h2 class="group-header">Maker</h2>
                @if ($product->user)
                    <a href="{{ route('admin.users.show', $product->user) }}" class="card card-hover block p-4 text-sm">
                        <p class="font-semibold">{{ $product->user->name }}</p>
                        <p class="text-ink-2">{{ $product->user->email }}</p>
                    </a>
                @endif
            </section>

            @if ($product->payments->isNotEmpty())
                <section>
                    <h2 class="group-header">Payments</h2>
                    <ul class="card overflow-hidden text-sm">
                        @foreach ($product->payments as $pay)
                            <li class="list-row flex justify-between px-4 py-2.5"><span>{{ \App\Services\Pricing::dollars($pay->amount) }} · {{ $pay->plan }}</span><span class="text-ink-2">{{ $pay->status }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section>
                <h2 class="group-header">History</h2>
                <ul class="card overflow-hidden text-xs">
                    @forelse ($activity as $log)
                        <li class="list-row px-4 py-2.5"><span class="text-ink-2">{{ $log->created_at->format('M j, H:i') }}</span><br>{{ $log->message }}</li>
                    @empty
                        <li class="px-4 py-4 text-ink-2">No history yet.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="group-header">Danger zone</h2>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="card space-y-3 p-4">
                    @csrf @method('DELETE')
                    <label class="flex items-start gap-2 text-xs text-ink-2"><input type="checkbox" name="confirm" value="1" class="mt-0.5 accent-[var(--ink)]"> Permanently delete this product, its votes and images. This can’t be undone.</label>
                    <button class="btn btn-secondary btn-sm w-full !text-danger">Delete product</button>
                </form>
            </section>
        </aside>
    </div>
</x-admin.shell>
