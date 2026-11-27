<x-layout title="Test checkout" :noindex="true">
    <div class="mx-auto max-w-sm px-4 pt-16">
        <div class="card p-6 text-center">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-3">Local test mode</p>
            <h1 class="title-2 mt-3">{{ config("rankedlaunch.plans.{$payment->plan}.name") }}</h1>
            <p class="mt-1 text-sm text-ink-2">{{ $payment->product->name }} · launches {{ $payment->product->requested_launch_date?->format('M j, Y') }}</p>
            <p class="mt-6 text-4xl font-bold tracking-[-0.04em]">${{ $payment->amount / 100 }}</p>
            <p class="mt-6 text-xs leading-relaxed text-ink-2">Payments aren’t set up yet (Polar or LemonSqueezy keys in .env), so this page stands in for the real checkout. It only works on your computer (APP_ENV=local).</p>
            <form method="POST" action="{{ route('checkout.simulate', $payment) }}" class="mt-6">
                @csrf
                <button class="btn btn-primary btn-lg w-full">Simulate successful payment</button>
            </form>
            <a href="{{ route('submit.plan', $payment->product) }}" class="btn btn-secondary mt-2 w-full">Cancel</a>
        </div>
    </div>
</x-layout>
