<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

/**
 * Machine-readable entry points: robots.txt, llms.txt (for AI assistants),
 * an RSS feed and a public JSON feed of launches.
 */
class DiscoveryController extends Controller
{
    public function robots()
    {
        $sitemap = route('sitemap');

        // Search engines and AI assistants are welcome on public pages.
        $body = <<<TXT
        User-agent: *
        Allow: /
        Disallow: /admin
        Disallow: /dashboard
        Disallow: /submit
        Disallow: /checkout
        Disallow: /webhooks

        # AI assistants and answer engines: welcome. See /llms.txt
        User-agent: GPTBot
        User-agent: OAI-SearchBot
        User-agent: ChatGPT-User
        User-agent: ClaudeBot
        User-agent: Claude-SearchBot
        User-agent: Claude-User
        User-agent: PerplexityBot
        User-agent: Perplexity-User
        User-agent: Google-Extended
        User-agent: Applebot-Extended
        Allow: /
        Disallow: /admin
        Disallow: /dashboard
        Disallow: /submit
        Disallow: /checkout

        Sitemap: {$sitemap}
        TXT;

        return response($body)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function llms()
    {
        $top = Product::launchedBetween(today()->startOfWeek(), today())->with('category')->ranked()->limit(10)->get();
        $categories = Category::whereHas('products', fn ($q) => $q->live())->orderBy('sort')->get();

        return response()
            ->view('discovery.llms', compact('top', 'categories'))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function feed()
    {
        $products = Product::live()->with('category')->latest('launch_date')->ranked()->limit(50)->get();

        return response()
            ->view('discovery.feed', compact('products'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function json()
    {
        $map = fn (Product $p) => [
            'name' => $p->name,
            'tagline' => $p->tagline,
            'url' => route('product', $p),
            'website' => $p->url,
            'category' => $p->category?->name,
            'pricing' => $p->pricing_type,
            'launch_date' => $p->launch_date->toDateString(),
            'upvotes' => $p->votes_count,
            'logo' => $p->logoUrl(),
        ];

        return response()->json([
            'name' => 'RankedLaunch',
            'description' => 'Daily ranked product launches from indie makers.',
            'generated_at' => now()->toIso8601String(),
            'today' => Product::live()->whereDate('launch_date', today())->with('category')->ranked()->get()->map($map),
            'this_week' => Product::launchedBetween(today()->startOfWeek(), today())->with('category')->ranked()->limit(30)->get()->map($map),
        ])->header('Cache-Control', 'public, max-age=300')
          ->header('Access-Control-Allow-Origin', '*');
    }
}
