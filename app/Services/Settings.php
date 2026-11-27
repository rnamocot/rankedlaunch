<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Admin-editable settings. Each key is a path in config/rankedlaunch.php;
 * saved values override the file at boot, so the rest of the app just reads config().
 */
class Settings
{
    private const CACHE_KEY = 'rankedlaunch.settings';

    /** key => [label, type, help] */
    public const FIELDS = [
        'Payments' => [
            'payments_enabled' => ['Accept payments (sell Pro)', 'bool', 'Off: the site is free (early access) and Pro is hidden. Turn on only after your payment provider has approved your account.'],
        ],
        'Launch offer' => [
            'promo.enabled' => ['Offer is on', 'bool', 'Turn the discounted launch offer on or off.'],
            'promo.price' => ['Offer price (USD)', 'money', 'What the first buyers pay for Pro.'],
            'promo.limit' => ['Number of offer spots', 'int', 'After this many paid sales, the price goes back to normal.'],
            'promo.label' => ['Offer name', 'string', 'Shown in the banner, e.g. “Launch offer”.'],
        ],
        'Prices' => [
            'plans.pro.price' => ['Pro (USD)', 'money', 'Regular one-time price, after the launch offer.'],
            'plans.pro.featured_days' => ['Days featured', 'int', 'How long Pro launches stay featured on the homepage, category and Discover.'],
        ],
        'Community' => [
            'chat_enabled' => ['Global chat', 'bool', 'Anonymous global chat on public pages. Turn off any time.'],
        ],
        'Listings' => [
            'review_listings' => ['Review listings before they go live', 'bool', 'On: you approve each new listing. Off: listings go live instantly (you can still remove them).'],
            'free_slots_per_day' => ['Free launches per day', 'int', 'Cap on free launches each day, so the board doesn’t get flooded.'],
            'paid_slots_per_day' => ['Pro launches per day', 'int', 'Every Pro launch is featured. Keep this small so features stay valuable.'],
        ],
    ];

    public static function keys(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::FIELDS)));
    }

    /** Apply saved overrides on top of config/rankedlaunch.php. */
    public function apply(): void
    {
        // Only keys that still exist: old saved values must never recreate removed config.
        $allowed = array_flip(self::keys());
        foreach ($this->all() as $key => $value) {
            if (isset($allowed[$key])) {
                config(["rankedlaunch.$key" => $value]);
            }
        }
    }

    public function all(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return DB::table('settings')->pluck('value', 'key')
                    ->map(fn ($v) => json_decode($v, true))
                    ->all();
            });
        } catch (Throwable) {
            return []; // e.g. before the database exists
        }
    }

    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
            );
        }

        Cache::forget(self::CACHE_KEY);
        $this->apply();
    }
}
