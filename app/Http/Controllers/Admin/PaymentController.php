<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Services\Pricing;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $payments = Payment::with('user', 'product')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $totals = [
            'paid' => (int) Payment::where('status', 'paid')->sum('amount'),
            'refunded' => (int) Payment::where('status', 'refunded')->sum('amount'),
            'promo' => Payment::where('status', 'paid')->where('is_promo', true)->count(),
            'pending' => Payment::where('status', 'pending')->count(),
        ];

        return view('admin.payments', compact('payments', 'status', 'totals'));
    }

    /**
     * Records a refund here. The money itself is returned in Polar / LemonSqueezy.
     * Optionally moves the launch back to the free queue.
     */
    public function refund(Request $request, Payment $payment)
    {
        abort_unless($payment->status === 'paid', 422, 'Only paid payments can be refunded.');

        $payment->update(['status' => 'refunded', 'refunded_at' => now()]);

        $product = $payment->product;
        if ($product && $request->boolean('downgrade') && ! $product->isLive()) {
            $product->update(['plan' => 'free', 'is_dofollow' => false, 'featured_until' => null]);
        }

        ActivityLog::record('refund', 'Refunded '.Pricing::dollars($payment->amount).' to '.($payment->user?->name ?? 'user').($product ? " for {$product->name}" : ''), $product, $payment->user);

        return back()->with('status', 'Marked as refunded. Remember to issue the refund in your payment dashboard.');
    }
}
