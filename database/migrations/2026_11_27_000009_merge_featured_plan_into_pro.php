<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Pricing is now Free + Pro. The old "featured" plan becomes Pro (it had everything Pro has). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->where('plan', 'featured')->update(['plan' => 'pro']);
        DB::table('products')->where('requested_plan', 'featured')->update(['requested_plan' => 'pro']);
        DB::table('payments')->where('plan', 'featured')->update(['plan' => 'pro']);
    }

    public function down(): void
    {
        // Not reversible: the two plans were merged.
    }
};
