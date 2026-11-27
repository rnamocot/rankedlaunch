<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-editable overrides for config/rankedlaunch.php (prices, offer, queue).
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Everything that happens on the site, for the admin activity feed.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('message');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable();
            $table->string('ban_reason')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('refunded_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['banned_at', 'ban_reason']));
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
    }
};
