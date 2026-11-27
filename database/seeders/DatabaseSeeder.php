<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $categories = Category::all()->keyBy('name');

        // Everything below is local demo data. On the live site only categories are seeded;
        // create your admin by signing up, then: php artisan rankedlaunch:make-admin you@example.com
        if (! app()->isLocal()) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@rankedlaunch.test'],
            ['name' => 'Admin', 'password' => 'password']
        );
        $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $maker = User::firstOrCreate(
            ['email' => 'maker@rankedlaunch.test'],
            ['name' => 'Demo Maker', 'password' => 'password']
        );

        // [name, domain, tagline, category, pricing, days ago (0 = today), plan]
        $demo = [
            ['Raycast', 'raycast.com', 'A blazingly fast, extendable launcher for your Mac', 'Productivity', 'freemium', 0, 'featured'],
            ['Linear', 'linear.app', 'Issue tracking built for high-performance teams', 'Developer Tools', 'freemium', 0, 'pro'],
            ['Plausible', 'plausible.io', 'Simple, privacy-friendly website analytics', 'Analytics', 'paid', 0, 'pro'],
            ['Cal.com', 'cal.com', 'Open scheduling infrastructure for everyone', 'Productivity', 'freemium', 0, 'free'],
            ['Resend', 'resend.com', 'Email API built for developers', 'Developer Tools', 'freemium', 0, 'pro'],
            ['Framer', 'framer.com', 'Design and publish websites that look great', 'Design', 'freemium', 0, 'free'],
            ['Tally', 'tally.so', 'The simplest way to create forms, for free', 'No-Code', 'freemium', 0, 'free'],
            ['Ahrefs', 'ahrefs.com', 'SEO tools to grow your search traffic', 'SEO', 'paid', 1, 'pro'],
            ['Perplexity', 'perplexity.ai', 'An answer engine for curious minds', 'AI Tools', 'freemium', 1, 'featured'],
            ['Supabase', 'supabase.com', 'The open source Postgres development platform', 'Developer Tools', 'freemium', 1, 'pro'],
            ['Beehiiv', 'beehiiv.com', 'The newsletter platform built for growth', 'Marketing', 'freemium', 1, 'free'],
            ['Lemon Squeezy', 'lemonsqueezy.com', 'Payments, tax and subscriptions for software', 'Finance', 'paid', 1, 'pro'],
            ['Apollo', 'apollo.io', 'Find, contact and close your ideal buyers', 'Sales & Leads', 'freemium', 1, 'free'],
            ['Excalidraw', 'excalidraw.com', 'Virtual whiteboard for sketching hand-drawn diagrams', 'Design', 'free', 2, 'free'],
            ['PostHog', 'posthog.com', 'Product analytics, session replay and feature flags', 'Analytics', 'freemium', 2, 'pro'],
            ['Webflow', 'webflow.com', 'Build sites visually, with the power of code', 'No-Code', 'freemium', 3, 'pro'],
            ['Typefully', 'typefully.com', 'Write, schedule and grow on X and LinkedIn', 'Marketing', 'freemium', 3, 'free'],
            ['Cursor', 'cursor.com', 'The AI code editor', 'AI Tools', 'freemium', 4, 'pro'],
            ['Notion', 'notion.so', 'The connected workspace for docs, wikis and projects', 'Productivity', 'freemium', 9, 'pro'],
            ['Figma', 'figma.com', 'Where teams design together', 'Design', 'freemium', 12, 'pro'],
            ['Semrush', 'semrush.com', 'Online visibility management platform', 'SEO', 'paid', 15, 'free'],
            ['Clay', 'clay.com', 'Data enrichment and outreach, automated', 'Sales & Leads', 'paid', 20, 'pro'],
        ];

        foreach ($demo as [$name, $domain, $tagline, $category, $pricing, $daysAgo, $plan]) {
            $launch = today()->subDays($daysAgo);

            Product::updateOrCreate(['slug' => Str::slug($name)], [
                'user_id' => $maker->id,
                'category_id' => $categories[$category]->id,
                'name' => $name,
                'url' => "https://{$domain}",
                'tagline' => $tagline,
                'description' => "{$name} — {$tagline}.\n\nThis is demo content for local previews. Real listings will show the maker's own description, screenshots and links here.",
                'logo' => "https://www.google.com/s2/favicons?sz=128&domain={$domain}",
                'pricing_type' => $pricing,
                'plan' => $plan,
                'status' => 'approved',
                'launch_date' => $launch,
                'is_dofollow' => $plan !== 'free',
                'featured_until' => $plan === 'featured' ? now()->addDays(7 - $daysAgo) : null,
                'votes_count' => random_int(8, 240),
            ]);
        }
    }
}
