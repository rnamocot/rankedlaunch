<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Engagement: saved products, "notify me" reminders for upcoming launches,
 * comment upvotes, and daily activity streaks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('launch_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'notified_at']);
        });

        Schema::create('comment_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'comment_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedInteger('votes_count')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('streak_days')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('streak_last_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['streak_days', 'longest_streak', 'streak_last_date']);
        });
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('votes_count');
        });
        Schema::dropIfExists('comment_votes');
        Schema::dropIfExists('launch_reminders');
        Schema::dropIfExists('bookmarks');
    }
};
