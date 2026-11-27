<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // When each scheduled email went out, so a re-run never double-sends.
            $table->timestamp('notified_tomorrow_at')->nullable();
            $table->timestamp('notified_live_at')->nullable();
            $table->timestamp('notified_results_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['notified_tomorrow_at', 'notified_live_at', 'notified_results_at']);
        });
    }
};
