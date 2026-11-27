<?php

return [

    'name' => 'RankedLaunch',

    'tagline' => 'Launch your product. Climb the ranks.',

    // Shown on About, Terms, Privacy and Refunds. Fill these in before going live.
    'contact_email' => env('RL_CONTACT_EMAIL', 'hello@rankedlaunch.com'),
    'legal_name' => env('RL_LEGAL_NAME', 'RankedLaunch'),
    'legal_updated' => 'November 27, 2026',

    // Privacy-friendly analytics (optional). Plausible: set the domain. Umami: script URL + website ID.
    'analytics' => [
        'plausible_domain' => env('PLAUSIBLE_DOMAIN'),
        'umami_src' => env('UMAMI_SRC'),
        'umami_website_id' => env('UMAMI_WEBSITE_ID'),
    ],

    // Listings and launches. Every plan can list today or pick any future date.
    // When true, new listings wait for admin approval; when false they go live instantly.
    'review_listings' => (bool) env('RL_REVIEW_LISTINGS', true),
    'max_days_ahead' => 90,
    // Anonymous global chat ("Maker lounge") on public pages.
    'chat_enabled' => (bool) env('RL_CHAT_ENABLED', true),
    'free_slots_per_day' => (int) env('RL_FREE_SLOTS_PER_DAY', 25),
    // Pro launches per day. Every Pro launch is featured, so this keeps the homepage rotation valuable.
    'paid_slots_per_day' => (int) env('RL_PAID_SLOTS_PER_DAY', 5),
    // How many Pro launches the homepage Featured row shows at once (rotates evenly).
    'featured_on_homepage' => 3,

    // Plans (prices in USD cents). variant_id = LemonSqueezy variant for that plan.
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'features' => [
                'List today or pick any launch day',
                'Ranked on the daily board',
                'Launch hub: stats, share kit and badges',
                'Nofollow link',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 3900,
            'variant_id' => env('LEMONSQUEEZY_VARIANT_PRO'),
            'featured_days' => 7,
            'features' => [
                'Everything in Free',
                'Dofollow backlink that stays (SEO value)',
                'Featured on the homepage for 7 days, first on launch day',
                'Pinned at the top of your category for 7 days',
                'Shown first in Discover for 7 days',
                '“Featured” label next to your launch',
                'Priority review, usually same day',
            ],
        ],
    ],

    // Limited launch offer: the first `limit` purchases of `plan` cost `price`,
    // then it switches back to the plan's normal price automatically.
    'promo' => [
        'enabled' => (bool) env('RL_PROMO_ENABLED', true),
        'plan' => 'pro',
        'price' => (int) env('RL_PROMO_PRICE', 2000),
        'limit' => (int) env('RL_PROMO_LIMIT', 50),
        'label' => 'Launch offer',
    ],

    // polar or lemonsqueezy
    'payment_provider' => env('PAYMENT_PROVIDER', 'polar'),

    'polar' => [
        'access_token' => env('POLAR_ACCESS_TOKEN'),
        'webhook_secret' => env('POLAR_WEBHOOK_SECRET'),
        'sandbox' => (bool) env('POLAR_SANDBOX', false),
        // Polar product IDs. pro_promo is the discounted launch-offer product.
        'products' => [
            'pro' => env('POLAR_PRODUCT_PRO'),
            'pro_promo' => env('POLAR_PRODUCT_PRO_PROMO'),
        ],
    ],

    'lemonsqueezy' => [
        'api_key' => env('LEMONSQUEEZY_API_KEY'),
        'store_id' => env('LEMONSQUEEZY_STORE_ID'),
        'webhook_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET'),
    ],

];
