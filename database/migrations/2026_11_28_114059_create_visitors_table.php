<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous visitors: one row per browser (a random cookie, stored hashed).
 * Powers "N here right now", visitors today and all-time visitors. No IPs, no personal data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_hash', 64)->unique();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
