<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Saved admin settings from the three-plan era: the old Featured plan is now Pro,
 * so its saved price and feature days move to Pro; keys that no longer exist are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $saved = DB::table('settings')->pluck('value', 'key');

        foreach (['plans.featured.price' => 'plans.pro.price', 'plans.featured.featured_days' => 'plans.pro.featured_days'] as $old => $new) {
            if ($saved->has($old)) {
                DB::table('settings')->updateOrInsert(['key' => $new], ['value' => $saved[$old], 'updated_at' => now()]);
            }
        }

        DB::table('settings')->whereIn('key', [
            'plans.featured.price', 'plans.featured.featured_days', 'featured_slots_per_day', 'free_wait_days',
        ])->delete();

        Cache::forget('rankedlaunch.settings');
    }

    public function down(): void
    {
        //
    }
};
