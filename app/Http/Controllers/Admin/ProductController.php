<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LaunchEmail;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Services\MakerMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public const STATUSES = ['draft', 'awaiting_payment', 'pending', 'approved', 'rejected'];

    public function index(Request $request)
    {
        $status = $request->query('status');
        $plan = $request->query('plan');
        $q = trim((string) $request->query('q'));

        $products = Product::with('user', 'category')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('url', 'like', "%{$q}%")->orWhere('tagline', 'like', "%{$q}%")))
            ->when($status === 'live', fn ($query) => $query->live())
            ->when($status === 'scheduled', fn ($query) => $query->where('status', 'approved')->whereDate('launch_date', '>', today()))
            ->when(in_array($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($plan, fn ($query) => $query->where('plan', $plan))
            ->when($status === 'pending', fn ($query) => $query->orderByRaw("plan = 'free'")->orderBy('launch_date'), fn ($query) => $query->latest())
            ->paginate(30)
            ->withQueryString();

        $counts = Product::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.products.index', compact('products', 'status', 'plan', 'q', 'counts'));
    }

    public function edit(Product $product)
    {
        $product->load('user', 'payments');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('sort')->get(),
            'activity' => ActivityLog::where('product_id', $product->id)->latest('created_at')->limit(15)->get(),
            'newAccountVotes' => $product->votes()->whereHas('user', fn ($q) => $q->where('created_at', '>=', now()->subDay()))->count(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('products')->ignore($product->id)],
            'url' => ['required', 'url:http,https', 'max:255'],
            'tagline' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'pricing_type' => ['required', Rule::in(array_keys(Product::PRICING_TYPES))],
            'plan' => ['required', Rule::in(array_keys(config('rankedlaunch.plans')))],
            'status' => ['required', Rule::in(self::STATUSES)],
            'launch_date' => ['nullable', 'date'],
            'featured_until' => ['nullable', 'date'],
            'is_dofollow' => ['boolean'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        } else {
            unset($data['logo']);
        }

        $data['is_dofollow'] = $request->boolean('is_dofollow');

        $product->fill($data);
        $changes = array_keys($product->getDirty());
        $product->save();

        if ($changes) {
            ActivityLog::record('moderation', "Admin edited {$product->name}: ".implode(', ', $changes), $product, $request->user());
        }

        return back()->with('status', 'Saved.');
    }

    public function approve(Request $request, Product $product, MakerMailer $mailer)
    {
        $wasApproved = $product->status === 'approved';
        $product->update(['status' => 'approved', 'rejection_reason' => null]);

        if (! $wasApproved) {
            ActivityLog::record('review', "{$product->name} approved", $product, $request->user());
            $mailer->send($product, 'approved');
        }

        return back()->with('status', "{$product->name} approved.");
    }

    public function reject(Request $request, Product $product, MakerMailer $mailer)
    {
        $product->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason') ?: 'Does not meet our listing guidelines.',
        ]);

        ActivityLog::record('review', "{$product->name} rejected: {$product->rejection_reason}", $product, $request->user());
        $mailer->send($product, 'rejected');

        return back()->with('status', "{$product->name} rejected.");
    }

    public function feature(Request $request, Product $product)
    {
        $product->update(['featured_until' => $product->isFeatured() ? null : now()->addDays(7)]);
        ActivityLog::record('moderation', $product->isFeatured() ? "{$product->name} featured for 7 days" : "{$product->name} unfeatured", $product, $request->user());

        return back()->with('status', $product->isFeatured() ? "{$product->name} featured for 7 days." : "{$product->name} unfeatured.");
    }

    /** Recalculate votes_count from the votes table (after removing fake votes). */
    public function recount(Product $product)
    {
        $product->update(['votes_count' => $product->votes()->count()]);

        return back()->with('status', "Votes recounted: {$product->votes_count}.");
    }

    public function email(Request $request, Product $product, MakerMailer $mailer)
    {
        $type = $request->validate(['type' => ['required', Rule::in(LaunchEmail::TYPES)]])['type'];

        return back()->with('status', $mailer->send($product->load('user'), $type) ? "“{$type}” email sent." : 'Email could not be sent. Check System for mail settings.');
    }

    public function destroy(Request $request, Product $product)
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm deletion.']);

        $name = $product->name;
        if ($product->logo && ! str_starts_with($product->logo, 'http')) {
            Storage::disk('public')->delete($product->logo);
        }
        Storage::disk('public')->delete($product->screenshots ?? []);
        $product->delete();

        ActivityLog::record('moderation', "Deleted product {$name}", user: $request->user());

        return redirect()->route('admin.products.index')->with('status', "{$name} deleted.");
    }
}
