<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Services\LaunchScheduler;
use App\Services\MakerMailer;
use App\Services\Payments;
use App\Services\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SubmitController extends Controller
{
    public function create()
    {
        return view('submit.create', [
            'product' => new Product(['pricing_type' => 'freemium']),
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorizeOwner($request, $product);
        abort_if($product->isLive(), 403, 'Live products can no longer be edited.');

        return view('submit.create', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, logoRequired: true);

        $product = new Product($data);
        $product->user_id = $request->user()->id;
        $product->slug = Product::uniqueSlug($data['name']);
        $product->status = 'draft';
        $this->storeFiles($request, $product);
        $product->save();
        \App\Models\ActivityLog::record('submission', "{$product->name} was submitted", $product);

        return redirect()->route('submit.plan', $product);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($request, $product);
        abort_if($product->isLive(), 403);

        $product->fill($this->validated($request, logoRequired: false));
        $this->storeFiles($request, $product);
        $product->save();

        return $product->status === 'draft'
            ? redirect()->route('submit.plan', $product)
            : redirect()->route('dashboard')->with('status', 'Changes saved.');
    }

    public function plan(Request $request, Product $product, LaunchScheduler $scheduler, Pricing $pricing)
    {
        $this->authorizeOwner($request, $product);
        abort_unless(in_array($product->status, ['draft', 'awaiting_payment', 'pending']), 403);

        // Spots left per day for each plan, so the picker can grey out full days.
        $dates = collect(array_keys(config('rankedlaunch.plans')))
            ->mapWithKeys(fn ($plan) => [$plan => collect($scheduler->dateOptions($plan, $product))->pluck('left', 'date')->mapWithKeys(fn ($left, $date) => [substr($date, 0, 10) => $left])])
            ->all();

        return view('submit.plan', [
            'product' => $product,
            'plans' => config('rankedlaunch.plans'),
            'days' => $scheduler->dateOptions('free', $product),
            'dates' => $dates,
            'pricing' => $pricing,
        ]);
    }

    public function choosePlan(Request $request, Product $product, LaunchScheduler $scheduler, Payments $payments, Pricing $pricing)
    {
        $this->authorizeOwner($request, $product);
        abort_unless(in_array($product->status, ['draft', 'awaiting_payment', 'pending']), 403);

        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('rankedlaunch.plans')))],
            'when' => ['nullable', Rule::in(['now', 'date'])],
            'launch_date' => ['required_if:when,date', 'nullable', 'date'],
        ]);

        // "List it now" = today's board. Otherwise the date they picked. Free for every plan.
        $when = $data['when'] ?? (filled($data['launch_date'] ?? null) ? 'date' : 'now');
        $date = $when === 'now' ? today() : Carbon::parse($data['launch_date'])->startOfDay();

        if (! $scheduler->available($date, $data['plan'], $product)) {
            return back()->withInput()->withErrors(['launch_date' => match (true) {
                $data['plan'] === 'pro' => 'Pro spots for that day are taken. Pick another day.',
                $date->isToday() => 'Today’s board is full. Pick a launch date instead.',
                default => 'That day is full. Pick another one.',
            }]);
        }

        if ($data['plan'] === 'free') {
            $firstBooking = ! in_array($product->status, ['pending', 'approved']);
            $review = config('rankedlaunch.review_listings');

            $product->plan = 'free';
            $product->launch_date = $date;
            $product->status = $review ? 'pending' : 'approved';
            $product->save();

            if ($firstBooking) {
                ActivityLog::record('booking', $date->isToday()
                    ? "{$product->name} was listed for today"
                    : "{$product->name} booked a free launch for {$date->format('M j')}", $product);
                app(MakerMailer::class)->send($product, 'booked');
            }

            $message = match (true) {
                ! $review && $date->isToday() => "{$product->name} is live on today’s board.",
                ! $review => "{$product->name} is booked for {$date->format('M j, Y')}.",
                $date->isToday() => "{$product->name} is submitted. It goes on today’s board as soon as it’s approved.",
                default => "{$product->name} is booked for {$date->format('M j, Y')}. We’ll review it shortly.",
            };

            return redirect()->route('launch.hub', $product)->with('status', $message);
        }

        $product->requested_plan = $data['plan'];
        $product->requested_launch_date = $date;
        if ($product->status === 'draft') {
            $product->status = 'awaiting_payment';
        }
        $product->save();

        $gateway = $payments->gateway();

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'provider' => $gateway->name(),
            'plan' => $data['plan'],
            'amount' => $pricing->priceFor($data['plan']),
            'is_promo' => $pricing->isPromoPlan($data['plan']),
            'status' => 'pending',
        ]);

        if (! $gateway->configured()) {
            if (app()->environment('local', 'testing')) {
                return redirect()->route('checkout.simulate', $payment);
            }

            return back()->withErrors(['plan' => 'Payments are not set up yet. Please try again later.']);
        }

        return redirect()->away($gateway->checkoutUrl($product, $payment));
    }

    private function validated(Request $request, bool $logoRequired): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'url' => ['required', 'url:http,https', 'max:255'],
            'tagline' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:40', 'max:3000'],
            'category_id' => ['required', 'exists:categories,id'],
            'pricing_type' => ['required', Rule::in(array_keys(Product::PRICING_TYPES))],
            'logo' => [$logoRequired ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=64,min_height=64'],
            'screenshots' => ['nullable', 'array', 'max:4'],
            'screenshots.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ]);

        unset($data['logo'], $data['screenshots']);

        return $data;
    }

    private function storeFiles(Request $request, Product $product): void
    {
        if ($request->hasFile('logo')) {
            $product->logo = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('screenshots')) {
            $product->screenshots = collect($request->file('screenshots'))
                ->map(fn ($file) => $file->store('screenshots', 'public'))
                ->all();
        }
    }

    private function authorizeOwner(Request $request, Product $product): void
    {
        abort_unless($request->user()->id === $product->user_id || $request->user()->is_admin, 403);
    }
}
