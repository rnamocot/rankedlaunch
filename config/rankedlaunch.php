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

    // Launch queue
    'free_wait_days' => (int) env('RL_FREE_WAIT_DAYS', 28),
    'free_slots_per_day' => (int) env('RL_FREE_SLOTS_PER_DAY', 10),
    'paid_slots_per_day' => (int) env('RL_PAID_SLOTS_PER_DAY', 10),
    // Pro launches per day (counted inside paid_slots_per_day). Keeps the Featured rotation small and fair.
    'featured_slots_per_day' => (int) env('RL_FEATURED_SLOTS_PER_DAY', 2),
    // How many Pro launches the homepage Featured row shows at once (rotates evenly).
    'featured_on_homepage' => 3,
    'paid_max_days_ahead' => 90,

    // Plans (prices in USD cents). variant_id = LemonSqueezy variant for that plan.
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'features' => [
                'Joins the launch queue',
                'Listed on your launch day',
                'Nofollow link',
            ],
        ],
        'pro' => [
            'name' => 'Listing',
            'price' => 2900,
            'variant_id' => env('LEMONSQUEEZY_VARIANT_PRO'),
            'features' => [
                'Skip the queue, pick your date',
                'Dofollow backlink',
                'Launch badge for your site',
                'Kept in rankings forever',
            ],
        ],
        'featured' => [
            'name' => 'Pro',
            'price' => 3900,
            'variant_id' => env('LEMONSQUEEZY_VARIANT_FEATURED'),
            'featured_days' => 7,
            'features' => [
                'Everything in Listing',
                'Featured on the homepage for 7 days, first on your launch day',
                'Pinned at the top of your category for 7 days',
                '“Featured” label next to your launch',
                'Only 2 Pro launches per day',
                'Priority review within 24h',
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
            'featured' => env('POLAR_PRODUCT_FEATURED'),
        ],
    ],

    'lemonsqueezy' => [
        'api_key' => env('LEMONSQUEEZY_API_KEY'),
        'store_id' => env('LEMONSQUEEZY_STORE_ID'),
        'webhook_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET'),
    ],

];
