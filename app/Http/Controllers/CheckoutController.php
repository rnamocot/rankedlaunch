<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Services\LemonSqueezy;
use App\Services\Polar;
use App\Services\PlanActivator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /** Local-only stand-in for the payment page, so the flow can be tested without LemonSqueezy. */
    public function simulate(Request $request, Payment $payment)
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        abort_unless($payment->user_id === $request->user()->id, 403);

        return view('checkout-simulate', ['payment' => $payment->load('product')]);
    }

    public function simulateComplete(Request $request, Payment $payment, PlanActivator $activator)
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->status !== 'paid') {
            $payment->update(['status' => 'paid', 'provider' => 'simulated']);
            $activator->activate($payment->product, $payment);
        }

        return redirect()->route('launch.hub', $payment->product)->with('status', 'Payment received. Your launch is booked.');
    }

    public function webhook(Request $request, LemonSqueezy $lemon, PlanActivator $activator)
    {
        $payload = $request->getContent();

        if (! $lemon->validSignature($payload, $request->header('X-Signature'))) {
            return response('Invalid signature', 401);
        }

        $event = $request->json('meta.event_name');
        $custom = $request->json('meta.custom_data', []);

        if ($event !== 'order_created') {
            return response('Ignored');
        }

        $payment = Payment::with('product')->find($custom['payment_id'] ?? null);

        if (! $payment || ! $payment->product) {
            Log::warning('LemonSqueezy webhook: payment not found', ['custom' => $custom]);

            return response('Payment not found', 200);
        }

        if ($payment->status === 'paid') {
            return response('Already processed');
        }

        if ($request->json('data.attributes.status') !== 'paid') {
            return response('Not paid');
        }

        $this->markPaid($payment, $activator, [
            'provider' => 'lemonsqueezy',
            'provider_order_id' => (string) $request->json('data.id'),
            'amount' => (int) $request->json('data.attributes.total', $payment->amount),
            'currency' => $request->json('data.attributes.currency', 'USD'),
            'payload' => $request->json()->all(),
        ]);

        return response('OK');
    }

    public function polarWebhook(Request $request, Polar $polar, PlanActivator $activator)
    {
        $payload = $request->getContent();

        if (! $polar->validSignature($payload, $request->header('webhook-id'), $request->header('webhook-timestamp'), $request->header('webhook-signature'))) {
            return response('Invalid signature', 401);
        }

        if ($request->json('type') !== 'order.paid') {
            return response('Ignored', 202);
        }

        $payment = Payment::with('product')->find($request->json('data.metadata.payment_id'));

        if (! $payment || ! $payment->product) {
            Log::warning('Polar webhook: payment not found', ['metadata' => $request->json('data.metadata')]);

            return response('Payment not found', 202);
        }

        $this->adoptCheckoutEmail($payment, $request->json('data.customer.email') ?? $request->json('data.customer_email'));

        if ($payment->status !== 'paid') {
            $this->markPaid($payment, $activator, [
                'provider' => 'polar',
                'provider_order_id' => (string) $request->json('data.id'),
                'amount' => (int) ($request->json('data.total_amount') ?? $request->json('data.amount') ?? $payment->amount),
                'currency' => strtoupper((string) $request->json('data.currency', 'usd')),
                'payload' => $request->json()->all(),
            ]);
        }

        return response('OK', 202);
    }

    /**
     * X sign-ins may only have a placeholder email. If they typed a real one at
     * checkout, save it so launch emails reach them (unless another account uses it).
     */
    private function adoptCheckoutEmail(Payment $payment, ?string $email): void
    {
        $user = $payment->user;

        if (! $user || $user->deliverableEmail() || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (! User::where('email', $email)->exists()) {
            $user->forceFill(['email' => $email])->save();
        }
    }

    private function markPaid(Payment $payment, PlanActivator $activator, array $attributes): void
    {
        $payment->update(['status' => 'paid'] + $attributes);
        $activator->activate($payment->product, $payment);
    }
}
