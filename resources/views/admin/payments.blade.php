<x-admin.shell title="Payments" subtitle="Every checkout, paid or not. Refunds are issued in your payment dashboard, then recorded here.">
    <dl class="grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach (['Collected' => \App\Services\Pricing::dollars($totals['paid']), 'Refunded' => \App\Services\Pricing::dollars($totals['refunded']), 'Launch-offer sales' => $totals['promo'], 'Unfinished checkouts' => $totals['pending']] as $l => $v)
            <div class="card p-4"><dt class="text-xs text-ink-2">{{ $l }}</dt><dd class="mt-1 text-xl font-bold tabular-nums">{{ $v }}</dd></div>
        @endforeach
    </dl>

    <nav class="segmented mt-8" aria-label="Status">
        @foreach (['' => 'All', 'paid' => 'Paid', 'pending' => 'Unfinished', 'refunded' => 'Refunded'] as $key => $label)
            <a href="{{ route('admin.payments', array_filter(['status' => $key])) }}" @if((string) $status === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="card mt-5 overflow-x-auto">
        <table class="w-full min-w-[44rem] text-sm">
            <thead>
                <tr class="text-left text-xs text-ink-2">
                    <th class="px-4 py-3 font-medium">Date</th><th class="px-4 py-3 font-medium">Product</th><th class="px-4 py-3 font-medium">Maker</th>
                    <th class="px-4 py-3 font-medium">Plan</th><th class="px-4 py-3 text-right font-medium">Amount</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $pay)
                    <tr class="border-t border-line align-top">
                        <td class="px-4 py-3 whitespace-nowrap text-ink-2 tabular-nums">{{ $pay->created_at->format('M j, H:i') }}</td>
                        <td class="px-4 py-3">@if($pay->product)<a href="{{ route('admin.products.edit', $pay->product) }}" class="font-medium hover:underline">{{ $pay->product->name }}</a>@else <span class="text-ink-2">Deleted</span> @endif</td>
                        <td class="px-4 py-3">@if($pay->user)<a href="{{ route('admin.users.show', $pay->user) }}" class="hover:underline">{{ $pay->user->email }}</a>@endif</td>
                        <td class="px-4 py-3">{{ config("rankedlaunch.plans.{$pay->plan}.name") }}{{ $pay->is_promo ? ' · offer' : '' }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ \App\Services\Pricing::dollars($pay->amount) }}</td>
                        <td class="px-4 py-3"><span class="{{ $pay->status === 'paid' ? 'font-medium' : 'text-ink-2' }}">{{ ucfirst($pay->status) }}</span><br><span class="text-xs text-ink-2">{{ $pay->provider }}{{ $pay->provider_order_id ? ' · '.$pay->provider_order_id : '' }}</span></td>
                        <td class="px-4 py-3 text-right">
                            @if ($pay->status === 'paid')
                                <form method="POST" action="{{ route('admin.payments.refund', $pay) }}" class="flex flex-col items-end gap-1.5">
                                    @csrf
                                    <label class="flex items-center gap-1.5 text-xs text-ink-2 whitespace-nowrap"><input type="checkbox" name="downgrade" value="1" checked class="accent-[var(--ink)]"> Switch to Free plan</label>
                                    <button class="btn btn-outline btn-sm">Mark refunded</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-ink-2">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
</x-admin.shell>
